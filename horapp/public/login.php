<?php
session_start();

require_once '../config/database.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['usuari_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/admin/dashboard.php');
    } else {
        header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/empleat/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Omple tots els camps.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT id, nom, cognoms, email, password_hash, rol, actiu FROM usuaris WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $usuari = $stmt->fetch();

            if ($usuari && $usuari['actiu'] == 1 && password_verify($password, $usuari['password_hash'])) {
                $_SESSION['usuari_id'] = $usuari['id'];
                $_SESSION['nom']       = $usuari['nom'];
                $_SESSION['cognoms']   = $usuari['cognoms'];
                $_SESSION['email']     = $usuari['email'];
                $_SESSION['rol']       = $usuari['rol'];

                // Cookie per recordar l'email (mai la contrasenya)
                setcookie('remember_email', $email, time() + (86400 * 30), '/');

                if ($usuari['rol'] === 'admin') {
                    header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/admin/dashboard.php');
                } else {
                    header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/empleat/dashboard.php');
                }
                exit;
            } else {
                $error = 'Email o contrasenya incorrectes.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Error intern. Torna-ho a intentar.';
        }
    }
}

// Recuperar email de la cookie si existeix
$remembered_email = htmlspecialchars($_COOKIE['remember_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HorApp - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .login-card {
            max-width: 420px;
            margin: 100px auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .login-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <h2>⏱ HorApp</h2>
            <p class="mb-0">Control d'hores de feina</p>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= $remembered_email ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contrasenya</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>