<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        $error = "El usuario ya existe. Elige otro nombre.";
    } else {
        // Encriptar la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario en la base de datos
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, 'admin']);  // Asigna el rol de 'admin' o 'vendedor'

        $success = "Usuario creado exitosamente. Ahora puedes iniciar sesión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Sistema de Inventario</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles/main.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-card">
                    <div class="card-header text-center py-3">
                        <h4 class="mb-0"><i class="fas fa-box-open me-2"></i>Sistema de Inventario</h4>
                    </div>
                    <div class="card-body p-4">
                        <h5 class="text-center mb-4">Crear Cuenta</h5>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" id="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="password" required>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-2">
                                    <i class="fas fa-user-plus me-2"></i>Crear cuenta
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none">Inicia sesión aquí</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

