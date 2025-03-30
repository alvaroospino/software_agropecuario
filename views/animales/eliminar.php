<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Requerir login
requireLogin();

// Verificar ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    setMessage('ID de animal no válido', 'danger');
    redirect('views/animales/lista.php');
}

$db = new Database();

// Verificar que el animal existe
$animal = $db->selectOne('SELECT * FROM animales WHERE id = ?', [$id]);
if (!$animal) {
    setMessage('Animal no encontrado', 'danger');
    redirect('views/animales/lista.php');
}

// Eliminar el animal
$result = $db->delete('animales', 'id = ?', [$id]);

if ($result) {
    setMessage('Animal eliminado correctamente');
} else {
    setMessage('Error al eliminar el animal', 'danger');
}

redirect('views/animales/lista.php');
?>