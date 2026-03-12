<?php
/**
 * ML Prediction API Endpoint
 * RBI Engineering Suite
 *
 * Routes:
 *   POST ?action=train          - Train model for asset
 *   POST ?action=predict        - Get prediction
 *   GET  ?action=models         - List trained models
 *   GET  ?action=accuracy&id=X  - Get model metrics
 *   POST ?action=anomaly_detect - Run anomaly detection
 *   POST ?action=retrain_all    - Batch retrain all models
 *   POST ?action=trend          - Trend analysis
 *   POST ?action=health_index   - Calculate health index
 *   POST ?action=cluster        - Run K-means clustering
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
    $ml = new MLEngine();
    $db = new Database();

    switch ($action) {

        // ── Train Model ─────────────────────────────────────────────
        case 'train':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $assetId = (int)($input['asset_id'] ?? 0);
            $modelType = $input['model_type'] ?? 'corrosion';

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            if ($modelType === 'weibull') {
                // Get asset type
                $asset = $db->query("SELECT asset_type FROM asset_registry WHERE id = ?", [$assetId])->fetch();
                if (!$asset) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Asset not found']);
                    exit;
                }
                $result = $ml->trainFailureProbability($asset['asset_type']);
            } else {
                $result = $ml->trainCorrosionModel($assetId);
            }

            echo json_encode($result);
            break;

        // ── Predict ──────────────────────────────────────────────────
        case 'predict':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $assetId = (int)($input['asset_id'] ?? 0);
            $predType = $input['prediction_type'] ?? 'corrosion_rate';
            $horizon = (float)($input['horizon'] ?? 5);

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            if ($predType === 'failure_probability') {
                $result = $ml->predictFailureProbability($assetId, $horizon);
            } else {
                $result = $ml->predictCorrosionRate($assetId, $horizon);
            }

            echo json_encode($result);
            break;

        // ── List Models ──────────────────────────────────────────────
        case 'models':
            $assetId = (int)($_GET['asset_id'] ?? 0);
            $status = $_GET['status'] ?? 'active';

            $sql = "SELECT m.*, ar.asset_tag, ar.asset_name
                    FROM ml_models m
                    LEFT JOIN asset_registry ar ON m.asset_id = ar.id
                    WHERE 1=1";
            $params = [];

            if ($assetId > 0) {
                $sql .= " AND m.asset_id = ?";
                $params[] = $assetId;
            }
            if ($status !== 'all') {
                $sql .= " AND m.status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY m.trained_at DESC";

            $models = $db->query($sql, $params)->fetchAll();

            echo json_encode(['success' => true, 'models' => $models]);
            break;

        // ── Model Accuracy ───────────────────────────────────────────
        case 'accuracy':
            $modelId = (int)($_GET['id'] ?? 0);
            if ($modelId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Model id is required']);
                exit;
            }
            $result = $ml->getModelAccuracy($modelId);
            echo json_encode($result);
            break;

        // ── Anomaly Detection ────────────────────────────────────────
        case 'anomaly_detect':
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

            $result = $ml->anomalyDetection($assetId);
            echo json_encode($result);
            break;

        // ── Retrain All ──────────────────────────────────────────────
        case 'retrain_all':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }
            $result = $ml->retrainAllModels();
            echo json_encode(['success' => true, 'results' => $result]);
            break;

        // ── Trend Analysis ───────────────────────────────────────────
        case 'trend':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? $_GET;
            $assetId = (int)($input['asset_id'] ?? 0);
            $parameter = $input['parameter'] ?? 'thickness';

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            $result = $ml->trendAnalysis($assetId, $parameter);
            echo json_encode($result);
            break;

        // ── Health Index ─────────────────────────────────────────────
        case 'health_index':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? $_GET;
            $assetId = (int)($input['asset_id'] ?? 0);

            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'asset_id is required']);
                exit;
            }

            $result = $ml->calculateHealthIndex($assetId);
            echo json_encode($result);
            break;

        // ── Clustering ───────────────────────────────────────────────
        case 'cluster':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $k = (int)($input['k'] ?? 5);

            $result = $ml->clusterAssets($k);
            echo json_encode($result);
            break;

        // ── Get Clusters ─────────────────────────────────────────────
        case 'get_clusters':
            $clusters = $db->query(
                "SELECT ac.*, ar.asset_tag, ar.asset_name, ar.asset_type,
                        ar.criticality, ar.status
                 FROM asset_clusters ac
                 JOIN asset_registry ar ON ac.asset_id = ar.id
                 ORDER BY ac.cluster_id, ac.distance_to_centroid ASC"
            )->fetchAll();

            // Group by cluster
            $grouped = [];
            foreach ($clusters as $c) {
                $cid = $c['cluster_id'];
                if (!isset($grouped[$cid])) {
                    $grouped[$cid] = [
                        'cluster_id'   => $cid,
                        'cluster_name' => $c['cluster_name'],
                        'centroid'     => json_decode($c['centroid'], true),
                        'assets'       => [],
                    ];
                }
                $grouped[$cid]['assets'][] = $c;
            }

            echo json_encode(['success' => true, 'clusters' => array_values($grouped)]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }

} catch (\Throwable $e) {
    error_log('[ML API] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => APP_ENV === 'development' ? $e->getMessage() : 'Internal server error',
    ]);
}
