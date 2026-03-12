<?php
/**
 * DamageMechanism - Damage mechanism library and susceptibility assessment
 *
 * Based on API 571 damage mechanism taxonomy and API 581 screening criteria.
 */
class DamageMechanism
{
    private Database $db;

    /**
     * Built-in damage mechanism reference library (API 571 based).
     * Used as defaults if the database table is empty.
     */
    private const MECHANISM_LIBRARY = [
        // ── Thinning ──
        ['code' => 'THIN-01', 'name' => 'General Corrosion',          'category' => 'thinning',  'description' => 'Uniform metal loss over a broad area.'],
        ['code' => 'THIN-02', 'name' => 'Localized Corrosion',        'category' => 'thinning',  'description' => 'Concentrated metal loss in discrete areas.'],
        ['code' => 'THIN-03', 'name' => 'Galvanic Corrosion',         'category' => 'thinning',  'description' => 'Corrosion from dissimilar metal contact.'],
        ['code' => 'THIN-04', 'name' => 'Erosion / Erosion-Corrosion','category' => 'thinning',  'description' => 'Metal loss from fluid impingement or combined erosion and corrosion.'],
        ['code' => 'THIN-05', 'name' => 'Microbiologically Induced Corrosion (MIC)', 'category' => 'thinning', 'description' => 'Corrosion promoted by microbial activity.'],
        ['code' => 'THIN-06', 'name' => 'Naphthenic Acid Corrosion',  'category' => 'thinning',  'description' => 'Corrosion by naphthenic acids in crude oil at high temperatures.'],
        ['code' => 'THIN-07', 'name' => 'Sulfidic Corrosion',         'category' => 'thinning',  'description' => 'Corrosion by H2S and sulfur compounds at elevated temperature.'],
        ['code' => 'THIN-08', 'name' => 'HF Acid Corrosion',          'category' => 'thinning',  'description' => 'Corrosion in hydrofluoric acid alkylation units.'],
        ['code' => 'THIN-09', 'name' => 'CO2 Corrosion',              'category' => 'thinning',  'description' => 'Sweet corrosion in the presence of CO2 and water.'],
        ['code' => 'THIN-10', 'name' => 'Under-Deposit Corrosion',    'category' => 'thinning',  'description' => 'Corrosion beneath deposits and fouling.'],

        // ── Cracking ──
        ['code' => 'SCC-01',  'name' => 'Chloride Stress Corrosion Cracking (Cl-SCC)', 'category' => 'cracking', 'description' => 'Cracking of austenitic SS in chloride environments above ~60C.'],
        ['code' => 'SCC-02',  'name' => 'Sulfide Stress Cracking (SSC)',               'category' => 'cracking', 'description' => 'Cracking in sour (H2S) environments at low temperatures.'],
        ['code' => 'SCC-03',  'name' => 'Hydrogen Induced Cracking (HIC)',             'category' => 'cracking', 'description' => 'Internal cracking from hydrogen absorption in sour service.'],
        ['code' => 'SCC-04',  'name' => 'Stress Oriented HIC (SOHIC)',                 'category' => 'cracking', 'description' => 'Stacked HIC cracks aligned with principal stress.'],
        ['code' => 'SCC-05',  'name' => 'Caustic Stress Corrosion Cracking',           'category' => 'cracking', 'description' => 'Cracking in caustic (NaOH/KOH) environments.'],
        ['code' => 'SCC-06',  'name' => 'Amine Stress Corrosion Cracking',             'category' => 'cracking', 'description' => 'Cracking in amine treating units.'],
        ['code' => 'SCC-07',  'name' => 'Carbonate Stress Corrosion Cracking',         'category' => 'cracking', 'description' => 'Cracking in alkaline carbonate environments.'],
        ['code' => 'SCC-08',  'name' => 'Polythionic Acid SCC',                        'category' => 'cracking', 'description' => 'Cracking of sensitised SS during shutdowns.'],
        ['code' => 'SCC-09',  'name' => 'Wet H2S Cracking (Blistering)',               'category' => 'cracking', 'description' => 'Blistering and cracking from wet H2S.'],

        // ── High-Temperature ──
        ['code' => 'HT-01',   'name' => 'High-Temperature Hydrogen Attack (HTHA)',  'category' => 'high_temp', 'description' => 'Internal decarburization and fissuring by hydrogen at high T/P.'],
        ['code' => 'HT-02',   'name' => 'Creep',                                    'category' => 'high_temp', 'description' => 'Time-dependent deformation at elevated temperature.'],
        ['code' => 'HT-03',   'name' => 'Thermal Fatigue',                           'category' => 'high_temp', 'description' => 'Cracking from cyclic thermal stresses.'],
        ['code' => 'HT-04',   'name' => 'High-Temperature Oxidation',               'category' => 'high_temp', 'description' => 'Scaling and metal loss from high-temperature oxidation.'],
        ['code' => 'HT-05',   'name' => 'Carburization',                             'category' => 'high_temp', 'description' => 'Carbon diffusion into steel at high temperatures.'],
        ['code' => 'HT-06',   'name' => 'Sigma Phase Embrittlement',                 'category' => 'high_temp', 'description' => 'Embrittlement from sigma phase in high-Cr alloys.'],

        // ── External ──
        ['code' => 'EXT-01',  'name' => 'Corrosion Under Insulation (CUI)', 'category' => 'external', 'description' => 'External corrosion beneath insulation or fireproofing.'],
        ['code' => 'EXT-02',  'name' => 'Atmospheric Corrosion',            'category' => 'external', 'description' => 'External corrosion in ambient atmospheric conditions.'],
        ['code' => 'EXT-03',  'name' => 'Soil-Side Corrosion',              'category' => 'external', 'description' => 'Corrosion on buried or soil-contacting surfaces.'],

        // ── Mechanical/Fatigue ──
        ['code' => 'FAT-01',  'name' => 'Mechanical Fatigue',  'category' => 'mechanical', 'description' => 'Cracking from cyclic mechanical loading.'],
        ['code' => 'FAT-02',  'name' => 'Vibration Fatigue',   'category' => 'mechanical', 'description' => 'Fatigue from flow-induced or mechanical vibration.'],
        ['code' => 'FAT-03',  'name' => 'Cavitation',          'category' => 'mechanical', 'description' => 'Pitting damage from collapsing vapour bubbles.'],

        // ── Brittle Fracture ──
        ['code' => 'BF-01',   'name' => 'Brittle Fracture',               'category' => 'metallurgical', 'description' => 'Sudden fracture below the ductile-to-brittle transition temperature.'],
        ['code' => 'BF-02',   'name' => 'Temper Embrittlement',           'category' => 'metallurgical', 'description' => 'Embrittlement from prolonged exposure at 370-560C.'],
        ['code' => 'BF-03',   'name' => '885F Embrittlement',             'category' => 'metallurgical', 'description' => 'Embrittlement of ferritic SS at 370-540C.'],
    ];

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── CRUD ────────────────────────────────────────────────────────

