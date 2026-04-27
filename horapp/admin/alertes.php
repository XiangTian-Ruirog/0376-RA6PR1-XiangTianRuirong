<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();

// Empleats que NO han fitxat avui
$stmt = $pdo->prepare('
    SELECT u.id, u.nom, u.cognoms, u.email
    FROM usuaris u
    WHERE u.rol = :rol AND u.actiu = 1
    AND u.id NOT IN (
        SELECT DISTINCT r.usuari_id
        FROM registres_hores r
        WHERE r.data = CURDATE()
    )
    ORDER BY u.cognoms, u.nom
');
$stmt->execute([':rol' => 'empleat']);
$no_fitxats = $stmt->fetchAll();

// Empleats que han fitxat menys de 7 hores avui (ja han fitxat sortida)
$stmt = $pdo->prepare('
    SELECT u.nom, u.cognoms, u.email,
           SUM(r.total_hores) AS total_hores
    FROM registres_hores r
    JOIN usuaris u ON r.usuari_id = u.id
    WHERE r.data = CURDATE()
    AND r.hora_sortida IS NOT NULL
    GROUP BY u.id, u.nom, u.cognoms, u.email
    HAVING SUM(r.total_hores) < 7
    ORDER BY total_hores ASC
');
$stmt->execute();
$poques_hores = $stmt->fetchAll();

// Empleats que estan treballant ara mateix
$stmt = $pdo->prepare('
    SELECT u.nom, u.cognoms, r.hora_entrada, p.nom AS projecte
    FROM registres_hores r
    JOIN usuaris u ON r.usuari_id = u.id
    JOIN projectes p ON r.projecte_id = p.id
    WHERE r.data = CURDATE() AND r.hora_sortida IS NULL
    ORDER BY r.hora_entrada ASC
');
$stmt->execute();
$treballant_ara = $stmt->fetchAll();
?>

<h2 class="mb-4">🚨 Alertes i Llista Vermella</h2>

<!-- Treballant ara -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">✅ Treballant ara mateix (<?= count($treballant_ara) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Empleat</th>
                    <th>Projecte</th>
                    <th>Hora entrada</th>
                    <th>Hores acumulades</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($treballant_ara)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Ningú treballant ara mateix</td></tr>
                <?php else: ?>
                    <?php foreach ($treballant_ara as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['nom'] . ' ' . $t['cognoms']) ?></td>
                            <td><?= htmlspecialchars($t['projecte']) ?></td>
                            <td><?= htmlspecialchars($t['hora_entrada']) ?></td>
                            <td>
                                <?php
                                $entrada = new DateTime($t['hora_entrada']);
                                $ara = new DateTime();
                                $diff = $entrada->diff($ara);
                                echo $diff->h . 'h ' . $diff->i . 'min';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- No han fitxat avui -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">🔴 No han fitxat avui (<?= count($no_fitxats) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Cognoms</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($no_fitxats)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">Tots els empleats han fitxat avui 🎉</td></tr>
                <?php else: ?>
                    <?php foreach ($no_fitxats as $e): ?>
                        <tr class="table-danger">
                            <td><?= htmlspecialchars($e['nom']) ?></td>
                            <td><?= htmlspecialchars($e['cognoms']) ?></td>
                            <td><?= htmlspecialchars($e['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Poques hores -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">⚠️ Han fitxat menys de 7 hores avui (<?= count($poques_hores) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Cognoms</th>
                    <th>Email</th>
                    <th>Hores registrades</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($poques_hores)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Tots compleixen les hores</td></tr>
                <?php else: ?>
                    <?php foreach ($poques_hores as $e): ?>
                        <tr class="table-warning">
                            <td><?= htmlspecialchars($e['nom']) ?></td>
                            <td><?= htmlspecialchars($e['cognoms']) ?></td>
                            <td><?= htmlspecialchars($e['email']) ?></td>
                            <td><strong><?= number_format($e['total_hores'], 2) ?>h</strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>