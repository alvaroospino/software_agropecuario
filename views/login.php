<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Redirigir si ya está logueado
requireGuest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones
    if (empty($email)) {
        $errors[] = 'El email es obligatorio';
    }
    
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    }
    
    // Verificar credenciales
    if (empty($errors)) {
        $db = new Database();
        $user = $db->selectOne('SELECT id, nombre, email, password FROM usuarios WHERE email = ?', [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Regenerar ID de sesión para prevenir ataques de fijación de sesión
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            setMessage('Inicio de sesión exitoso. ¡Bienvenido!');
            redirect('dashboard.php');
        }else {
            $errors[] = 'Email o contraseña incorrectos';
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-container">
            <h2 class="text-center mb-4">Iniciar Sesión</h2>
            
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
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= $email ?? '' ?>" autocomplete="email">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="current-password">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p>¿No tienes cuenta? <a href="<?= APP_URL ?>/views/register.php">Registrarse</a></p>
                <p><a href="<?= APP_URL ?>/views/recover.php">¿Olvidaste tu contraseña?</a></p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>