<?php
session_start(); // Asegura que la sesión está disponible

require_once 'config/config.php';

// Si el usuario está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); // Redirige al usuario si ya está autenticado
    exit();
}

// Incluir el encabezado
include 'includes/header.php';
?>

<div class="row align-items-center">
    <div class="col-lg-6">
        <h1 class="display-4 mb-4">Gestión Agropecuaria</h1>
        <p class="lead">
            Bienvenido a nuestra plataforma de gestión agropecuaria. Administra tus animales, 
            finanzas y recordatorios de forma eficiente.
        </p>
        <div class="mt-5">
            <a href="<?= APP_URL ?>/views/register.php" class="btn btn-primary btn-lg me-3">Registrarse</a>
            <a href="<?= APP_URL ?>/views/login.php" class="btn btn-outline-primary btn-lg">Iniciar Sesión</a>
        </div>
    </div>
    <div class="col-lg-6">
        <img src="<?= APP_URL ?>/assets/images/hero-image.jpg" alt="Gestión Agropecuaria" class="img-fluid rounded shadow">
    </div>
</div>

<div class="row mt-5 py-5">
    <div class="col-md-4 mb-4">
        <div class="card h-100 dashboard-card">
            <div class="card-body text-center">
                <i class="bi bi-card-checklist fs-1 text-primary mb-3"></i>
                <h3>Gestión de Animales</h3>
                <p>Registra tus animales, realiza seguimiento a su crecimiento y producción.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100 dashboard-card">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack fs-1 text-primary mb-3"></i>
                <h3>Control Financiero</h3>
                <p>Administra tus ingresos y egresos, generando reportes detallados.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100 dashboard-card">
            <div class="card-body text-center">
                <i class="bi bi-journal-text fs-1 text-primary mb-3"></i>
                <h3>Notas y Recordatorios</h3>
                <p>Mantén al día tus actividades con recordatorios y notas importantes.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>