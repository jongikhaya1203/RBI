<?php
/**
 * InspectionPlan - Inspection planning, scheduling, and task management
 *
 * Calculates inspection intervals based on risk, selects appropriate
 * inspection strategies, and manages the inspection task lifecycle.
 */
class InspectionPlan
{
    private Database $db;

    /** Inspection strategy options */
    private const STRATEGIES = [
        'visual'    => ['name' => 'Visual Inspection',             'effectiveness' => 'E', 'cost_factor' => 1.0],
        'ut_spot'   => ['name' => 'UT Spot Thickness',             'effectiveness' => 'D', 'cost_factor' => 2.0],
        'ut_grid'   => ['name' => 'UT Grid/Scan',                  'effectiveness' => 'C', 'cost_factor' => 4.0],
        'rt'        => ['name' => 'Radiographic Testing',          'effectiveness' => 'C', 'cost_factor' => 5.0],
        'mt'        => ['name' => 'Magnetic Particle Testing',     'effectiveness' => 'C', 'cost_factor' => 3.0],
        'pt'        => ['name' => 'Penetrant Testing',             'effectiveness' => 'C', 'cost_factor' => 2.5],
        'paut'      => ['name' => 'Phased Array UT',               'effectiveness' => 'B', 'cost_factor' => 7.0],
        'tofd'      => ['name' => 'Time of Flight Diffraction',    'effectiveness' => 'B', 'cost_factor' => 8.0],
        'aut'       => ['name' => 'Automated UT Scanning',         'effectiveness' => 'A', 'cost_factor' => 10.0],
        'ect'       => ['name' => 'Eddy Current Testing',          'effectiveness' => 'B', 'cost_factor' => 6.0],
        'iris'      => ['name' => 'IRIS Tube Inspection',          'effectiveness' => 'B', 'cost_factor' => 8.0],
        'mfl'       => ['name' => 'Magnetic Flux Leakage',         'effectiveness' => 'B', 'cost_factor' => 7.0],
        'acoustic'  => ['name' => 'Acoustic Emission Testing',     'effectiveness' => 'C', 'cost_factor' => 6.0],
        'internal'  => ['name' => 'Internal Visual / Entry',       'effectiveness' => 'B', 'cost_factor' => 9.0],
        'rbi_review'=> ['name' => 'RBI Desktop Review',            'effectiveness' => 'E', 'cost_factor' => 0.5],
    ];

    /** Effectiveness to factor map (API 581 Table 7.2) */
    private const EFFECTIVENESS_FACTORS = [
        'A' => 0.1,   // Highly Effective
        'B' => 0.2,   // Usually Effective
        'C' => 0.5,   // Fairly Effective
        'D' => 0.8,   // Poorly Effective
        'E' => 1.0,   // Ineffective
    ];

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── Plan CRUD ───────────────────────────────────────────────────

