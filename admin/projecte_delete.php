<?php
/**
 * Eliminació de Projectes - TimeTracker
 * Soft delete: canvia actiu=0 en lloc d'eliminar físicament
 */

// Iniciar sessió
session_start();

// Incluir archivo de autenticación
require_once __DIR__ . '/../includes/auth.php';

// Verificar que el usuario es administrador
requireRole('admin');

// Obtener usuari actual
$usuari = obtenerUsuarioActual();

// Obtenir ID del projecte
$projecteId = $_GET['id'] ?? 0;

if ($projecteId <= 0) {
    header('Location: projectes.php');
    exit;
}

try {
    $conexion = obtenerConexion();
    
    // Verificar que el projecte existeix
    $sqlCheck = "SELECT id, nom, actiu FROM projectes WHERE id = :id";
    $stmt = $conexion->prepare($sqlCheck);
    $stmt->bindParam(':id', $projecteId, PDO::PARAM_INT);
    $stmt->execute();
    $projecte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projecte) {
        $_SESSION['error'] = 'El projecte no existeix.';
        header('Location: projectes.php');
        exit;
    }
    
    if ($projecte['actiu'] == 0) {
        $_SESSION['error'] = 'El projecte ja està desactivat.';
        header('Location: projectes.php');
        exit;
    }
    
    // Comprovar si té registres d'hores associats
    $sqlRegistres = "SELECT COUNT(*) as total FROM registres_hores WHERE projecte_id = :id";
    $stmt = $conexion->prepare($sqlRegistres);
    $stmt->bindParam(':id', $projecteId, PDO::PARAM_INT);
    $stmt->execute();
    $totalRegistres = $stmt->fetchColumn();
    
    // Soft delete: actualitzar actiu=0 en lloc d'eliminar
    $sqlUpdate = "UPDATE projectes SET actiu = 0 WHERE id = :id";
    $stmt = $conexion->prepare($sqlUpdate);
    $stmt->bindParam(':id', $projecteId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Projecte '{$projecte['nom']}' desactivat correctament.";
        if ($totalRegistres > 0) {
            $_SESSION['info'] = "El projecte tenia $totalRegistres registres d'hores associats que s'han mantingut.";
        }
    } else {
        $_SESSION['error'] = 'Error en desactivar el projecte. Intenteu-ho de nou.';
    }
    
} catch (PDOException $e) {
    error_log("Error en eliminar projecte: " . $e->getMessage());
    $_SESSION['error'] = 'Error en el sistema. Intenteu-ho de nou.';
}

header('Location: projectes.php');
exit;
?>