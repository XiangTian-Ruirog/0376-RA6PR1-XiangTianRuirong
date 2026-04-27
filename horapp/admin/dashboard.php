<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();

// Total empleats actius
$stmt = $pdo->prepare('SELECT COUNT(id) AS total FROM usuaris WHERE rol = :rol AND actiu = 1');
$stmt->execute([':rol' => 'empleat']);
$total_empleats = $stmt->fetch()['total'];

// Empleats que han fitxat avui
$stmt = $pdo->prepare('
    SELECT COUNT(DISTINCT usuari_id) AS total
    FROM registres_hores
    WHERE data = CURDATE()
');
$stmt->execute();
$fitxats_avui = $stmt->fetch()['total'];

// Empleats que NO han fitxat avui
$no_fitxats = $total_empleats - $fitxats_avui;

// Hores totals registrades avui
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(total_hores), 0) AS total
    FROM registres_hores
    WHERE data = CURDATE() AND total_hores IS NOT NULL
');
$stmt->execute();
$hores_avui = $stmt->fetch()['total'];

// Últims registres del dia
$stmt = $pdo->prepare('
    SELECT u.nom, u.cognoms, p.nom AS projecte, r.hora_entrada, r.hora_sortida, r.total_hores
    FROM registres_hores r
    JOIN usuaris u ON r.usuari_id = u.id
    JOIN projectes p ON r.projecte_id = p.id
    WHERE r.data = CURDATE()
    ORDER BY r.hora_entrada DESC
');
$stmt->execute();
$registres_avui = $stmt->fetchAll();
?>

<h2 class="mb-4">📊 Dashboard Admin</h2>

<!-- Targetes resum -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body text-center">
                <h3><?= $total_empleats ?></h3>
                <p class="mb-0">Empleats actius</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body text-center">
                <h3><?= $fitxats_avui ?></h3>
                <p class="mb-0">Han fitxat avui</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger shadow-sm">
            <div class="card-body text-center">
                <h3><?= $no_fitxats ?></h3>
                <p class="mb-0">No han fitxat avui</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info shadow-sm">
            <div class="card-body text-center">
                <h3><?= number_format($hores_avui, 1) ?>h</h3>
                <p class="mb-0">Hores totals avui</p>
            </div>
        </div>
    </div>
</div>

<!-- Registres d'avui -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">📋 Activitat d'avui</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Empleat</th>
                    <th>Projecte</th>
                    <th>Entrada</th>
                    <th>Sortida</th>
                    <th>Total hores</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registres_avui)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Sense activitat avui</td></tr>
                <?php else: ?>
                    <?php foreach ($registres_avui as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nom'] . ' ' . $r['cognoms']) ?></td>
                            <td><?= htmlspecialchars($r['projecte']) ?></td>
                            <td><?= htmlspecialchars($r['hora_entrada']) ?></td>
                            <td><?= $r['hora_sortida'] ? htmlspecialchars($r['hora_sortida']) : '<span class="badge bg-success">Actiu</span>' ?></td>
                            <td><?= $r['total_hores'] ? $r['total_hores'] . 'h' : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>