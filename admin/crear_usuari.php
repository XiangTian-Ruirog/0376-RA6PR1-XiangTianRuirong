<?php
/**
 * Página de creación de usuarios para administradores
 * Solo accesible para usuarios con rol 'admin'
 */

// Iniciar sesión
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario es administrador
requireRole('admin');

// Inicializar variables
$errores = [];
$exito = '';
$datosForm = [
    'nom' => '',
    'cognoms' => '',
    'email' => '',
    'rol' => 'empleat'
];

// Procesar formulario cuando se envía via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario (solo via POST)
    $nom = $_POST['nom'] ?? '';
    $cognoms = $_POST['cognoms'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $rol = $_POST['rol'] ?? 'empleat';
    
    // Guardar datos en variable para mostrar en formulario (limpios)
    $datosForm = [
        'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
        'cognoms' => htmlspecialchars($cognoms, ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($emailInput, ENT_QUOTES, 'UTF-8'),
        'rol' => $rol
    ];
    
    // Validar nombre
    if (empty(trim($nom))) {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (strlen(trim($nom)) < 2) {
        $errores[] = 'El nombre debe tener al menos 2 caracteres.';
    }
    
    // Validar apellidos
    if (empty(trim($cognoms))) {
        $errores[] = 'Los apellidos son obligatorios.';
    } elseif (strlen(trim($cognoms)) < 2) {
        $errores[] = 'Los apellidos deben tener al menos 2 caracteres.';
    }
    
    // Validar email
    $emailLimpio = validarEmail($emailInput);
    if ($emailLimpio === false) {
        $errores[] = 'El email no es válido.';
    }
    
    // Validar contraseña
    if (empty($password)) {
        $errores[] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    
    // Validar confirmación de contraseña
    if ($password !== $passwordConfirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    }
    
    // Validar rol
    $rolesValidos = ['admin', 'empleat'];
    if (!in_array($rol, $rolesValidos)) {
        $errores[] = 'El rol seleccionado no es válido.';
    }
    
    // Si no hay errores, proceder a crear el usuario
    if (empty($errores)) {
        try {
            $conexion = obtenerConexion();
            
            // Verificar si el email ya existe
            $sqlCheck = "SELECT id FROM usuaris WHERE email = :email";
            $stmtCheck = $conexion->prepare($sqlCheck);
            $stmtCheck->bindParam(':email', $emailLimpio, PDO::PARAM_STR);
            $stmtCheck->execute();
            
            if ($stmtCheck->fetch()) {
                $errores[] = 'Ya existe un usuario con ese email.';
            } else {
                // Cifrar contraseña
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar nuevo usuario
                $sqlInsert = "INSERT INTO usuaris (nom, cognoms, email, password_hash, rol, actiu, created_at) 
                              VALUES (:nom, :cognoms, :email, :password_hash, :rol, 1, NOW())";
                
                $stmtInsert = $conexion->prepare($sqlInsert);
                $stmtInsert->bindParam(':nom', $nom, PDO::PARAM_STR);
                $stmtInsert->bindParam(':cognoms', $cognoms, PDO::PARAM_STR);
                $stmtInsert->bindParam(':email', $emailLimpio, PDO::PARAM_STR);
                $stmtInsert->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
                $stmtInsert->bindParam(':rol', $rol, PDO::PARAM_STR);
                
                if ($stmtInsert->execute()) {
                    $exito = 'Usuario creado correctamente.';
                    // Limpiar datos del formulario
                    $datosForm = [
                        'nom' => '',
                        'cognoms' => '',
                        'email' => '',
                        'rol' => 'empleat'
                    ];
                } else {
                    $errores[] = 'Error al crear el usuario. Intente nuevamente.';
                }
            }
        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            $errores[] = 'Error en el sistema. Intente nuevamente.';
        }
    }
}

// Obtener usuario actual para mostrar en header
$usuario = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Crear Usuario</title>
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
            background: #667eea;
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem;
        }
        
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            margin-top: 1rem;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h1 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
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
        
        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .error-list li {
            padding: 0.25rem 0;
        }
        
        .error-list li::before {
            content: "•";
            margin-right: 0.5rem;
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
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.8rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn-submit {
            width: 100%;
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
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-back {
            display: inline-block;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
        }
        
        .btn-back:hover {
            text-decoration: underline;
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
        <div class="form-card">
            <div class="form-header">
                <h1>Crear Nuevo Usuario</h1>
                <p>Complete el formulario para registrar un nuevo usuario</p>
            </div>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <ul class="error-list">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($exito)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($exito, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="crear_usuari.php" autocomplete="off">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nombre <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="nom" 
                            name="nom" 
                            value="<?php echo $datosForm['nom']; ?>"
                            required 
                            placeholder="Nombre"
                            minlength="2"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="cognoms">Apellidos <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="cognoms" 
                            name="cognoms" 
                            value="<?php echo $datosForm['cognoms']; ?>"
                            required 
                            placeholder="Apellidos"
                            minlength="2"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo $datosForm['email']; ?>"
                        required 
                        placeholder="usuario@ejemplo.com"
                    >
                    <small>Se usará para iniciar sesión</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña <span class="required">*</span></label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="••••••••"
                        minlength="8"
                    >
                    <small>Mínimo 8 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña <span class="required">*</span></label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        required 
                        placeholder="••••••••"
                    >
                </div>
                
                <div class="form-group">
                    <label for="rol">Rol <span class="required">*</span></label>
                    <select id="rol" name="rol" required>
                        <option value="empleat" <?php echo $datosForm['rol'] === 'empleat' ? 'selected' : ''; ?>>Empleado</option>
                        <option value="admin" <?php echo $datosForm['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Crear Usuario</button>
            </form>
            
            <a href="dashboard.php" class="btn-back">← Volver al panel de administración</a>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> TimeTracker - Panel de Administración</p>
    </footer>
</body>
</html>