    /**
     * Get all damage mechanisms from the library
     */
    public function getAllMechanisms(?string $category = null): array
    {
        if ($category) {
            return $this->db->query(
                "SELECT * FROM damage_mechanisms WHERE category = ? ORDER BY code",
                [$category]
            )->fetchAll();
        }
        return $this->db->query("SELECT * FROM damage_mechanisms ORDER BY category, code")->fetchAll();
    }

    /**
     * Get mechanisms assigned to a specific asset with susceptibility ratings
     */
    public function getMechanismsByAsset(int $assetId): array
    {
        return $this->db->query(
            "SELECT dm.*, adm.susceptibility, adm.damage_rate, adm.active,
                    adm.inspection_effectiveness, adm.last_assessed_date, adm.notes
             FROM asset_damage_mechanisms adm
             JOIN damage_mechanisms dm ON adm.mechanism_id = dm.id
             WHERE adm.asset_id = ?
             ORDER BY FIELD(adm.susceptibility, 'high', 'medium', 'low', 'none'), dm.code",
            [$assetId]
        )->fetchAll();
    }

    /**
     * Assess susceptibility of an asset to all applicable damage mechanisms
     *
     * Screening logic based on material, temperature, fluid, and process conditions
     * per API 581 Part 2 screening tables.
     *
     * @return array  List of mechanisms with susceptibility levels
     */
    public function assessSusceptibility(int $assetId): array
    {
        $asset  = (new Asset())->getById($assetId);
        $opData = (new Asset())->getOperationalData($assetId);

        if (!$asset) {
            throw new InvalidArgumentException("Asset not found: {$assetId}");
        }

        $material = strtolower($asset['material'] ?? 'carbon steel');
        $temp     = (float) ($asset['operating_temperature'] ?? 25);
        $pressure = (float) ($asset['operating_pressure'] ?? 0);
        $h2s      = (float) ($opData['h2s_content'] ?? 0);
        $co2      = (float) ($opData['co2_content'] ?? 0);
        $chloride = (float) ($opData['chloride_content'] ?? 0);
        $ph       = (float) ($opData['ph_level'] ?? 7.0);
        $velocity = (float) ($opData['flow_velocity'] ?? 0);
        $fluid    = $opData['fluid_service'] ?? 'hydrocarbon';

        $results = [];

        // ── General Corrosion ──
        $results[] = ['code' => 'THIN-01', 'susceptibility' => 'medium', 'reason' => 'Default for all carbon steel equipment.'];

        // ── Erosion-Corrosion ──
        if ($velocity > 3.0) {
            $sus = $velocity > 10.0 ? 'high' : 'medium';
            $results[] = ['code' => 'THIN-04', 'susceptibility' => $sus, 'reason' => "Flow velocity {$velocity} m/s exceeds threshold."];
        }

        // ── Sulfidic Corrosion ──
        if ($h2s > 0 && $temp > 230) {
            $sus = ($temp > 340 || $h2s > 1.0) ? 'high' : 'medium';
            $results[] = ['code' => 'THIN-07', 'susceptibility' => $sus, 'reason' => "H2S present at {$temp}C."];
        }

        // ── CO2 Corrosion ──
        if ($co2 > 0 && str_contains($fluid, 'gas')) {
            $sus = $co2 > 2.0 ? 'high' : 'medium';
            $results[] = ['code' => 'THIN-09', 'susceptibility' => $sus, 'reason' => "CO2 content {$co2}% in gas service."];
        }

        // ── MIC ──
        if ($temp < 80 && $temp > 10 && str_contains($fluid, 'water')) {
            $results[] = ['code' => 'THIN-05', 'susceptibility' => 'medium', 'reason' => 'Water service in MIC-favorable temperature range.'];
        }

        // ── Cl-SCC ──
        if (str_contains($material, 'stainless') && str_contains($material, 'austenitic') && $chloride > 10 && $temp > 60) {
            $sus = ($chloride > 100 || $temp > 100) ? 'high' : 'medium';
            $results[] = ['code' => 'SCC-01', 'susceptibility' => $sus, 'reason' => "Austenitic SS with {$chloride} ppm Cl- at {$temp}C."];
        }

        // ── Wet H2S Cracking ──
        if ($h2s > 0 && $temp < 80 && $ph < 7) {
            $sus = $h2s > 0.5 ? 'high' : 'medium';
            $results[] = ['code' => 'SCC-09', 'susceptibility' => $sus, 'reason' => "Wet H2S conditions ({$h2s}% H2S, pH {$ph})."];
        }

        // ── SSC ──
        if ($h2s > 0 && $temp < 80 && !str_contains($material, 'stainless')) {
            $results[] = ['code' => 'SCC-02', 'susceptibility' => $h2s > 1.0 ? 'high' : 'medium', 'reason' => "Sour service at {$temp}C."];
        }

        // ── HIC / SOHIC ──
        if ($h2s > 0.05 && $temp < 150 && !str_contains($material, 'hic resistant')) {
            $results[] = ['code' => 'SCC-03', 'susceptibility' => $h2s > 1.0 ? 'high' : 'medium', 'reason' => "H2S {$h2s}% may cause HIC."];
            $results[] = ['code' => 'SCC-04', 'susceptibility' => 'low', 'reason' => 'SOHIC possible where HIC is present with high stress.'];
        }

        // ── HTHA ──
        if ($temp > 204 && $pressure > 7 && str_contains($fluid, 'hydrogen')) {
            $results[] = ['code' => 'HT-01', 'susceptibility' => $temp > 300 ? 'high' : 'medium', 'reason' => "Hydrogen at {$temp}C / {$pressure} bar."];
        }

        // ── Creep ──
        if ($temp > 400 && str_contains($material, 'carbon')) {
            $results[] = ['code' => 'HT-02', 'susceptibility' => $temp > 480 ? 'high' : 'medium', 'reason' => "Carbon steel at {$temp}C above creep threshold."];
        }

        // ── CUI ──
        if ($temp >= -12 && $temp <= 175) {
            $sus = ($temp >= 50 && $temp <= 150) ? 'high' : 'medium';
            $results[] = ['code' => 'EXT-01', 'susceptibility' => $sus, 'reason' => "Operating temperature {$temp}C in CUI-susceptible range."];
        }

        // ── Atmospheric Corrosion ──
        $results[] = ['code' => 'EXT-02', 'susceptibility' => 'low', 'reason' => 'Default for all external surfaces.'];

        // Persist results
        $this->saveAssessmentResults($assetId, $results);

        return $results;
    }

