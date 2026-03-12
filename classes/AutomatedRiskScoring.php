<?php
/**
 * AutomatedRiskScoring - Automated Risk Scoring Engine
 * RBI Engineering Suite
 *
 * Provides automated, ML-enhanced risk scoring including:
 * - Dynamic POF/COF calculation
 * - Fleet-wide batch scoring
 * - Monte Carlo risk simulation
 * - What-if scenario analysis
 * - Risk-based inspection optimization
 * - Risk alert generation
 */
class AutomatedRiskScoring
{
    private Database $db;
    private MLEngine $ml;

    public function __construct()
    {
        $this->db = new Database();
        $this->ml = new MLEngine();
    }

    // =========================================================================
    // AUTO SCORE ASSET
    // =========================================================================

    /**
     * Automatically calculate risk score using all available data.
     * No manual input needed -- pulls from inspection history, corrosion data,
     * operating conditions, damage mechanisms, age, and design data.
     *
     * @param int $assetId
     * @return array Complete risk score breakdown
     */
    public function autoScoreAsset(int $assetId): array
    {
        $asset = $this->db->query(
            "SELECT ar.*, dd.nominal_thickness_mm, dd.minimum_required_thickness_mm,
                    dd.design_pressure_mpa, dd.design_temperature_c,
                    dd.corrosion_allowance_mm, dd.material_spec, dd.joint_efficiency
             FROM asset_registry ar
             LEFT JOIN design_data dd ON dd.asset_id = ar.id
             WHERE ar.id = ?",
            [$assetId]
        )->fetch();

        if (!$asset) {
            return ['success' => false, 'error' => 'Asset not found'];
        }

        // Calculate POF and COF
        $pof = $this->calculateDynamicPOF($assetId);
        $cof = $this->calculateDynamicCOF($assetId);

        if (!$pof['success'] || !$cof['success']) {
            return ['success' => false, 'error' => 'Failed to calculate POF/COF'];
        }

        $pofScore = $pof['pof_score'];
        $cofScore = $cof['cof_score'];

        // Overall risk = POF x COF (both on 1-5 scale)
        $overallRisk = $pofScore * $cofScore;

        // Determine risk category
        $riskCategory = $this->riskCategoryFromScore($overallRisk);

        // Health index
        $health = $this->ml->calculateHealthIndex($assetId);
        $healthIndex = $health['success'] ? $health['health_index'] : null;

        // Build input data snapshot
        $inputData = [
            'pof_components' => $pof['components'] ?? [],
            'cof_components' => $cof['components'] ?? [],
            'health_index'   => $healthIndex,
            'asset_age'      => $pof['asset_age'] ?? null,
        ];

        // Store risk score
        $scoreId = $this->db->insert('risk_scores', [
            'asset_id'       => $assetId,
            'overall_risk'   => round($overallRisk, 4),
            'pof_score'      => round($pofScore, 4),
            'cof_score'      => round($cofScore, 4),
            'health_index'   => $healthIndex,
            'risk_category'  => $riskCategory,
            'scoring_method' => 'ml_enhanced',
            'input_data'     => json_encode($inputData),
            'scored_at'      => date('Y-m-d H:i:s'),
            'scored_by'      => $_SESSION['user_id'] ?? null,
        ]);

        // Check for alerts
        $this->checkForAlerts($assetId, $overallRisk, $riskCategory, $pof, $cof);

        return [
            'success'        => true,
            'score_id'       => $scoreId,
            'asset_id'       => $assetId,
            'asset_tag'      => $asset['asset_tag'],
            'asset_name'     => $asset['asset_name'],
            'overall_risk'   => round($overallRisk, 4),
            'pof_score'      => round($pofScore, 4),
            'cof_score'      => round($cofScore, 4),
            'health_index'   => $healthIndex,
            'risk_category'  => $riskCategory,
            'pof_details'    => $pof,
            'cof_details'    => $cof,
        ];
    }

    // =========================================================================
    // BATCH SCORE ALL ASSETS
    // =========================================================================

