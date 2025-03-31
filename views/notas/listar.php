<?php
// Modificación para listar.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$db = new Database();
$usuario_id = $_SESSION['user_id'] ?? 0;

// Obtener notas del usuario actual
$notas = $db->select("SELECT * FROM notas WHERE usuario_id = ? ORDER BY fecha_registro DESC", [$usuario_id]);

// Procesar cambio de estado si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_completada'])) {
    $nota_id = filter_input(INPUT_POST, 'nota_id', FILTER_SANITIZE_NUMBER_INT);
    $estado_actual = filter_input(INPUT_POST, 'estado_actual', FILTER_SANITIZE_NUMBER_INT);
    $nuevo_estado = $estado_actual ? 0 : 1;
    
    if ($db->query("UPDATE notas SET completada = ? WHERE id = ? AND usuario_id = ?", 
                   [$nuevo_estado, $nota_id, $usuario_id])) {
        header("Location: listar.php?mensaje=Estado de nota actualizado&tipo=success");
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="container mt-4">
    <h4>Lista de Notas</h4>
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-<?php echo $_GET['tipo'] ?? 'info'; ?>"><?php echo htmlspecialchars($_GET['mensaje']); ?></div>
    <?php endif; ?>
    
    <a href="crear.php" class="btn btn-success mb-3">Nueva Nota</a>
    <ul class="list-group">
        <?php foreach ($notas as $nota): ?>
            <li class="list-group-item <?php echo $nota['completada'] ? 'list-group-item-success' : ''; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="nota_id" value="<?php echo $nota['id']; ?>">
                            <input type="hidden" name="estado_actual" value="<?php echo $nota['completada']; ?>">
                            <button type="submit" name="toggle_completada" class="btn btn-sm <?php echo $nota['completada'] ? 'btn-outline-success' : 'btn-outline-secondary'; ?> mr-2">
                                <i class="fas fa-check"></i> <?php echo $nota['completada'] ? 'Completada' : 'Marcar completada'; ?>
                            </button>
                        </form>
                        <strong style="<?php echo $nota['completada'] ? 'text-decoration: line-through;' : ''; ?>">
                            <?php echo htmlspecialchars($nota['titulo']); ?>
                        </strong>
                        <?php if ($nota['fecha_recordatorio']): ?>
                            <small class="text-muted ml-2">
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($nota['fecha_recordatorio'])); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="editar.php?id=<?php echo $nota['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="eliminar.php?id=<?php echo $nota['id']; ?>" class="btn btn-danger btn-sm">Eliminar</a>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        <?php if (empty($notas)): ?>
            <li class="list-group-item text-center">No hay notas registradas</li>
        <?php endif; ?>
    </ul>
</div>

<?php include '../../includes/footer.php'; ?>