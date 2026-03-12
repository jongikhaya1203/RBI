<?php
/**
 * Asset - CRUD and domain logic for equipment / piping assets
 *
 * Supports hierarchical asset trees, corrosion circuits,
 * design data, and operational data management.
 */
class Asset
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── CRUD ────────────────────────────────────────────────────────

    /**
     * Create a new asset record
     *
     * @return int New asset ID
     */
    public function create(array $data): int
    {
        $this->validateAssetData($data);

        return $this->db->insert('assets', [
            'asset_tag'           => $data['asset_tag'],
            'name'                => $data['name'],
            'description'         => $data['description'] ?? null,
            'asset_type'          => $data['asset_type'],            // vessel, tank, piping, heat_exchanger, etc.
            'parent_id'           => $data['parent_id'] ?? null,
            'facility_id'         => $data['facility_id'] ?? null,
            'unit_id'             => $data['unit_id'] ?? null,
            'circuit_id'          => $data['circuit_id'] ?? null,
            'material'            => $data['material'] ?? null,
            'design_pressure'     => $data['design_pressure'] ?? null,
            'design_temperature'  => $data['design_temperature'] ?? null,
            'operating_pressure'  => $data['operating_pressure'] ?? null,
            'operating_temperature'=> $data['operating_temperature'] ?? null,
            'nominal_thickness'   => $data['nominal_thickness'] ?? null,
            'minimum_thickness'   => $data['minimum_thickness'] ?? null,
            'corrosion_allowance' => $data['corrosion_allowance'] ?? null,
            'install_date'        => $data['install_date'] ?? null,
            'commission_date'     => $data['commission_date'] ?? null,
            'status'              => $data['status'] ?? 'active',
            'criticality'         => $data['criticality'] ?? 'medium',
            'created_by'          => $data['created_by'] ?? null,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Retrieve a single asset by ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->query(
            "SELECT a.*, f.name AS facility_name, c.name AS circuit_name
             FROM assets a
             LEFT JOIN facilities f ON a.facility_id = f.id
             LEFT JOIN corrosion_circuits c ON a.circuit_id = c.id
             WHERE a.id = ?",
            [$id]
        )->fetch();
    }

    /**
     * List assets with optional filtering, sorting, and pagination
     */
    public function getAll(array $filters = [], string $orderBy = 'a.name ASC', int $limit = DEFAULT_PAGE_SIZE, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['asset_type'])) {
            $where[]  = 'a.asset_type = ?';
            $params[] = $filters['asset_type'];
        }
        if (!empty($filters['facility_id'])) {
            $where[]  = 'a.facility_id = ?';
            $params[] = $filters['facility_id'];
        }
        if (!empty($filters['circuit_id'])) {
            $where[]  = 'a.circuit_id = ?';
            $params[] = $filters['circuit_id'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 'a.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['criticality'])) {
            $where[]  = 'a.criticality = ?';
            $params[] = $filters['criticality'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(a.name LIKE ? OR a.asset_tag LIKE ? OR a.description LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params   = array_merge($params, [$term, $term, $term]);
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT a.*, f.name AS facility_name, c.name AS circuit_name
                FROM assets a
                LEFT JOIN facilities f ON a.facility_id = f.id
                LEFT JOIN corrosion_circuits c ON a.circuit_id = c.id
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Update an asset
     */
    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('assets', $data, 'id = ?', [$id]);
    }

    /**
     * Soft-delete an asset (set status = decommissioned)
     */
    public function delete(int $id): int
    {
        return $this->db->update('assets', [
            'status'     => 'decommissioned',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /**
     * Count total assets matching filters (for pagination)
     */
    public function count(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['asset_type'])) { $where[] = 'asset_type = ?'; $params[] = $filters['asset_type']; }
        if (!empty($filters['facility_id'])) { $where[] = 'facility_id = ?'; $params[] = $filters['facility_id']; }
        if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }

        $whereClause = implode(' AND ', $where);
        return (int) $this->db->query("SELECT COUNT(*) FROM assets WHERE {$whereClause}", $params)->fetchColumn();
    }

    // ── Hierarchy ───────────────────────────────────────────────────

    /**
     * Get the full asset hierarchy tree (facility -> unit -> system -> asset)
     */
    public function getHierarchy(?int $facilityId = null): array
    {
        $params = [];
        $where  = '';
        if ($facilityId) {
            $where  = 'WHERE a.facility_id = ?';
            $params = [$facilityId];
        }

        $rows = $this->db->query(
            "SELECT a.id, a.name, a.asset_tag, a.asset_type, a.parent_id, a.facility_id,
                    a.status, a.criticality
             FROM assets a
             {$where}
             ORDER BY a.parent_id, a.name",
            $params
        )->fetchAll();

        return $this->buildTree($rows);
    }

    /**
     * Recursively build tree from flat rows
     */
    private function buildTree(array $rows, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] == $parentId) {
                $row['children'] = $this->buildTree($rows, (int) $row['id']);
                $branch[] = $row;
            }
        }
        return $branch;
    }

    // ── Corrosion Circuits ──────────────────────────────────────────

    /**
     * Get all assets belonging to a corrosion circuit
     */
    public function getByCircuit(int $circuitId): array
    {
        return $this->db->query(
            "SELECT a.*, dm.mechanism_name, dm.susceptibility
             FROM assets a
             LEFT JOIN asset_damage_mechanisms adm ON a.id = adm.asset_id
             LEFT JOIN damage_mechanisms dm ON adm.mechanism_id = dm.id
             WHERE a.circuit_id = ?
             ORDER BY a.name",
            [$circuitId]
        )->fetchAll();
    }

    // ── Design & Operational Data ───────────────────────────────────

    /**
     * Get design-basis data for an asset
     */
    public function getDesignData(int $assetId): ?array
    {
        return $this->db->query(
            "SELECT a.material, a.design_pressure, a.design_temperature,
                    a.nominal_thickness, a.minimum_thickness, a.corrosion_allowance,
                    a.install_date, a.commission_date,
                    dd.code_standard, dd.joint_efficiency, dd.stress_allowable,
                    dd.hydro_test_pressure, dd.pwht_performed
             FROM assets a
             LEFT JOIN asset_design_data dd ON a.id = dd.asset_id
             WHERE a.id = ?",
            [$assetId]
        )->fetch();
    }

    /**
     * Get current operational data for an asset
     */
    public function getOperationalData(int $assetId): ?array
    {
        return $this->db->query(
            "SELECT a.operating_pressure, a.operating_temperature,
                    od.fluid_service, od.fluid_phase, od.h2s_content,
                    od.co2_content, od.chloride_content, od.ph_level,
                    od.flow_velocity, od.injection_points,
                    od.last_reading_date
             FROM assets a
             LEFT JOIN asset_operational_data od ON a.id = od.asset_id
             WHERE a.id = ?",
            [$assetId]
        )->fetch();
    }

    /**
     * Update the current condition of an asset (after inspection)
     */
    public function updateCondition(int $assetId, array $condition): int
    {
        return $this->db->update('assets', [
            'measured_thickness'  => $condition['measured_thickness'] ?? null,
            'condition_grade'     => $condition['condition_grade'] ?? null,    // A, B, C, D, E
            'last_inspection_date'=> $condition['inspection_date'] ?? date('Y-m-d'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ], 'id = ?', [$assetId]);
    }

    // ── Validation ──────────────────────────────────────────────────

    private function validateAssetData(array $data): void
    {
        if (empty($data['asset_tag'])) {
            throw new InvalidArgumentException('Asset tag is required.');
        }
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Asset name is required.');
        }
        if (empty($data['asset_type'])) {
            throw new InvalidArgumentException('Asset type is required.');
        }

        $validTypes = ['vessel', 'tank', 'piping', 'heat_exchanger', 'column', 'reactor', 'boiler', 'valve', 'pump', 'other'];
        if (!in_array($data['asset_type'], $validTypes, true)) {
            throw new InvalidArgumentException('Invalid asset type: ' . $data['asset_type']);
        }
    }
}
