<?php
// Configuración de la aplicación
define('APP_NAME', 'Gestión Agropecuaria');
define('APP_URL', 'http://localhost/proyecto_agropecuario');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gestion_agropecuaria');

// Configuración de sesión
session_start();

// Función para redireccionar
function redirect($page) {
    header('Location: ' . APP_URL . '/' . $page);
    exit;
}

// Función para mostrar mensajes
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type
    ];
}

// Función para obtener mensajes
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}
?>