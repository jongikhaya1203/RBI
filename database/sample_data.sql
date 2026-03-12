-- =============================================================================
-- RBI Engineering Suite - Comprehensive Sample Data
-- All data EXCEPT users (handled by load_sample_data.php)
-- Safe to re-run: uses INSERT IGNORE throughout
-- =============================================================================

USE `rbi_engineering`;

SET FOREIGN_KEY_CHECKS = 0;
SET @admin_id = 1;

-- =============================================================================
-- EQUIPMENT HIERARCHY (Plant > Units > Systems)
-- =============================================================================

INSERT IGNORE INTO `equipment_hierarchy` (`id`,`name`,`code`,`level`,`parent_id`,`description`,`created_by`) VALUES
-- Plant (parent of SITE-001 which is id=1 from schema defaults)
(10, 'PetroTech Refinery Complex', 'PCT-001', 'plant', 1, 'PetroTech integrated refinery complex — 150,000 bpd capacity', @admin_id),
-- Units
(20, 'Crude Distillation Unit', 'CDU-100', 'unit', 10, 'Atmospheric and vacuum crude distillation — 100,000 bpd', @admin_id),
(21, 'Fluid Catalytic Cracking Unit', 'FCC-200', 'unit', 10, 'FCC unit — 45,000 bpd fresh feed capacity', @admin_id),
(22, 'Hydrotreater Unit', 'HDT-300', 'unit', 10, 'Diesel hydrotreater — 30,000 bpd', @admin_id),
(23, 'Tank Farm', 'TF-400', 'unit', 10, 'Crude and product storage tank farm', @admin_id),
(24, 'Utilities', 'UTL-500', 'unit', 10, 'Steam generation, cooling water, and utility systems', @admin_id),
-- Systems
(30, 'Atmospheric Column System', 'CDU-110', 'system', 20, 'Atmospheric distillation column and overhead system', @admin_id),
(31, 'Vacuum Column System', 'CDU-120', 'system', 20, 'Vacuum distillation column and ejector system', @admin_id),
(32, 'Reactor System', 'FCC-210', 'system', 21, 'FCC reactor-regenerator system', @admin_id),
(33, 'Fractionation System', 'FCC-220', 'system', 21, 'FCC main fractionator and gas concentration', @admin_id),
(34, 'Reactor System', 'HDT-310', 'system', 22, 'Hydrotreater reactor loop and HP separation', @admin_id),
(35, 'Crude Storage', 'TF-410', 'system', 23, 'Crude oil storage tanks', @admin_id),
(36, 'Product Storage', 'TF-420', 'system', 23, 'Finished product storage tanks', @admin_id),
(37, 'Steam System', 'UTL-510', 'system', 24, 'HP/MP steam generation and distribution', @admin_id),
(38, 'Cooling Water System', 'UTL-520', 'system', 24, 'Cooling water circulation and treatment', @admin_id);

-- =============================================================================
-- ASSET REGISTRY (20 assets)
-- =============================================================================

INSERT IGNORE INTO `asset_registry`
  (`id`,`asset_tag`,`asset_name`,`hierarchy_id`,`asset_type`,`asset_subtype`,`manufacturer`,`year_manufactured`,`installation_date`,`commission_date`,`design_code`,`design_code_year`,`p_and_id_reference`,`status`,`criticality`,`rbi_status`,`last_rbi_date`,`next_rbi_date`,`created_by`) VALUES
(1, 'T-101', 'Atmospheric Column T-101', 30, 'column', 'Distillation Column', 'Kawasaki Heavy Industries', 1998, '1999-03-15', '1999-06-01', 'ASME VIII Div 1', '1998', 'PID-CDU-001', 'in_service', 'critical', 'assessed', '2025-01-15', '2027-01-15', @admin_id),
(2, 'E-101', 'Overhead Condenser E-101', 30, 'heat_exchanger', 'Shell & Tube', 'Alfa Laval', 2000, '2000-08-10', '2000-10-01', 'ASME VIII Div 1', '1998', 'PID-CDU-002', 'in_service', 'high', 'assessed', '2025-03-20', '2027-03-20', @admin_id),
(3, 'D-101', 'Reflux Drum D-101', 30, 'pressure_vessel', 'Horizontal Drum', 'CB&I', 1998, '1999-03-15', '1999-06-01', 'ASME VIII Div 1', '1998', 'PID-CDU-002', 'in_service', 'medium', 'assessed', '2025-02-10', '2028-02-10', @admin_id),
(4, 'H-101', 'Crude Charge Heater H-101', 30, 'fired_heater', 'Cabin Type', 'Foster Wheeler', 1998, '1999-02-20', '1999-06-01', 'API 560', '1996', 'PID-CDU-003', 'in_service', 'critical', 'assessed', '2024-11-05', '2026-11-05', @admin_id),
(5, 'T-102', 'Vacuum Column T-102', 31, 'column', 'Vacuum Column', 'Kawasaki Heavy Industries', 1998, '1999-03-15', '1999-06-01', 'ASME VIII Div 1', '1998', 'PID-CDU-010', 'in_service', 'high', 'assessed', '2025-01-15', '2027-01-15', @admin_id),
(6, 'EJ-101', 'Vacuum Overhead Ejector EJ-101', 31, 'other', 'Steam Ejector', 'Graham Corp', 2000, '2000-04-10', '2000-06-01', 'HEI Standards', '1998', 'PID-CDU-011', 'in_service', 'low', 'not_assessed', NULL, NULL, @admin_id),
(7, 'R-201', 'FCC Reactor R-201', 32, 'reactor', 'Fluidized Bed Reactor', 'UOP LLC', 2002, '2003-01-15', '2003-04-01', 'ASME VIII Div 2', '2001', 'PID-FCC-001', 'in_service', 'critical', 'assessed', '2024-09-10', '2026-09-10', @admin_id),
(8, 'R-202', 'Regenerator R-202', 32, 'reactor', 'Fluidized Bed Regenerator', 'UOP LLC', 2002, '2003-01-15', '2003-04-01', 'ASME VIII Div 2', '2001', 'PID-FCC-002', 'in_service', 'critical', 'assessed', '2024-09-10', '2026-09-10', @admin_id),
(9, 'E-201', 'Feed Preheater E-201', 32, 'heat_exchanger', 'Shell & Tube', 'Alfa Laval', 2002, '2003-01-10', '2003-04-01', 'ASME VIII Div 1', '2001', 'PID-FCC-003', 'in_service', 'medium', 'assessed', '2025-04-12', '2028-04-12', @admin_id),
(10, 'T-201', 'Main Fractionator T-201', 33, 'column', 'Fractionation Column', 'IHI Corporation', 2002, '2003-02-20', '2003-04-01', 'ASME VIII Div 1', '2001', 'PID-FCC-010', 'in_service', 'high', 'assessed', '2025-05-01', '2027-05-01', @admin_id),
(11, 'C-201', 'Gas Compressor C-201', 33, 'compressor', 'Centrifugal', 'Elliott Group', 2002, '2003-02-28', '2003-04-01', 'API 617', '2000', 'PID-FCC-011', 'in_service', 'high', 'not_assessed', NULL, NULL, @admin_id),
(12, 'R-301', 'Hydrotreater Reactor R-301', 34, 'reactor', 'Fixed Bed Reactor', 'IHI Corporation', 2005, '2006-03-10', '2006-06-01', 'ASME VIII Div 2', '2004', 'PID-HDT-001', 'in_service', 'critical', 'assessed', '2024-06-15', '2026-06-15', @admin_id),
(13, 'D-301', 'HP Separator D-301', 34, 'pressure_vessel', 'Vertical Drum', 'Samsung Heavy', 2005, '2006-03-10', '2006-06-01', 'ASME VIII Div 1', '2004', 'PID-HDT-002', 'in_service', 'high', 'assessed', '2025-01-20', '2027-01-20', @admin_id),
(14, 'E-301', 'Reactor Feed/Effluent Exchanger E-301', 34, 'heat_exchanger', 'Shell & Tube', 'Alfa Laval', 2005, '2006-03-15', '2006-06-01', 'ASME VIII Div 1', '2004', 'PID-HDT-003', 'in_service', 'high', 'assessed', '2025-02-28', '2027-02-28', @admin_id),
(15, 'TK-401', 'Crude Oil Storage Tank TK-401', 35, 'storage_tank', 'Floating Roof', 'CB&I', 1995, '1996-06-01', '1996-09-01', 'API 650', '1993', 'PID-TF-001', 'in_service', 'high', 'assessed', '2024-12-01', '2029-12-01', @admin_id),
(16, 'TK-402', 'Crude Oil Storage Tank TK-402', 35, 'storage_tank', 'Floating Roof', 'CB&I', 1995, '1996-06-01', '1996-09-01', 'API 650', '1993', 'PID-TF-001', 'in_service', 'high', 'assessed', '2024-12-01', '2029-12-01', @admin_id),
(17, 'TK-403', 'Gasoline Storage Tank TK-403', 36, 'storage_tank', 'Cone Roof', 'CB&I', 2000, '2001-01-15', '2001-04-01', 'API 650', '1998', 'PID-TF-010', 'in_service', 'medium', 'assessed', '2025-06-15', '2030-06-15', @admin_id),
(18, 'TK-404', 'Diesel Storage Tank TK-404', 36, 'storage_tank', 'Cone Roof', 'CB&I', 2000, '2001-01-15', '2001-04-01', 'API 650', '1998', 'PID-TF-010', 'in_service', 'medium', 'not_assessed', NULL, NULL, @admin_id),
(19, 'B-501', 'HP Steam Boiler B-501', 37, 'boiler', 'Water Tube Boiler', 'Babcock & Wilcox', 2003, '2004-05-01', '2004-08-01', 'ASME I', '2001', 'PID-UTL-001', 'in_service', 'high', 'assessed', '2025-03-01', '2027-03-01', @admin_id),
(20, 'P-501', 'Steam Header Piping P-501', 37, 'piping', 'HP Steam Header', 'N/A', 2003, '2004-05-01', '2004-08-01', 'ASME B31.1', '2001', 'PID-UTL-002', 'in_service', 'medium', 'assessed', '2025-04-15', '2027-04-15', @admin_id);

-- Assets 21-22 for cooling water
INSERT IGNORE INTO `asset_registry`
  (`id`,`asset_tag`,`asset_name`,`hierarchy_id`,`asset_type`,`asset_subtype`,`manufacturer`,`year_manufactured`,`installation_date`,`commission_date`,`design_code`,`design_code_year`,`p_and_id_reference`,`status`,`criticality`,`rbi_status`,`created_by`) VALUES
(21, 'CT-501', 'Cooling Tower CT-501', 38, 'other', 'Mechanical Draft Cooling Tower', 'SPX Cooling', 2003, '2004-06-01', '2004-08-01', 'CTI Standards', '2002', 'PID-UTL-010', 'in_service', 'medium', 'not_assessed', @admin_id),
(22, 'P-502', 'CW Pump P-502', 38, 'pump', 'Centrifugal Pump', 'Flowserve', 2003, '2004-06-01', '2004-08-01', 'API 610', '2001', 'PID-UTL-011', 'in_service', 'low', 'not_assessed', @admin_id);

-- =============================================================================
-- DESIGN DATA (for each asset)
-- =============================================================================

