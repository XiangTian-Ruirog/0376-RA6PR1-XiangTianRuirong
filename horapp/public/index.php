<?php
session_start();

if (isset($_SESSION['usuari_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/admin/dashboard.php');
    } else {
        header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/empleat/dashboard.php');
    }
} else {
    header('Location: /0376-RA6PR1-XiangTianRuirong/horapp/public/login.php');
}
exit;