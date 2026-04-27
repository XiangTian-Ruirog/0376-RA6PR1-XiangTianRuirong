<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole('admin');

$pdo = getDB();
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: /admin/empleats.php?error=ID no vàlid.');
    exit;
}

// No es pot desactivar a si mateix
if ($id === $_SESSION['usuari_id']) {
    header('Location: /admin/empleats.php?error=No pots desactivar el teu propi usuari.');
    exit;
}

// Obtenir estat actual
$stmt = $pdo->prepare('SELECT actiu FROM usuaris WHERE id = :id');
$stmt->execute([':id' => $id]);
$usuari = $stmt->fetch();

if (!$usuari) {
    header('Location: /admin/empleats.php?error=Usuari no trobat.');
    exit;
}

// Canviar estat
$nou_estat = $usuari['actiu'] ? 0 : 1;
$stmt = $pdo->prepare('UPDATE usuaris SET actiu = :actiu WHERE id = :id');
$stmt->execute([':actiu' => $nou_estat, ':id' => $id]);

$msg = $nou_estat ? 'Usuari activat correctament.' : 'Usuari desactivat correctament.';
header('Location: /admin/empleats.php?msg=' . urlencode($msg));
exit;