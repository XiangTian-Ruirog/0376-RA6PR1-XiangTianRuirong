<?php
/**
 * Dashboard d'Administrador - TimeTracker
 * Mostra resum general de l'activitat
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario es administrador
requireRole('admin');

// Obtener usuari actual
$usuari = obtenerUsuarioActual();

try {
    $conexion = obtenerConexion();
    
    // Total d'empleats actius
    $sqlTotalEmpleats = "SELECT COUNT(*) as total FROM usuaris WHERE rol = 'empleat' AND actiu = 1";
    $stmt = $conexion->query($sqlTotalEmpleats);
    $totalEmpleats = $stmt->fetchColumn();
    
    // Quants han fitxat avui (tenen almenys un registre avui)
    $sqlFitxatAvui = "SELECT COUNT(DISTINCT usuari_id) as total 
                      FROM registres_hores 
                      WHERE data = CURDATE()";
    $stmt = $conexion->query($sqlFitxatAvui);
    $fitxatAvui = $stmt->fetchColumn();
    
    // Quants NO han fitxat avui (alerta)
    $noFitxatAvui = $totalEmpleats - $fitxatAvui;
    
    // Hores totals registrades avui
    $sqlHoresAvui = "SELECT COALESCE(SUM(total_hores), 0) as total
                     FROM registres_hores 
                     WHERE data = CURDATE() AND hora_sortida IS NOT NULL";
    $stmt = $conexion->query($sqlHoresAvui);
    $horesAvui = $stmt->fetchColumn();
    
    // Últims 5 registres d'empleats
    $sqlUltimsRegistres = "SELECT rh.*, u.nom, u.cognoms, u.email, p.nom as projecte_nom
                           FROM registres_hores rh
                           INNER JOIN usuaris u ON rh.usuari_id = u.id
                           INNER JOIN projectes p ON rh.projecte_id = p.id
                           WHERE rh.data = CURDATE()
                           ORDER BY rh.hora_entrada DESC
                           LIMIT 5";
    $stmt = $conexion->query($sqlUltimsRegistres);
    $ultimsRegistres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Empleats amb registre obert ara mateix
    $sqlObertsAra = "SELECT u.id, u.nom, u.cognoms, u.email, rh.hora_entrada, p.nom as projecte_nom
                     FROM registres_hores rh
                     INNER JOIN usuaris u ON rh.usuari_id = u.id
                     INNER JOIN projectes p ON rh.projecte_id = p.id
                     WHERE rh.data = CURDATE() AND rh.hora_sortida IS NULL";
    $stmt = $conexion->query($sqlObertsAra);
    $obertsAra = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al cargar dashboard admin: " . $e->getMessage());
    $error = "Error al cargar los datos. Intente nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Panel de Administración</title>
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
        
        .page-title {
            color: white;
            margin-bottom: 2rem;
            font-size: 1.75rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .stat-card.blue .stat-value { color: #667eea; }
        .stat-card.green .stat-value { color: #27ae60; }
        .stat-card.red .stat-value { color: #e74c3c; }
        .stat-card.orange .stat-value { color: #f39c12; }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .empleat-item {
            padding: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .empleat-item:last-child {
            border-bottom: none;
        }
        
        .empleat-name {
            font-weight: 500;
            color: #333;
        }
        
        .empleat-email {
            color: #666;
            font-size: 0.85rem;
        }
        
        .empleat-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .project-badge {
            background: #f0f0f0;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
            color: #666;
        }
        
        .time-badge {
            color: #667eea;
            font-weight: 500;
        }
        
        .sin-datos {
            text-align: center;
            padding: 2rem;
            color: #95a5a6;
        }
        
        .alert-banner {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .alert-banner .alert-icon {
            font-size: 2rem;
        }
        
        .alert-banner .alert-text {
            flex: 1;
        }
        
        .alert-banner .alert-number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .btn-action {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .btn-action:hover {
            background: #5a6fd6;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .content-grid {
                grid-template-columns: 1fr;
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
        <a href="dashboard.php" class="active">📊 Resumen</a>
        <a href="empleats.php">👥 Empleados</a>
        <a href="alertes.php">🚨 Alertas</a>
        <a href="crear_usuari.php">➕ Nuevo Usuario</a>
    </div>
    
    <main>
        <h1 class="page-title">Panel de Administración</h1>
        
        <?php if ($noFitxatAvui > 0): ?>
            <div class="alert-banner">
                <div class="alert-icon">⚠️</div>
                <div class="alert-text">
                    <strong>Atención:</strong> Hay empleados que no han fichado hoy
                </div>
                <div class="alert-number"><?php echo $noFitxatAvui; ?></div>
            </div>
        <?php endif; ?>
        
        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-value"><?php echo $totalEmpleats; ?></div>
                <div class="stat-label">Total Empleados</div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?php echo $fitxatAvui; ?></div>
                <div class="stat-label">Ficharon Hoy</div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?php echo $noFitxatAvui; ?></div>
                <div class="stat-label">No Ficharon Hoy</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?php echo number_format($horesAvui, 1); ?>h</div>
                <div class="stat-label">Horas Hoy</div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="content-grid">
            <!-- Empleats amb registre obert -->
            <div class="card">
                <h3>🟢 Trabajando Ahora</h3>
                <?php if (empty($obertsAra)): ?>
                    <div class="sin-datos">
                        <p>No hay empleados trabajando en este momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($obertsAra as $empleat): ?>
                        <div class="empleat-item">
                            <div class="empleat-info">
                                <div>
                                    <div class="empleat-name">
                                        <?php echo htmlspecialchars($empleat['nom'] . ' ' . $empleat['cognoms']); ?>
                                    </div>
                                    <div class="empleat-email"><?php echo htmlspecialchars($empleat['email']); ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="time-badge">
                                        Desde: <?php echo date('H:i', strtotime($empleat['hora_entrada'])); ?>
                                    </div>
                                    <div class="project-badge">
                                        <?php echo htmlspecialchars($empleat['projecte_nom']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Últims registres -->
            <div class="card">
                <h3>📝 Últimos Registros (Hoy)</h3>
                <?php if (empty($ultimsRegistres)): ?>
                    <div class="sin-datos">
                        <p>No hay registros hoy.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimsRegistres as $registre): ?>
                        <div class="empleat-item">
                            <div class="empleat-info">
                                <div>
                                    <div class="empleat-name">
                                        <?php echo htmlspecialchars($registre['nom'] . ' ' . $registre['cognoms']); ?>
                                    </div>
                                    <div class="empleat-email">
                                        <?php echo date('H:i', strtotime($registre['hora_entrada'])); ?> - 
                                        <?php echo $registre['hora_sortida'] ? date('H:i', strtotime($registre['hora_sortida'])) : 'En curso'; ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="project-badge">
                                        <?php echo htmlspecialchars($registre['projecte_nom']); ?>
                                    </span>
                                    <?php if ($registre['total_hores']): ?>
                                        <span class="time-badge">
                                            <?php echo number_format($registre['total_hores'], 2); ?>h
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Accions ràpides -->
        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="alertes.php" class="btn-action">Ver Lista de Alertas</a>
            <a href="empleats.php" class="btn-action">Ver Todos los Empleados</a>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
</body>
</html>