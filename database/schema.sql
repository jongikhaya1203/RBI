-- =============================================================================
-- RBI (Risk-Based Inspection) Engineering Platform
-- Enterprise Database Schema
-- =============================================================================
-- Benchmarked against: DNV Synergi RBI, GE Meridium APM, Cenosco IMS PEI,
--   Equity Engineering PlantManager, TWI RiskWISE, PCMS RBI
-- Standards: API 580/581, API 571, ASME PCC-3, DNV-RP-G101
-- =============================================================================

DROP DATABASE IF EXISTS `rbi_engineering`;
CREATE DATABASE `rbi_engineering`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `rbi_engineering`;

SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- =============================================================================
-- MODULE 1: USER MANAGEMENT & ACCESS CONTROL
-- =============================================================================

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(100) NOT NULL,
  `role_key` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `is_system_role` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_key` (`role_key`)
) ENGINE=InnoDB COMMENT='Access control roles for the platform';

CREATE TABLE `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `permission_key` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL COMMENT 'Functional module this permission belongs to',
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permission_key` (`permission_key`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB COMMENT='Granular permissions for RBAC';

CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `idx_permission_id` (`permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Maps roles to their permissions';

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `job_title` VARCHAR(150) NULL,
  `department` VARCHAR(150) NULL,
  `phone` VARCHAR(30) NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `status` ENUM('active','inactive','locked','pending') NOT NULL DEFAULT 'pending',
  `last_login_at` DATETIME NULL,
  `password_changed_at` DATETIME NULL,
  `failed_login_attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `preferences` JSON NULL COMMENT 'User-specific UI and notification preferences',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB COMMENT='Platform users and authentication credentials';

CREATE TABLE `user_activity_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NULL,
  `entity_type` VARCHAR(100) NULL,
  `entity_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `details` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_ual_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Tracks all user actions for security and compliance auditing';

CREATE TABLE `audit_trail` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `table_name` VARCHAR(100) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `action` ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `change_reason` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_at_user` (`user_id`),
  KEY `idx_at_table_record` (`table_name`, `record_id`),
  KEY `idx_at_created` (`created_at`),
  CONSTRAINT `fk_at_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Full change-data-capture audit trail for regulatory compliance';

-- =============================================================================
-- MODULE 2: ASSET INTEGRITY MANAGEMENT
-- =============================================================================

CREATE TABLE `equipment_hierarchy` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL COMMENT 'Short alphanumeric code e.g. UNIT-100',
  `level` ENUM('company','site','plant','unit','system','subsystem') NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `description` TEXT NULL,
  `location_lat` DECIMAL(10,7) NULL,
  `location_lng` DECIMAL(10,7) NULL,
  `metadata` JSON NULL,
  `status` ENUM('active','inactive','decommissioned') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_eh_code` (`code`),
  KEY `idx_eh_parent` (`parent_id`),
  KEY `idx_eh_level` (`level`),
  KEY `idx_eh_status` (`status`),
  CONSTRAINT `fk_eh_parent` FOREIGN KEY (`parent_id`) REFERENCES `equipment_hierarchy` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_eh_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Multi-level functional location hierarchy (company > site > plant > unit > system)';

CREATE TABLE `asset_registry` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_tag` VARCHAR(50) NOT NULL COMMENT 'Unique plant tag number',
  `asset_name` VARCHAR(255) NOT NULL,
  `hierarchy_id` INT UNSIGNED NULL COMMENT 'Links to equipment_hierarchy functional location',
  `asset_type` ENUM(
    'pressure_vessel','heat_exchanger','storage_tank','piping',
    'column','reactor','boiler','fired_heater','pump',
    'compressor','valve','relief_device','structural','other'
  ) NOT NULL,
  `asset_subtype` VARCHAR(100) NULL,
  `serial_number` VARCHAR(100) NULL,
  `manufacturer` VARCHAR(255) NULL,
  `model_number` VARCHAR(100) NULL,
  `year_manufactured` YEAR NULL,
  `installation_date` DATE NULL,
  `commission_date` DATE NULL,
  `design_code` VARCHAR(100) NULL COMMENT 'e.g. ASME VIII Div 1, API 650, EN 13445',
  `design_code_year` VARCHAR(10) NULL,
  `p_and_id_reference` VARCHAR(100) NULL COMMENT 'P&ID drawing reference',
  `pfd_reference` VARCHAR(100) NULL,
  `status` ENUM('in_service','out_of_service','mothballed','retired','pending_install') NOT NULL DEFAULT 'in_service',
  `criticality` ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `rbi_status` ENUM('not_assessed','in_progress','assessed','overdue') NOT NULL DEFAULT 'not_assessed',
  `last_rbi_date` DATE NULL,
  `next_rbi_date` DATE NULL,
  `photo_url` VARCHAR(500) NULL,
  `documents` JSON NULL COMMENT 'Array of linked document references',
  `custom_fields` JSON NULL COMMENT 'Client-specific additional fields',
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asset_tag` (`asset_tag`),
  KEY `idx_ar_hierarchy` (`hierarchy_id`),
  KEY `idx_ar_type` (`asset_type`),
  KEY `idx_ar_status` (`status`),
  KEY `idx_ar_criticality` (`criticality`),
  KEY `idx_ar_rbi_status` (`rbi_status`),
  KEY `idx_ar_next_rbi` (`next_rbi_date`),
  CONSTRAINT `fk_ar_hierarchy` FOREIGN KEY (`hierarchy_id`) REFERENCES `equipment_hierarchy` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ar_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Master equipment register — central asset record analogous to GE APM Asset entity';

CREATE TABLE `design_data` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `material_spec` VARCHAR(100) NULL COMMENT 'e.g. SA-516 Gr 70, SS 316L',
  `material_grade` VARCHAR(50) NULL,
  `nominal_thickness_mm` DECIMAL(8,3) NULL,
  `corrosion_allowance_mm` DECIMAL(8,3) NULL,
  `minimum_required_thickness_mm` DECIMAL(8,3) NULL COMMENT 't_min per code calculation',
  `design_pressure_mpa` DECIMAL(10,4) NULL,
  `design_temperature_c` DECIMAL(8,2) NULL,
  `mawp_mpa` DECIMAL(10,4) NULL COMMENT 'Maximum allowable working pressure',
  `test_pressure_mpa` DECIMAL(10,4) NULL,
  `joint_efficiency` DECIMAL(4,3) NULL COMMENT 'Weld joint efficiency factor',
  `diameter_mm` DECIMAL(10,2) NULL,
  `length_mm` DECIMAL(10,2) NULL,
  `volume_m3` DECIMAL(12,4) NULL,
  `weight_kg` DECIMAL(12,2) NULL,
  `nps_inches` VARCHAR(20) NULL COMMENT 'Nominal pipe size for piping',
  `schedule` VARCHAR(20) NULL COMMENT 'Pipe schedule',
  `shell_material` VARCHAR(100) NULL,
  `head_material` VARCHAR(100) NULL,
  `lining_type` VARCHAR(100) NULL,
  `coating_type` VARCHAR(100) NULL,
  `insulation_type` VARCHAR(100) NULL,
  `insulation_thickness_mm` DECIMAL(8,3) NULL,
  `heat_treatment` VARCHAR(100) NULL COMMENT 'e.g. PWHT, normalised',
  `nde_requirements` VARCHAR(255) NULL,
  `additional_specs` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dd_asset` (`asset_id`),
  CONSTRAINT `fk_dd_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Design/fabrication data per asset — wall thicknesses, materials, code data';

CREATE TABLE `operational_data` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `operating_pressure_mpa` DECIMAL(10,4) NULL,
  `operating_temperature_c` DECIMAL(8,2) NULL,
  `operating_temperature_min_c` DECIMAL(8,2) NULL,
  `operating_temperature_max_c` DECIMAL(8,2) NULL,
  `flow_rate_m3h` DECIMAL(12,4) NULL,
  `fluid_service` VARCHAR(150) NULL COMMENT 'Primary process fluid',
  `fluid_phase` ENUM('gas','liquid','two_phase','multiphase','solid') NULL,
  `h2s_content_ppm` DECIMAL(12,4) NULL,
  `co2_content_pct` DECIMAL(8,4) NULL,
  `chloride_content_ppm` DECIMAL(12,4) NULL,
  `water_content_pct` DECIMAL(8,4) NULL,
  `ph_value` DECIMAL(4,2) NULL,
  `amine_concentration_wt_pct` DECIMAL(8,4) NULL,
  `caustic_concentration_wt_pct` DECIMAL(8,4) NULL,
  `hydrogen_partial_pressure_mpa` DECIMAL(10,4) NULL,
  `velocity_ms` DECIMAL(10,4) NULL,
  `cycles_per_year` INT UNSIGNED NULL COMMENT 'Pressure/thermal cycles for fatigue assessment',
  `startup_shutdown_frequency` VARCHAR(50) NULL,
  `injection_points` TEXT NULL COMMENT 'Chemical injection details',
  `dead_legs` TINYINT(1) NOT NULL DEFAULT 0,
  `soil_side_exposure` TINYINT(1) NOT NULL DEFAULT 0,
  `external_environment` ENUM('marine','industrial','rural','arctic','desert','tropical') NULL,
  `under_insulation` TINYINT(1) NOT NULL DEFAULT 0,
  `heat_traced` TINYINT(1) NOT NULL DEFAULT 0,
  `cathodic_protection` TINYINT(1) NOT NULL DEFAULT 0,
  `additional_operating_data` JSON NULL,
  `effective_date` DATE NOT NULL COMMENT 'Date these operating conditions became effective',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_od_asset` (`asset_id`),
  KEY `idx_od_effective` (`effective_date`),
  CONSTRAINT `fk_od_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Current and historical operating conditions per asset — drives damage mechanism screening';

