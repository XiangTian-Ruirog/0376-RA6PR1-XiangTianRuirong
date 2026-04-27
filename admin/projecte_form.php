<?php
/**
 * Formulari de Projectes - TimeTracker
 * Crear o editar projectes
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario es administrador
requireRole('admin');

// Obtener usuari actual
$usuari = obtenerUsuarioActual();

// Obtenir ID del projecte (si es vol editar)
$projecteId = $_GET['id'] ?? null;
$edicio = $projecteId !== null;

// Inicialitzar variables
$errors = [];
$nom = '';
$descripcio = '';
$horesEstimades = '';

try {
    $conexion = obtenerConexion();
    
    // Si es edició, carregar dades del projecte
    if ($edicio) {
        $sqlProjecte = "SELECT id, nom, descripcio, hores_estimades, actiu 
                        FROM projectes 
                        WHERE id = :id";
        
        $stmt = $conexion->prepare($sqlProjecte);
        $stmt->bindParam(':id', $projecteId, PDO::PARAM_INT);
        $stmt->execute();
        $projecte = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$projecte) {
            header('Location: projectes.php');
            exit;
        }
        
        $nom = $projecte['nom'];
        $descripcio = $projecte['descripcio'];
        $horesEstimades = $projecte['hores_estimades'];
    }
    
    // Processar formulari (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recollir i netejar dades
        $nom = isset($_POST['nom']) ? htmlspecialchars(trim($_POST['nom']), ENT_QUOTES, 'UTF-8') : '';
        $descripcio = isset($_POST['descripcio']) ? htmlspecialchars(trim($_POST['descripcio']), ENT_QUOTES, 'UTF-8') : '';
        $horesEstimades = isset($_POST['hores_estimades']) ? floatval($_POST['hores_estimades']) : 0;
        
        // Validacions
        if (empty($nom)) {
            $errors[] = 'El nom del projecte és obligatori.';
        } elseif (strlen($nom) < 3) {
            $errors[] = 'El nom del projecte ha de tenir almenys 3 caràcters.';
        }
        
        if ($horesEstimades < 0) {
            $errors[] = 'Les hores estimades no poden ser negatives.';
        }
        
        // Si no hi ha errors, guardar
        if (empty($errors)) {
            if ($edicio) {
                // Actualitzar projecte existent
                $sqlUpdate = "UPDATE projectes 
                              SET nom = :nom, 
                                  descripcio = :descripcio, 
                                  hores_estimades = :hores_estimades 
                              WHERE id = :id";
                
                $stmt = $conexion->prepare($sqlUpdate);
                $stmt->bindParam(':id', $projecteId, PDO::PARAM_INT);
                $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
                $stmt->bindParam(':descripcio', $descripcio, PDO::PARAM_STR);
                $stmt->bindParam(':hores_estimades', $horesEstimades, PDO::PARAM_STR);
                
                if ($stmt->execute()) {
                    header('Location: projectes.php?msg=actualitzat');
                    exit;
                } else {
                    $errors[] = 'Error en actualitzar el projecte. Intenteu-ho de nou.';
                }
            } else {
                // Crear nou projecte
                $sqlInsert = "INSERT INTO projectes (nom, descripcio, hores_estimades, actiu, created_at) 
                              VALUES (:nom, :descripcio, :hores_estimades, 1, NOW())";
                
                $stmt = $conexion->prepare($sqlInsert);
                $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
                $stmt->bindParam(':descripcio', $descripcio, PDO::PARAM_STR);
                $stmt->bindParam(':hores_estimades', $horesEstimades, PDO::PARAM_STR);
                
                if ($stmt->execute()) {
                    header('Location: projectes.php?msg=creat');
                    exit;
                } else {
                    $errors[] = 'Error en crear el projecte. Intenteu-ho de nou.';
                }
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Error en formulari projecte: " . $e->getMessage());
    $errors[] = 'Error en el sistema. Intenteu-ho de nou.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - <?php echo $edicio ? 'Editar' : 'Nou'; ?> Projecte</title>
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
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }
        
        .page-title {
            color: white;
            margin-bottom: 2rem;
            font-size: 1.75rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="number"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.85rem;
        }
        
        .error-list {
            list-style: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .error-list li {
            padding: 0.5rem 1rem;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-submit {
            flex: 1;
            padding: 0.875rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-cancel {
            flex: 1;
            padding: 0.875rem;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.2s;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
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
        <h1 class="page-title">
            <?php echo $edicio ? '✏️ Editar Projecte' : '➕ Nou Projecte'; ?>
        </h1>
        
        <div class="card">
            <?php if (!empty($errors)): ?>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <form method="POST" action="projecte_form.php<?php echo $edicio ? '?id=' . $projecteId : ''; ?>">
                <div class="form-group">
                    <label for="nom">Nom del Projecte <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="nom" 
                        name="nom" 
                        value="<?php echo htmlspecialchars($nom); ?>"
                        required 
                        placeholder="Ex: Desenvolupament Web"
                        minlength="3"
                    >
                    <small>El nom ha de ser únic i descriptiu.</small>
                </div>
                
                <div class="form-group">
                    <label for="descripcio">Descripció</label>
                    <textarea 
                        id="descripcio" 
                        name="descripcio" 
                        placeholder="Descriu breument l'objectiu del projecte..."
                    ><?php echo htmlspecialchars($descripcio); ?></textarea>
                    <small>Opcional. Pots incloure detalls sobre l'abast del projecte.</small>
                </div>
                
                <div class="form-group">
                    <label for="hores_estimades">Hores Estimades <span class="required">*</span></label>
                    <input 
                        type="number" 
                        id="hores_estimades" 
                        name="hores_estimades" 
                        value="<?php echo htmlspecialchars($horesEstimades); ?>"
                        required 
                        step="0.5"
                        min="0"
                        placeholder="Ex: 40"
                    >
                    <small>Estimació inicial d'hores per completar el projecte.</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <?php echo $edicio ? '💾 Guardar Canvis' : '✅ Crear Projecte'; ?>
                    </button>
                    <a href="projectes.php" class="btn-cancel">❌ Cancel·lar</a>
                </div>
            </form>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
</body>
</html>