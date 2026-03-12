<?php
/**
 * Equipment Hierarchy - RBI Engineering Suite
 */
$pageTitle = 'Equipment Hierarchy';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Equipment Hierarchy', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Equipment Hierarchy</h5>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-1" onclick="expandAll()"><i class="bi bi-arrows-expand me-1"></i>Expand All</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="collapseAll()"><i class="bi bi-arrows-collapse me-1"></i>Collapse All</button>
    </div>
</div>

<div class="row">
    <!-- Tree Panel -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-nested me-2"></i>Asset Tree</div>
            <div class="card-body">
                <div id="hierarchy-tree">
                    <ul class="tree-root list-unstyled">
                        <!-- Plant Level -->
                        <li class="tree-node">
                            <div class="tree-toggle d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="toggleNode(this)" data-id="plant-1">
                                <i class="bi bi-chevron-down text-muted small"></i>
                                <i class="bi bi-building text-primary"></i>
                                <span class="fw-semibold">Refinery Complex Alpha</span>
                                <span class="badge bg-secondary ms-auto">4 Units</span>
                            </div>
                            <ul class="tree-children list-unstyled ps-4">
                                <!-- Unit 100 -->
                                <li class="tree-node">
                                    <div class="tree-toggle d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="toggleNode(this)">
                                        <i class="bi bi-chevron-down text-muted small"></i>
                                        <i class="bi bi-gear text-success"></i>
                                        <span>Unit 100 - Crude Distillation</span>
                                    </div>
                                    <ul class="tree-children list-unstyled ps-4">
                                        <li class="tree-node">
                                            <div class="tree-toggle d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="toggleNode(this)">
                                                <i class="bi bi-chevron-right text-muted small"></i>
                                                <i class="bi bi-hdd-stack text-info"></i>
                                                <span>Feed System</span>
                                            </div>
                                            <ul class="tree-children list-unstyled ps-4 d-none">
                                                <li><div class="tree-leaf d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="showAsset(1)"><i class="bi bi-box text-warning"></i> <span>V-101 Inlet Separator</span> <span class="badge bg-danger ms-auto">High</span></div></li>
                                                <li><div class="tree-leaf d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="showAsset(5)"><i class="bi bi-box text-warning"></i> <span>C-102 Atmospheric Column</span> <span class="badge bg-warning text-dark ms-auto">Med-High</span></div></li>
                                                <li><div class="tree-leaf d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="showAsset(6)"><i class="bi bi-box text-warning"></i> <span>E-103 Preheater</span> <span class="badge bg-info ms-auto">Medium</span></div></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                                <!-- Unit 200 -->
                                <li class="tree-node">
                                    <div class="tree-toggle d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="toggleNode(this)">
                                        <i class="bi bi-chevron-right text-muted small"></i>
                                        <i class="bi bi-gear text-success"></i>
                                        <span>Unit 200 - Catalytic Reformer</span>
                                    </div>
                                    <ul class="tree-children list-unstyled ps-4 d-none">
                                        <li><div class="tree-leaf d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="showAsset(2)"><i class="bi bi-box text-warning"></i> <span>E-205 Feed/Effluent Exchanger</span> <span class="badge bg-warning text-dark ms-auto">Med-High</span></div></li>
                                        <li><div class="tree-leaf d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="showAsset(7)"><i class="bi bi-box text-warning"></i> <span>R-201 Reactor</span> <span class="badge bg-danger ms-auto">High</span></div></li>
                                    </ul>
                                </li>
                                <!-- Unit 300 -->
                                <li class="tree-node">
                                    <div class="tree-toggle d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="toggleNode(this)">
                                        <i class="bi bi-chevron-right text-muted small"></i>
                                        <i class="bi bi-gear text-success"></i>
                                        <span>Unit 300 - Hydrodesulfurization</span>
                                    </div>
                                    <ul class="tree-children list-unstyled ps-4 d-none">
                                        <li><div class="tree-leaf d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="showAsset(4)"><i class="bi bi-box text-warning"></i> <span>P-302A HDS Feed Pump</span> <span class="badge bg-info ms-auto">Medium</span></div></li>
                                    </ul>
                                </li>
                                <!-- Unit 400 -->
                                <li class="tree-node">
                                    <div class="tree-toggle d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="toggleNode(this)">
                                        <i class="bi bi-chevron-right text-muted small"></i>
                                        <i class="bi bi-gear text-success"></i>
                                        <span>Unit 400 - Tank Farm</span>
                                    </div>
                                    <ul class="tree-children list-unstyled ps-4 d-none">
                                        <li><div class="tree-leaf d-flex align-items-center gap-2 py-1 px-2 rounded cursor-pointer" onclick="showAsset(3)"><i class="bi bi-box text-warning"></i> <span>T-401 Crude Storage Tank</span> <span class="badge bg-danger ms-auto">V.High</span></div></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Panel -->
    <div class="col-lg-7">
        <div class="card" id="asset-detail-panel">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Asset Details</div>
            <div class="card-body" id="asset-detail-content">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-cursor-fill fs-1 d-block mb-2"></i>
                    <p>Select an asset from the hierarchy tree to view details.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
