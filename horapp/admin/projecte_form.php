<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();
$error = '';

// Mode edició
$edicio = false;
$projecte = ['nom' => '', 'descripcio' => '', 'hores_estimades' => ''];

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT id, nom, descripcio, hores_estimades FROM projectes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $projecte = $stmt->fetch();
        if ($projecte) {
            $edicio = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom             = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $descripcio      = htmlspecialchars(trim($_POST['descripcio'] ?? ''));
    $hores_estimades = filter_var($_POST['hores_estimades'] ?? 0, FILTER_VALIDATE_FLOAT);
    $edit_id         = filter_var($_POST['edit_id'] ?? 0, FILTER_VALIDATE_INT);

    if (empty($nom)) {
        $error = 'El nom del projecte és obligatori.';
    } elseif ($hores_estimades === false || $hores_estimades < 0) {
        $error = 'Les hores estimades han de ser un número positiu.';
    } else {
        try {
            if ($edit_id) {
                $stmt = $pdo->prepare('UPDATE projectes SET nom = :nom, descripcio = :descripcio, hores_estimades = :hores WHERE id = :id');
                $stmt->execute([':nom' => $nom, ':descripcio' => $descripcio, ':hores' => $hores_estimades, ':id' => $edit_id]);
                header('Location: /admin/projectes.php?msg=Projecte actualitzat correctament.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO projectes (nom, descripcio, hores_estimades) VALUES (:nom, :descripcio, :hores)');
                $stmt->execute([':nom' => $nom, ':descripcio' => $descripcio, ':hores' => $hores_estimades]);
                header('Location: /admin/projectes.php?msg=Projecte creat correctament.');
            }
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Error en guardar el projecte.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $edicio ? '✏️ Editar Projecte' : '➕ Nou Projecte' ?></h2>
    <a href="projectes.php" class="btn btn-outline-secondary">← Tornar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="edit_id" value="<?= $edicio ? $projecte['id'] : '' ?>">
            <div class="mb-3">
                <label class="form-label">Nom del projecte *</label>
                <input type="text" name="nom" class="form-control"
                       value="<?= htmlspecialchars($projecte['nom']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripció</label>
                <textarea name="descripcio" class="form-control" rows="3"><?= htmlspecialchars($projecte['descripcio']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Hores estimades</label>
                <input type="number" name="hores_estimades" class="form-control"
                       value="<?= htmlspecialchars($projecte['hores_estimades']) ?>"
                       min="0" step="0.5">
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $edicio ? 'Guardar canvis' : 'Crear projecte' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>