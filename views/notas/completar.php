<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$db = new Database();
$usuario_id = $_SESSION['user_id'] ?? 0;

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nota_id = filter_input(INPUT_POST, 'nota_id', FILTER_SANITIZE_NUMBER_INT);
    $redirect = filter_input(INPUT_POST, 'redirect', FILTER_SANITIZE_STRING) ?? 'listar';
    
    // Verificar que la nota exista y pertenezca al usuario
    $nota = $db->selectOne("SELECT id FROM notas WHERE id = ? AND usuario_id = ?", [$nota_id, $usuario_id]);
    
    if ($nota) {
        // Actualizar la nota como completada
        if ($db->query("UPDATE notas SET completada = 1 WHERE id = ?", [$nota_id])) {
            $mensaje = "Nota marcada como completada correctamente";
            $tipo = "success";
        } else {
            $mensaje = "Error al marcar la nota como completada";
            $tipo = "danger";
        }
    } else {
        $mensaje = "Nota no encontrada o no tienes permisos para modificarla";
        $tipo = "danger";
    }
    
    // Redireccionar según el parámetro redirect
    if ($redirect === 'dashboard') {
        header("Location: " . APP_URL . "/index.php?mensaje=$mensaje&tipo=$tipo");
    } else {
        header("Location: " . APP_URL . "/views/notas/listar.php?mensaje=$mensaje&tipo=$tipo");
    }
    exit;
} else {
    // Si no se envió el formulario, redirigir a la lista de notas
    header("Location: " . APP_URL . "/views/notas/listar.php");
    exit;
}
?>