function toggleNode(el) {
    let children = el.nextElementSibling;
    let icon = el.querySelector(".bi-chevron-right, .bi-chevron-down");
    if (children) {
        children.classList.toggle("d-none");
        if (icon) {
            icon.classList.toggle("bi-chevron-right");
            icon.classList.toggle("bi-chevron-down");
        }
    }
}
function expandAll() {
    document.querySelectorAll(".tree-children").forEach(el => el.classList.remove("d-none"));
    document.querySelectorAll(".tree-toggle .bi-chevron-right").forEach(el => {
        el.classList.remove("bi-chevron-right"); el.classList.add("bi-chevron-down");
    });
}
function collapseAll() {
    document.querySelectorAll(".tree-children").forEach((el,i) => { if(i>0) el.classList.add("d-none"); });
    document.querySelectorAll(".tree-toggle .bi-chevron-down").forEach(el => {
        el.classList.remove("bi-chevron-down"); el.classList.add("bi-chevron-right");
    });
}
function showAsset(id) {
    document.querySelectorAll(".tree-leaf,.tree-toggle").forEach(el => el.classList.remove("bg-primary","bg-opacity-10"));
    event.currentTarget.classList.add("bg-primary","bg-opacity-10");
    document.getElementById("asset-detail-content").innerHTML = `
        <div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading asset #${id}...</div>
    `;
    // In production, fetch via AJAX: fetch(BASE_URL + "/api/assets.php?id=" + id)
    setTimeout(() => {
        document.getElementById("asset-detail-content").innerHTML = `
            <div class="row g-3">
                <div class="col-md-6"><strong>Asset Tag:</strong> V-101</div>
                <div class="col-md-6"><strong>Name:</strong> Inlet Separator</div>
                <div class="col-md-6"><strong>Type:</strong> Pressure Vessel</div>
                <div class="col-md-6"><strong>Material:</strong> SA-516 Gr 70</div>
                <div class="col-md-6"><strong>Design Pressure:</strong> 1.5 MPa</div>
                <div class="col-md-6"><strong>Design Temp:</strong> 150 C</div>
                <div class="col-md-6"><strong>Nominal Thickness:</strong> 12.7 mm</div>
                <div class="col-md-6"><strong>Min Required:</strong> 4.2 mm</div>
                <div class="col-md-6"><strong>Install Date:</strong> Mar 2005</div>
                <div class="col-md-6"><strong>Risk Level:</strong> <span class="badge bg-danger">High</span></div>
                <div class="col-12 mt-3"><a href="' . BASE_URL . '/assets/view.php?id=${id}" class="btn btn-primary btn-sm"><i class="bi bi-eye me-1"></i>View Full Details</a></div>
            </div>`;
    }, 300);
}
</script>
<style>.cursor-pointer{cursor:pointer}.tree-leaf:hover,.tree-toggle:hover{background:rgba(0,0,0,.03)}</style>';
require_once INCLUDES_PATH . '/footer.php';
