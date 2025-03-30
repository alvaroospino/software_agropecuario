<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$db = new Database();
$notas = $db->select("SELECT * FROM notas ORDER BY fecha_registro DESC");

include '../../includes/header.php';
?>

<div class="container mt-4">
    <h4>Lista de Notas</h4>
    <a href="crear.php" class="btn btn-success mb-3">Nueva Nota</a>
    <ul class="list-group">
        <?php foreach ($notas as $nota): ?>
            <li class="list-group-item">
                <strong><?php echo htmlspecialchars($nota['titulo']); ?></strong>
                <a href="editar.php?id=<?php echo $nota['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                <a href="eliminar.php?id=<?php echo $nota['id']; ?>" class="btn btn-danger btn-sm">Eliminar</a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php include '../../includes/footer.php'; ?>
