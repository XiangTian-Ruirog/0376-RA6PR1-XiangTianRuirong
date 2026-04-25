<?php
/**
 * Reports de Projectes - TimeTracker
 * Mostra estadístiques i gràfics dels projectes
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario es administrador
requireRole('admin');

// Obtener usuari actual
$usuari = obtenerUsuarioActual();

// Obtenir filtres de data (GET)
$dataInici = $_GET['data_inici'] ?? date('Y-m-01'); // Primer dia del mes actual
$dataFi = $_GET['data_fi'] ?? date('Y-m-d'); // Avui

// Validar dates
if (!empty($_GET['data_inici']) && !empty($_GET['data_fi'])) {
    $dataInici = $_GET['data_inici'];
    $dataFi = $_GET['data_fi'];
}

try {
    $conexion = obtenerConexion();
    
    // Llistat de projectes amb hores estimades i reals
    // SELECT específic sense usar *, amb JOIN entre taules
    $sqlProjectes = "SELECT 
                        p.id, 
                        p.nom, 
                        p.descripcio, 
                        p.hores_estimades,
                        COALESCE(SUM(rh.total_hores), 0) as hores_reals,
                        COUNT(DISTINCT rh.usuari_id) as empleats_assignats,
                        COUNT(rh.id) as registres_total
                     FROM projectes p
                     LEFT JOIN registres_hores rh ON p.id = rh.projecte_id
                         AND rh.data >= :data_inici 
                         AND rh.data <= :data_fi
                     WHERE p.actiu = 1
                     GROUP BY p.id, p.nom, p.descripcio, p.hores_estimades
                     ORDER BY hores_reals DESC";
    
    $stmt = $conexion->prepare($sqlProjectes);
    $stmt->bindParam(':data_inici', $dataInici, PDO::PARAM_STR);
    $stmt->bindParam(':data_fi', $dataFi, PDO::PARAM_STR);
    $stmt->execute();
    $projectes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dades per al gràfic de barres: hores per empleat en cada projecte
    $sqlHoresEmpleats = "SELECT 
                            p.id as projecte_id,
                            p.nom as projecte_nom,
                            u.id as empleat_id,
                            CONCAT(u.nom, ' ', u.cognoms) as empleat_nom,
                            COALESCE(SUM(rh.total_hores), 0) as hores
                         FROM projectes p
                         LEFT JOIN registres_hores rh ON p.id = rh.projecte_id
                             AND rh.data >= :data_inici 
                             AND rh.data <= :data_fi
                         LEFT JOIN usuaris u ON rh.usuari_id = u.id
                         WHERE p.actiu = 1
                         GROUP BY p.id, p.nom, u.id, u.nom, u.cognoms
                         ORDER BY p.id, hores DESC";
    
    $stmt = $conexion->prepare($sqlHoresEmpleats);
    $stmt->bindParam(':data_inici', $dataInici, PDO::PARAM_STR);
    $stmt->bindParam(':data_fi', $dataFi, PDO::PARAM_STR);
    $stmt->execute();
    $horesEmpleats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dades per al gràfic de pastís: distribució d'hores per projecte
    $sqlDistribucio = "SELECT 
                           p.id,
                           p.nom,
                           COALESCE(SUM(rh.total_hores), 0) as total_hores
                        FROM projectes p
                        LEFT JOIN registres_hores rh ON p.id = rh.projecte_id
                            AND rh.data >= :data_inici 
                            AND rh.data <= :data_fi
                        WHERE p.actiu = 1
                        GROUP BY p.id, p.nom
                        ORDER BY total_hores DESC";
    
    $stmt = $conexion->prepare($sqlDistribucio);
    $stmt->bindParam(':data_inici', $dataInici, PDO::PARAM_STR);
    $stmt->bindParam(':data_fi', $dataFi, PDO::PARAM_STR);
    $stmt->execute();
    $distribucio = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar dades per a Chart.js
    $projectesJson = json_encode($projectes, JSON_NUMERIC_CHECK);
    $horesEmpleatsJson = json_encode($horesEmpleats, JSON_NUMERIC_CHECK);
    $distribucioJson = json_encode($distribucio, JSON_NUMERIC_CHECK);
    
    // Calcular totals
    $totalEstimat = array_sum(array_column($projectes, 'hores_estimades'));
    $totalReal = array_sum(array_column($projectes, 'hores_reals'));
    
} catch (PDOException $e) {
    error_log("Error al cargar reports: " . $e->getMessage());
    $error = "Error al cargar los datos. Intente nuevamente.";
    $projectesJson = '[]';
    $horesEmpleatsJson = '[]';
    $distribucioJson = '[]';
    $projectes = [];
    $totalEstimat = 0;
    $totalReal = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Reports de Projectes</title>
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
        
        .page-title {
            color: white;
            margin-bottom: 2rem;
            font-size: 1.75rem;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
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
            height: 300px;
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
        
        .table-header h3 {
            margin: 0;
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
        
        .project-name {
            font-weight: 500;
            color: #333;
        }
        
        .project-desc {
            color: #666;
            font-size: 0.85rem;
        }
        
        .hours-value {
            font-weight: 500;
        }
        
        .deviation {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .deviation.positive {
            background: #d5f5e3;
            color: #27ae60;
        }
        
        .deviation.negative {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .deviation.neutral {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-detail {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.85rem;
        }
        
        .btn-detail:hover {
            background: #5a6fd6;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.85rem;
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
            header, .nav-links, .filters, .btn-print, .charts-grid, footer {
                display: none !important;
            }
            .page-title {
                color: #333;
            }
            .table-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
        
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .data-table {
                font-size: 0.85rem;
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
        <h1 class="page-title">📈 Reports de Projectes</h1>
        
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
        
        <!-- Estadístiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?php echo count($projectes); ?></div>
                <div class="label">Projectes Actius</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo number_format($totalEstimat, 1); ?>h</div>
                <div class="label">Hores Estimades</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo number_format($totalReal, 1); ?>h</div>
                <div class="label">Hores Reals</div>
            </div>
        </div>
        
        <!-- Gràfics -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>📊 Hores per Empleat i Projecte</h3>
                <div class="chart-container">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>🥧 Distribució d'Hores</h3>
                <div class="chart-container">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Taula de projectes -->
        <div class="table-card" id="printableArea">
            <div class="table-header">
                <h3>📋 Detall de Projectes</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Projecte</th>
                        <th>Hores Estimades</th>
                        <th>Hores Reals</th>
                        <th>Desviació</th>
                        <th>Empleats</th>
                        <th>Accions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projectes as $projecte): ?>
                        <?php
                        $desviacio = $projecte['hores_estimades'] > 0 
                            ? (($projecte['hores_reals'] - $projecte['hores_estimades']) / $projecte['hores_estimades']) * 100 
                            : 0;
                        $classeDesviacio = $desviacio > 0 ? 'negative' : ($desviacio < 0 ? 'positive' : 'neutral');
                        $iconaAlerta = $desviacio > 10 ? '⚠️ ' : '';
                        ?>
                        <tr>
                            <td>
                                <div class="project-name"><?php echo htmlspecialchars($projecte['nom']); ?></div>
                                <?php if ($desviacio > 10): ?>
                                    <div class="alert-warning">
                                        <?php echo $iconaAlerta; ?>Projecte sobrepassat en <?php echo number_format($desviacio, 1); ?>%
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="hours-value"><?php echo number_format($projecte['hores_estimades'], 2); ?>h</td>
                            <td class="hours-value" style="color: <?php echo $desviacio > 0 ? '#e74c3c' : '#27ae60'; ?>">
                                <?php echo number_format($projecte['hores_reals'], 2); ?>h
                            </td>
                            <td>
                                <span class="deviation <?php echo $classeDesviacio; ?>">
                                    <?php echo ($desviacio >= 0 ? '+' : '') . number_format($desviacio, 1); ?>%
                                </span>
                            </td>
                            <td><?php echo $projecte['empleats_assignats']; ?></td>
                            <td>
                                <a href="report_projecte.php?id=<?php echo $projecte['id']; ?>&data_inici=<?php echo $dataInici; ?>&data_fi=<?php echo $dataFi; ?>" 
                                   class="btn-detail">
                                    Veure detall
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
    
    <script>
        // Dades passades de PHP a JavaScript
        const horesEmpleats = <?php echo $horesEmpleatsJson; ?>;
        const distribucio = <?php echo $distribucioJson; ?>;
        
        // Configurar colors
        const colors = [
            '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe',
            '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'
        ];
        
        // Gràfic de barres - Hores per empleat i projecte
        function crearBarChart() {
            const ctx = document.getElementById('barChart').getContext('2d');
            
            // Agrupar dades per projecte
            const projectesData = {};
            horesEmpleats.forEach(item => {
                if (!projectesData[item.projecte_nom]) {
                    projectesData[item.projecte_nom] = { labels: [], data: [] };
                }
                if (item.empleat_nom) {
                    projectesData[item.projecte_nom].labels.push(item.empleat_nom);
                    projectesData[item.projecte_nom].data.push(item.hores);
                }
            });
            
            // Crear datasets per a cada projecte
            const datasets = [];
            const allLabels = new Set();
            let colorIndex = 0;
            
            Object.keys(projectesData).forEach((projecte, index) => {
                const projectData = projectesData[projecte];
                projectData.labels.forEach(label => allLabels.add(label));
                
                datasets.push({
                    label: projecte,
                    data: projectData.data,
                    backgroundColor: colors[colorIndex % colors.length],
                    borderColor: colors[colorIndex % colors.length],
                    borderWidth: 1
                });
                colorIndex++;
            });
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Array.from(allLabels),
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
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
        
        // Gràfic de pastís - Distribució d'hores
        function crearPieChart() {
            const ctx = document.getElementById('pieChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: distribucio.map(d => d.nom),
                    datasets: [{
                        data: distribucio.map(d => d.total_hores),
                        backgroundColor: colors.slice(0, distribucio.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        }
        
        // Aplicar filtres
        function aplicarFiltres() {
            const dataInici = document.getElementById('data_inici').value;
            const dataFi = document.getElementById('data_fi').value;
            window.location.href = `reports.php?data_inici=${dataInici}&data_fi=${dataFi}`;
        }
        
        // Inicialitzar gràfics
        document.addEventListener('DOMContentLoaded', function() {
            crearBarChart();
            crearPieChart();
        });
    </script>
</body>
</html>