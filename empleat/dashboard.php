<?php
/**
 * Dashboard de l'empleat - TimeTracker
 * Mostra l'estat actual, botons de fitxar i historial
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticació i rol d'empleat
checkAuth();

// Si és admin, redirigir al dashboard d'admin
if (esAdmin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Obtener usuari actual
$usuari = obtenerUsuarioActual();
$usuariId = $usuari['id'];

try {
    $conexion = obtenerConexion();
    
    // Comprovar si té un registre obert (entrada sense sortida) avui
    $sqlRegistreObert = "SELECT rh.id, rh.hora_entrada, rh.notes, p.nom as projecte_nom, p.id as projecte_id
                         FROM registres_hores rh
                         INNER JOIN projectes p ON rh.projecte_id = p.id
                         WHERE rh.usuari_id = :usuari_id 
                           AND rh.data = CURDATE() 
                           AND rh.hora_sortida IS NULL
                         LIMIT 1";
    
    $stmt = $conexion->prepare($sqlRegistreObert);
    $stmt->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
    $stmt->execute();
    $registreObert = $stmt->fetch();
    
    $estaFitxat = !empty($registreObert);
    
    // Obtener registres dels últims 7 dies
    $sqlHistorial = "SELECT rh.id, rh.hora_entrada, rh.hora_sortida, rh.total_hores, rh.notes, rh.data,
                            p.nom as projecte_nom
                     FROM registres_hores rh
                     INNER JOIN projectes p ON rh.projecte_id = p.id
                     WHERE rh.usuari_id = :usuari_id 
                       AND rh.data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       AND rh.hora_sortida IS NOT NULL
                     ORDER BY rh.data DESC, rh.hora_entrada DESC
                     LIMIT 20";
    
    $stmtHistorial = $conexion->prepare($sqlHistorial);
    $stmtHistorial->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
    $stmtHistorial->execute();
    $historial = $stmtHistorial->fetchAll();
    
    // Calcular hores totals de la setmana
    $sqlTotalSetmana = "SELECT COALESCE(SUM(total_hores), 0) as total
                        FROM registres_hores
                        WHERE usuari_id = :usuari_id 
                          AND data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                          AND hora_sortida IS NOT NULL";
    
    $stmtTotal = $conexion->prepare($sqlTotalSetmana);
    $stmtTotal->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
    $stmtTotal->execute();
    $totalSetmana = $stmtTotal->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Error al cargar dashboard: " . $e->getMessage());
    $error = "Error al cargar los datos. Intente nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Dashboard Empleado</title>
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
            background: #27ae60;
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
        
        main {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .card h2 {
            color: #667eea;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .status-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-dot.active {
            background-color: #27ae60;
        }
        
        .status-dot.inactive {
            background-color: #95a5a6;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .status-text {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .status-text.active {
            color: #27ae60;
        }
        
        .status-text.inactive {
            color: #95a5a6;
        }
        
        .current-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .current-info p {
            margin-bottom: 0.5rem;
            color: #555;
        }
        
        .current-info strong {
            color: #333;
        }
        
        .btn-fichar {
            width: 100%;
            padding: 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-transform: uppercase;
        }
        
        .btn-fichar.entrada {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        
        .btn-fichar.salida {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .btn-fichar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-fichar:active {
            transform: translateY(0);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .historial-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .historial-table th {
            background: #667eea;
            color: white;
            padding: 0.75rem;
            text-align: left;
            font-weight: 500;
        }
        
        .historial-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .historial-table tr:hover {
            background: #f8f9fa;
        }
        
        .horas {
            font-weight: bold;
            color: #27ae60;
        }
        
        .sin-registros {
            text-align: center;
            padding: 2rem;
            color: #95a5a6;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
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
                <span class="role-badge">Empleado</span>
            </span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </header>
    
    <main>
        <div class="dashboard-grid">
            <!-- Tarjeta de estado y fichaje -->
            <div class="card">
                <h2>📍 Estado Actual</h2>
                
                <div class="status-indicator">
                    <div class="status-dot <?php echo $estaFitxat ? 'active' : 'inactive'; ?>"></div>
                    <span class="status-text <?php echo $estaFitxat ? 'active' : 'inactive'; ?>">
                        <?php echo $estaFitxat ? 'Fichado - En trabajo' : 'No fichado'; ?>
                    </span>
                </div>
                
                <?php if ($estaFitxat): ?>
                    <div class="current-info">
                        <p><strong>Proyecto:</strong> <?php echo htmlspecialchars($registreObert['projecte_nom']); ?></p>
                        <p><strong>Hora de entrada:</strong> <?php echo date('H:i:s', strtotime($registreObert['hora_entrada'])); ?></p>
                        <?php if (!empty($registreObert['notes'])): ?>
                            <p><strong>Notas:</strong> <?php echo htmlspecialchars($registreObert['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="current-info">
                        <p>No hay registro activo hoy.</p>
                    </div>
                <?php endif; ?>
                
                <a href="fitxar.php?accio=<?php echo $estaFitxat ? 'sortida' : 'entrada'; ?>" 
                   class="btn-fichar <?php echo $estaFitxat ? 'salida' : 'entrada'; ?>">
                    <?php echo $estaFitxat ? '⏱️ Registrar Salida' : '▶️ Registrar Entrada'; ?>
                </a>
            </div>
            
            <!-- Tarjeta de estadísticas -->
            <div class="card">
                <h2>📊 Estadísticas (7 días)</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($totalSetmana, 1); ?>h</div>
                        <div class="stat-label">Horas esta semana</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($historial); ?></div>
                        <div class="stat-label">Registros</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historial -->
        <div class="card">
            <h2>📋 Historial de Registros (Últimos 7 días)</h2>
            
            <?php if (empty($historial)): ?>
                <div class="sin-registros">
                    <p>No hay registros en los últimos 7 días.</p>
                </div>
            <?php else: ?>
                <table class="historial-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Proyecto</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Horas</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $registre): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($registre['data'])); ?></td>
                                <td><?php echo htmlspecialchars($registre['projecte_nom']); ?></td>
                                <td><?php echo date('H:i', strtotime($registre['hora_entrada'])); ?></td>
                                <td><?php echo date('H:i', strtotime($registre['hora_sortida'])); ?></td>
                                <td class="horas"><?php echo number_format($registre['total_hores'], 2); ?>h</td>
                                <td><?php echo !empty($registre['notes']) ? htmlspecialchars($registre['notes']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Empleado</p>
    </footer>
</body>
</html>