CREATE TABLE `corrosion_circuits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `circuit_name` VARCHAR(255) NOT NULL,
  `circuit_code` VARCHAR(50) NOT NULL,
  `hierarchy_id` INT UNSIGNED NULL,
  `description` TEXT NULL,
  `process_fluid` VARCHAR(150) NULL,
  `material_spec` VARCHAR(100) NULL,
  `operating_temperature_range` VARCHAR(100) NULL,
  `operating_pressure_range` VARCHAR(100) NULL,
  `expected_damage_mechanisms` TEXT NULL,
  `corrosion_rate_mm_yr` DECIMAL(8,4) NULL COMMENT 'Governing corrosion rate for the circuit',
  `corrosion_rate_basis` ENUM('measured','estimated','default') NULL,
  `status` ENUM('active','inactive','merged') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cc_code` (`circuit_code`),
  KEY `idx_cc_hierarchy` (`hierarchy_id`),
  KEY `idx_cc_status` (`status`),
  CONSTRAINT `fk_cc_hierarchy` FOREIGN KEY (`hierarchy_id`) REFERENCES `equipment_hierarchy` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cc_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Corrosion circuits group assets with similar corrosion environments (API 570/574 concept)';

CREATE TABLE `corrosion_circuit_assets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `circuit_id` INT UNSIGNED NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `cml_reference` VARCHAR(100) NULL COMMENT 'Condition Monitoring Location reference',
  `position_description` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cca_circuit_asset` (`circuit_id`, `asset_id`),
  KEY `idx_cca_asset` (`asset_id`),
  CONSTRAINT `fk_cca_circuit` FOREIGN KEY (`circuit_id`) REFERENCES `corrosion_circuits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cca_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Many-to-many link between corrosion circuits and assets';

-- Legacy-compatible view alias
CREATE OR REPLACE VIEW `assets` AS
SELECT
  ar.id, ar.asset_tag, ar.asset_name, ar.hierarchy_id, ar.asset_type,
  ar.status, ar.criticality, ar.rbi_status,
  dd.material_spec, dd.nominal_thickness_mm, dd.design_pressure_mpa,
  dd.design_temperature_c, dd.mawp_mpa
FROM `asset_registry` ar
LEFT JOIN `design_data` dd ON dd.asset_id = ar.id;

-- =============================================================================
-- MODULE 3: DAMAGE MECHANISM LIBRARY
-- =============================================================================

CREATE TABLE `damage_mechanisms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dm_code` VARCHAR(30) NOT NULL COMMENT 'Short code e.g. CUI, SCC-CL, HTHA',
  `dm_name` VARCHAR(255) NOT NULL,
  `category` ENUM(
    'general_corrosion','localised_corrosion','stress_corrosion_cracking',
    'hydrogen_damage','high_temperature','mechanical_fatigue',
    'erosion','metallurgical','environmental','external'
  ) NOT NULL,
  `api_571_reference` VARCHAR(50) NULL COMMENT 'API 571 section reference',
  `description` TEXT NULL,
  `typical_materials_affected` TEXT NULL,
  `typical_services` TEXT NULL,
  `temperature_range_min_c` DECIMAL(8,2) NULL,
  `temperature_range_max_c` DECIMAL(8,2) NULL,
  `screening_questions` JSON NULL COMMENT 'Array of yes/no screening questions used in susceptibility analysis',
  `default_susceptibility` ENUM('none','low','medium','high') NOT NULL DEFAULT 'low',
  `inspection_methods` JSON NULL COMMENT 'Recommended NDE/inspection techniques',
  `mitigation_measures` JSON NULL COMMENT 'Common mitigation strategies',
  `references_standards` JSON NULL COMMENT 'Applicable codes and standards',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dm_code` (`dm_code`),
  KEY `idx_dm_category` (`category`),
  KEY `idx_dm_active` (`is_active`)
) ENGINE=InnoDB COMMENT='Master library of damage mechanisms per API 571 / API 581 — comparable to Meridium DM catalogue';

CREATE TABLE `damage_mechanism_assignments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `damage_mechanism_id` INT UNSIGNED NOT NULL,
  `susceptibility` ENUM('none','low','medium','high') NOT NULL DEFAULT 'low',
  `severity` ENUM('negligible','minor','moderate','major','critical') NULL,
  `is_active_threat` TINYINT(1) NOT NULL DEFAULT 1,
  `basis` TEXT NULL COMMENT 'Engineering justification for the assignment and susceptibility rating',
  `screening_answers` JSON NULL COMMENT 'Answers to screening questions for this DM on this asset',
  `assigned_by` INT UNSIGNED NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `review_date` DATE NULL,
  `next_review_date` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dma_asset_dm` (`asset_id`, `damage_mechanism_id`),
  KEY `idx_dma_dm` (`damage_mechanism_id`),
  KEY `idx_dma_susceptibility` (`susceptibility`),
  KEY `idx_dma_active` (`is_active_threat`),
  CONSTRAINT `fk_dma_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dma_dm` FOREIGN KEY (`damage_mechanism_id`) REFERENCES `damage_mechanisms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dma_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dma_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Links active damage mechanisms to specific assets with susceptibility ratings';

CREATE TABLE `susceptibility_inputs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` INT UNSIGNED NOT NULL COMMENT 'Links to damage_mechanism_assignments',
  `input_parameter` VARCHAR(150) NOT NULL COMMENT 'e.g. H2S_partial_pressure, temperature, chloride_content',
  `input_value` VARCHAR(255) NOT NULL,
  `input_unit` VARCHAR(30) NULL,
  `data_source` ENUM('design','operational','lab_analysis','field_measurement','assumed') NOT NULL DEFAULT 'operational',
  `confidence` ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
  `notes` TEXT NULL,
  `recorded_date` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_si_assignment` (`assignment_id`),
  KEY `idx_si_parameter` (`input_parameter`),
  CONSTRAINT `fk_si_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `damage_mechanism_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Detailed input parameters driving susceptibility calculations for each DM assignment';

-- =============================================================================
-- MODULE 4: RISK ASSESSMENT ENGINE
-- =============================================================================

CREATE TABLE `risk_matrices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matrix_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `rows_label` VARCHAR(50) NOT NULL DEFAULT 'Probability' COMMENT 'Y-axis label',
  `cols_label` VARCHAR(50) NOT NULL DEFAULT 'Consequence' COMMENT 'X-axis label',
  `num_rows` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `num_cols` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `configuration` JSON NULL COMMENT 'Additional matrix display and calculation settings',
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_default` (`is_default`),
  CONSTRAINT `fk_rm_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Configurable risk matrices (e.g. 5x5 API 581 style)';

CREATE TABLE `risk_matrix_cells` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matrix_id` INT UNSIGNED NOT NULL,
  `row_index` TINYINT UNSIGNED NOT NULL COMMENT '1-based row (probability level)',
  `col_index` TINYINT UNSIGNED NOT NULL COMMENT '1-based column (consequence level)',
  `row_label` VARCHAR(50) NOT NULL COMMENT 'e.g. Very Low, Low, Medium, High, Very High',
  `col_label` VARCHAR(50) NOT NULL,
  `risk_level` ENUM('negligible','low','medium','medium_high','high','very_high') NOT NULL,
  `risk_score` DECIMAL(8,2) NULL COMMENT 'Numeric risk score for ranking',
  `color_hex` VARCHAR(7) NULL COMMENT 'Display colour e.g. #FF0000',
  `action_required` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rmc_cell` (`matrix_id`, `row_index`, `col_index`),
  KEY `idx_rmc_risk_level` (`risk_level`),
  CONSTRAINT `fk_rmc_matrix` FOREIGN KEY (`matrix_id`) REFERENCES `risk_matrices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Individual cells of each risk matrix with risk level, colour, and score';

CREATE TABLE `risk_assessments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_code` VARCHAR(50) NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `matrix_id` INT UNSIGNED NOT NULL,
  `assessment_type` ENUM('qualitative','semi_quantitative','quantitative') NOT NULL DEFAULT 'semi_quantitative',
  `methodology` ENUM('api_581_level1','api_581_level2','api_581_level3','dnv_rp_g101','custom') NOT NULL DEFAULT 'api_581_level1',
  `assessment_date` DATE NOT NULL,
  `review_date` DATE NULL,
  `next_assessment_date` DATE NULL,
  `status` ENUM('draft','in_review','approved','superseded','cancelled') NOT NULL DEFAULT 'draft',

  -- Pre-mitigation (inherent) risk
  `inherent_pof_category` TINYINT UNSIGNED NULL COMMENT 'Probability row index (1-5)',
  `inherent_cof_category` TINYINT UNSIGNED NULL COMMENT 'Consequence column index (1-5)',
  `inherent_risk_level` ENUM('negligible','low','medium','medium_high','high','very_high') NULL,
  `inherent_risk_score` DECIMAL(10,2) NULL,

  -- Post-mitigation (residual) risk — after inspections/mitigations applied
  `residual_pof_category` TINYINT UNSIGNED NULL,
  `residual_cof_category` TINYINT UNSIGNED NULL,
  `residual_risk_level` ENUM('negligible','low','medium','medium_high','high','very_high') NULL,
  `residual_risk_score` DECIMAL(10,2) NULL,

  `risk_target_date` DATE NULL COMMENT 'Target date for risk reduction',
  `overall_confidence` ENUM('high','medium','low') NULL,
  `notes` TEXT NULL,
  `assumptions` TEXT NULL,
  `assessed_by` INT UNSIGNED NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_date` DATE NULL,
  `calculation_data` JSON NULL COMMENT 'Full calculation inputs/outputs for reproducibility',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ra_code` (`assessment_code`),
  KEY `idx_ra_asset` (`asset_id`),
  KEY `idx_ra_status` (`status`),
  KEY `idx_ra_date` (`assessment_date`),
  KEY `idx_ra_inherent_risk` (`inherent_risk_level`),
  KEY `idx_ra_residual_risk` (`residual_risk_level`),
  KEY `idx_ra_next_date` (`next_assessment_date`),
  CONSTRAINT `fk_ra_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ra_matrix` FOREIGN KEY (`matrix_id`) REFERENCES `risk_matrices` (`id`),
  CONSTRAINT `fk_ra_assessed_by` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ra_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ra_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Core risk assessment records per asset — the central RBI output';

