<?php
// En auth.php
require_once __DIR__ . '/../config/config.php';

// Verifica si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirige a login si no está autenticado
function requireLogin() {
    if (!isLoggedIn()) {
        setMessage('Debes iniciar sesión para acceder a esta página', 'warning');
        redirect('login.php');
    }
}

// Redirige a dashboard si ya está autenticado
function requireGuest() {
    if (isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'dashboard.php' && basename($_SERVER['PHP_SELF']) !== 'index.php') {
        redirect('dashboard.php');
    }
}
?>