    /**
     * Create an inspection plan for an asset
     *
     * @return int  Plan ID
     */
    public function createPlan(array $data): int
    {
        if (empty($data['asset_id'])) {
            throw new InvalidArgumentException('Asset ID is required.');
        }

        $interval = $data['interval_months'] ?? $this->calculateInterval($data['asset_id']);

        $nextDue = date('Y-m-d', strtotime("+{$interval} months"));

        return $this->db->insert('inspection_plans', [
            'asset_id'          => $data['asset_id'],
            'plan_name'         => $data['plan_name'] ?? 'RBI Plan - Asset ' . $data['asset_id'],
            'plan_type'         => $data['plan_type'] ?? 'risk_based',
            'risk_level'        => $data['risk_level'] ?? null,
            'strategy'          => $data['strategy'] ?? null,
            'interval_months'   => $interval,
            'next_due_date'     => $nextDue,
            'scope'             => $data['scope'] ?? null,
            'priority'          => $data['priority'] ?? 'normal',
            'status'            => 'active',
            'created_by'        => $data['created_by'] ?? $_SESSION['auth_user_id'] ?? null,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get a plan by ID
     */
    public function getPlan(int $planId): ?array
    {
        return $this->db->query(
            "SELECT ip.*, a.name AS asset_name, a.asset_tag
             FROM inspection_plans ip
             JOIN assets a ON ip.asset_id = a.id
             WHERE ip.id = ?",
            [$planId]
        )->fetch();
    }

    /**
     * Update an existing plan
     */
    public function updatePlan(int $planId, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('inspection_plans', $data, 'id = ?', [$planId]);
    }

    // ── Interval Calculation ────────────────────────────────────────

    /**
     * Calculate recommended inspection interval in months based on risk and remaining life
     *
     * Logic:
     *   1. Get remaining life from corrosion data
     *   2. Apply risk-based factor
     *   3. Cap at regulatory maximum and remaining-life fraction
     *
     * @return int  Interval in months
     */
    public function calculateInterval(int $assetId): int
    {
        $riskAssess = new RiskAssessment();
        $analytics  = new IntegrityAnalytics();

        // Get the latest risk level
        $latestRisk = $this->db->query(
            "SELECT risk_level, risk_value FROM risk_assessments
             WHERE asset_id = ? ORDER BY calculated_at DESC LIMIT 1",
            [$assetId]
        )->fetch();

        $riskLevel = $latestRisk['risk_level'] ?? 'M';

        // Base interval by risk level (months)
        $baseInterval = match ($riskLevel) {
            'VH' => 6,
            'H'  => 12,
            'MH' => 24,
            'M'  => 48,
            'L'  => 72,
            default => 48,
        };

        // Remaining life constraint: interval should not exceed half of remaining life
        try {
            $rlData = $analytics->calculateRemainingLife($assetId);
            $remainingMonths = ($rlData['remaining_life_years'] ?? 999) * 12;
            $maxByRL = (int) ($remainingMonths * 0.5);
            $baseInterval = min($baseInterval, max($maxByRL, 3));
        } catch (\Throwable $e) {
            // If remaining life cannot be calculated, rely on risk-based interval
        }

        // Regulatory cap (e.g., 10 years max per API 510/570)
        $regulatoryCap = 120; // months
        $interval = min($baseInterval, $regulatoryCap);

        return max($interval, 3); // minimum 3 months
    }

    // ── Strategy Selection ──────────────────────────────────────────

    /**
     * Select inspection strategies based on active damage mechanisms
     *
     * @return array  Recommended strategies with rationale
     */
    public function selectStrategy(int $assetId): array
    {
        $dm = new DamageMechanism();
        $mechanisms = $dm->getMechanismsByAsset($assetId);

        $strategies = [];

        foreach ($mechanisms as $mech) {
            if (!$mech['active']) continue;

            $category = $mech['category'];
            $susceptibility = $mech['susceptibility'];

            $recommended = match ($category) {
                'thinning'      => $this->thinningStrategies($susceptibility),
                'cracking'      => $this->crackingStrategies($susceptibility),
                'high_temp'     => $this->highTempStrategies($susceptibility),
                'external'      => $this->externalStrategies($susceptibility),
                'mechanical'    => $this->mechanicalStrategies($susceptibility),
                'metallurgical' => $this->metallurgicalStrategies($susceptibility),
                default         => [['strategy' => 'visual', 'reason' => 'Default visual inspection.']],
            };

            foreach ($recommended as $r) {
                $key = $r['strategy'];
                if (!isset($strategies[$key])) {
                    $strategies[$key] = self::STRATEGIES[$key] ?? ['name' => $key];
                    $strategies[$key]['reasons'] = [];
                }
                $strategies[$key]['reasons'][] = $mech['name'] . ': ' . $r['reason'];
            }
        }

        // If no mechanisms found, default to visual
        if (empty($strategies)) {
            $strategies['visual'] = array_merge(self::STRATEGIES['visual'], [
                'reasons' => ['No active damage mechanisms identified; baseline visual inspection recommended.'],
            ]);
        }

        return array_values($strategies);
    }

    // ── Task Management ─────────────────────────────────────────────

    /**
     * Create an inspection task
     *
     * @return int  Task ID
     */
    public function createTask(array $data): int
    {
        return $this->db->insert('inspection_tasks', [
            'plan_id'         => $data['plan_id'],
            'asset_id'        => $data['asset_id'],
            'task_type'       => $data['task_type'] ?? 'inspection',
            'strategy'        => $data['strategy'] ?? 'visual',
            'description'     => $data['description'] ?? '',
            'due_date'        => $data['due_date'],
            'priority'        => $data['priority'] ?? 'normal',
            'assigned_to'     => $data['assigned_to'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'status'          => 'pending',
            'created_by'      => $_SESSION['auth_user_id'] ?? null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Prioritize tasks using a weighted scoring model
     *
     * Score = (risk_weight * risk_value) + (overdue_weight * overdue_factor) + (criticality_weight * criticality_score)
     *
     * @return array  Tasks sorted by priority score
     */
    public function prioritizeTasks(array $filters = []): array
    {
        $where  = ['it.status IN ("pending", "overdue")'];
        $params = [];

        if (!empty($filters['facility_id'])) {
            $where[]  = 'a.facility_id = ?';
            $params[] = $filters['facility_id'];
        }

        $whereClause = implode(' AND ', $where);

        $tasks = $this->db->query(
            "SELECT it.*, a.name AS asset_name, a.asset_tag, a.criticality,
                    ip.risk_level, ra.risk_value
             FROM inspection_tasks it
             JOIN assets a ON it.asset_id = a.id
             LEFT JOIN inspection_plans ip ON it.plan_id = ip.id
             LEFT JOIN risk_assessments ra ON a.id = ra.asset_id
                AND ra.id = (SELECT MAX(id) FROM risk_assessments WHERE asset_id = a.id)
             WHERE {$whereClause}
             ORDER BY it.due_date ASC",
            $params
        )->fetchAll();

        // Calculate priority scores
        foreach ($tasks as &$task) {
            $riskScore = (float) ($task['risk_value'] ?? 0);

            $overdueDays = max(0, (strtotime('now') - strtotime($task['due_date'])) / 86400);
            $overdueFactor = min($overdueDays / 30, 5.0); // cap at 5

            $criticalityScore = match ($task['criticality'] ?? 'medium') {
                'very_high' => 5, 'high' => 4, 'medium' => 3, 'low' => 2, default => 1,
            };

            $task['priority_score'] = round(
                (0.5 * $riskScore) + (0.3 * $overdueFactor * 100) + (0.2 * $criticalityScore * 20),
                2
            );
        }
        unset($task);

        // Sort by priority score descending
        usort($tasks, fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);

        return $tasks;
    }

    /**
     * Get inspection schedule (upcoming tasks)
     */
    public function getSchedule(array $filters = [], int $limit = 50): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'it.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['from_date'])) {
            $where[]  = 'it.due_date >= ?';
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where[]  = 'it.due_date <= ?';
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[]  = 'it.assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query(
            "SELECT it.*, a.name AS asset_name, a.asset_tag, ip.plan_name,
                    u.first_name, u.last_name
             FROM inspection_tasks it
             JOIN assets a ON it.asset_id = a.id
             LEFT JOIN inspection_plans ip ON it.plan_id = ip.id
             LEFT JOIN users u ON it.assigned_to = u.id
             WHERE {$whereClause}
             ORDER BY it.due_date ASC
             LIMIT {$limit}",
            $params
        )->fetchAll();
    }

    /**
     * Record inspection findings against a task
     *
     * @return int  Finding ID
     */
    public function recordFindings(int $taskId, array $findings): int
    {
        $task = $this->db->find('inspection_tasks', $taskId);
        if (!$task) {
            throw new InvalidArgumentException("Task not found: {$taskId}");
        }

        return $this->db->transaction(function (Database $db) use ($taskId, $task, $findings) {
            // Insert finding
            $findingId = $db->insert('inspection_findings', [
                'task_id'             => $taskId,
                'asset_id'            => $task['asset_id'],
                'inspector_id'        => $findings['inspector_id'] ?? $_SESSION['auth_user_id'] ?? null,
                'inspection_date'     => $findings['inspection_date'] ?? date('Y-m-d'),
                'method_used'         => $findings['method_used'] ?? $task['strategy'],
                'measured_thickness'  => $findings['measured_thickness'] ?? null,
                'corrosion_rate'      => $findings['corrosion_rate'] ?? null,
                'condition_grade'     => $findings['condition_grade'] ?? null,
                'findings_summary'    => $findings['findings_summary'] ?? '',
                'recommendations'     => $findings['recommendations'] ?? '',
                'follow_up_required'  => $findings['follow_up_required'] ?? 0,
                'photos'              => $findings['photos'] ?? null,
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            // Mark task as completed
            $db->update('inspection_tasks', [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$taskId]);

            // Update asset condition if thickness was measured
            if (!empty($findings['measured_thickness'])) {
                $db->insert('inspection_readings', [
                    'asset_id'          => $task['asset_id'],
                    'reading_date'      => $findings['inspection_date'] ?? date('Y-m-d'),
                    'measured_thickness' => $findings['measured_thickness'],
                    'corrosion_rate'     => $findings['corrosion_rate'] ?? null,
                    'reading_location'   => $findings['reading_location'] ?? null,
                    'method'            => $findings['method_used'] ?? $task['strategy'],
                    'created_at'        => date('Y-m-d H:i:s'),
                ]);

                (new Asset())->updateCondition($task['asset_id'], [
                    'measured_thickness' => $findings['measured_thickness'],
                    'condition_grade'    => $findings['condition_grade'],
                    'inspection_date'    => $findings['inspection_date'] ?? date('Y-m-d'),
                ]);
            }

            return $findingId;
        });
    }

    // ── Strategy Recommendation Helpers ─────────────────────────────

    private function thinningStrategies(string $susceptibility): array
    {
        return match ($susceptibility) {
            'high'   => [
                ['strategy' => 'ut_grid',  'reason' => 'Grid UT for accurate wall loss mapping.'],
                ['strategy' => 'aut',      'reason' => 'Automated UT for comprehensive coverage.'],
            ],
            'medium' => [
                ['strategy' => 'ut_spot',  'reason' => 'Spot UT at known corrosion-prone locations.'],
                ['strategy' => 'rt',       'reason' => 'Profile RT for inaccessible areas.'],
            ],
            default  => [
                ['strategy' => 'visual',   'reason' => 'Visual for low-susceptibility thinning.'],
                ['strategy' => 'ut_spot',  'reason' => 'Confirmatory spot UT measurements.'],
            ],
        };
    }

    private function crackingStrategies(string $susceptibility): array
    {
        return match ($susceptibility) {
            'high'   => [
                ['strategy' => 'paut',  'reason' => 'Phased array UT for crack detection and sizing.'],
                ['strategy' => 'tofd',  'reason' => 'TOFD for accurate crack depth measurement.'],
            ],
            'medium' => [
                ['strategy' => 'mt',    'reason' => 'MT for surface-breaking cracks.'],
                ['strategy' => 'paut',  'reason' => 'PAUT for subsurface crack detection.'],
            ],
            default  => [
                ['strategy' => 'pt',    'reason' => 'PT for surface crack screening.'],
                ['strategy' => 'mt',    'reason' => 'MT at weld seams.'],
            ],
        };
    }

    private function highTempStrategies(string $susceptibility): array
    {
        return match ($susceptibility) {
            'high'   => [
                ['strategy' => 'paut',      'reason' => 'PAUT for HTHA/creep damage detection.'],
                ['strategy' => 'acoustic',  'reason' => 'AET for online creep monitoring.'],
                ['strategy' => 'internal',  'reason' => 'Internal entry for direct assessment.'],
            ],
            default  => [
                ['strategy' => 'ut_grid',  'reason' => 'UT for high-temperature degradation monitoring.'],
            ],
        };
    }

    private function externalStrategies(string $susceptibility): array
    {
        return match ($susceptibility) {
            'high'   => [
                ['strategy' => 'visual',   'reason' => 'Strip insulation and visual inspection.'],
                ['strategy' => 'ut_grid',  'reason' => 'UT grid survey under insulation.'],
            ],
            default  => [
                ['strategy' => 'visual',   'reason' => 'External visual inspection.'],
            ],
        };
    }

    private function mechanicalStrategies(string $susceptibility): array
    {
        return [
            ['strategy' => 'visual',   'reason' => 'Visual for fatigue crack indications.'],
            ['strategy' => 'mt',       'reason' => 'MT at high-stress areas.'],
        ];
    }

    private function metallurgicalStrategies(string $susceptibility): array
    {
        return [
            ['strategy' => 'internal',  'reason' => 'Internal assessment for metallurgical degradation.'],
            ['strategy' => 'paut',      'reason' => 'PAUT for embrittlement evaluation.'],
        ];
    }
}
