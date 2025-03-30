<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
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
    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as total_gastos
    FROM transacciones";
$balance = $db->select($sqlBalance);

// Obtener categorías para filtros
$categorias = $db->select("SELECT DISTINCT categoria FROM transacciones ORDER BY categoria");

include '../../includes/header.php';
?>

<div class="container mt-4">
    <h1>Gestión de Finanzas</h1>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Ingresos Totales</h5>
                    <h3 class="card-text">$<?php echo number_format($balance[0]['total_ingresos'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Gastos Totales</h5>
                    <h3 class="card-text">$<?php echo number_format($balance[0]['total_gastos'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Balance</h5>
                    <h3 class="card-text">$<?php echo number_format($balance[0]['total_ingresos'] - $balance[0]['total_gastos'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5>Filtros</h5>
                        <a href="crear.php" class="btn btn-success">Nueva Transacción</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row">
                        <div class="col-md-3 mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select name="tipo" id="tipo" class="form-select">
                                <option value="">Todos</option>
                                <option value="ingreso" <?php echo $tipo === 'ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                                <option value="gasto" <?php echo $tipo === 'gasto' ? 'selected' : ''; ?>>Gasto</option>
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
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Listado de Transacciones</h5>
            <a href="reporte.php" class="btn btn-info">Generar Reporte</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th>Acciones</th>
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
                                        <span class="badge <?php echo $t['tipo'] === 'ingreso' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo ucfirst($t['tipo']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['categoria']); ?></td>
                                    <td>$<?php echo number_format($t['monto'], 2); ?></td>
                                    <td>
                                        <a href="editar.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <button class="btn btn-sm btn-danger eliminar-transaccion" data-id="<?php echo $t['id']; ?>">Eliminar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Paginación de transacciones">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?>&tipo=<?php echo $tipo; ?>&desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarModalLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar esta transacción? Esta acción no se puede deshacer.
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar modales de eliminación
        const botonesEliminar = document.querySelectorAll('.eliminar-transaccion');
        const modalEliminar = new bootstrap.Modal(document.getElementById('eliminarModal'));
        const idEliminarInput = document.getElementById('idEliminar');
        
        botonesEliminar.forEach(boton => {
            boton.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                idEliminarInput.value = id;
                modalEliminar.show();
            });
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>