<?php
/**
 * Lògica de fitxar entrada/sortida - TimeTracker
 * Processa els registres d'hores dels empleats
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticació
checkAuth();

// Si és admin, redirigir al dashboard d'admin
if (esAdmin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Obtener usuari actual
$usuari = obtenerUsuarioActual();
$usuariId = $usuari['id'];

// Obtenir acció (entrada o sortida)
$accio = $_GET['accio'] ?? $_POST['accio'] ?? '';

// Missatges
$missatge = '';
$error = false;

try {
    $conexion = obtenerConexion();
    
    if ($accio === 'entrada') {
        // ============ FITXAR ENTRADA ============
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $projecteId = $_POST['projecte_id'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            
            // Validar projecte
            if (empty($projecteId)) {
                $missatge = 'Debe seleccionar un proyecto.';
                $error = true;
            } else {
                // Verificar que el projecte existeix i està actiu
                $sqlProjecte = "SELECT id FROM projectes WHERE id = :id AND actiu = 1";
                $stmtProjecte = $conexion->prepare($sqlProjecte);
                $stmtProjecte->bindParam(':id', $projecteId, PDO::PARAM_INT);
                $stmtProjecte->execute();
                
                if (!$stmtProjecte->fetch()) {
                    $missatge = 'El proyecto seleccionado no es válido.';
                    $error = true;
                } else {
                    // Verificar que no té ja una entrada oberta
                    $sqlVerificar = "SELECT id FROM registres_hores 
                                     WHERE usuari_id = :usuari_id 
                                       AND data = CURDATE() 
                                       AND hora_sortida IS NULL
                                     LIMIT 1";
                    
                    $stmtVerificar = $conexion->prepare($sqlVerificar);
                    $stmtVerificar->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
                    $stmtVerificar->execute();
                    
                    if ($stmtVerificar->fetch()) {
                        $missatge = 'Ya tienes un registro abierto. Debes cerrar el anterior antes de iniciar uno nuevo.';
                        $error = true;
                    } else {
                        // Netejar notes
                        $notesNetes = htmlspecialchars(trim($notes), ENT_QUOTES, 'UTF-8');
                        
                        // INSERT del registre amb hora_entrada = NOW()
                        $sqlInsert = "INSERT INTO registres_hores 
                                      (usuari_id, projecte_id, hora_entrada, data, notes, created_at) 
                                      VALUES (:usuari_id, :projecte_id, NOW(), CURDATE(), :notes, NOW())";
                        
                        $stmtInsert = $conexion->prepare($sqlInsert);
                        $stmtInsert->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
                        $stmtInsert->bindParam(':projecte_id', $projecteId, PDO::PARAM_INT);
                        $stmtInsert->bindParam(':notes', $notesNetes, PDO::PARAM_STR);
                        
                        if ($stmtInsert->execute()) {
                            // Redirigir al dashboard
                            header('Location: dashboard.php?msg=entrada_ok');
                            exit;
                        } else {
                            $missatge = 'Error al registrar la entrada. Intente nuevamente.';
                            $error = true;
                        }
                    }
                }
            }
        }
        
        // Si és GET, mostrar formulari de selecció de projecte
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Verificar que no té ja una entrada oberta
            $sqlVerificar = "SELECT id FROM registres_hores 
                             WHERE usuari_id = :usuari_id 
                               AND data = CURDATE() 
                               AND hora_sortida IS NULL
                             LIMIT 1";
            
            $stmtVerificar = $conexion->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
            $stmtVerificar->execute();
            
            if ($stmtVerificar->fetch()) {
                // Ja té una entrada oberta, redirigir
                header('Location: dashboard.php?msg=ja_obert');
                exit;
            }
            
            // Obtenir projectes actius
            $sqlProjectes = "SELECT id, nom, descripcio FROM projectes WHERE actiu = 1 ORDER BY nom";
            $stmtProjectes = $conexion->query($sqlProjectes);
            $projectes = $stmtProjectes->fetchAll();
        }
        
    } elseif ($accio === 'sortida') {
        // ============ FITXAR SORTIDA ============
        
        // Verificar que té un registre obert
        $sqlRegistreObert = "SELECT rh.id, rh.hora_entrada, p.nom as projecte_nom
                             FROM registres_hores rh
                             INNER JOIN projectes p ON rh.projecte_id = p.id
                             WHERE rh.usuari_id = :usuari_id 
                               AND rh.data = CURDATE() 
                               AND rh.hora_sortida IS NULL
                             LIMIT 1";
        
        $stmtRegistre = $conexion->prepare($sqlRegistreObert);
        $stmtRegistre->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
        $stmtRegistre->execute();
        $registre = $stmtRegistre->fetch();
        
        if (!$registre) {
            // No té registre obert
            header('Location: dashboard.php?msg=no_registre');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // UPDATE del registre amb hora_sortida i total_hores
            $sqlUpdate = "UPDATE registres_hores 
                          SET hora_sortida = NOW(),
                              total_hores = ROUND(TIMESTAMPDIFF(MINUTE, hora_entrada, NOW()) / 60, 2)
                          WHERE id = :id AND usuari_id = :usuari_id";
            
            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':id', $registre['id'], PDO::PARAM_INT);
            $stmtUpdate->bindParam(':usuari_id', $usuariId, PDO::PARAM_INT);
            
            if ($stmtUpdate->execute()) {
                // Redirigir al dashboard
                header('Location: dashboard.php?msg=sortida_ok');
                exit;
            } else {
                $missatge = 'Error al registrar la salida. Intente nuevamente.';
                $error = true;
            }
        }
        
    } else {
        // Acció no vàlida
        header('Location: dashboard.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Error en fitxar: " . $e->getMessage());
    $missatge = 'Error en el sistema. Intente nuevamente.';
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - <?php echo $accio === 'entrada' ? 'Registrar Entrada' : 'Registrar Salida'; ?></title>
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
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .card-header h1 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .card-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background-color: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .info-box p {
            margin-bottom: 0.5rem;
            color: #555;
        }
        
        .info-box strong {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.8rem;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-submit.entrada {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        
        .btn-submit.salida {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            text-decoration: underline;
        }
        
        .project-option {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .project-option:last-child {
            border-bottom: none;
        }
        
        .project-name {
            font-weight: 500;
            color: #333;
        }
        
        .project-desc {
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($accio === 'entrada' && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
            <!-- Formulari de selecció de projecte per fitxar entrada -->
            <div class="card-header">
                <h1>▶️ Registrar Entrada</h1>
                <p>Selecciona el proyecto en el que comenzarás a trabajar</p>
            </div>
            
            <?php if ($error && !empty($missatge)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($missatge); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="fitxar.php?accio=entrada">
                <div class="form-group">
                    <label for="projecte_id">Proyecto <span class="required">*</span></label>
                    <select id="projecte_id" name="projecte_id" required>
                        <option value="">-- Selecciona un proyecto --</option>
                        <?php foreach ($projectes as $projecte): ?>
                            <option value="<?php echo $projecte['id']; ?>">
                                <?php echo htmlspecialchars($projecte['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notas (opcional)</label>
                    <textarea id="notes" name="notes" placeholder="Describe brevemente la tarea que realizarás..."></textarea>
                    <small>Máximo 500 caracteres</small>
                </div>
                
                <button type="submit" class="btn-submit entrada">▶️ Comenzar Jornada</button>
            </form>
            
        <?php elseif ($accio === 'sortida'): ?>
            <!-- Confirmació de sortida -->
            <div class="card-header">
                <h1>⏱️ Registrar Salida</h1>
                <p>Confirma que deseas finalizar tu jornada</p>
            </div>
            
            <?php if ($error && !empty($missatge)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($missatge); ?></div>
            <?php endif; ?>
            
            <div class="info-box">
                <p><strong>Proyecto:</strong> <?php echo htmlspecialchars($registre['projecte_nom']); ?></p>
                <p><strong>Hora de entrada:</strong> <?php echo date('H:i:s', strtotime($registre['hora_entrada'])); ?></p>
            </div>
            
            <form method="POST" action="fitxar.php?accio=sortida">
                <button type="submit" class="btn-submit salida">⏱️ Finalizar Jornada</button>
            </form>
            
        <?php else: ?>
            <!-- Missatge d'error -->
            <div class="card-header">
                <h1>⚠️ Error</h1>
            </div>
            
            <?php if (!empty($missatge)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($missatge); ?></div>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-cancel">← Volver al dashboard</a>
    </div>
</body>
</html>