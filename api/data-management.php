<?php
/**
 * Data Management API - RBI Engineering Suite
 * Handles sample data loading, clearing, DB status, maintenance, export/import
 *
 * POST /api/data-management.php?action=<action>
 * GET  /api/data-management.php?action=db_status
 * GET  /api/data-management.php?action=export_csv&table=<table_name>
 * GET  /api/data-management.php?action=export_sql
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

// Auth check
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = new Database();
    $pdo = $db->getPdo();

    switch ($action) {

        // =====================================================================
        // LOAD SAMPLE DATA
        // =====================================================================
        case 'load_sample_data':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $summary = [];
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // ── 1. Demo Users ──────────────────────────────────────────────
            $passwordHash = password_hash('demo123', PASSWORD_DEFAULT);
            $users = [
                ['john.engineer', 'john.engineer@petrotech.com', $passwordHash, 'John', 'Anderson', 'Senior Integrity Engineer', 'Mechanical Integrity', '(713) 555-0101', 2, 'active'],
                ['sarah.inspector', 'sarah.inspector@petrotech.com', $passwordHash, 'Sarah', 'Mitchell', 'Lead Inspector API 510/570', 'Inspection Services', '(713) 555-0102', 3, 'active'],
                ['mike.manager', 'mike.manager@petrotech.com', $passwordHash, 'Mike', 'Rodriguez', 'Integrity Manager', 'Engineering Management', '(713) 555-0103', 2, 'active'],
                ['lisa.viewer', 'lisa.viewer@petrotech.com', $passwordHash, 'Lisa', 'Chen', 'Operations Engineer', 'Operations', '(713) 555-0104', 4, 'active'],
            ];
            $userCount = 0;
            $stmtUser = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, job_title, department, phone, role_id, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            foreach ($users as $u) {
                $stmtUser->execute($u);
                $userCount += $stmtUser->rowCount();
            }
            $summary['users'] = $userCount;

            // ── 2. Equipment Hierarchy ─────────────────────────────────────
            $hierarchy = [];

            // Plant level
            $pdo->exec("INSERT IGNORE INTO equipment_hierarchy (id, name, code, level, parent_id, description, status) VALUES
                (100, 'PetroTech Refinery Complex', 'PTR-PLANT', 'plant', NULL, 'PetroTech 150,000 BPD petroleum refinery complex located on the Gulf Coast', 'active')");

            // Units
            $pdo->exec("INSERT IGNORE INTO equipment_hierarchy (id, name, code, level, parent_id, description, status) VALUES
                (101, 'Crude Distillation Unit', 'CDU-100', 'unit', 100, 'Atmospheric and vacuum distillation processing 150,000 BPD crude oil', 'active'),
                (102, 'Fluid Catalytic Cracking Unit', 'FCC-200', 'unit', 100, 'FCC unit converting heavy gas oils to gasoline and lighter products', 'active'),
                (103, 'Hydrotreater Unit', 'HDT-300', 'unit', 100, 'Naphtha and diesel hydrotreating for sulfur removal', 'active'),
                (104, 'Tank Farm', 'TF-400', 'unit', 100, 'Crude and product storage tank farm with 20+ tanks', 'active'),
                (105, 'Utilities', 'UTL-500', 'unit', 100, 'Steam generation, cooling water, instrument air, and power distribution', 'active')");

            // Systems (10 systems across units)
            $pdo->exec("INSERT IGNORE INTO equipment_hierarchy (id, name, code, level, parent_id, description, status) VALUES
                (110, 'Atmospheric Column System', 'CDU-110', 'system', 101, 'Atmospheric distillation column and associated equipment', 'active'),
                (111, 'Vacuum Column System', 'CDU-120', 'system', 101, 'Vacuum distillation column and ejector system', 'active'),
                (112, 'Preheat Train', 'CDU-130', 'system', 101, 'Crude preheat exchanger train', 'active'),
                (120, 'Reactor Section', 'FCC-210', 'system', 102, 'FCC reactor and regenerator system', 'active'),
                (121, 'Main Fractionator', 'FCC-220', 'system', 102, 'FCC main fractionator and overhead system', 'active'),
                (130, 'Reactor Loop', 'HDT-310', 'system', 103, 'Hydrotreater reactor and high-pressure loop', 'active'),
                (131, 'Stripper Section', 'HDT-320', 'system', 103, 'Product stripper and stabilizer system', 'active'),
                (140, 'Crude Storage', 'TF-410', 'system', 104, 'Crude oil storage tanks', 'active'),
                (141, 'Product Storage', 'TF-420', 'system', 104, 'Refined product storage tanks', 'active'),
                (150, 'Steam Generation', 'UTL-510', 'system', 105, 'High and medium pressure steam boilers', 'active')");

            // Equipment items (20)
            $pdo->exec("INSERT IGNORE INTO equipment_hierarchy (id, name, code, level, parent_id, description, status) VALUES
                (1001, 'Atmospheric Column', 'CDU-C-101', 'subsystem', 110, 'Main atmospheric distillation column', 'active'),
                (1002, 'Crude Overhead Condenser', 'CDU-E-101', 'subsystem', 110, 'Overhead fin-fan condenser', 'active'),
                (1003, 'Overhead Accumulator Drum', 'CDU-V-101', 'subsystem', 110, 'Overhead reflux accumulator', 'active'),
                (1004, 'Vacuum Column', 'CDU-C-201', 'subsystem', 111, 'Vacuum distillation column', 'active'),
                (1005, 'Crude/Residue Exchanger', 'CDU-E-102', 'subsystem', 112, 'Hot crude/residue heat exchanger', 'active'),
                (1006, 'Crude Desalter', 'CDU-V-102', 'subsystem', 112, 'Electrostatic desalter vessel', 'active'),
                (1007, 'FCC Reactor', 'FCC-R-201', 'subsystem', 120, 'Fluid catalytic cracking reactor', 'active'),
                (1008, 'FCC Regenerator', 'FCC-R-202', 'subsystem', 120, 'Catalyst regenerator vessel', 'active'),
                (1009, 'Main Fractionator Column', 'FCC-C-201', 'subsystem', 121, 'FCC main fractionator', 'active'),
                (1010, 'Slurry Settler', 'FCC-V-201', 'subsystem', 121, 'Decant oil slurry settler', 'active'),
                (1011, 'HDT Reactor', 'HDT-R-301', 'subsystem', 130, 'Hydrotreater fixed-bed reactor', 'active'),
                (1012, 'HP Separator', 'HDT-V-301', 'subsystem', 130, 'High-pressure hot separator', 'active'),
                (1013, 'Reactor Effluent Exchanger', 'HDT-E-301', 'subsystem', 130, 'Reactor effluent/feed exchanger', 'active'),
                (1014, 'Product Stripper', 'HDT-C-301', 'subsystem', 131, 'Naphtha product stripper column', 'active'),
                (1015, 'Crude Tank TK-401', 'TF-TK-401', 'subsystem', 140, 'Crude oil floating roof storage tank - 500,000 bbl', 'active'),
                (1016, 'Crude Tank TK-402', 'TF-TK-402', 'subsystem', 140, 'Crude oil floating roof storage tank - 500,000 bbl', 'active'),
                (1017, 'Gasoline Tank TK-501', 'TF-TK-501', 'subsystem', 141, 'Gasoline fixed roof storage tank - 200,000 bbl', 'active'),
                (1018, 'Diesel Tank TK-502', 'TF-TK-502', 'subsystem', 141, 'Diesel floating roof storage tank - 300,000 bbl', 'active'),
                (1019, 'HP Steam Boiler', 'UTL-B-501', 'subsystem', 150, 'High pressure steam boiler - 600 psig', 'active'),
                (1020, 'BFW Deaerator', 'UTL-V-501', 'subsystem', 150, 'Boiler feedwater deaerator vessel', 'active')");

            $summary['hierarchy'] = 36;

            // ── 3. Asset Registry (20 assets) ──────────────────────────────
            $pdo->exec("INSERT IGNORE INTO asset_registry (id, asset_tag, asset_name, hierarchy_id, asset_type, asset_subtype, manufacturer, year_manufactured, installation_date, commission_date, design_code, status, criticality, rbi_status) VALUES
                (1, 'CDU-C-101', 'Atmospheric Distillation Column', 1001, 'column', 'Trayed Column', 'CB&I', 2005, '2005-06-15', '2005-09-01', 'ASME VIII Div 1', 'in_service', 'critical', 'assessed'),
                (2, 'CDU-E-101', 'Crude Overhead Condenser', 1002, 'heat_exchanger', 'Air Cooled Exchanger', 'Hamon', 2005, '2005-06-15', '2005-09-01', 'ASME VIII Div 1', 'in_service', 'high', 'assessed'),
                (3, 'CDU-V-101', 'Overhead Accumulator Drum', 1003, 'pressure_vessel', 'Horizontal Drum', 'Hanover Fabrication', 2005, '2005-05-20', '2005-09-01', 'ASME VIII Div 1', 'in_service', 'high', 'assessed'),
                (4, 'CDU-C-201', 'Vacuum Distillation Column', 1004, 'column', 'Packed Column', 'CB&I', 2005, '2005-06-15', '2005-09-01', 'ASME VIII Div 1', 'in_service', 'critical', 'assessed'),
                (5, 'CDU-E-102', 'Crude/Residue Heat Exchanger', 1005, 'heat_exchanger', 'Shell & Tube', 'Alfa Laval', 2005, '2005-05-10', '2005-09-01', 'ASME VIII Div 1', 'in_service', 'medium', 'assessed'),
                (6, 'CDU-V-102', 'Crude Desalter Vessel', 1006, 'pressure_vessel', 'Horizontal Vessel', 'NATCO Group', 2005, '2005-05-10', '2005-09-01', 'ASME VIII Div 1', 'in_service', 'high', 'assessed'),
                (7, 'FCC-R-201', 'FCC Reactor', 1007, 'reactor', 'Fluidized Bed Reactor', 'UOP/Honeywell', 2008, '2008-03-15', '2008-06-01', 'ASME VIII Div 2', 'in_service', 'critical', 'assessed'),
                (8, 'FCC-R-202', 'FCC Regenerator', 1008, 'reactor', 'Fluidized Bed Vessel', 'UOP/Honeywell', 2008, '2008-03-15', '2008-06-01', 'ASME VIII Div 2', 'in_service', 'critical', 'assessed'),
                (9, 'FCC-C-201', 'FCC Main Fractionator', 1009, 'column', 'Trayed Column', 'CB&I', 2008, '2008-04-01', '2008-06-01', 'ASME VIII Div 1', 'in_service', 'high', 'assessed'),
                (10, 'FCC-V-201', 'Slurry Settler Vessel', 1010, 'pressure_vessel', 'Vertical Vessel', 'Hanover Fabrication', 2008, '2008-04-01', '2008-06-01', 'ASME VIII Div 1', 'in_service', 'medium', 'assessed'),
                (11, 'HDT-R-301', 'Hydrotreater Reactor', 1011, 'reactor', 'Fixed Bed Reactor', 'IHI Corporation', 2010, '2010-01-20', '2010-04-01', 'ASME VIII Div 2', 'in_service', 'critical', 'assessed'),
                (12, 'HDT-V-301', 'HP Hot Separator', 1012, 'pressure_vessel', 'Vertical Vessel', 'Hanover Fabrication', 2010, '2010-01-20', '2010-04-01', 'ASME VIII Div 1', 'in_service', 'high', 'assessed'),
                (13, 'HDT-E-301', 'Reactor Effluent Exchanger', 1013, 'heat_exchanger', 'Shell & Tube', 'Alfa Laval', 2010, '2010-02-10', '2010-04-01', 'ASME VIII Div 1', 'in_service', 'high', 'assessed'),
                (14, 'HDT-C-301', 'Product Stripper Column', 1014, 'column', 'Trayed Column', 'Koch-Glitsch', 2010, '2010-02-10', '2010-04-01', 'ASME VIII Div 1', 'in_service', 'medium', 'assessed'),
                (15, 'TF-TK-401', 'Crude Oil Storage Tank A', 1015, 'storage_tank', 'External Floating Roof', 'Matrix Service Co', 2003, '2003-08-15', '2003-11-01', 'API 650', 'in_service', 'high', 'assessed'),
                (16, 'TF-TK-402', 'Crude Oil Storage Tank B', 1016, 'storage_tank', 'External Floating Roof', 'Matrix Service Co', 2003, '2003-08-15', '2003-11-01', 'API 650', 'in_service', 'high', 'assessed'),
                (17, 'TF-TK-501', 'Gasoline Storage Tank', 1017, 'storage_tank', 'Internal Floating Roof', 'Tank Industry Consultants', 2006, '2006-04-10', '2006-07-01', 'API 650', 'in_service', 'high', 'assessed'),
                (18, 'TF-TK-502', 'Diesel Storage Tank', 1018, 'storage_tank', 'External Floating Roof', 'Matrix Service Co', 2006, '2006-04-10', '2006-07-01', 'API 650', 'in_service', 'medium', 'assessed'),
                (19, 'UTL-B-501', 'HP Steam Boiler', 1019, 'boiler', 'Water Tube Boiler', 'Babcock & Wilcox', 2004, '2004-11-01', '2005-02-01', 'ASME I', 'in_service', 'critical', 'assessed'),
                (20, 'UTL-V-501', 'BFW Deaerator', 1020, 'pressure_vessel', 'Horizontal Vessel', 'Kansas City Deaerator', 2004, '2004-11-01', '2005-02-01', 'ASME VIII Div 1', 'in_service', 'medium', 'assessed')");
            $summary['assets'] = 20;

            // ── 4. Design Data ─────────────────────────────────────────────
            $pdo->exec("INSERT IGNORE INTO design_data (asset_id, material_spec, material_grade, nominal_thickness_mm, corrosion_allowance_mm, minimum_required_thickness_mm, design_pressure_mpa, design_temperature_c, mawp_mpa, joint_efficiency, diameter_mm, length_mm) VALUES
                (1,  'SA-516 Gr 70',  'Gr 70',  38.0, 6.0, 28.5, 0.69, 400, 0.62, 0.850, 4500, 45000),
                (2,  'SA-214',        NULL,      12.7, 3.0, 8.5,  0.35, 150, 0.30, 1.000, 1200, 12000),
                (3,  'SA-516 Gr 70',  'Gr 70',  25.4, 3.0, 18.2, 1.38, 180, 1.25, 0.850, 2400, 6000),
                (4,  'SA-240 Tp 410S','Tp 410S', 32.0, 3.0, 22.0, 0.10, 420, 0.08, 0.850, 5500, 42000),
                (5,  'SA-516 Gr 60',  'Gr 60',  19.0, 3.0, 12.5, 2.76, 370, 2.50, 0.850, 900,  6000),
                (6,  'SA-516 Gr 70',  'Gr 70',  28.0, 3.0, 19.5, 1.03, 150, 0.95, 0.850, 3000, 12000),
                (7,  'SA-387 Gr 11',  'Gr 11 Cl2', 50.0, 6.0, 38.0, 2.76, 550, 2.50, 0.850, 4000, 22000),
                (8,  'SA-387 Gr 11',  'Gr 11 Cl2', 45.0, 6.0, 34.0, 0.34, 730, 0.30, 0.850, 6000, 28000),
                (9,  'SA-516 Gr 70',  'Gr 70',  35.0, 6.0, 25.0, 0.34, 400, 0.30, 0.850, 4200, 38000),
                (10, 'SA-516 Gr 70',  'Gr 70',  22.0, 3.0, 15.0, 0.69, 350, 0.62, 0.850, 2000, 5000),
                (11, 'SA-336 F22',    'F22',    88.0, 6.0, 72.0, 15.50, 430, 14.0, 1.000, 2800, 18000),
                (12, 'SA-387 Gr 22',  'Gr 22 Cl2', 45.0, 6.0, 32.0, 15.50, 430, 14.0, 0.850, 1800, 5000),
                (13, 'SA-387 Gr 11',  'Gr 11 Cl2', 25.0, 3.0, 18.0, 15.50, 400, 14.0, 0.850, 800, 5000),
                (14, 'SA-516 Gr 70',  'Gr 70',  28.0, 3.0, 20.0, 0.69, 280, 0.62, 0.850, 2500, 18000),
                (15, 'A283 Gr C',     'Gr C',   12.7, 3.0, 8.0,  0.00, 90,  0.00, 0.850, 60000, 14600),
                (16, 'A283 Gr C',     'Gr C',   12.7, 3.0, 8.0,  0.00, 90,  0.00, 0.850, 60000, 14600),
                (17, 'A283 Gr C',     'Gr C',   10.0, 3.0, 6.0,  0.00, 60,  0.00, 0.850, 40000, 14600),
                (18, 'A283 Gr C',     'Gr C',   12.7, 3.0, 8.0,  0.00, 90,  0.00, 0.850, 50000, 14600),
                (19, 'SA-178 Gr A',   'Gr A',   8.0,  1.5, 5.5,  4.14, 290, 3.80, 1.000, 2000, 10000),
                (20, 'SA-516 Gr 70',  'Gr 70',  16.0, 3.0, 10.0, 0.10, 175, 0.08, 0.850, 2200, 8000)");

            // ── 5. Operational Data ────────────────────────────────────────
            $pdo->exec("INSERT IGNORE INTO operational_data (asset_id, operating_pressure_mpa, operating_temperature_c, fluid_service, fluid_phase, h2s_content_ppm, chloride_content_ppm, effective_date) VALUES
                (1,  0.55, 365, 'Crude oil / mixed hydrocarbons', 'two_phase', 500, 50, '2024-01-01'),
                (2,  0.28, 120, 'Naphtha / light ends with HCl', 'two_phase', 200, 350, '2024-01-01'),
                (3,  1.10, 65,  'Naphtha with sour water', 'two_phase', 1500, 200, '2024-01-01'),
                (4,  0.007, 395, 'Vacuum gas oil / vacuum residue', 'two_phase', 100, 20, '2024-01-01'),
                (5,  2.40, 345, 'Crude oil / atmospheric residue', 'liquid', 400, 40, '2024-01-01'),
                (6,  0.90, 135, 'Crude oil with brine', 'two_phase', 800, 15000, '2024-01-01'),
                (7,  2.40, 525, 'Hydrocarbon vapor / catalyst', 'gas', 50, 5, '2024-01-01'),
                (8,  0.28, 700, 'Air / flue gas / catalyst', 'gas', 5, 2, '2024-01-01'),
                (9,  0.25, 370, 'Mixed hydrocarbons', 'two_phase', 100, 30, '2024-01-01'),
                (10, 0.55, 320, 'Slurry oil / catalyst fines', 'liquid', 50, 15, '2024-01-01'),
                (11, 13.80, 395, 'Naphtha / hydrogen / H2S', 'two_phase', 25000, 5, '2024-01-01'),
                (12, 13.80, 395, 'Mixed HC / H2 / H2S', 'two_phase', 20000, 5, '2024-01-01'),
                (13, 13.80, 370, 'Reactor effluent / H2', 'two_phase', 18000, 5, '2024-01-01'),
                (14, 0.55, 250, 'Naphtha / light hydrocarbons', 'two_phase', 500, 10, '2024-01-01'),
                (15, 0.00, 50,  'Crude oil (sour)', 'liquid', 3000, 100, '2024-01-01'),
                (16, 0.00, 50,  'Crude oil (sour)', 'liquid', 3000, 100, '2024-01-01'),
                (17, 0.00, 30,  'Gasoline', 'liquid', 5, 2, '2024-01-01'),
                (18, 0.00, 40,  'Diesel fuel', 'liquid', 50, 10, '2024-01-01'),
                (19, 3.80, 260, 'BFW / steam', 'two_phase', 0, 5, '2024-01-01'),
                (20, 0.07, 105, 'Boiler feedwater', 'liquid', 0, 2, '2024-01-01')");

            // ── 6. Corrosion Circuits (8) ──────────────────────────────────
            $pdo->exec("INSERT IGNORE INTO corrosion_circuits (id, circuit_name, circuit_code, hierarchy_id, description, process_fluid, material_spec, operating_temperature_range, operating_pressure_range, expected_damage_mechanisms, corrosion_rate_mm_yr, corrosion_rate_basis, status) VALUES
                (1, 'CDU Overhead Circuit', 'CC-CDU-OH', 110, 'Crude unit overhead system from column top through condensers to accumulator drum', 'Naphtha with HCl and H2S', 'CS / SA-516 Gr 70', '60-180 C', '0.3-1.4 MPa', 'HCl corrosion, ammonium chloride, wet H2S', 0.25, 'measured', 'active'),
                (2, 'CDU Atmospheric Tower Shell', 'CC-CDU-AT', 110, 'Atmospheric column shell and internals', 'Crude oil fractions', 'CS / SA-516 Gr 70', '120-380 C', '0.1-0.7 MPa', 'Naphthenic acid, sulfidation, erosion', 0.15, 'measured', 'active'),
                (3, 'CDU Preheat / Hot Circuit', 'CC-CDU-HT', 112, 'Hot crude preheat train and desalter circuit', 'Crude oil with brine', 'CS / SA-516', '130-370 C', '0.9-2.8 MPa', 'Sulfidation, HCl dew point, under-deposit', 0.20, 'measured', 'active'),
                (4, 'FCC Reactor System', 'CC-FCC-RX', 120, 'FCC reactor and regenerator shells and internals', 'HC vapor / catalyst / flue gas', 'Cr-Mo / SA-387 Gr 11', '500-730 C', '0.2-2.8 MPa', 'High-temp oxidation, erosion, creep, carburization', 0.08, 'measured', 'active'),
                (5, 'FCC Fractionator Circuit', 'CC-FCC-FR', 121, 'Main fractionator and overhead system', 'Mixed HC with H2S', 'CS / SA-516 Gr 70', '65-370 C', '0.1-0.7 MPa', 'Sulfidation, wet H2S, ammonium bisulfide', 0.18, 'measured', 'active'),
                (6, 'HDT High-Pressure Loop', 'CC-HDT-HP', 130, 'Hydrotreater reactor, separators, and HP exchangers', 'H2/HC/H2S at high pressure', 'Cr-Mo / SA-387 Gr 22', '200-430 C', '13-16 MPa', 'HTHA, hydrogen embrittlement, H2S/H2 cracking', 0.05, 'measured', 'active'),
                (7, 'Tank Farm Crude Circuits', 'CC-TF-CR', 140, 'Crude storage tanks bottom and shell courses', 'Sour crude oil', 'CS / A283 Gr C', '20-60 C', 'Atmospheric', 'Bottom-side corrosion, soil-side, MIC, CUI', 0.30, 'estimated', 'active'),
                (8, 'Utilities Steam Circuit', 'CC-UTL-ST', 150, 'HP steam boiler and BFW system', 'BFW / steam', 'CS / SA-178, SA-516', '100-290 C', '0.1-4.2 MPa', 'Oxygen pitting, caustic gouging, FAC, stress corrosion', 0.10, 'measured', 'active')");
            $summary['corrosion_circuits'] = 8;

            // ── 7. Corrosion Circuit Asset Assignments ──────────────────────
            $pdo->exec("INSERT IGNORE INTO corrosion_circuit_assets (circuit_id, asset_id, cml_reference, position_description) VALUES
                (1, 2, 'CML-E101-01', 'Overhead condenser tubes and header'),
                (1, 3, 'CML-V101-01', 'Accumulator drum shell and internals'),
                (2, 1, 'CML-C101-01', 'Atmospheric column shell courses 1-20'),
                (3, 5, 'CML-E102-01', 'Shell side hot crude service'),
                (3, 6, 'CML-V102-01', 'Desalter vessel shell'),
                (4, 7, 'CML-R201-01', 'Reactor shell and cyclone area'),
                (4, 8, 'CML-R202-01', 'Regenerator shell and grid area'),
                (5, 9, 'CML-C201-01', 'Main fractionator shell'),
                (5, 10, 'CML-V201-01', 'Slurry settler vessel'),
                (6, 11, 'CML-R301-01', 'HDT reactor shell and nozzles'),
                (6, 12, 'CML-V301-01', 'HP separator shell'),
                (6, 13, 'CML-E301-01', 'Effluent exchanger tubes'),
                (7, 15, 'CML-TK401-01', 'Tank bottom and first shell course'),
                (7, 16, 'CML-TK402-01', 'Tank bottom and first shell course'),
                (7, 17, 'CML-TK501-01', 'Tank bottom'),
                (7, 18, 'CML-TK502-01', 'Tank bottom and first shell course'),
                (8, 19, 'CML-B501-01', 'Boiler tubes and drum'),
                (8, 20, 'CML-V501-01', 'Deaerator shell and trays')");

            // ── 8. Damage Mechanism Assignments (30) ───────────────────────
            // First ensure damage_mechanisms library has entries; use existing IDs if available, otherwise insert
            $dmCheck = $pdo->query("SELECT COUNT(*) FROM damage_mechanisms")->fetchColumn();
            if ($dmCheck == 0) {
                $pdo->exec("INSERT IGNORE INTO damage_mechanisms (id, dm_code, dm_name, category, api_571_reference, description, typical_materials_affected, temperature_range_min_c, temperature_range_max_c, default_susceptibility) VALUES
                    (1, 'SULPH', 'Sulfidation (High-Temperature H2S)', 'high_temperature', '5.1.2.3', 'High-temperature sulfur corrosion in hydrocarbon streams containing H2S or sulfur compounds', 'Carbon steel, low-alloy steels', 230, 600, 'medium'),
                    (2, 'NAC', 'Naphthenic Acid Corrosion', 'high_temperature', '5.1.2.4', 'Corrosion from naphthenic acids in crude oil at elevated temperatures', 'Carbon steel, 5Cr, 9Cr steels', 220, 400, 'medium'),
                    (3, 'HCL-DWP', 'HCl Dew Point Corrosion', 'localised_corrosion', '5.1.1.6', 'Severe corrosion at the HCl dew point in crude unit overheads', 'Carbon steel', 100, 200, 'high'),
                    (4, 'NH4CL', 'Ammonium Chloride Corrosion', 'localised_corrosion', '5.1.1.4', 'Under-deposit corrosion from ammonium chloride salt deposition', 'Carbon steel, alloy steels', 100, 250, 'medium'),
                    (5, 'NH4HS', 'Ammonium Bisulfide Corrosion', 'localised_corrosion', '5.1.1.5', 'Erosion-corrosion in alkaline sour water systems', 'Carbon steel', 20, 150, 'medium'),
                    (6, 'WET-H2S', 'Wet H2S Damage (Blistering/HIC/SOHIC)', 'hydrogen_damage', '5.1.3.2', 'Hydrogen-induced cracking in wet H2S service', 'Carbon steel, low-alloy steels', 0, 150, 'high'),
                    (7, 'CUI', 'Corrosion Under Insulation', 'external', '5.1.1.10', 'External corrosion of insulated carbon steel equipment', 'Carbon steel', -12, 175, 'high'),
                    (8, 'HTHA', 'High Temperature Hydrogen Attack', 'hydrogen_damage', '5.1.3.1', 'Irreversible damage from hydrogen at high temperature and pressure per Nelson curves', '0.5Mo, 1Cr-0.5Mo, 1.25Cr-0.5Mo, 2.25Cr-1Mo', 200, 600, 'medium'),
                    (9, 'SSC', 'Sulfide Stress Cracking', 'stress_corrosion_cracking', '5.1.3.3', 'Cracking of hard or high-strength steels in wet H2S per NACE MR0175', 'Carbon steel >HRC 22, high-strength bolting', 0, 80, 'medium'),
                    (10, 'CL-SCC', 'Chloride Stress Corrosion Cracking', 'stress_corrosion_cracking', '5.1.3.4', 'SCC of austenitic stainless steels in chloride environments', 'Type 304, 316 SS', 50, 200, 'high'),
                    (11, 'CAUSTIC-SCC', 'Caustic Stress Corrosion Cracking', 'stress_corrosion_cracking', '5.1.3.5', 'Cracking in caustic (NaOH) service', 'Carbon steel', 50, 200, 'medium'),
                    (12, 'CREEP', 'High-Temperature Creep', 'high_temperature', '5.1.2.1', 'Time-dependent deformation at elevated temperatures', 'Carbon steel >425C, Cr-Mo steels', 400, 900, 'medium'),
                    (13, 'OXD', 'High-Temperature Oxidation', 'high_temperature', '5.1.2.2', 'Scaling and metal loss from oxidation in air/flue gas', 'Carbon steel, low-alloy steels', 480, 900, 'low'),
                    (14, 'ERO-C', 'Erosion-Corrosion', 'erosion', '5.1.4.2', 'Combined erosion and corrosion from turbulent flow with corrosive media', 'Carbon steel', 0, 500, 'medium'),
                    (15, 'FAC', 'Flow-Accelerated Corrosion', 'erosion', '5.1.4.3', 'Wall thinning in single and two-phase flow systems', 'Carbon steel', 100, 280, 'medium'),
                    (16, 'SOIL-CORR', 'Soil-Side Corrosion', 'external', '5.1.1.11', 'External corrosion of buried or soil-contacting surfaces', 'Carbon steel', 0, 60, 'medium'),
                    (17, 'MIC', 'Microbiologically Influenced Corrosion', 'localised_corrosion', '5.1.1.12', 'Corrosion enhanced by microbial activity', 'Carbon steel', 10, 80, 'medium'),
                    (18, 'O2-PIT', 'Oxygen Pitting', 'localised_corrosion', '5.1.1.8', 'Pitting from dissolved oxygen in water systems', 'Carbon steel', 20, 200, 'medium'),
                    (19, 'CAUSTIC-GOUG', 'Caustic Gouging', 'localised_corrosion', '5.1.1.9', 'Localized corrosion under deposits in boiler systems due to caustic concentration', 'Carbon steel', 100, 350, 'medium'),
                    (20, 'BRIT-FRAC', 'Brittle Fracture', 'metallurgical', '5.1.5.1', 'Sudden fracture due to operation below the MDMT', 'Carbon steel, killed/semi-killed', -50, 20, 'low')");
            }

            $pdo->exec("INSERT IGNORE INTO damage_mechanism_assignments (asset_id, damage_mechanism_id, susceptibility, severity, is_active_threat, basis) VALUES
                (1, 1, 'high', 'major', 1, 'Operating at 365C in sour crude service - above 230C sulfidation threshold per API 571'),
                (1, 2, 'medium', 'moderate', 1, 'TAN levels of 0.5-1.5 mg KOH/g in crude blend - moderate naphthenic acid risk'),
                (1, 7, 'high', 'moderate', 1, 'Carbon steel column with external insulation operating at 120-365C - prime CUI range'),
                (2, 3, 'high', 'major', 1, 'CDU overhead condenser at HCl dew point transition zone - highest severity location'),
                (2, 4, 'high', 'major', 1, 'Ammonium chloride deposition in overhead condenser - salt point above wash water injection'),
                (3, 6, 'high', 'critical', 1, 'Overhead accumulator in wet H2S service (1500 ppm H2S, sour water) - HIC/SOHIC susceptible'),
                (3, 5, 'medium', 'moderate', 1, 'Aqueous NH4HS in accumulator boot water - concentration exceeds 2 wt%'),
                (4, 1, 'medium', 'moderate', 1, 'Vacuum column operating at 395C in sulfur-bearing VGO - moderate sulfidation'),
                (5, 1, 'high', 'major', 1, 'Hot crude/residue exchanger at 345C - significant sulfidation corrosion measured'),
                (6, 3, 'medium', 'moderate', 1, 'Desalter operates at crude HCl dew point conditions with brine'),
                (7, 12, 'high', 'critical', 1, 'FCC reactor at 525C - above 425C creep threshold for Cr-Mo steel'),
                (7, 14, 'high', 'major', 1, 'Catalyst erosion of reactor internals and cyclone system'),
                (8, 13, 'high', 'major', 1, 'Regenerator at 700C in oxidizing flue gas - significant high-temp oxidation'),
                (8, 12, 'high', 'critical', 1, 'Regenerator at 700C exceeds creep threshold significantly'),
                (9, 1, 'medium', 'moderate', 1, 'Main fractionator in sulfidic HC service at 370C'),
                (9, 14, 'medium', 'moderate', 1, 'Erosion from catalyst fines carryover into fractionator'),
                (10, 1, 'low', 'minor', 1, 'Slurry settler at 320C with low sulfur species concentration'),
                (11, 8, 'high', 'critical', 1, 'HDT reactor at 395C with 13.8 MPa H2 - above Nelson curve limits for 2.25Cr-1Mo'),
                (11, 9, 'medium', 'major', 1, 'High-pressure wet H2S service - SSC risk on reactor nozzle welds and bolting'),
                (12, 8, 'high', 'critical', 1, 'HP separator at 395C, 13.8 MPa H2 partial pressure - HTHA susceptible per API 941'),
                (12, 6, 'medium', 'moderate', 1, 'Wet H2S condensation possible during shutdowns - HIC in HAZ regions'),
                (13, 8, 'medium', 'major', 1, 'Reactor effluent exchanger in H2/H2S at 370C and high pressure'),
                (14, 6, 'medium', 'moderate', 1, 'Product stripper boot in sour water - wet H2S damage possible'),
                (15, 16, 'high', 'major', 1, 'Tank bottom directly on soil pad - no liner, limited CP effectiveness'),
                (15, 17, 'medium', 'moderate', 1, 'Stagnant sour crude promotes SRB and MIC at tank bottom'),
                (16, 16, 'high', 'major', 1, 'Tank bottom on soil pad - same exposure as TK-401'),
                (17, 7, 'medium', 'moderate', 1, 'Insulated tank roof operating near ambient - CUI in tropical climate'),
                (18, 16, 'medium', 'moderate', 1, 'Diesel tank bottom on soil pad with partial CP'),
                (19, 18, 'high', 'major', 1, 'Boiler water system susceptible to oxygen pitting during transients'),
                (19, 19, 'medium', 'major', 1, 'Caustic gouging risk under deposits at high heat flux areas')");
            $summary['damage_mechanism_assignments'] = 30;

            // ── 9. Risk Assessments (15) ───────────────────────────────────
            // First check for default risk matrix
            $matrixId = $pdo->query("SELECT id FROM risk_matrices WHERE is_default = 1 LIMIT 1")->fetchColumn();
            if (!$matrixId) {
                $pdo->exec("INSERT IGNORE INTO risk_matrices (id, matrix_name, description, num_rows, num_cols, is_default, is_active) VALUES (1, 'API 581 5x5 Risk Matrix', 'Standard 5x5 risk matrix per API 581 methodology', 5, 5, 1, 1)");
                $matrixId = 1;
            }

            $pdo->exec("INSERT IGNORE INTO risk_assessments (id, assessment_code, asset_id, matrix_id, assessment_type, methodology, assessment_date, status, inherent_pof_category, inherent_cof_category, inherent_risk_level, inherent_risk_score, residual_pof_category, residual_cof_category, residual_risk_level, residual_risk_score, overall_confidence) VALUES
                (1,  'RA-CDU-C101-2025', 1,  {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-03-15', 'approved', 4, 5, 'high',        20.0, 3, 4, 'medium_high', 12.0, 'high'),
                (2,  'RA-CDU-E101-2025', 2,  {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-03-15', 'approved', 5, 4, 'very_high',    20.0, 4, 3, 'high',        12.0, 'high'),
                (3,  'RA-CDU-V101-2025', 3,  {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-03-16', 'approved', 4, 4, 'high',         16.0, 3, 3, 'medium',       9.0, 'high'),
                (4,  'RA-CDU-C201-2025', 4,  {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-04-01', 'approved', 3, 5, 'medium_high',  15.0, 2, 4, 'medium',       8.0, 'medium'),
                (5,  'RA-FCC-R201-2025', 7,  {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-04-10', 'approved', 4, 5, 'very_high',    20.0, 3, 5, 'high',        15.0, 'high'),
                (6,  'RA-FCC-R202-2025', 8,  {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-04-10', 'approved', 4, 5, 'very_high',    20.0, 3, 5, 'high',        15.0, 'high'),
                (7,  'RA-FCC-C201-2025', 9,  {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-04-15', 'approved', 3, 4, 'medium_high',  12.0, 2, 3, 'medium',       6.0, 'medium'),
                (8,  'RA-HDT-R301-2025', 11, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-05-01', 'approved', 5, 5, 'very_high',    25.0, 4, 5, 'very_high',   20.0, 'high'),
                (9,  'RA-HDT-V301-2025', 12, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-05-01', 'approved', 4, 4, 'high',         16.0, 3, 4, 'medium_high', 12.0, 'high'),
                (10, 'RA-HDT-E301-2025', 13, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-05-02', 'approved', 3, 4, 'medium_high',  12.0, 2, 3, 'medium',       6.0, 'medium'),
                (11, 'RA-TF-TK401-2025', 15, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-06-01', 'approved', 4, 4, 'high',         16.0, 3, 3, 'medium',       9.0, 'medium'),
                (12, 'RA-TF-TK402-2025', 16, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-06-01', 'approved', 4, 4, 'high',         16.0, 3, 3, 'medium',       9.0, 'medium'),
                (13, 'RA-TF-TK501-2025', 17, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-06-10', 'approved', 2, 3, 'medium',        6.0, 1, 2, 'low',          2.0, 'medium'),
                (14, 'RA-UTL-B501-2025', 19, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-07-01', 'approved', 3, 5, 'high',         15.0, 2, 4, 'medium',       8.0, 'high'),
                (15, 'RA-UTL-V501-2025', 20, {$matrixId}, 'semi_quantitative', 'api_581_level1', '2025-07-01', 'approved', 2, 2, 'low',           4.0, 1, 2, 'low',          2.0, 'medium')");
            $summary['risk_assessments'] = 15;

            // ── 10. Inspection Plans (10) ──────────────────────────────────
            $pdo->exec("INSERT IGNORE INTO inspection_plans (id, plan_code, asset_id, assessment_id, plan_name, description, status, priority, plan_start_date, plan_end_date) VALUES
                (1, 'IP-CDU-C101-2026', 1, 1,  'CDU Atmospheric Column Inspection Plan', 'Comprehensive internal inspection during T/A 2026 focusing on sulfidation and CUI', 'active', 'critical', '2026-01-01', '2026-12-31'),
                (2, 'IP-CDU-E101-2026', 2, 2,  'CDU Overhead Condenser Inspection', 'Condenser tube inspection for HCl dew point and NH4Cl under-deposit corrosion', 'active', 'critical', '2026-01-01', '2026-06-30'),
                (3, 'IP-CDU-V101-2026', 3, 3,  'Overhead Accumulator Drum Inspection', 'Wet H2S damage assessment - HIC/SOHIC scanning of drum shell', 'active', 'high', '2026-01-01', '2026-12-31'),
                (4, 'IP-FCC-R201-2026', 7, 5,  'FCC Reactor Internal Inspection', 'Creep damage and erosion assessment during catalyst changeout', 'active', 'critical', '2026-03-01', '2026-05-31'),
                (5, 'IP-FCC-R202-2026', 8, 6,  'FCC Regenerator Inspection', 'High-temp oxidation and creep assessment', 'active', 'critical', '2026-03-01', '2026-05-31'),
                (6, 'IP-HDT-R301-2026', 11, 8, 'HDT Reactor HTHA Inspection', 'HTHA screening using AUBT and replica metallography', 'active', 'critical', '2026-01-01', '2026-12-31'),
                (7, 'IP-HDT-V301-2026', 12, 9, 'HP Separator Inspection', 'Wet H2S and HTHA assessment', 'active', 'high', '2026-01-01', '2026-12-31'),
                (8, 'IP-TK401-2026',    15, 11, 'Crude Tank A Floor Inspection', 'API 653 floor scan and shell UT', 'active', 'high', '2026-04-01', '2026-09-30'),
                (9, 'IP-UTL-B501-2026', 19, 14, 'HP Boiler Inspection', 'Tube thickness survey and waterside inspection', 'active', 'high', '2026-01-01', '2026-12-31'),
                (10, 'IP-FCC-C201-2026', 9, 7, 'FCC Fractionator Inspection', 'Internal tray inspection and shell UT survey', 'active', 'medium', '2026-06-01', '2026-12-31')");
            $summary['inspection_plans'] = 10;

            // ── 11. Inspection Tasks (20) ──────────────────────────────────
            $pdo->exec("INSERT IGNORE INTO inspection_tasks (id, task_code, plan_id, task_name, inspection_method, inspection_effectiveness, coverage_pct, scope_description, status, priority, scheduled_date, due_date, estimated_hours) VALUES
                (1,  'IT-001', 1, 'CDU Column UT Thickness Survey', 'ut_thickness', 'usually_effective', 80.00, 'UT grid mapping of all 20 shell courses - focus on sulfidation zones above tray 15', 'scheduled', 'critical', '2026-04-15', '2026-05-15', 40.0),
                (2,  'IT-002', 1, 'CDU Column Internal Visual', 'internal_visual', 'usually_effective', 90.00, 'Visual inspection of column internals, trays, downcomers, and distributors', 'scheduled', 'critical', '2026-04-15', '2026-05-15', 24.0),
                (3,  'IT-003', 1, 'CDU Column CUI Survey', 'ut_thickness', 'fairly_effective', 30.00, 'Targeted CUI survey at insulation damage locations, nozzle penetrations, and supports', 'planned', 'high', '2026-06-01', '2026-07-31', 16.0),
                (4,  'IT-004', 2, 'Overhead Condenser Tube Inspection', 'eddy_current', 'usually_effective', 100.00, 'Full IRIS/ECT of all condenser tubes for HCl and NH4Cl pitting', 'scheduled', 'critical', '2026-03-15', '2026-04-15', 32.0),
                (5,  'IT-005', 2, 'Condenser Header Box Visual', 'internal_visual', 'fairly_effective', 95.00, 'Visual assessment of header box and tube sheet for corrosion', 'planned', 'high', '2026-03-15', '2026-04-15', 8.0),
                (6,  'IT-006', 3, 'Accumulator Drum WFMT', 'magnetic_particle', 'usually_effective', 70.00, 'Wet fluorescent magnetic particle of drum welds for HIC/SOHIC', 'planned', 'high', '2026-04-15', '2026-06-30', 16.0),
                (7,  'IT-007', 3, 'Accumulator Drum UT Shear Wave', 'ut_shear_wave', 'highly_effective', 60.00, 'UT shear wave scanning of shell-to-head welds for SOHIC/HIC', 'planned', 'high', '2026-04-15', '2026-06-30', 24.0),
                (8,  'IT-008', 4, 'FCC Reactor Replication', 'other', 'usually_effective', 20.00, 'In-situ metallographic replication of reactor shell welds for creep voids', 'scheduled', 'critical', '2026-04-01', '2026-04-30', 20.0),
                (9,  'IT-009', 4, 'FCC Reactor PAUT', 'ut_phased_array', 'highly_effective', 85.00, 'Phased array UT of reactor shell and nozzle welds', 'scheduled', 'critical', '2026-04-01', '2026-04-30', 32.0),
                (10, 'IT-010', 5, 'FCC Regen Thickness Survey', 'ut_thickness', 'usually_effective', 75.00, 'UT thickness survey of regenerator shell and dome - high-temp oxidation assessment', 'scheduled', 'critical', '2026-04-01', '2026-04-30', 24.0),
                (11, 'IT-011', 6, 'HDT Reactor AUBT Screening', 'ut_phased_array', 'highly_effective', 90.00, 'Advanced ultrasonic backscatter testing for HTHA per API RP 941', 'planned', 'critical', '2026-09-01', '2026-10-31', 48.0),
                (12, 'IT-012', 6, 'HDT Reactor Nozzle TOFD', 'ut_tofd', 'highly_effective', 95.00, 'TOFD scanning of all reactor nozzle-to-shell welds for hydrogen cracking', 'planned', 'critical', '2026-09-01', '2026-10-31', 32.0),
                (13, 'IT-013', 7, 'HP Separator UT Survey', 'ut_thickness', 'usually_effective', 80.00, 'UT thickness grid of separator shell and heads', 'planned', 'high', '2026-09-01', '2026-10-31', 16.0),
                (14, 'IT-014', 7, 'HP Separator Wet H2S WFMT', 'magnetic_particle', 'usually_effective', 65.00, 'WFMT of separator welds for HIC/SOHIC in wet H2S service', 'planned', 'high', '2026-09-01', '2026-10-31', 12.0),
                (15, 'IT-015', 8, 'Tank Floor MFL Scan', 'mfl', 'highly_effective', 95.00, 'Full MFL floor scan of TK-401 per API 653 requirements', 'completed', 'high', '2025-08-15', '2025-09-30', 24.0),
                (16, 'IT-016', 8, 'Tank Shell UT Survey', 'ut_thickness', 'usually_effective', 85.00, 'UT thickness of shell courses 1-5 at soil/product contact zone', 'completed', 'high', '2025-08-15', '2025-09-30', 16.0),
                (17, 'IT-017', 9, 'Boiler Tube UT Survey', 'ut_thickness', 'usually_effective', 50.00, 'UT thickness of waterwall and superheat tubes at representative locations', 'in_progress', 'high', '2026-02-01', '2026-03-31', 20.0),
                (18, 'IT-018', 9, 'Boiler Waterside Visual', 'internal_visual', 'fairly_effective', 80.00, 'Visual inspection of drum internals and tube ends for pitting and deposits', 'in_progress', 'medium', '2026-02-01', '2026-03-31', 12.0),
                (19, 'IT-019', 10, 'FCC Frac Internal Visual', 'internal_visual', 'fairly_effective', 85.00, 'Visual inspection of fractionator trays, downcomers, and wear plates', 'deferred', 'medium', '2026-08-01', '2026-10-31', 16.0),
                (20, 'IT-020', 10, 'FCC Frac UT Thickness', 'ut_thickness', 'usually_effective', 70.00, 'UT thickness survey of fractionator shell at sulfidation-prone zones', 'planned', 'medium', '2026-08-01', '2026-10-31', 12.0)");
            $summary['inspection_tasks'] = 20;

            // ── 12. Inspection Findings (15) ───────────────────────────────
            $pdo->exec("INSERT IGNORE INTO inspection_findings (id, finding_code, task_id, asset_id, finding_type, severity, location_description, cml_reference, measured_thickness_mm, min_measured_thickness_mm, damage_mechanism_id, disposition, notes) VALUES
                (1,  'IF-001', 15, 15, 'wall_thinning', 'moderate', 'Tank floor annular plate section NW quadrant', 'CML-TK401-F01', 9.8, 8.5, 16, 'monitor', 'General thinning of floor plates consistent with soil-side corrosion - 3.2mm loss from nominal'),
                (2,  'IF-002', 15, 15, 'pitting', 'major', 'Tank floor center section near sump', 'CML-TK401-F02', NULL, NULL, 17, 'repair', 'Multiple pits up to 5mm deep in 8mm floor plate - likely MIC under sludge deposits'),
                (3,  'IF-003', 16, 15, 'wall_thinning', 'minor', 'Shell course 1, 0-2m above floor', 'CML-TK401-S01', 11.5, 11.2, 16, 'acceptable', 'Minor thinning at soil contact zone - 1.5mm loss in 23 years (0.065 mm/yr)'),
                (4,  'IF-004', 1, 1, 'wall_thinning', 'major', 'Shell course 16-18, tray 38-42 zone', 'CML-C101-S16', 30.2, 29.5, 1, 'monitor', 'Sulfidation thinning above tray 38 at 340-365C zone - 7.8mm loss, rate 0.38 mm/yr'),
                (5,  'IF-005', 1, 1, 'wall_thinning', 'moderate', 'Shell course 8-10, stripping section', 'CML-C101-S08', 34.1, 33.5, 1, 'acceptable', 'Moderate sulfidation at 280-310C zone - 4mm loss in 21 years'),
                (6,  'IF-006', 4, 2, 'pitting', 'critical', 'Tube bundle row 3-5, inlet end', 'CML-E101-T03', NULL, NULL, 3, 'replace', 'Severe HCl dew point pitting - 12 tubes perforated, 35 tubes below minimum wall'),
                (7,  'IF-007', 4, 2, 'wall_thinning', 'major', 'Header box divider plate', 'CML-E101-H01', 8.2, 7.5, 4, 'repair', 'NH4Cl under-deposit corrosion on header box - 5.2mm loss from 12.7mm nominal'),
                (8,  'IF-008', 6, 3, 'cracking', 'moderate', 'Longitudinal weld seam, lower shell', 'CML-V101-W01', NULL, NULL, 6, 'monitor', 'Minor HIC indication 15mm x 8mm x 2mm deep in weld HAZ - below FFS threshold'),
                (9,  'IF-009', 8, 7, 'wall_thinning', 'moderate', 'Reactor shell, riser area', 'CML-R201-RS01', 47.5, 46.8, 12, 'acceptable', 'Minor wall thinning at cyclone riser attachment - within allowable limits'),
                (10, 'IF-010', 10, 8, 'wall_thinning', 'major', 'Regenerator dome, 12 o clock position', 'CML-R202-D01', 38.5, 37.2, 13, 'monitor', 'High-temp oxidation thinning of regenerator dome - 6.5mm loss, accelerating rate'),
                (11, 'IF-011', 17, 19, 'pitting', 'moderate', 'Waterwall tube, row 3, elevation 8m', 'CML-B501-WW03', 5.8, 5.5, 18, 'monitor', 'Oxygen pitting on tube OD under deposit - 2.2mm deep pit in 8mm tube'),
                (12, 'IF-012', 17, 19, 'wall_thinning', 'minor', 'Superheat tube bank, pass 2', 'CML-B501-SH02', 6.9, 6.7, 15, 'acceptable', 'Minor FAC thinning on SH bend - 1.1mm loss, rate 0.05 mm/yr'),
                (13, 'IF-013', 2, 1, 'erosion', 'minor', 'Column tray 5-8 deck plates', 'CML-C101-TR05', NULL, NULL, 14, 'acceptable', 'Minor erosion of tray deck plates from catalyst fines carryover - 10% thickness loss'),
                (14, 'IF-014', 1, 1, 'corrosion_under_insulation', 'moderate', 'Column skirt, 0.5-1.5m elevation', 'CML-C101-SK01', 35.5, 34.8, 7, 'monitor', 'CUI at skirt attachment area under damaged insulation cladding'),
                (15, 'IF-015', 9, 7, 'no_defect', 'negligible', 'Reactor bottom head weld seams', 'CML-R201-BH01', 49.2, 48.8, NULL, 'acceptable', 'No indications found on PAUT scan of bottom head welds - FCC reactor in good condition')");
            $summary['inspection_findings'] = 15;

            // ── 13. Corrosion Rate Tracking (20 records) ───────────────────
            $pdo->exec("INSERT IGNORE INTO corrosion_rate_tracking (id, asset_id, cml_reference, measurement_date, measured_thickness_mm, measurement_method, measurement_quality, operator, notes) VALUES
                (1,  1, 'CML-C101-S16', '2008-04-15', 37.2, 'ut_manual', 'good', 'J. Anderson', 'Baseline reading after initial 3-year inspection'),
                (2,  1, 'CML-C101-S16', '2012-04-20', 35.5, 'ut_manual', 'good', 'J. Anderson', 'Second turnaround reading - consistent sulfidation'),
                (3,  1, 'CML-C101-S16', '2016-04-18', 33.2, 'ut_manual', 'good', 'S. Mitchell', 'Third T/A reading - rate slightly increasing'),
                (4,  1, 'CML-C101-S16', '2020-04-22', 31.5, 'ut_manual', 'good', 'S. Mitchell', 'Fourth T/A reading - consistent rate'),
                (5,  1, 'CML-C101-S16', '2024-04-15', 29.5, 'ut_manual', 'good', 'S. Mitchell', 'Latest reading - 0.50 mm/yr short-term rate'),
                (6,  1, 'CML-C101-S08', '2008-04-15', 37.0, 'ut_manual', 'good', 'J. Anderson', 'Baseline at lower shell stripping section'),
                (7,  1, 'CML-C101-S08', '2016-04-18', 35.5, 'ut_manual', 'good', 'S. Mitchell', 'Lower rate in stripping zone - 0.19 mm/yr'),
                (8,  1, 'CML-C101-S08', '2024-04-15', 34.1, 'ut_manual', 'good', 'S. Mitchell', 'Continued moderate rate at 0.175 mm/yr LT'),
                (9,  15, 'CML-TK401-S01', '2008-09-10', 12.3, 'ut_manual', 'good', 'M. Rodriguez', 'Shell course 1 baseline'),
                (10, 15, 'CML-TK401-S01', '2016-09-15', 11.8, 'ut_manual', 'good', 'M. Rodriguez', 'Shell course 1 second reading - very low rate'),
                (11, 15, 'CML-TK401-S01', '2025-08-20', 11.2, 'ut_manual', 'good', 'S. Mitchell', 'Latest reading - 0.065 mm/yr'),
                (12, 7, 'CML-R201-RS01', '2012-05-10', 49.5, 'ut_manual', 'good', 'J. Anderson', 'Reactor riser baseline reading post-commissioning'),
                (13, 7, 'CML-R201-RS01', '2018-05-15', 48.3, 'ut_manual', 'good', 'S. Mitchell', 'Riser area showing minor creep/erosion'),
                (14, 7, 'CML-R201-RS01', '2024-05-20', 46.8, 'ut_manual', 'good', 'S. Mitchell', 'Rate increasing - 0.25 mm/yr ST vs 0.22 LT'),
                (15, 8, 'CML-R202-D01', '2012-05-10', 44.2, 'ut_manual', 'good', 'J. Anderson', 'Regenerator dome baseline'),
                (16, 8, 'CML-R202-D01', '2018-05-15', 41.8, 'ut_manual', 'good', 'S. Mitchell', 'Dome oxidation rate 0.40 mm/yr'),
                (17, 8, 'CML-R202-D01', '2024-05-20', 38.5, 'ut_manual', 'good', 'S. Mitchell', 'Accelerating - 0.55 mm/yr ST, regenerator dome priority'),
                (18, 19, 'CML-B501-WW03', '2010-03-15', 7.8, 'ut_manual', 'acceptable', 'J. Anderson', 'Waterwall tube baseline'),
                (19, 19, 'CML-B501-WW03', '2018-03-20', 6.5, 'ut_manual', 'good', 'S. Mitchell', 'Moderate thinning with pitting overlay'),
                (20, 19, 'CML-B501-WW03', '2026-02-15', 5.5, 'ut_manual', 'good', 'S. Mitchell', 'Continued thinning - 0.14 mm/yr LT')");
            $summary['corrosion_rate_tracking'] = 20;

            // ── 14. Remaining Life Estimates (15) ──────────────────────────
            $pdo->exec("INSERT IGNORE INTO remaining_life_estimates (id, asset_id, assessment_id, calculation_date, measured_thickness_mm, min_required_thickness_mm, long_term_corrosion_rate_mm_yr, short_term_corrosion_rate_mm_yr, governing_corrosion_rate_mm_yr, rate_basis, remaining_life_years, retirement_date, confidence, calculation_method, notes) VALUES
                (1,  1,  1,  '2025-06-01', 29.5, 28.5, 0.38, 0.50, 0.50, 'short_term', 2.0,  '2027-06-01', 'high',   'Linear projection', 'CDU column shell course 16-18 at max sulfidation zone - critical remaining life'),
                (2,  1,  1,  '2025-06-01', 34.1, 28.5, 0.18, 0.175, 0.18, 'long_term', 31.1, '2056-07-01', 'high',   'Linear projection', 'CDU column lower stripping section - adequate remaining life'),
                (3,  2,  2,  '2025-06-01', 7.5,  8.5,  0.26, 0.30, 0.30, 'short_term', 0.0,  '2025-01-01', 'high',   'Linear projection', 'Overhead condenser header BELOW t_min - urgent replacement required'),
                (4,  3,  3,  '2025-06-01', 23.8, 18.2, 0.10, 0.12, 0.12, 'short_term', 46.7, '2071-09-01', 'medium', 'Linear projection', 'Overhead accumulator drum - long remaining life, monitor for HIC'),
                (5,  7,  5,  '2025-06-01', 46.8, 38.0, 0.22, 0.25, 0.25, 'short_term', 35.2, '2060-08-01', 'medium', 'Linear projection', 'FCC reactor shell - adequate RL but creep assessment needed'),
                (6,  8,  6,  '2025-06-01', 38.5, 34.0, 0.40, 0.55, 0.55, 'short_term', 8.2,  '2033-08-01', 'high',   'Linear projection', 'FCC regenerator dome - accelerating rate, plan replacement by 2033 T/A'),
                (7,  9,  7,  '2025-06-01', 32.5, 25.0, 0.15, 0.18, 0.18, 'short_term', 41.7, '2066-12-01', 'medium', 'Linear projection', 'FCC fractionator - adequate remaining life'),
                (8,  11, 8,  '2025-06-01', 85.0, 72.0, 0.04, 0.05, 0.05, 'short_term', 260.0, '2285-06-01', 'low', 'Linear projection', 'HDT reactor wall thinning RL - long, but HTHA is the governing mechanism not thinning'),
                (9,  12, 9,  '2025-06-01', 42.5, 32.0, 0.08, 0.10, 0.10, 'short_term', 105.0, '2130-06-01', 'medium', 'Linear projection', 'HP separator - adequate RL from thinning'),
                (10, 15, 11, '2025-06-01', 11.2, 8.0,  0.065, 0.08, 0.08, 'short_term', 40.0, '2065-06-01', 'medium', 'Linear projection', 'Crude tank shell course 1 - adequate RL'),
                (11, 15, 11, '2025-06-01', 8.5,  6.0,  0.20, 0.25, 0.25, 'short_term', 10.0, '2035-06-01', 'medium', 'Linear projection', 'Crude tank floor annular plates - monitor floor scan data'),
                (12, 16, 12, '2025-06-01', 11.5, 8.0,  0.06, 0.07, 0.07, 'short_term', 50.0, '2075-06-01', 'medium', 'Linear projection', 'Crude tank B shell - adequate'),
                (13, 17, 13, '2025-06-01', 9.2,  6.0,  0.04, 0.04, 0.04, 'long_term', 80.0, '2105-06-01', 'low',    'Linear projection', 'Gasoline tank - very low corrosion rate'),
                (14, 19, 14, '2025-06-01', 5.5,  5.5,  0.14, 0.16, 0.16, 'short_term', 0.0,  '2025-01-01', 'high',   'Linear projection', 'Boiler tube row 3 AT t_min - tube replacement required'),
                (15, 20, 15, '2025-06-01', 14.5, 10.0, 0.05, 0.06, 0.06, 'short_term', 75.0, '2100-06-01', 'medium', 'Linear projection', 'Deaerator - adequate remaining life')");
            $summary['remaining_life_estimates'] = 15;

            // ── 15. Risk Scores (20 ML-scored records) ─────────────────────
            $pdo->exec("INSERT IGNORE INTO risk_scores (id, asset_id, overall_risk, pof_score, cof_score, health_index, risk_category, scoring_method, scored_at, notes) VALUES
                (1,  1,  85.5, 72.0, 95.0, 32.0, 'very_high', 'ml_enhanced', '2025-12-01 10:00:00', 'CDU column - sulfidation driving high PoF, critical consequence'),
                (2,  2,  92.0, 88.0, 90.0, 18.0, 'very_high', 'ml_enhanced', '2025-12-01 10:00:00', 'Overhead condenser - below t_min, critical HCl corrosion'),
                (3,  3,  55.0, 48.0, 65.0, 58.0, 'medium',    'ml_enhanced', '2025-12-01 10:00:00', 'Accumulator drum - wet H2S service but adequate wall'),
                (4,  4,  42.0, 35.0, 58.0, 65.0, 'medium',    'ml_enhanced', '2025-12-01 10:00:00', 'Vacuum column - moderate sulfidation, alloy upgrade helps'),
                (5,  5,  38.0, 40.0, 35.0, 70.0, 'low',       'automated',   '2025-12-01 10:00:00', 'Crude/residue exchanger - moderate risk, adequate thickness'),
                (6,  6,  45.0, 42.0, 50.0, 62.0, 'medium',    'automated',   '2025-12-01 10:00:00', 'Desalter - moderate HCl risk, manageable'),
                (7,  7,  88.0, 78.0, 98.0, 28.0, 'very_high', 'ml_enhanced', '2025-12-01 10:00:00', 'FCC reactor - creep and erosion concern, highest consequence'),
                (8,  8,  90.0, 82.0, 95.0, 22.0, 'very_high', 'ml_enhanced', '2025-12-01 10:00:00', 'FCC regenerator - accelerating oxidation, high consequence'),
                (9,  9,  52.0, 45.0, 62.0, 55.0, 'medium',    'automated',   '2025-12-01 10:00:00', 'FCC fractionator - moderate sulfidation risk'),
                (10, 10, 32.0, 28.0, 42.0, 72.0, 'low',       'automated',   '2025-12-01 10:00:00', 'Slurry settler - low risk, adequate condition'),
                (11, 11, 95.0, 90.0, 98.0, 15.0, 'very_high', 'ml_enhanced', '2025-12-01 10:00:00', 'HDT reactor - HTHA governing mechanism, highest risk asset'),
                (12, 12, 72.0, 65.0, 82.0, 38.0, 'high',      'ml_enhanced', '2025-12-01 10:00:00', 'HP separator - HTHA and wet H2S risks combined'),
                (13, 13, 55.0, 50.0, 62.0, 52.0, 'medium',    'automated',   '2025-12-01 10:00:00', 'Effluent exchanger - moderate risk from HTHA'),
                (14, 14, 35.0, 30.0, 45.0, 68.0, 'low',       'automated',   '2025-12-01 10:00:00', 'Product stripper - low risk, good condition'),
                (15, 15, 68.0, 62.0, 75.0, 42.0, 'high',      'ml_enhanced', '2025-12-01 10:00:00', 'Crude tank A - floor corrosion and MIC concerns'),
                (16, 16, 65.0, 58.0, 72.0, 45.0, 'high',      'ml_enhanced', '2025-12-01 10:00:00', 'Crude tank B - similar to TK-401'),
                (17, 17, 25.0, 20.0, 35.0, 80.0, 'low',       'automated',   '2025-12-01 10:00:00', 'Gasoline tank - low risk, clean service'),
                (18, 18, 30.0, 25.0, 38.0, 75.0, 'low',       'automated',   '2025-12-01 10:00:00', 'Diesel tank - low risk'),
                (19, 19, 78.0, 72.0, 85.0, 30.0, 'high',      'ml_enhanced', '2025-12-01 10:00:00', 'HP boiler - tubes at t_min, oxygen pitting active'),
                (20, 20, 22.0, 18.0, 30.0, 82.0, 'low',       'automated',   '2025-12-01 10:00:00', 'Deaerator - low risk, good condition')");
            $summary['risk_scores'] = 20;

            // ── 16. Risk Alerts (10) ───────────────────────────────────────
            $pdo->exec("INSERT IGNORE INTO risk_alerts (id, asset_id, alert_type, severity, message, acknowledged) VALUES
                (1,  2,  'threshold_breach', 'critical', 'CDU Overhead Condenser (CDU-E-101) header box thickness BELOW minimum required thickness (7.5mm < 8.5mm t_min). Immediate engineering assessment required per API 510.', 0),
                (2,  19, 'threshold_breach', 'critical', 'HP Steam Boiler (UTL-B-501) waterwall tube row 3 at minimum thickness (5.5mm = 5.5mm t_min). Schedule tube replacement at next available outage.', 0),
                (3,  1,  'accelerating_degradation', 'warning', 'CDU Atmospheric Column (CDU-C-101) sulfidation rate increasing: short-term rate 0.50 mm/yr vs long-term 0.38 mm/yr at shell courses 16-18. Remaining life 2.0 years.', 0),
                (4,  8,  'accelerating_degradation', 'warning', 'FCC Regenerator (FCC-R-202) dome oxidation rate accelerating: 0.55 mm/yr short-term vs 0.40 mm/yr long-term. Review metallurgy upgrade options for next T/A.', 0),
                (5,  11, 'risk_increase', 'critical', 'HDT Reactor (HDT-R-301) has highest fleet risk score (95.0). HTHA is governing damage mechanism. Ensure AUBT screening is completed before next operating cycle.', 0),
                (6,  7,  'overdue_inspection', 'warning', 'FCC Reactor (FCC-R-201) creep replication due 2026-04-01. Schedule during upcoming catalyst changeout outage.', 1),
                (7,  15, 'risk_increase', 'warning', 'Crude Tank A (TF-TK-401) floor MFL scan identified active MIC damage near sump. Consider chemical treatment and re-scan in 2 years.', 0),
                (8,  3,  'anomaly_detected', 'info', 'Overhead Accumulator (CDU-V-101) HIC indication detected at lower shell weld HAZ - currently below FFS threshold but requires monitoring.', 1),
                (9,  12, 'risk_increase', 'warning', 'HP Separator (HDT-V-301) combined HTHA and wet H2S risk warrants priority inspection during next HDT turnaround.', 0),
                (10, 17, 'overdue_inspection', 'info', 'Gasoline Tank (TF-TK-501) API 653 internal inspection due within 12 months. Low risk - schedule per normal interval.', 0)");
            $summary['risk_alerts'] = 10;

            // ── 17. Financial Risk Models ──────────────────────────────────
            $pdo->exec("INSERT IGNORE INTO financial_risk_models (id, assessment_id, model_name, model_type, annual_risk_cost_usd, inspection_cost_usd, mitigation_cost_usd, risk_reduction_value_usd, net_benefit_usd, return_on_investment_pct, optimal_interval_months) VALUES
                (1, 1,  'CDU Column Annual Risk Cost', 'expected_value', 2850000.00, 185000.00, 0.00, 1200000.00, 1015000.00, 548.65, 48),
                (2, 2,  'CDU Overhead Condenser Risk', 'expected_value', 1500000.00, 95000.00, 850000.00, 1350000.00, 405000.00, 42.63, 24),
                (3, 5,  'FCC Reactor Annual Risk', 'expected_value', 8500000.00, 320000.00, 0.00, 3500000.00, 3180000.00, 993.75, 48),
                (4, 8,  'HDT Reactor HTHA Risk', 'expected_value', 12000000.00, 480000.00, 0.00, 5000000.00, 4520000.00, 941.67, 36),
                (5, 11, 'Crude Tank A Floor Risk', 'expected_value', 950000.00, 120000.00, 350000.00, 720000.00, 250000.00, 53.19, 60)");

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            echo json_encode([
                'success' => true,
                'summary' => $summary,
                'message' => 'Sample data loaded successfully. Created ' . array_sum($summary) . ' total records across ' . count($summary) . ' categories.'
            ]);
            break;

        // =====================================================================
        // CLEAR SAMPLE DATA
        // =====================================================================
        case 'clear_sample_data':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $tablesToClear = [
                'financial_risk_models', 'sensitivity_analyses',
                'corrosion_rate_history', 'corrosion_rate_tracking',
                'remaining_life_estimates', 'work_priorities',
                'inspection_findings', 'inspection_history',
                'inspection_tasks', 'inspection_intervals', 'inspection_plans',
                'risk_rankings', 'consequence_of_failure', 'probability_of_failure',
                'risk_assessments', 'susceptibility_inputs',
                'damage_mechanism_assignments',
                'corrosion_circuit_assets', 'corrosion_circuits',
                'operational_data', 'design_data', 'asset_registry',
                'equipment_hierarchy',
                'risk_alerts', 'risk_scores', 'ml_predictions', 'ml_models',
                'monte_carlo_results', 'asset_clusters',
                'integration_sync_log', 'operating_excursions',
                'user_activity_log', 'audit_trail'
            ];

            $cleared = [];
            foreach ($tablesToClear as $table) {
                try {
                    $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    if ($count > 0) {
                        $pdo->exec("DELETE FROM `{$table}`");
                        $cleared[$table] = (int)$count;
                    }
                } catch (PDOException $e) {
                    // Table might not exist
                    $cleared[$table] = 'table_not_found';
                }
            }

            // Clear users except admin (id=1)
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM users WHERE id > 1")->fetchColumn();
                if ($count > 0) {
                    $pdo->exec("DELETE FROM users WHERE id > 1");
                    $cleared['users'] = (int)$count;
                }
            } catch (PDOException $e) {
                $cleared['users'] = 'error';
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            echo json_encode([
                'success' => true,
                'cleared' => $cleared,
                'message' => 'Sample data cleared successfully. Preserved roles, permissions, damage mechanism library, risk matrix, and admin user.'
            ]);
            break;

        // =====================================================================
        // FACTORY RESET
        // =====================================================================
        case 'factory_reset':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $confirmToken = $_POST['confirm'] ?? '';
            if ($confirmToken !== 'RESET') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Confirmation token "RESET" required']);
                exit;
            }

            $results = [];
            $schemaFiles = [
                BASE_PATH . '/database/schema.sql',
                BASE_PATH . '/database/ml_tables.sql',
                BASE_PATH . '/database/integration_tables.sql'
            ];

            foreach ($schemaFiles as $file) {
                if (file_exists($file)) {
                    $sql = file_get_contents($file);
                    try {
                        $pdo->exec($sql);
                        $results[basename($file)] = 'executed';
                    } catch (PDOException $e) {
                        $results[basename($file)] = 'error: ' . $e->getMessage();
                    }
                } else {
                    $results[basename($file)] = 'file_not_found';
                }
            }

            echo json_encode([
                'success' => true,
                'results' => $results,
                'message' => 'Factory reset completed. All tables have been dropped and recreated from schema files.'
            ]);
            break;

        // =====================================================================
        // DATABASE STATUS
        // =====================================================================
        case 'db_status':
            $tables = $pdo->query("
                SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, AUTO_INCREMENT, ENGINE, TABLE_COLLATION, CREATE_TIME, UPDATE_TIME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            ")->fetchAll();

            $totalDataSize = 0;
            $totalIndexSize = 0;
            $totalRows = 0;
            foreach ($tables as &$t) {
                $totalDataSize += $t['DATA_LENGTH'];
                $totalIndexSize += $t['INDEX_LENGTH'];
                $totalRows += $t['TABLE_ROWS'];
                $t['DATA_LENGTH_FORMATTED'] = formatBytes($t['DATA_LENGTH']);
                $t['INDEX_LENGTH_FORMATTED'] = formatBytes($t['INDEX_LENGTH']);
            }

            $mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();

            // Check for backups directory
            $backupsDir = BASE_PATH . '/backups';
            $lastBackup = null;
            if (is_dir($backupsDir)) {
                $backupFiles = glob($backupsDir . '/*.sql');
                if (!empty($backupFiles)) {
                    usort($backupFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
                    $lastBackup = date('Y-m-d H:i:s', filemtime($backupFiles[0]));
                }
            }

            echo json_encode([
                'success' => true,
                'tables' => $tables,
                'total_data_size' => formatBytes($totalDataSize),
                'total_index_size' => formatBytes($totalIndexSize),
                'total_size' => formatBytes($totalDataSize + $totalIndexSize),
                'total_rows' => $totalRows,
                'table_count' => count($tables),
                'connection' => [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'user' => DB_USER,
                    'charset' => DB_CHARSET,
                    'port' => DB_PORT
                ],
                'mysql_version' => $mysqlVersion,
                'last_backup' => $lastBackup
            ]);
            break;

        // =====================================================================
        // OPTIMIZE TABLES
        // =====================================================================
        case 'optimize_tables':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $tables = $pdo->query("
                SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_TYPE = 'BASE TABLE'
            ")->fetchAll();

            $results = [];
            foreach ($tables as $t) {
                $name = $t['TABLE_NAME'];
                $res = $pdo->query("OPTIMIZE TABLE `{$name}`")->fetchAll();
                $results[$name] = $res[0]['Msg_text'] ?? 'done';
            }

            echo json_encode(['success' => true, 'results' => $results, 'message' => 'All tables optimized']);
            break;

        // =====================================================================
        // CHECK TABLES
        // =====================================================================
        case 'check_tables':
            $tables = $pdo->query("
                SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_TYPE = 'BASE TABLE'
            ")->fetchAll();

            $results = [];
            foreach ($tables as $t) {
                $name = $t['TABLE_NAME'];
                $res = $pdo->query("CHECK TABLE `{$name}`")->fetchAll();
                $results[$name] = $res[0]['Msg_text'] ?? 'unknown';
            }

            echo json_encode(['success' => true, 'results' => $results, 'message' => 'All tables checked']);
            break;

        // =====================================================================
        // REPAIR TABLES
        // =====================================================================
        case 'repair_tables':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $tables = $pdo->query("
                SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_TYPE = 'BASE TABLE'
            ")->fetchAll();

            $results = [];
            foreach ($tables as $t) {
                $name = $t['TABLE_NAME'];
                try {
                    $res = $pdo->query("REPAIR TABLE `{$name}`")->fetchAll();
                    $results[$name] = $res[0]['Msg_text'] ?? 'done';
                } catch (PDOException $e) {
                    $results[$name] = 'not supported (InnoDB)';
                }
            }

            echo json_encode(['success' => true, 'results' => $results, 'message' => 'Table repair completed']);
            break;

        // =====================================================================
        // FLUSH CACHES
        // =====================================================================
        case 'flush_caches':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $results = [];
            try {
                $pdo->exec("FLUSH TABLES");
                $results['flush_tables'] = 'OK';
            } catch (PDOException $e) {
                $results['flush_tables'] = $e->getMessage();
            }
            try {
                $pdo->exec("RESET QUERY CACHE");
                $results['reset_query_cache'] = 'OK';
            } catch (PDOException $e) {
                $results['reset_query_cache'] = 'not available (MySQL 8+)';
            }

            echo json_encode(['success' => true, 'results' => $results, 'message' => 'Caches flushed']);
            break;

        // =====================================================================
        // EXPORT SQL
        // =====================================================================
        case 'export_sql':
            $backupsDir = BASE_PATH . '/backups';
            if (!is_dir($backupsDir)) {
                mkdir($backupsDir, 0755, true);
            }

            $filename = 'rbi_backup_' . date('Y-m-d_His') . '.sql';
            $filepath = $backupsDir . '/' . $filename;

            $tables = $pdo->query("
                SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            ")->fetchAll();

            $dump = "-- RBI Engineering Suite Database Backup\n";
            $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $dump .= "-- Database: " . DB_NAME . "\n\n";
            $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            $selectedTables = isset($_GET['tables']) ? explode(',', $_GET['tables']) : null;

            foreach ($tables as $t) {
                $name = $t['TABLE_NAME'];
                if ($selectedTables && !in_array($name, $selectedTables)) continue;

                // Get CREATE TABLE
                $create = $pdo->query("SHOW CREATE TABLE `{$name}`")->fetch();
                $dump .= "DROP TABLE IF EXISTS `{$name}`;\n";
                $dump .= $create['Create Table'] . ";\n\n";

                // Get data
                $rows = $pdo->query("SELECT * FROM `{$name}`")->fetchAll();
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $colList = implode('`, `', $columns);

                    foreach (array_chunk($rows, 100) as $chunk) {
                        $dump .= "INSERT INTO `{$name}` (`{$colList}`) VALUES\n";
                        $values = [];
                        foreach ($chunk as $row) {
                            $vals = [];
                            foreach ($row as $val) {
                                if ($val === null) {
                                    $vals[] = 'NULL';
                                } else {
                                    $vals[] = $pdo->quote($val);
                                }
                            }
                            $values[] = '(' . implode(', ', $vals) . ')';
                        }
                        $dump .= implode(",\n", $values) . ";\n\n";
                    }
                }
            }

            $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            // Save to file
            file_put_contents($filepath, $dump);

            if (isset($_GET['download'])) {
                header('Content-Type: application/sql');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                echo $dump;
                exit;
            }

            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'size' => formatBytes(strlen($dump)),
                'path' => '/rbi/backups/' . $filename,
                'message' => 'SQL backup created successfully'
            ]);
            break;

        // =====================================================================
        // EXPORT CSV
        // =====================================================================
        case 'export_csv':
            $tableName = $_GET['table'] ?? '';
            if (!$tableName) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Table name required']);
                exit;
            }

            // Validate table name exists
            $tableCheck = $pdo->query("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = " . $pdo->quote($tableName)
            )->fetchColumn();

            if (!$tableCheck) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Table not found']);
                exit;
            }

            $rows = $pdo->query("SELECT * FROM `{$tableName}`")->fetchAll();

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $tableName . '_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');

            if (!empty($rows)) {
                fputcsv($output, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }
            }

            fclose($output);
            exit;

        // =====================================================================
        // CREATE BACKUP
        // =====================================================================
        case 'create_backup':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            // Redirect to export_sql
            $_GET['action'] = 'export_sql';
            include __FILE__;
            exit;

        // =====================================================================
        // LIST BACKUPS
        // =====================================================================
        case 'list_backups':
            $backupsDir = BASE_PATH . '/backups';
            $backups = [];

            if (is_dir($backupsDir)) {
                $files = glob($backupsDir . '/*.sql');
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => formatBytes(filesize($file)),
                        'size_bytes' => filesize($file),
                        'created' => date('Y-m-d H:i:s', filemtime($file)),
                        'path' => '/rbi/backups/' . basename($file)
                    ];
                }
                usort($backups, function($a, $b) {
                    return strtotime($b['created']) - strtotime($a['created']);
                });
            }

            echo json_encode(['success' => true, 'backups' => $backups]);
            break;

        // =====================================================================
        // IMPORT SQL
        // =====================================================================
        case 'import_sql':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            if (!isset($_FILES['sql_file'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No SQL file uploaded']);
                exit;
            }

            $file = $_FILES['sql_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Upload error: ' . $file['error']]);
                exit;
            }

            $sql = file_get_contents($file['tmp_name']);
            try {
                $pdo->exec($sql);
                echo json_encode(['success' => true, 'message' => 'SQL file imported successfully', 'size' => formatBytes(strlen($sql))]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'SQL import failed: ' . $e->getMessage()]);
            }
            break;

        // =====================================================================
        // IMPORT CSV
        // =====================================================================
        case 'import_csv':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                exit;
            }

            $tableName = $_POST['table_name'] ?? '';
            if (!$tableName || !isset($_FILES['csv_file'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Table name and CSV file required']);
                exit;
            }

            $file = $_FILES['csv_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Upload error']);
                exit;
            }

            $handle = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($handle);
            $rowCount = 0;
            $errors = 0;

            $colList = implode('`, `', $headers);
            $placeholders = implode(', ', array_fill(0, count($headers), '?'));
            $stmt = $pdo->prepare("INSERT INTO `{$tableName}` (`{$colList}`) VALUES ({$placeholders})");

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    // Convert empty strings to null
                    $row = array_map(function($v) { return $v === '' ? null : $v; }, $row);
                    $stmt->execute($row);
                    $rowCount++;
                } catch (PDOException $e) {
                    $errors++;
                }
            }
            fclose($handle);

            echo json_encode([
                'success' => true,
                'rows_imported' => $rowCount,
                'errors' => $errors,
                'message' => "Imported {$rowCount} rows into {$tableName}" . ($errors ? " with {$errors} errors" : "")
            ]);
            break;

        // =====================================================================
        // GET TABLE LIST (for dropdowns)
        // =====================================================================
        case 'table_list':
            $tables = $pdo->query("
                SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            ")->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(['success' => true, 'tables' => $tables]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => APP_ENV === 'development' ? $e->getMessage() : 'Internal server error'
    ]);
}

/**
 * Format bytes to human-readable string
 */
function formatBytes($bytes, $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
