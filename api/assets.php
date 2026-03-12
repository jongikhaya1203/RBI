<?php
/**
 * Assets API - JSON CRUD endpoint
 * GET /api/assets.php           - List assets
 * GET /api/assets.php?id=N      - Get single asset
 * POST /api/assets.php          - Create asset
 * PUT /api/assets.php?id=N      - Update asset
 * DELETE /api/assets.php?id=N   - Delete (decommission) asset
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$assetId = (int)($_GET['id'] ?? 0);

try {
    $db = new Database();

    switch ($method) {
        case 'GET':
            if ($assetId > 0) {
                // Get single asset
                $asset = $db->query(
                    "SELECT ar.*, dd.material_spec, dd.nominal_thickness_mm,
                            dd.minimum_required_thickness_mm, dd.design_pressure_mpa,
                            dd.design_temperature_c, dd.corrosion_allowance_mm,
                            eh.name AS location_name
                     FROM asset_registry ar
                     LEFT JOIN design_data dd ON ar.id = dd.asset_id
                     LEFT JOIN equipment_hierarchy eh ON ar.hierarchy_id = eh.id
                     WHERE ar.id = ?",
                    [$assetId]
                )->fetch();

                if (!$asset) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Asset not found']);
                    exit;
                }

                echo json_encode(['success' => true, 'data' => $asset]);
            } else {
                // List assets with pagination
                $page = max(1, (int)($_GET['page'] ?? 1));
                $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 25)));
                $offset = ($page - 1) * $perPage;

                $where = ['1=1'];
                $params = [];

                if (!empty($_GET['type'])) {
                    $where[] = 'ar.asset_type = ?';
                    $params[] = $_GET['type'];
                }
                if (!empty($_GET['status'])) {
                    $where[] = 'ar.status = ?';
                    $params[] = $_GET['status'];
                }
                if (!empty($_GET['search'])) {
                    $where[] = '(ar.asset_tag LIKE ? OR ar.asset_name LIKE ?)';
                    $term = '%' . $_GET['search'] . '%';
                    $params[] = $term;
                    $params[] = $term;
                }

                $whereClause = implode(' AND ', $where);

                $total = $db->query("SELECT COUNT(*) FROM asset_registry ar WHERE {$whereClause}", $params)->fetchColumn();

                $assets = $db->query(
                    "SELECT ar.id, ar.asset_tag, ar.asset_name, ar.asset_type,
                            ar.status, ar.criticality, ar.rbi_status
                     FROM asset_registry ar
                     WHERE {$whereClause}
                     ORDER BY ar.asset_tag ASC
                     LIMIT {$perPage} OFFSET {$offset}",
                    $params
                )->fetchAll();

                echo json_encode([
                    'success' => true,
                    'data' => $assets,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => $page,
                        'per_page' => $perPage,
                        'total_pages' => ceil($total / $perPage),
                    ]
                ]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            // Validate CSRF for form submissions
            if (!empty($input['csrf_token']) && !verifyCsrfToken($input['csrf_token'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                exit;
            }

            // Validate required fields
            if (empty($input['asset_tag']) || empty($input['asset_name'] ?? $input['name'] ?? '') || empty($input['asset_type'])) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'asset_tag, name, and asset_type are required']);
                exit;
            }

            $newId = $db->insert('asset_registry', [
                'asset_tag'    => $input['asset_tag'],
                'asset_name'   => $input['asset_name'] ?? $input['name'],
                'asset_type'   => $input['asset_type'],
                'status'       => $input['status'] ?? 'in_service',
                'criticality'  => $input['criticality'] ?? 'medium',
                'created_by'   => $auth->getUserId(),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            // Insert design data if provided
            if (!empty($input['nominal_thickness']) || !empty($input['design_pressure'])) {
                $db->insert('design_data', [
                    'asset_id'                     => $newId,
                    'material_spec'                => $input['material'] ?? null,
                    'nominal_thickness_mm'         => $input['nominal_thickness'] ?? null,
                    'minimum_required_thickness_mm'=> $input['minimum_thickness'] ?? null,
                    'design_pressure_mpa'          => $input['design_pressure'] ?? null,
                    'design_temperature_c'         => $input['design_temperature'] ?? null,
                    'corrosion_allowance_mm'       => $input['corrosion_allowance'] ?? null,
                ]);
            }

            http_response_code(201);
            echo json_encode(['success' => true, 'data' => ['id' => $newId], 'message' => 'Asset created successfully']);
            break;

        case 'PUT':
            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Asset ID required']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
                exit;
            }

            $allowed = ['asset_name', 'asset_type', 'status', 'criticality', 'rbi_status'];
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            foreach ($allowed as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }

            $db->update('asset_registry', $updateData, 'id = ?', [$assetId]);
            echo json_encode(['success' => true, 'message' => 'Asset updated successfully']);
            break;

        case 'DELETE':
            if ($assetId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Asset ID required']);
                exit;
            }

            // Soft delete - set status to retired
            $db->update('asset_registry', [
                'status' => 'retired',
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$assetId]);

            echo json_encode(['success' => true, 'message' => 'Asset decommissioned successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
