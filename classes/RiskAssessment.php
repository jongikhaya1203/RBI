<?php
/**
 * RiskAssessment - Risk-Based Inspection calculation engine
 *
 * Implements API 580/581 methodology for:
 *   - Probability of Failure (POF)
 *   - Consequence of Failure (COF)
 *   - Risk ranking and risk matrix placement
 *   - Damage mechanism assessment
 */
class RiskAssessment
{
    private Database $db;

    /*
     * 5 x 5 Risk Matrix Categories
     * POF categories : 1 (Improbable) .. 5 (Very Likely)
     * COF categories : A (Low) .. E (Very High)
     */
    private const POF_CATEGORIES = [1 => 'Improbable', 2 => 'Unlikely', 3 => 'Possible', 4 => 'Likely', 5 => 'Very Likely'];
    private const COF_CATEGORIES = ['A' => 'Low', 'B' => 'Medium-Low', 'C' => 'Medium', 'D' => 'Medium-High', 'E' => 'Very High'];

    /**
     * Risk matrix: [POF_category][COF_category] => risk level
     * L = Low, M = Medium, MH = Medium-High, H = High, VH = Very High
     */
    private const RISK_MATRIX = [
        5 => ['A' => 'MH', 'B' => 'H',  'C' => 'H',  'D' => 'VH', 'E' => 'VH'],
        4 => ['A' => 'M',  'B' => 'MH', 'C' => 'H',  'D' => 'H',  'E' => 'VH'],
        3 => ['A' => 'M',  'B' => 'M',  'C' => 'MH', 'D' => 'H',  'E' => 'H' ],
        2 => ['A' => 'L',  'B' => 'M',  'C' => 'M',  'D' => 'MH', 'E' => 'H' ],
        1 => ['A' => 'L',  'B' => 'L',  'C' => 'M',  'D' => 'M',  'E' => 'MH'],
    ];

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── Core Risk Calculations (API 580/581) ────────────────────────

