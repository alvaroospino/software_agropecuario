
<?php
require_once __DIR__ . '/../config/config.php';

// Iniciar sesión si no está iniciada (para evitar errores)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Guardar mensaje antes de destruir la sesión
$_SESSION['logout_message'] = 'Has cerrado sesión correctamente';

// Eliminar todas las variables de sesión
$_SESSION = [];

// Destruir la sesión completamente
session_destroy();

// Redirigir al login con el mensaje
header("Location: " . APP_URL . "/views/login.php?logout=1");
exit;
?>
