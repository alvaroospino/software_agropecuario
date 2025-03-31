<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$db = new Database();
$usuario_id = $_SESSION['user_id'] ?? 0;

$id = $_GET['id'] ?? null;
if (!$id) {
    die('ID no válido');
}

// Asegurar que el usuario solo pueda editar sus propias notas
$nota = $db->selectOne("SELECT * FROM notas WHERE id = ? AND usuario_id = ?", [$id, $usuario_id]);
if (!$nota) {
    die('Nota no encontrada o no tienes permisos para editarla');
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $contenido = filter_input(INPUT_POST, 'contenido', FILTER_SANITIZE_STRING);
    $fecha_recordatorio = filter_input(INPUT_POST, 'fecha_recordatorio', FILTER_SANITIZE_STRING);
    $completada = isset($_POST['completada']) ? 1 : 0;

    if (empty($titulo) || empty($contenido)) {
        $mensaje = 'El título y el contenido son obligatorios';
        $tipo_mensaje = 'danger';
    } else {
        $sql = "UPDATE notas SET titulo = ?, contenido = ?, fecha_recordatorio = ?, completada = ? WHERE id = ? AND usuario_id = ?";
        $params = [$titulo, $contenido, $fecha_recordatorio, $completada, $id, $usuario_id];

        if ($db->query($sql, $params)) {
            $mensaje = 'Nota actualizada correctamente';
            $tipo_mensaje = 'success';
            $nota = $db->selectOne("SELECT * FROM notas WHERE id = ?", [$id]); // Recargar datos
        } else {
            $mensaje = 'Error al actualizar la nota';
            $tipo_mensaje = 'danger';
        }
    }
}

include '../../includes/header.php';
?>

<div class="container mt-4">
    <h4>Editar Nota</h4>
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($nota['titulo']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contenido</label>
            <textarea name="contenido" class="form-control" rows="4" required><?php echo htmlspecialchars($nota['contenido']); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha de Recordatorio</label>
            <input type="datetime-local" name="fecha_recordatorio" class="form-control" value="<?php echo $nota['fecha_recordatorio']; ?>">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="completada" name="completada" <?php echo $nota['completada'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="completada">Marcar como completada</label>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>