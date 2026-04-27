<?php
/**
 * Página de login de TimeTracker
 * Procesa el inicio de sesión de usuarios
 */

// Iniciar sesión
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Si ya está autenticado, redirigir según rol
if (usuarioAutenticado()) {
    redirigirSegunRol();
}

// Inicializar variables
$error = '';
$email = obtenerEmailRecordado() ?? '';

// Procesar formulario cuando se envía via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario (solo via POST, nunca GET)
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $recordar = isset($_POST['recordar']);
    
    // Validar y limpiar email
    $emailLimpio = validarEmail($emailInput);
    
    if ($emailLimpio === false) {
        $error = 'Credenciales inválidas. Por favor, inténtelo de nuevo.';
    } elseif (empty($password)) {
        $error = 'Credenciales inválidas. Por favor, inténtelo de nuevo.';
    } else {
        // Intentar iniciar sesión
        $resultado = iniciarSesion($emailLimpio, $password);
        
        if ($resultado['success']) {
            // Establecer cookie para recordar email si se solicitó
            if ($recordar) {
                recordarEmail($emailLimpio);
            } else {
                eliminarCookieRecordarEmail();
            }
            
            // Redirigir según rol
            redirigirSegunRol();
        } else {
            // Mensaje de error genérico (no revelar si falla email o contraseña)
            $error = 'Credenciales inválidas. Por favor, inténtelo de nuevo.';
            $email = htmlspecialchars($emailLimpio, ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTracker - Iniciar Sesión</title>
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
        
        .login-card {
            background: white;
            border-radius: 10px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #667eea;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
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
        
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .remember-me label {
            color: #666;
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            font-size: 0.85rem;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo">⏱️</div>
            <h1>TimeTracker</h1>
            <p>Inicia sesión para continuar</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" autocomplete="off">
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    required 
                    placeholder="tu@email.com"
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="••••••••"
                >
            </div>
            
            <div class="form-options">
                <div class="remember-me">
                    <input type="checkbox" id="recordar" name="recordar" value="1">
                    <label for="recordar">Recordar mi email</label>
                </div>
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="footer">
            <p>¿Olvidaste tu contraseña? Contacta al administrador</p>
        </div>
    </div>
</body>
</html>