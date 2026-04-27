<?php
/**
 * Página de logout de TimeTracker
 * Cierra la sesión del usuario de forma segura
 */

// Iniciar sesión para poder destruirla
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Cerrar sesión usando la función de auth.php
cerrarSesion();

// Eliminar cookie de recordar email si existe
eliminarCookieRecordarEmail();

// Redirigir a la página de login
header('Location: login.php');
exit;