    /**
     * Score all in-service assets with progress tracking.
     *
     * @return array Batch scoring results
     */
    public function batchScoreAllAssets(): array
    {
        $assets = $this->db->query(
            "SELECT id, asset_tag, asset_name FROM asset_registry WHERE status = 'in_service' ORDER BY id"
        )->fetchAll();

        $total = count($assets);
        $results = [
            'total'     => $total,
            'scored'    => 0,
            'failed'    => 0,
            'scores'    => [],
            'started_at' => date('Y-m-d H:i:s'),
        ];

        foreach ($assets as $idx => $asset) {
            try {
                $score = $this->autoScoreAsset((int)$asset['id']);
                if ($score['success']) {
                    $results['scored']++;
                    $results['scores'][] = [
                        'asset_id'      => $asset['id'],
                        'asset_tag'     => $asset['asset_tag'],
                        'overall_risk'  => $score['overall_risk'],
                        'risk_category' => $score['risk_category'],
                        'health_index'  => $score['health_index'],
                    ];
                } else {
                    $results['failed']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
            }
        }

        $results['completed_at'] = date('Y-m-d H:i:s');
        return $results;
    }

    // =========================================================================
    // DYNAMIC POF
    // =========================================================================

    /**
     * Dynamic POF that updates based on:
     * - Time since last inspection (degradation factor)
     * - ML-predicted corrosion rate
     * - Active damage mechanisms
     * - Operating excursions
     * - Weibull reliability
     *
     * @param int $assetId
     * @return array POF score (1-5 scale) and component breakdown
     */
    public function calculateDynamicPOF(int $assetId): array
    {
        $asset = $this->db->query(
            "SELECT ar.*, dd.nominal_thickness_mm, dd.minimum_required_thickness_mm,
                    dd.corrosion_allowance_mm
             FROM asset_registry ar
             LEFT JOIN design_data dd ON dd.asset_id = ar.id
             WHERE ar.id = ?",
            [$assetId]
        )->fetch();

        if (!$asset) {
            return ['success' => false, 'error' => 'Asset not found'];
        }

        $age = 0;
        if ($asset['installation_date']) {
            $age = (new DateTime($asset['installation_date']))->diff(new DateTime())->days / 365.25;
        }

        // --- Component 1: Thinning POF (30%) ---
        $thinningScore = 1.0;
        $latestRate = $this->db->query(
            "SELECT long_term_rate_mm_yr FROM corrosion_rate_history
             WHERE asset_id = ? ORDER BY period_end_date DESC LIMIT 1",
            [$assetId]
        )->fetch();

        if ($latestRate) {
            $rate = (float)$latestRate['long_term_rate_mm_yr'];
            // Score based on rate severity
            if ($rate >= 0.5) $thinningScore = 5.0;
            elseif ($rate >= 0.25) $thinningScore = 4.0;
            elseif ($rate >= 0.12) $thinningScore = 3.0;
            elseif ($rate >= 0.05) $thinningScore = 2.0;
            else $thinningScore = 1.0;
        }

        // Check wall loss percentage
        $lastThickness = $this->db->query(
            "SELECT measured_thickness_mm FROM corrosion_rate_tracking
             WHERE asset_id = ? ORDER BY measurement_date DESC LIMIT 1",
            [$assetId]
        )->fetch();

        if ($lastThickness && $asset['nominal_thickness_mm']) {
            $wallLoss = 1 - (float)$lastThickness['measured_thickness_mm'] / (float)$asset['nominal_thickness_mm'];
            if ($wallLoss > 0.5) $thinningScore = max($thinningScore, 5.0);
            elseif ($wallLoss > 0.35) $thinningScore = max($thinningScore, 4.0);
            elseif ($wallLoss > 0.2) $thinningScore = max($thinningScore, 3.0);
        }

        // --- Component 2: Inspection Degradation Factor (20%) ---
        $inspDegradation = 1.0;
        $lastInspection = $this->db->query(
            "SELECT MAX(it.completion_date) as last_date
             FROM inspection_tasks it
             WHERE it.asset_id = ? AND it.status = 'completed'",
            [$assetId]
        )->fetch();

        $yearsSinceInspection = 5; // Default if never inspected
        if ($lastInspection && $lastInspection['last_date']) {
            $yearsSinceInspection = (new DateTime($lastInspection['last_date']))->diff(new DateTime())->days / 365.25;
        }

        // Inspection confidence degrades over time
        if ($yearsSinceInspection >= 10) $inspDegradation = 5.0;
        elseif ($yearsSinceInspection >= 7) $inspDegradation = 4.0;
        elseif ($yearsSinceInspection >= 5) $inspDegradation = 3.0;
        elseif ($yearsSinceInspection >= 3) $inspDegradation = 2.0;
        else $inspDegradation = 1.0;

        // --- Component 3: Damage Mechanisms (20%) ---
        $dmScore = 1.0;
        $dmCount = (int)$this->db->query(
            "SELECT COUNT(*) FROM asset_damage_mechanisms WHERE asset_id = ? AND active = 1",
            [$assetId]
        )->fetchColumn();

        if ($dmCount >= 5) $dmScore = 5.0;
        elseif ($dmCount >= 4) $dmScore = 4.0;
        elseif ($dmCount >= 3) $dmScore = 3.0;
        elseif ($dmCount >= 2) $dmScore = 2.0;
        else $dmScore = 1.0;

        // Check for high-susceptibility damage mechanisms
        $highSusc = (int)$this->db->query(
            "SELECT COUNT(*) FROM asset_damage_mechanisms
             WHERE asset_id = ? AND active = 1 AND susceptibility IN ('high','very_high')",
            [$assetId]
        )->fetchColumn();
        if ($highSusc > 0) $dmScore = min(5, $dmScore + $highSusc);

        // --- Component 4: Weibull Reliability (15%) ---
        $weibullScore = 2.0;
        try {
            $weibull = $this->ml->predictFailureProbability($assetId, 1);
            if ($weibull['success']) {
                $pof1yr = $weibull['pof_1yr'];
                if ($pof1yr >= 0.10) $weibullScore = 5.0;
                elseif ($pof1yr >= 0.05) $weibullScore = 4.0;
                elseif ($pof1yr >= 0.02) $weibullScore = 3.0;
                elseif ($pof1yr >= 0.005) $weibullScore = 2.0;
                else $weibullScore = 1.0;
            }
        } catch (\Throwable $e) {
            // Use default
        }

        // --- Component 5: Age / Equipment Factor (15%) ---
        $ageFactor = 1.0;
        if ($age >= 35) $ageFactor = 5.0;
        elseif ($age >= 25) $ageFactor = 4.0;
        elseif ($age >= 15) $ageFactor = 3.0;
        elseif ($age >= 8) $ageFactor = 2.0;
        else $ageFactor = 1.0;

        // Weighted composite POF
        $pofScore = 0.30 * $thinningScore
                  + 0.20 * $inspDegradation
                  + 0.20 * $dmScore
                  + 0.15 * $weibullScore
                  + 0.15 * $ageFactor;

        return [
            'success'    => true,
            'pof_score'  => round($pofScore, 4),
            'pof_category' => $this->scoreToCategoryNum($pofScore),
            'asset_age'  => round($age, 1),
            'components' => [
                'thinning'          => ['score' => round($thinningScore, 2), 'weight' => 0.30],
                'inspection_degrad' => ['score' => round($inspDegradation, 2), 'weight' => 0.20, 'years_since' => round($yearsSinceInspection, 1)],
                'damage_mechanisms' => ['score' => round($dmScore, 2), 'weight' => 0.20, 'dm_count' => $dmCount],
                'weibull'           => ['score' => round($weibullScore, 2), 'weight' => 0.15],
                'age_equipment'     => ['score' => round($ageFactor, 2), 'weight' => 0.15, 'age_years' => round($age, 1)],
            ],
        ];
    }

    // =========================================================================
    // DYNAMIC COF
    // =========================================================================

    /**
     * Dynamic COF based on:
     * - Fluid properties (toxicity, flammability)
     * - Operating pressure/temperature
     * - Proximity to personnel
     * - Environmental sensitivity
     * - Business criticality
     * - Production impact
     *
     * @param int $assetId
     * @return array COF score (1-5 scale) and component breakdown
     */
    public function calculateDynamicCOF(int $assetId): array
    {
        $asset = $this->db->query(
            "SELECT ar.*, dd.design_pressure_mpa, dd.design_temperature_c, dd.volume_m3
             FROM asset_registry ar
             LEFT JOIN design_data dd ON dd.asset_id = ar.id
             WHERE ar.id = ?",
            [$assetId]
        )->fetch();

        if (!$asset) {
            return ['success' => false, 'error' => 'Asset not found'];
        }

        $opData = $this->db->query(
            "SELECT * FROM operational_data WHERE asset_id = ? ORDER BY effective_date DESC LIMIT 1",
            [$assetId]
        )->fetch();

        // --- Component 1: Fluid Hazard (25%) ---
        $fluidScore = 2.0;
        if ($opData) {
            // H2S content check
            $h2s = (float)($opData['h2s_content_ppm'] ?? 0);
            if ($h2s > 100) $fluidScore = 5.0;
            elseif ($h2s > 20) $fluidScore = 4.0;
            elseif ($h2s > 5) $fluidScore = 3.0;

            // Fluid toxicity/flammability from service
            $hazardousFluids = ['hydrogen', 'h2s', 'ammonia', 'chlorine', 'hf', 'acid'];
            $service = strtolower($opData['fluid_service'] ?? '');
            foreach ($hazardousFluids as $hf) {
                if (str_contains($service, $hf)) {
                    $fluidScore = max($fluidScore, 4.0);
                    break;
                }
            }
        }

        // --- Component 2: Pressure/Temperature Severity (20%) ---
        $ptScore = 2.0;
        if ($opData) {
            $pressure = (float)($opData['operating_pressure_mpa'] ?? 0);
            $temp = (float)($opData['operating_temperature_c'] ?? 25);

            if ($pressure > 10 || $temp > 400) $ptScore = 5.0;
            elseif ($pressure > 5 || $temp > 300) $ptScore = 4.0;
            elseif ($pressure > 2 || $temp > 200) $ptScore = 3.0;
            elseif ($pressure > 0.5 || $temp > 100) $ptScore = 2.0;
            else $ptScore = 1.0;
        }

        // --- Component 3: Inventory / Release Potential (20%) ---
        $inventoryScore = 2.0;
        $volume = (float)($asset['volume_m3'] ?? 0);
        if ($volume > 100) $inventoryScore = 5.0;
        elseif ($volume > 50) $inventoryScore = 4.0;
        elseif ($volume > 10) $inventoryScore = 3.0;
        elseif ($volume > 1) $inventoryScore = 2.0;
        else $inventoryScore = 1.0;

        // --- Component 4: Business Criticality (20%) ---
        $critMap = ['critical' => 5.0, 'high' => 4.0, 'medium' => 3.0, 'low' => 1.5];
        $businessScore = $critMap[$asset['criticality']] ?? 3.0;

        // Check production impact from COF table
        $cofData = $this->db->query(
            "SELECT cof.production_loss_per_day_usd, cof.estimated_downtime_days,
                    cof.total_consequence_cost_usd
             FROM consequence_of_failure cof
             JOIN risk_assessments ra ON cof.assessment_id = ra.id
             WHERE ra.asset_id = ?
             ORDER BY ra.assessment_date DESC LIMIT 1",
            [$assetId]
        )->fetch();

        if ($cofData && $cofData['production_loss_per_day_usd']) {
            $dailyLoss = (float)$cofData['production_loss_per_day_usd'];
            if ($dailyLoss > 500000) $businessScore = max($businessScore, 5.0);
            elseif ($dailyLoss > 100000) $businessScore = max($businessScore, 4.0);
        }

        // --- Component 5: Environmental / Safety (15%) ---
        $envSafetyScore = 2.0;
        if ($opData) {
            $env = $opData['external_environment'] ?? 'industrial';
            if (in_array($env, ['marine', 'arctic', 'tropical'])) {
                $envSafetyScore = max($envSafetyScore, 3.0);
            }
        }

        // Check if near personnel or environmentally sensitive
        if ($cofData) {
            $personnel = (int)($cofData['total_consequence_cost_usd'] ?? 0);
            if ($personnel > 1000000) $envSafetyScore = 5.0;
            elseif ($personnel > 500000) $envSafetyScore = 4.0;
        }

        // Weighted composite COF
        $cofScore = 0.25 * $fluidScore
                  + 0.20 * $ptScore
                  + 0.20 * $inventoryScore
                  + 0.20 * $businessScore
                  + 0.15 * $envSafetyScore;

        return [
            'success'    => true,
            'cof_score'  => round($cofScore, 4),
            'cof_category' => $this->scoreToCategoryNum($cofScore),
            'components' => [
                'fluid_hazard'     => ['score' => round($fluidScore, 2), 'weight' => 0.25],
                'pressure_temp'    => ['score' => round($ptScore, 2), 'weight' => 0.20],
                'inventory'        => ['score' => round($inventoryScore, 2), 'weight' => 0.20],
                'business_crit'    => ['score' => round($businessScore, 2), 'weight' => 0.20],
                'env_safety'       => ['score' => round($envSafetyScore, 2), 'weight' => 0.15],
            ],
        ];
    }

    // =========================================================================
    // RISK TREND
    // =========================================================================

    /**
     * Historical risk score trend showing how risk evolves over time.
     *
     * @param int $assetId
     * @param int $periods Number of historical periods to return
     * @return array Risk score history
     */
    public function getRiskTrend(int $assetId, int $periods = 12): array
    {
        $scores = $this->db->query(
            "SELECT id, overall_risk, pof_score, cof_score, health_index,
                    risk_category, scoring_method, scored_at
             FROM risk_scores
             WHERE asset_id = ?
             ORDER BY scored_at DESC
             LIMIT ?",
            [$assetId, $periods]
        )->fetchAll();

        $scores = array_reverse($scores);

        // Also pull from risk_assessments for historical data
        $assessments = $this->db->query(
            "SELECT ra.assessment_date, ra.inherent_risk_score, ra.residual_risk_score,
                    ra.inherent_risk_level, ra.residual_risk_level
             FROM risk_assessments ra
             WHERE ra.asset_id = ?
             ORDER BY ra.assessment_date DESC
             LIMIT ?",
            [$assetId, $periods]
        )->fetchAll();

        $assessments = array_reverse($assessments);

        // Determine trend direction
        $trendDirection = 'stable';
        if (count($scores) >= 2) {
            $recent = end($scores);
            $previous = $scores[count($scores) - 2];
            $change = $recent['overall_risk'] - $previous['overall_risk'];
            if ($change > 0.5) $trendDirection = 'increasing';
            elseif ($change < -0.5) $trendDirection = 'decreasing';
        }

        return [
            'success'    => true,
            'trend'      => $trendDirection,
            'scores'     => $scores,
            'assessments'=> $assessments,
        ];
    }

    // =========================================================================
    // FLEET RISK SUMMARY
    // =========================================================================

    /**
     * Fleet-wide risk statistics.
     *
     * @return array Risk distribution, top riskers, by-unit breakdown
     */
    public function getFleetRiskSummary(): array
    {
        // Get latest score for each asset
        $latestScores = $this->db->query(
            "SELECT rs.*, ar.asset_tag, ar.asset_name, ar.asset_type, ar.criticality,
                    eh.name as unit_name
             FROM risk_scores rs
             INNER JOIN (
                SELECT asset_id, MAX(id) as max_id FROM risk_scores GROUP BY asset_id
             ) latest ON rs.id = latest.max_id
             JOIN asset_registry ar ON rs.asset_id = ar.id
             LEFT JOIN equipment_hierarchy eh ON ar.hierarchy_id = eh.id
             ORDER BY rs.overall_risk DESC"
        )->fetchAll();

        // Distribution
        $distribution = ['very_low' => 0, 'low' => 0, 'medium' => 0, 'high' => 0, 'very_high' => 0];
        $riskValues = [];
        $byType = [];
        $byUnit = [];

        foreach ($latestScores as $score) {
            $cat = $score['risk_category'];
            $distribution[$cat] = ($distribution[$cat] ?? 0) + 1;
            $riskValues[] = (float)$score['overall_risk'];

            // By asset type
            $type = $score['asset_type'];
            if (!isset($byType[$type])) {
                $byType[$type] = ['count' => 0, 'avg_risk' => 0, 'total_risk' => 0];
            }
            $byType[$type]['count']++;
            $byType[$type]['total_risk'] += (float)$score['overall_risk'];

            // By unit
            $unit = $score['unit_name'] ?? 'Unassigned';
            if (!isset($byUnit[$unit])) {
                $byUnit[$unit] = ['count' => 0, 'avg_risk' => 0, 'total_risk' => 0];
            }
            $byUnit[$unit]['count']++;
            $byUnit[$unit]['total_risk'] += (float)$score['overall_risk'];
        }

        // Calculate averages
        foreach ($byType as &$t) {
            $t['avg_risk'] = $t['count'] > 0 ? round($t['total_risk'] / $t['count'], 2) : 0;
        }
        foreach ($byUnit as &$u) {
            $u['avg_risk'] = $u['count'] > 0 ? round($u['total_risk'] / $u['count'], 2) : 0;
        }

        // Top 10 highest risk
        $topRiskers = array_slice($latestScores, 0, 10);

        return [
            'success'      => true,
            'total_scored' => count($latestScores),
            'distribution' => $distribution,
            'statistics'   => [
                'mean_risk'   => !empty($riskValues) ? round(MLEngine::mean($riskValues), 2) : 0,
                'median_risk' => !empty($riskValues) ? round(MLEngine::percentile($riskValues, 50), 2) : 0,
                'std_risk'    => !empty($riskValues) ? round(MLEngine::stddev($riskValues), 2) : 0,
                'max_risk'    => !empty($riskValues) ? round(max($riskValues), 2) : 0,
                'min_risk'    => !empty($riskValues) ? round(min($riskValues), 2) : 0,
            ],
            'top_riskers'  => array_map(fn($s) => [
                'asset_id'      => $s['asset_id'],
                'asset_tag'     => $s['asset_tag'],
                'asset_name'    => $s['asset_name'],
                'overall_risk'  => round((float)$s['overall_risk'], 2),
                'pof_score'     => round((float)$s['pof_score'], 2),
                'cof_score'     => round((float)$s['cof_score'], 2),
                'risk_category' => $s['risk_category'],
                'health_index'  => $s['health_index'],
            ], $topRiskers),
            'by_type'      => $byType,
            'by_unit'      => $byUnit,
        ];
    }

    // =========================================================================
    // RISK ALERTS
    // =========================================================================

    /**
     * Auto-generate alerts for risk changes, threshold breaches, overdue inspections.
     *
     * @return array Generated alerts
     */
    public function generateRiskAlerts(): array
    {
        $alerts = [];

        // 1. Risk level changes (compare latest two scores per asset)
        $riskChanges = $this->db->query(
            "SELECT rs1.asset_id, rs1.overall_risk as current_risk, rs1.risk_category as current_cat,
                    rs2.overall_risk as previous_risk, rs2.risk_category as previous_cat,
                    ar.asset_tag, ar.asset_name
             FROM risk_scores rs1
             INNER JOIN (SELECT asset_id, MAX(id) as max_id FROM risk_scores GROUP BY asset_id) latest
                ON rs1.id = latest.max_id
             LEFT JOIN (
                SELECT rs3.asset_id, rs3.overall_risk, rs3.risk_category
                FROM risk_scores rs3
                INNER JOIN (
                    SELECT asset_id, MAX(id) as max_id
                    FROM risk_scores
                    WHERE id NOT IN (SELECT MAX(id) FROM risk_scores GROUP BY asset_id)
                    GROUP BY asset_id
                ) prev ON rs3.id = prev.max_id
             ) rs2 ON rs1.asset_id = rs2.asset_id
             JOIN asset_registry ar ON rs1.asset_id = ar.id
             WHERE rs2.overall_risk IS NOT NULL
               AND rs1.overall_risk > rs2.overall_risk * 1.2"
        )->fetchAll();

        foreach ($riskChanges as $rc) {
            $increase = round(((float)$rc['current_risk'] - (float)$rc['previous_risk']) / (float)$rc['previous_risk'] * 100, 1);
            $severity = $increase > 50 ? 'critical' : ($increase > 25 ? 'warning' : 'info');

            $alertId = $this->db->insert('risk_alerts', [
                'asset_id'   => $rc['asset_id'],
                'alert_type' => 'risk_increase',
                'severity'   => $severity,
                'message'    => sprintf(
                    'Risk score for %s (%s) increased by %.1f%% from %.2f to %.2f',
                    $rc['asset_name'], $rc['asset_tag'],
                    $increase, $rc['previous_risk'], $rc['current_risk']
                ),
                'data'       => json_encode($rc),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $alerts[] = ['id' => $alertId, 'type' => 'risk_increase', 'severity' => $severity];
        }

        // 2. Overdue inspections affecting risk
        $overdueInsp = $this->db->query(
            "SELECT it.asset_id, ar.asset_tag, ar.asset_name,
                    COUNT(*) as overdue_count,
                    MIN(it.due_date) as oldest_due_date
             FROM inspection_tasks it
             JOIN asset_registry ar ON it.asset_id = ar.id
             WHERE it.status IN ('pending','overdue') AND it.due_date < CURDATE()
             GROUP BY it.asset_id, ar.asset_tag, ar.asset_name
             HAVING overdue_count > 0"
        )->fetchAll();

        foreach ($overdueInsp as $oi) {
            $daysPast = (new DateTime($oi['oldest_due_date']))->diff(new DateTime())->days;
            $severity = $daysPast > 365 ? 'critical' : ($daysPast > 180 ? 'warning' : 'info');

            $alertId = $this->db->insert('risk_alerts', [
                'asset_id'   => $oi['asset_id'],
                'alert_type' => 'overdue_inspection',
                'severity'   => $severity,
                'message'    => sprintf(
                    '%s (%s) has %d overdue inspection(s), oldest due %s (%d days overdue)',
                    $oi['asset_name'], $oi['asset_tag'],
                    $oi['overdue_count'], $oi['oldest_due_date'], $daysPast
                ),
                'data'       => json_encode($oi),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $alerts[] = ['id' => $alertId, 'type' => 'overdue_inspection', 'severity' => $severity];
        }

        // 3. Accelerating degradation
        $accelDeg = $this->db->query(
            "SELECT crh1.asset_id, ar.asset_tag, ar.asset_name,
                    crh1.short_term_rate_mm_yr as current_rate,
                    crh2.short_term_rate_mm_yr as previous_rate
             FROM corrosion_rate_history crh1
             INNER JOIN (SELECT asset_id, MAX(id) as max_id FROM corrosion_rate_history GROUP BY asset_id) l1
                ON crh1.id = l1.max_id
             LEFT JOIN (
                SELECT crh3.asset_id, crh3.short_term_rate_mm_yr
                FROM corrosion_rate_history crh3
                INNER JOIN (
                    SELECT asset_id, MAX(id) as max_id
                    FROM corrosion_rate_history
                    WHERE id NOT IN (SELECT MAX(id) FROM corrosion_rate_history GROUP BY asset_id)
                    GROUP BY asset_id
                ) l2 ON crh3.id = l2.max_id
             ) crh2 ON crh1.asset_id = crh2.asset_id
             JOIN asset_registry ar ON crh1.asset_id = ar.id
             WHERE crh2.previous_rate IS NOT NULL
               AND crh1.short_term_rate_mm_yr > crh2.previous_rate * 1.5"
        )->fetchAll();

        foreach ($accelDeg as $ad) {
            $alertId = $this->db->insert('risk_alerts', [
                'asset_id'   => $ad['asset_id'],
                'alert_type' => 'accelerating_degradation',
                'severity'   => 'warning',
                'message'    => sprintf(
                    'Accelerating corrosion detected on %s (%s): rate increased from %.3f to %.3f mm/yr',
                    $ad['asset_name'], $ad['asset_tag'],
                    $ad['previous_rate'], $ad['current_rate']
                ),
                'data'       => json_encode($ad),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $alerts[] = ['id' => $alertId, 'type' => 'accelerating_degradation', 'severity' => 'warning'];
        }

        return [
            'success'      => true,
            'alerts_generated' => count($alerts),
            'alerts'       => $alerts,
        ];
    }

    // =========================================================================
    // INSPECTION OPTIMIZATION
    // =========================================================================

    /**
     * Risk-based inspection interval optimization.
     * Recommend intervals that minimize total risk while respecting budget.
     *
     * @return array Recommended inspection intervals per asset
     */
    public function optimizeInspectionPlan(): array
    {
        // Get all scored assets
        $assets = $this->db->query(
            "SELECT rs.asset_id, rs.overall_risk, rs.pof_score, rs.cof_score,
                    rs.health_index, ar.asset_tag, ar.asset_name, ar.criticality,
                    (SELECT MAX(it.due_date) FROM inspection_tasks it WHERE it.asset_id = rs.asset_id AND it.status = 'completed') as last_insp,
                    (SELECT MIN(it.due_date) FROM inspection_tasks it WHERE it.asset_id = rs.asset_id AND it.status IN ('pending','in_progress')) as next_planned
             FROM risk_scores rs
             INNER JOIN (SELECT asset_id, MAX(id) as max_id FROM risk_scores GROUP BY asset_id) latest
                ON rs.id = latest.max_id
             JOIN asset_registry ar ON rs.asset_id = ar.id
             WHERE ar.status = 'in_service'
             ORDER BY rs.overall_risk DESC"
        )->fetchAll();

        $recommendations = [];

        foreach ($assets as $a) {
            $risk = (float)$a['overall_risk'];
            $pof = (float)$a['pof_score'];

            // Risk-based interval: higher risk = shorter interval
            if ($risk >= 20) $recInterval = 0.5;      // 6 months
            elseif ($risk >= 15) $recInterval = 1.0;   // 1 year
            elseif ($risk >= 10) $recInterval = 2.0;   // 2 years
            elseif ($risk >= 5) $recInterval = 3.0;    // 3 years
            else $recInterval = 5.0;                    // 5 years

            // Adjust for criticality
            $critMultiplier = match ($a['criticality']) {
                'critical' => 0.7,
                'high'     => 0.85,
                'medium'   => 1.0,
                'low'      => 1.3,
                default    => 1.0,
            };
            $recInterval = round($recInterval * $critMultiplier, 1);

            // Calculate risk reduction benefit of inspection
            $riskReductionBenefit = $risk * 0.3; // Assume 30% risk reduction from inspection
            $estimatedInspCost = 5000; // Base cost estimate

            // Cost-benefit ratio
            $costBenefitRatio = ($estimatedInspCost > 0) ? round($riskReductionBenefit / $estimatedInspCost * 10000, 2) : 0;

            $recommendations[] = [
                'asset_id'              => $a['asset_id'],
                'asset_tag'             => $a['asset_tag'],
                'asset_name'            => $a['asset_name'],
                'current_risk'          => round($risk, 2),
                'criticality'           => $a['criticality'],
                'recommended_interval_years' => $recInterval,
                'current_next_planned'  => $a['next_planned'],
                'last_inspection'       => $a['last_insp'],
                'risk_reduction_benefit'=> round($riskReductionBenefit, 2),
                'cost_benefit_ratio'    => $costBenefitRatio,
                'priority'              => $risk >= 15 ? 'urgent' : ($risk >= 10 ? 'high' : ($risk >= 5 ? 'medium' : 'routine')),
            ];
        }

        return [
            'success'        => true,
            'total_assets'   => count($recommendations),
            'recommendations'=> $recommendations,
        ];
    }

    // =========================================================================
    // WHAT-IF ANALYSIS
    // =========================================================================

    /**
     * What-if scenario modeling.
     * Change parameters and see impact on risk score.
     *
     * @param int   $assetId
     * @param array $scenarios Array of scenario definitions, each with parameter changes
     * @return array Scenario results compared to baseline
     */
    public function whatIfAnalysis(int $assetId, array $scenarios): array
    {
        // Get current baseline
        $baseline = $this->autoScoreAsset($assetId);
        if (!$baseline['success']) {
            return ['success' => false, 'error' => 'Could not calculate baseline risk'];
        }

        $results = [
            'success'  => true,
            'baseline' => [
                'overall_risk'  => $baseline['overall_risk'],
                'pof_score'     => $baseline['pof_score'],
                'cof_score'     => $baseline['cof_score'],
                'risk_category' => $baseline['risk_category'],
            ],
            'scenarios' => [],
        ];

        foreach ($scenarios as $idx => $scenario) {
            $scenarioResult = [
                'name'        => $scenario['name'] ?? 'Scenario ' . ($idx + 1),
                'parameters'  => $scenario,
            ];

            // Adjust POF based on scenario
            $pofAdjust = 1.0;
            $cofAdjust = 1.0;

            // Inspection interval change
            if (isset($scenario['inspection_interval_years'])) {
                $interval = (float)$scenario['inspection_interval_years'];
                if ($interval <= 1) $pofAdjust *= 0.6;
                elseif ($interval <= 2) $pofAdjust *= 0.75;
                elseif ($interval <= 3) $pofAdjust *= 0.85;
                elseif ($interval <= 5) $pofAdjust *= 1.0;
                else $pofAdjust *= 1.3;
            }

            // Damage mechanism addition/removal
            if (isset($scenario['dm_change'])) {
                $dmChange = (int)$scenario['dm_change'];
                $pofAdjust *= (1 + $dmChange * 0.15);
            }

            // Operating temperature change
            if (isset($scenario['temp_change_pct'])) {
                $tempChange = (float)$scenario['temp_change_pct'] / 100;
                $pofAdjust *= (1 + $tempChange * 0.3);
                $cofAdjust *= (1 + $tempChange * 0.1);
            }

            // Operating pressure change
            if (isset($scenario['pressure_change_pct'])) {
                $pressChange = (float)$scenario['pressure_change_pct'] / 100;
                $cofAdjust *= (1 + $pressChange * 0.2);
            }

            // Apply coating/lining
            if (isset($scenario['apply_coating']) && $scenario['apply_coating']) {
                $pofAdjust *= 0.5;
            }

            // Apply inhibitor
            if (isset($scenario['apply_inhibitor']) && $scenario['apply_inhibitor']) {
                $pofAdjust *= 0.6;
            }

            $newPof = $baseline['pof_score'] * max(0.1, $pofAdjust);
            $newCof = $baseline['cof_score'] * max(0.1, $cofAdjust);
            $newRisk = $newPof * $newCof;
            $riskChange = $newRisk - $baseline['overall_risk'];
            $riskChangePct = ($baseline['overall_risk'] > 0)
                ? round($riskChange / $baseline['overall_risk'] * 100, 1) : 0;

            $scenarioResult['pof_score'] = round($newPof, 4);
            $scenarioResult['cof_score'] = round($newCof, 4);
            $scenarioResult['overall_risk'] = round($newRisk, 4);
            $scenarioResult['risk_category'] = $this->riskCategoryFromScore($newRisk);
            $scenarioResult['risk_change'] = round($riskChange, 4);
            $scenarioResult['risk_change_pct'] = $riskChangePct;

            $results['scenarios'][] = $scenarioResult;
        }

        return $results;
    }

    // =========================================================================
    // MONTE CARLO SIMULATION
    // =========================================================================

    /**
     * Monte Carlo simulation for risk uncertainty quantification.
     * Varies input parameters to get risk distribution.
     *
     * @param int $assetId
     * @param int $iterations Number of simulation iterations
     * @return array Risk distribution statistics
     */
    public function calculateRiskOfRisk(int $assetId, int $iterations = 1000): array
    {
        // Get baseline scores
        $pof = $this->calculateDynamicPOF($assetId);
        $cof = $this->calculateDynamicCOF($assetId);

        if (!$pof['success'] || !$cof['success']) {
            return ['success' => false, 'error' => 'Could not calculate baseline risk'];
        }

        $basePof = $pof['pof_score'];
        $baseCof = $cof['cof_score'];

        // Define uncertainty ranges for each component
        $pofComponents = $pof['components'];
        $cofComponents = $cof['components'];

        $riskSamples = [];
        $pofSamples = [];
        $cofSamples = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Vary POF components with +-20% normal distribution
            $simPof = 0;
            foreach ($pofComponents as $comp) {
                $baseScore = $comp['score'];
                $weight = $comp['weight'];
                $variation = $this->normalRandom($baseScore, $baseScore * 0.2);
                $variation = max(1, min(5, $variation));
                $simPof += $weight * $variation;
            }

            // Vary COF components
            $simCof = 0;
            foreach ($cofComponents as $comp) {
                $baseScore = $comp['score'];
                $weight = $comp['weight'];
                $variation = $this->normalRandom($baseScore, $baseScore * 0.15);
                $variation = max(1, min(5, $variation));
                $simCof += $weight * $variation;
            }

            $simRisk = $simPof * $simCof;
            $riskSamples[] = $simRisk;
            $pofSamples[] = $simPof;
            $cofSamples[] = $simCof;
        }

        sort($riskSamples);

        // Build histogram (20 bins)
        $minRisk = min($riskSamples);
        $maxRisk = max($riskSamples);
        $binWidth = ($maxRisk - $minRisk) / 20;
        $histogram = [];
        for ($b = 0; $b < 20; $b++) {
            $binStart = $minRisk + $b * $binWidth;
            $binEnd = $binStart + $binWidth;
            $count = 0;
            foreach ($riskSamples as $s) {
                if ($s >= $binStart && ($s < $binEnd || ($b === 19 && $s <= $binEnd))) {
                    $count++;
                }
            }
            $histogram[] = [
                'bin_start' => round($binStart, 2),
                'bin_end'   => round($binEnd, 2),
                'count'     => $count,
                'frequency' => round($count / $iterations, 4),
            ];
        }

        $runId = $this->generateUUID();

        // Store results
        $this->db->insert('monte_carlo_results', [
            'asset_id'          => $assetId,
            'simulation_run_id' => $runId,
            'iterations'        => $iterations,
            'mean_risk'         => round(MLEngine::mean($riskSamples), 6),
            'p10_risk'          => round(MLEngine::percentile($riskSamples, 10), 6),
            'p50_risk'          => round(MLEngine::percentile($riskSamples, 50), 6),
            'p90_risk'          => round(MLEngine::percentile($riskSamples, 90), 6),
            'std_dev'           => round(MLEngine::stddev($riskSamples), 6),
            'distribution_data' => json_encode($histogram),
            'parameters'        => json_encode([
                'base_pof' => $basePof,
                'base_cof' => $baseCof,
                'pof_variation' => 0.20,
                'cof_variation' => 0.15,
            ]),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        return [
            'success'         => true,
            'simulation_id'   => $runId,
            'iterations'      => $iterations,
            'baseline_risk'   => round($basePof * $baseCof, 4),
            'statistics'      => [
                'mean'    => round(MLEngine::mean($riskSamples), 4),
                'median'  => round(MLEngine::percentile($riskSamples, 50), 4),
                'std_dev' => round(MLEngine::stddev($riskSamples), 4),
                'p5'      => round(MLEngine::percentile($riskSamples, 5), 4),
                'p10'     => round(MLEngine::percentile($riskSamples, 10), 4),
                'p25'     => round(MLEngine::percentile($riskSamples, 25), 4),
                'p50'     => round(MLEngine::percentile($riskSamples, 50), 4),
                'p75'     => round(MLEngine::percentile($riskSamples, 75), 4),
                'p90'     => round(MLEngine::percentile($riskSamples, 90), 4),
                'p95'     => round(MLEngine::percentile($riskSamples, 95), 4),
                'min'     => round(min($riskSamples), 4),
                'max'     => round(max($riskSamples), 4),
            ],
            'histogram'       => $histogram,
            'category_probs'  => [
                'very_low'  => round(count(array_filter($riskSamples, fn($r) => $r < 4)) / $iterations, 4),
                'low'       => round(count(array_filter($riskSamples, fn($r) => $r >= 4 && $r < 8)) / $iterations, 4),
                'medium'    => round(count(array_filter($riskSamples, fn($r) => $r >= 8 && $r < 15)) / $iterations, 4),
                'high'      => round(count(array_filter($riskSamples, fn($r) => $r >= 15 && $r < 20)) / $iterations, 4),
                'very_high' => round(count(array_filter($riskSamples, fn($r) => $r >= 20)) / $iterations, 4),
            ],
        ];
    }

    /**
     * Generate a normally distributed random number (Box-Muller transform)
     */
    private function normalRandom(float $mean, float $std): float
    {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        $z = sqrt(-2 * log(max(1e-10, $u1))) * cos(2 * M_PI * $u2);
        return $mean + $z * $std;
    }

    /**
     * Generate a UUID v4
     */
    private function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Check for and create alerts when scoring an asset
     */
    private function checkForAlerts(int $assetId, float $risk, string $category, array $pof, array $cof): void
    {
        // Threshold breach
        if ($risk >= 20) {
            $this->db->insert('risk_alerts', [
                'asset_id'   => $assetId,
                'alert_type' => 'threshold_breach',
                'severity'   => 'critical',
                'message'    => sprintf('Risk score %.2f exceeds critical threshold (20). Immediate review recommended.', $risk),
                'data'       => json_encode(['risk' => $risk, 'category' => $category]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } elseif ($risk >= 15) {
            $this->db->insert('risk_alerts', [
                'asset_id'   => $assetId,
                'alert_type' => 'threshold_breach',
                'severity'   => 'warning',
                'message'    => sprintf('Risk score %.2f exceeds high threshold (15). Review recommended.', $risk),
                'data'       => json_encode(['risk' => $risk, 'category' => $category]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function riskCategoryFromScore(float $score): string
    {
        if ($score >= 20) return 'very_high';
        if ($score >= 15) return 'high';
        if ($score >= 8) return 'medium';
        if ($score >= 4) return 'low';
        return 'very_low';
    }

    private function scoreToCategoryNum(float $score): int
    {
        if ($score >= 4.5) return 5;
        if ($score >= 3.5) return 4;
        if ($score >= 2.5) return 3;
        if ($score >= 1.5) return 2;
        return 1;
    }
}
