<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Requerir login
requireLogin();

$db = new Database();
$errors = [];

// Verificar ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    setMessage('ID de animal no válido', 'danger');
    redirect('views/animales/lista.php');
}

// Obtener datos del animal
$animal = $db->selectOne('SELECT * FROM animales WHERE id = ?', [$id]);
if (!$animal) {
    setMessage('Animal no encontrado', 'danger');
    redirect('views/animales/lista.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar datos
    $identificacion = trim($_POST['identificacion'] ?? '');
    $tipo_produccion = trim($_POST['tipo_produccion'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    
    // Validaciones
    if (empty($identificacion)) {
        $errors[] = 'La identificación del animal es obligatoria';
    }
    
    if (empty($tipo_produccion)) {
        $errors[] = 'El tipo de producción es obligatorio';
    }
    
    if (!empty($peso) && !is_numeric($peso)) {
        $errors[] = 'El peso debe ser un número válido';
    }
    
    // Verificar si la identificación ya existe (excluyendo el animal actual)
    if (empty($errors) && $identificacion !== $animal['identificacion']) {
        $existingAnimal = $db->selectOne('SELECT id FROM animales WHERE identificacion = ? AND id != ?', 
                                         [$identificacion, $id]);
        
        if ($existingAnimal) {
            $errors[] = 'Ya existe otro animal con esta identificación';
        }
    }
    
    // Actualizar animal
    if (empty($errors)) {
        // Obtener valores antiguos para historial si han cambiado
        if ($animal['peso'] != $peso) {
            // Registrar cambio en historial
            $db->insert('historial_animal', [
                'animal_id' => $id,
                'tipo_cambio' => 'Peso',
                'valor_anterior' => $animal['peso'],
                'valor_nuevo' => $peso
            ]);
        }
        
        if ($animal['ubicacion'] != $ubicacion) {
            // Registrar cambio en historial
            $db->insert('historial_animal', [
                'animal_id' => $id,
                'tipo_cambio' => 'Ubicación',
                'valor_anterior' => $animal['ubicacion'],
                'valor_nuevo' => $ubicacion
            ]);
        }
        
        $animalData = [
            'identificacion' => $identificacion,
            'tipo_produccion' => $tipo_produccion,
            'fecha_nacimiento' => !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
            'peso' => !empty($peso) ? $peso : null,
            'ubicacion' => $ubicacion
        ];
        
        $result = $db->update('animales', $animalData, 'id = ?', ['id' => $id]);
        
        if ($result) {
            setMessage('Animal actualizado correctamente');
            redirect('views/animales/lista.php');
        } else {
            $errors[] = 'Error al actualizar el animal. Inténtalo de nuevo.';
        }
    }
} else {
    // Pre-llenar formulario con datos actuales
    $identificacion = $animal['identificacion'];
    $tipo_produccion = $animal['tipo_produccion'];
    $fecha_nacimiento = $animal['fecha_nacimiento'];
    $peso = $animal['peso'];
    $ubicacion = $animal['ubicacion'];
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Editar Animal</h1>
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
                    <label for="identificacion" class="form-label">Identificación *</label>
                    <input type="text" class="form-control" id="identificacion" name="identificacion" 
                           value="<?= $identificacion ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="tipo_produccion" class="form-label">Tipo de Producción *</label>
                    <select class="form-select" id="tipo_produccion" name="tipo_produccion" required>
                        <option value="" disabled>Selecciona el tipo</option>
                        <option value="Leche" <?= $tipo_produccion == 'Leche' ? 'selected' : '' ?>>Leche</option>
                        <option value="Carne" <?= $tipo_produccion == 'Carne' ? 'selected' : '' ?>>Carne</option>
                        <option value="Mixto" <?= $tipo_produccion == 'Mixto' ? 'selected' : '' ?>>Mixto</option>
                        <option value="Reproducción" <?= $tipo_produccion == 'Reproducción' ? 'selected' : '' ?>>Reproducción</option>
                        <option value="Otro" <?= $tipo_produccion == 'Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                           value="<?= $fecha_nacimiento ?>">
                </div>
                
                <div class="mb-3">
                    <label for="peso" class="form-label">Peso (kg)</label>
                    <input type="number" step="0.01" class="form-control" id="peso" name="peso" 
                           value="<?= $peso ?>">
                </div>
                
                <div class="mb-3">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                           value="<?= $ubicacion ?>">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Actualizar Animal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>