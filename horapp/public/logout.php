<?php
session_start();
session_unset();
session_destroy();

// Eliminar la cookie de l'email
setcookie('remember_email', '', time() - 3600, '/');

header('Location: /public/login.php');
exit;