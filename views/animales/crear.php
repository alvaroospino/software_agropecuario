<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Requerir login
requireLogin();


$db = new Database();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo_produccion = trim($_POST['tipo_produccion'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $usuario_id = $_SESSION['user_id'] ?? null; // Obtener usuario de la sesión

    // Validaciones
    if (empty($nombre)) {
        $errors[] = 'El nombre del animal es obligatorio.';
    }

    if (empty($tipo_produccion)) {
        $errors[] = 'El tipo de producción es obligatorio.';
    }

    if (!empty($peso) && !is_numeric($peso)) {
        $errors[] = 'El peso debe ser un número válido.';
    }

    if (!$usuario_id) {
        $errors[] = 'Usuario no identificado.';
    }

    // Verificar si el nombre ya existe
    if (empty($errors)) {
        $existingAnimal = $db->selectOne('SELECT id FROM animales WHERE nombre = ?', [$nombre]);

        if ($existingAnimal) {
            $errors[] = 'Ya existe un animal con este nombre.';
        }
    }

    // Guardar animal
    if (empty($errors)) {
        $animalData = [
            'nombre' => $nombre,
            'tipo_produccion' => $tipo_produccion,
            'fecha_nacimiento' => !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
            'peso' => !empty($peso) ? $peso : null,
            'ubicacion' => $ubicacion,
            'usuario_id' => $_SESSION['user_id'] // <-- Agregar el usuario de la sesión
        ];        

        $id = $db->insert('animales', $animalData);

        if ($id) {
            setMessage('Animal registrado correctamente.');
            redirect('views/animales/lista.php');
        } else {
            $errors[] = 'Error al registrar el animal. Inténtalo de nuevo.';
        }
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Registrar Nuevo Animal</h1>
    <a href="<?= APP_URL ?>/views/animales/lista.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Volver a la lista
    </a>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                           value="<?= htmlspecialchars($nombre ?? '') ?>" required>
                    <div class="form-text">Nombre único del animal.</div>
                </div>

                <div class="mb-3">
                    <label for="tipo_produccion" class="form-label">Tipo de Producción *</label>
                    <select class="form-select" id="tipo_produccion" name="tipo_produccion" required>
                        <option value="" disabled <?= !isset($tipo_produccion) ? 'selected' : '' ?>>Selecciona el tipo</option>
                        <option value="Leche" <?= (isset($tipo_produccion) && $tipo_produccion == 'Leche') ? 'selected' : '' ?>>Leche</option>
                        <option value="Carne" <?= (isset($tipo_produccion) && $tipo_produccion == 'Carne') ? 'selected' : '' ?>>Carne</option>
                        <option value="Mixto" <?= (isset($tipo_produccion) && $tipo_produccion == 'Mixto') ? 'selected' : '' ?>>Mixto</option>
                        <option value="Reproducción" <?= (isset($tipo_produccion) && $tipo_produccion == 'Reproducción') ? 'selected' : '' ?>>Reproducción</option>
                        <option value="Otro" <?= (isset($tipo_produccion) && $tipo_produccion == 'Otro') ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                           value="<?= htmlspecialchars($fecha_nacimiento ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="peso" class="form-label">Peso (kg)</label>
                    <input type="number" step="0.01" class="form-control" id="peso" name="peso" 
                           value="<?= htmlspecialchars($peso ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                           value="<?= htmlspecialchars($ubicacion ?? '') ?>">
                    <div class="form-text">Potrero, corral, establo, etc.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Registrar Animal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
