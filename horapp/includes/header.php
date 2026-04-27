<?php
$nom_usuari = htmlspecialchars($_SESSION['nom'] ?? '');
$rol = $_SESSION['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HorApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .navbar-brand { font-weight: bold; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">⏱ HorApp</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($rol === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/empleats.php">Empleats</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/projectes.php">Projectes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/alertes.php">Alertes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/calendari.php">Calendari</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/empleat/dashboard.php">Inici</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/empleat/calendari.php">Calendari</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link text-white">👤 <?= $nom_usuari ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/public/logout.php">Sortir</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">