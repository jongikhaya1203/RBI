USE `rbi_engineering`;

SET FOREIGN_KEY_CHECKS = 0;

-- Seed Equipment Hierarchy
INSERT IGNORE INTO `equipment_hierarchy` (`id`, `name`, `code`, `level`, `parent_id`, `description`) VALUES
(2, 'Refinery Plant A', 'PLT-A', 'plant', 1, 'Main Refinery Plant A'),
(3, 'Crude Distillation Unit', 'CDU-100', 'unit', 2, 'Primary Crude Distillation Unit');

-- Seed Assets
INSERT IGNORE INTO `asset_registry` (`id`, `asset_tag`, `asset_name`, `hierarchy_id`, `asset_type`, `status`, `criticality`, `rbi_status`) VALUES
(1, 'V-101', 'Crude Desalter', 3, 'pressure_vessel', 'in_service', 'critical', 'assessed'),
(2, 'E-205', 'Pre-heat Exchanger', 3, 'heat_exchanger', 'in_service', 'high', 'assessed'),
(3, 'P-302A', 'Crude Charge Pump', 3, 'pump', 'in_service', 'medium', 'assessed');

-- Seed Risk Scores (Populates the dashboard)
INSERT IGNORE INTO `risk_scores` (`asset_id`, `overall_risk`, `pof_score`, `cof_score`, `health_index`, `risk_category`, `scoring_method`) VALUES
(1, 16.5, 4.2, 3.9, 45.0, 'high', 'ml_enhanced'),
(2, 12.0, 3.5, 3.4, 65.0, 'medium', 'ml_enhanced'),
(3, 8.5, 2.8, 3.0, 80.0, 'low', 'ml_enhanced');

SET FOREIGN_KEY_CHECKS = 1;