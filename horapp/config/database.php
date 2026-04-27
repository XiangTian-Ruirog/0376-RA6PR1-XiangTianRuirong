<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'horapp');
define('DB_USER', 'isard');
define('DB_PASS', 'pirineus'); 
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die('Error de connexió a la base de dades. Contacta amb l\'administrador.');
        }
    }
    return $pdo;
}