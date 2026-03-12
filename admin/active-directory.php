<?php
/**
 * Active Directory / User Directory - RBI Engineering Suite
 * Organization chart, user directory, LDAP configuration, role mapping
 */
$pageTitle = 'Active Directory';
$pageSection = 'Administration';
$currentModule = 'admin';

require_once dirname(__DIR__) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    flash('Please log in to continue.', 'warning');
    redirect(BASE_URL . '/login.php');
}

// Sample directory data
$departments = [
    'Operations' => [
        'color' => '#3b82f6',
        'icon' => 'fa-cogs',
        'members' => [
            ['name' => 'Robert Thompson', 'title' => 'Plant Manager', 'email' => 'r.thompson@refinery.com', 'phone' => '+1 (555) 100-0001', 'certs' => ['PMP', 'Six Sigma BB'], 'level' => 1],
            ['name' => 'Maria Garcia', 'title' => 'Unit Supervisor - CDU', 'email' => 'm.garcia@refinery.com', 'phone' => '+1 (555) 100-0002', 'certs' => ['API 510'], 'level' => 2],
            ['name' => 'James Wilson', 'title' => 'Unit Supervisor - FCC', 'email' => 'j.wilson@refinery.com', 'phone' => '+1 (555) 100-0003', 'certs' => ['API 570'], 'level' => 2],
            ['name' => 'Linda Chen', 'title' => 'Senior Operator', 'email' => 'l.chen@refinery.com', 'phone' => '+1 (555) 100-0004', 'certs' => [], 'level' => 3],
        ]
    ],
    'Engineering' => [
        'color' => '#8b5cf6',
        'icon' => 'fa-drafting-compass',
        'members' => [
            ['name' => 'Dr. Sarah Mitchell', 'title' => 'Chief Engineer', 'email' => 's.mitchell@refinery.com', 'phone' => '+1 (555) 200-0001', 'certs' => ['PE', 'PhD Materials Science'], 'level' => 1],
            ['name' => 'Ahmed Hassan', 'title' => 'Senior Process Engineer', 'email' => 'a.hassan@refinery.com', 'phone' => '+1 (555) 200-0002', 'certs' => ['PE'], 'level' => 2],
            ['name' => 'David Park', 'title' => 'Mechanical Engineer', 'email' => 'd.park@refinery.com', 'phone' => '+1 (555) 200-0003', 'certs' => ['PE', 'API 510'], 'level' => 2],
            ['name' => 'Rachel Adams', 'title' => 'Corrosion Engineer', 'email' => 'r.adams@refinery.com', 'phone' => '+1 (555) 200-0004', 'certs' => ['NACE CIP Level 3', 'API 571'], 'level' => 2],
        ]
    ],
    'Inspection' => [
        'color' => '#f59e0b',
        'icon' => 'fa-search',
        'members' => [
            ['name' => 'Michael O\'Brien', 'title' => 'Chief Inspector', 'email' => 'm.obrien@refinery.com', 'phone' => '+1 (555) 300-0001', 'certs' => ['API 510', 'API 570', 'API 653', 'CWI'], 'level' => 1],
            ['name' => 'Jennifer Lee', 'title' => 'Senior Inspector', 'email' => 'j.lee@refinery.com', 'phone' => '+1 (555) 300-0002', 'certs' => ['API 510', 'API 570'], 'level' => 2],
            ['name' => 'Carlos Rodriguez', 'title' => 'Field Inspector', 'email' => 'c.rodriguez@refinery.com', 'phone' => '+1 (555) 300-0003', 'certs' => ['API 510', 'ASNT Level II UT'], 'level' => 2],
            ['name' => 'Emily Watson', 'title' => 'Field Inspector', 'email' => 'e.watson@refinery.com', 'phone' => '+1 (555) 300-0004', 'certs' => ['API 570', 'ASNT Level II MT'], 'level' => 3],
            ['name' => 'Kevin Nguyen', 'title' => 'NDE Technician Level III', 'email' => 'k.nguyen@refinery.com', 'phone' => '+1 (555) 300-0005', 'certs' => ['ASNT Level III UT', 'ASNT Level III RT', 'ASNT Level II PT/MT'], 'level' => 3],
            ['name' => 'Aisha Patel', 'title' => 'NDE Technician Level II', 'email' => 'a.patel@refinery.com', 'phone' => '+1 (555) 300-0006', 'certs' => ['ASNT Level II UT', 'ASNT Level II MT'], 'level' => 3],
        ]
    ],
    'HSE' => [
        'color' => '#ef4444',
        'icon' => 'fa-hard-hat',
        'members' => [
            ['name' => 'Patricia Brown', 'title' => 'HSE Manager', 'email' => 'p.brown@refinery.com', 'phone' => '+1 (555) 400-0001', 'certs' => ['CSP', 'CIH'], 'level' => 1],
            ['name' => 'Thomas Anderson', 'title' => 'Safety Officer', 'email' => 't.anderson@refinery.com', 'phone' => '+1 (555) 400-0002', 'certs' => ['OSHA 30', 'First Aid'], 'level' => 2],
            ['name' => 'Susan Martinez', 'title' => 'Environmental Specialist', 'email' => 's.martinez@refinery.com', 'phone' => '+1 (555) 400-0003', 'certs' => ['CHMM'], 'level' => 2],
        ]
    ],
    'Maintenance' => [
        'color' => '#22c55e',
        'icon' => 'fa-wrench',
        'members' => [
            ['name' => 'Richard Taylor', 'title' => 'Maintenance Manager', 'email' => 'r.taylor@refinery.com', 'phone' => '+1 (555) 500-0001', 'certs' => ['CMRP'], 'level' => 1],
            ['name' => 'Daniel Kim', 'title' => 'Maintenance Planner', 'email' => 'd.kim@refinery.com', 'phone' => '+1 (555) 500-0002', 'certs' => ['CMMS Certified'], 'level' => 2],
            ['name' => 'Steven Wright', 'title' => 'Lead Technician', 'email' => 's.wright@refinery.com', 'phone' => '+1 (555) 500-0003', 'certs' => ['Welding Certified', 'Rigging'], 'level' => 2],
        ]
    ],
];

