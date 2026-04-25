<?php
/**
 * Configuracion de la base de datos para TimeTracker
 * Este archivo maneja la conexión PDO a MySQL de forma segura
 */

// Definir constantes de configuración (en producción usar variables de entorno)
define('DB_HOST', 'localhost');
define('DB_NAME', 'timetracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Función para obtener la conexión PDO a la base de datos
 * @return PDO|null Retorna la instancia PDO o null en caso de error
 */
function obtenerConexion() {
    static $conexion = null;
    
    if ($conexion === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $conexion = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // Nunca mostrar la contraseña o detalles sensibles en producción
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
        }
    }
    
    return $conexion;
}

/**
 * Función para cerrar la conexión a la base de datos
 */
function cerrarConexion() {
    $conexion = null;
}