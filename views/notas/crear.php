<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$db = new Database();

$mensaje = '';
$tipo_mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $contenido = filter_input(INPUT_POST, 'contenido', FILTER_SANITIZE_STRING);
    $fecha_recordatorio = filter_input(INPUT_POST, 'fecha_recordatorio', FILTER_SANITIZE_STRING);
    $completada = isset($_POST['completada']) ? 1 : 0;
    $usuario_id = $_SESSION['user_id'] ?? 0; // Obtener el ID del usuario actual

    if ($usuario_id == 0) {
        $mensaje = 'Error: Usuario no autenticado';
        $tipo_mensaje = 'danger';
    } elseif (empty($titulo) || empty($contenido)) {
        $mensaje = 'El título y el contenido son obligatorios';
        $tipo_mensaje = 'danger';
    } else {
        $sql = "INSERT INTO notas (usuario_id, titulo, contenido, fecha_recordatorio, completada, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $params = [$usuario_id, $titulo, $contenido, $fecha_recordatorio, $completada];

        if ($db->query($sql, $params)) {
            $mensaje = 'Nota creada correctamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al crear la nota';
            $tipo_mensaje = 'danger';
        }
    }
}

include '../../includes/header.php';
?>

<div class="container mt-4">
    <h4>Nueva Nota</h4>
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contenido</label>
            <textarea name="contenido" class="form-control" rows="4" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha de Recordatorio</label>
            <input type="datetime-local" name="fecha_recordatorio" class="form-control">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="completada" name="completada">
            <label class="form-check-label" for="completada">Marcar como completada</label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>