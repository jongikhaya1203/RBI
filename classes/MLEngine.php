<?php
/**
 * MLEngine - Machine Learning Engine for Predictive Analytics
 * RBI Engineering Suite
 *
 * Pure PHP implementation of:
 * - Linear / Polynomial / Exponential regression
 * - Weibull distribution analysis
 * - K-means clustering
 * - Anomaly detection (Z-score, Modified Z-score)
 * - Time series decomposition
 * - Health index computation
 */
class MLEngine
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // =========================================================================
    // STATISTICAL HELPER FUNCTIONS
    // =========================================================================

    /**
     * Calculate arithmetic mean
     */
    public static function mean(array $values): float
    {
        if (empty($values)) return 0.0;
        return array_sum($values) / count($values);
    }

    /**
     * Calculate population standard deviation
     */
    public static function stddev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;
        $mean = self::mean($values);
        $sumSqDiff = 0.0;
        foreach ($values as $v) {
            $sumSqDiff += ($v - $mean) ** 2;
        }
        return sqrt($sumSqDiff / $n);
    }

    /**
     * Calculate sample standard deviation
     */
    public static function sampleStddev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;
        $mean = self::mean($values);
        $sumSqDiff = 0.0;
        foreach ($values as $v) {
            $sumSqDiff += ($v - $mean) ** 2;
        }
        return sqrt($sumSqDiff / ($n - 1));
    }

    /**
     * Calculate covariance between two arrays
     */
    public static function covariance(array $x, array $y): float
    {
        $n = min(count($x), count($y));
        if ($n < 2) return 0.0;
        $meanX = self::mean($x);
        $meanY = self::mean($y);
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sum += ($x[$i] - $meanX) * ($y[$i] - $meanY);
        }
        return $sum / ($n - 1);
    }

    /**
     * Pearson correlation coefficient
     */
    public static function correlation(array $x, array $y): float
    {
        $sx = self::sampleStddev($x);
        $sy = self::sampleStddev($y);
        if ($sx == 0 || $sy == 0) return 0.0;
        return self::covariance($x, $y) / ($sx * $sy);
    }

    /**
     * Calculate percentile using linear interpolation
     */
    public static function percentile(array $values, float $p): float
    {
        if (empty($values)) return 0.0;
        sort($values);
        $n = count($values);
        if ($n === 1) return $values[0];
        $rank = ($p / 100.0) * ($n - 1);
        $lower = (int)floor($rank);
        $upper = (int)ceil($rank);
        if ($lower === $upper) return $values[$lower];
        $frac = $rank - $lower;
        return $values[$lower] + $frac * ($values[$upper] - $values[$lower]);
    }

    /**
     * Median Absolute Deviation
     */
    public static function mad(array $values): float
    {
        if (empty($values)) return 0.0;
        $median = self::percentile($values, 50);
        $deviations = array_map(fn($v) => abs($v - $median), $values);
        return self::percentile($deviations, 50);
    }

    // =========================================================================
    // MATRIX OPERATIONS (for regression)
    // =========================================================================

    /**
     * Transpose a matrix
     */
    private static function matTranspose(array $A): array
    {
        $rows = count($A);
        $cols = count($A[0]);
        $T = [];
        for ($j = 0; $j < $cols; $j++) {
            for ($i = 0; $i < $rows; $i++) {
                $T[$j][$i] = $A[$i][$j];
            }
        }
        return $T;
    }

    /**
     * Multiply two matrices
     */
    private static function matMultiply(array $A, array $B): array
    {
        $rowsA = count($A);
        $colsA = count($A[0]);
        $colsB = count($B[0]);
        $C = [];
        for ($i = 0; $i < $rowsA; $i++) {
            for ($j = 0; $j < $colsB; $j++) {
                $C[$i][$j] = 0;
                for ($k = 0; $k < $colsA; $k++) {
                    $C[$i][$j] += $A[$i][$k] * $B[$k][$j];
                }
            }
        }
        return $C;
    }

    /**
     * Invert a square matrix using Gauss-Jordan elimination
     */
    private static function matInverse(array $A): ?array
    {
        $n = count($A);
        // Augment with identity
        $aug = [];
        for ($i = 0; $i < $n; $i++) {
            $aug[$i] = $A[$i];
            for ($j = 0; $j < $n; $j++) {
                $aug[$i][$n + $j] = ($i === $j) ? 1.0 : 0.0;
            }
        }

        for ($col = 0; $col < $n; $col++) {
            // Find pivot
            $maxVal = abs($aug[$col][$col]);
            $maxRow = $col;
            for ($row = $col + 1; $row < $n; $row++) {
                if (abs($aug[$row][$col]) > $maxVal) {
                    $maxVal = abs($aug[$row][$col]);
                    $maxRow = $row;
                }
            }
            if ($maxVal < 1e-12) return null; // Singular

            // Swap rows
            if ($maxRow !== $col) {
                $tmp = $aug[$col];
                $aug[$col] = $aug[$maxRow];
                $aug[$maxRow] = $tmp;
            }

            // Eliminate
            $pivot = $aug[$col][$col];
            for ($j = 0; $j < 2 * $n; $j++) {
                $aug[$col][$j] /= $pivot;
            }
            for ($row = 0; $row < $n; $row++) {
                if ($row === $col) continue;
                $factor = $aug[$row][$col];
                for ($j = 0; $j < 2 * $n; $j++) {
                    $aug[$row][$j] -= $factor * $aug[$col][$j];
                }
            }
        }

        // Extract inverse
        $inv = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $inv[$i][$j] = $aug[$i][$n + $j];
            }
        }
        return $inv;
    }

    /**
     * Ordinary Least Squares: solve beta = (X'X)^-1 X'y
     * @param array $X Design matrix (n x p)
     * @param array $y Response vector (n x 1 column)
     * @return array|null Coefficient vector
     */
    private static function ols(array $X, array $y): ?array
    {
        $Xt = self::matTranspose($X);
        $XtX = self::matMultiply($Xt, $X);
        $XtXinv = self::matInverse($XtX);
        if ($XtXinv === null) return null;
        $Xty = self::matMultiply($Xt, $y);
        $beta = self::matMultiply($XtXinv, $Xty);
        // flatten
        return array_map(fn($row) => $row[0], $beta);
    }

    // =========================================================================
    // MODEL ACCURACY METRICS
    // =========================================================================

    /**
     * Compute R², RMSE, MAE from actual/predicted arrays
     */
    private static function computeMetrics(array $actual, array $predicted): array
    {
        $n = count($actual);
        if ($n === 0) return ['r_squared' => 0, 'rmse' => 0, 'mae' => 0];

        $meanActual = self::mean($actual);
        $ssTot = 0; $ssRes = 0; $sumAbsErr = 0; $sumSqErr = 0;

        for ($i = 0; $i < $n; $i++) {
            $err = $actual[$i] - $predicted[$i];
            $ssTot += ($actual[$i] - $meanActual) ** 2;
            $ssRes += $err ** 2;
            $sumAbsErr += abs($err);
            $sumSqErr += $err ** 2;
        }

        $rSquared = ($ssTot > 0) ? 1 - ($ssRes / $ssTot) : 0;
        $rmse = sqrt($sumSqErr / $n);
        $mae = $sumAbsErr / $n;

        return [
            'r_squared' => round($rSquared, 6),
            'rmse'      => round($rmse, 6),
            'mae'       => round($mae, 6),
        ];
    }

    // =========================================================================
    // CORROSION MODEL TRAINING
    // =========================================================================

    /**
     * Train a corrosion rate prediction model using historical thickness measurements.
     * Fits linear, polynomial (degree 2), and exponential decay models.
     * Stores best model coefficients in DB.
     *
     * @param int $assetId
     * @return array Model info including ID and accuracy
     */
    public function trainCorrosionModel(int $assetId): array
    {
        // Fetch historical thickness data
        $rows = $this->db->query(
            "SELECT measurement_date, measured_thickness_mm
             FROM corrosion_rate_tracking
             WHERE asset_id = ?
             ORDER BY measurement_date ASC",
            [$assetId]
        )->fetchAll();

        if (count($rows) < 3) {
            return ['success' => false, 'error' => 'Insufficient data points (minimum 3 required)', 'data_points' => count($rows)];
        }

        // Convert dates to fractional years from first measurement
        $baseDate = new DateTime($rows[0]['measurement_date']);
        $times = [];
        $thicknesses = [];
        foreach ($rows as $row) {
            $dt = new DateTime($row['measurement_date']);
            $diff = $baseDate->diff($dt);
            $years = $diff->days / 365.25;
            $times[] = $years;
            $thicknesses[] = (float)$row['measured_thickness_mm'];
        }

        $n = count($times);
        $models = [];

        // --- Linear Regression: thickness = a + b*t ---
        $X_lin = [];
        $y_col = [];
        for ($i = 0; $i < $n; $i++) {
            $X_lin[$i] = [1, $times[$i]];
            $y_col[$i] = [$thicknesses[$i]];
        }
        $beta_lin = self::ols($X_lin, $y_col);
        if ($beta_lin !== null) {
            $predicted = array_map(fn($i) => $beta_lin[0] + $beta_lin[1] * $times[$i], range(0, $n - 1));
            $metrics = self::computeMetrics($thicknesses, $predicted);
            $models['linear_regression'] = [
                'coefficients' => ['intercept' => $beta_lin[0], 'slope' => $beta_lin[1]],
                'base_date' => $baseDate->format('Y-m-d'),
                'metrics' => $metrics,
            ];
        }

        // --- Polynomial Regression (degree 2): thickness = a + b*t + c*t² ---
        $X_poly = [];
        for ($i = 0; $i < $n; $i++) {
            $X_poly[$i] = [1, $times[$i], $times[$i] ** 2];
        }
        $beta_poly = self::ols($X_poly, $y_col);
        if ($beta_poly !== null) {
            $predicted = array_map(fn($i) => $beta_poly[0] + $beta_poly[1] * $times[$i] + $beta_poly[2] * $times[$i] ** 2, range(0, $n - 1));
            $metrics = self::computeMetrics($thicknesses, $predicted);
            $models['polynomial'] = [
                'coefficients' => ['a0' => $beta_poly[0], 'a1' => $beta_poly[1], 'a2' => $beta_poly[2]],
                'degree' => 2,
                'base_date' => $baseDate->format('Y-m-d'),
                'metrics' => $metrics,
            ];
        }

        // --- Exponential Decay: thickness = A * exp(-k*t) ---
        // Linearize: ln(thickness) = ln(A) - k*t
        $validForExp = true;
        $logThick = [];
        foreach ($thicknesses as $th) {
            if ($th <= 0) { $validForExp = false; break; }
            $logThick[] = log($th);
        }
        if ($validForExp && $n >= 2) {
            $X_exp = [];
            $y_exp = [];
            for ($i = 0; $i < $n; $i++) {
                $X_exp[$i] = [1, $times[$i]];
                $y_exp[$i] = [$logThick[$i]];
            }
            $beta_exp = self::ols($X_exp, $y_exp);
            if ($beta_exp !== null) {
                $A = exp($beta_exp[0]);
                $k = -$beta_exp[1];
                $predicted = array_map(fn($i) => $A * exp(-$k * $times[$i]), range(0, $n - 1));
                $metrics = self::computeMetrics($thicknesses, $predicted);
                $models['exponential'] = [
                    'coefficients' => ['A' => $A, 'k' => $k],
                    'base_date' => $baseDate->format('Y-m-d'),
                    'metrics' => $metrics,
                ];
            }
        }

        if (empty($models)) {
            return ['success' => false, 'error' => 'All model fitting attempts failed'];
        }

        // Select best model by R²
        $bestType = null;
        $bestR2 = -INF;
        foreach ($models as $type => $m) {
            if ($m['metrics']['r_squared'] > $bestR2) {
                $bestR2 = $m['metrics']['r_squared'];
                $bestType = $type;
            }
        }

        $best = $models[$bestType];

        // Mark previous models as outdated
        $this->db->query(
            "UPDATE ml_models SET status = 'outdated'
             WHERE asset_id = ? AND model_type IN ('linear_regression','polynomial','exponential') AND status = 'active'",
            [$assetId]
        );

        // Store best model
        $modelId = $this->db->insert('ml_models', [
            'asset_id'             => $assetId,
            'model_type'           => $bestType,
            'parameters'           => json_encode($best['coefficients']),
            'r_squared'            => $best['metrics']['r_squared'],
            'rmse'                 => $best['metrics']['rmse'],
            'mae'                  => $best['metrics']['mae'],
            'training_data_points' => $n,
            'trained_at'           => date('Y-m-d H:i:s'),
            'status'               => 'active',
        ]);

        return [
            'success'       => true,
            'model_id'      => $modelId,
            'model_type'    => $bestType,
            'data_points'   => $n,
            'metrics'       => $best['metrics'],
            'all_models'    => array_map(fn($m) => [
                'type'    => array_search($m, $models) ?: '',
                'metrics' => $m['metrics'],
            ], $models),
        ];
    }

    // =========================================================================
    // CORROSION RATE PREDICTION
    // =========================================================================

    /**
     * Predict future corrosion rates and projected thickness.
     *
     * @param int   $assetId
     * @param float $horizonYears
     * @return array Predicted rates, confidence intervals, projected thickness timeline
     */
    public function predictCorrosionRate(int $assetId, float $horizonYears = 5.0): array
    {
        // Get active model
        $model = $this->db->query(
            "SELECT * FROM ml_models
             WHERE asset_id = ? AND model_type IN ('linear_regression','polynomial','exponential') AND status = 'active'
             ORDER BY trained_at DESC LIMIT 1",
            [$assetId]
        )->fetch();

        if (!$model) {
            return ['success' => false, 'error' => 'No trained corrosion model found. Please train a model first.'];
        }

        $params = json_decode($model['parameters'], true);
        $modelType = $model['model_type'];

        // Get historical data for confidence intervals
        $rows = $this->db->query(
            "SELECT measurement_date, measured_thickness_mm
             FROM corrosion_rate_tracking
             WHERE asset_id = ?
             ORDER BY measurement_date ASC",
            [$assetId]
        )->fetchAll();

        $baseDate = new DateTime($rows[0]['measurement_date']);
        $lastDate = new DateTime(end($rows)['measurement_date']);
        $lastTime = $baseDate->diff($lastDate)->days / 365.25;

        // Calculate residual std dev for confidence intervals
        $residuals = [];
        foreach ($rows as $row) {
            $dt = new DateTime($row['measurement_date']);
            $t = $baseDate->diff($dt)->days / 365.25;
            $predicted = $this->evaluateModel($modelType, $params, $t);
            $residuals[] = (float)$row['measured_thickness_mm'] - $predicted;
        }
        $residualStd = self::sampleStddev($residuals);

        // Generate predictions
        $predictions = [];
        $steps = max(1, (int)($horizonYears * 4)); // quarterly steps
        $stepSize = $horizonYears / $steps;

        for ($i = 0; $i <= $steps; $i++) {
            $futureTime = $lastTime + $i * $stepSize;
            $futureDate = clone $lastDate;
            $futureDate->modify('+' . (int)($i * $stepSize * 365.25) . ' days');

            $predictedThickness = $this->evaluateModel($modelType, $params, $futureTime);

            // Confidence interval widens with extrapolation distance
            $extrapolationFactor = 1 + 0.1 * ($i * $stepSize);
            $ci95 = 1.96 * $residualStd * $extrapolationFactor;

            // Corrosion rate = negative derivative of thickness model
            $dt_small = 0.01;
            $th1 = $this->evaluateModel($modelType, $params, $futureTime);
            $th2 = $this->evaluateModel($modelType, $params, $futureTime + $dt_small);
            $rate = -($th2 - $th1) / $dt_small; // mm/yr (positive = thinning)

            $predictions[] = [
                'date'                  => $futureDate->format('Y-m-d'),
                'years_ahead'           => round($i * $stepSize, 2),
                'predicted_thickness'   => round(max(0, $predictedThickness), 3),
                'confidence_lower'      => round(max(0, $predictedThickness - $ci95), 3),
                'confidence_upper'      => round($predictedThickness + $ci95, 3),
                'predicted_rate_mm_yr'  => round(max(0, $rate), 4),
            ];
        }

        // Store key prediction
        $mainPrediction = end($predictions);
        $this->db->insert('ml_predictions', [
            'model_id'         => $model['id'],
            'asset_id'         => $assetId,
            'prediction_type'  => 'corrosion_rate',
            'predicted_value'  => $mainPrediction['predicted_rate_mm_yr'],
            'confidence_lower' => $mainPrediction['confidence_lower'],
            'confidence_upper' => $mainPrediction['confidence_upper'],
            'prediction_date'  => date('Y-m-d'),
            'target_date'      => $mainPrediction['date'],
        ]);

        // Remaining life estimate
        $designData = $this->db->query(
            "SELECT minimum_required_thickness_mm FROM design_data WHERE asset_id = ? LIMIT 1",
            [$assetId]
        )->fetch();
        $tMin = $designData ? (float)$designData['minimum_required_thickness_mm'] : null;

        $remainingLife = null;
        if ($tMin && !empty($predictions)) {
            $currentThickness = $predictions[0]['predicted_thickness'];
            $currentRate = $predictions[0]['predicted_rate_mm_yr'];
            if ($currentRate > 0) {
                $remainingLife = round(($currentThickness - $tMin) / $currentRate, 1);
            }
        }

        return [
            'success'         => true,
            'model_id'        => $model['id'],
            'model_type'      => $modelType,
            'r_squared'       => (float)$model['r_squared'],
            'predictions'     => $predictions,
            'remaining_life'  => $remainingLife,
            'min_thickness'   => $tMin,
            'historical'      => array_map(fn($r) => [
                'date'      => $r['measurement_date'],
                'thickness' => (float)$r['measured_thickness_mm'],
            ], $rows),
        ];
    }

    /**
     * Evaluate a model at time t
     */
    private function evaluateModel(string $type, array $params, float $t): float
    {
        return match ($type) {
            'linear_regression' => $params['intercept'] + $params['slope'] * $t,
            'polynomial'        => $params['a0'] + $params['a1'] * $t + $params['a2'] * $t ** 2,
            'exponential'       => $params['A'] * exp(-$params['k'] * $t),
            default             => 0.0,
        };
    }

    // =========================================================================
    // WEIBULL / FAILURE PROBABILITY
    // =========================================================================

    /**
     * Train Weibull failure probability model using historical failure data.
     * Uses Median Rank Regression (MRR) for parameter estimation.
     *
     * @param string $assetType Equipment type to aggregate failure data for
     * @return array Weibull parameters (beta = shape, eta = scale)
     */
    public function trainFailureProbability(string $assetType): array
    {
        // Get failure ages (time from install to failure/current) from inspection findings
        $rows = $this->db->query(
            "SELECT ar.id, ar.installation_date, ar.asset_type,
                    COALESCE(
                        (SELECT MIN(f.finding_date) FROM inspection_findings f
                         JOIN inspection_tasks it ON f.task_id = it.id
                         WHERE it.asset_id = ar.id AND f.severity IN ('critical','major')),
                        CURDATE()
                    ) as event_date,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM inspection_findings f
                        JOIN inspection_tasks it ON f.task_id = it.id
                        WHERE it.asset_id = ar.id AND f.severity IN ('critical','major')
                    ) THEN 1 ELSE 0 END as failed
             FROM asset_registry ar
             WHERE ar.asset_type = ? AND ar.installation_date IS NOT NULL
             ORDER BY ar.installation_date ASC",
            [$assetType]
        )->fetchAll();

        if (count($rows) < 3) {
            // Generate synthetic Weibull if insufficient data
            return $this->syntheticWeibull($assetType);
        }

        // Calculate ages in years
        $ages = [];
        $censored = []; // 0 = failed, 1 = censored (still running)
        foreach ($rows as $row) {
            $install = new DateTime($row['installation_date']);
            $event = new DateTime($row['event_date']);
            $age = max(0.1, $install->diff($event)->days / 365.25);
            $ages[] = $age;
            $censored[] = $row['failed'] ? 0 : 1;
        }

        // Sort by age
        array_multisort($ages, SORT_ASC, $censored);

        // Median Rank Regression for Weibull parameters
        // Only use failure data (non-censored)
        $failAges = [];
        for ($i = 0; $i < count($ages); $i++) {
            if ($censored[$i] === 0) {
                $failAges[] = $ages[$i];
            }
        }

        if (count($failAges) < 2) {
            return $this->syntheticWeibull($assetType);
        }

        $n = count($failAges);
        sort($failAges);

        // Median rank: F(i) = (i - 0.3) / (n + 0.4)  (Bernard's approximation)
        $lnT = [];
        $lnlnF = [];
        for ($i = 1; $i <= $n; $i++) {
            $F = ($i - 0.3) / ($n + 0.4);
            $lnT[] = log($failAges[$i - 1]);
            $lnlnF[] = log(-log(1 - $F));
        }

        // Linear regression: lnln(1/(1-F)) = beta * ln(t) - beta * ln(eta)
        $X = [];
        $y = [];
        for ($i = 0; $i < $n; $i++) {
            $X[$i] = [1, $lnT[$i]];
            $y[$i] = [$lnlnF[$i]];
        }
        $beta_fit = self::ols($X, $y);
        if ($beta_fit === null) {
            return $this->syntheticWeibull($assetType);
        }

        $beta = max(0.1, $beta_fit[1]); // shape parameter
        $eta = exp(-$beta_fit[0] / $beta); // scale parameter

        // Compute fit quality
        $predicted = array_map(fn($i) => $beta_fit[0] + $beta_fit[1] * $lnT[$i], range(0, $n - 1));
        $metrics = self::computeMetrics($lnlnF, $predicted);

        // Mark old models outdated
        $this->db->query(
            "UPDATE ml_models SET status = 'outdated'
             WHERE model_type = 'weibull' AND status = 'active'
             AND parameters LIKE ?",
            ['%"asset_type":"' . $assetType . '"%']
        );

        $modelId = $this->db->insert('ml_models', [
            'asset_id'             => null,
            'model_type'           => 'weibull',
            'parameters'           => json_encode([
                'beta'       => round($beta, 4),
                'eta'        => round($eta, 2),
                'asset_type' => $assetType,
                'mttf'       => round($eta * self::gammaFunction(1 + 1 / $beta), 2),
            ]),
            'r_squared'            => $metrics['r_squared'],
            'rmse'                 => $metrics['rmse'],
            'mae'                  => $metrics['mae'],
            'training_data_points' => count($rows),
            'trained_at'           => date('Y-m-d H:i:s'),
            'status'               => 'active',
        ]);

        return [
            'success'     => true,
            'model_id'    => $modelId,
            'beta'        => round($beta, 4),
            'eta'         => round($eta, 2),
            'mttf'        => round($eta * self::gammaFunction(1 + 1 / $beta), 2),
            'data_points' => count($rows),
            'failures'    => count($failAges),
            'metrics'     => $metrics,
        ];
    }

    /**
     * Generate synthetic Weibull parameters based on industry defaults
     */
    private function syntheticWeibull(string $assetType): array
    {
        // Industry-standard Weibull defaults by asset type
        $defaults = [
            'pressure_vessel'  => ['beta' => 2.5, 'eta' => 30],
            'heat_exchanger'   => ['beta' => 2.0, 'eta' => 20],
            'storage_tank'     => ['beta' => 2.8, 'eta' => 35],
            'piping'           => ['beta' => 3.0, 'eta' => 25],
            'column'           => ['beta' => 2.5, 'eta' => 30],
            'reactor'          => ['beta' => 2.2, 'eta' => 22],
            'boiler'           => ['beta' => 2.0, 'eta' => 20],
            'fired_heater'     => ['beta' => 1.8, 'eta' => 18],
            'pump'             => ['beta' => 1.5, 'eta' => 12],
            'compressor'       => ['beta' => 1.5, 'eta' => 15],
            'valve'            => ['beta' => 1.8, 'eta' => 15],
            'relief_device'    => ['beta' => 1.5, 'eta' => 10],
        ];

        $params = $defaults[$assetType] ?? ['beta' => 2.0, 'eta' => 25];
        $mttf = round($params['eta'] * self::gammaFunction(1 + 1 / $params['beta']), 2);

        $modelId = $this->db->insert('ml_models', [
            'asset_id'             => null,
            'model_type'           => 'weibull',
            'parameters'           => json_encode([
                'beta'       => $params['beta'],
                'eta'        => $params['eta'],
                'asset_type' => $assetType,
                'mttf'       => $mttf,
                'synthetic'  => true,
            ]),
            'r_squared'            => 0,
            'rmse'                 => 0,
            'mae'                  => 0,
            'training_data_points' => 0,
            'trained_at'           => date('Y-m-d H:i:s'),
            'status'               => 'active',
        ]);

        return [
            'success'   => true,
            'model_id'  => $modelId,
            'beta'      => $params['beta'],
            'eta'       => $params['eta'],
            'mttf'      => $mttf,
            'synthetic' => true,
            'note'      => 'Using industry default parameters due to insufficient failure data',
        ];
    }

    /**
     * Approximate Gamma function using Stirling/Lanczos approximation
     */
    public static function gammaFunction(float $z): float
    {
        if ($z <= 0) return INF;
        // Lanczos approximation coefficients
        $g = 7;
        $c = [
            0.99999999999980993,
            676.5203681218851,
            -1259.1392167224028,
            771.32342877765313,
            -176.61502916214059,
            12.507343278686905,
            -0.13857109526572012,
            9.9843695780195716e-6,
            1.5056327351493116e-7,
        ];

        if ($z < 0.5) {
            return M_PI / (sin(M_PI * $z) * self::gammaFunction(1 - $z));
        }

        $z -= 1;
        $x = $c[0];
        for ($i = 1; $i < $g + 2; $i++) {
            $x += $c[$i] / ($z + $i);
        }
        $t = $z + $g + 0.5;
        return sqrt(2 * M_PI) * pow($t, $z + 0.5) * exp(-$t) * $x;
    }

    /**
     * Predict failure probability for a specific asset using Weibull CDF.
     * F(t) = 1 - exp(-(t/eta)^beta)
     *
     * @param int   $assetId
     * @param float $timeHorizon Years to project forward
     * @return array Failure probability curve and key values
     */
    public function predictFailureProbability(int $assetId, float $timeHorizon = 10.0): array
    {
        // Get asset info
        $asset = $this->db->query(
            "SELECT ar.*, dd.nominal_thickness_mm FROM asset_registry ar
             LEFT JOIN design_data dd ON dd.asset_id = ar.id
             WHERE ar.id = ?",
            [$assetId]
        )->fetch();

        if (!$asset) {
            return ['success' => false, 'error' => 'Asset not found'];
        }

        $assetType = $asset['asset_type'];

        // Find Weibull model for this asset type
        $model = $this->db->query(
            "SELECT * FROM ml_models
             WHERE model_type = 'weibull' AND status = 'active'
             AND JSON_EXTRACT(parameters, '$.asset_type') = ?
             ORDER BY trained_at DESC LIMIT 1",
            [$assetType]
        )->fetch();

        if (!$model) {
            // Auto-train
            $result = $this->trainFailureProbability($assetType);
            if (!$result['success']) {
                return ['success' => false, 'error' => 'Could not train Weibull model'];
            }
            $model = $this->db->query("SELECT * FROM ml_models WHERE id = ?", [$result['model_id']])->fetch();
        }

        $params = json_decode($model['parameters'], true);
        $beta = $params['beta'];
        $eta = $params['eta'];

        // Calculate current age
        $currentAge = 0;
        if ($asset['installation_date']) {
            $currentAge = (new DateTime($asset['installation_date']))->diff(new DateTime())->days / 365.25;
        }

        // Generate probability curve
        $curve = [];
        $steps = max(1, (int)($timeHorizon * 2));
        for ($i = 0; $i <= $steps; $i++) {
            $t = $currentAge + ($i / $steps) * $timeHorizon;
            $pof = 1 - exp(-pow($t / $eta, $beta));
            $reliability = 1 - $pof;
            $hazardRate = ($beta / $eta) * pow($t / $eta, $beta - 1);

            $curve[] = [
                'age_years'      => round($t, 1),
                'years_from_now' => round(($i / $steps) * $timeHorizon, 1),
                'pof'            => round($pof, 6),
                'reliability'    => round($reliability, 6),
                'hazard_rate'    => round($hazardRate, 6),
            ];
        }

        // Key probabilities
        $pofNow = 1 - exp(-pow($currentAge / $eta, $beta));
        $pof1yr = 1 - exp(-pow(($currentAge + 1) / $eta, $beta));
        $pof5yr = 1 - exp(-pow(($currentAge + 5) / $eta, $beta));

        // Store prediction
        $this->db->insert('ml_predictions', [
            'model_id'         => $model['id'],
            'asset_id'         => $assetId,
            'prediction_type'  => 'failure_probability',
            'predicted_value'  => $pof1yr,
            'confidence_lower' => max(0, $pof1yr * 0.7),
            'confidence_upper' => min(1, $pof1yr * 1.3),
            'prediction_date'  => date('Y-m-d'),
            'target_date'      => date('Y-m-d', strtotime('+1 year')),
        ]);

        return [
            'success'      => true,
            'model_id'     => $model['id'],
            'beta'         => $beta,
            'eta'          => $eta,
            'current_age'  => round($currentAge, 1),
            'mttf'         => $params['mttf'] ?? round($eta * self::gammaFunction(1 + 1 / $beta), 2),
            'pof_current'  => round($pofNow, 6),
            'pof_1yr'      => round($pof1yr, 6),
            'pof_5yr'      => round($pof5yr, 6),
            'curve'        => $curve,
        ];
    }

    // =========================================================================
    // ANOMALY DETECTION
    // =========================================================================

    /**
     * Detect anomalies in sensor/inspection data using Z-score and Modified Z-score.
     * Flags readings that deviate more than 3 sigma.
     *
     * @param int $assetId
     * @return array Anomalies found with severity and method
     */
    public function anomalyDetection(int $assetId): array
    {
        // Get thickness readings
        $readings = $this->db->query(
            "SELECT id, measurement_date, measured_thickness_mm, cml_reference, measurement_method
             FROM corrosion_rate_tracking
             WHERE asset_id = ?
             ORDER BY measurement_date ASC",
            [$assetId]
        )->fetchAll();

        if (count($readings) < 5) {
            return ['success' => false, 'error' => 'Insufficient data for anomaly detection (minimum 5 readings)'];
        }

        $values = array_map(fn($r) => (float)$r['measured_thickness_mm'], $readings);
        $mean = self::mean($values);
        $std = self::stddev($values);
        $median = self::percentile($values, 50);
        $madVal = self::mad($values);

        $anomalies = [];
        $threshold = 3.0;
        $modifiedThreshold = 3.5; // Recommended for Modified Z-score

        foreach ($readings as $i => $reading) {
            $val = (float)$reading['measured_thickness_mm'];
            $zscore = ($std > 0) ? ($val - $mean) / $std : 0;

            // Modified Z-score: 0.6745 * (x - median) / MAD
            $modifiedZ = ($madVal > 0) ? 0.6745 * ($val - $median) / $madVal : 0;

            $isAnomaly = false;
            $methods = [];

            if (abs($zscore) > $threshold) {
                $isAnomaly = true;
                $methods[] = 'z_score';
            }
            if (abs($modifiedZ) > $modifiedThreshold) {
                $isAnomaly = true;
                $methods[] = 'modified_z_score';
            }

            // Rate-of-change anomaly (sudden thickness change between consecutive readings)
            if ($i > 0) {
                $prevVal = (float)$readings[$i - 1]['measured_thickness_mm'];
                $prevDate = new DateTime($readings[$i - 1]['measurement_date']);
                $currDate = new DateTime($reading['measurement_date']);
                $daysDiff = max(1, $prevDate->diff($currDate)->days);
                $rateChange = abs($val - $prevVal) / ($daysDiff / 365.25);

                // Flag if instantaneous rate is > 5x the average rate
                $avgRate = (count($values) > 1) ? abs($values[0] - end($values)) / (count($values) - 1) : 0;
                if ($avgRate > 0 && $rateChange > 5 * $avgRate) {
                    $isAnomaly = true;
                    $methods[] = 'rate_of_change';
                }
            }

            $readings[$i]['z_score'] = round($zscore, 3);
            $readings[$i]['modified_z_score'] = round($modifiedZ, 3);
            $readings[$i]['is_anomaly'] = $isAnomaly;
            $readings[$i]['anomaly_methods'] = $methods;

            if ($isAnomaly) {
                $severity = (abs($zscore) > 4 || abs($modifiedZ) > 5) ? 'critical' : 'warning';
                $anomalies[] = [
                    'reading_id'       => $reading['id'],
                    'date'             => $reading['measurement_date'],
                    'value'            => $val,
                    'cml_reference'    => $reading['cml_reference'],
                    'z_score'          => round($zscore, 3),
                    'modified_z_score' => round($modifiedZ, 3),
                    'methods'          => $methods,
                    'severity'         => $severity,
                ];
            }
        }

        // Store anomaly alerts
        foreach ($anomalies as $anomaly) {
            $this->db->insert('risk_alerts', [
                'asset_id'   => $assetId,
                'alert_type' => 'anomaly_detected',
                'severity'   => $anomaly['severity'],
                'message'    => sprintf(
                    'Anomaly detected at CML %s on %s: %.3f mm (Z=%.2f, methods: %s)',
                    $anomaly['cml_reference'] ?? 'N/A',
                    $anomaly['date'],
                    $anomaly['value'],
                    $anomaly['z_score'],
                    implode(', ', $anomaly['methods'])
                ),
                'data'       => json_encode($anomaly),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'success'    => true,
            'total_readings' => count($readings),
            'anomaly_count'  => count($anomalies),
            'statistics' => [
                'mean'   => round($mean, 3),
                'std'    => round($std, 3),
                'median' => round($median, 3),
                'mad'    => round($madVal, 3),
            ],
            'anomalies' => $anomalies,
            'all_readings' => $readings,
        ];
    }

    // =========================================================================
    // K-MEANS CLUSTERING
    // =========================================================================

    /**
     * K-means clustering of assets by risk profile.
     * Features: corrosion rate, age, operating severity, inspection compliance, damage mechanism count.
     *
     * @param int $k Number of clusters (default 5)
     * @param int $maxIterations
     * @return array Cluster assignments and centroids
     */
    public function clusterAssets(int $k = 5, int $maxIterations = 100): array
    {
        // Build feature vectors
        $assets = $this->db->query(
            "SELECT ar.id, ar.asset_tag, ar.asset_name, ar.asset_type,
                    ar.installation_date, ar.criticality,
                    COALESCE(dd.nominal_thickness_mm, 0) as nominal_thickness,
                    (SELECT COUNT(*) FROM asset_damage_mechanisms adm WHERE adm.asset_id = ar.id AND adm.active = 1) as dm_count,
                    (SELECT AVG(crh.long_term_rate_mm_yr) FROM corrosion_rate_history crh WHERE crh.asset_id = ar.id) as avg_corrosion_rate,
                    (SELECT COUNT(*) FROM inspection_tasks it WHERE it.asset_id = ar.id AND it.status = 'completed') as completed_inspections,
                    (SELECT COUNT(*) FROM inspection_tasks it WHERE it.asset_id = ar.id AND it.status IN ('pending','overdue') AND it.due_date < CURDATE()) as overdue_inspections
             FROM asset_registry ar
             LEFT JOIN design_data dd ON dd.asset_id = ar.id
             WHERE ar.status = 'in_service'
             ORDER BY ar.id"
        )->fetchAll();

        if (count($assets) < $k) {
            return ['success' => false, 'error' => 'Not enough assets for ' . $k . ' clusters'];
        }

        // Build normalized feature matrix
        $features = [];
        $assetIds = [];
        foreach ($assets as $a) {
            $age = $a['installation_date']
                ? (new DateTime($a['installation_date']))->diff(new DateTime())->days / 365.25
                : 10; // default

            $critMap = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $crit = $critMap[$a['criticality']] ?? 2;

            $features[] = [
                (float)($a['avg_corrosion_rate'] ?? 0.1),  // corrosion rate
                $age,                                        // age
                $crit,                                       // criticality
                (int)$a['dm_count'],                         // damage mechanisms
                (int)$a['overdue_inspections'],              // overdue inspections
            ];
            $assetIds[] = (int)$a['id'];
        }

        $n = count($features);
        $dims = count($features[0]);

        // Normalize features (min-max)
        $mins = array_fill(0, $dims, INF);
        $maxs = array_fill(0, $dims, -INF);
        for ($i = 0; $i < $n; $i++) {
            for ($d = 0; $d < $dims; $d++) {
                $mins[$d] = min($mins[$d], $features[$i][$d]);
                $maxs[$d] = max($maxs[$d], $features[$i][$d]);
            }
        }
        $normalized = [];
        for ($i = 0; $i < $n; $i++) {
            $normalized[$i] = [];
            for ($d = 0; $d < $dims; $d++) {
                $range = $maxs[$d] - $mins[$d];
                $normalized[$i][$d] = ($range > 0) ? ($features[$i][$d] - $mins[$d]) / $range : 0;
            }
        }

        // Initialize centroids (K-means++ initialization)
        $centroids = [];
        $centroids[0] = $normalized[array_rand($normalized)];
        for ($c = 1; $c < $k; $c++) {
            $distances = [];
            for ($i = 0; $i < $n; $i++) {
                $minDist = INF;
                for ($j = 0; $j < $c; $j++) {
                    $dist = $this->euclideanDistance($normalized[$i], $centroids[$j]);
                    $minDist = min($minDist, $dist);
                }
                $distances[$i] = $minDist ** 2;
            }
            $totalDist = array_sum($distances);
            $rand = mt_rand() / mt_getrandmax() * $totalDist;
            $cumulative = 0;
            for ($i = 0; $i < $n; $i++) {
                $cumulative += $distances[$i];
                if ($cumulative >= $rand) {
                    $centroids[$c] = $normalized[$i];
                    break;
                }
            }
        }

        // K-means iteration
        $assignments = array_fill(0, $n, 0);
        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $changed = false;

            // Assign each point to nearest centroid
            for ($i = 0; $i < $n; $i++) {
                $minDist = INF;
                $bestCluster = 0;
                for ($c = 0; $c < $k; $c++) {
                    $dist = $this->euclideanDistance($normalized[$i], $centroids[$c]);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $bestCluster = $c;
                    }
                }
                if ($assignments[$i] !== $bestCluster) {
                    $assignments[$i] = $bestCluster;
                    $changed = true;
                }
            }

            if (!$changed) break;

            // Update centroids
            for ($c = 0; $c < $k; $c++) {
                $members = [];
                for ($i = 0; $i < $n; $i++) {
                    if ($assignments[$i] === $c) {
                        $members[] = $normalized[$i];
                    }
                }
                if (!empty($members)) {
                    $newCentroid = array_fill(0, $dims, 0);
                    foreach ($members as $m) {
                        for ($d = 0; $d < $dims; $d++) {
                            $newCentroid[$d] += $m[$d];
                        }
                    }
                    for ($d = 0; $d < $dims; $d++) {
                        $centroids[$c][$d] = $newCentroid[$d] / count($members);
                    }
                }
            }
        }

        // Build cluster names and results
        $clusterLabels = ['Low Risk / Well Maintained', 'Moderate Risk / Aging', 'Elevated Risk / Active Degradation', 'High Risk / Overdue', 'Critical Risk / Immediate Attention'];
        // Sort clusters by centroid magnitude (risk level)
        $centroidMagnitudes = [];
        for ($c = 0; $c < $k; $c++) {
            $centroidMagnitudes[$c] = array_sum($centroids[$c]);
        }
        arsort($centroidMagnitudes);
        $sortedLabels = array_values(array_keys($centroidMagnitudes));

        // Store in database
        $this->db->query("DELETE FROM asset_clusters");

        // Mark old kmeans models outdated
        $this->db->query(
            "UPDATE ml_models SET status = 'outdated' WHERE model_type = 'kmeans' AND status = 'active'"
        );

        $modelId = $this->db->insert('ml_models', [
            'asset_id'             => null,
            'model_type'           => 'kmeans',
            'parameters'           => json_encode([
                'k' => $k,
                'centroids' => $centroids,
                'feature_names' => ['corrosion_rate', 'age', 'criticality', 'dm_count', 'overdue_inspections'],
                'normalization' => ['mins' => $mins, 'maxs' => $maxs],
            ]),
            'r_squared'            => 0,
            'rmse'                 => 0,
            'mae'                  => 0,
            'training_data_points' => $n,
            'trained_at'           => date('Y-m-d H:i:s'),
            'status'               => 'active',
        ]);

        $clusters = [];
        for ($c = 0; $c < $k; $c++) {
            $clusters[$c] = ['assets' => [], 'count' => 0];
        }

        for ($i = 0; $i < $n; $i++) {
            $clusterId = $assignments[$i];
            $dist = $this->euclideanDistance($normalized[$i], $centroids[$clusterId]);

            $labelIdx = array_search($clusterId, $sortedLabels);
            $clusterName = $clusterLabels[$labelIdx] ?? 'Cluster ' . $clusterId;

            $this->db->insert('asset_clusters', [
                'cluster_id'           => $clusterId,
                'asset_id'             => $assetIds[$i],
                'cluster_name'         => $clusterName,
                'centroid'             => json_encode($centroids[$clusterId]),
                'distance_to_centroid' => round($dist, 6),
            ]);

            $clusters[$clusterId]['assets'][] = [
                'asset_id'   => $assetIds[$i],
                'asset_tag'  => $assets[$i]['asset_tag'],
                'asset_name' => $assets[$i]['asset_name'],
                'distance'   => round($dist, 4),
                'features'   => $features[$i],
            ];
            $clusters[$clusterId]['count']++;
            $clusters[$clusterId]['name'] = $clusterName;
            $clusters[$clusterId]['centroid'] = $centroids[$clusterId];
        }

        return [
            'success'    => true,
            'model_id'   => $modelId,
            'k'          => $k,
            'iterations' => $iter ?? 0,
            'clusters'   => $clusters,
            'total_assets' => $n,
            'feature_names' => ['Corrosion Rate', 'Age', 'Criticality', 'DM Count', 'Overdue Inspections'],
        ];
    }

    /**
     * Euclidean distance between two vectors
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += ($a[$i] - ($b[$i] ?? 0)) ** 2;
        }
        return sqrt($sum);
    }

    // =========================================================================
    // HEALTH INDEX
    // =========================================================================

    /**
     * Calculate composite health index (0-100) for an asset.
     * Components:
     *   - Age factor (20%): based on age vs MTTF
     *   - Corrosion factor (25%): based on wall loss percentage
     *   - Inspection compliance (20%): based on overdue inspections
     *   - Damage mechanism count (15%): active DMs relative to total possible
     *   - Operating severity (20%): operating conditions vs design limits
     *
     * @param int $assetId
     * @return array Health index breakdown
     */
    public function calculateHealthIndex(int $assetId): array
    {
        $asset = $this->db->query(
            "SELECT ar.*, dd.nominal_thickness_mm, dd.minimum_required_thickness_mm,
                    dd.design_pressure_mpa, dd.design_temperature_c, dd.corrosion_allowance_mm
             FROM asset_registry ar
             LEFT JOIN design_data dd ON dd.asset_id = ar.id
             WHERE ar.id = ?",
            [$assetId]
        )->fetch();

        if (!$asset) {
            return ['success' => false, 'error' => 'Asset not found'];
        }

        // --- Age Factor (20%) ---
        $age = $asset['installation_date']
            ? (new DateTime($asset['installation_date']))->diff(new DateTime())->days / 365.25
            : 10;
        // Use 40 years as typical design life
        $designLife = 40;
        $ageFactor = max(0, min(100, 100 * (1 - $age / $designLife)));

        // --- Corrosion Factor (25%) ---
        $lastReading = $this->db->query(
            "SELECT measured_thickness_mm FROM corrosion_rate_tracking
             WHERE asset_id = ? ORDER BY measurement_date DESC LIMIT 1",
            [$assetId]
        )->fetch();

        $corrosionFactor = 100;
        if ($lastReading && $asset['nominal_thickness_mm']) {
            $nominal = (float)$asset['nominal_thickness_mm'];
            $current = (float)$lastReading['measured_thickness_mm'];
            $tMin = (float)($asset['minimum_required_thickness_mm'] ?? $nominal * 0.5);
            $usableRange = $nominal - $tMin;
            if ($usableRange > 0) {
                $corrosionFactor = max(0, min(100, 100 * ($current - $tMin) / $usableRange));
            }
        }

        // --- Inspection Compliance (20%) ---
        $inspStats = $this->db->query(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('pending','overdue') AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue
             FROM inspection_tasks WHERE asset_id = ?",
            [$assetId]
        )->fetch();

        $inspectionFactor = 100;
        if ($inspStats && $inspStats['total'] > 0) {
            $complianceRate = $inspStats['completed'] / $inspStats['total'];
            $overduePenalty = min(50, $inspStats['overdue'] * 15);
            $inspectionFactor = max(0, $complianceRate * 100 - $overduePenalty);
        }

        // --- Damage Mechanism Factor (15%) ---
        $dmCount = (int)$this->db->query(
            "SELECT COUNT(*) FROM asset_damage_mechanisms WHERE asset_id = ? AND active = 1",
            [$assetId]
        )->fetchColumn();
        $maxExpectedDM = 8; // Typical max for a single asset
        $dmFactor = max(0, min(100, 100 * (1 - $dmCount / $maxExpectedDM)));

        // --- Operating Severity (20%) ---
        $opData = $this->db->query(
            "SELECT operating_pressure_mpa, operating_temperature_c
             FROM operational_data WHERE asset_id = ? ORDER BY effective_date DESC LIMIT 1",
            [$assetId]
        )->fetch();

        $operatingSeverity = 80; // Default moderate
        if ($opData && $asset['design_pressure_mpa'] && $asset['design_temperature_c']) {
            $pressureRatio = ($asset['design_pressure_mpa'] > 0)
                ? (float)$opData['operating_pressure_mpa'] / (float)$asset['design_pressure_mpa']
                : 0;
            $tempRatio = ($asset['design_temperature_c'] > 0)
                ? (float)$opData['operating_temperature_c'] / (float)$asset['design_temperature_c']
                : 0;

            $avgRatio = ($pressureRatio + $tempRatio) / 2;
            $operatingSeverity = max(0, min(100, 100 * (1 - $avgRatio * 0.8)));
        }

        // --- Weighted composite ---
        $weights = [
            'age'          => 0.20,
            'corrosion'    => 0.25,
            'inspection'   => 0.20,
            'damage_mech'  => 0.15,
            'operating'    => 0.20,
        ];

        $healthIndex = round(
            $weights['age'] * $ageFactor +
            $weights['corrosion'] * $corrosionFactor +
            $weights['inspection'] * $inspectionFactor +
            $weights['damage_mech'] * $dmFactor +
            $weights['operating'] * $operatingSeverity,
            1
        );

        // Store prediction
        $activeModel = $this->db->query(
            "SELECT id FROM ml_models WHERE asset_id = ? AND status = 'active' ORDER BY trained_at DESC LIMIT 1",
            [$assetId]
        )->fetch();

        if ($activeModel) {
            $this->db->insert('ml_predictions', [
                'model_id'        => $activeModel['id'],
                'asset_id'        => $assetId,
                'prediction_type' => 'health_index',
                'predicted_value' => $healthIndex,
                'prediction_date' => date('Y-m-d'),
            ]);
        }

        return [
            'success'      => true,
            'health_index' => $healthIndex,
            'category'     => $this->healthCategory($healthIndex),
            'components'   => [
                'age_factor'           => ['value' => round($ageFactor, 1), 'weight' => $weights['age'], 'weighted' => round($weights['age'] * $ageFactor, 1)],
                'corrosion_factor'     => ['value' => round($corrosionFactor, 1), 'weight' => $weights['corrosion'], 'weighted' => round($weights['corrosion'] * $corrosionFactor, 1)],
                'inspection_factor'    => ['value' => round($inspectionFactor, 1), 'weight' => $weights['inspection'], 'weighted' => round($weights['inspection'] * $inspectionFactor, 1)],
                'damage_mech_factor'   => ['value' => round($dmFactor, 1), 'weight' => $weights['damage_mech'], 'weighted' => round($weights['damage_mech'] * $dmFactor, 1)],
                'operating_severity'   => ['value' => round($operatingSeverity, 1), 'weight' => $weights['operating'], 'weighted' => round($weights['operating'] * $operatingSeverity, 1)],
            ],
            'details' => [
                'age_years'     => round($age, 1),
                'dm_count'      => $dmCount,
                'overdue_count' => (int)($inspStats['overdue'] ?? 0),
            ],
        ];
    }

    private function healthCategory(float $index): string
    {
        if ($index >= 80) return 'excellent';
        if ($index >= 60) return 'good';
        if ($index >= 40) return 'fair';
        if ($index >= 20) return 'poor';
        return 'critical';
    }

    // =========================================================================
    // TREND ANALYSIS
    // =========================================================================

    /**
     * Time series decomposition: trend, seasonality, residual.
     * Uses moving averages and exponential smoothing.
     *
     * @param int    $assetId
     * @param string $parameter 'thickness' or 'corrosion_rate'
     * @return array Decomposed time series
     */
    public function trendAnalysis(int $assetId, string $parameter = 'thickness'): array
    {
        if ($parameter === 'corrosion_rate') {
            $rows = $this->db->query(
                "SELECT period_end_date as date, long_term_rate_mm_yr as value
                 FROM corrosion_rate_history
                 WHERE asset_id = ?
                 ORDER BY period_end_date ASC",
                [$assetId]
            )->fetchAll();
        } else {
            $rows = $this->db->query(
                "SELECT measurement_date as date, measured_thickness_mm as value
                 FROM corrosion_rate_tracking
                 WHERE asset_id = ?
                 ORDER BY measurement_date ASC",
                [$assetId]
            )->fetchAll();
        }

        if (count($rows) < 4) {
            return ['success' => false, 'error' => 'Insufficient data for trend analysis (minimum 4 points)'];
        }

        $dates = array_column($rows, 'date');
        $values = array_map(fn($r) => (float)$r['value'], $rows);
        $n = count($values);

        // --- Moving average (window = min(4, n/2)) ---
        $window = max(2, min(4, (int)($n / 2)));
        $trend = [];
        for ($i = 0; $i < $n; $i++) {
            $start = max(0, $i - (int)($window / 2));
            $end = min($n - 1, $i + (int)($window / 2));
            $slice = array_slice($values, $start, $end - $start + 1);
            $trend[$i] = self::mean($slice);
        }

        // --- Seasonality (detrended values) ---
        $detrended = [];
        for ($i = 0; $i < $n; $i++) {
            $detrended[$i] = $values[$i] - $trend[$i];
        }

        // --- Exponential smoothing (alpha = 0.3) ---
        $alpha = 0.3;
        $smoothed = [$values[0]];
        for ($i = 1; $i < $n; $i++) {
            $smoothed[$i] = $alpha * $values[$i] + (1 - $alpha) * $smoothed[$i - 1];
        }

        // --- Residuals ---
        $residuals = [];
        for ($i = 0; $i < $n; $i++) {
            $residuals[$i] = $values[$i] - $smoothed[$i];
        }

        // --- Trend direction ---
        $firstHalf = array_slice($values, 0, (int)($n / 2));
        $secondHalf = array_slice($values, (int)($n / 2));
        $trendDirection = 'stable';
        $change = self::mean($secondHalf) - self::mean($firstHalf);
        if (abs($change) > self::stddev($values) * 0.5) {
            $trendDirection = $change > 0 ? 'increasing' : 'decreasing';
        }

        // --- Forecast next 4 periods using exponential smoothing ---
        $forecast = [];
        $lastSmoothed = end($smoothed);
        $lastDate = new DateTime(end($dates));
        // Estimate average interval
        $firstDate = new DateTime($dates[0]);
        $totalDays = $firstDate->diff($lastDate)->days;
        $avgInterval = ($n > 1) ? (int)($totalDays / ($n - 1)) : 365;

        for ($i = 1; $i <= 4; $i++) {
            $forecastDate = clone $lastDate;
            $forecastDate->modify('+' . ($avgInterval * $i) . ' days');
            // Simple exponential smoothing forecast is just the last smoothed value
            // plus trend extrapolation
            $trendSlope = ($n >= 2) ? ($smoothed[$n - 1] - $smoothed[$n - 2]) : 0;
            $forecastVal = $lastSmoothed + $trendSlope * $i;
            $forecast[] = [
                'date'  => $forecastDate->format('Y-m-d'),
                'value' => round($forecastVal, 4),
            ];
        }

        return [
            'success'    => true,
            'parameter'  => $parameter,
            'data_points'=> $n,
            'trend_direction' => $trendDirection,
            'series'     => array_map(fn($i) => [
                'date'       => $dates[$i],
                'actual'     => round($values[$i], 4),
                'trend'      => round($trend[$i], 4),
                'seasonal'   => round($detrended[$i], 4),
                'smoothed'   => round($smoothed[$i], 4),
                'residual'   => round($residuals[$i], 4),
            ], range(0, $n - 1)),
            'forecast'   => $forecast,
            'statistics' => [
                'mean'   => round(self::mean($values), 4),
                'std'    => round(self::stddev($values), 4),
                'min'    => round(min($values), 4),
                'max'    => round(max($values), 4),
            ],
        ];
    }

    // =========================================================================
    // MODEL ACCURACY
    // =========================================================================

    /**
     * Get accuracy metrics for a trained model.
     *
     * @param int $modelId
     * @return array R², RMSE, MAE, and prediction vs actual comparison
     */
    public function getModelAccuracy(int $modelId): array
    {
        $model = $this->db->find('ml_models', $modelId);
        if (!$model) {
            return ['success' => false, 'error' => 'Model not found'];
        }

        // Get predictions that have actual values filled in
        $comparisons = $this->db->query(
            "SELECT predicted_value, actual_value, prediction_date, target_date
             FROM ml_predictions
             WHERE model_id = ? AND actual_value IS NOT NULL",
            [$modelId]
        )->fetchAll();

        $accuracy = [
            'model_id'     => $modelId,
            'model_type'   => $model['model_type'],
            'r_squared'    => (float)$model['r_squared'],
            'rmse'         => (float)$model['rmse'],
            'mae'          => (float)$model['mae'],
            'data_points'  => (int)$model['training_data_points'],
            'trained_at'   => $model['trained_at'],
            'status'       => $model['status'],
            'parameters'   => json_decode($model['parameters'], true),
        ];

        if (!empty($comparisons)) {
            $actual = array_map(fn($c) => (float)$c['actual_value'], $comparisons);
            $predicted = array_map(fn($c) => (float)$c['predicted_value'], $comparisons);
            $liveMetrics = self::computeMetrics($actual, $predicted);
            $accuracy['live_metrics'] = $liveMetrics;
            $accuracy['comparisons'] = $comparisons;
        }

        return ['success' => true, 'accuracy' => $accuracy];
    }

    // =========================================================================
    // BATCH RETRAIN
    // =========================================================================

    /**
     * Retrain all models with latest data.
     *
     * @return array Summary of retrained models
     */
    public function retrainAllModels(): array
    {
        $results = ['trained' => 0, 'failed' => 0, 'details' => []];

        // Retrain corrosion models for all assets with thickness data
        $assetIds = $this->db->query(
            "SELECT DISTINCT asset_id FROM corrosion_rate_tracking"
        )->fetchAll();

        foreach ($assetIds as $row) {
            try {
                $result = $this->trainCorrosionModel((int)$row['asset_id']);
                if ($result['success']) {
                    $results['trained']++;
                    $results['details'][] = ['asset_id' => $row['asset_id'], 'type' => 'corrosion', 'status' => 'success'];
                } else {
                    $results['failed']++;
                    $results['details'][] = ['asset_id' => $row['asset_id'], 'type' => 'corrosion', 'status' => 'failed', 'error' => $result['error'] ?? ''];
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['details'][] = ['asset_id' => $row['asset_id'], 'type' => 'corrosion', 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        // Retrain Weibull models for each asset type
        $assetTypes = $this->db->query(
            "SELECT DISTINCT asset_type FROM asset_registry WHERE status = 'in_service'"
        )->fetchAll();

        foreach ($assetTypes as $row) {
            try {
                $result = $this->trainFailureProbability($row['asset_type']);
                if ($result['success']) {
                    $results['trained']++;
                    $results['details'][] = ['asset_type' => $row['asset_type'], 'type' => 'weibull', 'status' => 'success'];
                } else {
                    $results['failed']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
            }
        }

        // Re-run clustering
        try {
            $clusterResult = $this->clusterAssets();
            if ($clusterResult['success']) {
                $results['trained']++;
                $results['details'][] = ['type' => 'kmeans', 'status' => 'success'];
            }
        } catch (\Throwable $e) {
            $results['failed']++;
        }

        return $results;
    }
}
