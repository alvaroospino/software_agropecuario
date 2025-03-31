<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
// Requerir login
requireLogin();
// Obtener información del usuario actual
$currentUserId = $_SESSION['user_id'] ?? 0; // Asumiendo que guardas el ID del usuario en la sesión
$currentUser = [];

if ($currentUserId > 0) {
    $db = new Database();
    $currentUser = $db->selectOne('SELECT id, nombre, email FROM usuarios WHERE id = ?', [$currentUserId]);
}

$db = new Database();

// Obtener estadísticas básicas del usuario
$totalAnimales = $db->selectOne('SELECT COUNT(*) as total FROM animales WHERE usuario_id = ?', [$currentUserId])['total'] ?? 0;
$ingresos = $db->selectOne('SELECT SUM(monto) as total FROM transacciones WHERE usuario_id = ? AND tipo = "ingreso"', [$currentUserId])['total'] ?? 0;
$egresos = $db->selectOne('SELECT SUM(monto) as total FROM transacciones WHERE usuario_id = ? AND tipo = "egreso"', [$currentUserId])['total'] ?? 0;
$balance = $ingresos - $egresos;
$notasPendientes = $db->selectOne('SELECT COUNT(*) as total FROM notas WHERE usuario_id = ? AND completada = 0', [$currentUserId])['total'] ?? 0;

// Obtener últimos animales registrados por el usuario
$ultimosAnimales = $db->select('SELECT * FROM animales WHERE usuario_id = ? ORDER BY fecha_registro DESC LIMIT 5', [$currentUserId]);

// Obtener últimas transacciones del usuario
$ultimasTransacciones = $db->select('SELECT * FROM transacciones WHERE usuario_id = ? ORDER BY fecha_registro DESC LIMIT 5', [$currentUserId]);

// Obtener próximos recordatorios del usuario
$recordatorios = $db->select('SELECT * FROM notas WHERE usuario_id = ? AND fecha_recordatorio >= CURDATE() AND completada = 0 ORDER BY fecha_recordatorio ASC LIMIT 5', [$currentUserId]); 

