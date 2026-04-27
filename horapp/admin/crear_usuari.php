<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();
$error = '';
$msg = '';

// Mode edició
$edicio = false;
$usuari = ['nom' => '', 'cognoms' => '', 'email' => '', 'rol' => 'empleat'];

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT id, nom, cognoms, email, rol FROM usuaris WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $usuari = $stmt->fetch();
        if ($usuari) {
            $edicio = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $cognoms  = htmlspecialchars(trim($_POST['cognoms'] ?? ''));
    $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $rol      = $_POST['rol'] === 'admin' ? 'admin' : 'empleat';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $edit_id  = filter_var($_POST['edit_id'] ?? 0, FILTER_VALIDATE_INT);

    if (empty($nom) || empty($cognoms) || empty($email)) {
        $error = 'Nom, cognoms i email són obligatoris.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no vàlid.';
    } elseif (!$edit_id && empty($password)) {
        $error = 'La contrasenya és obligatòria.';
    } elseif (!empty($password) && strlen($password) < 8) {
        $error = 'La contrasenya ha de tenir mínim 8 caràcters.';
    } elseif (!empty($password) && $password !== $password2) {
        $error = 'Les contrasenyes no coincideixen.';
    } else {
        try {
            if ($edit_id) {
                // Editar usuari existent
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE usuaris SET nom = :nom, cognoms = :cognoms, email = :email, rol = :rol, password_hash = :hash WHERE id = :id');
                    $stmt->execute([':nom' => $nom, ':cognoms' => $cognoms, ':email' => $email, ':rol' => $rol, ':hash' => $hash, ':id' => $edit_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE usuaris SET nom = :nom, cognoms = :cognoms, email = :email, rol = :rol WHERE id = :id');
                    $stmt->execute([':nom' => $nom, ':cognoms' => $cognoms, ':email' => $email, ':rol' => $rol, ':id' => $edit_id]);
                }
                header('Location: /admin/empleats.php?msg=Usuari actualitzat correctament.');
            } else {
                // Crear nou usuari
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO usuaris (nom, cognoms, email, password_hash, rol) VALUES (:nom, :cognoms, :email, :hash, :rol)');
                $stmt->execute([':nom' => $nom, ':cognoms' => $cognoms, ':email' => $email, ':hash' => $hash, ':rol' => $rol]);
                header('Location: /admin/empleats.php?msg=Usuari creat correctament.');
            }
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Aquest email ja existeix o hi ha hagut un error.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $edicio ? '✏️ Editar Empleat' : '➕ Nou Empleat' ?></h2>
    <a href="empleats.php" class="btn btn-outline-secondary">← Tornar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="edit_id" value="<?= $edicio ? $usuari['id'] : '' ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control"
                           value="<?= htmlspecialchars($usuari['nom']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Cognoms *</label>
                    <input type="text" name="cognoms" class="form-control"
                           value="<?= htmlspecialchars($usuari['cognoms']) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($usuari['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Rol</label>
                <select name="rol" class="form-select">
                    <option value="empleat" <?= $usuari['rol'] === 'empleat' ? 'selected' : '' ?>>Empleat</option>
                    <option value="admin" <?= $usuari['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contrasenya <?= $edicio ? '(deixa buit per no canviar)' : '*' ?></label>
                    <input type="password" name="password" class="form-control" minlength="8">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirma contrasenya</label>
                    <input type="password" name="password2" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $edicio ? 'Guardar canvis' : 'Crear empleat' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>