    /**
     * Calculate damage rate (mm/yr) for a specific mechanism on an asset
     */
    public function calculateDamageRate(int $assetId, string $mechanismCode): float
    {
        $asset  = (new Asset())->getById($assetId);
        $opData = (new Asset())->getOperationalData($assetId);

        if (!$asset) {
            throw new InvalidArgumentException("Asset not found: {$assetId}");
        }

        $temp     = (float) ($asset['operating_temperature'] ?? 25);
        $material = strtolower($asset['material'] ?? 'carbon steel');
        $h2s      = (float) ($opData['h2s_content'] ?? 0);
        $velocity = (float) ($opData['flow_velocity'] ?? 0);

        return match (true) {
            // General / localized corrosion
            str_starts_with($mechanismCode, 'THIN-01'),
            str_starts_with($mechanismCode, 'THIN-02') => $this->generalCorrosionRate($material, $temp),

            // Erosion-corrosion
            str_starts_with($mechanismCode, 'THIN-04') => $this->erosionCorrosionRate($velocity, $material),

            // Sulfidic corrosion (modified McConomy curves)
            str_starts_with($mechanismCode, 'THIN-07') => $this->sulfidationRate($temp, $material, $h2s),

            // CO2 corrosion (de Waard-Milliams)
            str_starts_with($mechanismCode, 'THIN-09') => $this->co2CorrosionRate(
                $temp, (float)($opData['co2_content'] ?? 0)
            ),

            // CUI
            str_starts_with($mechanismCode, 'EXT-01') => $this->cuiRate($temp, $material),

            default => 0.1, // conservative default
        };
    }

