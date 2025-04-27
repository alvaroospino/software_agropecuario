<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
// Obtener información del usuario actual
$currentUserId = $_SESSION['user_id'] ?? 0; // Asumiendo que guardas el ID del usuario en la sesión
$currentUser = [];

if ($currentUserId > 0) {
    $db = new Database();
    $currentUser = $db->selectOne('SELECT id, nombre, email FROM usuarios WHERE id = ?', [$currentUserId]);
}
$db = new Database();

// Parámetros de filtrado y paginación
$tipo = $_GET['tipo'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Construir consulta SQL con filtros
$sql = "SELECT * FROM transacciones WHERE 1=1";
$params = [];

if (!empty($tipo)) {
    $sql .= " AND tipo = ?";
    $params[] = $tipo;
}

if (!empty($desde)) {
    $sql .= " AND fecha >= ?";
    $params[] = $desde;
}

if (!empty($hasta)) {
    $sql .= " AND fecha <= ?";
    $params[] = $hasta;
}

$sql .= " ORDER BY fecha DESC, id DESC LIMIT $offset, $porPagina";

// Obtener transacciones
$transacciones = $db->select($sql, $params);

// Contar total para paginación
$sqlCount = "SELECT COUNT(*) as total FROM transacciones WHERE 1=1";
$paramsCount = [];

if (!empty($tipo)) {
    $sqlCount .= " AND tipo = ?";
    $paramsCount[] = $tipo;
}

if (!empty($desde)) {
    $sqlCount .= " AND fecha >= ?";
    $paramsCount[] = $desde;
}

if (!empty($hasta)) {
    $sqlCount .= " AND fecha <= ?";
    $paramsCount[] = $hasta;
}

$totalResultado = $db->select($sqlCount, $paramsCount);
$total = $totalResultado[0]['total'];
$totalPaginas = ceil($total / $porPagina);

// Calcular balance
$sqlBalance = "SELECT 
    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
    SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as total_gastos
    FROM transacciones";
$balance = $db->select($sqlBalance);

// Obtener categorías para filtros
$categorias = $db->select("SELECT DISTINCT categoria FROM transacciones ORDER BY categoria");

// Obtener información del usuario actual
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUser = [];

if ($currentUserId > 0) {
    $currentUser = $db->selectOne('SELECT id, nombre, email FROM usuarios WHERE id = ?', [$currentUserId]);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Agropecuaria - Finanzas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
   <style>
    /* Estilos generales */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

.main-container {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #1e2a3a;
    color: #fff;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.sidebar-menu {
    flex-grow: 1;
}

.sidebar-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin: 0;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #b3b8bd;
    text-decoration: none;
    transition: all 0.2s ease;
}

.sidebar-menu a:hover, .sidebar-menu a.active {
    background-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.sidebar-menu i {
    margin-right: 10px;
    font-size: 18px;
}

.user-profile {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 15px 20px;
    display: flex;
    align-items: center;
}

.user-info {
    flex-grow: 1;
    margin-right: 10px;
}

.user-info h6 {
    margin: 0 0 3px 0;
    font-size: 14px;
    color: #fff;
}

.user-info span {
    font-size: 12px;
    color: #b3b8bd;
}

/* Toggle Sidebar Button (Mobile) */
.toggle-sidebar {
    position: fixed;
    top: 10px;
    left: 10px;
    z-index: 1100;
    background-color: #1e2a3a;
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 4px;
    display: none;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* Content Area */
.content {
    flex-grow: 1;
    padding: 20px;
    background-color: #f8f9fa;
    overflow-x: hidden;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dee2e6;
}

.date-display {
    background-color: #e9ecef;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
}

/* Cards */
.stat-card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    border: none;
    border-radius: 8px;
    overflow: hidden;
}

.stat-card .card-header {
    padding: 12px 20px;
    font-weight: 600;
    border: none;
}

.stat-card .card-body {
    padding: 20px;
}

.stat-value {
    margin-bottom: 0;
    font-size: 28px;
    font-weight: 700;
}

/* Botones y acciones */
.action-btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add {
    background-color: #28a745;
    color: white;
    border: none;
}

.btn-add:hover {
    background-color: #218838;
    color: white;
}

/* Búsqueda */
.search-container {
    position: relative;
    width: 100%;
}

.search-input {
    width: 100%;
    padding: 10px 10px 10px 40px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background-color: #fff;
    font-size: 14px;
    transition: all 0.2s;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

/* Panel de filtros */
.filter-panel {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

/* Tabla de datos */
.data-table {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge-success {
    background-color: #28a745;
}

.badge-danger {
    background-color: #dc3545;
}

.btn-action {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: #495057;
    border: 1px solid #dee2e6;
    background-color: #fff;
    margin: 0 3px;
    transition: all 0.2s;
}

.btn-action:hover {
    background-color: #f8f9fa;
}

.btn-view:hover {
    color: #17a2b8;
}

.btn-edit:hover {
    color: #ffc107;
}

.btn-delete:hover {
    color: #dc3545;
}

/* Paginación */
.pagination-container {
    margin-top: 20px;
}

.page-link {
    color: #1e2a3a;
}

.page-item.active .page-link {
    background-color: #1e2a3a;
    border-color: #1e2a3a;
}

/* Animaciones */
.animate-fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsividad */
@media (max-width: 768px) {
    .main-container {
        position: relative;
    }
    
    .sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        height: 100%;
        z-index: 1000;
        box-shadow: 2px 0 8px rgba(0,0,0,0.2);
        overflow-y: auto; /* Permitir scroll vertical */
        display: flex;
        flex-direction: column;
        max-height: 100vh; /* Asegurar que no exceda la altura de la ventana */
    }
    
    .sidebar-menu {
        flex: 1;
        overflow-y: auto; /* Permitir scroll en el menú si es necesario */
    }
    
    .sidebar.show-sidebar {
        left: 0;
    }
    
    .toggle-sidebar {
        display: flex;
    }
    
    .content {
        margin-left: 0;
        width: 100%;
        padding-top: 60px;
    }
    
    /* Asegurar que el perfil de usuario siempre sea visible */
    .user-profile {
        position: sticky;
        bottom: 0;
        background-color: #1e2a3a;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 2;
    }
}
   </style>
</head>
<body>
    <!-- Toggle Sidebar Button (Mobile) -->
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="bi bi-list"></i>
    </button>

    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Gestión Agropecuaria</h3>
            </div>

            <div class="sidebar-menu">
                <ul>
                    <li>
                        <a href="<?= APP_URL ?>">
                            <i class="bi bi-house-door"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/animales/lista.php">
                            <i class="bi bi-database"></i> <span>Animales</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/finanzas/lista.php" class="active">
                            <i class="bi bi-cash-stack"></i> <span>Finanzas</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/views/notas/listar.php">
                            <i class="bi bi-journal-text"></i> <span>Notas</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/configuracion.php">
                            <i class="bi bi-gear"></i> <span>Configuración</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User Profile -->
            <div class="user-profile">
                <i class="bi bi-person-circle" style="font-size: 40px; color:rgb(33, 106, 165); margin-right: 12px;"></i>
                <div class="user-info">
                    <h6><?= isset($currentUser['nombre']) ? htmlspecialchars($currentUser['nombre']) : 'Usuario' ?></h6>
                    <span><?= isset($currentUser['email']) ? htmlspecialchars($currentUser['email']) : 'Invitado' ?></span>
                </div>
                <a href="<?= APP_URL ?>/views/logout.php" class="text-muted">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content">
            <!-- Header -->
            <div class="content-header animate-fade-in">
                <h1 class="mb-0">Gestión de Finanzas</h1>
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

            <!-- Stats Cards Row -->
            <div class="row animate-fade-in" style="animation-delay: 0.1s;">
                <div class="col-md-4">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-header">Ingresos Totales</div>
                        <div class="card-body">
                            <h2 class="stat-value">$<?php echo number_format($balance[0]['total_ingresos'], 2); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-danger text-white">
                        <div class="card-header">Gastos Totales</div>
                        <div class="card-body">
                            <h2 class="stat-value">$<?php echo number_format($balance[0]['total_gastos'], 2); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-header">Balance</div>
                        <div class="card-body">
                            <h2 class="stat-value">$<?php echo number_format($balance[0]['total_ingresos'] - $balance[0]['total_gastos'], 2); ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions & Search Row -->
            <div class="row animate-fade-in" style="animation-delay: 0.2s;">
                <div class="col-md-6 mb-3">
                    <button class="btn action-btn btn-add" onclick="window.location.href='crear.php'">
                        <i class="bi bi-plus-circle"></i> Nueva Transacción
                    </button>
                      <button class="btn btn-sm btn-outline-secondary" onclick="exportarExcel()">
        <i class="bi bi-file-earmark-excel"></i> Excel
    </button>
    
                </div>
                <div class="col-md-6 mb-3">
                    <div class="search-container">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Buscar transacción...">
                    </div>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="filter-panel animate-fade-in" id="tablaReporte" style="animation-delay: 0.3s;">
                <form method="GET" action="" class="row">
                    <div class="col-md-3 mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select name="tipo" id="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="ingreso" <?php echo $tipo === 'ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                            <option value="egreso" <?php echo $tipo === 'egreso' ? 'selected' : ''; ?>>Gasto</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="desde" class="form-label">Desde</label>
                        <input type="date" name="desde" id="desde" class="form-control" value="<?php echo $desde; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="hasta" class="form-label">Hasta</label>
                        <input type="date" name="hasta" id="hasta" class="form-control" value="<?php echo $hasta; ?>">
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                        <a href="lista.php" class="btn btn-secondary">Limpiar</a>
                    </div>
                </form>
            </div>

            <!-- Data Table -->
            <div class="data-table animate-fade-in" style="animation-delay: 0.4s;">
                <div class="table-header">
                    <h5 class="mb-0">Listado de Transacciones</h5>
                    <span class="badge bg-primary"><?php echo $total; ?> registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th>Monto</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transacciones)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay transacciones registradas</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transacciones as $t): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($t['fecha'])); ?></td>
                                        <td><?php echo htmlspecialchars($t['concepto']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $t['tipo'] === 'ingreso' ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo ucfirst($t['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($t['categoria']); ?></td>
                                        <td>$<?php echo number_format($t['monto'], 2); ?></td>
                                        <td class="text-center">
                                            <a href="#" class="btn btn-action btn-view" title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="editar.php?id=<?php echo $t['id']; ?>" class="btn btn-action btn-edit" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-action btn-delete eliminar-transaccion" data-id="<?php echo $t['id']; ?>" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPaginas > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Paginación de transacciones">
                            <ul class="pagination justify-content-center mb-0">
                                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                    <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?>&tipo=<?php echo $tipo; ?>&desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2rem;"></i>
                    <p class="mt-3">¿Está seguro de que desea eliminar esta transacción? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="formEliminar" method="POST" action="eliminar.php">
                        <input type="hidden" name="id" id="idEliminar" value="">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
       
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar en dispositivos móviles
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(event) {
            event.stopPropagation(); // Evita que el clic se propague
            sidebar.classList.toggle('show-sidebar');
        });
        
        // Cerrar sidebar al hacer clic fuera de él
        document.addEventListener('click', function(event) {
            const isClickInside = sidebar.contains(event.target) || toggleBtn.contains(event.target);
            
            if (!isClickInside && sidebar.classList.contains('show-sidebar')) {
                sidebar.classList.remove('show-sidebar');
            }
        });
        
        // Ajustar altura máxima en dispositivos móviles para asegurar visibilidad completa
        function adjustSidebarHeight() {
            if (window.innerWidth <= 768) {
                const windowHeight = window.innerHeight;
                sidebar.style.maxHeight = windowHeight + 'px';
            } else {
                sidebar.style.maxHeight = '';
            }
        }
        
        // Ejecutar al cargar y al cambiar el tamaño de la ventana
        adjustSidebarHeight();
        window.addEventListener('resize', adjustSidebarHeight);
    }
    
    // Configuración para eliminar transacción
    const botonesEliminar = document.querySelectorAll('.eliminar-transaccion');
    const idEliminarInput = document.getElementById('idEliminar');
    
    if (document.getElementById('eliminarModal')) {
        const eliminarModal = new bootstrap.Modal(document.getElementById('eliminarModal'), {
            keyboard: false
        });
        
        botonesEliminar.forEach(boton => {
            boton.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                idEliminarInput.value = id;
                eliminarModal.show();
            });
        });
    }
    
    // Filtrado de búsqueda en tabla
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                let found = false;
                const cells = row.querySelectorAll('td');
                
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchText)) {
                        found = true;
                    }
                });
                
                if (found) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

// Función para exportar a Excel
function exportarExcel() {
    // Obtener la tabla de datos
    const tabla = document.querySelector('table');
    const ws = XLSX.utils.table_to_sheet(tabla);
    
    // Crear libro y añadir hoja
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Transacciones');
    
    // Guardar archivo
    const date = new Date();
    const fileName = `Reporte_Finanzas_${date.getDate()}-${date.getMonth()+1}-${date.getFullYear()}.xlsx`;
    XLSX.writeFile(wb, fileName);
}
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>

</html>