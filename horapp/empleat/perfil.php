<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('empleat');

$pdo = getDB();
$usuari_id = $_SESSION['usuari_id'];
$error = '';
$msg = '';

// Obtenir dades de l'usuari
$stmt = $pdo->prepare('SELECT id, nom, cognoms, email FROM usuaris WHERE id = :id');
$stmt->execute([':id' => $usuari_id]);
$usuari = $stmt->fetch();

// Estadístiques personals
$stmt = $pdo->prepare('
    SELECT 
        COUNT(DISTINCT data) AS dies_treballats,
        COALESCE(SUM(total_hores), 0) AS total_hores,
        COALESCE(AVG(total_hores), 0) AS mitja_hores,
        COUNT(DISTINCT projecte_id) AS projectes_diferents
    FROM registres_hores
    WHERE usuari_id = :uid AND total_hores IS NOT NULL
');
$stmt->execute([':uid' => $usuari_id]);
$stats = $stmt->fetch();

// Estadístiques del mes actual
$stmt = $pdo->prepare('
    SELECT 
        COUNT(DISTINCT data) AS dies_mes,
        COALESCE(SUM(total_hores), 0) AS hores_mes
    FROM registres_hores
    WHERE usuari_id = :uid 
    AND total_hores IS NOT NULL
    AND MONTH(data) = MONTH(CURDATE())
    AND YEAR(data) = YEAR(CURDATE())
');
$stmt->execute([':uid' => $usuari_id]);
$stats_mes = $stmt->fetch();

// Projectes treballats
$stmt = $pdo->prepare('
    SELECT p.nom, COUNT(DISTINCT r.data) AS dies, COALESCE(SUM(r.total_hores), 0) AS hores
    FROM registres_hores r
    JOIN projectes p ON r.projecte_id = p.id
    WHERE r.usuari_id = :uid AND r.total_hores IS NOT NULL
    GROUP BY p.id, p.nom
    ORDER BY hores DESC
');
$stmt->execute([':uid' => $usuari_id]);
$projectes = $stmt->fetchAll();

// Processar canvi de contrasenya
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual  = $_POST['password_actual'] ?? '';
    $password_nou     = $_POST['password_nou'] ?? '';
    $password_nou2    = $_POST['password_nou2'] ?? '';

    if (empty($password_actual) || empty($password_nou) || empty($password_nou2)) {
        $error = 'Omple tots els camps.';
    } elseif (strlen($password_nou) < 8) {
        $error = 'La nova contrasenya ha de tenir mínim 8 caràcters.';
    } elseif ($password_nou !== $password_nou2) {
        $error = 'Les noves contrasenyes no coincideixen.';
    } else {
        $stmt = $pdo->prepare('SELECT password_hash FROM usuaris WHERE id = :id');
        $stmt->execute([':id' => $usuari_id]);
        $hash_actual = $stmt->fetch()['password_hash'];

        if (!password_verify($password_actual, $hash_actual)) {
            $error = 'La contrasenya actual no és correcta.';
        } else {
            $nou_hash = password_hash($password_nou, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE usuaris SET password_hash = :hash WHERE id = :id');
            $stmt->execute([':hash' => $nou_hash, ':id' => $usuari_id]);
            $msg = 'Contrasenya canviada correctament.';
        }
    }
}
?>

<h2 class="mb-4">👤 El meu Perfil</h2>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<!-- Estadístiques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body text-center">
                <h3><?= number_format($stats['total_hores'], 1) ?>h</h3>
                <p class="mb-0">Total hores</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body text-center">
                <h3><?= $stats['dies_treballats'] ?></h3>
                <p class="mb-0">Dies treballats</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info shadow-sm">
            <div class="card-body text-center">
                <h3><?= number_format($stats['mitja_hores'], 1) ?>h</h3>
                <p class="mb-0">Mitjana diària</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning shadow-sm">
            <div class="card-body text-center">
                <h3><?= number_format($stats_mes['hores_mes'], 1) ?>h</h3>
                <p class="mb-0">Hores aquest mes</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Canvi contrasenya -->
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">🔒 Canviar Contrasenya</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Contrasenya actual</label>
                        <input type="password" name="password_actual" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nova contrasenya</label>
                        <input type="password" name="password_nou" class="form-control" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirma nova contrasenya</label>
                        <input type="password" name="password_nou2" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Canviar contrasenya</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Projectes treballats -->
    <div class="col-md-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">📁 Els meus Projectes</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Projecte</th>
                            <th>Dies</th>
                            <th>Total hores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projectes)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Sense registres</td></tr>
                        <?php else: ?>
                            <?php foreach ($projectes as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['nom']) ?></td>
                                    <td><?= $p['dies'] ?></td>
                                    <td><?= number_format($p['hores'], 1) ?>h</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>