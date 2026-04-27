<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();

$msg = htmlspecialchars($_GET['msg'] ?? '');
$error = htmlspecialchars($_GET['error'] ?? '');

// Obtenir tots els empleats
$stmt = $pdo->prepare('
    SELECT u.id, u.nom, u.cognoms, u.email, u.rol, u.actiu, u.created_at,
    (SELECT COUNT(r.id) FROM registres_hores r WHERE r.usuari_id = u.id AND r.data = CURDATE()) AS fitxat_avui
    FROM usuaris u
    ORDER BY u.cognoms, u.nom
');
$stmt->execute();
$empleats = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>👥 Gestió d'Empleats</h2>
    <a href="crear_usuari.php" class="btn btn-primary">+ Nou Empleat</a>
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
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estat</th>
                    <th>Fitxat avui</th>
                    <th>Accions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($empleats as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['nom'] . ' ' . $e['cognoms']) ?></td>
                        <td><?= htmlspecialchars($e['email']) ?></td>
                        <td>
                            <span class="badge <?= $e['rol'] === 'admin' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                <?= $e['rol'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $e['actiu'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $e['actiu'] ? 'Actiu' : 'Inactiu' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($e['rol'] === 'empleat'): ?>
                                <span class="badge <?= $e['fitxat_avui'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $e['fitxat_avui'] > 0 ? 'Sí' : 'No' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="crear_usuari.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <?php if ($e['id'] !== $_SESSION['usuari_id']): ?>
                                <a href="toggle_usuari.php?id=<?= $e['id'] ?>"
                                   class="btn btn-sm <?= $e['actiu'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                   onclick="return confirm('Estàs segur?')">
                                    <?= $e['actiu'] ? 'Desactivar' : 'Activar' ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>