CREATE TABLE `probability_of_failure` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` INT UNSIGNED NOT NULL,
  `damage_mechanism_id` INT UNSIGNED NULL,
  `thinning_pof` DECIMAL(12,8) NULL COMMENT 'Annual PoF from thinning (API 581)',
  `scc_pof` DECIMAL(12,8) NULL COMMENT 'Annual PoF from SCC',
  `external_pof` DECIMAL(12,8) NULL COMMENT 'Annual PoF from external damage',
  `htha_pof` DECIMAL(12,8) NULL COMMENT 'Annual PoF from HTHA',
  `brittle_fracture_pof` DECIMAL(12,8) NULL COMMENT 'Annual PoF from brittle fracture',
  `mechanical_fatigue_pof` DECIMAL(12,8) NULL,
  `combined_pof` DECIMAL(12,8) NULL COMMENT 'Combined annual probability of failure',
  `pof_category` TINYINT UNSIGNED NULL COMMENT 'Mapped category (1-5)',
  `art_group` DECIMAL(8,4) NULL COMMENT 'Art (damage rate x time in service)',
  `df_thinning` DECIMAL(12,4) NULL COMMENT 'Damage factor — thinning',
  `df_scc` DECIMAL(12,4) NULL,
  `df_external` DECIMAL(12,4) NULL,
  `df_htha` DECIMAL(12,4) NULL,
  `df_brittle` DECIMAL(12,4) NULL,
  `df_fatigue` DECIMAL(12,4) NULL,
  `df_total` DECIMAL(12,4) NULL COMMENT 'Total damage factor',
  `fms_factor` DECIMAL(6,4) NULL COMMENT 'Management systems factor (API 581)',
  `inspection_effectiveness` ENUM('highly_effective','usually_effective','fairly_effective','poorly_effective','ineffective') NULL,
  `num_inspections` INT UNSIGNED NULL DEFAULT 0,
  `last_inspection_date` DATE NULL,
  `calculation_details` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pof_assessment` (`assessment_id`),
  KEY `idx_pof_dm` (`damage_mechanism_id`),
  KEY `idx_pof_category` (`pof_category`),
  CONSTRAINT `fk_pof_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `risk_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pof_dm` FOREIGN KEY (`damage_mechanism_id`) REFERENCES `damage_mechanisms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Probability of failure calculations per API 581 methodology';

CREATE TABLE `consequence_of_failure` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` INT UNSIGNED NOT NULL,

  -- Flammable/explosive consequence
  `release_rate_kg_s` DECIMAL(12,4) NULL,
  `release_duration_min` DECIMAL(10,2) NULL,
  `release_mass_kg` DECIMAL(12,2) NULL,
  `flammable_consequence_area_m2` DECIMAL(14,2) NULL,
  `flammable_consequence_cost_usd` DECIMAL(16,2) NULL,

  -- Toxic consequence
  `toxic_consequence_area_m2` DECIMAL(14,2) NULL,
  `toxic_consequence_cost_usd` DECIMAL(16,2) NULL,

  -- Environmental consequence
  `environmental_consequence_area_m2` DECIMAL(14,2) NULL,
  `environmental_cleanup_cost_usd` DECIMAL(16,2) NULL,
  `environmental_penalty_cost_usd` DECIMAL(16,2) NULL,

  -- Business/production consequence
  `production_loss_per_day_usd` DECIMAL(16,2) NULL,
  `estimated_downtime_days` DECIMAL(8,2) NULL,
  `business_interruption_cost_usd` DECIMAL(16,2) NULL,

  -- Equipment / repair
  `equipment_repair_cost_usd` DECIMAL(16,2) NULL,
  `equipment_replacement_cost_usd` DECIMAL(16,2) NULL,

  -- Safety / personnel
  `personnel_injury_probability` DECIMAL(6,4) NULL,
  `fatality_probability` DECIMAL(6,4) NULL,
  `affected_personnel_count` INT UNSIGNED NULL,

  -- Totals
  `total_consequence_cost_usd` DECIMAL(18,2) NULL,
  `cof_category` TINYINT UNSIGNED NULL COMMENT 'Mapped category (1-5)',
  `consequence_category` ENUM('A','B','C','D','E') NULL COMMENT 'API 581 consequence category',

  `fluid_type` VARCHAR(100) NULL,
  `fluid_phase_at_release` ENUM('gas','liquid','two_phase') NULL,
  `detection_classification` ENUM('A','B','C') NULL COMMENT 'API 581 detection/isolation',
  `isolation_classification` ENUM('A','B','C') NULL,
  `mitigation_systems` JSON NULL COMMENT 'e.g. deluge, gas detection, etc.',

  `calculation_details` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cof_assessment` (`assessment_id`),
  KEY `idx_cof_category` (`cof_category`),
  CONSTRAINT `fk_cof_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `risk_assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Consequence of failure analysis per API 581 — safety, environmental, financial impacts';

CREATE TABLE `risk_rankings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` INT UNSIGNED NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `risk_rank` INT UNSIGNED NULL COMMENT 'Ordinal rank within the portfolio',
  `inherent_risk_score` DECIMAL(10,2) NULL,
  `residual_risk_score` DECIMAL(10,2) NULL,
  `risk_reduction_pct` DECIMAL(6,2) NULL,
  `risk_acceptable` TINYINT(1) NOT NULL DEFAULT 0,
  `ranking_date` DATE NOT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rr_assessment` (`assessment_id`),
  KEY `idx_rr_asset` (`asset_id`),
  KEY `idx_rr_rank` (`risk_rank`),
  KEY `idx_rr_date` (`ranking_date`),
  CONSTRAINT `fk_rr_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `risk_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Portfolio-level risk rankings for prioritisation dashboards';

-- =============================================================================
-- MODULE 5: INSPECTION PLANNING & SCHEDULING
-- =============================================================================

CREATE TABLE `inspection_strategies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `strategy_name` VARCHAR(255) NOT NULL,
  `strategy_code` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `applicable_damage_categories` JSON NULL COMMENT 'Array of damage mechanism categories this strategy covers',
  `applicable_asset_types` JSON NULL COMMENT 'Array of asset types',
  `inspection_methods` JSON NULL COMMENT 'Ordered array of NDE methods with effectiveness ratings',
  `default_interval_months` INT UNSIGNED NULL,
  `risk_driven_interval` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether interval is recalculated from RBI output',
  `regulatory_requirement` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_is_code` (`strategy_code`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB COMMENT='Inspection strategy templates — define what techniques to use for each damage type';

CREATE TABLE `inspection_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_code` VARCHAR(50) NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `assessment_id` INT UNSIGNED NULL COMMENT 'RBI assessment that generated this plan',
  `strategy_id` INT UNSIGNED NULL,
  `plan_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('draft','active','completed','deferred','cancelled') NOT NULL DEFAULT 'draft',
  `priority` ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `plan_start_date` DATE NULL,
  `plan_end_date` DATE NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_date` DATE NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ip_code` (`plan_code`),
  KEY `idx_ip_asset` (`asset_id`),
  KEY `idx_ip_assessment` (`assessment_id`),
  KEY `idx_ip_strategy` (`strategy_id`),
  KEY `idx_ip_status` (`status`),
  KEY `idx_ip_priority` (`priority`),
  KEY `idx_ip_end_date` (`plan_end_date`),
  CONSTRAINT `fk_ip_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ip_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `risk_assessments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ip_strategy` FOREIGN KEY (`strategy_id`) REFERENCES `inspection_strategies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ip_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ip_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Inspection plans generated from RBI assessments — one per asset per assessment cycle';

CREATE TABLE `inspection_intervals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `damage_mechanism_id` INT UNSIGNED NULL,
  `interval_months` INT UNSIGNED NOT NULL,
  `interval_basis` ENUM('rbi_calculated','regulatory','engineering_judgement','manufacturer') NOT NULL DEFAULT 'rbi_calculated',
  `max_interval_months` INT UNSIGNED NULL COMMENT 'Absolute maximum per code/regulation',
  `governing_standard` VARCHAR(100) NULL COMMENT 'e.g. API 510, API 570, NBIC',
  `remaining_life_months` INT UNSIGNED NULL,
  `half_life_used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Interval capped at half remaining life',
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ii_plan` (`plan_id`),
  KEY `idx_ii_dm` (`damage_mechanism_id`),
  CONSTRAINT `fk_ii_plan` FOREIGN KEY (`plan_id`) REFERENCES `inspection_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ii_dm` FOREIGN KEY (`damage_mechanism_id`) REFERENCES `damage_mechanisms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Calculated inspection intervals per damage mechanism per plan';

CREATE TABLE `inspection_tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_code` VARCHAR(50) NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `task_name` VARCHAR(255) NOT NULL,
  `inspection_method` ENUM(
    'visual','ut_thickness','ut_shear_wave','ut_phased_array','ut_tofd',
    'radiography','magnetic_particle','liquid_penetrant',
    'eddy_current','acoustic_emission','thermography',
    'guided_wave','paut_corrosion_mapping','mfl',
    'internal_visual','api_510_external','api_570_external',
    'hydrostatic_test','pneumatic_test','rbi_desk_review',
    'drone_inspection','robotic_crawler','other'
  ) NOT NULL,
  `inspection_effectiveness` ENUM('highly_effective','usually_effective','fairly_effective','poorly_effective','ineffective') NOT NULL DEFAULT 'fairly_effective',
  `coverage_pct` DECIMAL(5,2) NULL COMMENT 'Percentage of relevant area covered',
  `scope_description` TEXT NULL,
  `damage_mechanism_id` INT UNSIGNED NULL,
  `status` ENUM('planned','scheduled','in_progress','completed','deferred','cancelled') NOT NULL DEFAULT 'planned',
  `priority` ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `scheduled_date` DATE NULL,
  `due_date` DATE NULL,
  `completed_date` DATE NULL,
  `assigned_to` INT UNSIGNED NULL,
  `estimated_hours` DECIMAL(6,2) NULL,
  `actual_hours` DECIMAL(6,2) NULL,
  `cost_estimate_usd` DECIMAL(12,2) NULL,
  `actual_cost_usd` DECIMAL(12,2) NULL,
  `requires_shutdown` TINYINT(1) NOT NULL DEFAULT 0,
  `requires_scaffold` TINYINT(1) NOT NULL DEFAULT 0,
  `requires_insulation_removal` TINYINT(1) NOT NULL DEFAULT 0,
  `work_order_reference` VARCHAR(100) NULL COMMENT 'CMMS work order number',
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_it_code` (`task_code`),
  KEY `idx_it_plan` (`plan_id`),
  KEY `idx_it_status` (`status`),
  KEY `idx_it_due` (`due_date`),
  KEY `idx_it_assigned` (`assigned_to`),
  KEY `idx_it_dm` (`damage_mechanism_id`),
  KEY `idx_it_method` (`inspection_method`),
  CONSTRAINT `fk_it_plan` FOREIGN KEY (`plan_id`) REFERENCES `inspection_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_it_dm` FOREIGN KEY (`damage_mechanism_id`) REFERENCES `damage_mechanisms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_it_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Individual inspection work items within a plan — NDE scope, scheduling, assignment';

