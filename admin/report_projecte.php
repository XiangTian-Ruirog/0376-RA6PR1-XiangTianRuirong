<?php
/**
 * Detall de Projecte - TimeTracker
 * Mostra informació detallada d'un projecte específic
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario es administrador
requireRole('admin');

// Obtener usuari actual
$usuari = obtenerUsuarioActual();

// Obtenir ID del projecte
$projecteId = $_GET['id'] ?? 0;

// Obtenir filtres de data (GET)
$dataInici = $_GET['data_inici'] ?? date('Y-m-01');
$dataFi = $_GET['data_fi'] ?? date('Y-m-d');

if (!empty($_GET['data_inici']) && !empty($_GET['data_fi'])) {
    $dataInici = $_GET['data_inici'];
    $dataFi = $_GET['data_fi'];
}

try {
    $conexion = obtenerConexion();
    
    // Obtenir dades del projecte
    // SELECT específic sense usar *
    $sqlProjecte = "SELECT id, nom, descripcio, hores_estimades, actiu, created_at
                    FROM projectes 
                    WHERE id = :id";
    
    $stmt = $conexion->prepare($sqlProjecte);
    $stmt->bindParam(':id', $projecteId, PDO::PARAM_INT);
    $stmt->execute();
    $projecte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projecte) {
        header('Location: reports.php');
        exit;
    }
    
    // Obtenir registres del projecte amb dades d'empleats
    // JOIN entre registres_hores, usuaris i projectes
    $sqlRegistres = "SELECT 
                        rh.id,
                        rh.hora_entrada,
                        rh.hora_sortida,
                        rh.total_hores,
                        rh.notes,
                        rh.data,
                        u.id as empleat_id,
                        u.nom as empleat_nom,
                        u.cognoms as empleat_cognoms,
                        u.email as empleat_email
                     FROM registres_hores rh
                     INNER JOIN usuaris u ON rh.usuari_id = u.id
                     WHERE rh.projecte_id = :projecte_id
                       AND rh.data >= :data_inici 
                       AND rh.data <= :data_fi
                     ORDER BY rh.data DESC, rh.hora_entrada DESC";
    
    $stmt = $conexion->prepare($sqlRegistres);
    $stmt->bindParam(':projecte_id', $projecteId, PDO::PARAM_INT);
    $stmt->bindParam(':data_inici', $dataInici, PDO::PARAM_STR);
    $stmt->bindParam(':data_fi', $dataFi, PDO::PARAM_STR);
    $stmt->execute();
    $registres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtenir hores per empleat en aquest projecte
    $sqlHoresEmpleats = "SELECT 
                            u.id,
                            u.nom,
                            u.cognoms,
                            u.email,
                            COALESCE(SUM(rh.total_hores), 0) as hores_totals,
                            COUNT(rh.id) as registres_count
                         FROM usuaris u
                         LEFT JOIN registres_hores rh ON u.id = rh.usuari_id
                             AND rh.projecte_id = :projecte_id
                             AND rh.data >= :data_inici 
                             AND rh.data <= :data_fi
                         WHERE u.rol = 'empleat' AND u.actiu = 1
                         GROUP BY u.id, u.nom, u.cognoms, u.email
                         HAVING hores_totals > 0
                         ORDER BY hores_totals DESC";
    
    $stmt = $conexion->prepare($sqlHoresEmpleats);
    $stmt->bindParam(':projecte_id', $projecteId, PDO::PARAM_INT);
    $stmt->bindParam(':data_inici', $dataInici, PDO::PARAM_STR);
    $stmt->bindParam(':data_fi', $dataFi, PDO::PARAM_STR);
    $stmt->execute();
    $horesEmpleats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totals
    $totalHores = array_sum(array_column($registres, 'total_hores'));
    $desviacio = $projecte['hores_estimades'] > 0 
        ? (($totalHores - $projecte['hores_estimades']) / $projecte['hores_estimades']) * 100 
        : 0;
    
    // Preparar dades per a Chart.js
    $horesEmpleatsJson = json_encode($horesEmpleats, JSON_NUMERIC_CHECK);
    $registresJson = json_encode($registres, JSON_NUMERIC_CHECK);
    
} catch (PDOException $e) {
    error_log("Error al cargar detalle proyecto: " . $e->getMessage());
    $error = "Error al cargar los datos. Intente nuevamente.";
    $horesEmpleatsJson = '[]';
    $registresJson = '[]';
    $projecte = null;
    $registres = [];
    $horesEmpleats = [];
    $totalHores = 0;
    $desviacio = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Detall del Projecte</title>
    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1400px;
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
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }
        
        .filter-group input[type="date"] {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .btn-filter {
            padding: 0.5rem 1.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-filter:hover {
            background: #5a6fd6;
        }
        
        .btn-print {
            padding: 0.5rem 1.5rem;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-print:hover {
            background: #229954;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .info-card h3 {
            color: #667eea;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .project-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item .label {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .info-item .value {
            font-weight: 500;
            color: #333;
        }
        
        .info-item .value.alert {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .chart-card h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        .table-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            padding: 1rem 1.5rem;
            background: #667eea;
            color: white;
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
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .empleat-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }
        
        .hours-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.25rem;
        }
        
        .hours-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        @media print {
            body {
                background: white;
            }
            header, .nav-links, .filters, .btn-print, .charts-grid, footer, .btn-back {
                display: none !important;
            }
            .page-header {
                margin-bottom: 1rem;
            }
            .page-title {
                color: #333;
            }
        }
        
        @media (max-width: 992px) {
            .project-info {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
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
        <a href="dashboard.php">📊 Resumen</a>
        <a href="empleats.php">👥 Empleados</a>
        <a href="alertes.php">🚨 Alertas</a>
        <a href="crear_usuari.php">➕ Nuevo Usuario</a>
        <a href="reports.php" class="active">📈 Reports</a>
    </div>
    
    <main>
        <?php if ($projecte): ?>
            <div class="page-header">
                <h1 class="page-title">📊 <?php echo htmlspecialchars($projecte['nom']); ?></h1>
                <a href="reports.php?data_inici=<?php echo $dataInici; ?>&data_fi=<?php echo $dataFi; ?>" class="btn-back">← Volver a Reports</a>
            </div>
            
            <!-- Filtres de data -->
            <div class="filters">
                <div class="filter-group">
                    <label for="data_inici">Data Inici</label>
                    <input type="date" id="data_inici" name="data_inici" value="<?php echo htmlspecialchars($dataInici); ?>">
                </div>
                <div class="filter-group">
                    <label for="data_fi">Data Fi</label>
                    <input type="date" id="data_fi" name="data_fi" value="<?php echo htmlspecialchars($dataFi); ?>">
                </div>
                <button type="button" class="btn-filter" onclick="aplicarFiltres()">Filtrar</button>
                <button type="button" class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
            </div>
            
            <!-- Informació del projecte -->
            <div class="info-card">
                <h3>Informació del Projecte</h3>
                <div class="project-info">
                    <div class="info-item">
                        <div class="label">Descripció</div>
                        <div class="value"><?php echo $projecte['descripcio'] ? htmlspecialchars($projecte['descripcio']) : '-'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Hores Estimades</div>
                        <div class="value"><?php echo number_format($projecte['hores_estimades'], 2); ?>h</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Hores Reals</div>
                        <div class="value <?php echo $totalHores > $projecte['hores_estimades'] ? 'alert' : ''; ?>">
                            <?php echo number_format($totalHores, 2); ?>h
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="label">Desviació</div>
                        <div class="value <?php echo $desviacio > 0 ? 'alert' : ''; ?>">
                            <?php echo ($desviacio >= 0 ? '+' : '') . number_format($desviacio, 1); ?>%
                            <?php if ($desviacio > 10): ?>⚠️<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gràfics -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>📊 Hores per Empleat</h3>
                    <div class="chart-container">
                        <canvas id="empleatsChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>📅 Registros por Día</h3>
                    <div class="chart-container">
                        <canvas id="diasChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Taula d'empleats -->
            <div class="table-card">
                <div class="table-header">
                    <h3>👥 Empleats Assignats</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Empleat</th>
                            <th>Email</th>
                            <th>Hores Totals</th>
                            <th>% del Total</th>
                            <th>Registres</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $maxHores = !empty($horesEmpleats) ? max(array_column($horesEmpleats, 'hores_totals')) : 1;
                        foreach ($horesEmpleats as $empleat): 
                            $percentatge = $totalHores > 0 ? ($empleat['hores_totals'] / $totalHores) * 100 : 0;
                        ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <span class="empleat-avatar">
                                            <?php echo strtoupper(substr($empleat['nom'], 0, 1) . substr($empleat['cognoms'], 0, 1)); ?>
                                        </span>
                                        <?php echo htmlspecialchars($empleat['nom'] . ' ' . $empleat['cognoms']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($empleat['email']); ?></td>
                                <td>
                                    <strong><?php echo number_format($empleat['hores_totals'], 2); ?>h</strong>
                                    <div class="hours-bar">
                                        <div class="hours-bar-fill" style="width: <?php echo ($empleat['hores_totals'] / $maxHores) * 100; ?>%"></div>
                                    </div>
                                </td>
                                <td><?php echo number_format($percentatge, 1); ?>%</td>
                                <td><?php echo $empleat['registres_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($horesEmpleats)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #95a5a6; padding: 2rem;">
                                    No hay registros en el período seleccionado
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Taula de registres -->
            <div class="table-card" style="margin-top: 1.5rem;">
                <div class="table-header">
                    <h3>📋 Registros Detallados</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Horas</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registres as $registre): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($registre['data'])); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <span class="empleat-avatar">
                                            <?php echo strtoupper(substr($registre['empleat_nom'], 0, 1) . substr($registre['empleat_cognoms'], 0, 1)); ?>
                                        </span>
                                        <?php echo htmlspecialchars($registre['empleat_nom'] . ' ' . $registre['empleat_cognoms']); ?>
                                    </div>
                                </td>
                                <td><?php echo date('H:i', strtotime($registre['hora_entrada'])); ?></td>
                                <td><?php echo $registre['hora_sortida'] ? date('H:i', strtotime($registre['hora_sortida'])) : '-'; ?></td>
                                <td><strong><?php echo number_format($registre['total_hores'], 2); ?>h</strong></td>
                                <td><?php echo $registre['notes'] ? htmlspecialchars(substr($registre['notes'], 0, 50)) . (strlen($registre['notes']) > 50 ? '...' : '') : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($registres)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #95a5a6; padding: 2rem;">
                                    No hay registros en el período seleccionado
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        <?php else: ?>
            <div class="page-header">
                <h1 class="page-title">⚠️ Projecte no trobat</h1>
            </div>
            <div class="info-card">
                <p style="text-align: center; padding: 2rem; color: #666;">
                    El projecte sol·licitat no existeix. <a href="reports.php" style="color: #667eea;">Tornar a Reports</a>
                </p>
            </div>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
    
    <script>
        // Dades passades de PHP a JavaScript
        const horesEmpleats = <?php echo $horesEmpleatsJson; ?>;
        const registres = <?php echo $registresJson; ?>;
        
        // Colors
        const colors = [
            '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe',
            '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'
        ];
        
        // Gràfic de barres - Hores per empleat
        function crearEmpleatsChart() {
            const ctx = document.getElementById('empleatsChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: horesEmpleats.map(e => e.nom + ' ' + e.cognoms),
                    datasets: [{
                        label: 'Hores',
                        data: horesEmpleats.map(e => e.hores_totals),
                        backgroundColor: colors.slice(0, horesEmpleats.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Hores'
                            }
                        }
                    }
                }
            });
        }
        
        // Gràfic de línia - Registres per dia
        function crearDiasChart() {
            const ctx = document.getElementById('diasChart').getContext('2d');
            
            // Agrupar registres per dia
            const diesData = {};
            registres.forEach(r => {
                const data = r.data.substring(0, 10);
                if (!diesData[data]) {
                    diesData[data] = 0;
                }
                diesData[data] += parseFloat(r.total_hores);
            });
            
            // Ordenar dates
            const sortedDates = Object.keys(diesData).sort();
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: sortedDates.map(d => {
                        const parts = d.split('-');
                        return parts[2] + '/' + parts[1];
                    }),
                    datasets: [{
                        label: 'Hores per dia',
                        data: sortedDates.map(d => diesData[d]),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Hores'
                            }
                        }
                    }
                }
            });
        }
        
        // Aplicar filtres
        function aplicarFiltres() {
            const urlParams = new URLSearchParams(window.location.search);
            const projecteId = urlParams.get('id');
            const dataInici = document.getElementById('data_inici').value;
            const dataFi = document.getElementById('data_fi').value;
            window.location.href = `report_projecte.php?id=${projecteId}&data_inici=${dataInici}&data_fi=${dataFi}`;
        }
        
        // Inicialitzar gràfics
        document.addEventListener('DOMContentLoaded', function() {
            crearEmpleatsChart();
            crearDiasChart();
        });
    </script>
</body>
</html>