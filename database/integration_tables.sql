-- =============================================================================
-- Integration Tables - RBI Engineering Suite
-- SAP PM, IBM Maximo, OSIsoft PI integration support tables
-- =============================================================================

USE `rbi_engineering`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Sync operation log: tracks every sync execution
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integration_sync_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `integration_id` INT UNSIGNED NOT NULL,
  `sync_type` ENUM('full','incremental','manual') NOT NULL DEFAULT 'manual',
  `direction` ENUM('inbound','outbound','bidirectional') NOT NULL DEFAULT 'inbound',
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME NULL,
  `records_processed` INT UNSIGNED NOT NULL DEFAULT 0,
  `records_created` INT UNSIGNED NOT NULL DEFAULT 0,
  `records_updated` INT UNSIGNED NOT NULL DEFAULT 0,
  `records_failed` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('running','completed','failed','partial') NOT NULL DEFAULT 'running',
  `error_log` JSON NULL COMMENT 'Array of error messages encountered during sync',
  `created_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_isl_integration` (`integration_id`),
  KEY `idx_isl_status` (`status`),
  KEY `idx_isl_started` (`started_at`),
  CONSTRAINT `fk_isl_integration` FOREIGN KEY (`integration_id`) REFERENCES `integration_configs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_isl_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Log of all integration sync operations with statistics';

-- -----------------------------------------------------------------------------
-- Field mappings: configurable mapping between external and RBI fields
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integration_field_mappings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `integration_id` INT UNSIGNED NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'e.g. equipment, work_order, notification, measurement',
  `external_field` VARCHAR(100) NOT NULL COMMENT 'Field name in external system',
  `internal_field` VARCHAR(100) NOT NULL COMMENT 'Field name in RBI database',
  `transform_function` VARCHAR(100) NULL COMMENT 'PHP function for value transformation',
  `default_value` VARCHAR(255) NULL COMMENT 'Default if external value is null',
  `is_key` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether this field is a matching key',
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `direction` ENUM('inbound','outbound','both') NOT NULL DEFAULT 'both',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ifm_integration` (`integration_id`),
  KEY `idx_ifm_entity` (`entity_type`),
  UNIQUE KEY `uk_ifm_mapping` (`integration_id`, `entity_type`, `external_field`),
  CONSTRAINT `fk_ifm_integration` FOREIGN KEY (`integration_id`) REFERENCES `integration_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Configurable field-level mapping between external systems and RBI data model';

-- -----------------------------------------------------------------------------
-- Data cache: temporary storage of external data to reduce API calls
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integration_data_cache` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `integration_id` INT UNSIGNED NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `external_id` VARCHAR(100) NOT NULL,
  `cached_data` JSON NOT NULL,
  `cached_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_idc_lookup` (`integration_id`, `entity_type`, `external_id`),
  KEY `idx_idc_expires` (`expires_at`),
  CONSTRAINT `fk_idc_integration` FOREIGN KEY (`integration_id`) REFERENCES `integration_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Cached external system data to reduce API call frequency';

-- -----------------------------------------------------------------------------
-- Conflict log: tracks data conflicts between systems
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integration_conflict_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `integration_id` INT UNSIGNED NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `internal_id` INT UNSIGNED NULL,
  `external_id` VARCHAR(100) NOT NULL,
  `field_name` VARCHAR(100) NOT NULL,
  `internal_value` TEXT NULL,
  `external_value` TEXT NULL,
  `resolution` ENUM('pending','internal_wins','external_wins','manual','merged') NOT NULL DEFAULT 'pending',
  `resolved_by` INT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_icl_integration` (`integration_id`),
  KEY `idx_icl_resolution` (`resolution`),
  KEY `idx_icl_entity` (`entity_type`, `external_id`),
  CONSTRAINT `fk_icl_integration` FOREIGN KEY (`integration_id`) REFERENCES `integration_configs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_icl_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Data conflict tracking for bidirectional sync operations';

-- -----------------------------------------------------------------------------
-- PI tag mappings: links OSIsoft PI tags to RBI assets
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pi_tag_mappings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `pi_tag_name` VARCHAR(255) NOT NULL COMMENT 'Full PI point path e.g. SINUSOID or Plant1.Unit2.TI-101',
  `pi_web_id` VARCHAR(255) NULL COMMENT 'PI Web API WebId for direct access',
  `parameter_type` ENUM('temperature','pressure','flow_rate','thickness','vibration','corrosion_rate','ph','conductivity','h2s','co2','velocity','strain') NOT NULL,
  `unit` VARCHAR(50) NULL COMMENT 'Engineering unit (degC, MPa, mm, etc.)',
  `scaling_factor` DECIMAL(10,4) NOT NULL DEFAULT 1.0000 COMMENT 'Multiply raw value by this factor',
  `offset` DECIMAL(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Add this offset after scaling',
  `min_threshold` DECIMAL(10,4) NULL COMMENT 'Low alarm threshold',
  `max_threshold` DECIMAL(10,4) NULL COMMENT 'High alarm threshold',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_value` DECIMAL(15,4) NULL COMMENT 'Most recent cached value',
  `last_updated` DATETIME NULL COMMENT 'Timestamp of last cached value',
  PRIMARY KEY (`id`),
  KEY `idx_ptm_asset` (`asset_id`),
  KEY `idx_ptm_tag` (`pi_tag_name`),
  KEY `idx_ptm_active` (`is_active`),
  UNIQUE KEY `uk_ptm_tag` (`pi_tag_name`, `asset_id`),
  CONSTRAINT `fk_ptm_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Mapping between OSIsoft PI tags and RBI asset parameters';

-- -----------------------------------------------------------------------------
-- Operating excursions: detected out-of-range operating conditions
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `operating_excursions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `excursion_type` ENUM('over_pressure','over_temperature','under_temperature','high_flow','low_flow','high_vibration','corrosive_conditions') NOT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NULL,
  `duration_minutes` INT UNSIGNED NULL,
  `peak_value` DECIMAL(15,4) NOT NULL,
  `threshold_value` DECIMAL(15,4) NOT NULL,
  `severity` ENUM('minor','moderate','severe','critical') NOT NULL DEFAULT 'minor',
  `pi_tag` VARCHAR(255) NULL COMMENT 'Source PI tag that triggered excursion',
  `acknowledged` TINYINT(1) NOT NULL DEFAULT 0,
  `acknowledged_by` INT UNSIGNED NULL,
  `acknowledged_at` DATETIME NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_oe_asset` (`asset_id`),
  KEY `idx_oe_type` (`excursion_type`),
  KEY `idx_oe_severity` (`severity`),
  KEY `idx_oe_start` (`start_time`),
  KEY `idx_oe_acknowledged` (`acknowledged`),
  CONSTRAINT `fk_oe_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oe_ack_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Detected operating excursions from PI process data analysis';

SET FOREIGN_KEY_CHECKS = 1;
