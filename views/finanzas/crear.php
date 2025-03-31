<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
// Requerir login
requireLogin();

// Obtener el ID del usuario actual de la sesión
$usuario_id = $_SESSION['user_id']; // Ajusta esto si usas un nombre diferente en tu sesión

$db = new Database();

// Obtener categorías existentes para autocompletar
$categorias = $db->select("SELECT DISTINCT categoria FROM transacciones ORDER BY categoria");

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
    
    // Si no hay errores, guardar
    if (empty($errores)) {
        $sql = "INSERT INTO transacciones (fecha, concepto, tipo, categoria, monto, notas, usuario_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $params = [$fecha, $concepto, $tipo, $categoria, $monto, $notas, $usuario_id];
        
        if ($db->query($sql, $params)) {
            $mensaje = 'Transacción registrada correctamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al registrar la transacción';
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
                    <h4>Nueva Transacción</h4>
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
                            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="concepto" class="form-label">Concepto</label>
                            <input type="text" class="form-control" id="concepto" name="concepto" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoIngreso" value="ingreso" checked>
                                <label class="form-check-label" for="tipoIngreso">
                                    Ingreso
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoGasto" value="gasto">
                                <label class="form-check-label" for="tipoGasto">
                                    Gasto
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="categoria" name="categoria" list="listaCategorias" required>
                            <datalist id="listaCategorias">
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto ($)</label>
                            <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas (opcional)</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="lista.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>