// Obtener estadísticas semanales de ingresos (últimos 7 días)
$estadisticasSemanales = $db->select('
    SELECT 
        DATE_FORMAT(fecha, "%a") as dia,
        SUM(monto) as total
    FROM 
        transacciones
    WHERE 
        usuario_id = ? 
        AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND tipo = "ingreso"
    GROUP BY 
        DATE_FORMAT(fecha, "%a")
    ORDER BY 
        FIELD(DATE_FORMAT(fecha, "%a"), "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun")
', [$currentUserId]);

// Preparar datos para gráfico
$diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$valoresSemana = array_fill(0, 7, 0); // Inicializar con ceros

// Rellenar con datos reales donde existan
foreach ($estadisticasSemanales as $est) {
    $index = array_search(traducirDia($est['dia']), $diasSemana);
    if ($index !== false) {
        $valoresSemana[$index] = floatval($est['total']);
    }
}

// Función auxiliar para traducir días de inglés a español abreviado
function traducirDia($diaEn)
{
    $traduccion = [
        'Mon' => 'Lun',
        'Tue' => 'Mar',
        'Wed' => 'Mié',
        'Thu' => 'Jue',
        'Fri' => 'Vie',
        'Sat' => 'Sáb',
        'Sun' => 'Dom'
    ];
    return $traduccion[$diaEn] ?? $diaEn;
}
// seccion para el grafico de animales semanales 
// Obtener estadísticas de animales por semana (último mes)
$animalesSemana = $db->select('
    SELECT 
        WEEK(fecha_registro) as semana,
        COUNT(*) as total
    FROM 
        animales
    WHERE 
        usuario_id = ?
        AND fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
    GROUP BY 
        WEEK(fecha_registro)
    ORDER BY 
        semana ASC
', [$currentUserId]);

// Obtener estadísticas de transacciones por semana (último mes)
$transaccionesSemana = $db->select('
    SELECT 
        WEEK(fecha) as semana,
        COUNT(*) as total
    FROM 
        transacciones
    WHERE 
        usuario_id = ?
        AND fecha >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
    GROUP BY 
        WEEK(fecha)
    ORDER BY 
        semana ASC
', [$currentUserId]);

// Preparar datos para el gráfico semanal
$semanasLabel = [];
$animalesData = [];
$transaccionesData = [];

// Obtener números de semana para el último mes
$semanasUltimoMes = [];
for ($i = 0; $i < 4; $i++) {
    $fecha = date('Y-m-d', strtotime("-$i week"));
    $numSemana = date('W', strtotime($fecha));
    $semanasUltimoMes[] = $numSemana;
    $semanasLabel[] = "Semana $numSemana";
}
$semanasLabel = array_reverse($semanasLabel);

// Inicializar con ceros
$animalesData = array_fill(0, 4, 0);
$transaccionesData = array_fill(0, 4, 0);

// Rellenar datos de animales
foreach ($animalesSemana as $dato) {
    $index = array_search($dato['semana'], $semanasUltimoMes);
    if ($index !== false) {
        $animalesData[3 - $index] = intval($dato['total']);
    }
}

// Rellenar datos de transacciones
foreach ($transaccionesSemana as $dato) {
    $index = array_search($dato['semana'], $semanasUltimoMes);
    if ($index !== false) {
        $transaccionesData[3 - $index] = intval($dato['total']);
    }
}
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

        .table td,
        .table th {
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
    <?php if (isset($_GET['mensaje'])): ?>
    <div class="alert alert-<?= $_GET['tipo'] ?? 'info' ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_GET['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
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
            <i class="bi bi-person-circle" style="font-size: 40px; color:rgb(33, 106, 165); margin-right: 12px;"></i>                <div class="user-info">
                    <h6><?= isset($currentUser['nombre']) ? htmlspecialchars($currentUser['nombre']) : 'Usuario' ?></h6>
                    <span><?= isset($currentUser['email']) ? htmlspecialchars($currentUser['email']) : 'Invitado' ?></span>
                </div>
                <a href="<?= APP_URL ?>/views/logout.php" class="text-muted">
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Estadísticas Semanales</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-primary active" id="showIngresos">Ingresos</button>
                            <button type="button" class="btn btn-outline-primary" id="showAnimales">Animales</button>
                        </div>
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
                                                <th>Nombre</th>
                                                <th>Tipo</th>
                                                <th>Peso</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimosAnimales as $animal): ?>
                                                <?php
                                                // Determinar color según estado real
                                                $estadoColor = 'primary';
                                                if ($animal['estado'] == 'ACTIVO') {
                                                    $estadoColor = 'success';
                                                } elseif ($animal['estado'] == 'EN PROGRESO') {
                                                    $estadoColor = 'purple';
                                                } elseif ($animal['estado'] == 'REVISIÓN') {
                                                    $estadoColor = 'warning';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?= $animal['nombre'] ?></td>
                                                    <td><?= $animal['tipo_produccion'] ?></td>
                                                    <td><?= $animal['peso'] ?> kg</td>
                                                    <td>
                                                        <span class="status-badge bg-<?= $estadoColor ?>" style="color: white;">
                                                            <?= $animal['estado'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="<?= APP_URL ?>/views/animales/editar.php?id=<?= $animal['id'] ?>" class="action-btn btn-primary">EDITAR</a>
                                                        <button onclick="confirmarEliminacion(<?= $animal['id'] ?>)" class="action-btn btn-danger">BORRAR</>
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
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <form method="POST" action="<?= APP_URL ?>/views/notas/completar.php">
                                                <input type="hidden" name="nota_id" value="<?= $nota['id'] ?>">
                                                <input type="hidden" name="redirect" value="dashboard">
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check-circle"></i> Completar
                                                </button>
                                            </form>
                                            <a href="<?= APP_URL ?>/views/notas/editar.php?id=<?= $nota['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
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
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este animal? Esta acción no se puede deshacer.')) {
                window.location.href = '<?= APP_URL ?>/views/animales/eliminar.php?id=' + id;
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    if (sidebar.classList.contains('active')) {
                        mainContent.style.marginLeft = '0';
                    } else {
                        mainContent.style.marginLeft = '0';
                    }
                });
            }

            // Responsive checks
            const checkWidth = function() {
                if (window.innerWidth <= 992) {
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

        });
        // Gráfico de barras - Ingresos y Animales


        document.addEventListener('DOMContentLoaded', function() {
            // Contexto compartido para ambos gráficos
            const barCtx = document.getElementById('barChart').getContext('2d');
            let activeChart = null;

            // Datos para el gráfico de ingresos
            const ingresosData = {
                type: 'bar',
                data: {
                    labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                    datasets: [{
                        label: 'Ingresos',
                        data: [<?= implode(', ', $valoresSemana) ?>],
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
            };

            // Datos para el gráfico de animales y transacciones
            const animalesData = {
                type: 'bar',
                data: {
                    labels: <?= json_encode($semanasLabel) ?>,
                    datasets: [{
                            label: 'Animales',
                            data: <?= json_encode($animalesData) ?>,
                            backgroundColor: 'rgba(94, 86, 206, 0.8)',
                            borderColor: 'rgba(94, 86, 206, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Transacciones',
                            data: <?= json_encode($transaccionesData) ?>,
                            backgroundColor: 'rgba(46, 204, 113, 0.8)',
                            borderColor: 'rgba(46, 204, 113, 1)',
                            borderWidth: 1
                        }
                    ]
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
            };

            // Función para mostrar un gráfico específico
            function showChart(chartConfig) {
                // Destruir el gráfico activo si existe
                if (activeChart) {
                    activeChart.destroy();
                }
                // Crear el nuevo gráfico
                activeChart = new Chart(barCtx, chartConfig);
            }

            // Mostrar el gráfico de ingresos por defecto
            showChart(ingresosData);

            // Agregar event listeners a los botones
            document.getElementById('showIngresos').addEventListener('click', function() {
                document.getElementById('showIngresos').classList.add('active');
                document.getElementById('showIngresos').classList.remove('btn-outline-primary');
                document.getElementById('showIngresos').classList.add('btn-primary');

                document.getElementById('showAnimales').classList.remove('active');
                document.getElementById('showAnimales').classList.remove('btn-primary');
                document.getElementById('showAnimales').classList.add('btn-outline-primary');

                showChart(ingresosData);
            });

            document.getElementById('showAnimales').addEventListener('click', function() {
                document.getElementById('showAnimales').classList.add('active');
                document.getElementById('showAnimales').classList.remove('btn-outline-primary');
                document.getElementById('showAnimales').classList.add('btn-primary');

                document.getElementById('showIngresos').classList.remove('active');
                document.getElementById('showIngresos').classList.remove('btn-primary');
                document.getElementById('showIngresos').classList.add('btn-outline-primary');

                showChart(animalesData);
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>