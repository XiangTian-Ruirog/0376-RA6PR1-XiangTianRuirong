<?php
/**
 * Página de inicio de TimeTracker
 * Redirige a login si no hay sesión activa
 */

// Iniciar sesión
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Redirigir a login si no está autenticado
redirigirSiNoAutenticado();

// Obtener datos del usuario actual
$usuario = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Inicio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        header {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-name {
            color: #333;
        }
        
        .btn-logout {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-logout:hover {
            background-color: #c0392b;
        }
        
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .welcome-card {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px;
        }
        
        .welcome-card h1 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .welcome-card p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .welcome-card .role-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .navigation-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn-nav {
            background-color: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
        }
        
        .btn-nav:hover {
            background-color: #5a6fd6;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">⏱️ TimeTracker</div>
        <div class="user-info">
            <span class="user-name">
                <?php echo htmlspecialchars($usuario['nombre']); ?>
                <span class="role-badge"><?php echo htmlspecialchars($usuario['rol']); ?></span>
            </span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </header>
    
    <main>
        <div class="welcome-card">
            <h1>¡Bienvenido a TimeTracker!</h1>
            <p>
                Sistema de gestión y seguimiento de horas de trabajo.
                Registra tu tiempo, gestiona proyectos y mejora tu productividad.
            </p>
            <div class="navigation-links">
                <a href="registros.php" class="btn-nav">Mis Registros</a>
                <?php if (esAdmin()): ?>
                    <a href="admin.php" class="btn-nav">Administración</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Sistema de Gestión de Horas</p>
    </footer>
</body>
</html>