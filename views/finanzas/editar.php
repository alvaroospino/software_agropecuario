<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
// Requerir login
requireLogin();
$db = new Database();

// Obtener ID de la transacción
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: lista.php');
    exit;
}

// Obtener categorías existentes para autocompletar
$categorias = $db->select("SELECT DISTINCT categoria FROM transacciones WHERE id != ? ORDER BY categoria", [$id]);

// Obtener datos de la transacción
$transaccion = $db->select("SELECT * FROM transacciones WHERE id = ?", [$id]);

if (empty($transaccion)) {
    header('Location: lista.php');
    exit;
}

$transaccion = $transaccion[0];
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $concepto = filter_input(INPUT_POST, 'concepto', FILTER_SANITIZE_STRING);
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);
    $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
    $notas = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_STRING);
    
    // Validaciones
    $errores = [];
    
    if (empty($fecha)) {
        $errores[] = 'La fecha es obligatoria';
    }
    
    if (empty($concepto)) {
        $errores[] = 'El concepto es obligatorio';
    }
    
    if (!in_array($tipo, ['ingreso', 'gasto'])) {
        $errores[] = 'El tipo debe ser ingreso o gasto';
    }
    
    if (empty($categoria)) {
        $errores[] = 'La categoría es obligatoria';
    }
    
    if ($monto === false || $monto <= 0) {
        $errores[] = 'El monto debe ser un número positivo';
    }
    
    // Si no hay errores, actualizar
    if (empty($errores)) {
        $sql = "UPDATE transacciones SET fecha = ?, concepto = ?, tipo = ?, categoria = ?, monto = ?, 
                notas = ?, updated_at = NOW() WHERE id = ?";
        $params = [$fecha, $concepto, $tipo, $categoria, $monto, $notas, $id];
        
        if ($db->query($sql, $params)) {
            $mensaje = 'Transacción actualizada correctamente';
            $tipo_mensaje = 'success';
            
            // Actualizar datos mostrados
            $transaccion['fecha'] = $fecha;
            $transaccion['concepto'] = $concepto;
            $transaccion['tipo'] = $tipo;
            $transaccion['categoria'] = $categoria;
            $transaccion['monto'] = $monto;
            $transaccion['notas'] = $notas;
        } else {
            $mensaje = 'Error al actualizar la transacción';
            $tipo_mensaje = 'danger';
        }
    } else {
        $mensaje = 'Por favor, corrija los siguientes errores: ' . implode(', ', $errores);
        $tipo_mensaje = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Editar Transacción</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
                            <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo $transaccion['fecha']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="concepto" class="form-label">Concepto</label>
                            <input type="text" class="form-control" id="concepto" name="concepto" value="<?php echo htmlspecialchars($transaccion['concepto']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoIngreso" value="ingreso" <?php echo $transaccion['tipo'] === 'ingreso' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tipoIngreso">
                                    Ingreso
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoGasto" value="gasto" <?php echo $transaccion['tipo'] === 'gasto' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tipoGasto">
                                    Gasto
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="categoria" name="categoria" list="listaCategorias" value="<?php echo htmlspecialchars($transaccion['categoria']); ?>" required>
                            <datalist id="listaCategorias">
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto ($)</label>
                            <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0.01" value="<?php echo $transaccion['monto']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas (opcional)</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3"><?php echo htmlspecialchars($transaccion['notas'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="lista.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>