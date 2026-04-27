<?php
/**
 * Gestió de Projectes - TimeTracker
 * CRUD complet per a administradors
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario es administrador
requireRole('admin');

// Obtener usuari actual
$usuari = obtenerUsuarioActual();

// Missatge de feedback
$missatge = $_GET['msg'] ?? '';

try {
    $conexion = obtenerConexion();
    
    // Llistat de projectes (actius i inactius)
    // SELECT específic sense usar *
    $sqlProjectes = "SELECT id, nom, descripcio, hores_estimades, actiu, created_at
                     FROM projectes 
                     ORDER BY actiu DESC, nom ASC";
    
    $stmt = $conexion->query($sqlProjectes);
    $projectes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al cargar proyectos: " . $e->getMessage());
    $error = "Error al cargar los datos. Intente nuevamente.";
    $projectes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Gestió de Projectes</title>
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
        
        .role-badge {
            display: inline-block;
            background: #8e44ad;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            text-transform: uppercase;
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
        
        .nav-links {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 0.75rem 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            color: #667eea;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #667eea;
            color: white;
        }
        
        .nav-links a.active {
            background-color: #667eea;
            color: white;
        }
        
        main {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            color: white;
            font-size: 1.75rem;
        }
        
        .btn-new {
            background: #27ae60;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-new:hover {
            background: #229954;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background-color: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .alert-info {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            padding: 1rem 1.5rem;
            background: #667eea;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: #f8f9fa;
        }
        
        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 500;
            color: #333;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tr.inactive {
            opacity: 0.6;
            background: #fafafa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-badge.active {
            background: #d5f5e3;
            color: #27ae60;
        }
        
        .status-badge.inactive {
            background: #f0f0f0;
            color: #666;
        }
        
        .project-name {
            font-weight: 500;
            color: #333;
        }
        
        .project-desc {
            color: #666;
            font-size: 0.85rem;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .hours-value {
            font-weight: 500;
            color: #667eea;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.75rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: background-color 0.2s;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .sin-datos {
            text-align: center;
            padding: 3rem;
            color: #95a5a6;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            .data-table {
                font-size: 0.85rem;
            }
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">⏱️ TimeTracker</div>
        <div class="user-info">
            <span class="user-name">
                <?php echo htmlspecialchars($usuari['nombre']); ?>
                <span class="role-badge">Admin</span>
            </span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </header>
    
    <div class="nav-links">
        <a href="dashboard.php">📊 Resumen</a>
        <a href="empleats.php">👥 Empleados</a>
        <a href="alertes.php">🚨 Alertas</a>
        <a href="crear_usuari.php">➕ Nuevo Usuario</a>
        <a href="reports.php">📈 Reports</a>
        <a href="projectes.php" class="active">📁 Projectos</a>
    </div>
    
    <main>
        <div class="page-header">
            <h1 class="page-title">📁 Gestió de Projectes</h1>
            <a href="projecte_form.php" class="btn-new">➕ Nou Projecte</a>
        </div>
        
        <?php 
        session_start();
        if (isset($_SESSION['success'])): 
        ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['info']); ?></div>
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>
        
        <?php if ($missatge === 'creat'): ?>
            <div class="alert alert-success">Projecte creat correctament.</div>
        <?php elseif ($missatge === 'actualitzat'): ?>
            <div class="alert alert-success">Projecte actualitzat correctament.</div>
        <?php elseif ($missatge === 'desactivat'): ?>
            <div class="alert alert-success">Projecte desactivat correctament.</div>
        <?php endif; ?>
        
        <div class="card">
            <div class="table-header">
                <h3>Llistat de Projectes</h3>
                <span style="font-size: 0.9rem;">
                    <?php 
                    $actius = count(array_filter($projectes, fn($p) => $p['actiu'] == 1));
                    $inactius = count($projectes) - $actius;
                    echo "$actius actius, $inactius inactius";
                    ?>
                </span>
            </div>
            
            <?php if (empty($projectes)): ?>
                <div class="sin-datos">
                    <p>No hi ha projectes registrats.</p>
                    <a href="projecte_form.php" class="btn-new" style="margin-top: 1rem; display: inline-block;">Crear primer projecte</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estat</th>
                            <th>Nom</th>
                            <th>Descripció</th>
                            <th>Hores Estimades</th>
                            <th>Accions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projectes as $projecte): ?>
                            <tr class="<?php echo $projecte['actiu'] ? '' : 'inactive'; ?>">
                                <td>
                                    <span class="status-badge <?php echo $projecte['actiu'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $projecte['actiu'] ? '✅ Actiu' : '❌ Inactiu'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="project-name"><?php echo htmlspecialchars($projecte['nom']); ?></div>
                                </td>
                                <td>
                                    <div class="project-desc" title="<?php echo htmlspecialchars($projecte['descripcio']); ?>">
                                        <?php echo htmlspecialchars($projecte['descripcio'] ?: 'Sense descripció'); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="hours-value"><?php echo number_format($projecte['hores_estimades'], 2); ?>h</span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="projecte_form.php?id=<?php echo $projecte['id']; ?>" class="btn-action btn-edit">
                                            ✏️ Editar
                                        </a>
                                        <?php if ($projecte['actiu']): ?>
                                            <a href="projecte_delete.php?id=<?php echo $projecte['id']; ?>" 
                                               class="btn-action btn-delete"
                                               onclick="return confirm('Estàs segur que vols desactivar aquest projecte?\n\nAquesta acció no eliminarà els registres d\'hores associats, però el projecte deixarà d\'estar disponible per a nous fitxatges.');">
                                                🗑️ Desactivar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
</body>
</html>