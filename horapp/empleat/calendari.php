<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('empleat');

$pdo = getDB();
$usuari_id = $_SESSION['usuari_id'];

// Obtenir tots els registres de l'empleat
$stmt = $pdo->prepare('
    SELECT r.data, SUM(r.total_hores) AS total_hores, 
           GROUP_CONCAT(p.nom SEPARATOR ", ") AS projectes
    FROM registres_hores r
    JOIN projectes p ON r.projecte_id = p.id
    WHERE r.usuari_id = :uid
    GROUP BY r.data
');
$stmt->execute([':uid' => $usuari_id]);
$registres = $stmt->fetchAll();

// Preparar dades per al calendari
$events = [];
foreach ($registres as $r) {
    $hores = $r['total_hores'] ?? 0;
    $color = $hores >= 7 ? '#198754' : ($hores > 0 ? '#ffc107' : '#dc3545');
    $events[] = [
        'title' => $hores ? number_format($hores, 1) . 'h - ' . $r['projectes'] : 'Sense hores',
        'start' => $r['data'],
        'color' => $color,
    ];
}
?>

<h2 class="mb-4">📅 El meu Calendari</h2>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex gap-3 align-items-center">
            <span><span class="badge bg-success">■</span> 7h o més</span>
            <span><span class="badge bg-warning text-dark">■</span> Menys de 7h</span>
            <span><span class="badge bg-danger">■</span> Sense registre</span>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div id="calendari"></div>
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
            alert(info.event.title);
        }
    });
    calendar.render();
});
</script>

<?php require_once '../includes/footer.php'; ?>