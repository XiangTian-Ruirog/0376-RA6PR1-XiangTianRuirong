<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('empleat');

$pdo = getDB();
$usuari_id = $_SESSION['usuari_id'];

// Comprovar si té una entrada oberta avui
$stmt = $pdo->prepare('SELECT id, hora_entrada, projecte_id FROM registres_hores WHERE usuari_id = :uid AND hora_sortida IS NULL AND data = CURDATE()');
$stmt->execute([':uid' => $usuari_id]);
$entrada_oberta = $stmt->fetch();

// Obtenir projectes actius
$stmt = $pdo->prepare('SELECT id, nom FROM projectes WHERE actiu = 1 ORDER BY nom');
$stmt->execute();
$projectes = $stmt->fetchAll();

// Historial dels últims 7 dies
$stmt = $pdo->prepare('
    SELECT r.data, r.hora_entrada, r.hora_sortida, r.total_hores, r.notes, p.nom AS projecte
    FROM registres_hores r
    JOIN projectes p ON r.projecte_id = p.id
    WHERE r.usuari_id = :uid AND r.data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY r.hora_entrada DESC
');
$stmt->execute([':uid' => $usuari_id]);
$historial = $stmt->fetchAll();

// Missatge de feedback
$msg = htmlspecialchars($_GET['msg'] ?? '');
$error = htmlspecialchars($_GET['error'] ?? '');
?>

<h2 class="mb-4">Benvingut/da, <?= htmlspecialchars($_SESSION['nom']) ?>! 👋</h2>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<!-- Botó fitxar -->
<div class="card mb-4 text-center shadow-sm">
    <div class="card-body py-5">
        <?php if ($entrada_oberta): ?>
            <h4 class="text-success mb-3">✅ Estàs treballant</h4>
            <p class="text-muted">Entrada: <?= htmlspecialchars($entrada_oberta['hora_entrada']) ?></p>
            <form method="POST" action="fitxar.php">
                <input type="hidden" name="accio" value="sortida">
                <input type="hidden" name="registre_id" value="<?= $entrada_oberta['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Notes (opcional)</label>
                    <input type="text" name="notes" class="form-control w-50 mx-auto" placeholder="Afegeix una nota...">
                </div>
                <button type="submit" class="btn btn-danger btn-lg px-5">🔴 Fitxar Sortida</button>
            </form>
        <?php else: ?>
            <h4 class="text-secondary mb-3">⏸ No estàs treballant</h4>
            <form method="POST" action="fitxar.php">
                <input type="hidden" name="accio" value="entrada">
                <div class="mb-3">
                    <label class="form-label">Projecte</label>
                    <select name="projecte_id" class="form-select w-50 mx-auto" required>
                        <option value="">-- Selecciona un projecte --</option>
                        <?php foreach ($projectes as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success btn-lg px-5">🟢 Fitxar Entrada</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Historial -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">📋 Historial dels últims 7 dies</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Projecte</th>
                    <th>Entrada</th>
                    <th>Sortida</th>
                    <th>Total hores</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historial)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Sense registres</td></tr>
                <?php else: ?>
                    <?php foreach ($historial as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['data']) ?></td>
                            <td><?= htmlspecialchars($r['projecte']) ?></td>
                            <td><?= htmlspecialchars($r['hora_entrada']) ?></td>
                            <td><?= $r['hora_sortida'] ? htmlspecialchars($r['hora_sortida']) : '<span class="badge bg-success">Actiu</span>' ?></td>
                            <td><?= $r['total_hores'] ? $r['total_hores'] . 'h' : '-' ?></td>
                            <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>