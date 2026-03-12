<?php
/**
 * Report - Report generation, export, and dashboard data
 *
 * Generates PDF/CSV reports and supplies KPI/dashboard data.
 */
class Report
{
    private Database $db;

    /** Available report types */
    private const REPORT_TYPES = [
        'risk_summary'       => 'Risk Summary Report',
        'risk_ranking'       => 'Risk Ranking Report',
        'risk_matrix'        => 'Risk Matrix Report',
        'inspection_plan'    => 'Inspection Plan Report',
        'inspection_history' => 'Inspection History Report',
        'remaining_life'     => 'Remaining Life Report',
        'corrosion_rate'     => 'Corrosion Rate Report',
        'damage_mechanism'   => 'Damage Mechanism Report',
        'asset_register'     => 'Asset Register Report',
        'financial_risk'     => 'Financial Risk Report',
        'compliance'         => 'Regulatory Compliance Report',
        'kpi_dashboard'      => 'KPI Dashboard Report',
    ];

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── Report Generation ───────────────────────────────────────────

    /**
     * Generate a report by type
     *
     * @return array  Report data and metadata
     */
    public function generateReport(string $reportType, array $params = []): array
    {
        if (!isset(self::REPORT_TYPES[$reportType])) {
            throw new InvalidArgumentException("Unknown report type: {$reportType}");
        }

        $report = [
            'report_type'  => $reportType,
            'title'        => self::REPORT_TYPES[$reportType],
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $_SESSION['user_name'] ?? 'System',
            'parameters'   => $params,
            'data'         => [],
        ];

        $report['data'] = match ($reportType) {
            'risk_summary'       => $this->riskSummaryData($params),
            'risk_ranking'       => $this->riskRankingData($params),
            'risk_matrix'        => $this->riskMatrixData($params),
            'inspection_plan'    => $this->inspectionPlanData($params),
            'inspection_history' => $this->inspectionHistoryData($params),
            'remaining_life'     => $this->remainingLifeData($params),
            'corrosion_rate'     => $this->corrosionRateData($params),
            'damage_mechanism'   => $this->damageMechanismData($params),
            'asset_register'     => $this->assetRegisterData($params),
            'financial_risk'     => $this->financialRiskData($params),
            'compliance'         => $this->complianceData($params),
            'kpi_dashboard'      => $this->getDashboardData(),
            default              => [],
        };

        // Log report generation
        $this->logReport($report);

        return $report;
    }

    // ── Export ───────────────────────────────────────────────────────

