<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
// Requerir login
requireLogin();

// Obtener el ID del usuario actual de la sesión
$usuario_id = $_SESSION['user_id']; // Ajusta esto si usas un nombre diferente en tu sesión

$db = new Database();
$errors = [];

// Obtener categorías existentes para autocompletar
$categorias = $db->select("SELECT DISTINCT categoria FROM transacciones ORDER BY categoria");

// Obtener estadísticas para las tarjetas informativas
$sqlTotalTransacciones = "SELECT COUNT(*) as total FROM transacciones WHERE usuario_id = ?";
$totalTransacciones = $db->selectOne($sqlTotalTransacciones, [$usuario_id])['total'] ?? 0;

$sqlTotalIngresos = "SELECT SUM(monto) as total FROM transacciones WHERE tipo = 'ingreso' AND usuario_id = ?";
$totalIngresos = $db->selectOne($sqlTotalIngresos, [$usuario_id])['total'] ?? 0;

$sqlTotalGastos = "SELECT SUM(monto) as total FROM transacciones WHERE tipo = 'egreso' AND usuario_id = ?";
$totalGastos = $db->selectOne($sqlTotalGastos, [$usuario_id])['total'] ?? 0;

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
    if (empty($fecha)) {
        $errors[] = 'La fecha es obligatoria';
    }
    
    if (empty($concepto)) {
        $errors[] = 'El concepto es obligatorio';
    }
    
    if (!in_array($tipo, ['ingreso', 'egreso'])) {
        $errors[] = 'El tipo debe ser ingreso o gasto';
    }
    
    if (empty($categoria)) {
        $errors[] = 'La categoría es obligatoria';
    }
    
    if ($monto === false || $monto <= 0) {
        $errors[] = 'El monto debe ser un número positivo';
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        $sql = "INSERT INTO transacciones (fecha, concepto, tipo, categoria, monto, notas, usuario_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $params = [$fecha, $concepto, $tipo, $categoria, $monto, $notas, $usuario_id];
        
        if ($db->query($sql, $params)) {
            setMessage('Transacción registrada correctamente.');
            redirect('views/finanzas/lista.php');
        } else {
            $errors[] = 'Error al registrar la transacción';
        }
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
    <title>Nueva Transacción - Gestión Agropecuaria</title>
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3>Gestión Agropecuaria</h3>
            <button type="button" class="btn-close text-white d-md-none" id="sidebarClose" aria-label="Close"></button>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= APP_URL ?>/dashboard.php" class="nav-link <?= $paginaActual === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-house-door me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= APP_URL ?>/views/animales/lista.php" class="nav-link <?= $paginaActual === 'animales' ? 'active' : '' ?>">
                    <i class="bi bi-list-ul me-2"></i> Animales
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= APP_URL ?>/views/finanzas/lista.php" class="nav-link <?= $paginaActual === 'finanzas' ? 'active' : '' ?>">
                    <i class="bi bi-cash-coin me-2"></i> Finanzas
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= APP_URL ?>/views/notas/listar.php" class="nav-link <?= $paginaActual === 'notas' ? 'active' : '' ?>">
                    <i class="bi bi-journal-text me-2"></i> Notas
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
                <span>Usuario Invitado</span>
            </div>
        </div>
    </nav>

    <!-- Overlay para cerrar sidebar en móviles -->
    <div id="sidebarOverlay" class="overlay"></div>

    <!-- Contenido principal -->
    <div class="content-wrapper">
        <!-- Header superior -->
        <div class="page-header d-flex justify-content-between align-items-center px-4 py-3">
            <div class="d-flex align-items-center">
                <button class="navbar-toggler me-3" type="button" id="sidebarToggler">
                    <i class="bi bi-list"></i>
                </button>
                <h4 class="mb-0 text-primary">Gestión de Finanzas</h4>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3"><?= date('d/m/Y') ?></span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/views/perfil/index.php">Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/views/logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Área de contenido -->
        <div class="page-content px-4 py-3">
            <!-- Resumen rápido -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="bi bi-receipt text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted">Total Transacciones</h6>
                                    <h3 class="mb-0"><?= $totalTransacciones ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted">Total Ingresos</h6>
                                    <h3 class="mb-0">$<?= number_format($totalIngresos, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="bi bi-graph-down-arrow text-danger fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted">Total Gastos</h6>
                                    <h3 class="mb-0">$<?= number_format($totalGastos, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario Principal -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-plus-circle text-primary me-2 fs-4"></i>
                        <h5 class="mb-0">Registrar Nueva Transacción</h5>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST" id="transaccionForm">
                        <div class="row">
                            <!-- Columna izquierda - Información básica -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">
                                            <i class="bi bi-info-circle me-1"></i> Información Básica
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="fecha" class="form-label">
                                                <i class="bi bi-calendar me-1"></i> Fecha <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control" id="fecha" name="fecha" 
                                                value="<?= date('Y-m-d') ?>" required>
                                            <div class="form-text">Fecha de la transacción</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="concepto" class="form-label">
                                                <i class="bi bi-tag me-1"></i> Concepto <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="concepto" name="concepto" 
                                                value="<?= htmlspecialchars($concepto ?? '') ?>" required>
                                            <div class="form-text">Descripción breve de la transacción</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="bi bi-arrow-left-right me-1"></i> Tipo <span class="text-danger">*</span>
                                            </label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check card py-2 px-3 border">
                                                        <input class="form-check-input" type="radio" name="tipo" id="tipoIngreso" value="ingreso" checked>
                                                        <label class="form-check-label" for="tipoIngreso">
                                                            <i class="bi bi-arrow-down-circle text-success me-1"></i> Ingreso
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check card py-2 px-3 border">
                                                        <input class="form-check-input" type="radio" name="tipo" id="tipoGasto" value="egreso">
                                                        <label class="form-check-label" for="tipoGasto">
                                                            <i class="bi bi-arrow-up-circle text-danger me-1"></i> Gasto
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna derecha - Detalles financieros -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">
                                            <i class="bi bi-cash me-1"></i> Detalles Financieros
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="categoria" class="form-label">
                                                <i class="bi bi-diagram-3 me-1"></i> Categoría <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="categoria" name="categoria" 
                                                list="listaCategorias" value="<?= htmlspecialchars($categoria ?? '') ?>" required>
                                            <datalist id="listaCategorias">
                                                <?php foreach ($categorias as $cat): ?>
                                                    <option value="<?= htmlspecialchars($cat['categoria']) ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                            <div class="form-text">Tipo de ingreso o gasto</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="monto" class="form-label">
                                                <i class="bi bi-currency-dollar me-1"></i> Monto <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" min="0.01" class="form-control" id="monto" name="monto" 
                                                    value="<?= htmlspecialchars($monto ?? '') ?>" required>
                                            </div>
                                            <div class="form-text">Valor de la transacción</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección de notas adicionales -->
                        <div class="card mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-journal-text me-1"></i> Notas Adicionales
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-0">
                                    <label for="notas" class="form-label">Observaciones adicionales</label>
                                    <textarea class="form-control" id="notas" name="notas" rows="3"><?= htmlspecialchars($notas ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vista previa -->
                        <div class="card mb-4" id="vistaPreviaCard">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-eye me-1"></i> Vista Previa
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Concepto</th>
                                                <th>Categoría</th>
                                                <th>Tipo</th>
                                                <th>Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td id="preview-fecha"><?= date('Y-m-d') ?></td>
                                                <td id="preview-concepto">-</td>
                                                <td id="preview-categoria">-</td>
                                                <td>
                                                    <span class="badge rounded-pill bg-success" id="preview-tipo">Ingreso</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold" id="preview-monto">$0.00</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-md-between">
                            <a href="<?= APP_URL ?>/views/finanzas/lista.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" id="btnLimpiar">
                                    <i class="bi bi-eraser me-1"></i> Limpiar Formulario
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Guardar Transacción
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para actualizar la vista previa
        function actualizarVistaPrevia() {
            const fecha = document.getElementById('fecha').value || '<?= date('Y-m-d') ?>';
            const concepto = document.getElementById('concepto').value || '-';
            const categoria = document.getElementById('categoria').value || '-';
            const tipoIngreso = document.getElementById('tipoIngreso').checked;
            const monto = document.getElementById('monto').value || '0.00';
            
            // Actualizar vista previa
            document.getElementById('preview-fecha').textContent = fecha;
            document.getElementById('preview-concepto').textContent = concepto;
            document.getElementById('preview-categoria').textContent = categoria;
            
            const tipoBadge = document.getElementById('preview-tipo');
            if (tipoIngreso) {
                tipoBadge.textContent = 'Ingreso';
                tipoBadge.className = 'badge rounded-pill bg-success';
            } else {
                tipoBadge.textContent = 'egreso';
                tipoBadge.className = 'badge rounded-pill bg-danger';
            }
            
            document.getElementById('preview-monto').textContent = '$' + parseFloat(monto || 0).toFixed(2);
        }
        
        // Evento para limpiar formulario
        document.getElementById('btnLimpiar').addEventListener('click', function() {
            document.getElementById('transaccionForm').reset();
            document.getElementById('fecha').value = '<?= date('Y-m-d') ?>';
            actualizarVistaPrevia();
        });
        
        // Vincular eventos de cambio a todos los inputs para actualizar vista previa
        const formInputs = document.querySelectorAll('#fecha, #concepto, #categoria, #monto, #tipoIngreso, #tipoGasto');
        formInputs.forEach(input => {
            input.addEventListener('input', actualizarVistaPrevia);
            input.addEventListener('change', actualizarVistaPrevia);
        });
        
        // Inicializar la vista previa
        actualizarVistaPrevia();
        
        // Validación de formulario
        const form = document.getElementById('transaccionForm');
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const required = form.querySelectorAll('[required]');
            
            required.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                // Mostrar mensaje de error en la parte superior
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                errorAlert.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Por favor complete todos los campos obligatorios.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                `;
                form.prepend(errorAlert);
                
                // Scroll al primer campo con error
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Sidebar toggle (para móviles)
        const sidebar = document.getElementById('sidebar');
        const sidebarToggler = document.getElementById('sidebarToggler');
        const overlay = document.getElementById('sidebarOverlay');
            
        if (sidebar && sidebarToggler && overlay) {
            sidebarToggler.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }

        // Responsive checks
        function checkWidth() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        }

        // Ejecutar al cargar y al cambiar tamaño
        window.addEventListener('resize', checkWidth);
        checkWidth();
    });
    </script>
</body>
</html>