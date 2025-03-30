<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$db = new Database();

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lista.php');
    exit;
}

// Obtener ID de la transacción
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: lista.php');
    exit;
}

// Eliminar la transacción
$sql = "DELETE FROM transacciones WHERE id = ?";
$result = $db->query($sql, [$id]);

// Redireccionar con mensaje
if ($result) {
    header('Location: lista.php?mensaje=Transacción eliminada correctamente&tipo=success');
} else {
    header('Location: lista.php?mensaje=Error al eliminar la transacción&tipo=danger');
}
?>