INSERT IGNORE INTO `design_data`
  (`asset_id`,`material_spec`,`material_grade`,`nominal_thickness_mm`,`corrosion_allowance_mm`,`minimum_required_thickness_mm`,`design_pressure_mpa`,`design_temperature_c`,`mawp_mpa`,`test_pressure_mpa`,`joint_efficiency`,`diameter_mm`,`length_mm`,`shell_material`,`insulation_type`,`insulation_thickness_mm`,`heat_treatment`,`coating_type`) VALUES
(1, 'SA-516 Gr 70', 'Gr 70', 38.00, 6.00, 28.50, 0.45, 400, 0.50, 0.68, 1.000, 4600, 42000, 'SA-516 Gr 70', 'Mineral Wool', 100, 'PWHT', NULL),
(2, 'SA-516 Gr 70', 'Gr 70', 19.00, 3.00, 12.80, 0.35, 180, 0.40, 0.53, 0.850, 1200, 6000, 'SA-516 Gr 70', 'Mineral Wool', 75, 'None', NULL),
(3, 'SA-516 Gr 70', 'Gr 70', 16.00, 3.00, 10.20, 0.35, 120, 0.40, 0.53, 0.850, 1800, 4500, 'SA-516 Gr 70', NULL, NULL, 'None', 'Epoxy'),
(4, 'SA-213 T5', 'T5', 8.00, 1.50, 5.20, 2.10, 550, 2.30, 3.15, 1.000, 168, 15000, 'SA-213 T5', 'Ceramic Fibre', 150, 'PWHT', NULL),
(5, 'SA-516 Gr 70 + SS 410S clad', 'Gr 70', 42.00, 6.00, 30.00, 0.15, 420, 0.18, 0.23, 1.000, 6000, 38000, 'SA-516 Gr 70', 'Mineral Wool', 100, 'PWHT', NULL),
(6, 'SA-240 Type 316', '316', 6.00, 1.00, 3.50, 0.10, 200, 0.12, 0.15, 0.850, 300, 2000, 'SA-240 Type 316', NULL, NULL, 'None', NULL),
(7, 'SA-387 Gr 11 Cl 2', 'Gr 11 Cl 2', 50.00, 6.00, 38.00, 0.35, 540, 0.40, 0.53, 1.000, 5500, 25000, 'SA-387 Gr 11 Cl 2', 'Mineral Wool + Calcium Silicate', 125, 'PWHT', NULL),
(8, 'SA-387 Gr 11 Cl 2', 'Gr 11 Cl 2', 45.00, 6.00, 33.50, 0.25, 730, 0.30, 0.38, 1.000, 7000, 30000, 'SA-387 Gr 11 Cl 2', 'Mineral Wool + Calcium Silicate', 150, 'PWHT', NULL),
(9, 'SA-516 Gr 70', 'Gr 70', 22.00, 3.00, 15.00, 1.05, 350, 1.15, 1.58, 0.850, 900, 5000, 'SA-516 Gr 70', 'Mineral Wool', 75, 'PWHT', NULL),
(10, 'SA-516 Gr 70', 'Gr 70', 32.00, 6.00, 22.00, 0.35, 380, 0.40, 0.53, 1.000, 4200, 35000, 'SA-516 Gr 70', 'Mineral Wool', 100, 'PWHT', NULL),
(11, 'SA-240 Type 304', '304', 12.00, 1.50, 8.00, 2.50, 150, 2.80, 3.75, 1.000, 600, 2500, 'SA-240 Type 304', NULL, NULL, 'None', NULL),
(12, 'SA-387 Gr 22 Cl 2 + SS 347 weld overlay', 'Gr 22 Cl 2', 85.00, 6.00, 68.00, 17.00, 420, 18.50, 25.50, 1.000, 3200, 18000, 'SA-387 Gr 22 Cl 2', 'Mineral Wool', 100, 'PWHT', NULL),
(13, 'SA-516 Gr 70', 'Gr 70', 45.00, 6.00, 33.00, 14.00, 320, 15.20, 21.00, 1.000, 2400, 6000, 'SA-516 Gr 70', 'Mineral Wool', 75, 'PWHT', NULL),
(14, 'SA-387 Gr 11 Cl 2', 'Gr 11 Cl 2', 28.00, 3.00, 20.00, 14.00, 380, 15.20, 21.00, 1.000, 1000, 7000, 'SA-387 Gr 11 Cl 2', 'Mineral Wool', 75, 'PWHT', NULL),
(15, 'SA-36', 'Structural', 12.70, 3.00, 6.50, 0.00, 90, NULL, NULL, 1.000, 54000, NULL, 'SA-36', NULL, NULL, 'None', 'Epoxy + Glass Flake'),
(16, 'SA-36', 'Structural', 12.70, 3.00, 6.50, 0.00, 90, NULL, NULL, 1.000, 54000, NULL, 'SA-36', NULL, NULL, 'None', 'Epoxy + Glass Flake'),
(17, 'SA-36', 'Structural', 9.50, 3.00, 5.00, 0.00, 60, NULL, NULL, 1.000, 36000, NULL, 'SA-36', NULL, NULL, 'None', 'Epoxy'),
(18, 'SA-36', 'Structural', 9.50, 3.00, 5.00, 0.00, 60, NULL, NULL, 1.000, 36000, NULL, 'SA-36', NULL, NULL, 'None', 'Epoxy'),
(19, 'SA-192', 'Boiler Tube', 6.00, 1.50, 3.50, 10.50, 320, 11.20, 15.75, 1.000, 76, 12000, 'SA-192', 'Mineral Wool', 100, 'None', NULL),
(20, 'SA-106 Gr B', 'Gr B', 11.13, 3.00, 6.35, 4.50, 400, 5.00, 6.75, 1.000, 254, NULL, 'SA-106 Gr B', 'Calcium Silicate', 75, 'None', NULL);

-- =============================================================================
-- OPERATIONAL DATA
-- =============================================================================

INSERT IGNORE INTO `operational_data`
  (`asset_id`,`operating_pressure_mpa`,`operating_temperature_c`,`operating_temperature_min_c`,`operating_temperature_max_c`,`flow_rate_m3h`,`fluid_service`,`fluid_phase`,`h2s_content_ppm`,`co2_content_pct`,`chloride_content_ppm`,`water_content_pct`,`ph_value`,`hydrogen_partial_pressure_mpa`,`velocity_ms`,`external_environment`,`under_insulation`,`heat_traced`,`soil_side_exposure`,`dead_legs`,`effective_date`) VALUES
