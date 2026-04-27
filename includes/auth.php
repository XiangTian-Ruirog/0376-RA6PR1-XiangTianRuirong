<?php
/**
 * Funcions d'autenticació i seguretat - TimeTracker
 * Inclou protecció CSRF i validació de sessió
 */

// Carregar configuració
require_once __DIR__ . '/../config/config.php';

/**
 * Genera un token CSRF i el guarda a la sessió
 * @return string Token CSRF
 */
function generarTokenCSRF() {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || 
        empty($_SESSION[CSRF_TOKEN_EXPIRE_TIME]) || 
        $_SESSION[CSRF_TOKEN_EXPIRE_TIME] < time()) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_EXPIRE_TIME] = time() + CSRF_TOKEN_EXPIRE;
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Valida el token CSRF enviat per POST
 * @param string $token Token a validar
 * @return bool True si és vàlid
 */
function validarTokenCSRF($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Retorna un camp ocult amb el token CSRF per a formularis
 * @return string Camp HTML ocult
 */
function campTokenCSRF() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generarTokenCSRF() . '">';
}

/**
 * Verifica que l'usuari està autenticat
 * Redirigeix a login si no hi ha sessió
 */
function checkAuth() {
    if (!isset($_SESSION['usuario_autenticado']) || !$_SESSION['usuario_autenticado']) {
        header('Location: ' . APP_URL . 'public/login.php');
        exit;
    }
    
    // Verificar timeout de sessió
    if (isset($_SESSION['ultima_activitat']) && (time() - $_SESSION['ultima_activitat']) > SESSION_LIFETIME) {
        session_destroy();
        header('Location: ' . APP_URL . 'public/login.php?msg=timeout');
        exit;
    }
    
    $_SESSION['ultima_activitat'] = time();
}

/**
 * Verifica que l'usuari té el rol especificat
 * @param string $rol Rol requerit
 */
function requireRole($rol) {
    checkAuth();
    
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== $rol) {
        header('Location: ' . APP_URL . 'public/login.php?msg=unauthorized');
        exit;
    }
}

/**
 * Retorna l'usuari actual de la sessió
 * @return array Dades de l'usuari
 */
function obtenerUsuarioActual() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['usuario_nombre'] ?? 'Usuari',
        'email' => $_SESSION['usuario_email'] ?? '',
        'rol' => $_SESSION['usuario_rol'] ?? 'empleat'
    ];
}

/**
 * Verifica si l'usuari actual és admin
 * @return bool
 */
function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Obté la connexió a la base de dades
 * @return PDO Connexió PDO
 */
function obtenerConexion() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $opciones);
    } catch (PDOException $e) {
        error_log("Error de connexió: " . $e->getMessage());
        die("Error en el sistema. Contacteu amb l'administrador.");
    }
}

/**
 * Neteja i sanititza una entrada
 * @param string $data Dada a netejar
 * @return string Dada neta
 */
function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Redirigeix amb un missatge de feedback
 * @param string $url URL de destí
 * @param string $tipus Tipus de missatge (success, error, info)
 * @param string $missatge Missatge
 */
function redirigirConMensaje($url, $tipus, $missatge) {
    $_SESSION['flash_type'] = $tipus;
    $_SESSION['flash_message'] = $missatge;
    header('Location: ' . $url);
    exit;
}

/**
 * Mostra i elimina un missatge flash
 * @return string HTML del missatge
 */
function mostrarFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $tipus = $_SESSION['flash_type'] ?? 'info';
        $missatge = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        $classes = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'info' => 'alert-info',
            'warning' => 'alert-warning'
        ];
        
        $classe = $classes[$tipus] ?? $classes['info'];
        
        return '<div class="alert ' . $classe . ' alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($missatge) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    return '';
}