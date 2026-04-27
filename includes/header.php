<?php
/**
 * Capçalera comuna per a totes les pàgines
 * Inclou Bootstrap 5 via CDN i estils personalitzats
 */

// Prevenir accés directe
if (!defined('APP_NAME') && !isset($_SESSION)) {
    die('Accés no permès');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TimeTracker - Sistema de gestió i seguiment d'hores de treball">
    <title><?php echo APP_NAME; ?> - <?php echo $pageTitle ?? 'Inici'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
        }
        
        .nav-link {
            color: var(--primary-color) !important;
            transition: background-color 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: var(--primary-color);
            color: white !important;
            border-radius: 5px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .btn-success {
            background: var(--success-color);
            border: none;
        }
        
        .btn-danger {
            background: var(--danger-color);
            border: none;
        }
        
        .badge-admin {
            background-color: #8e44ad;
        }
        
        .badge-empleat {
            background-color: var(--success-color);
        }
        
        .status-active {
            background-color: #d5f5e3;
            color: var(--success-color);
        }
        
        .status-inactive {
            background-color: #f0f0f0;
            color: #666;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .page-title {
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .footer {
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        @media (max-width: 768px) {
            .navbar-nav {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .nav-link {
                padding: 0.5rem !important;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                <i class="bi bi-clock-history"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($_SESSION['usuario_autenticado']) && $_SESSION['usuario_autenticado']): ?>
                        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>admin/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Resumen
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>admin/empleats.php">
                                    <i class="bi bi-people"></i> Empleados
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>admin/alertes.php">
                                    <i class="bi bi-bell"></i> Alertas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>admin/projectes.php">
                                    <i class="bi bi-folder"></i> Proyectos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>admin/reports.php">
                                    <i class="bi bi-graph-up"></i> Reports
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>empleat/dashboard.php">
                                    <i class="bi bi-house"></i> Inicio
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>empleat/fitxar.php?accio=entrada">
                                    <i class="bi bi-play-circle"></i> Fichar Entrada
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['usuario_autenticado']) && $_SESSION['usuario_autenticado']): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="avatar me-1">
                                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 2)); ?>
                                </span>
                                <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                                <span class="badge <?php echo $_SESSION['usuario_rol'] === 'admin' ? 'badge-admin' : 'badge-empleat'; ?>">
                                    <?php echo htmlspecialchars($_SESSION['usuario_rol']); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>public/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">