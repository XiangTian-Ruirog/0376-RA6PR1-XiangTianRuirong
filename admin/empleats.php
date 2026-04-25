<?php
/**
 * Llistat d'empleats - TimeTracker
 * Mostra tots els empleats amb el seu estat del dia
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
    
    // Llistat de tots els empleats actius amb el seu estat d'avui
    $sqlEmpleats = "SELECT u.id, u.nom, u.cognoms, u.email, u.created_at,
                           rh.hora_entrada, rh.hora_sortida, rh.total_hores,
                           p.nom as projecte_nom,
                           CASE 
                               WHEN rh.id IS NOT NULL AND rh.hora_sortida IS NULL THEN 'working'
                               WHEN rh.id IS NOT NULL AND rh.hora_sortida IS NOT NULL THEN 'finished'
                               ELSE 'absent'
                           END as estat_dia
                    FROM usuaris u
                    LEFT JOIN registres_hores rh ON u.id = rh.usuari_id AND rh.data = CURDATE()
                    LEFT JOIN projectes p ON rh.projecte_id = p.id
                    WHERE u.rol = 'empleat' AND u.actiu = 1
                    ORDER BY estat_dia ASC, u.nom ASC";
    
    $stmt = $conexion->query($sqlEmpleats);
    $empleats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Comptadors per estat
    $comptador = ['working' => 0, 'finished' => 0, 'absent' => 0];
    foreach ($empleats as $empleat) {
        $comptador[$empleat['estat_dia']]++;
    }
    
} catch (PDOException $e) {
    error_log("Error al cargar empleados: " . $e->getMessage());
    $error = "Error al cargar los datos. Intente nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Lista de Empleados</title>
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
        
        .filters {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .filter-badge:hover {
            opacity: 0.8;
        }
        
        .filter-badge.all { background: #f0f0f0; color: #333; }
        .filter-badge.working { background: #d5f5e3; color: #27ae60; }
        .filter-badge.finished { background: #d6eaf8; color: #2980b9; }
        .filter-badge.absent { background: #fadbd8; color: #e74c3c; }
        
        .filter-badge .count {
            background: rgba(0,0,0,0.1);
            padding: 0.1rem 0.5rem;
            border-radius: 10px;
            font-weight: bold;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1.5fr 1fr;
            padding: 1rem 1.5rem;
            background: #667eea;
            color: white;
            font-weight: 500;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1.5fr 1fr;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .table-row:hover {
            background: #f8f9fa;
        }
        
        .table-row:last-child {
            border-bottom: none;
        }
        
        .empleat-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .empleat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .empleat-name {
            font-weight: 500;
            color: #333;
        }
        
        .empleat-email {
            color: #666;
            font-size: 0.85rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-badge.working {
            background: #d5f5e3;
            color: #27ae60;
        }
        
        .status-badge.finished {
            background: #d6eaf8;
            color: #2980b9;
        }
        
        .status-badge.absent {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .time-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .project-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .hours-info {
            font-weight: 500;
            color: #667eea;
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
            .table-header, .table-row {
                grid-template-columns: 1fr 1fr;
            }
            .nav-links {
                flex-wrap: wrap;
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
        <a href="empleats.php" class="active">👥 Empleados</a>
        <a href="alertes.php">🚨 Alertas</a>
        <a href="crear_usuari.php">➕ Nuevo Usuario</a>
    </div>
    
    <main>
        <h1 class="page-title">Lista de Empleados</h1>
        
        <div class="filters">
            <span class="filter-badge all">
                Todos <span class="count"><?php echo count($empleats); ?></span>
            </span>
            <span class="filter-badge working">
                🟢 Trabajando <span class="count"><?php echo $comptador['working']; ?></span>
            </span>
            <span class="filter-badge finished">
                🔵 Finalizado <span class="count"><?php echo $comptador['finished']; ?></span>
            </span>
            <span class="filter-badge absent">
                🔴 Ausente <span class="count"><?php echo $comptador['absent']; ?></span>
            </span>
        </div>
        
        <div class="card">
            <div class="table-header">
                <div>Empleado</div>
                <div>Estado</div>
                <div>Horario</div>
                <div>Proyecto</div>
                <div>Horas</div>
            </div>
            
            <?php if (empty($empleats)): ?>
                <div class="sin-datos">
                    <p>No hay empleados registrados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($empleats as $empleat): ?>
                    <div class="table-row" data-estado="<?php echo $empleat['estat_dia']; ?>">
                        <div>
                            <div class="empleat-info">
                                <div class="empleat-avatar">
                                    <?php echo strtoupper(substr($empleat['nom'], 0, 1) . substr($empleat['cognoms'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="empleat-name">
                                        <?php echo htmlspecialchars($empleat['nom'] . ' ' . $empleat['cognoms']); ?>
                                    </div>
                                    <div class="empleat-email">
                                        <?php echo htmlspecialchars($empleat['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <?php
                            $statusLabels = [
                                'working' => '🟢 Trabajando',
                                'finished' => '🔵 Finalizado',
                                'absent' => '🔴 Ausente'
                            ];
                            ?>
                            <span class="status-badge <?php echo $empleat['estat_dia']; ?>">
                                <?php echo $statusLabels[$empleat['estat_dia']]; ?>
                            </span>
                        </div>
                        
                        <div class="time-info">
                            <?php if ($empleat['hora_entrada']): ?>
                                Entrada: <?php echo date('H:i', strtotime($empleat['hora_entrada'])); ?>
                                <?php if ($empleat['hora_sortida']): ?>
                                    <br>Salida: <?php echo date('H:i', strtotime($empleat['hora_sortida'])); ?>
                                <?php else: ?>
                                    <br><em>En curso</em>
                                <?php endif; ?>
                            <?php else: ?>
                                <em>Sin registro</em>
                            <?php endif; ?>
                        </div>
                        
                        <div class="project-info">
                            <?php echo $empleat['projecte_nom'] ? htmlspecialchars($empleat['projecte_nom']) : '-'; ?>
                        </div>
                        
                        <div class="hours-info">
                            <?php echo $empleat['total_hores'] ? number_format($empleat['total_hores'], 2) . 'h' : '-'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
    
    <script>
        // Filtre simple per estat
        document.querySelectorAll('.filter-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                const filter = this.classList[1]; // all, working, finished, absent
                const rows = document.querySelectorAll('.table-row');
                
                rows.forEach(row => {
                    if (filter === 'all' || row.dataset.estado === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>