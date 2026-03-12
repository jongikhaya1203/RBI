<?php
/**
 * Auto Risk Scoring API Endpoint
 * RBI Engineering Suite
 *
 * Routes:
 *   POST ?action=score            - Score single asset
 *   POST ?action=batch_score      - Score all assets
 *   GET  ?action=alerts           - Get risk alerts
 *   POST ?action=acknowledge      - Acknowledge alert
 *   POST ?action=what_if          - Run what-if analysis
 *   POST ?action=monte_carlo      - Run Monte Carlo simulation
 *   GET  ?action=fleet_summary    - Get fleet risk summary
 *   GET  ?action=risk_trend       - Get risk trend for asset
 *   GET  ?action=optimize         - Get inspection optimization
 *   POST ?action=generate_alerts  - Generate new risk alerts
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $riskEngine = new AutomatedRiskScoring();
    $db = new Database();

    switch ($action) {

        // ── Score Single Asset ───────────────────────────────────────
        case 'score':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $assetId = (int)($input['asset_id'] ?? 0);

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            $result = $riskEngine->autoScoreAsset($assetId);
            echo json_encode($result);
            break;

        // ── Batch Score All Assets ───────────────────────────────────
        case 'batch_score':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $result = $riskEngine->batchScoreAllAssets();
            echo json_encode(['success' => true, 'results' => $result]);
            break;

        // ── Get Alerts ───────────────────────────────────────────────
        case 'alerts':
            $assetId = (int)($_GET['asset_id'] ?? 0);
            $severity = $_GET['severity'] ?? '';
            $type = $_GET['type'] ?? '';
            $acknowledged = $_GET['acknowledged'] ?? '';
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

            $sql = "SELECT ra.*, ar.asset_tag, ar.asset_name
                    FROM risk_alerts ra
                    JOIN asset_registry ar ON ra.asset_id = ar.id
                    WHERE 1=1";
            $params = [];

            if ($assetId > 0) {
                $sql .= " AND ra.asset_id = ?";
                $params[] = $assetId;
            }
            if ($severity !== '') {
                $sql .= " AND ra.severity = ?";
                $params[] = $severity;
            }
            if ($type !== '') {
                $sql .= " AND ra.alert_type = ?";
                $params[] = $type;
            }
            if ($acknowledged !== '') {
                $sql .= " AND ra.acknowledged = ?";
                $params[] = (int)$acknowledged;
            }

            $sql .= " ORDER BY ra.created_at DESC LIMIT ?";
            $params[] = $limit;

            $alerts = $db->query($sql, $params)->fetchAll();

            // Summary counts
            $summary = $db->query(
                "SELECT severity, COUNT(*) as count
                 FROM risk_alerts WHERE acknowledged = 0
                 GROUP BY severity"
            )->fetchAll();

            echo json_encode([
                'success' => true,
                'alerts'  => $alerts,
                'summary' => $summary,
            ]);
            break;

        // ── Acknowledge Alert ────────────────────────────────────────
        case 'acknowledge':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $alertId = (int)($input['alert_id'] ?? 0);

            if ($alertId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'alert_id is required']);
                exit;
            }

            $db->update('risk_alerts', [
                'acknowledged'    => 1,
                'acknowledged_by' => $_SESSION['user_id'] ?? null,
                'acknowledged_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$alertId]);

            echo json_encode(['success' => true, 'message' => 'Alert acknowledged']);
            break;

        // ── What-If Analysis ─────────────────────────────────────────
        case 'what_if':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $assetId = (int)($input['asset_id'] ?? 0);
            $scenarios = $input['scenarios'] ?? [];

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }
            if (empty($scenarios)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'At least one scenario is required']);
                exit;
            }

            $result = $riskEngine->whatIfAnalysis($assetId, $scenarios);
            echo json_encode($result);
            break;

        // ── Monte Carlo Simulation ───────────────────────────────────
        case 'monte_carlo':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $assetId = (int)($input['asset_id'] ?? 0);
            $iterations = min(10000, max(100, (int)($input['iterations'] ?? 1000)));

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            $result = $riskEngine->calculateRiskOfRisk($assetId, $iterations);
            echo json_encode($result);
            break;

        // ── Fleet Risk Summary ───────────────────────────────────────
        case 'fleet_summary':
            $result = $riskEngine->getFleetRiskSummary();
            echo json_encode($result);
            break;

        // ── Risk Trend ───────────────────────────────────────────────
        case 'risk_trend':
            $assetId = (int)($_GET['asset_id'] ?? 0);
            $periods = min(50, max(1, (int)($_GET['periods'] ?? 12)));

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            $result = $riskEngine->getRiskTrend($assetId, $periods);
            echo json_encode($result);
            break;

        // ── Inspection Optimization ──────────────────────────────────
        case 'optimize':
            $result = $riskEngine->optimizeInspectionPlan();
            echo json_encode($result);
            break;

        // ── Generate Alerts ──────────────────────────────────────────
        case 'generate_alerts':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $result = $riskEngine->generateRiskAlerts();
            echo json_encode($result);
            break;

        // ── Get Score History for Asset ───────────────────────────────
        case 'score_history':
            $assetId = (int)($_GET['asset_id'] ?? 0);
            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            $scores = $db->query(
                "SELECT * FROM risk_scores WHERE asset_id = ? ORDER BY scored_at DESC LIMIT 50",
                [$assetId]
            )->fetchAll();

            echo json_encode(['success' => true, 'scores' => $scores]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }

} catch (\Throwable $e) {
    error_log('[Auto Risk API] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => APP_ENV === 'development' ? $e->getMessage() : 'Internal server error',
    ]);
}
