<?php
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['usuari_id'])) {
        header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/public/login.php');
        exit;
    }
}

function requireRole($rol) {
    checkAuth();
    if ($_SESSION['rol'] !== $rol) {
        if ($_SESSION['rol'] === 'admin') {
            header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/admin/dashboard.php');
        } else {
            header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/empleat/dashboard.php');
        }
        exit;
    }
}