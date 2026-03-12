<?php
/**
 * IntegrityAnalytics - Engineering analytics for asset integrity
 *
 * Remaining life calculation, corrosion rate trending, sensitivity analysis,
 * and financial risk quantification.
 */
class IntegrityAnalytics
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── Remaining Life ──────────────────────────────────────────────

    /**
     * Calculate remaining life of an asset based on corrosion data
     *
     * Remaining Life (years) = (t_actual - t_min) / corrosion_rate
     *
     * @return array  Remaining life data including retirement date
     */
    public function calculateRemainingLife(int $assetId): array
    {
        $asset = (new Asset())->getById($assetId);
        if (!$asset) {
            throw new InvalidArgumentException("Asset not found: {$assetId}");
        }

        // Get the latest thickness readings
        $readings = $this->db->query(
            "SELECT measured_thickness, reading_date, corrosion_rate
             FROM inspection_readings
             WHERE asset_id = ?
             ORDER BY reading_date DESC
             LIMIT 10",
            [$assetId]
        )->fetchAll();

        $nominalThickness = (float) ($asset['nominal_thickness'] ?? 10.0);
        $minThickness     = (float) ($asset['minimum_thickness'] ?? 2.5);
        $corrosionAllowance = (float) ($asset['corrosion_allowance'] ?? 3.0);

        // Current thickness: use latest reading or calculate from age and nominal
        $currentThickness = $nominalThickness;
        $corrosionRate    = 0.1; // default mm/yr
        $lastReadingDate  = null;

        if (!empty($readings)) {
            $currentThickness = (float) $readings[0]['measured_thickness'];
            $lastReadingDate  = $readings[0]['reading_date'];

            // Calculate corrosion rate from readings
            $corrosionRate = $this->trackCorrosionRate($assetId)['long_term_rate'] ?? 0.1;
        }

        // Remaining corrosion allowance
        $remainingCA = max($currentThickness - $minThickness, 0);

        // Remaining life
        $remainingLifeYears = ($corrosionRate > 0)
            ? $remainingCA / $corrosionRate
            : 999;

        // Retirement date
        $retirementDate = date('Y-m-d', strtotime("+{$remainingLifeYears} years"));

        // Confidence level based on data quality
        $confidence = $this->assessConfidence($readings);

        // Age
        $installDate = $asset['install_date'] ?? $asset['commission_date'] ?? date('Y-m-d');
        $ageYears = calculateAge($installDate);

        // Design life utilization
        $designLife = $ageYears + $remainingLifeYears;
        $utilization = ($designLife > 0) ? ($ageYears / $designLife) * 100 : 0;

        return [
            'asset_id'              => $assetId,
            'nominal_thickness'     => $nominalThickness,
            'minimum_thickness'     => $minThickness,
            'current_thickness'     => round($currentThickness, 2),
            'corrosion_rate'        => round($corrosionRate, 4),
            'remaining_ca'          => round($remainingCA, 2),
            'remaining_life_years'  => round($remainingLifeYears, 1),
            'retirement_date'       => $retirementDate,
            'age_years'             => round($ageYears, 1),
            'life_utilization_pct'  => round($utilization, 1),
            'confidence'            => $confidence,
            'last_reading_date'     => $lastReadingDate,
            'calculated_at'         => date('Y-m-d H:i:s'),
        ];
    }

    // ── Corrosion Rate Tracking ─────────────────────────────────────

    /**
     * Track and calculate corrosion rates from inspection history
     *
     * Returns short-term, long-term, and weighted rates.
     *
     * @return array  Corrosion rate analysis
     */
    public function trackCorrosionRate(int $assetId): array
    {
        $readings = $this->db->query(
            "SELECT measured_thickness, reading_date, reading_location
             FROM inspection_readings
             WHERE asset_id = ?
             ORDER BY reading_date ASC",
            [$assetId]
        )->fetchAll();

        if (count($readings) < 2) {
            return [
                'asset_id'        => $assetId,
                'short_term_rate' => null,
                'long_term_rate'  => null,
                'weighted_rate'   => null,
                'data_points'     => count($readings),
                'trend'           => 'insufficient_data',
            ];
        }

        // Long-term rate: first reading to last reading
        $first = $readings[0];
        $last  = end($readings);
        $totalLoss = (float) $first['measured_thickness'] - (float) $last['measured_thickness'];
        $totalYears = max((strtotime($last['reading_date']) - strtotime($first['reading_date'])) / (365.25 * 86400), 0.1);
        $longTermRate = max($totalLoss / $totalYears, 0);

        // Short-term rate: last two readings
        $shortTermRate = 0;
        if (count($readings) >= 2) {
            $prev = $readings[count($readings) - 2];
            $stLoss  = (float) $prev['measured_thickness'] - (float) $last['measured_thickness'];
            $stYears = max((strtotime($last['reading_date']) - strtotime($prev['reading_date'])) / (365.25 * 86400), 0.1);
            $shortTermRate = max($stLoss / $stYears, 0);
        }

        // Weighted rate (70% short-term, 30% long-term if both available)
        $weightedRate = (0.7 * $shortTermRate) + (0.3 * $longTermRate);

        // Trend detection
        $trend = 'stable';
        if ($shortTermRate > $longTermRate * 1.25) {
            $trend = 'increasing';
        } elseif ($shortTermRate < $longTermRate * 0.75 && $longTermRate > 0) {
            $trend = 'decreasing';
        }

        // Rate history for charting
        $rateHistory = [];
        for ($i = 1; $i < count($readings); $i++) {
            $loss  = (float) $readings[$i-1]['measured_thickness'] - (float) $readings[$i]['measured_thickness'];
            $years = max((strtotime($readings[$i]['reading_date']) - strtotime($readings[$i-1]['reading_date'])) / (365.25 * 86400), 0.01);
            $rateHistory[] = [
                'date' => $readings[$i]['reading_date'],
                'rate' => round(max($loss / $years, 0), 4),
                'thickness' => (float) $readings[$i]['measured_thickness'],
            ];
        }

        return [
            'asset_id'        => $assetId,
            'short_term_rate' => round($shortTermRate, 4),
            'long_term_rate'  => round($longTermRate, 4),
            'weighted_rate'   => round($weightedRate, 4),
            'data_points'     => count($readings),
            'trend'           => $trend,
            'rate_history'    => $rateHistory,
        ];
    }

    // ── Sensitivity Analysis ────────────────────────────────────────

    /**
     * Run sensitivity analysis showing how remaining life changes with corrosion rate variations
     *
     * @param  float $rateVariationPct  Percentage to vary the rate (e.g., 50 = +/- 50%)
     * @param  int   $steps             Number of steps on each side
     * @return array  Sensitivity data for charting
     */
    public function runSensitivityAnalysis(int $assetId, float $rateVariationPct = 50, int $steps = 5): array
    {
        $rlData = $this->calculateRemainingLife($assetId);
        $baseRate = $rlData['corrosion_rate'];
        $remainingCA = $rlData['remaining_ca'];

        if ($baseRate <= 0) {
            throw new RuntimeException('Cannot run sensitivity analysis: corrosion rate is zero.');
        }

        $results = [];
        $variation = $rateVariationPct / 100;

        for ($i = -$steps; $i <= $steps; $i++) {
            $factor = 1.0 + ($i / $steps) * $variation;
            $testRate = max($baseRate * $factor, 0.001);
            $testRL = ($testRate > 0) ? $remainingCA / $testRate : 999;

            $results[] = [
                'corrosion_rate'        => round($testRate, 4),
                'rate_factor'           => round($factor, 2),
                'remaining_life_years'  => round($testRL, 1),
                'retirement_date'       => date('Y-m-d', strtotime("+{$testRL} years")),
            ];
        }

        // Sensitivity index: % change in remaining life per % change in rate
        $baseRL = $rlData['remaining_life_years'];
        $highRateRL = $remainingCA / ($baseRate * (1 + $variation));
        $sensitivityIndex = abs(($highRateRL - $baseRL) / $baseRL) / $variation * 100;

        return [
            'asset_id'           => $assetId,
            'base_rate'          => $baseRate,
            'base_remaining_life'=> $baseRL,
            'variation_pct'      => $rateVariationPct,
            'sensitivity_index'  => round($sensitivityIndex, 1),
            'scenarios'          => $results,
        ];
    }

    // ── Financial Risk ──────────────────────────────────────────────

    /**
     * Calculate financial risk exposure
     *
     * Expected annual financial risk = POF x COF_financial
     */
    public function calculateFinancialRisk(int $assetId): array
    {
        $riskData = $this->db->query(
            "SELECT pof_value, cof_value, risk_value, risk_level
             FROM risk_assessments
             WHERE asset_id = ?
             ORDER BY calculated_at DESC LIMIT 1",
            [$assetId]
        )->fetch();

        if (!$riskData) {
            throw new RuntimeException("No risk assessment found for asset {$assetId}. Run assessment first.");
        }

        $pof = (float) $riskData['pof_value'];
        $cofFinancial = (float) $riskData['cof_value'];

        // Annual expected loss
        $annualExpectedLoss = $pof * $cofFinancial;

        // 5-year cumulative risk (assuming increasing POF)
        $cumulativeRisk = 0;
        for ($yr = 1; $yr <= 5; $yr++) {
            $adjustedPOF = $pof * pow(1.1, $yr); // 10% POF increase per year as asset degrades
            $cumulativeRisk += $adjustedPOF * $cofFinancial;
        }

        // Risk reduction from inspection
        $inspectionCost = 5000; // estimated average
        $riskReduction  = $annualExpectedLoss * 0.3; // 30% risk reduction from inspection
        $roi = ($inspectionCost > 0) ? ($riskReduction / $inspectionCost) * 100 : 0;

        return [
            'asset_id'                => $assetId,
            'annual_expected_loss'    => round($annualExpectedLoss, 2),
            'five_year_cumulative'    => round($cumulativeRisk, 2),
            'pof'                     => $pof,
            'cof_financial'           => $cofFinancial,
            'risk_level'              => $riskData['risk_level'],
            'inspection_cost_est'     => $inspectionCost,
            'risk_reduction_est'      => round($riskReduction, 2),
            'inspection_roi_pct'      => round($roi, 1),
        ];
    }

    // ── Trend Data ──────────────────────────────────────────────────

    /**
     * Get trend data for dashboard charts
     */
    public function getTrendData(int $assetId, string $metric = 'thickness', int $months = 60): array
    {
        $since = date('Y-m-d', strtotime("-{$months} months"));

        return match ($metric) {
            'thickness' => $this->db->query(
                "SELECT reading_date AS date, measured_thickness AS value
                 FROM inspection_readings
                 WHERE asset_id = ? AND reading_date >= ?
                 ORDER BY reading_date ASC",
                [$assetId, $since]
            )->fetchAll(),

            'corrosion_rate' => $this->db->query(
                "SELECT reading_date AS date, corrosion_rate AS value
                 FROM inspection_readings
                 WHERE asset_id = ? AND reading_date >= ? AND corrosion_rate IS NOT NULL
                 ORDER BY reading_date ASC",
                [$assetId, $since]
            )->fetchAll(),

            'risk' => $this->db->query(
                "SELECT DATE(calculated_at) AS date, risk_value AS value, risk_level
                 FROM risk_assessments
                 WHERE asset_id = ? AND calculated_at >= ?
                 ORDER BY calculated_at ASC",
                [$assetId, $since]
            )->fetchAll(),

            default => [],
        };
    }

    // ── Fleet-Level Analytics ───────────────────────────────────────

    /**
     * Get summary statistics across all assets
     */
    public function getFleetSummary(?int $facilityId = null): array
    {
        $where  = '1=1';
        $params = [];
        if ($facilityId) {
            $where  = 'a.facility_id = ?';
            $params = [$facilityId];
        }

        $summary = $this->db->query(
            "SELECT
                COUNT(DISTINCT a.id) AS total_assets,
                SUM(CASE WHEN ra.risk_level = 'VH' THEN 1 ELSE 0 END) AS very_high_risk,
                SUM(CASE WHEN ra.risk_level = 'H'  THEN 1 ELSE 0 END) AS high_risk,
                SUM(CASE WHEN ra.risk_level = 'MH' THEN 1 ELSE 0 END) AS medium_high_risk,
                SUM(CASE WHEN ra.risk_level = 'M'  THEN 1 ELSE 0 END) AS medium_risk,
                SUM(CASE WHEN ra.risk_level = 'L'  THEN 1 ELSE 0 END) AS low_risk,
                AVG(ra.risk_value) AS avg_risk_value,
                SUM(ra.pof_value * ra.cof_value) AS total_financial_risk
             FROM assets a
             LEFT JOIN risk_assessments ra ON a.id = ra.asset_id
                AND ra.id = (SELECT MAX(id) FROM risk_assessments WHERE asset_id = a.id)
             WHERE {$where}",
            $params
        )->fetch();

        return $summary ?: [];
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Assess confidence level of remaining life calculation
     */
    private function assessConfidence(array $readings): string
    {
        $count = count($readings);
        if ($count === 0) return 'very_low';
        if ($count === 1) return 'low';
        if ($count < 3)   return 'moderate';
        if ($count < 6)   return 'good';
        return 'high';
    }
}
