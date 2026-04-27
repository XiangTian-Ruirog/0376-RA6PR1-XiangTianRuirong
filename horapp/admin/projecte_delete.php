<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole('admin');

$pdo = getDB();
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: /admin/projectes.php?error=ID no vàlid.');
    exit;
}

// Obtenir estat actual
$stmt = $pdo->prepare('SELECT actiu FROM projectes WHERE id = :id');
$stmt->execute([':id' => $id]);
$projecte = $stmt->fetch();

if (!$projecte) {
    header('Location: /admin/projectes.php?error=Projecte no trobat.');
    exit;
}

// Soft delete: canviar actiu
$nou_estat = $projecte['actiu'] ? 0 : 1;
$stmt = $pdo->prepare('UPDATE projectes SET actiu = :actiu WHERE id = :id');
$stmt->execute([':actiu' => $nou_estat, ':id' => $id]);

$msg = $nou_estat ? 'Projecte activat correctament.' : 'Projecte desactivat correctament.';
header('Location: /admin/projectes.php?msg=' . urlencode($msg));
exit;