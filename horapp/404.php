<?php
session_start();
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HorApp - Pàgina no trobada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .error-box {
            max-width: 500px;
            margin: 100px auto;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #0d6efd;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code">404</div>
        <h2 class="mb-3">Pàgina no trobada</h2>
        <p class="text-muted mb-4">La pàgina que busques no existeix o ha estat moguda.</p>
        <?php if (isset($_SESSION['rol'])): ?>
            <a href="/<?= $_SESSION['rol'] === 'admin' ? 'admin' : 'empleat' ?>/dashboard.php" class="btn btn-primary">
                ← Tornar al Dashboard
            </a>
        <?php else: ?>
            <a href="/public/login.php" class="btn btn-primary">← Tornar al Login</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>