    /**
     * Export report data as PDF (generates HTML for conversion)
     *
     * Returns the HTML content suitable for a PDF renderer (e.g., mPDF, DOMPDF, wkhtmltopdf).
     */
    public function exportPDF(string $reportType, array $params = []): string
    {
        $report = $this->generateReport($reportType, $params);

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<title>' . htmlspecialchars($report['title']) . '</title>';
        $html .= '<style>';
        $html .= 'body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }';
        $html .= 'h1 { color: #1a237e; border-bottom: 2px solid #1a237e; padding-bottom: 5px; font-size: 18px; }';
        $html .= 'h2 { color: #283593; font-size: 14px; margin-top: 20px; }';
        $html .= '.meta { color: #666; font-size: 10px; margin-bottom: 15px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin: 10px 0; }';
        $html .= 'th { background: #1a237e; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }';
        $html .= 'td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }';
        $html .= 'tr:nth-child(even) { background: #f5f5f5; }';
        $html .= '.risk-vh { background: #721c24; color: #fff; padding: 2px 6px; }';
        $html .= '.risk-h  { background: #dc3545; color: #fff; padding: 2px 6px; }';
        $html .= '.risk-mh { background: #fd7e14; color: #fff; padding: 2px 6px; }';
        $html .= '.risk-m  { background: #ffc107; color: #333; padding: 2px 6px; }';
        $html .= '.risk-l  { background: #28a745; color: #fff; padding: 2px 6px; }';
        $html .= '.footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 9px; color: #999; }';
        $html .= '</style></head><body>';

        // Header
        $html .= '<h1>' . htmlspecialchars(APP_NAME) . '</h1>';
        $html .= '<h2>' . htmlspecialchars($report['title']) . '</h2>';
        $html .= '<div class="meta">Generated: ' . $report['generated_at'] . ' | By: ' . htmlspecialchars($report['generated_by']) . '</div>';

        // Data table
        if (!empty($report['data']) && is_array($report['data'])) {
            $data = $report['data'];

            // If the data has sub-arrays (tabular data)
            if (isset($data[0]) && is_array($data[0])) {
                $html .= '<table>';
                $html .= '<tr>';
                foreach (array_keys($data[0]) as $header) {
                    $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
                }
                $html .= '</tr>';

                foreach ($data as $row) {
                    $html .= '<tr>';
                    foreach ($row as $key => $val) {
                        $display = is_array($val) ? json_encode($val) : htmlspecialchars((string) $val);
                        if ($key === 'risk_level') {
                            $class = 'risk-' . strtolower($val);
                            $display = '<span class="' . $class . '">' . $display . '</span>';
                        }
                        $html .= '<td>' . $display . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</table>';
            } else {
                // Key-value display
                $html .= '<table>';
                foreach ($data as $key => $val) {
                    $html .= '<tr><th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</th>';
                    $html .= '<td>' . htmlspecialchars(is_array($val) ? json_encode($val) : (string) $val) . '</td></tr>';
                }
                $html .= '</table>';
            }
        }

        // Footer
        $html .= '<div class="footer">' . APP_NAME . ' v' . APP_VERSION . ' | Confidential</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Export report data as CSV
     *
     * @return string  File path of generated CSV
     */
    public function exportCSV(string $reportType, array $params = []): string
    {
        $report = $this->generateReport($reportType, $params);

        $filename = $reportType . '_' . date('Ymd_His') . '.csv';
        $filePath = UPLOADS_PATH . '/reports/' . $filename;

        // Ensure directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($filePath, 'w');
        if (!$handle) {
            throw new RuntimeException('Cannot create CSV file.');
        }

        $data = $report['data'];

        if (!empty($data) && isset($data[0]) && is_array($data[0])) {
            // Header row
            fputcsv($handle, array_keys($data[0]));
            // Data rows
            foreach ($data as $row) {
                $flat = array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $row);
                fputcsv($handle, $flat);
            }
        } elseif (!empty($data)) {
            fputcsv($handle, ['Key', 'Value']);
            foreach ($data as $key => $val) {
                fputcsv($handle, [$key, is_array($val) ? json_encode($val) : $val]);
            }
        }

        fclose($handle);

        return $filePath;
    }

    // ── Dashboard & KPIs ────────────────────────────────────────────

    /**
     * Get dashboard data for the main overview page
     */
    public function getDashboardData(): array
    {
        $analytics = new IntegrityAnalytics();

        return [
            'asset_summary'      => $this->getAssetSummary(),
            'risk_distribution'  => $this->getRiskDistribution(),
            'upcoming_tasks'     => $this->getUpcomingTasks(10),
            'overdue_tasks'      => $this->getOverdueTasks(),
            'recent_assessments' => $this->getRecentAssessments(10),
            'kpis'               => $this->getKPIs(),
            'alerts'             => $this->getActiveAlerts(),
        ];
    }

    /**
     * Get Key Performance Indicators
     */
    public function getKPIs(): array
    {
        // Total assets
        $totalAssets = (int) $this->db->query("SELECT COUNT(*) FROM assets WHERE status = 'active'")->fetchColumn();

        // Assets assessed
        $assessed = (int) $this->db->query(
            "SELECT COUNT(DISTINCT asset_id) FROM risk_assessments"
        )->fetchColumn();
        $assessmentCoverage = $totalAssets > 0 ? round(($assessed / $totalAssets) * 100, 1) : 0;

        // High/Very High risk count
        $highRiskCount = (int) $this->db->query(
            "SELECT COUNT(DISTINCT asset_id) FROM risk_assessments
             WHERE risk_level IN ('H', 'VH')
             AND id IN (SELECT MAX(id) FROM risk_assessments GROUP BY asset_id)"
        )->fetchColumn();

        // Inspection compliance (completed on time vs total due)
        $totalDue = (int) $this->db->query(
            "SELECT COUNT(*) FROM inspection_tasks
             WHERE due_date <= CURDATE() AND status IN ('pending','overdue','completed')"
        )->fetchColumn();
        $completedOnTime = (int) $this->db->query(
            "SELECT COUNT(*) FROM inspection_tasks
             WHERE status = 'completed' AND completed_at <= due_date"
        )->fetchColumn();
        $inspectionCompliance = $totalDue > 0 ? round(($completedOnTime / $totalDue) * 100, 1) : 100;

        // Overdue inspections
        $overdueCount = (int) $this->db->query(
            "SELECT COUNT(*) FROM inspection_tasks
             WHERE due_date < CURDATE() AND status IN ('pending','overdue')"
        )->fetchColumn();

        // Average risk value
        $avgRisk = (float) $this->db->query(
            "SELECT AVG(risk_value) FROM risk_assessments
             WHERE id IN (SELECT MAX(id) FROM risk_assessments GROUP BY asset_id)"
        )->fetchColumn();

        // Assets with remaining life < 3 years
        $criticalRL = (int) $this->db->query(
            "SELECT COUNT(*) FROM assets a
             JOIN inspection_readings ir ON a.id = ir.asset_id
             WHERE a.status = 'active'
             AND ir.id = (SELECT MAX(id) FROM inspection_readings WHERE asset_id = a.id)
             AND ir.corrosion_rate > 0
             AND (ir.measured_thickness - a.minimum_thickness) / ir.corrosion_rate < 3"
        )->fetchColumn();

        return [
            'total_assets'           => $totalAssets,
            'assessment_coverage'    => $assessmentCoverage,
            'high_risk_assets'       => $highRiskCount,
            'inspection_compliance'  => $inspectionCompliance,
            'overdue_inspections'    => $overdueCount,
            'avg_risk_value'         => round($avgRisk, 4),
            'critical_remaining_life'=> $criticalRL,
        ];
    }

    // ── Report Data Methods ─────────────────────────────────────────

    private function riskSummaryData(array $params): array
    {
        return $this->db->query(
            "SELECT a.asset_tag, a.name, a.asset_type, f.name AS facility,
                    ra.pof_value, ra.pof_category, ra.cof_value, ra.cof_category,
                    ra.risk_value, ra.risk_level, ra.calculated_at
             FROM risk_assessments ra
             JOIN assets a ON ra.asset_id = a.id
             LEFT JOIN facilities f ON a.facility_id = f.id
             WHERE ra.id IN (SELECT MAX(id) FROM risk_assessments GROUP BY asset_id)
             ORDER BY ra.risk_value DESC"
        )->fetchAll();
    }

    private function riskRankingData(array $params): array
    {
        $limit = $params['limit'] ?? 50;
        return (new RiskAssessment())->getRiskRanking($params, $limit);
    }

    private function riskMatrixData(array $params): array
    {
        return $this->db->query(
            "SELECT ra.pof_category, ra.cof_category, ra.risk_level, COUNT(*) AS count
             FROM risk_assessments ra
             WHERE ra.id IN (SELECT MAX(id) FROM risk_assessments GROUP BY asset_id)
             GROUP BY ra.pof_category, ra.cof_category, ra.risk_level
             ORDER BY ra.pof_category DESC, ra.cof_category"
        )->fetchAll();
    }

    private function inspectionPlanData(array $params): array
    {
        return $this->db->query(
            "SELECT ip.plan_name, a.asset_tag, a.name AS asset_name, ip.plan_type,
                    ip.strategy, ip.interval_months, ip.next_due_date, ip.risk_level,
                    ip.priority, ip.status
             FROM inspection_plans ip
             JOIN assets a ON ip.asset_id = a.id
             WHERE ip.status = 'active'
             ORDER BY ip.next_due_date ASC"
        )->fetchAll();
    }

    private function inspectionHistoryData(array $params): array
    {
        $where  = ['1=1'];
        $qParams = [];

        if (!empty($params['asset_id'])) {
            $where[]  = 'inf.asset_id = ?';
            $qParams[] = $params['asset_id'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query(
            "SELECT a.asset_tag, a.name AS asset_name, inf.inspection_date,
                    inf.method_used, inf.measured_thickness, inf.corrosion_rate,
                    inf.condition_grade, inf.findings_summary,
                    CONCAT(u.first_name, ' ', u.last_name) AS inspector
             FROM inspection_findings inf
             JOIN assets a ON inf.asset_id = a.id
             LEFT JOIN users u ON inf.inspector_id = u.id
             WHERE {$whereClause}
             ORDER BY inf.inspection_date DESC",
            $qParams
        )->fetchAll();
    }

    private function remainingLifeData(array $params): array
    {
        return $this->db->query(
            "SELECT a.asset_tag, a.name, a.asset_type, a.nominal_thickness,
                    a.minimum_thickness, ir.measured_thickness, ir.corrosion_rate,
                    ir.reading_date AS last_reading,
                    CASE
                        WHEN ir.corrosion_rate > 0 THEN ROUND((ir.measured_thickness - a.minimum_thickness) / ir.corrosion_rate, 1)
                        ELSE NULL
                    END AS remaining_life_years
             FROM assets a
             LEFT JOIN inspection_readings ir ON a.id = ir.asset_id
                AND ir.id = (SELECT MAX(id) FROM inspection_readings WHERE asset_id = a.id)
             WHERE a.status = 'active'
             ORDER BY remaining_life_years ASC"
        )->fetchAll();
    }

    private function corrosionRateData(array $params): array
    {
        return $this->db->query(
            "SELECT a.asset_tag, a.name, a.material,
                    ir.measured_thickness, ir.corrosion_rate, ir.reading_date, ir.method
             FROM inspection_readings ir
             JOIN assets a ON ir.asset_id = a.id
             WHERE ir.corrosion_rate IS NOT NULL
             ORDER BY ir.reading_date DESC
             LIMIT 200"
        )->fetchAll();
    }

    private function damageMechanismData(array $params): array
    {
        return $this->db->query(
            "SELECT a.asset_tag, a.name AS asset_name, dm.code, dm.name AS mechanism,
                    dm.category, adm.susceptibility, adm.damage_rate, adm.notes
             FROM asset_damage_mechanisms adm
             JOIN assets a ON adm.asset_id = a.id
             JOIN damage_mechanisms dm ON adm.mechanism_id = dm.id
             WHERE adm.active = 1
             ORDER BY FIELD(adm.susceptibility, 'high', 'medium', 'low'), a.asset_tag"
        )->fetchAll();
    }

    private function assetRegisterData(array $params): array
    {
        return $this->db->query(
            "SELECT a.asset_tag, a.name, a.asset_type, a.material,
                    a.design_pressure, a.design_temperature,
                    a.operating_pressure, a.operating_temperature,
                    a.nominal_thickness, a.minimum_thickness,
                    a.install_date, a.status, a.criticality,
                    f.name AS facility
             FROM assets a
             LEFT JOIN facilities f ON a.facility_id = f.id
             ORDER BY a.asset_tag"
        )->fetchAll();
    }

    private function financialRiskData(array $params): array
    {
        return $this->db->query(
            "SELECT a.asset_tag, a.name, a.asset_type,
                    ra.pof_value, ra.cof_value, ra.risk_value, ra.risk_level,
                    ROUND(ra.pof_value * ra.cof_value, 2) AS annual_expected_loss
             FROM risk_assessments ra
             JOIN assets a ON ra.asset_id = a.id
             WHERE ra.id IN (SELECT MAX(id) FROM risk_assessments GROUP BY asset_id)
             ORDER BY annual_expected_loss DESC"
        )->fetchAll();
    }

    private function complianceData(array $params): array
    {
        return $this->db->query(
            "SELECT a.asset_tag, a.name, a.asset_type,
                    ip.interval_months, ip.next_due_date,
                    CASE WHEN ip.next_due_date < CURDATE() THEN 'Overdue'
                         WHEN ip.next_due_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Due Soon'
                         ELSE 'Compliant'
                    END AS compliance_status,
                    MAX(inf.inspection_date) AS last_inspection
             FROM assets a
             LEFT JOIN inspection_plans ip ON a.id = ip.asset_id AND ip.status = 'active'
             LEFT JOIN inspection_findings inf ON a.id = inf.asset_id
             WHERE a.status = 'active'
             GROUP BY a.id, a.asset_tag, a.name, a.asset_type, ip.interval_months, ip.next_due_date
             ORDER BY ip.next_due_date ASC"
        )->fetchAll();
    }

    // ── Dashboard Helpers ───────────────────────────────────────────

    private function getAssetSummary(): array
    {
        return $this->db->query(
            "SELECT asset_type, COUNT(*) AS count, status
             FROM assets
             WHERE status = 'active'
             GROUP BY asset_type, status
             ORDER BY count DESC"
        )->fetchAll();
    }

    private function getRiskDistribution(): array
    {
        return $this->db->query(
            "SELECT risk_level, COUNT(*) AS count
             FROM risk_assessments
             WHERE id IN (SELECT MAX(id) FROM risk_assessments GROUP BY asset_id)
             GROUP BY risk_level
             ORDER BY FIELD(risk_level, 'VH', 'H', 'MH', 'M', 'L')"
        )->fetchAll();
    }

    private function getUpcomingTasks(int $limit = 10): array
    {
        return $this->db->query(
            "SELECT it.*, a.name AS asset_name, a.asset_tag
             FROM inspection_tasks it
             JOIN assets a ON it.asset_id = a.id
             WHERE it.status = 'pending' AND it.due_date >= CURDATE()
             ORDER BY it.due_date ASC
             LIMIT {$limit}"
        )->fetchAll();
    }

    private function getOverdueTasks(): array
    {
        return $this->db->query(
            "SELECT it.*, a.name AS asset_name, a.asset_tag,
                    DATEDIFF(CURDATE(), it.due_date) AS days_overdue
             FROM inspection_tasks it
             JOIN assets a ON it.asset_id = a.id
             WHERE it.status IN ('pending', 'overdue') AND it.due_date < CURDATE()
             ORDER BY it.due_date ASC"
        )->fetchAll();
    }

    private function getRecentAssessments(int $limit = 10): array
    {
        return $this->db->query(
            "SELECT ra.*, a.name AS asset_name, a.asset_tag
             FROM risk_assessments ra
             JOIN assets a ON ra.asset_id = a.id
             ORDER BY ra.calculated_at DESC
             LIMIT {$limit}"
        )->fetchAll();
    }

    private function getActiveAlerts(): array
    {
        return $this->db->query(
            "SELECT al.*, a.name AS asset_name, a.asset_tag
             FROM alerts al
             LEFT JOIN assets a ON al.asset_id = a.id
             WHERE al.is_read = 0
             ORDER BY FIELD(al.severity, 'critical', 'warning', 'info'), al.created_at DESC
             LIMIT 20"
        )->fetchAll();
    }

    // ── Logging ─────────────────────────────────────────────────────

    private function logReport(array $report): void
    {
        try {
            $this->db->insert('report_log', [
                'report_type'  => $report['report_type'],
                'title'        => $report['title'],
                'parameters'   => json_encode($report['parameters']),
                'generated_by' => $report['generated_by'],
                'generated_at' => $report['generated_at'],
            ]);
        } catch (\Throwable $e) {
            error_log('[RBI Report] Log failed: ' . $e->getMessage());
        }
    }
}
