<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Redirigir si ya está logueado
requireGuest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validaciones
    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio';
    }
    
    if (empty($email)) {
        $errors[] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email no válido';
    }
    
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    } elseif (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    // Verificar si el email ya existe
    if (empty($errors)) {
        $db = new Database();
        $existingUser = $db->selectOne('SELECT id FROM usuarios WHERE email = ?', [$email]);
        
        if ($existingUser) {
            $errors[] = 'Este email ya está registrado';
        }
    }
    
    // Registrar usuario
    if (empty($errors)) {
        $db = new Database();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $db->insert('usuarios', [
            'nombre' => $nombre,
            'email' => $email,
            'password' => $hashedPassword
        ]);
        
        if ($userId) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_nombre'] = $nombre;
            setMessage('Registro exitoso. ¡Bienvenido!');
            redirect('dashboard.php');
        } else {
            $errors[] = 'Error al registrar. Inténtalo de nuevo.';
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-container">
            <h2 class="text-center mb-4">Registrarse</h2>
            
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
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= $nombre ?? '' ?>">
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= $email ?? '' ?>">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Registrarse</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p>¿Ya tienes cuenta? <a href="<?= APP_URL ?>/views/login.php">Iniciar Sesión</a></p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>