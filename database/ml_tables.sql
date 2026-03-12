-- =============================================================================
-- ML Predictive Analytics & Automated Risk Scoring Tables
-- RBI Engineering Suite
-- =============================================================================

USE `rbi_engineering`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- ML Models: stores trained model coefficients and metadata
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ml_models` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NULL COMMENT 'NULL for fleet-wide models like clustering',
  `model_type` ENUM(
    'linear_regression','polynomial','exponential','weibull','kmeans'
  ) NOT NULL,
  `parameters` JSON NOT NULL COMMENT 'Model coefficients, centroids, etc.',
  `r_squared` DECIMAL(8,6) NULL COMMENT 'Coefficient of determination',
  `rmse` DECIMAL(12,6) NULL COMMENT 'Root mean square error',
  `mae` DECIMAL(12,6) NULL COMMENT 'Mean absolute error',
  `training_data_points` INT UNSIGNED NOT NULL DEFAULT 0,
  `trained_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('active','outdated','failed') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mlm_asset` (`asset_id`),
  KEY `idx_mlm_type` (`model_type`),
  KEY `idx_mlm_status` (`status`),
  KEY `idx_mlm_trained` (`trained_at`),
  CONSTRAINT `fk_mlm_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Trained ML model storage — coefficients, accuracy metrics, status';

-- -----------------------------------------------------------------------------
-- ML Predictions: stores individual prediction outputs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ml_predictions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_id` INT UNSIGNED NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL,
  `prediction_type` ENUM(
    'corrosion_rate','failure_probability','remaining_life','health_index','anomaly'
  ) NOT NULL,
  `predicted_value` DECIMAL(16,6) NOT NULL,
  `confidence_lower` DECIMAL(16,6) NULL,
  `confidence_upper` DECIMAL(16,6) NULL,
  `prediction_date` DATE NOT NULL COMMENT 'Date prediction was made',
  `target_date` DATE NULL COMMENT 'Date the prediction applies to',
  `actual_value` DECIMAL(16,6) NULL COMMENT 'Filled in later for accuracy tracking',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mlp_model` (`model_id`),
  KEY `idx_mlp_asset` (`asset_id`),
  KEY `idx_mlp_type` (`prediction_type`),
  KEY `idx_mlp_target` (`target_date`),
  CONSTRAINT `fk_mlp_model` FOREIGN KEY (`model_id`) REFERENCES `ml_models` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mlp_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='ML prediction results with confidence intervals';

-- -----------------------------------------------------------------------------
-- Risk Scores: automated and ML-enhanced risk scoring history
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `risk_scores` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `overall_risk` DECIMAL(10,4) NOT NULL,
  `pof_score` DECIMAL(10,4) NOT NULL,
  `cof_score` DECIMAL(10,4) NOT NULL,
  `health_index` DECIMAL(6,2) NULL COMMENT '0-100 composite health score',
  `risk_category` ENUM('very_low','low','medium','high','very_high') NOT NULL,
  `scoring_method` ENUM('manual','automated','ml_enhanced') NOT NULL DEFAULT 'automated',
  `input_data` JSON NULL COMMENT 'Snapshot of all inputs used for scoring',
  `scored_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scored_by` INT UNSIGNED NULL,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rs_asset` (`asset_id`),
  KEY `idx_rs_category` (`risk_category`),
  KEY `idx_rs_method` (`scoring_method`),
  KEY `idx_rs_scored_at` (`scored_at`),
  CONSTRAINT `fk_rs_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rs_scored_by` FOREIGN KEY (`scored_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Automated and ML-enhanced risk score history per asset';

-- -----------------------------------------------------------------------------
-- Risk Alerts: auto-generated risk notifications
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `risk_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `alert_type` ENUM(
    'risk_increase','threshold_breach','overdue_inspection',
    'accelerating_degradation','anomaly_detected','model_drift'
  ) NOT NULL,
  `severity` ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
  `message` TEXT NOT NULL,
  `data` JSON NULL COMMENT 'Structured alert data payload',
  `acknowledged` TINYINT(1) NOT NULL DEFAULT 0,
  `acknowledged_by` INT UNSIGNED NULL,
  `acknowledged_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ra2_asset` (`asset_id`),
  KEY `idx_ra2_type` (`alert_type`),
  KEY `idx_ra2_severity` (`severity`),
  KEY `idx_ra2_ack` (`acknowledged`),
  KEY `idx_ra2_created` (`created_at`),
  CONSTRAINT `fk_ra2_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ra2_ack_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Auto-generated risk alerts and notifications';

-- -----------------------------------------------------------------------------
-- Monte Carlo Results: simulation output storage
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `monte_carlo_results` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `simulation_run_id` VARCHAR(36) NOT NULL COMMENT 'UUID grouping one simulation run',
  `iterations` INT UNSIGNED NOT NULL DEFAULT 1000,
  `mean_risk` DECIMAL(12,6) NOT NULL,
  `p10_risk` DECIMAL(12,6) NOT NULL,
  `p50_risk` DECIMAL(12,6) NOT NULL,
  `p90_risk` DECIMAL(12,6) NOT NULL,
  `std_dev` DECIMAL(12,6) NOT NULL,
  `distribution_data` JSON NULL COMMENT 'Histogram bin data for charting',
  `parameters` JSON NULL COMMENT 'Input parameter distributions used',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mcr_asset` (`asset_id`),
  KEY `idx_mcr_run` (`simulation_run_id`),
  KEY `idx_mcr_created` (`created_at`),
  CONSTRAINT `fk_mcr_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Monte Carlo risk simulation results and distributions';

-- -----------------------------------------------------------------------------
-- Asset Clusters: K-means clustering assignments
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_clusters` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cluster_id` INT UNSIGNED NOT NULL COMMENT 'Cluster number (0-based)',
  `asset_id` INT UNSIGNED NOT NULL,
  `cluster_name` VARCHAR(100) NULL,
  `centroid` JSON NULL COMMENT 'Cluster centroid coordinates',
  `distance_to_centroid` DECIMAL(12,6) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ac_cluster` (`cluster_id`),
  KEY `idx_ac_asset` (`asset_id`),
  UNIQUE KEY `uk_ac_asset` (`asset_id`),
  CONSTRAINT `fk_ac_asset` FOREIGN KEY (`asset_id`) REFERENCES `asset_registry` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='K-means asset clustering by risk profile';

SET FOREIGN_KEY_CHECKS = 1;