CREATE TABLE `inspection_findings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `finding_code` VARCHAR(50) NOT NULL,
  `task_id` INT UNSIGNED NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `finding_type` ENUM(
    'wall_thinning','pitting','cracking','erosion','bulging',
    'corrosion_under_insulation','hydrogen_blistering','creep_damage',
    'mechanical_damage','coating_failure','leak','no_defect','other'
  ) NOT NULL,
  `severity` ENUM('negligible','minor','moderate','major','critical') NOT NULL DEFAULT 'moderate',
  `location_description` VARCHAR(500) NULL,
  `cml_reference` VARCHAR(100) NULL,
  `measured_thickness_mm` DECIMAL(8,3) NULL,
  `min_measured_thickness_mm` DECIMAL(8,3) NULL,
  `measured_depth_mm` DECIMAL(8,3) NULL COMMENT 'Pit depth, crack depth, etc.',
  `measured_length_mm` DECIMAL(8,3) NULL,
  `measured_width_mm` DECIMAL(8,3) NULL,
  `flaw_orientation` ENUM('axial','circumferential','both','not_applicable') NULL,
  `area_affected_pct` DECIMAL(5,2) NULL,
  `damage_mechanism_id` INT UNSIGNED NULL,
  `photos` JSON NULL COMMENT 'Array of photo/image URLs',
  `nde_report_reference` VARCHAR(100) NULL,
  `corrective_action` TEXT NULL,
  `fitness_for_service_required` TINYINT(1) NOT NULL DEFAULT 0,
  `ffs_reference` VARCHAR(100) NULL COMMENT 'FFS assessment reference (API 579)',
  `disposition` ENUM('acceptable','monitor','repair','replace','re_rate','retire') NULL,
  `disposition_date` DATE NULL,
  `disposition_by` INT UNSIGNED NULL,
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_if_code` (`finding_code`),
  KEY `idx_if_task` (`task_id`),
  KEY `idx_if_asset` (`asset_id`),
  KEY `idx_if_type` (`finding_type`),
  KEY `idx_if_severity` (`severity`),
  KEY `idx_if_dm` (`damage_mechanism_id`),
  KEY `idx_if_disposition` (`disposition`),
  CONSTRAINT `fk_if_task` FOREIGN KEY (`task_id`) REFERENCES `inspection_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_if_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_if_dm` FOREIGN KEY (`damage_mechanism_id`) REFERENCES `damage_mechanisms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_if_disposition_by` FOREIGN KEY (`disposition_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_if_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Inspection findings/defects — thickness readings, flaws, anomalies discovered during inspection';

CREATE TABLE `inspection_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `inspection_date` DATE NOT NULL,
  `inspection_type` VARCHAR(100) NULL,
  `inspection_method` VARCHAR(100) NULL,
  `inspector_name` VARCHAR(255) NULL,
  `inspector_company` VARCHAR(255) NULL,
  `inspector_certification` VARCHAR(100) NULL COMMENT 'e.g. API 510, API 570, ASNT Level II',
  `report_reference` VARCHAR(100) NULL,
  `scope_summary` TEXT NULL,
  `findings_summary` TEXT NULL,
  `overall_condition` ENUM('good','acceptable','marginal','poor','unacceptable') NULL,
  `effectiveness` ENUM('highly_effective','usually_effective','fairly_effective','poorly_effective','ineffective') NULL,
  `linked_task_id` INT UNSIGNED NULL,
  `documents` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ih_asset` (`asset_id`),
  KEY `idx_ih_date` (`inspection_date`),
  KEY `idx_ih_condition` (`overall_condition`),
  KEY `idx_ih_task` (`linked_task_id`),
  CONSTRAINT `fk_ih_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ih_task` FOREIGN KEY (`linked_task_id`) REFERENCES `inspection_tasks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Historical inspection records — imported or manually entered legacy data';

CREATE TABLE `work_priorities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `task_id` INT UNSIGNED NULL,
  `priority_score` DECIMAL(10,2) NOT NULL,
  `priority_rank` INT UNSIGNED NULL,
  `risk_score` DECIMAL(10,2) NULL,
  `cost_benefit_ratio` DECIMAL(10,4) NULL,
  `recommended_action` TEXT NULL,
  `target_turnaround` VARCHAR(100) NULL COMMENT 'Target maintenance window / turnaround',
  `status` ENUM('pending','approved','scheduled','completed','deferred') NOT NULL DEFAULT 'pending',
  `approved_by` INT UNSIGNED NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wp_asset` (`asset_id`),
  KEY `idx_wp_task` (`task_id`),
  KEY `idx_wp_rank` (`priority_rank`),
  KEY `idx_wp_status` (`status`),
  CONSTRAINT `fk_wp_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wp_task` FOREIGN KEY (`task_id`) REFERENCES `inspection_tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wp_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Prioritised work list for turnaround and maintenance planning';

-- =============================================================================
-- MODULE 6: INTEGRITY ANALYTICS
-- =============================================================================

CREATE TABLE `remaining_life_estimates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `assessment_id` INT UNSIGNED NULL,
  `calculation_date` DATE NOT NULL,
  `measured_thickness_mm` DECIMAL(8,3) NULL,
  `min_required_thickness_mm` DECIMAL(8,3) NOT NULL,
  `long_term_corrosion_rate_mm_yr` DECIMAL(8,4) NOT NULL,
  `short_term_corrosion_rate_mm_yr` DECIMAL(8,4) NULL,
  `governing_corrosion_rate_mm_yr` DECIMAL(8,4) NOT NULL COMMENT 'Rate used for RL calculation',
  `rate_basis` ENUM('long_term','short_term','maximum','estimated') NOT NULL DEFAULT 'long_term',
  `remaining_life_years` DECIMAL(8,2) NOT NULL,
  `retirement_date` DATE NULL COMMENT 'Projected retirement date at current rate',
  `half_life_date` DATE NULL COMMENT 'Date at half remaining life',
  `next_inspection_date_by_rl` DATE NULL COMMENT 'Next inspection date based on remaining life rules',
  `confidence` ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
  `calculation_method` VARCHAR(100) NULL COMMENT 'e.g. Linear projection, Bayesian update',
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rle_asset` (`asset_id`),
  KEY `idx_rle_assessment` (`assessment_id`),
  KEY `idx_rle_retirement` (`retirement_date`),
  KEY `idx_rle_rl` (`remaining_life_years`),
  CONSTRAINT `fk_rle_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rle_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `risk_assessments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rle_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Remaining life calculations — core predictive output';

CREATE TABLE `corrosion_rate_tracking` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `cml_reference` VARCHAR(100) NULL COMMENT 'Condition monitoring location',
  `measurement_date` DATE NOT NULL,
  `measured_thickness_mm` DECIMAL(8,3) NOT NULL,
  `measurement_method` ENUM('ut_manual','ut_automated','rt','visual_estimate','other') NOT NULL DEFAULT 'ut_manual',
  `measurement_quality` ENUM('good','acceptable','poor') NOT NULL DEFAULT 'acceptable',
  `instrument_id` VARCHAR(100) NULL,
  `operator` VARCHAR(255) NULL,
  `temperature_at_measurement_c` DECIMAL(8,2) NULL COMMENT 'For temperature compensation',
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crt_asset` (`asset_id`),
  KEY `idx_crt_cml` (`cml_reference`),
  KEY `idx_crt_date` (`measurement_date`),
  CONSTRAINT `fk_crt_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crt_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Individual thickness measurement readings at CML locations';

CREATE TABLE `corrosion_rate_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `cml_reference` VARCHAR(100) NULL,
  `period_start_date` DATE NOT NULL,
  `period_end_date` DATE NOT NULL,
  `start_thickness_mm` DECIMAL(8,3) NOT NULL,
  `end_thickness_mm` DECIMAL(8,3) NOT NULL,
  `short_term_rate_mm_yr` DECIMAL(8,4) NULL COMMENT 'Rate between last two readings',
  `long_term_rate_mm_yr` DECIMAL(8,4) NULL COMMENT 'Rate from original/baseline to latest',
  `rate_change_pct` DECIMAL(8,2) NULL COMMENT 'Change vs previous period',
  `rate_trend` ENUM('increasing','stable','decreasing','insufficient_data') NULL,
  `data_points_used` INT UNSIGNED NULL,
  `calculation_method` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crh_asset` (`asset_id`),
  KEY `idx_crh_cml` (`cml_reference`),
  KEY `idx_crh_end_date` (`period_end_date`),
  CONSTRAINT `fk_crh_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Computed corrosion rate trends over successive measurement periods';

CREATE TABLE `sensitivity_analyses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` INT UNSIGNED NOT NULL,
  `analysis_name` VARCHAR(255) NOT NULL,
  `analysis_type` ENUM('corrosion_rate','inspection_interval','consequence','operating_conditions','what_if') NOT NULL,
  `base_parameter` VARCHAR(100) NOT NULL,
  `base_value` DECIMAL(14,4) NOT NULL,
  `variation_range_low` DECIMAL(14,4) NOT NULL,
  `variation_range_high` DECIMAL(14,4) NOT NULL,
  `step_count` INT UNSIGNED NOT NULL DEFAULT 10,
  `results` JSON NOT NULL COMMENT 'Array of {parameter_value, risk_score, remaining_life, etc.}',
  `conclusion` TEXT NULL,
  `performed_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sa_assessment` (`assessment_id`),
  KEY `idx_sa_type` (`analysis_type`),
  CONSTRAINT `fk_sa_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `risk_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sa_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='What-if / sensitivity analyses exploring how risk changes with varying inputs';

CREATE TABLE `financial_risk_models` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` INT UNSIGNED NOT NULL,
  `model_name` VARCHAR(255) NOT NULL,
  `model_type` ENUM('expected_value','monte_carlo','cost_benefit','life_cycle','value_of_information') NOT NULL,
  `annual_risk_cost_usd` DECIMAL(16,2) NULL COMMENT 'Annualised risk = PoF x CoF',
  `inspection_cost_usd` DECIMAL(16,2) NULL,
  `mitigation_cost_usd` DECIMAL(16,2) NULL,
  `risk_reduction_value_usd` DECIMAL(16,2) NULL COMMENT 'Risk avoided by inspection/mitigation',
  `net_benefit_usd` DECIMAL(16,2) NULL,
  `return_on_investment_pct` DECIMAL(8,2) NULL,
  `optimal_interval_months` INT UNSIGNED NULL,
  `npv_of_risk_usd` DECIMAL(18,2) NULL,
  `discount_rate_pct` DECIMAL(6,4) NULL,
  `simulation_iterations` INT UNSIGNED NULL,
  `simulation_results` JSON NULL,
  `assumptions` JSON NULL,
  `performed_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_frm_assessment` (`assessment_id`),
  KEY `idx_frm_type` (`model_type`),
  CONSTRAINT `fk_frm_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `risk_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_frm_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Financial risk and cost-benefit models — ROI on inspection spend';

-- =============================================================================
-- MODULE 7: INTEGRATION MODULES
-- =============================================================================

CREATE TABLE `integration_configs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `integration_name` VARCHAR(255) NOT NULL,
  `system_type` ENUM('cmms','erp','scada','historian','document_management','gis','other') NOT NULL,
  `vendor` VARCHAR(100) NULL COMMENT 'e.g. SAP, IBM Maximo, Oracle, OSIsoft PI, Honeywell',
  `api_base_url` VARCHAR(500) NULL,
  `auth_type` ENUM('api_key','oauth2','basic','certificate','none') NOT NULL DEFAULT 'api_key',
  `credentials_encrypted` BLOB NULL COMMENT 'Encrypted credentials (AES-256)',
  `sync_direction` ENUM('inbound','outbound','bidirectional') NOT NULL DEFAULT 'bidirectional',
  `sync_frequency_minutes` INT UNSIGNED NULL DEFAULT 60,
  `last_sync_at` DATETIME NULL,
  `last_sync_status` ENUM('success','partial','failed','never') NOT NULL DEFAULT 'never',
  `last_sync_message` TEXT NULL,
  `field_mapping` JSON NULL COMMENT 'Maps RBI fields to external system fields',
  `filter_config` JSON NULL COMMENT 'Filters to control which records sync',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ic_type` (`system_type`),
  KEY `idx_ic_active` (`is_active`),
  CONSTRAINT `fk_ic_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Configuration for external system integrations (SAP PM, Maximo, PI, etc.)';

CREATE TABLE `scada_data_feeds` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `feed_name` VARCHAR(255) NOT NULL,
  `integration_id` INT UNSIGNED NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `tag_name` VARCHAR(255) NOT NULL COMMENT 'SCADA/DCS tag name',
  `parameter_type` ENUM('temperature','pressure','flow','level','vibration','corrosion_probe','ph','conductivity','other') NOT NULL,
  `unit_of_measure` VARCHAR(30) NOT NULL,
  `alarm_low` DECIMAL(14,4) NULL,
  `alarm_high` DECIMAL(14,4) NULL,
  `alarm_low_low` DECIMAL(14,4) NULL,
  `alarm_high_high` DECIMAL(14,4) NULL,
  `polling_interval_seconds` INT UNSIGNED NOT NULL DEFAULT 60,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_value` DECIMAL(14,4) NULL,
  `last_value_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sdf_integration` (`integration_id`),
  KEY `idx_sdf_asset` (`asset_id`),
  KEY `idx_sdf_tag` (`tag_name`),
  KEY `idx_sdf_active` (`is_active`),
  CONSTRAINT `fk_sdf_integration` FOREIGN KEY (`integration_id`) REFERENCES `integration_configs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sdf_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='SCADA/historian tag mappings for live process data feeds';

CREATE TABLE `iot_sensors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sensor_uid` VARCHAR(100) NOT NULL COMMENT 'Unique hardware identifier',
  `sensor_name` VARCHAR(255) NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `sensor_type` ENUM(
    'ut_thickness','corrosion_coupon','er_probe','lpr_probe',
    'acoustic_emission','strain_gauge','temperature','vibration',
    'hydrogen_flux','moisture','other'
  ) NOT NULL,
  `manufacturer` VARCHAR(100) NULL,
  `model` VARCHAR(100) NULL,
  `installation_date` DATE NULL,
  `installation_location` VARCHAR(255) NULL,
  `calibration_date` DATE NULL,
  `next_calibration_date` DATE NULL,
  `communication_protocol` ENUM('lorawan','wifi','cellular','bluetooth','wired','satellite') NULL,
  `battery_level_pct` DECIMAL(5,2) NULL,
  `status` ENUM('active','inactive','faulty','calibrating') NOT NULL DEFAULT 'active',
  `configuration` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sensor_uid` (`sensor_uid`),
  KEY `idx_iot_asset` (`asset_id`),
  KEY `idx_iot_type` (`sensor_type`),
  KEY `idx_iot_status` (`status`),
  KEY `idx_iot_next_cal` (`next_calibration_date`),
  CONSTRAINT `fk_iot_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='IoT/wireless sensor inventory for continuous integrity monitoring';

CREATE TABLE `iot_sensor_readings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sensor_id` INT UNSIGNED NOT NULL,
  `reading_timestamp` DATETIME NOT NULL,
  `value` DECIMAL(14,4) NOT NULL,
  `unit_of_measure` VARCHAR(30) NOT NULL,
  `quality` ENUM('good','suspect','bad') NOT NULL DEFAULT 'good',
  `battery_level_pct` DECIMAL(5,2) NULL,
  `raw_payload` JSON NULL COMMENT 'Original sensor payload for traceability',
  PRIMARY KEY (`id`),
  KEY `idx_isr_sensor` (`sensor_id`),
  KEY `idx_isr_timestamp` (`reading_timestamp`),
  KEY `idx_isr_sensor_time` (`sensor_id`, `reading_timestamp`),
  CONSTRAINT `fk_isr_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `iot_sensors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Time-series IoT sensor readings — high-volume table, consider partitioning by date';

CREATE TABLE `digital_twin_models` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `model_name` VARCHAR(255) NOT NULL,
  `model_type` ENUM('3d_geometry','fea','cfd','corrosion_map','thermal','structural') NOT NULL,
  `model_version` VARCHAR(50) NOT NULL,
  `file_path` VARCHAR(500) NULL,
  `file_format` VARCHAR(50) NULL COMMENT 'e.g. STEP, IFC, FBX, glTF, VTK',
  `file_size_bytes` BIGINT UNSIGNED NULL,
  `source_system` VARCHAR(100) NULL,
  `last_updated_from_inspection` DATE NULL,
  `metadata` JSON NULL,
  `status` ENUM('draft','validated','published','archived') NOT NULL DEFAULT 'draft',
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dtm_asset` (`asset_id`),
  KEY `idx_dtm_type` (`model_type`),
  KEY `idx_dtm_status` (`status`),
  CONSTRAINT `fk_dtm_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dtm_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Digital twin / 3D model references linked to assets';

CREATE TABLE `external_inspection_databases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `database_name` VARCHAR(255) NOT NULL,
  `provider` VARCHAR(255) NOT NULL COMMENT 'e.g. Bureau Veritas, Lloyds, TUV, NBBI',
  `connection_type` ENUM('api','file_import','manual','database_link') NOT NULL,
  `connection_config` JSON NULL,
  `asset_id` INT UNSIGNED NULL COMMENT 'If linked to specific asset',
  `external_reference` VARCHAR(255) NULL COMMENT 'Reference ID in external system',
  `last_import_at` DATETIME NULL,
  `last_import_records` INT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eid_asset` (`asset_id`),
  KEY `idx_eid_active` (`is_active`),
  CONSTRAINT `fk_eid_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Links to external inspection databases and third-party inspection data providers';

-- =============================================================================
-- MODULE 8: REPORTING & DASHBOARDS
-- =============================================================================

CREATE TABLE `report_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_name` VARCHAR(255) NOT NULL,
  `template_key` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `module` VARCHAR(50) NOT NULL,
  `output_format` ENUM('pdf','excel','csv','html','word') NOT NULL DEFAULT 'pdf',
  `template_body` LONGTEXT NULL COMMENT 'HTML/template markup or SQL query definition',
  `parameters_schema` JSON NULL COMMENT 'JSON schema defining available report parameters',
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rt_key` (`template_key`),
  KEY `idx_rt_module` (`module`),
  CONSTRAINT `fk_rt_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Report template definitions — reusable report formats';

CREATE TABLE `saved_reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_name` VARCHAR(255) NOT NULL,
  `template_id` INT UNSIGNED NULL,
  `parameters` JSON NULL COMMENT 'Parameter values used to generate this report',
  `output_format` ENUM('pdf','excel','csv','html','word') NOT NULL DEFAULT 'pdf',
  `file_path` VARCHAR(500) NULL COMMENT 'Path to generated report file',
  `file_size_bytes` BIGINT UNSIGNED NULL,
  `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` INT UNSIGNED NULL,
  `is_scheduled` TINYINT(1) NOT NULL DEFAULT 0,
  `schedule_cron` VARCHAR(100) NULL COMMENT 'Cron expression for scheduled reports',
  `recipients` JSON NULL COMMENT 'Email addresses for distribution',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sr_template` (`template_id`),
  KEY `idx_sr_generated_by` (`generated_by`),
  KEY `idx_sr_generated_at` (`generated_at`),
  CONSTRAINT `fk_sr_template` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sr_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Generated and saved report instances';

CREATE TABLE `dashboards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dashboard_name` VARCHAR(255) NOT NULL,
  `dashboard_key` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `layout_config` JSON NULL COMMENT 'Grid/layout configuration for widget placement',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `visibility` ENUM('public','role_based','private') NOT NULL DEFAULT 'public',
  `allowed_roles` JSON NULL COMMENT 'Array of role IDs if role_based',
  `owner_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dash_key` (`dashboard_key`),
  KEY `idx_dash_owner` (`owner_id`),
  CONSTRAINT `fk_dash_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Dashboard definitions for executive and operational views';

CREATE TABLE `dashboard_widgets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dashboard_id` INT UNSIGNED NOT NULL,
  `widget_type` ENUM(
    'risk_matrix_heatmap','risk_trend_chart','risk_distribution_pie',
    'corrosion_rate_trend','remaining_life_bar','thickness_trend',
    'inspection_compliance','inspection_backlog','overdue_inspections',
    'asset_summary_table','damage_mechanism_breakdown',
    'kpi_card','map_view','custom_chart','custom_table','custom_query'
  ) NOT NULL,
  `widget_title` VARCHAR(255) NOT NULL,
  `position_x` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `position_y` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `width` SMALLINT UNSIGNED NOT NULL DEFAULT 4,
  `height` SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  `data_source_config` JSON NULL COMMENT 'Query or API config for the widget data',
  `display_config` JSON NULL COMMENT 'Chart type, colours, thresholds, etc.',
  `filters` JSON NULL COMMENT 'Default filters (hierarchy, date range, etc.)',
  `refresh_interval_seconds` INT UNSIGNED NULL DEFAULT 300,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dw_dashboard` (`dashboard_id`),
  KEY `idx_dw_type` (`widget_type`),
  CONSTRAINT `fk_dw_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Individual widgets displayed on dashboards';

-- =============================================================================
-- DEFAULT DATA INSERTS
-- =============================================================================

-- -------------------------
-- Roles
-- -------------------------
INSERT INTO `roles` (`role_name`, `role_key`, `description`, `is_system_role`) VALUES
('Administrator', 'admin', 'Full system access — user management, configuration, all modules', 1),
('RBI Engineer', 'engineer', 'Create and manage RBI assessments, damage mechanisms, inspection plans', 1),
('Inspector', 'inspector', 'Record inspection findings, thickness data, upload reports', 1),
('Viewer', 'viewer', 'Read-only access to dashboards, reports, and asset data', 1);

-- -------------------------
-- Permissions
-- -------------------------
INSERT INTO `permissions` (`permission_key`, `module`, `description`) VALUES
-- Asset management
('assets.view', 'assets', 'View asset registry and design data'),
('assets.create', 'assets', 'Create new assets'),
('assets.edit', 'assets', 'Edit existing assets'),
('assets.delete', 'assets', 'Delete assets'),
('assets.import', 'assets', 'Bulk import assets'),
-- Risk assessments
('assessments.view', 'risk', 'View risk assessments'),
('assessments.create', 'risk', 'Create risk assessments'),
('assessments.edit', 'risk', 'Edit risk assessments'),
('assessments.approve', 'risk', 'Approve risk assessments'),
('assessments.delete', 'risk', 'Delete risk assessments'),
-- Damage mechanisms
('damage_mechanisms.view', 'damage_mechanisms', 'View damage mechanism library'),
('damage_mechanisms.manage', 'damage_mechanisms', 'Add/edit damage mechanisms and assignments'),
-- Inspection
('inspections.view', 'inspection', 'View inspection plans and findings'),
('inspections.create', 'inspection', 'Create inspection plans and tasks'),
('inspections.edit', 'inspection', 'Edit inspection plans and tasks'),
('inspections.record_findings', 'inspection', 'Record inspection findings and thickness data'),
('inspections.approve', 'inspection', 'Approve inspection plans'),
-- Analytics
('analytics.view', 'analytics', 'View remaining life, corrosion trends, sensitivity analyses'),
('analytics.create', 'analytics', 'Run new analyses and financial models'),
-- Integration
('integrations.view', 'integration', 'View integration configurations'),
('integrations.manage', 'integration', 'Configure and manage integrations'),
-- Reports
('reports.view', 'reports', 'View and download reports'),
('reports.create', 'reports', 'Generate new reports'),
('reports.manage_templates', 'reports', 'Create and edit report templates'),
-- Dashboards
('dashboards.view', 'dashboards', 'View dashboards'),
('dashboards.manage', 'dashboards', 'Create and configure dashboards'),
-- Administration
('users.view', 'admin', 'View user accounts'),
('users.manage', 'admin', 'Create/edit/deactivate users'),
('roles.manage', 'admin', 'Manage roles and permissions'),
('audit.view', 'admin', 'View audit trail'),
('system.configure', 'admin', 'System-wide configuration');

-- Admin gets everything
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Engineer gets most operational permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`
WHERE `permission_key` IN (
  'assets.view','assets.create','assets.edit','assets.import',
  'assessments.view','assessments.create','assessments.edit','assessments.approve',
  'damage_mechanisms.view','damage_mechanisms.manage',
  'inspections.view','inspections.create','inspections.edit','inspections.approve',
  'analytics.view','analytics.create',
  'integrations.view',
  'reports.view','reports.create','reports.manage_templates',
  'dashboards.view','dashboards.manage'
);

-- Inspector permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions`
WHERE `permission_key` IN (
  'assets.view',
  'assessments.view',
  'damage_mechanisms.view',
  'inspections.view','inspections.create','inspections.edit','inspections.record_findings',
  'analytics.view',
  'reports.view','reports.create',
  'dashboards.view'
);

-- Viewer permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions`
WHERE `permission_key` IN (
  'assets.view',
  'assessments.view',
  'damage_mechanisms.view',
  'inspections.view',
  'analytics.view',
  'reports.view',
  'dashboards.view'
);

-- -------------------------
-- Default admin user (password: changeme — bcrypt hash)
-- -------------------------
INSERT INTO `users` (`username`, `email`, `password_hash`, `first_name`, `last_name`, `job_title`, `role_id`, `status`) VALUES
('admin', 'admin@rbi-platform.local', '$2y$12$LJ3m4ys3Gz8y/NPIaj0MiOx6B.BvXkqOSwAjpPGHwVfcCj5dC/uVe', 'System', 'Administrator', 'Platform Administrator', 1, 'active');

-- -------------------------
-- Damage Mechanisms Library (API 571 based)
-- -------------------------
INSERT INTO `damage_mechanisms` (`dm_code`, `dm_name`, `category`, `api_571_reference`, `description`, `typical_materials_affected`, `typical_services`, `temperature_range_min_c`, `temperature_range_max_c`, `default_susceptibility`, `inspection_methods`, `sort_order`) VALUES

('GEN-THIN', 'General / Uniform Corrosion', 'general_corrosion', '4.3.1',
 'Uniform metal loss over a broad area due to general chemical or electrochemical reactions. The most common form of corrosion in refinery and petrochemical equipment.',
 'Carbon steel, low-alloy steels', 'Aqueous services, acids, caustics', NULL, NULL, 'medium',
 '["ut_thickness","visual","radiography","corrosion_coupon"]', 10),

('LOC-PIT', 'Localised / Pitting Corrosion', 'localised_corrosion', '4.3.3',
 'Localised areas of metal loss appearing as pits or clusters. Can cause through-wall penetration before significant general wall loss.',
 'Carbon steel, stainless steels, nickel alloys', 'Chloride-containing water, stagnant areas, under deposits', NULL, NULL, 'medium',
 '["ut_phased_array","paut_corrosion_mapping","radiography","visual"]', 20),

('CUI', 'Corrosion Under Insulation', 'external', '4.3.2',
 'External corrosion beneath thermal insulation caused by moisture ingress. A leading cause of unexpected failure in process piping and vessels.',
 'Carbon steel, 300-series SS (SCC under insulation)', 'Insulated equipment operating -12°C to 175°C (CS) or 50°C to 175°C (SS)', -12, 175, 'high',
 '["thermography","ut_thickness","radiography","visual_insulation_removal","paut_corrosion_mapping"]', 30),

('SCC-CL', 'Chloride Stress Corrosion Cracking', 'stress_corrosion_cracking', '4.5.1',
 'Cracking of austenitic stainless steels and some nickel alloys under the combined action of tensile stress, temperature, and chloride ions.',
 '300-series SS (304, 316, 321, 347), Duplex SS', 'Chloride-bearing water, steam, insulation leaching', 50, 300, 'medium',
 '["liquid_penetrant","eddy_current","ut_shear_wave","ut_phased_array"]', 40),

('SCC-SSC', 'Sulphide Stress Cracking (SSC)', 'stress_corrosion_cracking', '4.5.3',
 'Cracking of high-strength or hard steels in the presence of H2S and water at or near ambient temperature.',
 'Carbon steel (high hardness), high-strength low-alloy steels', 'Sour (H2S) services, amine systems', -50, 80, 'high',
 '["ut_shear_wave","magnetic_particle","liquid_penetrant","ut_tofd"]', 50),

('SCC-OH', 'Caustic Stress Corrosion Cracking', 'stress_corrosion_cracking', '4.5.2',
 'Cracking of carbon steel and some alloys in caustic (NaOH/KOH) service, accelerated by temperature and concentration.',
 'Carbon steel, 300-series SS', 'Caustic soda (NaOH) service, caustic treating', 50, 250, 'medium',
 '["ut_shear_wave","wet_fluorescent_magnetic_particle","liquid_penetrant"]', 60),

('SCC-PASCC', 'Polythionic Acid SCC', 'stress_corrosion_cracking', '4.5.5',
 'Intergranular cracking of sensitised austenitic stainless steels exposed to polythionic acids formed during shutdowns from sulphide scale and moisture.',
 '300-series SS (non-stabilised), Alloy 800', 'Sulphur-containing environments during shutdown', 0, 50, 'medium',
 '["liquid_penetrant","eddy_current","ut_shear_wave"]', 70),

('HIC-SOHIC', 'Hydrogen Induced Cracking / SOHIC', 'hydrogen_damage', '4.5.4',
 'Hydrogen blistering, HIC, and stress-oriented HIC in carbon steel caused by hydrogen charging in wet H2S environments.',
 'Carbon steel', 'Wet H2S (sour) service, amine units, sour water strippers', NULL, NULL, 'high',
 '["ut_shear_wave","ut_phased_array","wet_fluorescent_magnetic_particle","acoustic_emission"]', 80),

('HTHA', 'High Temperature Hydrogen Attack', 'hydrogen_damage', '4.5.6',
 'Irreversible damage to steel caused by hydrogen reacting with carbides at high temperature and hydrogen partial pressure, per Nelson curves (API 941).',
 'Carbon steel, C-0.5Mo, low-alloy Cr-Mo steels', 'Hydroprocessing, reformer, high-pressure hydrogen service', 200, 600, 'high',
 '["ut_advanced_backscatter","ut_phased_array","metallographic_replication","acoustic_emission"]', 90),

('EROSION', 'Erosion / Erosion-Corrosion', 'erosion', '4.3.8',
 'Metal loss caused by impingement of solid particles, liquid droplets, or high-velocity fluid flow, often combined with corrosion.',
 'Carbon steel, copper alloys', 'Slurries, two-phase flow, elbows, tees, downstream of control valves', NULL, NULL, 'medium',
 '["ut_thickness","visual","radiography","paut_corrosion_mapping"]', 100),

('CREEP', 'High Temperature Creep / Stress Rupture', 'high_temperature', '4.2.16',
 'Time-dependent deformation and eventual rupture under sustained stress at elevated temperatures, typically above 370°C for carbon steel.',
 'Carbon steel (>370°C), Cr-Mo steels (>425°C), SS (>510°C)', 'Fired heaters, reformer tubes, high-temp reactors', 370, 900, 'medium',
 '["visual","dimensional_measurement","metallographic_replication","ut_shear_wave","strain_measurement"]', 110),

('FATIGUE', 'Mechanical / Vibration Fatigue', 'mechanical_fatigue', '4.6.1',
 'Cracking due to cyclic stresses from mechanical vibration, thermal cycling, or pressure fluctuations.',
 'All metals', 'Small-bore connections, piping vibration, thermal cycling equipment', NULL, NULL, 'low',
 '["visual","magnetic_particle","liquid_penetrant","ut_shear_wave"]', 120),

('COR-FAT', 'Corrosion Fatigue', 'mechanical_fatigue', '4.5.8',
 'Fatigue cracking accelerated by a corrosive environment; occurs at lower stress levels than mechanical fatigue alone.',
 'Carbon steel, copper alloys', 'Aqueous services with cyclic loading', NULL, NULL, 'low',
 '["magnetic_particle","liquid_penetrant","ut_shear_wave","ut_phased_array"]', 130),

('MIC', 'Microbiologically Influenced Corrosion', 'localised_corrosion', '4.3.7',
 'Corrosion caused or accelerated by the activity of micro-organisms (bacteria, algae, fungi) — typically sulphate-reducing bacteria (SRB).',
 'Carbon steel, 300-series SS, copper alloys', 'Stagnant water, cooling water, hydrotest water left in equipment, soil-buried piping', NULL, 60, 'low',
 '["visual","ut_thickness","pit_depth_gauge","microbiological_sampling"]', 140),

('TEMP-EMBRITTLEMENT', 'Temper Embrittlement / 885F Embrittlement', 'metallurgical', '4.2.3',
 'Loss of toughness in Cr-Mo and ferritic stainless steels after long-term exposure in the 370-560°C range (temper) or 370-540°C range (885°F).',
 'Cr-Mo steels (2.25Cr-1Mo), ferritic SS (400-series)', 'Hydroprocessing reactors, high-temperature service', 370, 560, 'medium',
 '["charpy_impact_testing","metallographic_replication","hardness_testing"]', 150),

('EXTERNAL', 'Atmospheric / External Corrosion', 'external', '4.3.2',
 'Corrosion of external surfaces exposed to atmospheric conditions — rain, humidity, marine salt spray, industrial pollutants.',
 'Carbon steel, cast iron', 'All outdoor equipment, especially marine/coastal', NULL, NULL, 'low',
 '["visual","ut_thickness","coating_inspection"]', 160),

('ACID-DEW', 'Acid Dewpoint Corrosion', 'general_corrosion', '4.3.5',
 'Severe corrosion when metal temperatures fall below the acid dewpoint causing condensation of sulphuric or hydrochloric acid.',
 'Carbon steel, low-alloy steels', 'Fired heater convection sections, flue gas systems, atmospheric column overheads', 90, 180, 'medium',
 '["ut_thickness","visual","radiography"]', 170),

('NAP-ACID', 'Naphthenic Acid Corrosion', 'general_corrosion', '4.3.6',
 'High-temperature corrosion by naphthenic acids in crude oils with high Total Acid Number (TAN), typically 220-400°C.',
 'Carbon steel, 5Cr, 9Cr (12Cr and 316SS resistant)', 'Crude/vacuum distillation, high-TAN crude processing', 220, 400, 'medium',
 '["ut_thickness","radiography","er_probe","corrosion_coupon"]', 180);

-- -------------------------
-- Default Risk Matrix (5x5 API 581 style)
-- -------------------------
INSERT INTO `risk_matrices` (`matrix_name`, `description`, `rows_label`, `cols_label`, `num_rows`, `num_cols`, `is_default`, `is_active`) VALUES
('API 581 Standard 5x5', 'Default 5x5 risk matrix aligned with API 581 methodology. Rows = Probability of Failure (1=Very Low to 5=Very High). Columns = Consequence of Failure (A=Very Low to E=Very High).', 'Probability of Failure', 'Consequence of Failure', 5, 5, 1, 1);

-- Populate all 25 cells of the 5x5 matrix
-- Row labels: 1=Very Low, 2=Low, 3=Medium, 4=High, 5=Very High
-- Col labels: A=Very Low, B=Low, C=Medium, D=High, E=Very High
INSERT INTO `risk_matrix_cells` (`matrix_id`, `row_index`, `col_index`, `row_label`, `col_label`, `risk_level`, `risk_score`, `color_hex`, `action_required`) VALUES
-- Row 1 (PoF = Very Low)
(1, 1, 1, 'Very Low', 'A - Very Low',   'negligible',  1.00, '#228B22', 'Monitor — no immediate action required'),
(1, 1, 2, 'Very Low', 'B - Low',        'negligible',  2.00, '#228B22', 'Monitor — no immediate action required'),
(1, 1, 3, 'Very Low', 'C - Medium',     'low',         3.00, '#32CD32', 'Standard inspection per code intervals'),
(1, 1, 4, 'Very Low', 'D - High',       'low',         4.00, '#32CD32', 'Standard inspection per code intervals'),
(1, 1, 5, 'Very Low', 'E - Very High',  'medium',      5.00, '#FFD700', 'Enhanced inspection or monitoring recommended'),
-- Row 2 (PoF = Low)
(1, 2, 1, 'Low', 'A - Very Low',        'negligible',  2.00, '#228B22', 'Monitor — no immediate action required'),
(1, 2, 2, 'Low', 'B - Low',            'low',         4.00, '#32CD32', 'Standard inspection per code intervals'),
(1, 2, 3, 'Low', 'C - Medium',         'low',         6.00, '#32CD32', 'Standard inspection per code intervals'),
(1, 2, 4, 'Low', 'D - High',           'medium',      8.00, '#FFD700', 'Enhanced inspection or monitoring recommended'),
(1, 2, 5, 'Low', 'E - Very High',      'medium_high', 10.00,'#FFA500', 'Develop risk mitigation plan within 12 months'),
-- Row 3 (PoF = Medium)
(1, 3, 1, 'Medium', 'A - Very Low',     'low',         3.00, '#32CD32', 'Standard inspection per code intervals'),
(1, 3, 2, 'Medium', 'B - Low',         'low',         6.00, '#32CD32', 'Standard inspection per code intervals'),
(1, 3, 3, 'Medium', 'C - Medium',      'medium',      9.00, '#FFD700', 'Enhanced inspection or monitoring recommended'),
(1, 3, 4, 'Medium', 'D - High',        'medium_high', 12.00,'#FFA500', 'Develop risk mitigation plan within 12 months'),
(1, 3, 5, 'Medium', 'E - Very High',   'high',        15.00,'#FF4500', 'Immediate action — detailed assessment and mitigation required'),
-- Row 4 (PoF = High)
(1, 4, 1, 'High', 'A - Very Low',       'low',         4.00, '#32CD32', 'Standard inspection per code intervals'),
(1, 4, 2, 'High', 'B - Low',           'medium',      8.00, '#FFD700', 'Enhanced inspection or monitoring recommended'),
(1, 4, 3, 'High', 'C - Medium',        'medium_high', 12.00,'#FFA500', 'Develop risk mitigation plan within 12 months'),
(1, 4, 4, 'High', 'D - High',          'high',        16.00,'#FF4500', 'Immediate action — detailed assessment and mitigation required'),
(1, 4, 5, 'High', 'E - Very High',     'very_high',   20.00,'#DC143C', 'URGENT — cease operation or implement emergency mitigation'),
-- Row 5 (PoF = Very High)
(1, 5, 1, 'Very High', 'A - Very Low',  'medium',      5.00, '#FFD700', 'Enhanced inspection or monitoring recommended'),
(1, 5, 2, 'Very High', 'B - Low',      'medium_high', 10.00,'#FFA500', 'Develop risk mitigation plan within 12 months'),
(1, 5, 3, 'Very High', 'C - Medium',   'high',        15.00,'#FF4500', 'Immediate action — detailed assessment and mitigation required'),
(1, 5, 4, 'Very High', 'D - High',     'very_high',   20.00,'#DC143C', 'URGENT — cease operation or implement emergency mitigation'),
(1, 5, 5, 'Very High', 'E - Very High','very_high',   25.00,'#DC143C', 'URGENT — cease operation or implement emergency mitigation');

-- -------------------------
-- Default Inspection Strategies
-- -------------------------
INSERT INTO `inspection_strategies` (`strategy_name`, `strategy_code`, `description`, `applicable_damage_categories`, `applicable_asset_types`, `inspection_methods`, `default_interval_months`, `risk_driven_interval`, `regulatory_requirement`) VALUES

('General Thinning Monitoring', 'STRAT-THIN', 'UT thickness surveys targeting general and localised wall loss. Primary strategy for vessels and piping in corrosive service.',
 '["general_corrosion","localised_corrosion","erosion"]',
 '["pressure_vessel","piping","heat_exchanger","storage_tank","column"]',
 '[{"method":"ut_thickness","effectiveness":"usually_effective"},{"method":"paut_corrosion_mapping","effectiveness":"highly_effective"},{"method":"radiography","effectiveness":"usually_effective"}]',
 60, 1, 0),

('CUI Inspection Program', 'STRAT-CUI', 'Targeted CUI inspection using thermography screening followed by insulation removal and UT at suspect areas.',
 '["external"]',
 '["pressure_vessel","piping","heat_exchanger","column"]',
 '[{"method":"thermography","effectiveness":"fairly_effective"},{"method":"ut_thickness","effectiveness":"usually_effective"},{"method":"visual","effectiveness":"fairly_effective"},{"method":"paut_corrosion_mapping","effectiveness":"highly_effective"}]',
 48, 1, 0),

('SCC / Environmental Cracking Detection', 'STRAT-SCC', 'Crack detection inspection strategy using UT shear-wave, TOFD, or surface NDE methods.',
 '["stress_corrosion_cracking","hydrogen_damage"]',
 '["pressure_vessel","piping","heat_exchanger","column","reactor"]',
 '[{"method":"ut_shear_wave","effectiveness":"usually_effective"},{"method":"ut_tofd","effectiveness":"highly_effective"},{"method":"ut_phased_array","effectiveness":"highly_effective"},{"method":"magnetic_particle","effectiveness":"usually_effective"},{"method":"liquid_penetrant","effectiveness":"usually_effective"}]',
 48, 1, 0),

('High-Temperature Degradation Monitoring', 'STRAT-HITEMP', 'Inspection for creep, HTHA, and high-temperature metallurgical damage using advanced techniques.',
 '["high_temperature","hydrogen_damage","metallurgical"]',
 '["fired_heater","reactor","boiler","piping","heat_exchanger"]',
 '[{"method":"ut_phased_array","effectiveness":"highly_effective"},{"method":"metallographic_replication","effectiveness":"highly_effective"},{"method":"hardness_testing","effectiveness":"usually_effective"},{"method":"dimensional_measurement","effectiveness":"usually_effective"}]',
 36, 1, 0),

('External Visual & Coating Survey', 'STRAT-EXTVIS', 'External visual inspection and coating condition assessment per API 510/570.',
 '["external"]',
 '["pressure_vessel","piping","storage_tank","heat_exchanger","column","structural"]',
 '[{"method":"visual","effectiveness":"fairly_effective"},{"method":"ut_thickness","effectiveness":"usually_effective"}]',
 60, 0, 1),

('API 510 Internal Inspection', 'STRAT-API510', 'Comprehensive internal inspection per API 510 for pressure vessels including visual, UT, and surface NDE.',
 '["general_corrosion","localised_corrosion","stress_corrosion_cracking","erosion","hydrogen_damage"]',
 '["pressure_vessel","heat_exchanger","column","reactor"]',
 '[{"method":"internal_visual","effectiveness":"usually_effective"},{"method":"ut_thickness","effectiveness":"usually_effective"},{"method":"magnetic_particle","effectiveness":"usually_effective"},{"method":"liquid_penetrant","effectiveness":"usually_effective"}]',
 120, 1, 1),

('API 570 Piping Inspection', 'STRAT-API570', 'Piping inspection per API 570 covering thickness measurement, visual, and injection/dead-leg checks.',
 '["general_corrosion","localised_corrosion","erosion","external","stress_corrosion_cracking"]',
 '["piping"]',
 '[{"method":"ut_thickness","effectiveness":"usually_effective"},{"method":"visual","effectiveness":"fairly_effective"},{"method":"guided_wave","effectiveness":"fairly_effective"},{"method":"radiography","effectiveness":"usually_effective"}]',
 60, 1, 1),

('Storage Tank Inspection (API 653)', 'STRAT-API653', 'Atmospheric storage tank inspection per API 653 including floor scan, shell UT, and settlement survey.',
 '["general_corrosion","localised_corrosion","external"]',
 '["storage_tank"]',
 '[{"method":"mfl","effectiveness":"highly_effective"},{"method":"ut_thickness","effectiveness":"usually_effective"},{"method":"visual","effectiveness":"fairly_effective"},{"method":"acoustic_emission","effectiveness":"fairly_effective"}]',
 120, 1, 1),

('Relief Device Inspection / Test', 'STRAT-PRV', 'Pressure relief device inspection and pop-testing per API 576.',
 '["mechanical_fatigue"]',
 '["relief_device"]',
 '[{"method":"visual","effectiveness":"usually_effective"},{"method":"pop_test","effectiveness":"highly_effective"},{"method":"ut_thickness","effectiveness":"usually_effective"}]',
 60, 0, 1),

('IoT Continuous Monitoring', 'STRAT-IOT', 'Continuous online monitoring using permanently installed UT sensors, corrosion probes, or AE sensors — supplements periodic inspection.',
 '["general_corrosion","localised_corrosion","erosion","stress_corrosion_cracking"]',
 '["pressure_vessel","piping","heat_exchanger","column","reactor"]',
 '[{"method":"ut_automated","effectiveness":"highly_effective"},{"method":"acoustic_emission","effectiveness":"usually_effective"},{"method":"er_probe","effectiveness":"usually_effective"}]',
 NULL, 1, 0);

-- -------------------------
-- Default Equipment Hierarchy (example top levels)
-- -------------------------
INSERT INTO `equipment_hierarchy` (`name`, `code`, `level`, `parent_id`, `description`) VALUES
('RBI Platform Demo Site', 'SITE-001', 'site', NULL, 'Demonstration site for the RBI engineering platform');

-- -------------------------
-- Default Dashboard
-- -------------------------
INSERT INTO `dashboards` (`dashboard_name`, `dashboard_key`, `description`, `is_default`, `is_system`, `visibility`) VALUES
('RBI Overview', 'rbi-overview', 'Executive overview dashboard showing risk distribution, inspection compliance, and top-risk assets', 1, 1, 'public');

INSERT INTO `dashboard_widgets` (`dashboard_id`, `widget_type`, `widget_title`, `position_x`, `position_y`, `width`, `height`, `sort_order`) VALUES
(1, 'risk_matrix_heatmap',      'Risk Matrix — Current Asset Distribution', 0, 0, 6, 4, 1),
(1, 'risk_distribution_pie',    'Risk Level Distribution',                  6, 0, 3, 4, 2),
(1, 'kpi_card',                 'Total Assets Under Management',            9, 0, 3, 2, 3),
(1, 'kpi_card',                 'Overdue Inspections',                      9, 2, 3, 2, 4),
(1, 'inspection_compliance',    'Inspection Plan Compliance (%)',            0, 4, 6, 3, 5),
(1, 'remaining_life_bar',       'Assets by Remaining Life Band',            6, 4, 6, 3, 6),
(1, 'overdue_inspections',      'Overdue Inspection Tasks',                 0, 7, 6, 4, 7),
(1, 'risk_trend_chart',         'Risk Score Trend (12 months)',             6, 7, 6, 4, 8),
(1, 'damage_mechanism_breakdown','Active Damage Mechanisms by Category',    0, 11, 6, 3, 9),
(1, 'corrosion_rate_trend',     'Highest Corrosion Rates — Top 10 Assets', 6, 11, 6, 3, 10);

-- -------------------------
-- Default Report Templates
-- -------------------------
INSERT INTO `report_templates` (`template_name`, `template_key`, `description`, `module`, `output_format`, `is_system`) VALUES
('RBI Assessment Summary', 'rpt-rbi-summary', 'Single-asset RBI assessment report with risk results, damage mechanisms, and inspection plan', 'risk', 'pdf', 1),
('Risk Register', 'rpt-risk-register', 'Portfolio-wide risk register listing all assessed assets ranked by risk score', 'risk', 'excel', 1),
('Inspection Plan Report', 'rpt-inspection-plan', 'Detailed inspection plan with tasks, schedule, and scope for a single asset or unit', 'inspection', 'pdf', 1),
('Corrosion Rate Trending', 'rpt-corrosion-trend', 'Corrosion rate and thickness trending report with charts for selected CML locations', 'analytics', 'pdf', 1),
('Remaining Life Summary', 'rpt-remaining-life', 'Remaining life estimates for all assets with retirement date projections', 'analytics', 'excel', 1),
('Inspection Compliance', 'rpt-inspection-compliance', 'Inspection task compliance status — completed vs overdue vs upcoming', 'inspection', 'pdf', 1),
('Damage Mechanism Summary', 'rpt-dm-summary', 'Damage mechanism assignments across the portfolio with susceptibility ratings', 'damage_mechanisms', 'excel', 1),
('Audit Trail Report', 'rpt-audit-trail', 'Audit trail extract for compliance and regulatory review', 'admin', 'csv', 1);

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
