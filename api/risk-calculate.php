<?php
/**
 * Risk Calculation API - JSON endpoint
 * POST: accepts POF/COF parameters, returns risk level
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $assetId = (int)($input['asset_id'] ?? 0);
    $yearsFromNow = (float)($input['years_from_now'] ?? 0);

    // If asset ID provided, use the RiskAssessment class
    if ($assetId > 0) {
        $riskAssess = new RiskAssessment();
        $result = $riskAssess->calculateRisk($assetId, $yearsFromNow);
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    // Manual calculation with provided parameters
    $gff = (float)($input['gff'] ?? 3.06e-5);
    $emf = (float)($input['equipment_mod_factor'] ?? 1.0);
    $fms = (float)($input['management_factor'] ?? 1.0);
    $df  = (float)($input['damage_factor'] ?? 1.0);

    $cofFlammable    = (float)($input['cof_flammable'] ?? 0);
    $cofToxic        = (float)($input['cof_toxic'] ?? 0);
    $cofEnvironmental = (float)($input['cof_environmental'] ?? 0);
    $cofFinancial    = (float)($input['cof_financial'] ?? 0);

    // POF = gff * EMF * FMS * Df
    $pofValue = $gff * $emf * $fms * $df;

    // COF = max of all consequence types
    $cofValue = max($cofFlammable, $cofToxic, $cofEnvironmental, $cofFinancial);

    // Risk Value
    $riskValue = $pofValue * $cofValue;

    // Categorize POF
    $pofCat = $pofValue < 1e-5 ? 1 : ($pofValue < 1e-4 ? 2 : ($pofValue < 1e-3 ? 3 : ($pofValue < 1e-2 ? 4 : 5)));
    $pofLabels = [1 => 'Improbable', 2 => 'Unlikely', 3 => 'Possible', 4 => 'Likely', 5 => 'Very Likely'];

    // Categorize COF
    $cofCat = $cofValue < 10000 ? 'A' : ($cofValue < 50000 ? 'B' : ($cofValue < 200000 ? 'C' : ($cofValue < 1000000 ? 'D' : 'E')));
    $cofLabels = ['A' => 'Low', 'B' => 'Medium-Low', 'C' => 'Medium', 'D' => 'Medium-High', 'E' => 'Very High'];

    // Risk matrix lookup
    $matrix = [
        5 => ['A' => 'MH', 'B' => 'H',  'C' => 'H',  'D' => 'VH', 'E' => 'VH'],
        4 => ['A' => 'M',  'B' => 'MH', 'C' => 'H',  'D' => 'H',  'E' => 'VH'],
        3 => ['A' => 'M',  'B' => 'M',  'C' => 'MH', 'D' => 'H',  'E' => 'H'],
        2 => ['A' => 'L',  'B' => 'M',  'C' => 'M',  'D' => 'MH', 'E' => 'H'],
        1 => ['A' => 'L',  'B' => 'L',  'C' => 'M',  'D' => 'M',  'E' => 'MH'],
    ];
    $riskLevel = $matrix[$pofCat][$cofCat] ?? 'M';
    $riskLevelLabels = ['L' => 'Low', 'M' => 'Medium', 'MH' => 'Medium-High', 'H' => 'High', 'VH' => 'Very High'];

    echo json_encode([
        'success' => true,
        'data' => [
            'pof' => [
                'value'    => round($pofValue, 8),
                'category' => $pofCat,
                'label'    => $pofLabels[$pofCat],
            ],
            'cof' => [
                'value'    => round($cofValue, 2),
                'category' => $cofCat,
                'label'    => $cofLabels[$cofCat],
            ],
            'risk' => [
                'value' => round($riskValue, 4),
                'level' => $riskLevel,
                'label' => $riskLevelLabels[$riskLevel],
            ],
            'inputs' => [
                'gff' => $gff, 'emf' => $emf, 'fms' => $fms, 'df' => $df,
                'cof_flammable' => $cofFlammable, 'cof_toxic' => $cofToxic,
                'cof_environmental' => $cofEnvironmental, 'cof_financial' => $cofFinancial,
            ],
            'calculated_at' => date('Y-m-d H:i:s'),
        ]
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Calculation failed: ' . $e->getMessage()]);
}
