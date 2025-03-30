<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
// Requerir login
requireLogin();

$db = new Database();

// Obtener estadísticas básicas
$totalAnimales = $db->selectOne('SELECT COUNT(*) as total FROM animales')['total'] ?? 0;
$ingresos = $db->selectOne('SELECT SUM(monto) as total FROM transacciones WHERE tipo = "ingreso"')['total'] ?? 0;
$egresos = $db->selectOne('SELECT SUM(monto) as total FROM transacciones WHERE tipo = "egreso"')['total'] ?? 0;
$balance = $ingresos - $egresos;
$notasPendientes = $db->selectOne('SELECT COUNT(*) as total FROM notas WHERE completada = 0')['total'] ?? 0;

// Obtener últimos animales registrados
$ultimosAnimales = $db->select('SELECT * FROM animales ORDER BY fecha_registro DESC LIMIT 5');

// Obtener últimas transacciones
$ultimasTransacciones = $db->select('SELECT * FROM transacciones ORDER BY fecha_registro DESC LIMIT 5');

// Obtener próximos recordatorios
$recordatorios = $db->select('SELECT * FROM notas WHERE fecha_recordatorio >= CURDATE() AND completada = 0 ORDER BY fecha_recordatorio ASC LIMIT 5');

// Saltamos el header estándar ya que vamos a usar un layout personalizado 
// e incluiremos los elementos necesarios del header directamente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Fuentes adicionales -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS personalizado -->
    <style>
        :root {
            --dark-bg: #1e2130;
            --card-bg: #272a3d;
            --sidebar-bg: #272a3d;
            --text-color: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.7);
            --border-color: #3a3f5c;
            --primary-color: #5e56ce;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --header-height: 60px;
            --sidebar-width: 240px;
            --sidebar-collapsed-width: 70px;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* Layout principal */
        .app-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 10px 0;
        }

        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu li a {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu li a:hover, 
        .sidebar-menu li a.active {
            color: var(--text-color);
            background-color: rgba(255, 255, 255, 0.05);
            border-left-color: var(--primary-color);
        }

        .sidebar-menu li a i {
            margin-right: 10px;
            font-size: 18px;
        }

        .user-profile {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid var(--border-color);
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .user-profile .user-info {
            flex: 1;
        }

        .user-profile .user-info h6 {
            margin: 0;
            font-size: 14px;
        }

        .user-profile .user-info span {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Main content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        /* Responsive para sidebar */
        @media (max-width: 992px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-toggle {
                display: block;
            }
        }

        /* Cards */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 500;
        }

        .card-body {
            padding: 20px;
        }

        /* Dashboard específicos */
        .dashboard-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dashboard-title .toggle-btn {
            display: none;
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .dashboard-title .toggle-btn {
                display: block;
            }
        }

        .performance-summary {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 1200px) {
            .performance-summary {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .performance-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .performance-summary {
                grid-template-columns: 1fr;
            }
        }

        .performance-card {
            background-color: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .performance-card h6 {
            font-size: 12px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.7);
        }

        .performance-card h4 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .chart-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .chart-section {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }

        .data-table {
            background-color: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
        }

        .data-table thead th {
            background-color: rgba(0, 0, 0, 0.2);
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 12px;
        }

        .data-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .table {
            color: var(--text-color);
            margin-bottom: 0;
        }

        .table td, .table th {
            border-top: 1px solid var(--border-color);
            padding: 12px 15px;
            vertical-align: middle;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
            text-transform: uppercase;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            border: none;
            margin-right: 5px;
            transition: all 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        /* Colores */
        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .bg-success {
            background-color: var(--success-color) !important;
        }

        .bg-danger {
            background-color: var(--danger-color) !important;
        }

        .bg-warning {
            background-color: var(--warning-color) !important;
        }

        .bg-info {
            background-color: var(--info-color) !important;
        }

        .bg-purple {
            background-color: #9b59b6 !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .text-success {
            color: var(--success-color) !important;
        }

        .text-danger {
            color: var(--danger-color) !important;
        }

        .text-warning {
            color: var(--warning-color) !important;
        }

        .text-info {
            color: var(--info-color) !important;
        }

        /* Dark theme tweaks for mejor contraste */
        .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        /* Badge clases adicionales */
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><?= APP_NAME ?></h3>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li>
                        <a href="<?= APP_URL ?>" class="active">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/animales/lista.php">
                            <i class="bi bi-database"></i> Animales
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/finanzas/lista.php">
                            <i class="bi bi-cash-stack"></i> Finanzas
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/notas/listar.php">
                            <i class="bi bi-journal-text"></i> Notas
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/finanzas/reporte.php">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/config/settings.php">
                            <i class="bi bi-gear"></i> Configuración
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- User Profile -->
            <div class="user-profile">
                <img src="https://via.placeholder.com/40" alt="User Profile">
                <div class="user-info">
                    <h6>John Doe</h6>
                    <span>Administrador</span>
                </div>
                <a href="<?= APP_URL ?>/logout.php" class="text-muted">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <div class="dashboard-title">
                <div>
                    <i class="bi bi-list toggle-btn me-2" id="sidebar-toggle"></i>
                    Dashboard
                </div>
                <div>
                    <span class="text-muted me-2"><?= date('d/m/Y') ?></span>
                </div>
            </div>
            
            <!-- Performance Summary Cards -->
            <div class="performance-summary">
                <div class="performance-card" style="border-left: 4px solid var(--primary-color);">
                    <h6>Total Animales</h6>
                    <h4><?= $totalAnimales ?></h4>
                </div>
                <div class="performance-card" style="border-left: 4px solid var(--success-color);">
                    <h6>Ingresos Totales</h6>
                    <h4>$<?= number_format($ingresos, 2) ?></h4>
                </div>
                <div class="performance-card" style="border-left: 4px solid var(--danger-color);">
                    <h6>Egresos Totales</h6>
                    <h4>$<?= number_format($egresos, 2) ?></h4>
                </div>
                <div class="performance-card" style="border-left: 4px solid var(--info-color);">
                    <h6>Notas Pendientes</h6>
                    <h4><?= $notasPendientes ?></h4>
                </div>
                <div class="performance-card" style="border-left: 4px solid var(--warning-color);">
                    <h6>Balance</h6>
                    <h4>$<?= number_format($balance, 2) ?></h4>
                </div>
                <div class="performance-card" style="border-left: 4px solid var(--primary-color);">
                    <h6>Eficiencia</h6>
                    <h4><?= $balance > 0 ? ceil(($ingresos / ($egresos > 0 ? $egresos : 1)) * 100) . '%' : '0%' ?></h4>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="chart-section">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Balance Financiero</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container mb-4">
                                    <canvas id="doughnutChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mt-3">
                                    <h5>Resumen Financiero</h5>
                                    <div class="d-flex justify-content-between my-2">
                                        <span class="text-muted">Ingresos:</span>
                                        <span class="fw-bold">$<?= number_format($ingresos, 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between my-2">
                                        <span class="text-muted">Egresos:</span>
                                        <span class="fw-bold">$<?= number_format($egresos, 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between my-2">
                                        <span class="text-muted">Balance:</span>
                                        <span class="fw-bold">$<?= number_format($balance, 2) ?></span>
                                    </div>
                                    <div class="progress mt-4" style="height: 10px; background-color: rgba(0,0,0,0.2);">
                                        <?php $percentage = $ingresos > 0 ? ($balance / $ingresos) * 100 : 0; ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= max(0, min(100, $percentage)) ?>%" 
                                            aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Estadísticas Semanales</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Data Tables Section -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Últimos Animales</h5>
                            <a href="<?= APP_URL ?>/views/animales/lista.php" class="btn btn-sm btn-primary">Ver todos</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($ultimosAnimales)): ?>
                                <div class="p-4">
                                    <p class="text-muted">No hay animales registrados aún.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tipo</th>
                                                <th>Peso</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimosAnimales as $index => $animal): ?>
                                                <?php 
                                                // Generar estados ficticios para el ejemplo
                                                $estados = ['EN PROGRESO', 'ACTIVO', 'REVISIÓN'];
                                                $estado = $estados[$index % count($estados)];
                                                
                                                // Determinar color según estado
                                                $estadoColor = $estado == 'EN PROGRESO' ? 'purple' : ($estado == 'ACTIVO' ? 'success' : 'warning');
                                                ?>
                                                <tr>
                                                    <td><?= $animal['identificacion'] ?></td>
                                                    <td><?= $animal['tipo_produccion'] ?></td>
                                                    <td><?= $animal['peso'] ?> kg</td>
                                                    <td>
                                                        <span class="status-badge bg-<?= $estadoColor ?>" style="color: white;">
                                                            <?= $estado ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="action-btn btn-primary">EDITAR</button>
                                                        <button class="action-btn btn-danger">BORRAR</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Últimas Transacciones</h5>
                            <a href="<?= APP_URL ?>/views/finanzas/lista.php" class="btn btn-sm btn-primary">Ver todas</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($ultimasTransacciones)): ?>
                                <div class="p-4">
                                    <p class="text-muted">No hay transacciones registradas aún.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Descripción</th>
                                                <th>Monto</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimasTransacciones as $transaccion): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?= $transaccion['tipo'] == 'ingreso' ? 'success' : 'danger' ?>">
                                                            <?= ucfirst($transaccion['tipo']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $transaccion['descripcion'] ?></td>
                                                    <td>$<?= number_format($transaccion['monto'], 2) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($transaccion['fecha'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Weekly Status and Recordatorios -->
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Próximos Recordatorios</h5>
                            <a href="<?= APP_URL ?>/views/notas/listar.php" class="btn btn-sm btn-primary">Ver todos</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recordatorios)): ?>
                                <p class="text-muted">No hay recordatorios próximos.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($recordatorios as $nota): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100" style="background-color: rgba(0,0,0,0.2);">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= $nota['titulo'] ?></h5>
                                                    <p class="card-text"><?= substr($nota['contenido'], 0, 100) ?>...</p>
                                                    <p class="card-text text-muted">
                                                        <small>
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?= date('d/m/Y H:i', strtotime($nota['fecha_recordatorio'])) ?>
                                                        </small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                if(sidebar.classList.contains('active')) {
                    mainContent.style.marginLeft = '0';
                } else {
                    mainContent.style.marginLeft = '0';
                }
            });
        }
        
        // Responsive checks
        const checkWidth = function() {
            if(window.innerWidth <= 992) {
                sidebar.classList.remove('active');
                mainContent.style.marginLeft = '0';
            } else {
                sidebar.classList.add('active');
                mainContent.style.marginLeft = 'var(--sidebar-width)';
            }
        };
        
        // Initial check
        checkWidth();
        
        // Check on resize
        window.addEventListener('resize', checkWidth);
        
        // Chart configurations
        // Gráfico Dona - Balance Financiero
        const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
        const doughnutChart = new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ingresos', 'Egresos'],
                datasets: [{
                    label: 'Finanzas',
                    data: [<?= $ingresos ?>, <?= $egresos ?>],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(231, 76, 60, 0.8)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(231, 76, 60, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });
        
    
    // Gráfico de Barras - Estadísticas Semanales
    const barCtx = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Ingresos',
                data: [12000, 19000, 15000, 25000, 22000, 18000, 30000],
                backgroundColor: 'rgba(52, 152, 219, 0.8)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#ffffff'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#ffffff'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#ffffff'
                    }
                }
            }
        }
    });
    
    // Gráfico de Barras Semanales
    const weeklyBarCtx = document.getElementById('weeklyBarChart').getContext('2d');
    const weeklyBarChart = new Chart(weeklyBarCtx, {
        type: 'bar',
        data: {
            labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
            datasets: [{
                label: 'Animales',
                data: [15, 18, 12, 22],
                backgroundColor: 'rgba(94, 86, 206, 0.8)',
                borderColor: 'rgba(94, 86, 206, 1)',
                borderWidth: 1
            },
            {
                label: 'Transacciones',
                data: [20, 15, 25, 18],
                backgroundColor: 'rgba(46, 204, 113, 0.8)',
                borderColor: 'rgba(46, 204, 113, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#ffffff'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#ffffff'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#ffffff'
                    }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>