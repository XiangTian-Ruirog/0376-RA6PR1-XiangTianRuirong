<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole('empleat');

$pdo = getDB();
$usuari_id = $_SESSION['usuari_id'];
$accio = $_POST['accio'] ?? '';

if ($accio === 'entrada') {
    $projecte_id = filter_var($_POST['projecte_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$projecte_id) {
        header('Location: /empleat/dashboard.php?error=Selecciona un projecte vàlid.');
        exit;
    }

    // Comprovar que no té ja una entrada oberta
    $stmt = $pdo->prepare('SELECT id FROM registres_hores WHERE usuari_id = :uid AND hora_sortida IS NULL AND data = CURDATE()');
    $stmt->execute([':uid' => $usuari_id]);
    if ($stmt->fetch()) {
        header('Location: /empleat/dashboard.php?error=Ja tens una entrada oberta avui.');
        exit;
    }

    // Inserir nova entrada
    $stmt = $pdo->prepare('INSERT INTO registres_hores (usuari_id, projecte_id, hora_entrada, data) VALUES (:uid, :pid, NOW(), CURDATE())');
    $stmt->execute([':uid' => $usuari_id, ':pid' => $projecte_id]);

    header('Location: /empleat/dashboard.php?msg=Entrada fitxada correctament!');
    exit;

} elseif ($accio === 'sortida') {
    $registre_id = filter_var($_POST['registre_id'] ?? 0, FILTER_VALIDATE_INT);
    $notes = htmlspecialchars($_POST['notes'] ?? '');

    if (!$registre_id) {
        header('Location: /empleat/dashboard.php?error=Error en fitxar la sortida.');
        exit;
    }

    // Comprovar que el registre pertany a aquest usuari
    $stmt = $pdo->prepare('SELECT id, hora_entrada FROM registres_hores WHERE id = :id AND usuari_id = :uid AND hora_sortida IS NULL');
    $stmt->execute([':id' => $registre_id, ':uid' => $usuari_id]);
    $registre = $stmt->fetch();

    if (!$registre) {
        header('Location: /empleat/dashboard.php?error=Registre no vàlid.');
        exit;
    }

    // Actualitzar sortida i calcular total hores
    $stmt = $pdo->prepare('
        UPDATE registres_hores
        SET hora_sortida = NOW(),
            total_hores = ROUND(TIME_TO_SEC(TIMEDIFF(NOW(), hora_entrada)) / 3600, 2),
            notes = :notes
        WHERE id = :id AND usuari_id = :uid
    ');
    $stmt->execute([':notes' => $notes, ':id' => $registre_id, ':uid' => $usuari_id]);

    header('Location: /empleat/dashboard.php?msg=Sortida fitxada correctament!');
    exit;

} else {
    header('Location: /empleat/dashboard.php');
    exit;
}