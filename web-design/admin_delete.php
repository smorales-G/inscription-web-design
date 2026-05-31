<?php
// admin_delete.php
session_start();
require_once 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error'] = "ID de pre-inscripción no válido.";
    header("Location: admin.php");
    exit();
}

try {
    // 1. Verificar si el registro existe antes de borrar (opcional, pero buena práctica)
    $stmt_check = $pdo->prepare("SELECT nombre_completo FROM inscripciones WHERE id = :id");
    $stmt_check->execute([':id' => $id]);
    $nombre = $stmt_check->fetchColumn();

    if ($nombre) {
        // 2. Eliminar el registro
        $stmt_del = $pdo->prepare("DELETE FROM inscripciones WHERE id = :id");
        $stmt_del->execute([':id' => $id]);
        
        $_SESSION['mensaje'] = "La pre-inscripción de '$nombre' ha sido eliminada con éxito.";
    } else {
        $_SESSION['error'] = "El registro que intenta eliminar no existe.";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error de base de datos al eliminar el registro: " . $e->getMessage();
}

// Redirigir siempre de vuelta al panel de control
header("Location: admin.php");
exit();
?>
