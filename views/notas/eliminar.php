<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$db = new Database();

$id = $_GET['id'] ?? null;
if (!$id) {
    die('ID no válido');
}

$nota = $db->selectOne("SELECT * FROM notas WHERE id = ?", [$id]);
if (!$nota) {
    die('Nota no encontrada');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db->query("DELETE FROM notas WHERE id = ?", [$id])) {
        header("Location: lista.php?mensaje=Nota eliminada correctamente&tipo=success");
        exit;
    } else {
        $mensaje = 'Error al eliminar la nota';
        $tipo_mensaje = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="container mt-4">
    <h4>Eliminar Nota</h4>
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <p>¿Estás seguro de que quieres eliminar la nota "<strong><?php echo htmlspecialchars($nota['titulo']); ?></strong>"?</p>

    <form method="POST">
        <button type="submit" class="btn btn-danger">Eliminar</button>
        <a href="lista.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
