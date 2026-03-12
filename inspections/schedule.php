<?php
/**
 * Inspection Schedule - Calendar View - RBI Engineering Suite
 */
$pageTitle = 'Inspection Schedule';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Inspection Schedule', 'url' => '#']
];
$extraCss = '<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">';
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Inspection Schedule</h5>
    <div>
        <span class="badge bg-danger me-1">High Priority</span>
        <span class="badge bg-warning text-dark me-1">Medium Priority</span>
        <span class="badge bg-info me-1">Normal</span>
        <span class="badge bg-secondary">Completed</span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Inspection Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary" id="eventModalLink">View Task</a>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var cal = new FullCalendar.Calendar(document.getElementById("calendar"), {
        initialView: "dayGridMonth",
        headerToolbar: { left: "prev,next today", center: "title", right: "dayGridMonth,timeGridWeek,listWeek" },
        height: 700,
        events: [
            { title: "V-101 UT Grid Survey", start: "2026-03-18", color: "#dc3545", extendedProps: { asset: "V-101 Separator", strategy: "UT Grid", inspector: "John Smith", priority: "High" } },
            { title: "E-205 PAUT Inspection", start: "2026-03-22", color: "#ffc107", textColor: "#333", extendedProps: { asset: "E-205 Exchanger", strategy: "PAUT", inspector: "Sarah Jones", priority: "Medium" } },
            { title: "P-302A Visual + UT", start: "2026-03-28", color: "#17a2b8", extendedProps: { asset: "P-302A Pump", strategy: "Visual + UT Spot", inspector: "Mike Chen", priority: "Normal" } },
            { title: "T-401 Floor Scan", start: "2026-04-02", color: "#dc3545", extendedProps: { asset: "T-401 Tank", strategy: "MFL Floor Scan", inspector: "John Smith", priority: "High" } },
            { title: "C-102 Internal Visual", start: "2026-04-15", color: "#ffc107", textColor: "#333", extendedProps: { asset: "C-102 Column", strategy: "Internal Visual + UT", inspector: "Sarah Jones", priority: "Medium" } },
            { title: "V-101 External CUI Check", start: "2026-05-10", color: "#17a2b8", extendedProps: { asset: "V-101 Separator", strategy: "CUI Inspection", inspector: "Mike Chen", priority: "Normal" } },
        ],
        eventClick: function(info) {
            let p = info.event.extendedProps;
            document.getElementById("eventModalTitle").textContent = info.event.title;
            document.getElementById("eventModalBody").innerHTML = `
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted">Asset:</td><td class="fw-semibold">${p.asset}</td></tr>
                    <tr><td class="text-muted">Strategy:</td><td>${p.strategy}</td></tr>
                    <tr><td class="text-muted">Date:</td><td>${info.event.start.toLocaleDateString()}</td></tr>
                    <tr><td class="text-muted">Inspector:</td><td>${p.inspector}</td></tr>
                    <tr><td class="text-muted">Priority:</td><td><span class="badge bg-${p.priority==="High"?"danger":p.priority==="Medium"?"warning text-dark":"info"}">${p.priority}</span></td></tr>
                </table>`;
            new bootstrap.Modal(document.getElementById("eventModal")).show();
        }
    });
    cal.render();
});
</script>';
require_once INCLUDES_PATH . '/footer.php';
