<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
// Requerir login
requireLogin();
$db = new Database();

// Obtener ID de la transacción
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: lista.php');
    exit;
}

// Obtener categorías existentes para autocompletar
$categorias = $db->select("SELECT DISTINCT categoria FROM transacciones WHERE id != ? ORDER BY categoria", [$id]);

// Obtener datos de la transacción
$transaccion = $db->select("SELECT * FROM transacciones WHERE id = ?", [$id]);

if (empty($transaccion)) {
    header('Location: lista.php');
    exit;
}

$transaccion = $transaccion[0];
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $concepto = filter_input(INPUT_POST, 'concepto', FILTER_SANITIZE_STRING);
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);
    $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
    $notas = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_STRING);
    
    // Validaciones
    $errores = [];
    
    if (empty($fecha)) {
        $errores[] = 'La fecha es obligatoria';
    }
    
    if (empty($concepto)) {
        $errores[] = 'El concepto es obligatorio';
    }
    
    if (!in_array($tipo, ['ingreso', 'gasto'])) {
        $errores[] = 'El tipo debe ser ingreso o gasto';
    }
    
    if (empty($categoria)) {
        $errores[] = 'La categoría es obligatoria';
    }
    
    if ($monto === false || $monto <= 0) {
        $errores[] = 'El monto debe ser un número positivo';
    }
    
    // Si no hay errores, actualizar
    if (empty($errores)) {
        $sql = "UPDATE transacciones SET fecha = ?, concepto = ?, tipo = ?, categoria = ?, monto = ?, 
                notas = ?, updated_at = NOW() WHERE id = ?";
        $params = [$fecha, $concepto, $tipo, $categoria, $monto, $notas, $id];
        
        if ($db->query($sql, $params)) {
            $mensaje = 'Transacción actualizada correctamente';
            $tipo_mensaje = 'success';
            
            // Actualizar datos mostrados
            $transaccion['fecha'] = $fecha;
            $transaccion['concepto'] = $concepto;
            $transaccion['tipo'] = $tipo;
            $transaccion['categoria'] = $categoria;
            $transaccion['monto'] = $monto;
            $transaccion['notas'] = $notas;
        } else {
            $mensaje = 'Error al actualizar la transacción';
            $tipo_mensaje = 'danger';
        }
    } else {
        $mensaje = 'Por favor, corrija los siguientes errores: ' . implode(', ', $errores);
        $tipo_mensaje = 'danger';
    }
}

