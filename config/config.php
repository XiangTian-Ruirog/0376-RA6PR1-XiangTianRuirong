<?php
/**
 * Configuració global de TimeTracker
 * Constants i configuracions de seguretat
 */

// Desactivar error_reporting en producció (activar en desenvolupament)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Zona horària
date_default_timezone_set('Europe/Madrid');

// Constants de la base de dades
define('DB_HOST', 'localhost');
define('DB_NAME', 'timetracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Constants de l'aplicació
define('APP_NAME', 'TimeTracker');
define('APP_URL', '/');
define('SESSION_LIFETIME', 3600); // 1 hora

// Configuració de seguretat
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRE', 3600);

// Inici de sessió segur
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}