    // ── Rate Models ─────────────────────────────────────────────────

    private function generalCorrosionRate(string $material, float $temp): float
    {
        $baseRate = str_contains($material, 'stainless') ? 0.02 : 0.15;
        $tempFactor = 1.0 + max(0, ($temp - 50)) * 0.005;
        return round($baseRate * $tempFactor, 3);
    }

    private function erosionCorrosionRate(float $velocity, string $material): float
    {
        $hardnessFactor = str_contains($material, 'stainless') ? 0.5 : 1.0;
        return round(0.01 * pow($velocity, 1.5) * $hardnessFactor, 3);
    }

    private function sulfidationRate(float $temp, string $material, float $h2s): float
    {
        // Modified McConomy curve (simplified)
        if ($temp < 230) return 0.0;
        $baseRate = 0.025 * exp(0.005 * ($temp - 230));
        $chromeFactor = match (true) {
            str_contains($material, '9cr')    => 0.1,
            str_contains($material, '5cr')    => 0.15,
            str_contains($material, '2.25cr') => 0.3,
            str_contains($material, '1.25cr') => 0.5,
            default                           => 1.0,
        };
        return round($baseRate * $chromeFactor * max($h2s, 0.1), 3);
    }

    private function co2CorrosionRate(float $temp, float $co2Pct): float
    {
        // de Waard-Milliams model (simplified)
        if ($co2Pct <= 0) return 0.0;
        $logRate = 5.8 - 1710 / ($temp + 273) + 0.67 * log10(max($co2Pct, 0.01));
        return round(pow(10, $logRate), 3);
    }

    private function cuiRate(float $temp, string $material): float
    {
        $inRange = ($temp >= -12 && $temp <= 175);
        if (!$inRange) return 0.0;
        $baseRate = str_contains($material, 'stainless') ? 0.02 : 0.25;
        // Peak CUI zone 50-120C
        $peak = ($temp >= 50 && $temp <= 120) ? 1.5 : 1.0;
        return round($baseRate * $peak, 3);
    }

    // ── Persistence ─────────────────────────────────────────────────

    private function saveAssessmentResults(int $assetId, array $results): void
    {
        try {
            $this->db->beginTransaction();

            // Deactivate previous assessments
            $this->db->update('asset_damage_mechanisms', ['active' => 0], 'asset_id = ?', [$assetId]);

            foreach ($results as $r) {
                $mech = $this->db->query(
                    "SELECT id FROM damage_mechanisms WHERE code = ? LIMIT 1",
                    [$r['code']]
                )->fetch();

                if (!$mech) continue;

                $this->db->insert('asset_damage_mechanisms', [
                    'asset_id'               => $assetId,
                    'mechanism_id'           => $mech['id'],
                    'susceptibility'         => $r['susceptibility'],
                    'damage_rate'            => $this->calculateDamageRate($assetId, $r['code']),
                    'active'                 => 1,
                    'inspection_effectiveness' => $this->recommendedEffectiveness($r['susceptibility']),
                    'last_assessed_date'     => date('Y-m-d'),
                    'notes'                  => $r['reason'],
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('[RBI DM] Assessment save failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function recommendedEffectiveness(string $susceptibility): string
    {
        return match ($susceptibility) {
            'high'   => 'highly_effective',
            'medium' => 'usually_effective',
            'low'    => 'fairly_effective',
            default  => 'fairly_effective',
        };
    }

    /**
     * Seed the damage mechanisms table from the built-in library
     */
    public function seedLibrary(): int
    {
        $count = 0;
        foreach (self::MECHANISM_LIBRARY as $mech) {
            $existing = $this->db->query(
                "SELECT id FROM damage_mechanisms WHERE code = ? LIMIT 1",
                [$mech['code']]
            )->fetch();

            if (!$existing) {
                $this->db->insert('damage_mechanisms', array_merge($mech, [
                    'created_at' => date('Y-m-d H:i:s'),
                ]));
                $count++;
            }
        }
        return $count;
    }
}
