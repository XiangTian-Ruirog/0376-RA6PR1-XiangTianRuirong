<?php
session_start();
session_unset();
session_destroy();

// Eliminar la cookie de l'email
setcookie('remember_email', '', time() - 3600, '/');

header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/public/login.php');
exit;