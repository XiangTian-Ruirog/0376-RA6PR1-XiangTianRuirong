<?php
/**
 * Funciones de autenticación para TimeTracker
 * Manejo de sesiones y verificación de usuarios
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivo de configuración de base de datos
require_once __DIR__ . '/../config/database.php';

/**
 * Inicia sesión de usuario verificando credenciales
 * @param string $email Email del usuario
 * @param string $password Contraseña en texto plano
 * @return array ['success' => bool, 'message' => string]
 */
function iniciarSesion($email, $password) {
    try {
        $conexion = obtenerConexion();
        
        $sql = "SELECT id, nom, cognoms, email, password_hash, rol, actiu 
                FROM usuaris 
                WHERE email = :email AND actiu = 1";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Regenerar ID de sesión para prevenir fijación de sesión
            session_regenerate_id(true);
            
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nom'] . ' ' . $usuario['cognoms'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            $_SESSION['usuario_autenticado'] = true;
            
            return ['success' => true, 'message' => 'Sesión iniciada correctamente'];
        } else {
            return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
        }
    } catch (PDOException $e) {
        error_log("Error en inicio de sesión: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error en el sistema. Intente nuevamente.'];
    }
}

/**
 * Cierra la sesión del usuario actual
 */
function cerrarSesion() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Verifica si el usuario está autenticado
 * @return bool
 */
function usuarioAutenticado() {
    return isset($_SESSION['usuario_autenticado']) && $_SESSION['usuario_autenticado'] === true;
}

/**
 * Obtiene los datos del usuario actual en sesión
 * @return array|null
 */
function obtenerUsuarioActual() {
    if (usuarioAutenticado()) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'email' => $_SESSION['usuario_email'],
            'rol' => $_SESSION['usuario_rol']
        ];
    }
    return null;
}

/**
 * Redirige al login si el usuario no está autenticado
 */
function redirigirSiNoAutenticado() {
    if (!usuarioAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Verifica si el usuario tiene un rol específico
 * @param string $rol Rol a verificar ('admin' o 'empleat')
 * @return bool
 */
function tieneRol($rol) {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === $rol;
}

/**
 * Verifica si el usuario es administrador
 * @return bool
 */
function esAdmin() {
    return tieneRol('admin');
}

/**
 * Verifica que el usuario esté autenticado, redirigiendo al login si no lo está
 * Esta función debe llamarse al inicio de las páginas protegidas
 */
function checkAuth() {
    if (!usuarioAutenticado()) {
        // Guardar URL actual para redirigir después del login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /public/login.php');
        exit;
    }
}

/**
 * Verifica que el usuario tenga un rol específico, redirigiendo si no lo tiene
 * @param string $rolRequerido Rol requerido ('admin' o 'empleat')
 */
function requireRole($rolRequerido) {
    checkAuth(); // Primero verificamos que esté autenticado
    
    if (!tieneRol($rolRequerido)) {
        // Si no tiene el rol requerido, redirigir a su dashboard correspondiente
        $rolActual = $_SESSION['usuario_rol'] ?? 'empleat';
        if ($rolActual === 'admin') {
            header('Location: /admin/dashboard.php');
        } else {
            header('Location: /empleat/dashboard.php');
        }
        exit;
    }
}

/**
 * Limpia y valida los datos de entrada
 * @param string $data Dato a limpiar
 * @return string Dato limpio
 */
function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Valida un email
 * @param string $email Email a validar
 * @return string|false Email limpio si es válido, false si no
 */
function validarEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    return false;
}

/**
 * Establece una cookie para recordar el email del usuario
 * @param string $email Email a recordar
 * @param int $dias Días de duración de la cookie (por defecto 30)
 */
function recordarEmail($email, $dias = 30) {
    $tiempoExpiracion = time() + ($dias * 24 * 60 * 60);
    setcookie('recordar_email', $email, $tiempoExpiracion, '/');
}

/**
 * Elimina la cookie de recordar email
 */
function eliminarCookieRecordarEmail() {
    if (isset($_COOKIE['recordar_email'])) {
        setcookie('recordar_email', '', time() - 3600, '/');
    }
}

/**
 * Obtiene el email recordado de la cookie
 * @return string|null Email recordado o null
 */
function obtenerEmailRecordado() {
    return $_COOKIE['recordar_email'] ?? null;
}

/**
 * Redirige al usuario según su rol después del login
 */
function redirigirSegunRol() {
    // Verificar si hay una URL de redirección guardada
    $redirectUrl = $_SESSION['redirect_after_login'] ?? null;
    unset($_SESSION['redirect_after_login']);
    
    if ($redirectUrl && strpos($redirectUrl, '/public/') === 0) {
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // Redirección por defecto según rol
    $rol = $_SESSION['usuario_rol'] ?? 'empleat';
    if ($rol === 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /empleat/dashboard.php');
    }
    exit;
}
