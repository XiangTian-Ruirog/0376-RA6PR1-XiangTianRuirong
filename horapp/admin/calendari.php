<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();

// Obtenir tots els registres de tots els empleats
$stmt = $pdo->prepare('
    SELECT r.data, u.nom, u.cognoms,
           SUM(r.total_hores) AS total_hores,
           GROUP_CONCAT(DISTINCT p.nom SEPARATOR ", ") AS projectes
    FROM registres_hores r
    JOIN usuaris u ON r.usuari_id = u.id
    JOIN projectes p ON r.projecte_id = p.id
    WHERE u.rol = :rol AND u.actiu = 1
    GROUP BY r.data, u.id, u.nom, u.cognoms
    ORDER BY r.data DESC
');
$stmt->execute([':rol' => 'empleat']);
$registres = $stmt->fetchAll();

// Comptar quants empleats actius hi ha
$stmt = $pdo->prepare('SELECT COUNT(id) AS total FROM usuaris WHERE rol = :rol AND actiu = 1');
$stmt->execute([':rol' => 'empleat']);
$total_empleats = $stmt->fetch()['total'];

// Agrupar per data per saber quants han fitxat cada dia
$per_data = [];
foreach ($registres as $r) {
    $data = $r['data'];
    if (!isset($per_data[$data])) {
        $per_data[$data] = ['count' => 0, 'noms' => []];
    }
    $per_data[$data]['count']++;
    $per_data[$data]['noms'][] = $r['nom'] . ' ' . $r['cognoms'] . ' (' . number_format($r['total_hores'], 1) . 'h)';
}

// Preparar events per al calendari
$events = [];
foreach ($per_data as $data => $info) {
    $percentatge = $total_empleats > 0 ? ($info['count'] / $total_empleats) * 100 : 0;
    if ($percentatge >= 80) {
        $color = '#198754';
    } elseif ($percentatge >= 50) {
        $color = '#ffc107';
    } else {
        $color = '#dc3545';
    }
    $events[] = [
        'title'   => $info['count'] . '/' . $total_empleats . ' empleats',
        'start'   => $data,
        'color'   => $color,
        'extendedProps' => [
            'noms' => implode("\n", $info['noms'])
        ]
    ];
}
?>

<h2 class="mb-4">📅 Calendari General</h2>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex gap-3 align-items-center">
            <span><span class="badge bg-success">■</span> +80% han fitxat</span>
            <span><span class="badge bg-warning text-dark">■</span> 50-80% han fitxat</span>
            <span><span class="badge bg-danger">■</span> Menys del 50%</span>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div id="calendari"></div>
    </div>
</div>

<!-- Modal detall dia -->
<div class="modal fade" id="modalDia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitol">Detall del dia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="modalCos" style="white-space: pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendari');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ca',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek'
        },
        events: <?= json_encode($events) ?>,
        eventClick: function(info) {
            document.getElementById('modalTitol').textContent = info.event.startStr + ' - ' + info.event.title;
            document.getElementById('modalCos').textContent = info.event.extendedProps.noms;
            new bootstrap.Modal(document.getElementById('modalDia')).show();
        }
    });
    calendar.render();
});
</script>

<?php require_once '../includes/footer.php'; ?>