    /**
     * Calculate Probability of Failure for an asset.
     *
     * POF = gff x Df(t) x FMS
     *   gff  = generic failure frequency for equipment type
     *   Df(t)= damage factor at time t (driven by thinning, cracking, etc.)
     *   FMS  = management systems factor (0.1 – 10)
     *
     * @return array  POF details including numeric value and category
     */
    public function calculatePOF(int $assetId, ?float $yearsFromNow = null): array
    {
        try {
            $asset = (new Asset())->getById($assetId);
            if (!$asset) {
                throw new InvalidArgumentException("Asset not found: {$assetId}");
            }

            // Age in years
            $installDate = $asset['install_date'] ?? $asset['commission_date'] ?? date('Y-m-d');
            $ageYears    = calculateAge($installDate);
            $evalTime    = $ageYears + ($yearsFromNow ?? 0);

            // 1. Generic failure frequency by equipment type (per API 581 Table 4.1)
            $gff = $this->getGenericFailureFrequency($asset['asset_type']);

            // 2. Damage factor – aggregate across all applicable mechanisms
            $damageFactor = $this->calculateTotalDamageFactor($assetId, $evalTime, $asset);

            // 3. Management Systems Factor (FMS)
            $fms = $this->getManagementSystemsFactor($asset['facility_id']);

            // POF = gff x Df(t) x FMS
            $pof = $gff * $damageFactor * $fms;

            // Categorize
            $category = $this->categorizePOF($pof);

            return [
                'asset_id'              => $assetId,
                'pof_value'             => round($pof, 8),
                'pof_category'          => $category,
                'generic_failure_freq'  => $gff,
                'damage_factor'         => round($damageFactor, 4),
                'management_factor'     => $fms,
                'evaluation_years'      => $evalTime,
                'calculated_at'         => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            error_log('[RBI] POF calculation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate Consequence of Failure for an asset.
     *
     * COF = max(COF_flammable, COF_toxic, COF_financial)
     *
     * @return array  COF details including numeric value and category
     */
    public function calculateCOF(int $assetId): array
    {
        try {
            $asset = (new Asset())->getById($assetId);
            if (!$asset) {
                throw new InvalidArgumentException("Asset not found: {$assetId}");
            }

            $opData = (new Asset())->getOperationalData($assetId);

            // Hole size probabilities per API 581 (small, medium, large, rupture)
            $holeSizes = $this->getHoleSizeDistribution($asset['asset_type']);

            // Release rate calculation (simplified)
            $releaseRate = $this->calculateReleaseRate(
                $asset['operating_pressure'] ?? 0,
                $asset['operating_temperature'] ?? 0,
                $opData['fluid_service'] ?? 'hydrocarbon',
                $holeSizes
            );

            // Flammable / explosive consequence area (m^2)
            $cofFlammable = $this->calculateFlammableConsequence($releaseRate, $opData);

            // Toxic consequence area
            $cofToxic = $this->calculateToxicConsequence($releaseRate, $opData);

            // Financial consequence (USD)
            $cofFinancial = $this->calculateFinancialConsequence($asset, $releaseRate);

            // Use maximum consequence dimension
            $cofValue = max($cofFlammable, $cofToxic, $cofFinancial);

            $category = $this->categorizeCOF($cofValue);

            return [
                'asset_id'          => $assetId,
                'cof_value'         => round($cofValue, 2),
                'cof_category'      => $category,
                'cof_flammable'     => round($cofFlammable, 2),
                'cof_toxic'         => round($cofToxic, 2),
                'cof_financial'     => round($cofFinancial, 2),
                'release_rate'      => round($releaseRate, 4),
                'calculated_at'     => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            error_log('[RBI] COF calculation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Full risk calculation – combines POF and COF
     *
     * @return array  Complete risk record
     */
    public function calculateRisk(int $assetId, ?float $yearsFromNow = null): array
    {
        $pof = $this->calculatePOF($assetId, $yearsFromNow);
        $cof = $this->calculateCOF($assetId);

        $riskLevel = self::RISK_MATRIX[$pof['pof_category']][$cof['cof_category']] ?? 'M';
        $riskValue = $pof['pof_value'] * $cof['cof_value'];

        $result = [
            'asset_id'       => $assetId,
            'risk_value'     => round($riskValue, 4),
            'risk_level'     => $riskLevel,
            'pof'            => $pof,
            'cof'            => $cof,
            'calculated_at'  => date('Y-m-d H:i:s'),
        ];

        // Persist the assessment
        $this->saveAssessment($result);

        return $result;
    }

    /**
     * Get the full 5x5 risk matrix with labels
     */
    public function getRiskMatrix(): array
    {
        return [
            'matrix'         => self::RISK_MATRIX,
            'pof_categories' => self::POF_CATEGORIES,
            'cof_categories' => self::COF_CATEGORIES,
            'risk_levels'    => [
                'L'  => ['label' => 'Low',         'color' => '#28a745'],
                'M'  => ['label' => 'Medium',      'color' => '#ffc107'],
                'MH' => ['label' => 'Medium-High', 'color' => '#fd7e14'],
                'H'  => ['label' => 'High',        'color' => '#dc3545'],
                'VH' => ['label' => 'Very High',   'color' => '#721c24'],
            ],
        ];
    }

    /**
     * Get risk rankings (sorted list of all assessed assets)
     */
    public function getRiskRanking(array $filters = [], int $limit = 50): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['facility_id'])) {
            $where[]  = 'a.facility_id = ?';
            $params[] = $filters['facility_id'];
        }
        if (!empty($filters['risk_level'])) {
            $where[]  = 'ra.risk_level = ?';
            $params[] = $filters['risk_level'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query(
            "SELECT ra.*, a.name AS asset_name, a.asset_tag, a.asset_type,
                    f.name AS facility_name
             FROM risk_assessments ra
             JOIN assets a ON ra.asset_id = a.id
             LEFT JOIN facilities f ON a.facility_id = f.id
             WHERE {$whereClause}
             AND ra.id IN (
                 SELECT MAX(id) FROM risk_assessments GROUP BY asset_id
             )
             ORDER BY ra.risk_value DESC
             LIMIT {$limit}",
            $params
        )->fetchAll();
    }

    /**
     * Assess which damage mechanisms apply to a given asset and their severity
     */
    public function assessDamageMechanisms(int $assetId): array
    {
        $dm = new DamageMechanism();
        return $dm->getMechanismsByAsset($assetId);
    }

    // ── API 581 Sub-Calculations ────────────────────────────────────

    /**
     * Generic failure frequency by equipment type (failures / year)
     * Per API 581 Table 4.1
     */
    private function getGenericFailureFrequency(string $assetType): float
    {
        $gffTable = [
            'vessel'          => 3.06e-5,
            'column'          => 3.06e-5,
            'reactor'         => 3.06e-5,
            'heat_exchanger'  => 3.06e-5,
            'tank'            => 7.0e-4,
            'piping'          => 2.80e-5,
            'pump'            => 3.0e-3,
            'valve'           => 7.0e-5,
            'boiler'          => 3.06e-5,
            'other'           => 3.06e-5,
        ];
        return $gffTable[$assetType] ?? 3.06e-5;
    }

    /**
     * Calculate aggregate damage factor across all active mechanisms
     *
     * Df_total = max(Df_thin, Df_ext) + Df_scc + Df_htha + ...
     * (per API 581 Part 2)
     */
    private function calculateTotalDamageFactor(int $assetId, float $ageYears, array $asset): float
    {
        $damageFactor = 1.0;

        // Thinning damage factor (general/localized corrosion)
        $dfThin = $this->calculateThinningDamageFactor($asset, $ageYears);

        // External corrosion damage factor
        $dfExt = $this->calculateExternalDamageFactor($asset, $ageYears);

        // Stress corrosion cracking damage factor
        $dfSCC = $this->calculateSCCDamageFactor($assetId, $asset);

        // HTHA damage factor
        $dfHTHA = $this->calculateHTHADamageFactor($asset);

        // Aggregate: max of thinning-type + additive cracking
        $damageFactor = max($dfThin, $dfExt) + $dfSCC + $dfHTHA;

        return max($damageFactor, 1.0);
    }

    /**
     * Thinning damage factor per API 581 Part 2, Section 4
     *
     * Art = (corrosion_rate x age) / (t_actual - t_min)
     * Map Art to Df_thin via lookup table
     */
    private function calculateThinningDamageFactor(array $asset, float $ageYears): float
    {
        $nominalThickness = $asset['nominal_thickness'] ?? 10.0;   // mm
        $minThickness     = $asset['minimum_thickness'] ?? 2.5;    // mm
        $corrosionRate    = $this->getLatestCorrosionRate($asset['id']) ?? 0.1; // mm/yr

        $tActual = $nominalThickness - ($corrosionRate * $ageYears);
        $artRatio = ($corrosionRate * $ageYears) / max($nominalThickness - $minThickness, 0.01);

        // Damage factor lookup (simplified from API 581 Table 5.11)
        if ($artRatio < 0.1)  return 1.0;
        if ($artRatio < 0.2)  return 2.0;
        if ($artRatio < 0.3)  return 5.0;
        if ($artRatio < 0.4)  return 10.0;
        if ($artRatio < 0.5)  return 50.0;
        if ($artRatio < 0.6)  return 100.0;
        if ($artRatio < 0.8)  return 500.0;
        return 1000.0;
    }

    /**
     * External corrosion damage factor (marine / industrial atmosphere)
     */
    private function calculateExternalDamageFactor(array $asset, float $ageYears): float
    {
        $operatingTemp = $asset['operating_temperature'] ?? 25;

        // External corrosion is significant between -12C and 175C (uninsulated)
        if ($operatingTemp < -12 || $operatingTemp > 175) {
            return 1.0;
        }

        $externalRate = 0.05; // mm/yr typical for carbon steel in industrial env
        $nominalThickness = $asset['nominal_thickness'] ?? 10.0;
        $minThickness     = $asset['minimum_thickness'] ?? 2.5;

        $artRatio = ($externalRate * $ageYears) / max($nominalThickness - $minThickness, 0.01);

        if ($artRatio < 0.1)  return 1.0;
        if ($artRatio < 0.2)  return 2.0;
        if ($artRatio < 0.4)  return 10.0;
        if ($artRatio < 0.6)  return 50.0;
        return 200.0;
    }

    /**
     * Stress corrosion cracking damage factor
     */
    private function calculateSCCDamageFactor(int $assetId, array $asset): float
    {
        // Check if SCC mechanisms are active for this asset
        $sccMechs = $this->db->query(
            "SELECT adm.susceptibility FROM asset_damage_mechanisms adm
             JOIN damage_mechanisms dm ON adm.mechanism_id = dm.id
             WHERE adm.asset_id = ? AND dm.category = 'cracking'",
            [$assetId]
        )->fetchAll();

        if (empty($sccMechs)) {
            return 0.0;
        }

        $maxSusceptibility = 'low';
        foreach ($sccMechs as $m) {
            if ($m['susceptibility'] === 'high') { $maxSusceptibility = 'high'; break; }
            if ($m['susceptibility'] === 'medium') { $maxSusceptibility = 'medium'; }
        }

        // Severity-to-factor mapping
        return match ($maxSusceptibility) {
            'high'   => 500.0,
            'medium' => 50.0,
            'low'    => 5.0,
            default  => 1.0,
        };
    }

    /**
     * High-Temperature Hydrogen Attack damage factor
     */
    private function calculateHTHADamageFactor(array $asset): float
    {
        $temp     = $asset['operating_temperature'] ?? 0;
        $pressure = $asset['operating_pressure'] ?? 0;
        $material = strtolower($asset['material'] ?? 'carbon steel');

        // HTHA only applies above ~204C (400F) with hydrogen partial pressure
        if ($temp < 204) {
            return 0.0;
        }

        // Simplified Nelson curve check
        $nelsonLimit = match (true) {
            str_contains($material, '1.25cr')  => 315,
            str_contains($material, '2.25cr')  => 370,
            str_contains($material, 'stainless') => 500,
            default                             => 204, // carbon steel
        };

        if ($temp < $nelsonLimit) {
            return 1.0;
        }

        return ($temp - $nelsonLimit > 50) ? 500.0 : 50.0;
    }

    /**
     * Management Systems Factor (API 581 Section 4.8)
     * Score from management systems evaluation: 0 (best) to 1000 (worst)
     * FMS = 10^(−0.002 × score + 1) -> range 0.1 to 10
     */
    private function getManagementSystemsFactor(?int $facilityId): float
    {
        if (!$facilityId) {
            return 1.0;
        }

        $facility = $this->db->query(
            "SELECT management_score FROM facilities WHERE id = ?",
            [$facilityId]
        )->fetch();

        $score = $facility['management_score'] ?? 500;

        // FMS formula per API 581
        $fms = pow(10, (-0.002 * $score + 1));
        return max(0.1, min(10.0, round($fms, 3)));
    }

    // ── Consequence Sub-Calculations ────────────────────────────────

    private function getHoleSizeDistribution(string $assetType): array
    {
        // Probability of each hole size per API 581 Table 4.2
        $distributions = [
            'piping' => ['small' => 0.70, 'medium' => 0.20, 'large' => 0.08, 'rupture' => 0.02],
            'vessel' => ['small' => 0.65, 'medium' => 0.25, 'large' => 0.08, 'rupture' => 0.02],
            'tank'   => ['small' => 0.50, 'medium' => 0.30, 'large' => 0.15, 'rupture' => 0.05],
        ];
        return $distributions[$assetType] ?? $distributions['vessel'];
    }

    private function calculateReleaseRate(float $pressure, float $temperature, string $fluid, array $holeSizes): float
    {
        // Simplified release rate (kg/s) based on hole size and pressure
        $holeAreas = ['small' => 0.0005, 'medium' => 0.005, 'large' => 0.05, 'rupture' => 0.5]; // m^2
        $rate = 0;
        foreach ($holeSizes as $size => $prob) {
            $area = $holeAreas[$size];
            // Cd * A * sqrt(2 * rho * P) simplified
            $sizeRate = 0.61 * $area * sqrt(2 * max($pressure, 1) * 1e5 * 800);
            $rate += $prob * $sizeRate;
        }
        return $rate;
    }

    private function calculateFlammableConsequence(float $releaseRate, ?array $opData): float
    {
        // Consequence area (m^2) = K * W^n  (simplified API 581 approach)
        $fluid = $opData['fluid_service'] ?? 'hydrocarbon';
        $k = 55.0;   // empirical constant for hydrocarbons
        $n = 0.95;   // exponent
        return $k * pow(max($releaseRate, 0.01), $n);
    }

    private function calculateToxicConsequence(float $releaseRate, ?array $opData): float
    {
        $h2s = (float) ($opData['h2s_content'] ?? 0);
        if ($h2s <= 0) {
            return 0.0;
        }
        // Toxic area scales with H2S fraction and release rate
        return 200.0 * $h2s * pow(max($releaseRate, 0.01), 0.9);
    }

    private function calculateFinancialConsequence(array $asset, float $releaseRate): float
    {
        // Component damage + production loss + environmental cleanup
        $componentCost  = 50000;   // base repair/replacement cost (USD)
        $productionLoss = max($releaseRate * 3600 * 24 * 50, 0); // $/day lost product
        $envCleanup     = 10000;   // base environmental cost

        $criticalityMultiplier = match ($asset['criticality'] ?? 'medium') {
            'very_high' => 5.0,
            'high'      => 3.0,
            'medium'    => 1.5,
            'low'       => 1.0,
            default     => 1.0,
        };

        return ($componentCost + $productionLoss + $envCleanup) * $criticalityMultiplier;
    }

    // ── Categorization ──────────────────────────────────────────────

    private function categorizePOF(float $pof): int
    {
        // Probability thresholds (failures/year)
        if ($pof < 1e-5)  return 1;
        if ($pof < 1e-4)  return 2;
        if ($pof < 1e-3)  return 3;
        if ($pof < 1e-2)  return 4;
        return 5;
    }

    private function categorizeCOF(float $cof): string
    {
        // Consequence thresholds (area m^2 or USD)
        if ($cof < 10000)   return 'A';
        if ($cof < 50000)   return 'B';
        if ($cof < 200000)  return 'C';
        if ($cof < 1000000) return 'D';
        return 'E';
    }

    // ── Persistence ─────────────────────────────────────────────────

    private function saveAssessment(array $result): int
    {
        return $this->db->insert('risk_assessments', [
            'asset_id'       => $result['asset_id'],
            'pof_value'      => $result['pof']['pof_value'],
            'pof_category'   => $result['pof']['pof_category'],
            'cof_value'      => $result['cof']['cof_value'],
            'cof_category'   => $result['cof']['cof_category'],
            'risk_value'     => $result['risk_value'],
            'risk_level'     => $result['risk_level'],
            'damage_factor'  => $result['pof']['damage_factor'],
            'calculated_at'  => $result['calculated_at'],
            'created_by'     => $_SESSION['auth_user_id'] ?? null,
        ]);
    }

    private function getLatestCorrosionRate(int $assetId): ?float
    {
        $row = $this->db->query(
            "SELECT corrosion_rate FROM inspection_readings
             WHERE asset_id = ? ORDER BY reading_date DESC LIMIT 1",
            [$assetId]
        )->fetch();

        return $row ? (float) $row['corrosion_rate'] : null;
    }
}
