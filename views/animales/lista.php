<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Requerir login
requireLogin();

$db = new Database();

// Parámetros de filtrado y paginación
$busqueda = $_GET['busqueda'] ?? '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Construir consulta SQL con filtros
$sql = "SELECT * FROM animales";
$params = [];

if (!empty($busqueda)) {
    $sql .= " WHERE identificacion LIKE ? OR tipo_produccion LIKE ? OR ubicacion LIKE ?";
    $busquedaParam = "%$busqueda%";
    $params = [$busquedaParam, $busquedaParam, $busquedaParam];
}

$sql .= " ORDER BY fecha_registro DESC LIMIT $offset, $porPagina";

// Obtener animales
$animales = $db->select($sql, $params);

// Contar total para paginación
$sqlCount = "SELECT COUNT(*) as total FROM animales";
if (!empty($busqueda)) {
    $sqlCount .= " WHERE identificacion LIKE ? OR tipo_produccion LIKE ? OR ubicacion LIKE ?";
}
$totalAnimales = $db->selectOne($sqlCount, $params)['total'] ?? 0;
$totalPaginas = ceil($totalAnimales / $porPagina);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestión de Animales</h1>
    <a href="<?= APP_URL ?>/views/animales/crear.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo Animal
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="busqueda" placeholder="Buscar por ID, tipo o ubicación" value="<?= $busqueda ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($animales)): ?>
    <div class="alert alert-info">
        No se encontraron animales. <a href="<?= APP_URL ?>/views/animales/crear.php">Registra uno nuevo</a>.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover table-custom">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo Producción</th>
                    <th>Fecha Nacimiento</th>
                    <th>Peso (kg)</th>
                    <th>Ubicación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($animales as $animal): ?>
                    <tr>
                        <td><?= $animal['nombre'] ?></td>
                        <td><?= $animal['tipo_produccion'] ?></td>
                        <td><?= $animal['fecha_nacimiento'] ? date('d/m/Y', strtotime($animal['fecha_nacimiento'])) : 'No registrada' ?></td>
                        <td><?= $animal['peso'] ?></td>
                        <td><?= $animal['ubicacion'] ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/views/animales/editar.php?id=<?= $animal['id'] ?>" class="btn btn-sm btn-primary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= APP_URL ?>/views/animales/eliminar.php?id=<?= $animal['id'] ?>" class="btn btn-sm btn-danger" 
                               onclick="confirmarEliminar(event, '¿Estás seguro de eliminar este animal?')" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busqueda=<?= $busqueda ?>">Anterior</a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= $busqueda ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busqueda=<?= $busqueda ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>