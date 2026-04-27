<?php
/**
 * Sistema d'Alertes - TimeTracker
 * "Llista vermella" d'empleats amb incompliments
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
    
    // Alerta 1: Empleats que NO han fitxat entrada avui (o passades les 09:30 sense fitxar)
    // SELECT específic sense usar *, amb els camps necessaris
    $sqlNoFitxat = "SELECT u.id, u.nom, u.cognoms, u.email,
                           'no_fichado' as tipo_alerta,
                           'No ha registrado entrada hoy' as descripcion
                    FROM usuaris u
                    WHERE u.rol = 'empleat' AND u.actiu = 1
                      AND u.id NOT IN (
                          SELECT DISTINCT usuari_id 
                          FROM registres_hores 
                          WHERE data = CURDATE()
                      )";
    
    $stmt = $conexion->query($sqlNoFitxat);
    $alertesNoFitxat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Alerta 2: Empleats que porten menys de 7 hores si ja han fitxat sortida
    // Usa TIME_TO_SEC i TIMEDIFF per calcular hores
    $sqlPoquesHores = "SELECT u.id, u.nom, u.cognoms, u.email,
                              rh.total_hores,
                              TIME_TO_SEC(TIMEDIFF(rh.hora_sortida, rh.hora_entrada)) / 3600 as hores_calculades,
                              'pocas_horas' as tipo_alerta,
                              CONCAT('Solo trabajó ', ROUND(rh.total_hores, 2), ' horas') as descripcion
                       FROM usuaris u
                       INNER JOIN registres_hores rh ON u.id = rh.usuari_id
                       WHERE u.rol = 'empleat' AND u.actiu = 1
                         AND rh.data = CURDATE()
                         AND rh.hora_sortida IS NOT NULL
                         AND rh.total_hores < 7";
    
    $stmt = $conexion->query($sqlPoquesHores);
    $alertesPoquesHores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Alerta 3: Empleats treballant més de 10 hores (sobrecàrrega)
    $sqlSobrecarrega = "SELECT u.id, u.nom, u.cognoms, u.email,
                               rh.hora_entrada,
                               ROUND(TIMESTAMPDIFF(MINUTE, rh.hora_entrada, NOW()) / 60, 2) as hores_acumulades,
                               'sobrecarga' as tipo_alerta,
                               CONCAT('Lleva trabajando ', ROUND(TIMESTAMPDIFF(MINUTE, rh.hora_entrada, NOW()) / 60, 2), ' horas') as descripcion
                        FROM usuaris u
                        INNER JOIN registres_hores rh ON u.id = rh.usuari_id
                        WHERE u.rol = 'empleat' AND u.actiu = 1
                          AND rh.data = CURDATE()
                          AND rh.hora_sortida IS NULL
                          AND TIMESTAMPDIFF(MINUTE, rh.hora_entrada, NOW()) > 600"; // més de 10 hores
    
    $stmt = $conexion->query($sqlSobrecarrega);
    $alertesSobrecarrega = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar totes les alertes
    $totesAlertes = array_merge($alertesNoFitxat, $alertesPoquesHores, $alertesSobrecarrega);
    
    // Comptadors
    $totalAlertes = count($totesAlertes);
    $alertesNoFitxatCount = count($alertesNoFitxat);
    $alertesPoquesHoresCount = count($alertesPoquesHores);
    $alertesSobrecarregaCount = count($alertesSobrecarrega);
    
} catch (PDOException $e) {
    error_log("Error al cargar alertas: " . $e->getMessage());
    $error = "Error al cargar los datos. Intente nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Alertas</title>
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
        
        .alert-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .summary-card .number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .summary-card .label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .summary-card.red .number { color: #e74c3c; }
        .summary-card.orange .number { color: #f39c12; }
        .summary-card.yellow .number { color: #f1c40f; }
        .summary-card.total .number { color: #667eea; }
        
        .alert-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
        }
        
        .section-title.red {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .section-title.orange {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }
        
        .section-title.yellow {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            color: white;
        }
        
        .section-title .badge {
            background: rgba(255,255,255,0.3);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .card {
            background: white;
            border-radius: 0 0 10px 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1fr;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            color: #666;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1fr;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }
        
        .table-row:last-child {
            border-bottom: none;
        }
        
        .table-row.alert-row {
            background: #fff5f5;
        }
        
        .table-row.alert-row:hover {
            background: #ffebeb;
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
            background: #e74c3c;
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
        
        .alert-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .alert-type.no_fichado {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .alert-type.pocas_horas {
            background: #fdebd0;
            color: #e67e22;
        }
        
        .alert-type.sobrecarga {
            background: #fef9e7;
            color: #f39c12;
        }
        
        .alert-desc {
            color: #666;
            font-size: 0.9rem;
        }
        
        .hours-value {
            font-weight: 500;
            color: #e74c3c;
        }
        
        .sin-alertas {
            text-align: center;
            padding: 3rem;
            color: #27ae60;
            background: #f0fff4;
        }
        
        .sin-alertas .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        @media (max-width: 992px) {
            .alert-summary {
                grid-template-columns: repeat(2, 1fr);
            }
            .table-header, .table-row {
                grid-template-columns: 1fr 1fr;
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
        <a href="alertes.php" class="active">🚨 Alertas</a>
        <a href="crear_usuari.php">➕ Nuevo Usuario</a>
    </div>
    
    <main>
        <h1 class="page-title">🚨 Lista de Alertas</h1>
        
        <!-- Resum d'alertes -->
        <div class="alert-summary">
            <div class="summary-card red">
                <div class="number"><?php echo $alertesNoFitxatCount; ?></div>
                <div class="label">No Ficharon</div>
            </div>
            <div class="summary-card orange">
                <div class="number"><?php echo $alertesPoquesHoresCount; ?></div>
                <div class="label">< 7 Horas</div>
            </div>
            <div class="summary-card yellow">
                <div class="number"><?php echo $alertesSobrecarregaCount; ?></div>
                <div class="label">Sobrecarga</div>
            </div>
            <div class="summary-card total">
                <div class="number"><?php echo $totalAlertes; ?></div>
                <div class="label">Total Alertas</div>
            </div>
        </div>
        
        <?php if ($totalAlertes === 0): ?>
            <div class="card">
                <div class="sin-alertas">
                    <div class="icon">✅</div>
                    <h3>¡Todo en orden!</h3>
                    <p>No hay alertas que mostrar en este momento.</p>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Alerta: No han fitxat -->
            <?php if ($alertesNoFitxatCount > 0): ?>
                <div class="alert-section">
                    <div class="section-title red">
                        <span>🔴 Empleados que no han fichado hoy</span>
                        <span class="badge"><?php echo $alertesNoFitxatCount; ?></span>
                    </div>
                    <div class="card">
                        <div class="table-header">
                            <div>Empleado</div>
                            <div>Tipo</div>
                            <div>Descripción</div>
                            <div>Acción</div>
                        </div>
                        <?php foreach ($alertesNoFitxat as $alerta): ?>
                            <div class="table-row alert-row">
                                <div>
                                    <div class="empleat-info">
                                        <div class="empleat-avatar">
                                            <?php echo strtoupper(substr($alerta['nom'], 0, 1) . substr($alerta['cognoms'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="empleat-name">
                                                <?php echo htmlspecialchars($alerta['nom'] . ' ' . $alerta['cognoms']); ?>
                                            </div>
                                            <div class="empleat-email">
                                                <?php echo htmlspecialchars($alerta['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="alert-type no_fichado">No fichado</span>
                                </div>
                                <div class="alert-desc">
                                    <?php echo htmlspecialchars($alerta['descripcion']); ?>
                                </div>
                                <div>
                                    <a href="empleats.php" style="color: #667eea; text-decoration: none;">Ver perfil</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Alerta: Poques hores -->
            <?php if ($alertesPoquesHoresCount > 0): ?>
                <div class="alert-section">
                    <div class="section-title orange">
                        <span>🟠 Empleados con menos de 7 horas</span>
                        <span class="badge"><?php echo $alertesPoquesHoresCount; ?></span>
                    </div>
                    <div class="card">
                        <div class="table-header">
                            <div>Empleado</div>
                            <div>Tipo</div>
                            <div>Descripción</div>
                            <div>Horas</div>
                        </div>
                        <?php foreach ($alertesPoquesHores as $alerta): ?>
                            <div class="table-row alert-row">
                                <div>
                                    <div class="empleat-info">
                                        <div class="empleat-avatar">
                                            <?php echo strtoupper(substr($alerta['nom'], 0, 1) . substr($alerta['cognoms'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="empleat-name">
                                                <?php echo htmlspecialchars($alerta['nom'] . ' ' . $alerta['cognoms']); ?>
                                            </div>
                                            <div class="empleat-email">
                                                <?php echo htmlspecialchars($alerta['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="alert-type pocas_horas">Pocas horas</span>
                                </div>
                                <div class="alert-desc">
                                    <?php echo htmlspecialchars($alerta['descripcion']); ?>
                                </div>
                                <div class="hours-value">
                                    <?php echo number_format($alerta['total_hores'], 2); ?>h
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Alerta: Sobrecàrrega -->
            <?php if ($alertesSobrecarregaCount > 0): ?>
                <div class="alert-section">
                    <div class="section-title yellow">
                        <span>🟡 Empleados con sobrecarga (+10h)</span>
                        <span class="badge"><?php echo $alertesSobrecarregaCount; ?></span>
                    </div>
                    <div class="card">
                        <div class="table-header">
                            <div>Empleado</div>
                            <div>Tipo</div>
                            <div>Descripción</div>
                            <div>Horas</div>
                        </div>
                        <?php foreach ($alertesSobrecarrega as $alerta): ?>
                            <div class="table-row alert-row">
                                <div>
                                    <div class="empleat-info">
                                        <div class="empleat-avatar">
                                            <?php echo strtoupper(substr($alerta['nom'], 0, 1) . substr($alerta['cognoms'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="empleat-name">
                                                <?php echo htmlspecialchars($alerta['nom'] . ' ' . $alerta['cognoms']); ?>
                                            </div>
                                            <div class="empleat-email">
                                                <?php echo htmlspecialchars($alerta['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="alert-type sobrecarga">Sobrecarga</span>
                                </div>
                                <div class="alert-desc">
                                    <?php echo htmlspecialchars($alerta['descripcion']); ?>
                                </div>
                                <div class="hours-value">
                                    <?php echo number_format($alerta['hores_acumulades'], 2); ?>h
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
</body>
</html>