(1, 0.21, 365, 340, 380, 450.0, 'Crude oil / petroleum fractions', 'two_phase', 500, 0.5, 30, 2.5, NULL, NULL, 1.2, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(2, 0.18, 120, 80, 140, 320.0, 'Naphtha / overhead vapors + water', 'two_phase', 2000, 1.2, 150, 8.0, 4.5, NULL, 8.5, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(3, 0.15, 65, 40, 80, 180.0, 'Naphtha + sour water', 'two_phase', 1500, 0.8, 80, 12.0, 5.0, NULL, 0.8, 'industrial', 0, 0, 0, 1, '2024-01-01'),
(4, 1.80, 480, 400, 520, 380.0, 'Crude oil', 'liquid', 500, 0.3, 20, 1.0, NULL, NULL, 3.5, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(5, 0.08, 395, 370, 410, 350.0, 'Reduced crude / vacuum gas oil', 'two_phase', 200, 0.2, 10, 0.5, NULL, NULL, 0.6, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(6, 0.05, 130, 100, 150, 50.0, 'Steam / non-condensables', 'gas', 100, 0.1, 5, 0.0, NULL, NULL, 300.0, 'industrial', 0, 0, 0, 0, '2024-01-01'),
(7, 0.25, 520, 490, 540, 1200.0, 'Hydrocarbon vapor + catalyst', 'gas', 50, 2.0, 5, 0.2, NULL, NULL, 15.0, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(8, 0.22, 700, 680, 730, 1500.0, 'Flue gas + regenerated catalyst', 'gas', 10, 15.0, 2, 0.1, NULL, NULL, 12.0, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(9, 0.90, 310, 280, 340, 250.0, 'FCC feed (vacuum gas oil)', 'liquid', 100, 0.3, 15, 0.5, NULL, NULL, 2.0, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(10, 0.20, 350, 320, 370, 600.0, 'Hydrocarbon fractions', 'two_phase', 150, 0.8, 20, 3.0, NULL, NULL, 1.5, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(11, 2.00, 110, 80, 130, 800.0, 'Wet gas (C2-C4 hydrocarbons)', 'gas', 300, 1.5, 10, 5.0, NULL, NULL, 25.0, 'industrial', 0, 0, 0, 0, '2024-01-01'),
(12, 15.50, 385, 370, 400, 200.0, 'Diesel + hydrogen + H2S', 'two_phase', 15000, 0.5, 5, 0.2, NULL, 12.00, 2.5, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(13, 14.00, 280, 260, 300, 200.0, 'Hydrogen-rich gas + light HC', 'two_phase', 8000, 0.3, 3, 1.0, NULL, 10.00, 1.5, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(14, 14.00, 350, 320, 380, 200.0, 'Diesel + hydrogen', 'two_phase', 10000, 0.4, 4, 0.3, NULL, 11.00, 3.0, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(15, 0.00, 35, 5, 50, NULL, 'Crude oil (medium sour)', 'liquid', 800, 0.2, 50, 0.5, NULL, NULL, NULL, 'industrial', 0, 0, 1, 0, '2024-01-01'),
(16, 0.00, 35, 5, 50, NULL, 'Crude oil (medium sour)', 'liquid', 800, 0.2, 50, 0.5, NULL, NULL, NULL, 'industrial', 0, 0, 1, 0, '2024-01-01'),
(17, 0.00, 28, 5, 40, NULL, 'Gasoline (finished product)', 'liquid', 10, 0.0, 2, 0.1, NULL, NULL, NULL, 'industrial', 0, 0, 0, 0, '2024-01-01'),
(18, 0.00, 30, 5, 45, NULL, 'Diesel (finished product)', 'liquid', 20, 0.0, 5, 0.2, NULL, NULL, NULL, 'industrial', 0, 0, 0, 0, '2024-01-01'),
(19, 9.50, 300, 200, 320, 85.0, 'BFW / steam', 'two_phase', 0, 0.0, 0, 100.0, 9.5, NULL, 15.0, 'industrial', 1, 0, 0, 0, '2024-01-01'),
(20, 4.20, 380, 350, 400, 120.0, 'HP superheated steam', 'gas', 0, 0.0, 0, 0.0, NULL, NULL, 35.0, 'industrial', 1, 0, 0, 0, '2024-01-01');

-- =============================================================================
-- CORROSION CIRCUITS (8 circuits)
-- =============================================================================

INSERT IGNORE INTO `corrosion_circuits`
  (`id`,`circuit_name`,`circuit_code`,`hierarchy_id`,`description`,`process_fluid`,`material_spec`,`operating_temperature_range`,`operating_pressure_range`,`expected_damage_mechanisms`,`corrosion_rate_mm_yr`,`corrosion_rate_basis`,`status`,`created_by`) VALUES
(1, 'CDU Overhead Circuit', 'CC-CDU-OH', 30, 'CDU overhead system — high H2S/HCl corrosion environment. Column top, overhead line, condenser, reflux drum.', 'Naphtha + H2S + HCl + water', 'CS (SA-516 Gr 70)', '65-140 C', '0.15-0.35 MPa', 'General corrosion, pitting, CUI, acid dewpoint', 0.3500, 'measured', 'active', @admin_id),
(2, 'CDU Atmospheric Column Circuit', 'CC-CDU-COL', 30, 'CDU atmospheric column body and internals — mid-range temperature crude service.', 'Crude oil fractions', 'CS (SA-516 Gr 70)', '340-380 C', '0.15-0.45 MPa', 'General corrosion, sulfidation, naphthenic acid', 0.2000, 'measured', 'active', @admin_id),
(3, 'FCC Reactor Circuit', 'CC-FCC-RX', 32, 'FCC reactor and regenerator — high-temperature catalyst and hydrocarbon service.', 'HC vapor + catalyst fines', 'Cr-Mo (SA-387 Gr 11)', '520-730 C', '0.20-0.25 MPa', 'Creep, oxidation, erosion, carburization', 0.1500, 'estimated', 'active', @admin_id),
(4, 'FCC Fractionator Circuit', 'CC-FCC-FRAC', 33, 'FCC main fractionator and associated piping — intermediate temperature HC service.', 'Hydrocarbon fractions', 'CS (SA-516 Gr 70)', '320-370 C', '0.15-0.35 MPa', 'General corrosion, sulfidation, erosion', 0.2500, 'measured', 'active', @admin_id),
(5, 'HDT High Pressure Circuit', 'CC-HDT-HP', 34, 'Hydrotreater reactor loop — high-pressure hydrogen and H2S service.', 'Diesel + H2 + H2S', 'Cr-Mo (SA-387 Gr 22)', '280-400 C', '14.0-17.0 MPa', 'HTHA, H2/H2S corrosion, temper embrittlement, HIC', 0.1200, 'measured', 'active', @admin_id),
(6, 'Tank Farm Bottoms Circuit', 'CC-TF-BTM', 35, 'Storage tank bottoms in contact with crude oil and water settlings.', 'Crude oil + BS&W', 'CS (SA-36)', '5-50 C', 'Atmospheric', 'Soil-side corrosion, MIC, underdeposit corrosion, pitting', 0.2800, 'measured', 'active', @admin_id),
(7, 'Steam System Circuit', 'CC-UTL-STM', 37, 'HP steam generation and distribution — boiler and steam header piping.', 'BFW / HP steam', 'CS (SA-106 Gr B, SA-192)', '200-400 C', '4.2-10.5 MPa', 'FAC, creep, oxidation, erosion-corrosion', 0.1800, 'measured', 'active', @admin_id),
(8, 'Cooling Water Circuit', 'CC-UTL-CW', 38, 'Cooling water circulation system — open recirculating with treatment.', 'Treated cooling water', 'CS', '25-45 C', '0.3-0.5 MPa', 'General corrosion, MIC, under-deposit, erosion', 0.2200, 'estimated', 'active', @admin_id);

-- =============================================================================
-- CORROSION CIRCUIT ASSET ASSIGNMENTS
-- =============================================================================

INSERT IGNORE INTO `corrosion_circuit_assets` (`circuit_id`,`asset_id`,`cml_reference`,`position_description`) VALUES
-- CC-CDU-OH: overhead circuit
(1, 2, 'CML-E101-01', 'Overhead condenser shell'),
(1, 3, 'CML-D101-01', 'Reflux drum shell'),
-- CC-CDU-COL: atmospheric column
(2, 1, 'CML-T101-01', 'Column shell lower section'),
(2, 4, 'CML-H101-01', 'Heater transfer line'),
-- CC-FCC-RX: FCC reactor
(3, 7, 'CML-R201-01', 'Reactor shell'),
(3, 8, 'CML-R202-01', 'Regenerator shell'),
-- CC-FCC-FRAC: FCC fractionator
(4, 10, 'CML-T201-01', 'Main fractionator shell'),
(4, 9, 'CML-E201-01', 'Feed preheater shell'),
-- CC-HDT-HP: HDT high pressure
(5, 12, 'CML-R301-01', 'Reactor shell'),
(5, 13, 'CML-D301-01', 'HP separator shell'),
(5, 14, 'CML-E301-01', 'Feed/effluent exchanger'),
-- CC-TF-BTM: tank bottoms
(6, 15, 'CML-TK401-01', 'Tank floor plates'),
(6, 16, 'CML-TK402-01', 'Tank floor plates'),
(6, 17, 'CML-TK403-01', 'Tank floor plates'),
(6, 18, 'CML-TK404-01', 'Tank floor plates'),
-- CC-UTL-STM: steam
(7, 19, 'CML-B501-01', 'Boiler tubes'),
(7, 20, 'CML-P501-01', 'Steam header piping'),
-- CC-UTL-CW: cooling water
(8, 21, 'CML-CT501-01', 'Cooling tower basin'),
(8, 22, 'CML-P502-01', 'CW pump casing');

-- =============================================================================
-- DAMAGE MECHANISM ASSIGNMENTS (30+ assignments)
-- We reference dm by dm_code; need subqueries for IDs.
-- =============================================================================

INSERT IGNORE INTO `damage_mechanism_assignments`
  (`asset_id`,`damage_mechanism_id`,`susceptibility`,`severity`,`is_active_threat`,`basis`,`assigned_by`,`review_date`,`next_review_date`) VALUES
-- T-101 Atmospheric Column
(1, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'medium', 'moderate', 1, 'Crude service at 365C — moderate sulfidation rate confirmed by UT trending', @admin_id, '2025-01-15', '2027-01-15'),
(1, (SELECT id FROM damage_mechanisms WHERE dm_code='CUI'), 'high', 'major', 1, 'Insulated CS column operating 340-380C — prime CUI range for austenitic SCC of clamps/attachments', @admin_id, '2025-01-15', '2027-01-15'),
(1, (SELECT id FROM damage_mechanisms WHERE dm_code='NAP-ACID'), 'medium', 'moderate', 1, 'Processing medium-TAN crude (TAN 0.5-1.2) at temperatures >220C', @admin_id, '2025-01-15', '2027-01-15'),

-- E-101 Overhead Condenser
(2, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'high', 'major', 1, 'Overhead service with high H2S (2000ppm) and HCl — accelerated general corrosion', @admin_id, '2025-03-20', '2027-03-20'),
(2, (SELECT id FROM damage_mechanisms WHERE dm_code='CUI'), 'high', 'moderate', 1, 'Insulated exchanger operating 80-140C in CUI-susceptible range', @admin_id, '2025-03-20', '2027-03-20'),
(2, (SELECT id FROM damage_mechanisms WHERE dm_code='LOC-PIT'), 'medium', 'moderate', 1, 'Under-deposit corrosion risk from NH4Cl salt deposition in overhead', @admin_id, '2025-03-20', '2027-03-20'),
(2, (SELECT id FROM damage_mechanisms WHERE dm_code='EROSION'), 'medium', 'minor', 1, 'Two-phase flow impingement at inlet nozzle', @admin_id, '2025-03-20', '2027-03-20'),

-- D-101 Reflux Drum
(3, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'medium', 'moderate', 1, 'Sour water accumulation at bottom — corrosive aqueous phase', @admin_id, '2025-02-10', '2028-02-10'),
(3, (SELECT id FROM damage_mechanisms WHERE dm_code='HIC-SOHIC'), 'medium', 'moderate', 1, 'Wet H2S service (1500ppm H2S) — HIC susceptible CS', @admin_id, '2025-02-10', '2028-02-10'),

-- H-101 Crude Charge Heater
(4, (SELECT id FROM damage_mechanisms WHERE dm_code='NAP-ACID'), 'high', 'major', 1, 'High temperature crude service (480C) with TAN — severe naphthenic acid corrosion risk', @admin_id, '2024-11-05', '2026-11-05'),
(4, (SELECT id FROM damage_mechanisms WHERE dm_code='CREEP'), 'high', 'major', 1, 'Heater tubes operating at 480C — approaching creep range for Cr-Mo', @admin_id, '2024-11-05', '2026-11-05'),
(4, (SELECT id FROM damage_mechanisms WHERE dm_code='EXTERNAL'), 'low', 'minor', 1, 'Firebox external skin exposure', @admin_id, '2024-11-05', '2026-11-05'),

-- T-102 Vacuum Column
(5, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'medium', 'moderate', 1, 'Vacuum service at 395C with reduced crude', @admin_id, '2025-01-15', '2027-01-15'),
(5, (SELECT id FROM damage_mechanisms WHERE dm_code='NAP-ACID'), 'high', 'major', 1, 'Vacuum column wash zone sees highest TAN naphthenic acid attack', @admin_id, '2025-01-15', '2027-01-15'),

-- R-201 FCC Reactor
(7, (SELECT id FROM damage_mechanisms WHERE dm_code='CREEP'), 'high', 'major', 1, 'Operating at 520C — Cr-Mo in creep regime', @admin_id, '2024-09-10', '2026-09-10'),
(7, (SELECT id FROM damage_mechanisms WHERE dm_code='EROSION'), 'high', 'major', 1, 'Catalyst erosion at cyclone inlets and riser termination', @admin_id, '2024-09-10', '2026-09-10'),
(7, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'medium', 'moderate', 1, 'High-temperature oxidation and sulfidation', @admin_id, '2024-09-10', '2026-09-10'),

-- R-202 Regenerator
(8, (SELECT id FROM damage_mechanisms WHERE dm_code='CREEP'), 'high', 'critical', 1, 'Operating at 700C — highest temperature in the refinery', @admin_id, '2024-09-10', '2026-09-10'),
(8, (SELECT id FROM damage_mechanisms WHERE dm_code='EROSION'), 'high', 'major', 1, 'Severe catalyst erosion in dilute phase', @admin_id, '2024-09-10', '2026-09-10'),

-- R-301 Hydrotreater Reactor
(12, (SELECT id FROM damage_mechanisms WHERE dm_code='HTHA'), 'high', 'critical', 1, 'H2 partial pressure 12 MPa at 385C — above Nelson curve for CS, within range for 2.25Cr-1Mo', @admin_id, '2024-06-15', '2026-06-15'),
(12, (SELECT id FROM damage_mechanisms WHERE dm_code='TEMP-EMBRITTLEMENT'), 'high', 'major', 1, '2.25Cr-1Mo operating in temper embrittlement range (370-560C)', @admin_id, '2024-06-15', '2026-06-15'),
(12, (SELECT id FROM damage_mechanisms WHERE dm_code='HIC-SOHIC'), 'medium', 'moderate', 1, 'H2S in feed at 15000ppm — SOHIC risk at welds', @admin_id, '2024-06-15', '2026-06-15'),
(12, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'low', 'minor', 1, 'Weld overlay provides corrosion resistance', @admin_id, '2024-06-15', '2026-06-15'),

-- D-301 HP Separator
(13, (SELECT id FROM damage_mechanisms WHERE dm_code='HIC-SOHIC'), 'high', 'major', 1, 'Wet H2S service at 8000ppm — high HIC risk in CS vessel', @admin_id, '2025-01-20', '2027-01-20'),
(13, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'medium', 'moderate', 1, 'General corrosion from sour water condensation at bottom', @admin_id, '2025-01-20', '2027-01-20'),

-- TK-401 Crude Storage
(15, (SELECT id FROM damage_mechanisms WHERE dm_code='EXTERNAL'), 'medium', 'moderate', 1, 'Atmospheric corrosion of shell exterior', @admin_id, '2024-12-01', '2029-12-01'),
(15, (SELECT id FROM damage_mechanisms WHERE dm_code='LOC-PIT'), 'high', 'major', 1, 'Soil-side pitting corrosion on tank floor', @admin_id, '2024-12-01', '2029-12-01'),
(15, (SELECT id FROM damage_mechanisms WHERE dm_code='MIC'), 'medium', 'moderate', 1, 'SRB activity detected in tank bottoms sludge', @admin_id, '2024-12-01', '2029-12-01'),

-- TK-402 Crude Storage
(16, (SELECT id FROM damage_mechanisms WHERE dm_code='EXTERNAL'), 'medium', 'moderate', 1, 'Atmospheric corrosion of shell exterior', @admin_id, '2024-12-01', '2029-12-01'),
(16, (SELECT id FROM damage_mechanisms WHERE dm_code='LOC-PIT'), 'high', 'major', 1, 'Soil-side pitting corrosion on tank floor', @admin_id, '2024-12-01', '2029-12-01'),
(16, (SELECT id FROM damage_mechanisms WHERE dm_code='MIC'), 'low', 'minor', 1, 'Low SRB count in latest test', @admin_id, '2024-12-01', '2029-12-01'),

-- B-501 HP Steam Boiler
(19, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'medium', 'moderate', 1, 'Boiler tube waterside corrosion', @admin_id, '2025-03-01', '2027-03-01'),
(19, (SELECT id FROM damage_mechanisms WHERE dm_code='CREEP'), 'medium', 'moderate', 1, 'Boiler tubes at 300C under high pressure — low but relevant creep', @admin_id, '2025-03-01', '2027-03-01'),
(19, (SELECT id FROM damage_mechanisms WHERE dm_code='FATIGUE'), 'low', 'minor', 1, 'Thermal fatigue from load cycling', @admin_id, '2025-03-01', '2027-03-01'),

-- P-501 Steam Header
(20, (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'medium', 'moderate', 1, 'Flow-accelerated corrosion in steam piping', @admin_id, '2025-04-15', '2027-04-15'),
(20, (SELECT id FROM damage_mechanisms WHERE dm_code='CUI'), 'high', 'major', 1, 'Calcium silicate insulation on HP steam piping — moisture ingress confirmed', @admin_id, '2025-04-15', '2027-04-15'),
(20, (SELECT id FROM damage_mechanisms WHERE dm_code='EROSION'), 'medium', 'moderate', 1, 'Erosion-corrosion at elbows and tees in steam service', @admin_id, '2025-04-15', '2027-04-15'),
(20, (SELECT id FROM damage_mechanisms WHERE dm_code='CREEP'), 'low', 'minor', 1, 'HP steam at 380C — approaching creep threshold for CS', @admin_id, '2025-04-15', '2027-04-15');

-- =============================================================================
-- RISK ASSESSMENTS (15 assessments)
-- =============================================================================

INSERT IGNORE INTO `risk_assessments`
  (`id`,`assessment_code`,`asset_id`,`matrix_id`,`assessment_type`,`methodology`,`assessment_date`,`next_assessment_date`,`status`,
   `inherent_pof_category`,`inherent_cof_category`,`inherent_risk_level`,`inherent_risk_score`,
   `residual_pof_category`,`residual_cof_category`,`residual_risk_level`,`residual_risk_score`,
   `overall_confidence`,`assessed_by`,`notes`) VALUES
-- Very High risk (2)
(1, 'RA-2025-001', 12, 1, 'semi_quantitative', 'api_581_level1', '2024-06-15', '2026-06-15', 'approved',
  5, 5, 'very_high', 25.00, 4, 4, 'high', 16.00, 'high', @admin_id, 'Hydrotreater reactor — HTHA and temper embrittlement are governing threats'),
(2, 'RA-2025-002', 8, 1, 'semi_quantitative', 'api_581_level1', '2024-09-10', '2026-09-10', 'approved',
  5, 4, 'very_high', 20.00, 4, 3, 'medium_high', 12.00, 'medium', @admin_id, 'FCC regenerator — extreme temperature creep and erosion'),

-- High risk (3)
(3, 'RA-2025-003', 7, 1, 'semi_quantitative', 'api_581_level1', '2024-09-10', '2026-09-10', 'approved',
  4, 4, 'high', 16.00, 3, 3, 'medium', 9.00, 'high', @admin_id, 'FCC reactor — creep and catalyst erosion'),
(4, 'RA-2025-004', 4, 1, 'semi_quantitative', 'api_581_level1', '2024-11-05', '2026-11-05', 'approved',
  4, 4, 'high', 16.00, 3, 4, 'medium_high', 12.00, 'medium', @admin_id, 'Crude heater — high-temp creep and naphthenic acid'),
(5, 'RA-2025-005', 1, 1, 'semi_quantitative', 'api_581_level1', '2025-01-15', '2027-01-15', 'approved',
  4, 3, 'medium_high', 12.00, 3, 3, 'medium', 9.00, 'high', @admin_id, 'Atmospheric column — moderate corrosion with high consequence'),

-- Medium risk (5)
(6, 'RA-2025-006', 2, 1, 'semi_quantitative', 'api_581_level1', '2025-03-20', '2027-03-20', 'approved',
  3, 3, 'medium', 9.00, 2, 3, 'low', 6.00, 'high', @admin_id, 'Overhead condenser — sour overhead corrosion managed by chemical injection'),
(7, 'RA-2025-007', 5, 1, 'semi_quantitative', 'api_581_level1', '2025-01-15', '2027-01-15', 'approved',
  3, 3, 'medium', 9.00, 2, 3, 'low', 6.00, 'medium', @admin_id, 'Vacuum column — naphthenic acid risk managed with clad internals'),
(8, 'RA-2025-008', 13, 1, 'semi_quantitative', 'api_581_level1', '2025-01-20', '2027-01-20', 'approved',
  3, 4, 'medium_high', 12.00, 3, 3, 'medium', 9.00, 'medium', @admin_id, 'HP separator — HIC risk in wet H2S service'),
(9, 'RA-2025-009', 10, 1, 'semi_quantitative', 'api_581_level1', '2025-05-01', '2027-05-01', 'approved',
  3, 3, 'medium', 9.00, 2, 2, 'low', 4.00, 'high', @admin_id, 'FCC main fractionator — moderate corrosion with effective monitoring'),
(10, 'RA-2025-010', 20, 1, 'semi_quantitative', 'api_581_level1', '2025-04-15', '2027-04-15', 'approved',
  3, 2, 'low', 6.00, 3, 2, 'low', 6.00, 'medium', @admin_id, 'Steam header — CUI and FAC moderate risk'),

-- Low risk (3)
(11, 'RA-2025-011', 3, 1, 'semi_quantitative', 'api_581_level1', '2025-02-10', '2028-02-10', 'approved',
  2, 2, 'low', 4.00, 2, 2, 'low', 4.00, 'high', @admin_id, 'Reflux drum — moderate H2S but effective chemical injection program'),
(12, 'RA-2025-012', 15, 1, 'semi_quantitative', 'api_581_level1', '2024-12-01', '2029-12-01', 'approved',
  2, 3, 'low', 6.00, 2, 2, 'low', 4.00, 'medium', @admin_id, 'Crude storage tank — soil-side corrosion managed with CP'),
(13, 'RA-2025-013', 19, 1, 'semi_quantitative', 'api_581_level1', '2025-03-01', '2027-03-01', 'approved',
  2, 3, 'low', 6.00, 2, 2, 'low', 4.00, 'high', @admin_id, 'HP steam boiler — well-managed water treatment program'),

-- Very Low risk (2)
(14, 'RA-2025-014', 17, 1, 'semi_quantitative', 'api_581_level1', '2025-06-15', '2030-06-15', 'approved',
  1, 2, 'negligible', 2.00, 1, 2, 'negligible', 2.00, 'high', @admin_id, 'Gasoline storage tank — benign service, good coating condition'),
(15, 'RA-2025-015', 9, 1, 'semi_quantitative', 'api_581_level1', '2025-04-12', '2028-04-12', 'approved',
  1, 2, 'negligible', 2.00, 1, 1, 'negligible', 1.00, 'high', @admin_id, 'Feed preheater — low corrosion rate, effective monitoring');

-- =============================================================================
-- PROBABILITY OF FAILURE (for each assessment)
-- =============================================================================

INSERT IGNORE INTO `probability_of_failure`
  (`assessment_id`,`thinning_pof`,`scc_pof`,`external_pof`,`htha_pof`,`combined_pof`,`pof_category`,`df_thinning`,`df_total`,`fms_factor`,`inspection_effectiveness`,`num_inspections`,`last_inspection_date`) VALUES
(1, 0.01200000, NULL, 0.00050000, 0.02500000, 0.03750000, 5, 120.5, 450.2, 0.85, 'usually_effective', 4, '2024-06-15'),
(2, 0.00800000, NULL, 0.00100000, NULL, 0.02200000, 5, 85.3, 280.5, 0.85, 'fairly_effective', 3, '2024-09-10'),
(3, 0.00500000, NULL, 0.00080000, NULL, 0.01500000, 4, 65.2, 180.3, 0.85, 'usually_effective', 3, '2024-09-10'),
(4, 0.00800000, NULL, 0.00050000, NULL, 0.01400000, 4, 90.1, 195.6, 0.85, 'fairly_effective', 3, '2024-11-05'),
(5, 0.00400000, NULL, 0.00120000, NULL, 0.00800000, 4, 45.8, 120.2, 0.85, 'usually_effective', 5, '2025-01-15'),
(6, 0.00250000, NULL, 0.00080000, NULL, 0.00420000, 3, 28.5, 65.3, 0.85, 'usually_effective', 4, '2025-03-20'),
(7, 0.00200000, NULL, 0.00060000, NULL, 0.00350000, 3, 22.1, 48.7, 0.85, 'usually_effective', 4, '2025-01-15'),
(8, 0.00150000, 0.00200000, 0.00050000, NULL, 0.00500000, 3, 18.9, 72.5, 0.85, 'fairly_effective', 3, '2025-01-20'),
(9, 0.00180000, NULL, 0.00040000, NULL, 0.00280000, 3, 20.5, 38.2, 0.85, 'usually_effective', 4, '2025-05-01'),
(10, 0.00200000, NULL, 0.00150000, NULL, 0.00420000, 3, 25.3, 55.8, 0.85, 'fairly_effective', 3, '2025-04-15'),
(11, 0.00080000, 0.00050000, 0.00020000, NULL, 0.00180000, 2, 8.5, 22.1, 0.85, 'usually_effective', 5, '2025-02-10'),
(12, 0.00100000, NULL, 0.00080000, NULL, 0.00220000, 2, 12.3, 28.5, 0.85, 'usually_effective', 3, '2024-12-01'),
(13, 0.00060000, NULL, 0.00030000, NULL, 0.00120000, 2, 6.8, 15.2, 0.85, 'usually_effective', 4, '2025-03-01'),
(14, 0.00020000, NULL, 0.00010000, NULL, 0.00035000, 1, 2.1, 4.5, 0.85, 'usually_effective', 3, '2025-06-15'),
(15, 0.00015000, NULL, 0.00005000, NULL, 0.00025000, 1, 1.5, 3.2, 0.85, 'usually_effective', 4, '2025-04-12');

-- =============================================================================
-- CONSEQUENCE OF FAILURE
-- =============================================================================

INSERT IGNORE INTO `consequence_of_failure`
  (`assessment_id`,`flammable_consequence_area_m2`,`flammable_consequence_cost_usd`,`toxic_consequence_area_m2`,`toxic_consequence_cost_usd`,
   `production_loss_per_day_usd`,`estimated_downtime_days`,`business_interruption_cost_usd`,
   `equipment_replacement_cost_usd`,`total_consequence_cost_usd`,`cof_category`,`consequence_category`,
   `fluid_type`,`detection_classification`,`isolation_classification`) VALUES
(1, 2500.00, 1500000.00, 800.00, 500000.00, 450000.00, 90, 40500000.00, 5000000.00, 47500000.00, 5, 'E', 'Diesel + H2 + H2S', 'B', 'B'),
(2, 3000.00, 2000000.00, 200.00, 100000.00, 350000.00, 60, 21000000.00, 4500000.00, 27600000.00, 4, 'D', 'Flue gas + catalyst', 'B', 'C'),
(3, 2800.00, 1800000.00, 150.00, 80000.00, 350000.00, 45, 15750000.00, 3800000.00, 21430000.00, 4, 'D', 'HC vapor + catalyst', 'B', 'B'),
(4, 1500.00, 900000.00, 300.00, 150000.00, 500000.00, 30, 15000000.00, 2000000.00, 18050000.00, 4, 'D', 'Crude oil', 'B', 'B'),
(5, 1200.00, 750000.00, 400.00, 200000.00, 500000.00, 45, 22500000.00, 3500000.00, 26950000.00, 3, 'C', 'Crude oil fractions', 'A', 'B'),
(6, 600.00, 350000.00, 100.00, 50000.00, 500000.00, 14, 7000000.00, 800000.00, 8200000.00, 3, 'C', 'Naphtha', 'A', 'A'),
(7, 800.00, 500000.00, 150.00, 80000.00, 500000.00, 45, 22500000.00, 3000000.00, 26080000.00, 3, 'C', 'Vacuum gas oil', 'A', 'B'),
(8, 400.00, 250000.00, 200.00, 120000.00, 200000.00, 30, 6000000.00, 1500000.00, 7870000.00, 4, 'D', 'H2 + HC', 'B', 'B'),
(9, 900.00, 550000.00, 100.00, 50000.00, 350000.00, 21, 7350000.00, 2000000.00, 9950000.00, 3, 'C', 'HC fractions', 'A', 'B'),
(10, 200.00, 120000.00, 0.00, 0.00, 100000.00, 7, 700000.00, 300000.00, 1120000.00, 2, 'B', 'HP steam', 'A', 'A'),
(11, 300.00, 180000.00, 150.00, 80000.00, 500000.00, 7, 3500000.00, 400000.00, 4160000.00, 2, 'B', 'Naphtha + sour water', 'A', 'A'),
(12, 500.00, 300000.00, 100.00, 60000.00, 250000.00, 14, 3500000.00, 1200000.00, 5060000.00, 3, 'C', 'Crude oil', 'B', 'C'),
(13, 150.00, 90000.00, 0.00, 0.00, 100000.00, 14, 1400000.00, 1000000.00, 2490000.00, 3, 'C', 'Steam / BFW', 'A', 'A'),
(14, 200.00, 120000.00, 0.00, 0.00, 50000.00, 7, 350000.00, 300000.00, 770000.00, 2, 'B', 'Gasoline', 'A', 'A'),
(15, 150.00, 90000.00, 0.00, 0.00, 350000.00, 7, 2450000.00, 500000.00, 3040000.00, 2, 'B', 'VGO', 'A', 'A');

-- =============================================================================
-- INSPECTION PLANS (10 plans)
-- =============================================================================

INSERT IGNORE INTO `inspection_plans`
  (`id`,`plan_code`,`asset_id`,`assessment_id`,`strategy_id`,`plan_name`,`description`,`status`,`priority`,`plan_start_date`,`plan_end_date`,`revision`,`created_by`) VALUES
(1, 'IP-2025-001', 12, 1, 4, 'R-301 HTHA/Creep Monitoring Plan', 'Advanced UT and metallographic inspection for HTHA and temper embrittlement', 'active', 'critical', '2025-01-01', '2026-06-15', 1, @admin_id),
(2, 'IP-2025-002', 8, 2, 4, 'R-202 Creep & Erosion Inspection', 'High-temperature degradation monitoring of regenerator shell', 'active', 'critical', '2025-01-01', '2026-09-10', 1, @admin_id),
(3, 'IP-2025-003', 1, 5, 1, 'T-101 Thinning Survey', 'UT thickness survey of atmospheric column', 'active', 'high', '2025-03-01', '2027-01-15', 1, @admin_id),
(4, 'IP-2025-004', 2, 6, 2, 'E-101 CUI & Corrosion Inspection', 'CUI inspection with thermography and UT follow-up', 'active', 'medium', '2025-04-01', '2027-03-20', 1, @admin_id),
(5, 'IP-2025-005', 15, 12, 8, 'TK-401 API 653 Inspection', 'Tank floor MFL scan and shell UT survey per API 653', 'completed', 'high', '2024-06-01', '2024-12-01', 1, @admin_id),
(6, 'IP-2025-006', 7, 3, 4, 'R-201 Reactor Integrity Assessment', 'Internal visual, UT, and replication of FCC reactor', 'active', 'high', '2025-06-01', '2026-09-10', 1, @admin_id),
(7, 'IP-2025-007', 4, 4, 1, 'H-101 Heater Tube Inspection', 'UT thickness and creep assessment of fired heater tubes', 'active', 'high', '2025-01-01', '2026-11-05', 1, @admin_id),
(8, 'IP-2025-008', 20, 10, 7, 'P-501 Steam Header CUI Program', 'CUI inspection program for HP steam piping', 'active', 'medium', '2025-06-01', '2027-04-15', 1, @admin_id),
(9, 'IP-2024-009', 13, 8, 3, 'D-301 HIC/SCC Assessment', 'Cracking inspection of HP separator in sour service', 'active', 'high', '2025-03-01', '2027-01-20', 1, @admin_id),
(10, 'IP-2024-010', 19, 13, 6, 'B-501 Boiler Internal Inspection', 'API 510 internal inspection of HP boiler', 'completed', 'medium', '2024-09-01', '2025-03-01', 1, @admin_id);

-- =============================================================================
-- INSPECTION TASKS (20 tasks)
-- =============================================================================

INSERT IGNORE INTO `inspection_tasks`
  (`id`,`task_code`,`plan_id`,`task_name`,`inspection_method`,`inspection_effectiveness`,`coverage_pct`,`scope_description`,
   `status`,`priority`,`scheduled_date`,`due_date`,`completed_date`,`estimated_hours`,`actual_hours`,`requires_shutdown`,`requires_scaffold`,`requires_insulation_removal`,`notes`) VALUES
-- Plan 1: R-301 HTHA
(1, 'IT-2025-001', 1, 'R-301 Advanced UT HTHA Screening', 'ut_phased_array', 'highly_effective', 80.00, 'PAUT scan of reactor shell welds for HTHA indications per API RP 941', 'completed', 'critical', '2025-06-01', '2025-06-15', '2025-06-10', 24.00, 28.00, 1, 1, 1, 'Completed during 2025 TA'),
(2, 'IT-2025-002', 1, 'R-301 Metallographic Replication', 'other', 'highly_effective', 30.00, 'In-situ metallographic replication at hot-wall nozzles', 'completed', 'critical', '2025-06-01', '2025-06-15', '2025-06-12', 16.00, 18.00, 1, 1, 1, 'Replication showed no HTHA fissuring'),
(3, 'IT-2025-003', 1, 'R-301 UT Thickness Survey', 'ut_thickness', 'usually_effective', 90.00, 'UT grid thickness survey of reactor shell and heads', 'completed', 'high', '2025-06-01', '2025-06-15', '2025-06-08', 16.00, 14.00, 1, 1, 1, NULL),

-- Plan 2: R-202 Creep
(4, 'IT-2025-004', 2, 'R-202 Visual Inspection (internal)', 'internal_visual', 'usually_effective', 95.00, 'Internal visual of regenerator shell, cyclones, and grid', 'scheduled', 'critical', '2026-04-01', '2026-04-15', NULL, 20.00, NULL, 1, 0, 0, 'Planned for 2026 TA'),
(5, 'IT-2025-005', 2, 'R-202 Creep Replication', 'other', 'highly_effective', 25.00, 'Metallographic replication at hot spots identified during previous inspection', 'planned', 'high', '2026-04-01', '2026-04-15', NULL, 12.00, NULL, 1, 0, 0, NULL),

-- Plan 3: T-101 Thinning
(6, 'IT-2025-006', 3, 'T-101 External UT Thickness Survey', 'ut_thickness', 'usually_effective', 85.00, 'UT thickness grid survey of column shell — 12 CML locations', 'in_progress', 'high', '2025-11-01', '2025-12-15', NULL, 20.00, NULL, 0, 1, 1, 'Scaffold erected, survey in progress'),
(7, 'IT-2025-007', 3, 'T-101 Internal Visual Inspection', 'internal_visual', 'usually_effective', 90.00, 'Internal visual during next turnaround', 'planned', 'medium', '2026-10-01', '2026-11-15', NULL, 16.00, NULL, 1, 0, 0, NULL),

-- Plan 4: E-101 CUI
(8, 'IT-2025-008', 4, 'E-101 Thermography CUI Screening', 'thermography', 'fairly_effective', 70.00, 'IR thermography scan of insulated condenser for moisture ingress', 'completed', 'medium', '2025-07-01', '2025-07-31', '2025-07-15', 4.00, 3.50, 0, 0, 0, 'Hot spots identified at shell nozzle area'),
(9, 'IT-2025-009', 4, 'E-101 Insulation Removal & UT', 'ut_thickness', 'usually_effective', 40.00, 'Strip insulation at hot spots and UT measure — follow-up to thermography', 'scheduled', 'high', '2025-10-01', '2025-10-31', NULL, 8.00, NULL, 0, 1, 1, 'Follow-up to thermography findings'),

-- Plan 5: TK-401 API 653
(10, 'IT-2025-010', 5, 'TK-401 Floor MFL Scan', 'mfl', 'highly_effective', 95.00, 'MFL scan of entire tank floor per API 653', 'completed', 'high', '2024-09-01', '2024-10-15', '2024-09-20', 24.00, 22.00, 1, 0, 0, '12 pits identified, 3 requiring repair'),
(11, 'IT-2025-011', 5, 'TK-401 Shell UT Survey', 'ut_thickness', 'usually_effective', 80.00, 'Shell plate UT thickness survey — all courses', 'completed', 'medium', '2024-09-01', '2024-10-15', '2024-09-22', 12.00, 10.00, 1, 0, 0, 'Shell in good condition'),

-- Plan 6: R-201 reactor
(12, 'IT-2025-012', 6, 'R-201 Internal Visual + UT', 'internal_visual', 'usually_effective', 90.00, 'Internal inspection of reactor during 2026 TA', 'planned', 'high', '2026-04-01', '2026-05-01', NULL, 24.00, NULL, 1, 0, 0, NULL),

-- Plan 7: H-101 heater
(13, 'IT-2025-013', 7, 'H-101 Tube UT Thickness Survey', 'ut_thickness', 'usually_effective', 75.00, 'UT thickness of selected heater tubes during TA', 'scheduled', 'high', '2026-03-01', '2026-03-31', NULL, 20.00, NULL, 1, 1, 0, 'Focus on return bends and outlet legs'),
(14, 'IT-2025-014', 7, 'H-101 External Visual (online)', 'visual', 'fairly_effective', 60.00, 'Online external visual of heater casing and stack', 'completed', 'low', '2025-08-01', '2025-08-31', '2025-08-15', 4.00, 3.00, 0, 0, 0, 'No anomalies observed'),

-- Plan 8: P-501 CUI
(15, 'IT-2025-015', 8, 'P-501 Thermography CUI Survey', 'thermography', 'fairly_effective', 65.00, 'IR survey of insulated HP steam header for CUI indications', 'completed', 'medium', '2025-09-01', '2025-09-30', '2025-09-18', 6.00, 5.50, 0, 0, 0, 'Suspect areas at pipe supports'),
(16, 'IT-2025-016', 8, 'P-501 UT at CUI Suspect Locations', 'ut_thickness', 'usually_effective', 35.00, 'UT thickness at suspect locations from thermography', 'in_progress', 'high', '2025-12-01', '2026-01-31', NULL, 12.00, NULL, 0, 1, 1, 'Insulation removal in progress'),

-- Plan 9: D-301 HIC
(17, 'IT-2025-017', 9, 'D-301 Wet Fluorescent MPI', 'magnetic_particle', 'usually_effective', 85.00, 'WFMPI of all shell welds for HIC/SOHIC indications', 'planned', 'high', '2026-04-01', '2026-04-30', NULL, 16.00, NULL, 1, 0, 0, NULL),
(18, 'IT-2025-018', 9, 'D-301 UT Shear Wave Weld Scan', 'ut_shear_wave', 'highly_effective', 70.00, 'Shear wave UT of longitudinal and circumferential welds', 'planned', 'high', '2026-04-01', '2026-04-30', NULL, 20.00, NULL, 1, 0, 0, NULL),

-- Plan 10: B-501 boiler
(19, 'IT-2025-019', 10, 'B-501 Internal Visual Inspection', 'internal_visual', 'usually_effective', 90.00, 'Internal inspection of boiler drum and tubes', 'completed', 'medium', '2025-01-15', '2025-02-15', '2025-01-28', 16.00, 14.00, 1, 0, 0, 'Minor pitting on lower drum'),
(20, 'IT-2025-020', 10, 'B-501 Tube UT Thickness', 'ut_thickness', 'usually_effective', 60.00, 'UT thickness of selected boiler tubes', 'completed', 'medium', '2025-01-15', '2025-02-15', '2025-01-30', 12.00, 11.00, 1, 0, 0, 'Remaining wall adequate');

-- =============================================================================
-- INSPECTION FINDINGS (15 findings)
-- =============================================================================

INSERT IGNORE INTO `inspection_findings`
  (`id`,`finding_code`,`task_id`,`asset_id`,`finding_type`,`severity`,`location_description`,`cml_reference`,
   `measured_thickness_mm`,`min_measured_thickness_mm`,`measured_depth_mm`,`measured_length_mm`,
   `damage_mechanism_id`,`corrective_action`,`disposition`,`disposition_date`,`notes`,`created_by`) VALUES
(1, 'FND-2025-001', 1, 12, 'no_defect', 'negligible', 'Reactor shell — all PAUT zones', 'CML-R301-01', NULL, NULL, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='HTHA'), 'No HTHA indications detected — continue monitoring per plan', 'acceptable', '2025-06-15', 'Backscatter UT confirmed no HTHA fissuring', @admin_id),
(2, 'FND-2025-002', 3, 12, 'wall_thinning', 'minor', 'Reactor inlet nozzle weld area', 'CML-R301-02', 82.50, 81.80, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'Monitor at next inspection — 0.12 mm/yr rate within acceptable range', 'monitor', '2025-06-15', 'Thinning rate consistent with long-term average', @admin_id),
(3, 'FND-2025-003', 10, 15, 'pitting', 'major', 'Tank floor — NE quadrant near water draw-off', 'CML-TK401-BTM', 8.20, 6.80, 5.90, 150.00,
  (SELECT id FROM damage_mechanisms WHERE dm_code='LOC-PIT'), 'Weld repair of 3 deepest pits — floor plate replacement in NE sector', 'repair', '2024-10-15', '12 pits found, 3 through 50% wall loss, repaired during outage', @admin_id),
(4, 'FND-2025-004', 10, 15, 'corrosion_under_insulation', 'moderate', 'Tank floor — soil-side general thinning', 'CML-TK401-BTM', 10.50, 9.80, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='EXTERNAL'), 'Monitor — floor thickness above minimum. Cathodic protection verified functional.', 'monitor', '2024-10-15', 'Average floor thickness 10.5mm vs nominal 12.7mm', @admin_id),
(5, 'FND-2025-005', 11, 15, 'no_defect', 'negligible', 'Tank shell — all courses', 'CML-TK401-SHL', 12.10, 11.80, NULL, NULL,
  NULL, 'Shell in good condition — continue standard external monitoring', 'acceptable', '2024-10-15', 'Shell coating in good condition, minimal loss', @admin_id),
(6, 'FND-2025-006', 8, 2, 'corrosion_under_insulation', 'moderate', 'Condenser shell — nozzle N1 area', 'CML-E101-N1', 16.80, 15.20, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='CUI'), 'Strip insulation, blast clean, UT confirm, re-coat and re-insulate', 'monitor', '2025-07-20', 'CUI confirmed by thermography and follow-up UT', @admin_id),
(7, 'FND-2025-007', 6, 1, 'wall_thinning', 'moderate', 'Column shell — tray 15 elevation', 'CML-T101-05', 33.80, 32.50, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'Monitor — accelerating rate compared to last period. Review at next assessment.', 'monitor', '2025-12-01', 'Rate increase from 0.15 to 0.22 mm/yr in last period', @admin_id),
(8, 'FND-2025-008', 6, 1, 'wall_thinning', 'minor', 'Column shell — bottom sump area', 'CML-T101-12', 35.20, 34.80, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'Monitor — stable corrosion rate', 'acceptable', '2025-12-01', NULL, @admin_id),
(9, 'FND-2025-009', 14, 4, 'no_defect', 'negligible', 'Heater casing and stack exterior', NULL, NULL, NULL, NULL, NULL,
  NULL, 'External condition satisfactory — no hot spots or distortion observed', 'acceptable', '2025-08-15', 'Routine visual — no concerns', @admin_id),
(10, 'FND-2025-010', 15, 20, 'corrosion_under_insulation', 'major', 'Steam header — pipe support location PS-03', 'CML-P501-PS03', 8.20, 7.10, NULL, 300.00,
  (SELECT id FROM damage_mechanisms WHERE dm_code='CUI'), 'Urgent: localized wall loss at support. Temporary repair applied. Plan permanent repair.', 'repair', '2025-10-01', 'Severe CUI at support — moisture trap. Wall below t_min at one spot.', @admin_id),
(11, 'FND-2025-011', 15, 20, 'corrosion_under_insulation', 'moderate', 'Steam header — elbow downstream of B-501', 'CML-P501-ELB1', 9.50, 8.80, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='CUI'), 'Strip and re-insulate with vapor barrier. Monitor thickness.', 'monitor', '2025-10-01', 'CUI at elbow — insulation in poor condition', @admin_id),
(12, 'FND-2025-012', 19, 19, 'pitting', 'minor', 'Lower drum — waterside near blowdown nozzle', 'CML-B501-LD', 5.50, 5.20, 0.80, 20.00,
  (SELECT id FROM damage_mechanisms WHERE dm_code='LOC-PIT'), 'Shallow pitting — monitor. Review water treatment program.', 'monitor', '2025-02-01', 'Minor pitting near stagnant area', @admin_id),
(13, 'FND-2025-013', 20, 19, 'wall_thinning', 'negligible', 'Boiler tubes — selected tubes Row 3', 'CML-B501-T3', 5.70, 5.50, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'Tube thickness adequate — well within limits', 'acceptable', '2025-02-01', 'Nominal 6.0mm, measured 5.5mm min after 21 years', @admin_id),
(14, 'FND-2025-014', 16, 20, 'corrosion_under_insulation', 'moderate', 'Steam header — valve flange V-501', 'CML-P501-V501', 9.80, 9.20, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='CUI'), 'Remove damaged insulation, treat surface, re-insulate', 'monitor', '2026-01-15', 'CUI at valve flange — moisture trap at insulation termination', @admin_id),
(15, 'FND-2025-015', 1, 12, 'wall_thinning', 'minor', 'Reactor outlet nozzle', 'CML-R301-03', 83.20, 82.50, NULL, NULL,
  (SELECT id FROM damage_mechanisms WHERE dm_code='GEN-THIN'), 'Minor thinning at outlet — within corrosion allowance', 'acceptable', '2025-06-15', NULL, @admin_id);

-- =============================================================================
-- CORROSION RATE TRACKING (thickness measurements — 20 records)
-- =============================================================================

INSERT IGNORE INTO `corrosion_rate_tracking`
  (`asset_id`,`cml_reference`,`measurement_date`,`measured_thickness_mm`,`measurement_method`,`measurement_quality`,`operator`,`temperature_at_measurement_c`,`notes`,`created_by`) VALUES
-- T-101: multiple readings over time showing acceleration
(1, 'CML-T101-05', '2018-04-15', 36.20, 'ut_manual', 'good', 'J. Smith', 35.0, 'Baseline after turnaround', @admin_id),
(1, 'CML-T101-05', '2020-10-20', 35.50, 'ut_manual', 'good', 'J. Smith', 32.0, 'Routine survey', @admin_id),
(1, 'CML-T101-05', '2023-03-12', 34.60, 'ut_manual', 'good', 'S. Lee', 30.0, 'Routine survey', @admin_id),
(1, 'CML-T101-05', '2025-11-15', 33.80, 'ut_manual', 'good', 'S. Lee', 28.0, 'Rate increasing — review at next assessment', @admin_id),

-- E-101: overhead condenser (higher rate)
(2, 'CML-E101-SH1', '2019-06-10', 18.50, 'ut_manual', 'good', 'J. Smith', 40.0, 'Baseline', @admin_id),
(2, 'CML-E101-SH1', '2022-03-15', 17.20, 'ut_manual', 'good', 'S. Lee', 38.0, 'Overhead corrosion evident', @admin_id),
(2, 'CML-E101-SH1', '2025-07-20', 15.80, 'ut_manual', 'good', 'S. Lee', 35.0, 'Rate consistent ~0.42 mm/yr', @admin_id),

-- R-301: hydrotreater reactor (low rate due to weld overlay)
(12, 'CML-R301-01', '2010-06-15', 84.80, 'ut_manual', 'good', 'M. Chen', 30.0, 'Baseline after commissioning', @admin_id),
(12, 'CML-R301-01', '2016-06-20', 84.10, 'ut_manual', 'good', 'M. Chen', 28.0, 'Routine TA survey', @admin_id),
(12, 'CML-R301-01', '2021-06-18', 83.40, 'ut_manual', 'good', 'S. Lee', 30.0, 'Routine TA survey', @admin_id),
(12, 'CML-R301-01', '2025-06-10', 82.50, 'ut_manual', 'good', 'S. Lee', 29.0, 'Consistent low rate — weld overlay effective', @admin_id),

-- TK-401: tank floor
(15, 'CML-TK401-BTM', '2009-08-20', 12.50, 'ut_manual', 'good', 'R. Patel', 25.0, 'API 653 inspection', @admin_id),
(15, 'CML-TK401-BTM', '2016-09-15', 11.00, 'ut_manual', 'good', 'R. Patel', 28.0, 'API 653 inspection', @admin_id),
(15, 'CML-TK401-BTM', '2024-09-20', 9.80, 'ut_manual', 'good', 'S. Lee', 26.0, 'Floor thinning continues — CP operational', @admin_id),

-- P-501: steam header (CUI affected)
(20, 'CML-P501-PS03', '2015-03-20', 11.00, 'ut_manual', 'good', 'J. Smith', 30.0, 'Baseline', @admin_id),
(20, 'CML-P501-PS03', '2020-04-18', 9.50, 'ut_manual', 'acceptable', 'S. Lee', 32.0, 'CUI developing at support', @admin_id),
(20, 'CML-P501-PS03', '2025-09-18', 7.10, 'ut_manual', 'good', 'S. Lee', 28.0, 'Severe CUI — below t_min at one location', @admin_id),

-- B-501: boiler tubes
(19, 'CML-B501-T3', '2012-02-10', 5.95, 'ut_manual', 'good', 'M. Chen', 25.0, 'Baseline', @admin_id),
(19, 'CML-B501-T3', '2019-01-22', 5.70, 'ut_manual', 'good', 'M. Chen', 26.0, 'Routine', @admin_id),
(19, 'CML-B501-T3', '2025-01-30', 5.50, 'ut_manual', 'good', 'S. Lee', 24.0, 'Steady low rate', @admin_id);

-- =============================================================================
-- CORROSION RATE HISTORY (computed rates — 20 records)
-- =============================================================================

INSERT IGNORE INTO `corrosion_rate_history`
  (`asset_id`,`cml_reference`,`period_start_date`,`period_end_date`,`start_thickness_mm`,`end_thickness_mm`,
   `short_term_rate_mm_yr`,`long_term_rate_mm_yr`,`rate_change_pct`,`rate_trend`,`data_points_used`,`calculation_method`) VALUES
(1, 'CML-T101-05', '2018-04-15', '2020-10-20', 36.20, 35.50, 0.2700, 0.2700, NULL, 'stable', 2, 'Linear two-point'),
(1, 'CML-T101-05', '2020-10-20', '2023-03-12', 35.50, 34.60, 0.3700, 0.2900, 37.04, 'increasing', 3, 'Linear regression'),
(1, 'CML-T101-05', '2023-03-12', '2025-11-15', 34.60, 33.80, 0.3000, 0.3100, -18.92, 'stable', 4, 'Linear regression'),
(1, 'CML-T101-05', '2018-04-15', '2025-11-15', 36.20, 33.80, 0.3000, 0.3100, NULL, 'increasing', 4, 'Long-term linear'),

(2, 'CML-E101-SH1', '2019-06-10', '2022-03-15', 18.50, 17.20, 0.4700, 0.4700, NULL, 'stable', 2, 'Linear two-point'),
(2, 'CML-E101-SH1', '2022-03-15', '2025-07-20', 17.20, 15.80, 0.4100, 0.4400, -12.77, 'stable', 3, 'Linear regression'),
(2, 'CML-E101-SH1', '2019-06-10', '2025-07-20', 18.50, 15.80, 0.4100, 0.4400, NULL, 'stable', 3, 'Long-term linear'),

(12, 'CML-R301-01', '2010-06-15', '2016-06-20', 84.80, 84.10, 0.1200, 0.1200, NULL, 'stable', 2, 'Linear two-point'),
(12, 'CML-R301-01', '2016-06-20', '2021-06-18', 84.10, 83.40, 0.1400, 0.1200, 16.67, 'stable', 3, 'Linear regression'),
(12, 'CML-R301-01', '2021-06-18', '2025-06-10', 83.40, 82.50, 0.2200, 0.1500, 57.14, 'increasing', 4, 'Linear regression'),
(12, 'CML-R301-01', '2010-06-15', '2025-06-10', 84.80, 82.50, 0.2200, 0.1500, NULL, 'increasing', 4, 'Long-term linear'),

(15, 'CML-TK401-BTM', '2009-08-20', '2016-09-15', 12.50, 11.00, 0.2100, 0.2100, NULL, 'stable', 2, 'Linear two-point'),
(15, 'CML-TK401-BTM', '2016-09-15', '2024-09-20', 11.00, 9.80, 0.1500, 0.1800, -28.57, 'decreasing', 3, 'Linear regression'),
(15, 'CML-TK401-BTM', '2009-08-20', '2024-09-20', 12.50, 9.80, 0.1500, 0.1800, NULL, 'decreasing', 3, 'Long-term linear'),

(20, 'CML-P501-PS03', '2015-03-20', '2020-04-18', 11.00, 9.50, 0.2900, 0.2900, NULL, 'stable', 2, 'Linear two-point'),
(20, 'CML-P501-PS03', '2020-04-18', '2025-09-18', 9.50, 7.10, 0.4400, 0.3700, 51.72, 'increasing', 3, 'Linear regression'),
(20, 'CML-P501-PS03', '2015-03-20', '2025-09-18', 11.00, 7.10, 0.4400, 0.3700, NULL, 'increasing', 3, 'Long-term linear'),

(19, 'CML-B501-T3', '2012-02-10', '2019-01-22', 5.95, 5.70, 0.0360, 0.0360, NULL, 'stable', 2, 'Linear two-point'),
(19, 'CML-B501-T3', '2019-01-22', '2025-01-30', 5.70, 5.50, 0.0330, 0.0350, -8.33, 'stable', 3, 'Linear regression'),
(19, 'CML-B501-T3', '2012-02-10', '2025-01-30', 5.95, 5.50, 0.0330, 0.0350, NULL, 'stable', 3, 'Long-term linear');

-- =============================================================================
-- REMAINING LIFE ESTIMATES (15 records)
-- =============================================================================

INSERT IGNORE INTO `remaining_life_estimates`
  (`asset_id`,`assessment_id`,`calculation_date`,`measured_thickness_mm`,`min_required_thickness_mm`,
   `long_term_corrosion_rate_mm_yr`,`short_term_corrosion_rate_mm_yr`,`governing_corrosion_rate_mm_yr`,
   `rate_basis`,`remaining_life_years`,`retirement_date`,`half_life_date`,`next_inspection_date_by_rl`,
   `confidence`,`calculation_method`,`notes`,`created_by`) VALUES
-- Critical: <2 years remaining
(20, 10, '2025-10-01', 7.10, 6.35, 0.3700, 0.4400, 0.4400, 'short_term', 1.70, '2027-06-01', '2026-08-01', '2026-02-01', 'medium', 'Linear projection', 'CRITICAL: CUI-driven wall loss approaching t_min. Repair or replace within 18 months.', @admin_id),
(2, 6, '2025-07-20', 15.80, 12.80, 0.4400, 0.4100, 0.4400, 'long_term', 6.82, '2032-05-01', '2029-01-01', '2027-03-01', 'high', 'Linear projection', 'Overhead corrosion well characterized — stable rate', @admin_id),

-- Normal: 5-15 years
(1, 5, '2025-12-01', 33.80, 28.50, 0.3100, 0.3000, 0.3100, 'long_term', 17.10, '2043-01-01', '2034-06-01', '2030-01-01', 'high', 'Linear projection', 'Accelerating trend noted — review in next assessment', @admin_id),
(12, 1, '2025-06-15', 82.50, 68.00, 0.1500, 0.2200, 0.2200, 'short_term', 6.59, '2032-01-01', '2028-10-01', '2027-06-01', 'medium', 'Linear projection', 'Rate increase noted — may indicate overlay degradation', @admin_id),
(15, 12, '2024-10-01', 9.80, 6.50, 0.1800, 0.1500, 0.1800, 'long_term', 18.33, '2043-03-01', '2034-02-01', '2033-03-01', 'medium', 'Linear projection', 'CP system reducing soil-side rate', @admin_id),
(13, 8, '2025-02-01', 43.50, 33.00, 0.1800, 0.2000, 0.2000, 'short_term', 52.50, '2077-08-01', '2051-05-01', '2041-02-01', 'medium', 'Linear projection', 'Excellent remaining life despite sour service', @admin_id),
(19, 13, '2025-02-01', 5.50, 3.50, 0.0350, 0.0330, 0.0350, 'long_term', 57.14, '2082-03-01', '2053-08-01', '2050-02-01', 'high', 'Linear projection', 'Very low corrosion rate — well-treated BFW', @admin_id),
(3, 11, '2025-03-01', 14.50, 10.20, 0.1200, 0.1300, 0.1300, 'short_term', 33.08, '2058-04-01', '2041-09-01', '2039-03-01', 'high', 'Linear projection', 'Good condition — chemical injection effective', @admin_id),
(5, 7, '2025-02-01', 39.50, 30.00, 0.2500, 0.2800, 0.2800, 'short_term', 33.93, '2059-01-01', '2042-02-01', '2039-02-01', 'medium', 'Linear projection', 'SS clad provides protection in wash zone', @admin_id),
(10, 9, '2025-06-01', 29.50, 22.00, 0.2000, 0.1800, 0.2000, 'long_term', 37.50, '2063-01-01', '2044-04-01', '2040-06-01', 'high', 'Linear projection', 'Stable rate — well-managed corrosion', @admin_id),
(7, 3, '2025-01-01', 47.80, 38.00, 0.1500, 0.1200, 0.1500, 'long_term', 65.33, '2090-05-01', '2057-09-01', '2050-01-01', 'medium', 'Linear projection', 'Erosion is localized — monitor cyclone areas separately', @admin_id),
(4, 4, '2025-01-01', 7.20, 5.20, 0.1800, 0.2000, 0.2000, 'short_term', 10.00, '2035-01-01', '2030-01-01', '2028-01-01', 'medium', 'Linear projection', 'Heater tubes — accelerated naphthenic acid corrosion at bends', @admin_id),
(8, 2, '2025-01-01', 42.50, 33.50, 0.1500, 0.1800, 0.1800, 'short_term', 50.00, '2075-01-01', '2050-01-01', '2045-01-01', 'low', 'Linear projection', 'Creep life is governing, not wall loss — separate creep assessment needed', @admin_id),
(9, 15, '2025-05-01', 21.00, 15.00, 0.0800, 0.0700, 0.0800, 'long_term', 75.00, '2100-05-01', '2062-11-01', '2055-05-01', 'high', 'Linear projection', 'Very low corrosion rate — benign service', @admin_id),
(14, NULL, '2025-03-01', 26.50, 20.00, 0.1000, 0.1100, 0.1100, 'short_term', 59.09, '2084-04-01', '2054-09-01', '2049-03-01', 'medium', 'Linear projection', 'Cr-Mo material provides good H2S resistance', @admin_id);

-- =============================================================================
-- RISK SCORES (ML module — 20 records)
-- =============================================================================

INSERT IGNORE INTO `risk_scores`
  (`asset_id`,`overall_risk`,`pof_score`,`cof_score`,`health_index`,`risk_category`,`scoring_method`,`scored_at`,`scored_by`,`notes`) VALUES
(12, 25.0000, 5.0000, 5.0000, 22.50, 'very_high', 'ml_enhanced', '2025-07-01 08:00:00', @admin_id, 'HTHA threat — lowest health index in fleet'),
(8, 20.0000, 5.0000, 4.0000, 28.00, 'very_high', 'ml_enhanced', '2025-07-01 08:00:00', @admin_id, 'Extreme temperature service — creep governing'),
(7, 16.0000, 4.0000, 4.0000, 35.50, 'high', 'ml_enhanced', '2025-07-01 08:00:00', @admin_id, 'Catalyst erosion and creep'),
(4, 16.0000, 4.0000, 4.0000, 38.00, 'high', 'ml_enhanced', '2025-07-01 08:00:00', @admin_id, 'Heater tube degradation'),
(1, 12.0000, 4.0000, 3.0000, 45.00, 'medium', 'automated', '2025-07-01 08:00:00', @admin_id, 'Accelerating corrosion trend'),
(20, 6.0000, 3.0000, 2.0000, 42.00, 'medium', 'ml_enhanced', '2025-10-01 08:00:00', @admin_id, 'CUI-driven — critical remaining life'),
(13, 12.0000, 3.0000, 4.0000, 48.00, 'medium', 'automated', '2025-07-01 08:00:00', @admin_id, 'HIC risk in sour service'),
(2, 9.0000, 3.0000, 3.0000, 52.00, 'medium', 'automated', '2025-07-01 08:00:00', @admin_id, 'Overhead corrosion managed'),
(5, 9.0000, 3.0000, 3.0000, 55.00, 'medium', 'automated', '2025-07-01 08:00:00', @admin_id, 'Naphthenic acid risk managed'),
(10, 9.0000, 3.0000, 3.0000, 58.00, 'medium', 'automated', '2025-07-01 08:00:00', @admin_id, 'Fractionator in good condition'),
(14, 6.0000, 2.0000, 3.0000, 62.00, 'low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Cr-Mo exchanger — good condition'),
(3, 4.0000, 2.0000, 2.0000, 68.00, 'low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Reflux drum — benign conditions'),
(15, 6.0000, 2.0000, 3.0000, 65.00, 'low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Tank floor managed with CP'),
(16, 6.0000, 2.0000, 3.0000, 66.00, 'low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Sister tank to TK-401'),
(19, 6.0000, 2.0000, 3.0000, 72.00, 'low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Boiler — excellent water chemistry'),
(17, 2.0000, 1.0000, 2.0000, 85.00, 'very_low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Gasoline tank — benign service'),
(18, 2.0000, 1.0000, 2.0000, 82.00, 'very_low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Diesel tank — benign service'),
(9, 2.0000, 1.0000, 2.0000, 88.00, 'very_low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Feed preheater — excellent condition'),
(6, 2.0000, 1.0000, 2.0000, 90.00, 'very_low', 'automated', '2025-07-01 08:00:00', @admin_id, 'SS ejector — minimal corrosion'),
(11, 4.0000, 2.0000, 2.0000, 78.00, 'low', 'automated', '2025-07-01 08:00:00', @admin_id, 'Compressor — mechanical risk only');

-- =============================================================================
-- RISK ALERTS (10 alerts)
-- =============================================================================

INSERT IGNORE INTO `risk_alerts`
  (`asset_id`,`alert_type`,`severity`,`message`,`data`,`acknowledged`,`acknowledged_by`,`acknowledged_at`,`created_at`) VALUES
(12, 'threshold_breach', 'critical', 'HTHA risk score exceeds critical threshold. Asset R-301 requires immediate engineering review per API 941.',
  '{"risk_score": 25.0, "threshold": 20.0, "health_index": 22.5}', 1, @admin_id, '2025-07-02 09:15:00', '2025-07-01 08:05:00'),
(8, 'risk_increase', 'critical', 'Risk score for R-202 Regenerator increased from 16.0 to 20.0 due to updated creep assessment.',
  '{"previous_score": 16.0, "current_score": 20.0, "change_pct": 25.0}', 1, @admin_id, '2025-07-01 14:30:00', '2025-07-01 08:05:00'),
(20, 'accelerating_degradation', 'critical', 'Steam header P-501 corrosion rate accelerating: 0.29 to 0.44 mm/yr (+52%). Remaining life <2 years at CML-P501-PS03.',
  '{"previous_rate": 0.29, "current_rate": 0.44, "remaining_life_years": 1.7}', 0, NULL, NULL, '2025-10-01 08:10:00'),
(1, 'accelerating_degradation', 'warning', 'Atmospheric column T-101 shows accelerating corrosion at tray 15 elevation. Short-term rate 0.30 mm/yr vs long-term 0.31 mm/yr.',
  '{"previous_rate": 0.27, "current_rate": 0.30, "cml": "CML-T101-05"}', 0, NULL, NULL, '2025-12-01 08:15:00'),
(2, 'overdue_inspection', 'warning', 'CUI follow-up UT inspection for E-101 is overdue by 15 days. Scheduled 2025-10-01, not yet completed.',
  '{"task_code": "IT-2025-009", "due_date": "2025-10-31", "days_overdue": 15}', 0, NULL, NULL, '2025-11-15 08:00:00'),
(4, 'risk_increase', 'warning', 'Crude heater H-101 risk increased due to updated naphthenic acid corrosion assessment with higher TAN crude.',
  '{"previous_score": 12.0, "current_score": 16.0, "reason": "Higher TAN crude switch"}', 1, @admin_id, '2024-12-01 10:00:00', '2024-11-15 08:00:00'),
(15, 'anomaly_detected', 'info', 'Tank TK-401 floor pitting distribution shows clustering in NE quadrant — possible localized MIC or water pooling.',
  '{"pit_count": 12, "quadrant": "NE", "max_depth_mm": 5.9}', 1, @admin_id, '2024-10-20 11:30:00', '2024-10-01 08:00:00'),
(13, 'threshold_breach', 'warning', 'HP Separator D-301 H2S content at 8000 ppm exceeds HIC screening threshold of 5000 ppm.',
  '{"h2s_ppm": 8000, "threshold_ppm": 5000}', 0, NULL, NULL, '2025-08-01 08:00:00'),
(7, 'overdue_inspection', 'info', 'FCC Reactor R-201 internal inspection planned for 2026 TA. Advance planning required — 12 months lead time.',
  '{"plan_code": "IP-2025-006", "target_date": "2026-04-01"}', 0, NULL, NULL, '2025-04-01 08:00:00'),
(19, 'anomaly_detected', 'info', 'Boiler B-501 pitting detected near blowdown nozzle. Low severity — monitor water chemistry parameters.',
  '{"location": "Lower drum blowdown nozzle", "pit_depth_mm": 0.8, "severity": "minor"}', 1, @admin_id, '2025-02-15 09:00:00', '2025-02-01 08:00:00');

-- =============================================================================
-- FINANCIAL RISK MODELS (8 records)
-- =============================================================================

INSERT IGNORE INTO `financial_risk_models`
  (`assessment_id`,`model_name`,`model_type`,`annual_risk_cost_usd`,`inspection_cost_usd`,`mitigation_cost_usd`,
   `risk_reduction_value_usd`,`net_benefit_usd`,`return_on_investment_pct`,`optimal_interval_months`,
   `npv_of_risk_usd`,`discount_rate_pct`,`performed_by`) VALUES
(1, 'R-301 HTHA Risk-Cost Model', 'expected_value', 1781250.00, 85000.00, 250000.00, 1187500.00, 852500.00, 254.48, 24, 8500000.00, 8.0000, @admin_id),
(2, 'R-202 Creep Risk-Cost Model', 'expected_value', 607200.00, 60000.00, 150000.00, 380000.00, 170000.00, 80.95, 24, 3200000.00, 8.0000, @admin_id),
(3, 'R-201 Reactor Risk-Cost Model', 'cost_benefit', 321450.00, 55000.00, 120000.00, 214300.00, 39300.00, 22.46, 24, 1800000.00, 8.0000, @admin_id),
(4, 'H-101 Heater Risk-Cost Model', 'expected_value', 252700.00, 45000.00, 80000.00, 168467.00, 43467.00, 34.77, 24, 1500000.00, 8.0000, @admin_id),
(5, 'T-101 Column Risk-Cost Model', 'cost_benefit', 215600.00, 35000.00, 60000.00, 143733.00, 48733.00, 51.30, 36, 1200000.00, 8.0000, @admin_id),
(12, 'TK-401 Tank Risk-Cost Model', 'life_cycle', 111320.00, 80000.00, 200000.00, 74213.00, -205787.00, -73.50, 60, 800000.00, 8.0000, @admin_id),
(13, 'B-501 Boiler Risk-Cost Model', 'expected_value', 29880.00, 25000.00, 40000.00, 19920.00, -45080.00, -69.35, 48, 250000.00, 8.0000, @admin_id),
(8, 'D-301 Separator Risk-Cost Model', 'cost_benefit', 393500.00, 50000.00, 100000.00, 262333.00, 112333.00, 74.89, 30, 2100000.00, 8.0000, @admin_id);

-- =============================================================================
-- RISK RANKINGS
-- =============================================================================

INSERT IGNORE INTO `risk_rankings`
  (`assessment_id`,`asset_id`,`risk_rank`,`inherent_risk_score`,`residual_risk_score`,`risk_reduction_pct`,`risk_acceptable`,`ranking_date`,`notes`) VALUES
(1, 12, 1, 25.00, 16.00, 36.00, 0, '2025-07-01', 'Highest risk — HTHA governing'),
(2, 8, 2, 20.00, 12.00, 40.00, 0, '2025-07-01', 'Extreme temperature creep risk'),
(3, 7, 3, 16.00, 9.00, 43.75, 0, '2025-07-01', 'Catalyst erosion and creep'),
(4, 4, 4, 16.00, 12.00, 25.00, 0, '2025-07-01', 'Heater tube degradation'),
(5, 1, 5, 12.00, 9.00, 25.00, 0, '2025-07-01', 'Accelerating corrosion trend'),
(8, 13, 6, 12.00, 9.00, 25.00, 0, '2025-07-01', 'HIC risk in sour service'),
(6, 2, 7, 9.00, 6.00, 33.33, 1, '2025-07-01', 'Overhead corrosion managed'),
(7, 5, 8, 9.00, 6.00, 33.33, 1, '2025-07-01', 'Naphthenic acid managed'),
(9, 10, 9, 9.00, 4.00, 55.56, 1, '2025-07-01', 'Fractionator in good condition'),
(10, 20, 10, 6.00, 6.00, 0.00, 1, '2025-07-01', 'CUI managed with monitoring'),
(11, 3, 11, 4.00, 4.00, 0.00, 1, '2025-07-01', 'Low risk'),
(12, 15, 12, 6.00, 4.00, 33.33, 1, '2025-07-01', 'Tank managed with CP'),
(13, 19, 13, 6.00, 4.00, 33.33, 1, '2025-07-01', 'Well-maintained boiler'),
(14, 17, 14, 2.00, 2.00, 0.00, 1, '2025-07-01', 'Very low risk'),
(15, 9, 15, 2.00, 1.00, 50.00, 1, '2025-07-01', 'Excellent condition');

SET FOREIGN_KEY_CHECKS = 1;
