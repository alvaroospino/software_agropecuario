<?php

// Iniciar sesión antes de cualquier otra cosa solo si aún no está activa
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de sesiones
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    session_start();
    
    // Regenera el ID de sesión periódicamente
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Configuración de la aplicación
define('APP_NAME', 'Gestión Agropecuaria');
define('APP_URL', 'http://localhost/proyecto_agropecuario');


// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gestion_agropecuaria');

;

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