<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();

// Filtre de dates
$data_inici = $_GET['data_inici'] ?? date('Y-m-01');
$data_fi    = $_GET['data_fi'] ?? date('Y-m-d');

$data_inici = htmlspecialchars($data_inici);
$data_fi    = htmlspecialchars($data_fi);

// Hores per projecte
$stmt = $pdo->prepare('
    SELECT p.nom AS projecte, p.hores_estimades,
           COALESCE(SUM(r.total_hores), 0) AS hores_consumides
    FROM projectes p
    LEFT JOIN registres_hores r ON p.id = r.projecte_id
        AND r.data BETWEEN :inici AND :fi
        AND r.total_hores IS NOT NULL
    GROUP BY p.id, p.nom, p.hores_estimades
    ORDER BY hores_consumides DESC
');
$stmt->execute([':inici' => $data_inici, ':fi' => $data_fi]);
$report_projectes = $stmt->fetchAll();

// Hores per empleat
$stmt = $pdo->prepare('
    SELECT u.nom, u.cognoms,
           COALESCE(SUM(r.total_hores), 0) AS total_hores,
           COUNT(DISTINCT r.data) AS dies_treballats
    FROM usuaris u
    LEFT JOIN registres_hores r ON u.id = r.usuari_id
        AND r.data BETWEEN :inici AND :fi
        AND r.total_hores IS NOT NULL
    WHERE u.rol = :rol AND u.actiu = 1
    GROUP BY u.id, u.nom, u.cognoms
    ORDER BY total_hores DESC
');
$stmt->execute([':inici' => $data_inici, ':fi' => $data_fi, ':rol' => 'empleat']);
$report_empleats = $stmt->fetchAll();

// Dades per als gràfics
$labels_projectes = [];
$hores_consumides = [];
$hores_estimades  = [];
foreach ($report_projectes as $p) {
    $labels_projectes[] = $p['projecte'];
    $hores_consumides[]  = (float)$p['hores_consumides'];
    $hores_estimades[]   = (float)$p['hores_estimades'];
}

$labels_empleats  = [];
$hores_empleats   = [];
foreach ($report_empleats as $e) {
    $labels_empleats[] = $e['nom'] . ' ' . $e['cognoms'];
    $hores_empleats[]  = (float)$e['total_hores'];
}
?>

<h2 class="mb-4">📈 Reports</h2>

<!-- Filtre dates -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Data inici</label>
                <input type="date" name="data_inici" class="form-control" value="<?= $data_inici ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Data fi</label>
                <input type="date" name="data_fi" class="form-control" value="<?= $data_fi ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Taula projectes -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">📁 Hores per Projecte</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Projecte</th>
                    <th>Hores estimades</th>
                    <th>Hores consumides</th>
                    <th>Desviació</th>
                    <th>Estat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_projectes as $p): ?>
                    <?php
                    $desviacio = $p['hores_estimades'] > 0
                        ? (($p['hores_consumides'] - $p['hores_estimades']) / $p['hores_estimades']) * 100
                        : 0;
                    $alerta = $p['hores_consumides'] > $p['hores_estimades'];
                    ?>
                    <tr class="<?= $alerta ? 'table-danger' : '' ?>">
                        <td><?= htmlspecialchars($p['projecte']) ?></td>
                        <td><?= number_format($p['hores_estimades'], 1) ?>h</td>
                        <td><?= number_format($p['hores_consumides'], 1) ?>h</td>
                        <td class="<?= $alerta ? 'text-danger fw-bold' : 'text-success' ?>">
                            <?= $desviacio > 0 ? '+' : '' ?><?= number_format($desviacio, 1) ?>%
                        </td>
                        <td>
                            <?php if ($alerta): ?>
                                <span class="badge bg-danger">⚠️ Sobrepassat</span>
                            <?php else: ?>
                                <span class="badge bg-success">✅ OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Gràfic barres projectes -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">📊 Hores estimades vs consumides per projecte</h5>
    </div>
    <div class="card-body">
        <canvas id="graficProjectes" height="100"></canvas>
    </div>
</div>

<!-- Gràfic pastís empleats -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">🥧 Distribució d'hores per empleat</h5>
    </div>
    <div class="card-body">
        <canvas id="graficEmpleat" height="100"></canvas>
    </div>
</div>

<!-- Taula empleats -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">👥 Hores per Empleat</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Empleat</th>
                    <th>Dies treballats</th>
                    <th>Total hores</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_empleats as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['nom'] . ' ' . $e['cognoms']) ?></td>
                        <td><?= $e['dies_treballats'] ?></td>
                        <td><?= number_format($e['total_hores'], 1) ?>h</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labelsProjectes = <?= json_encode($labels_projectes) ?>;
const horesConsumides = <?= json_encode($hores_consumides) ?>;
const horesEstimades  = <?= json_encode($hores_estimades) ?>;
const labelsEmpleat   = <?= json_encode($labels_empleats) ?>;
const horesEmpleat    = <?= json_encode($hores_empleats) ?>;

// Gràfic barres
new Chart(document.getElementById('graficProjectes'), {
    type: 'bar',
    data: {
        labels: labelsProjectes,
        datasets: [
            {
                label: 'Hores estimades',
                data: horesEstimades,
                backgroundColor: 'rgba(13, 110, 253, 0.5)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            },
            {
                label: 'Hores consumides',
                data: horesConsumides,
                backgroundColor: 'rgba(220, 53, 69, 0.5)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});

// Gràfic pastís
new Chart(document.getElementById('graficEmpleat'), {
    type: 'pie',
    data: {
        labels: labelsEmpleat,
        datasets: [{
            data: horesEmpleat,
            backgroundColor: [
                '#0d6efd','#dc3545','#198754','#ffc107',
                '#0dcaf0','#6f42c1','#fd7e14','#20c997'
            ]
        }]
    },
    options: { responsive: true }
});
</script>

<?php require_once '../includes/footer.php'; ?>