<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();

$msg = htmlspecialchars($_GET['msg'] ?? '');
$error = htmlspecialchars($_GET['error'] ?? '');

// Obtenir tots els projectes amb total hores consumides
$stmt = $pdo->prepare('
    SELECT p.id, p.nom, p.descripcio, p.hores_estimades, p.actiu,
           COALESCE(SUM(r.total_hores), 0) AS hores_consumides
    FROM projectes p
    LEFT JOIN registres_hores r ON p.id = r.projecte_id
    GROUP BY p.id, p.nom, p.descripcio, p.hores_estimades, p.actiu
    ORDER BY p.actiu DESC, p.nom ASC
');
$stmt->execute();
$projectes = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>📁 Gestió de Projectes</h2>
    <a href="projecte_form.php" class="btn btn-primary">+ Nou Projecte</a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Descripció</th>
                    <th>Hores estimades</th>
                    <th>Hores consumides</th>
                    <th>Desviació</th>
                    <th>Estat</th>
                    <th>Accions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projectes as $p): ?>
                    <?php
                    $desviacio = $p['hores_estimades'] > 0
                        ? (($p['hores_consumides'] - $p['hores_estimades']) / $p['hores_estimades']) * 100
                        : 0;
                    $desviacio_class = $desviacio > 0 ? 'text-danger fw-bold' : 'text-success';
                    ?>
                    <tr class="<?= !$p['actiu'] ? 'table-secondary' : '' ?>">
                        <td><?= htmlspecialchars($p['nom']) ?></td>
                        <td><?= htmlspecialchars($p['descripcio'] ?? '') ?></td>
                        <td><?= number_format($p['hores_estimades'], 1) ?>h</td>
                        <td><?= number_format($p['hores_consumides'], 1) ?>h</td>
                        <td class="<?= $desviacio_class ?>">
                            <?= $desviacio > 0 ? '+' : '' ?><?= number_format($desviacio, 1) ?>%
                        </td>
                        <td>
                            <span class="badge <?= $p['actiu'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $p['actiu'] ? 'Actiu' : 'Inactiu' ?>
                            </span>
                        </td>
                        <td>
                            <a href="projecte_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <a href="projecte_delete.php?id=<?= $p['id'] ?>"
                               class="btn btn-sm <?= $p['actiu'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                               onclick="return confirm('Estàs segur?')">
                                <?= $p['actiu'] ? 'Desactivar' : 'Activar' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>