// Definir página actual para menú
$paginaActual = 'finanzas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Transacción - Gestión Financiera</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Layout y estructura base */
        body {
            min-height: 100vh;
            background-color: #f8f9fa;
            display: flex;
            overflow-x: hidden;
        }
        
        /* Estilos del sidebar */
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            background-color: #2c3e50;
            color: #fff;
            transition: all 0.3s;
            height: 100vh;
            position: fixed;
            z-index: 1030;
            left: 0;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: #1a2530;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 10px 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-footer {
            padding: 15px 20px;
            position: absolute;
            bottom: 0;
            width: 100%;
            background-color: #1a2530;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        /* Navbar superior */
        .top-navbar {
            height: 60px;
            background-color: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        /* Overlay para móviles */
        .overlay {
            display: none;
            position: fixed;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1025;
            opacity: 0;
            transition: all 0.5s ease-in-out;
        }
        
        .overlay.show {
            display: block;
            opacity: 1;
        }
        
        /* Contenido principal */
        .content-wrapper {
            flex: 1;
            width: 100%;
            transition: all 0.3s;
            margin-left: 250px;
        }
        
        .page-header {
            background-color: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        /* Componentes UI */
        .stat-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .form-text {
            font-size: 0.75rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            background-color: white;
        }
        
        .form-control, .form-select {
            padding: 0.6rem 1rem;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
            }
            
            .sidebar {
                margin-left: -250px;
                position: fixed;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
            
            .content-wrapper {
                width: 100%;
                margin-left: 0;
            }
            
            .navbar-toggler {
                display: block;
            }
        }
        
        @media (min-width: 769px) {
            .navbar-toggler {
                display: none;
            }
        }
        
        /* Estilo para la búsqueda */
        .search-container {
            width: 300px;
        }
        
        /* Campos obligatorios */
        .required-field::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }
        
        /* Estilos específicos para transacciones */
        .ingreso-color {
            color: #198754;
        }
        
        .gasto-color {
            color: #dc3545;
        }
        
        .tipo-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3>Gestión Financiera</h3>
            <button type="button" class="btn-close text-white d-md-none" id="sidebarClose" aria-label="Close"></button>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= APP_URL ?>/dashboard.php" class="nav-link <?= $paginaActual === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-house-door me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= APP_URL ?>/views/finanzas/lista.php" class="nav-link <?= $paginaActual === 'finanzas' ? 'active' : '' ?>">
                    <i class="bi bi-cash-coin me-2"></i> Transacciones
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= APP_URL ?>/configuracion.php" class="nav-link <?= $paginaActual === 'configuracion' ? 'active' : '' ?>">
                    <i class="bi bi-gear me-2"></i> Configuración
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <i class="bi bi-person-circle me-2"></i>
                <span>Usuario: <?= $_SESSION['user_name'] ?? 'Invitado' ?></span>
            </div>
        </div>
    </nav>

    <!-- Overlay para cerrar sidebar en móviles -->
    <div id="sidebarOverlay" class="overlay"></div>

    <!-- Contenido principal -->
    <div class="content-wrapper">
        <!-- Navbar superior -->
        <nav class="navbar navbar-expand-lg top-navbar">
            <div class="container-fluid px-4">
                <button class="navbar-toggler border-0" type="button" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                
                <a class="navbar-brand d-none d-md-block" href="#">Gestión de Transacciones</a>
                
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                1
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">Nuevas transacciones pendientes</a></li>
                        </ul>
                    </div>
                    
                    <div class="ms-3">
                        <span class="text-muted"><?= date('d/m/Y') ?></span>
                    </div>
                    
                    <div class="dropdown ms-3">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2 fs-5"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Contenido de la página -->
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 text-primary">
                    <i class="bi bi-pencil-square me-2"></i>Editar Transacción
                </h1>
                <a href="<?= APP_URL ?>/views/finanzas/lista.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Transacciones
                </a>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_mensaje; ?>" role="alert">
                    <i class="bi bi-info-circle me-2"></i><?= $mensaje; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Formulario de edición -->
                <div class="col-12">
                    <form method="POST">
                        <div class="card">
                            <div class="card-header py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle text-primary me-2"></i>Información Básica
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="fecha" class="form-label required-field">
                                            <i class="bi bi-calendar text-primary me-1"></i>Fecha
                                        </label>
                                        <input type="date" class="form-control" id="fecha" name="fecha" 
                                               value="<?= htmlspecialchars($transaccion['fecha']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="concepto" class="form-label required-field">
                                            <i class="bi bi-file-text text-primary me-1"></i>Concepto
                                        </label>
                                        <input type="text" class="form-control" id="concepto" name="concepto" 
                                               value="<?= htmlspecialchars($transaccion['concepto']); ?>" required>
                                        <small class="text-muted">Descripción breve de la transacción.</small>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label required-field">
                                            <i class="bi bi-arrow-left-right text-primary me-1"></i>Tipo
                                        </label>
                                        <div class="d-flex">
                                            <div class="form-check me-4">
                                                <input class="form-check-input" type="radio" name="tipo" id="tipoIngreso" 
                                                       value="ingreso" <?= $transaccion['tipo'] === 'ingreso' ? 'checked' : ''; ?>>
                                                <label class="form-check-label ingreso-color" for="tipoIngreso">
                                                    <i class="bi bi-graph-up-arrow me-1"></i>Ingreso
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="tipo" id="tipoGasto" 
                                                       value="gasto" <?= $transaccion['tipo'] === 'gasto' ? 'checked' : ''; ?>>
                                                <label class="form-check-label gasto-color" for="tipoGasto">
                                                    <i class="bi bi-graph-down-arrow me-1"></i>Gasto
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="monto" class="form-label required-field">
                                            <i class="bi bi-currency-dollar text-primary me-1"></i>Monto
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="monto" name="monto" 
                                                   step="0.01" min="0.01" value="<?= $transaccion['monto']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-tag text-primary me-2"></i>Categorización y Notas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="categoria" class="form-label required-field">
                                            <i class="bi bi-bookmark text-primary me-1"></i>Categoría
                                        </label>
                                        <input type="text" class="form-control" id="categoria" name="categoria" 
                                               list="listaCategorias" value="<?= htmlspecialchars($transaccion['categoria']); ?>" required>
                                        <datalist id="listaCategorias">
                                            <?php foreach ($categorias as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                        <small class="text-muted">Selecciona una categoría existente o crea una nueva.</small>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label for="notas" class="form-label">
                                            <i class="bi bi-journal-text text-primary me-1"></i>Notas (opcional)
                                        </label>
                                        <textarea class="form-control" id="notas" name="notas" rows="3"><?= htmlspecialchars($transaccion['notas'] ?? ''); ?></textarea>
                                        <small class="text-muted">Información adicional sobre la transacción.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="<?= APP_URL ?>/views/finanzas/lista.php" class="btn btn-light me-md-2">
                                <i class="bi bi-x-circle me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en dispositivos móviles
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });
            
            sidebarClose.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            // Cambia el color del monto según el tipo
            const tipoIngreso = document.getElementById('tipoIngreso');
            const tipoGasto = document.getElementById('tipoGasto');
            const montoInput = document.getElementById('monto');
            
            function actualizarEstiloMonto() {
                if (tipoIngreso.checked) {
                    montoInput.classList.add('text-success');
                    montoInput.classList.remove('text-danger');
                } else {
                    montoInput.classList.add('text-danger');
                    montoInput.classList.remove('text-success');
                }
            }
            
            tipoIngreso.addEventListener('change', actualizarEstiloMonto);
            tipoGasto.addEventListener('change', actualizarEstiloMonto);
            
            // Inicializar
            actualizarEstiloMonto();
        });
    </script>
</body>
</html>