$allMembers = [];
foreach ($departments as $deptName => $dept) {
    foreach ($dept['members'] as $m) {
        $m['department'] = $deptName;
        $m['deptColor'] = $dept['color'];
        $allMembers[] = $m;
    }
}

include INCLUDES_PATH . '/header.php';
?>

<style>
.ad-tabs .nav-link { font-weight: 600; color: #64748b; border: none; padding: 12px 24px; }
.ad-tabs .nav-link.active { color: #1a237e; border-bottom: 3px solid #1a237e; background: transparent; }

/* Org Chart */
.org-chart { overflow-x: auto; padding: 20px; }
.org-tree { display: flex; flex-direction: column; align-items: center; }
.org-node {
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    text-align: center;
    min-width: 180px;
    position: relative;
    transition: all 0.2s;
    cursor: default;
}
.org-node:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
.org-node-name { font-weight: 700; font-size: 0.9rem; color: #1e293b; }
.org-node-title { font-size: 0.75rem; color: #64748b; }

.org-dept-group {
    display: flex;
    gap: 40px;
    justify-content: center;
    position: relative;
    margin-top: 30px;
}
.org-dept-group::before {
    content: '';
    position: absolute;
    top: -30px;
    left: 50%;
    width: 0;
    height: 30px;
    border-left: 2px solid #cbd5e1;
}
.org-dept {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.org-connector-h {
    display: flex;
    gap: 0;
    position: relative;
    margin-top: 40px;
    padding-top: 20px;
}
.org-connector-h::before {
    content: '';
    position: absolute;
    top: 0;
    left: 10%;
    right: 10%;
    height: 2px;
    background: #cbd5e1;
}
.org-dept-col { position: relative; text-align: center; flex: 1; padding: 0 10px; }
.org-dept-col::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 50%;
    width: 0;
    height: 20px;
    border-left: 2px solid #cbd5e1;
}

.dept-header-node {
    border-left: 4px solid;
    text-align: left;
}
.dept-members { margin-top: 16px; display: flex; flex-direction: column; align-items: center; gap: 8px; }
.dept-member-node {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 0.8rem;
    min-width: 160px;
}

/* User Cards */
.user-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    padding: 24px;
    text-align: center;
    transition: all 0.2s;
    height: 100%;
}
.user-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
.user-avatar-lg {
    width: 72px; height: 72px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 0 auto 12px;
}
.user-card .user-name { font-weight: 700; font-size: 1rem; color: #1e293b; }
.user-card .user-title { font-size: 0.82rem; color: #64748b; margin-bottom: 8px; }
.user-card .user-dept { font-size: 0.75rem; }
.user-card .user-contact { font-size: 0.78rem; color: #94a3b8; }
.cert-tag { font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; background: #f1f5f9; color: #475569; display: inline-block; margin: 2px; }

.user-list-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}
.user-list-item:hover { background: #f8fafc; }

.ldap-form .form-label { font-weight: 600; font-size: 0.82rem; color: #475569; }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fas fa-sitemap me-2 text-primary"></i>Active Directory</h1>
        <p class="text-muted mb-0 mt-1">Organization structure, user directory, and LDAP configuration</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-import me-1"></i>Import CSV
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ldapModal">
            <i class="fas fa-plug me-1"></i>LDAP Settings
        </button>
    </div>
</div>

<!-- Tabs -->
<ul class="nav ad-tabs mb-4" id="adTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#orgChart">
            <i class="fas fa-sitemap me-1"></i>Organization Chart
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#directory">
            <i class="fas fa-address-book me-1"></i>User Directory
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#roleMapping">
            <i class="fas fa-user-shield me-1"></i>Role Mapping
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- Organization Chart Tab -->
    <div class="tab-pane fade show active" id="orgChart">
        <div class="card">
            <div class="card-body org-chart">
                <!-- Top Level -->
                <div class="org-tree">
                    <div class="org-node" style="border-color:#1a237e;border-width:3px;">
                        <div class="org-node-name">Robert Thompson</div>
                        <div class="org-node-title">Plant Manager</div>
                        <small class="badge bg-primary mt-1">Operations</small>
                    </div>

                    <!-- Department Level -->
                    <div class="org-connector-h">
                        <?php
                        $deptIndex = 0;
                        foreach ($departments as $deptName => $dept):
                            if ($deptName === 'Operations') continue; // Plant Manager already shown
                        ?>
                        <div class="org-dept-col">
                            <div class="org-node dept-header-node" style="border-left-color:<?= $dept['color'] ?>;">
                                <div class="org-node-name">
                                    <i class="fas <?= $dept['icon'] ?> me-1" style="color:<?= $dept['color'] ?>;"></i>
                                    <?= e($deptName) ?>
                                </div>
                                <div class="org-node-title"><?= e($dept['members'][0]['name']) ?></div>
                                <small style="color:<?= $dept['color'] ?>;"><?= e($dept['members'][0]['title']) ?></small>
                            </div>
                            <div class="dept-members">
                                <?php foreach (array_slice($dept['members'], 1) as $member): ?>
                                <div class="dept-member-node">
                                    <div class="fw-semibold"><?= e($member['name']) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;"><?= e($member['title']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $deptIndex++; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Directory Tab -->
    <div class="tab-pane fade" id="directory">
        <!-- Search & Filter Bar -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search by name, title, or email..." oninput="filterDirectory()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Department</label>
                        <select class="form-select" id="deptFilter" onchange="filterDirectory()">
                            <option value="">All Departments</option>
                            <?php foreach (array_keys($departments) as $d): ?>
                            <option value="<?= e($d) ?>"><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Certification</label>
                        <select class="form-select" id="certFilter" onchange="filterDirectory()">
                            <option value="">All Certifications</option>
                            <option value="API 510">API 510</option>
                            <option value="API 570">API 570</option>
                            <option value="API 653">API 653</option>
                            <option value="NACE">NACE</option>
                            <option value="ASNT">ASNT</option>
                            <option value="PE">PE</option>
                            <option value="CWI">CWI</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="btn-group w-100">
                            <button class="btn btn-outline-secondary active" id="gridViewBtn" onclick="setView('grid')"><i class="fas fa-th"></i></button>
                            <button class="btn btn-outline-secondary" id="listViewBtn" onclick="setView('list')"><i class="fas fa-list"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div class="row g-4" id="gridView">
            <?php foreach ($allMembers as $i => $member):
                $initials = '';
                $parts = explode(' ', $member['name']);
                foreach ($parts as $p) $initials .= strtoupper(substr(preg_replace("/[^a-zA-Z]/", '', $p), 0, 1));
            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 directory-item"
                 data-name="<?= e(strtolower($member['name'])) ?>"
                 data-title="<?= e(strtolower($member['title'])) ?>"
                 data-email="<?= e(strtolower($member['email'])) ?>"
                 data-dept="<?= e($member['department']) ?>"
                 data-certs="<?= e(strtolower(implode(',', $member['certs']))) ?>">
                <div class="user-card">
                    <div class="user-avatar-lg" style="background:<?= $member['deptColor'] ?>;"><?= $initials ?></div>
                    <div class="user-name"><?= e($member['name']) ?></div>
                    <div class="user-title"><?= e($member['title']) ?></div>
                    <div class="user-dept mb-2"><span class="badge" style="background:<?= $member['deptColor'] ?>20;color:<?= $member['deptColor'] ?>;"><?= e($member['department']) ?></span></div>
                    <div class="user-contact mb-2">
                        <div><i class="fas fa-envelope me-1"></i><?= e($member['email']) ?></div>
                        <div><i class="fas fa-phone me-1"></i><?= e($member['phone']) ?></div>
                    </div>
                    <?php if (!empty($member['certs'])): ?>
                    <div>
                        <?php foreach ($member['certs'] as $cert): ?>
                        <span class="cert-tag"><?= e($cert) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- List View (hidden by default) -->
        <div class="card d-none" id="listView">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Certifications</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allMembers as $member):
                                $initials = '';
                                $parts = explode(' ', $member['name']);
                                foreach ($parts as $p) $initials .= strtoupper(substr(preg_replace("/[^a-zA-Z]/", '', $p), 0, 1));
                            ?>
                            <tr class="directory-list-item"
                                data-name="<?= e(strtolower($member['name'])) ?>"
                                data-title="<?= e(strtolower($member['title'])) ?>"
                                data-email="<?= e(strtolower($member['email'])) ?>"
                                data-dept="<?= e($member['department']) ?>"
                                data-certs="<?= e(strtolower(implode(',', $member['certs']))) ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar-lg" style="background:<?= $member['deptColor'] ?>;width:36px;height:36px;font-size:0.75rem;"><?= $initials ?></div>
                                        <strong><?= e($member['name']) ?></strong>
                                    </div>
                                </td>
                                <td><?= e($member['title']) ?></td>
                                <td><span class="badge" style="background:<?= $member['deptColor'] ?>20;color:<?= $member['deptColor'] ?>;"><?= e($member['department']) ?></span></td>
                                <td><small><?= e($member['email']) ?></small></td>
                                <td><small><?= e($member['phone']) ?></small></td>
                                <td><?php foreach ($member['certs'] as $c): ?><span class="cert-tag"><?= e($c) ?></span><?php endforeach; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Mapping Tab -->
    <div class="tab-pane fade" id="roleMapping">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><i class="fas fa-user-shield me-2"></i>AD Group to RBI Role Mapping</div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Map Active Directory groups to RBI Engineering Suite roles. Users will inherit permissions based on their AD group membership.</p>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr><th>AD Group</th><th></th><th>RBI System Role</th><th>Permissions</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $roleMappings = [
                                        ['ad' => 'CN=RBI-Admins,OU=Groups,DC=refinery,DC=com', 'role' => 'Administrator', 'perms' => 'Full Access'],
                                        ['ad' => 'CN=RBI-Engineers,OU=Groups,DC=refinery,DC=com', 'role' => 'Engineer', 'perms' => 'Edit Assessments, Analytics, Reports'],
                                        ['ad' => 'CN=RBI-Inspectors,OU=Groups,DC=refinery,DC=com', 'role' => 'Inspector', 'perms' => 'Inspections, Readings, Field Data'],
                                        ['ad' => 'CN=RBI-Managers,OU=Groups,DC=refinery,DC=com', 'role' => 'Manager', 'perms' => 'View All, Approve, Reports'],
                                        ['ad' => 'CN=RBI-Viewers,OU=Groups,DC=refinery,DC=com', 'role' => 'Viewer', 'perms' => 'Read Only Access'],
                                    ];
                                    foreach ($roleMappings as $rm):
                                    ?>
                                    <tr>
                                        <td><code class="small"><?= e($rm['ad']) ?></code></td>
                                        <td><i class="fas fa-arrow-right text-muted"></i></td>
                                        <td><span class="badge bg-primary"><?= e($rm['role']) ?></span></td>
                                        <td><small class="text-muted"><?= e($rm['perms']) ?></small></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i>Add Mapping</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-info-circle me-2"></i>Role Summary</div>
                    <div class="card-body">
                        <?php
                        $roles = [
                            ['name' => 'Administrator', 'count' => 2, 'color' => 'danger'],
                            ['name' => 'Engineer', 'count' => 5, 'color' => 'primary'],
                            ['name' => 'Inspector', 'count' => 8, 'color' => 'warning'],
                            ['name' => 'Manager', 'count' => 3, 'color' => 'success'],
                            ['name' => 'Viewer', 'count' => 4, 'color' => 'secondary'],
                        ];
                        foreach ($roles as $role):
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-<?= $role['color'] ?>"><?= $role['name'] ?></span>
                            <span class="fw-bold"><?= $role['count'] ?> users</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><i class="fas fa-sync me-2"></i>Sync Status</div>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-cloud-download-alt fa-3x text-muted mb-3" style="opacity:0.3;"></i>
                        <p class="text-muted mb-2">AD Sync Not Configured</p>
                        <small class="text-muted">Configure LDAP settings to enable automatic user synchronization.</small>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ldapModal">Configure LDAP</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LDAP Configuration Modal -->
<div class="modal fade" id="ldapModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plug me-2"></i>LDAP / Active Directory Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ldap-form">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">LDAP Server URL</label>
                        <input type="text" class="form-control" placeholder="ldap://dc01.refinery.com" value="ldap://dc01.refinery.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Port</label>
                        <input type="text" class="form-control" placeholder="389" value="389">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Base DN</label>
                        <input type="text" class="form-control" placeholder="DC=refinery,DC=com" value="DC=refinery,DC=com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bind Username</label>
                        <input type="text" class="form-control" placeholder="CN=svc-rbi,OU=Service,DC=refinery,DC=com" value="CN=svc-rbi,OU=Service,DC=refinery,DC=com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bind Password</label>
                        <input type="password" class="form-control" placeholder="Enter password" value="••••••••">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">User Search Filter</label>
                        <input type="text" class="form-control font-monospace" value="(&(objectClass=person)(memberOf=CN=RBI-Users,OU=Groups,DC=refinery,DC=com))">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username Attribute</label>
                        <input type="text" class="form-control" value="sAMAccountName">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Attribute</label>
                        <input type="text" class="form-control" value="mail">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ldapSSL">
                            <label class="form-check-label" for="ldapSSL">Use SSL/TLS (LDAPS)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoSync">
                            <label class="form-check-label" for="autoSync">Enable automatic sync (every 6 hours)</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="alert('Connection test successful!')"><i class="fas fa-plug me-1"></i>Test Connection</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><i class="fas fa-save me-1"></i>Save Configuration</button>
            </div>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Import Users from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Upload a CSV file with columns: Name, Title, Department, Email, Phone, Certifications</p>
                <div class="mb-3">
                    <label class="form-label">CSV File</label>
                    <input type="file" class="form-control" accept=".csv">
                </div>
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>Template:</strong> Name,Title,Department,Email,Phone,Certifications<br>
                    John Doe,Senior Inspector,Inspection,j.doe@company.com,555-1234,"API 510,API 570"
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="alert('Import feature ready for implementation.'); bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();">Import</button>
            </div>
        </div>
    </div>
</div>

<script>
function filterDirectory() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const dept = document.getElementById('deptFilter').value;
    const cert = document.getElementById('certFilter').value.toLowerCase();

    // Filter grid view
    document.querySelectorAll('.directory-item').forEach(item => {
        const name = item.dataset.name;
        const title = item.dataset.title;
        const email = item.dataset.email;
        const itemDept = item.dataset.dept;
        const certs = item.dataset.certs;

        const matchSearch = !search || name.includes(search) || title.includes(search) || email.includes(search);
        const matchDept = !dept || itemDept === dept;
        const matchCert = !cert || certs.includes(cert);

        item.style.display = (matchSearch && matchDept && matchCert) ? '' : 'none';
    });

    // Filter list view
    document.querySelectorAll('.directory-list-item').forEach(item => {
        const name = item.dataset.name;
        const title = item.dataset.title;
        const email = item.dataset.email;
        const itemDept = item.dataset.dept;
        const certs = item.dataset.certs;

        const matchSearch = !search || name.includes(search) || title.includes(search) || email.includes(search);
        const matchDept = !dept || itemDept === dept;
        const matchCert = !cert || certs.includes(cert);

        item.style.display = (matchSearch && matchDept && matchCert) ? '' : 'none';
    });
}

function setView(view) {
    if (view === 'grid') {
        document.getElementById('gridView').classList.remove('d-none');
        document.getElementById('listView').classList.add('d-none');
        document.getElementById('gridViewBtn').classList.add('active');
        document.getElementById('listViewBtn').classList.remove('active');
    } else {
        document.getElementById('gridView').classList.add('d-none');
        document.getElementById('listView').classList.remove('d-none');
        document.getElementById('gridViewBtn').classList.remove('active');
        document.getElementById('listViewBtn').classList.add('active');
    }
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
