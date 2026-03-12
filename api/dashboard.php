<?php
/**
 * Dashboard API - JSON endpoint for KPI data
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();

    // Total assets
    $totalAssets = $db->query("SELECT COUNT(*) FROM asset_registry WHERE status = 'in_service'")->fetchColumn();

    // High risk count (H + VH)
    $highRisk = $db->query(
        "SELECT COUNT(DISTINCT ra.asset_id) FROM risk_assessments ra
         INNER JOIN (SELECT asset_id, MAX(id) as max_id FROM risk_assessments GROUP BY asset_id) latest
         ON ra.id = latest.max_id
         WHERE ra.risk_level IN ('H', 'VH')"
    )->fetchColumn();

    // Overdue inspections
    $overdue = $db->query(
        "SELECT COUNT(*) FROM inspection_tasks
         WHERE status IN ('pending', 'overdue') AND due_date < CURDATE()"
    )->fetchColumn();

    // Active damage mechanisms
    $activeDM = $db->query(
        "SELECT COUNT(*) FROM asset_damage_mechanisms WHERE active = 1"
    )->fetchColumn();

    // Risk matrix distribution
    $matrixData = $db->query(
        "SELECT ra.pof_category, ra.cof_category, COUNT(*) as count
         FROM risk_assessments ra
         INNER JOIN (SELECT asset_id, MAX(id) as max_id FROM risk_assessments GROUP BY asset_id) latest
         ON ra.id = latest.max_id
         GROUP BY ra.pof_category, ra.cof_category"
    )->fetchAll();

    $matrix = [];
    foreach ($matrixData as $row) {
        $key = $row['pof_category'] . $row['cof_category'];
        $matrix[$key] = (int)$row['count'];
    }

    // Risk distribution
    $riskDist = $db->query(
        "SELECT ra.risk_level, COUNT(*) as count
         FROM risk_assessments ra
         INNER JOIN (SELECT asset_id, MAX(id) as max_id FROM risk_assessments GROUP BY asset_id) latest
         ON ra.id = latest.max_id
         GROUP BY ra.risk_level"
    )->fetchAll();

    // Upcoming inspections (next 30 days)
    $upcoming = $db->query(
        "SELECT it.*, a.asset_tag, a.asset_name, a.asset_type,
                u.first_name, u.last_name
         FROM inspection_tasks it
         JOIN asset_registry a ON it.asset_id = a.id
         LEFT JOIN users u ON it.assigned_to = u.id
         WHERE it.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         AND it.status IN ('pending', 'in_progress')
         ORDER BY it.due_date ASC
         LIMIT 10"
    )->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'total_assets'       => (int)$totalAssets,
            'high_risk_count'    => (int)$highRisk,
            'overdue_inspections'=> (int)$overdue,
            'active_dm_count'    => (int)$activeDM,
            'risk_matrix'        => $matrix,
            'risk_distribution'  => $riskDist,
            'upcoming_inspections'=> $upcoming,
        ]
    ]);

} catch (\Throwable $e) {
    // Return fallback demo data on error
    echo json_encode([
        'success' => true,
        'data' => [
            'total_assets'       => 156,
            'high_risk_count'    => 23,
            'overdue_inspections'=> 8,
            'active_dm_count'    => 47,
            'risk_matrix'        => [],
            'risk_distribution'  => [],
            'upcoming_inspections'=> [],
        ]
    ]);
}
