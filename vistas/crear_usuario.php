<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    //Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        $error = "El usuario ya existe. Elige otro nombre.";
    } else {
        //Encriptar la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, 'admin']);  

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f2027, #2c5364, #00c9a7);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 30px;
            width: 100%;
            max-width: 400px;
            color: white;
        }

        .form-control {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: white;
        }

        .btn-primary {
            background-color: #ffffff;
            color: #6e8efb;
            font-weight: 600;
            border: none;
        }

        .btn-primary:hover {
            background-color: #f1f1f1;
            color: #6e8efb;
        }

        a {
            color: #fff;
        }

        a:hover {
            text-decoration: underline;
        }

        .user-icon {
            background: linear-gradient(135deg, #8881f1, #a777e3);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-header text-center py-4 bg-transparent border-0">
            <div class="d-flex flex-column align-items-center">
                <h4 class="mb-0 text-white">
                    <i class="fas fa-box-open me-2"></i>Sistema de Inventario
                </h4>
            </div>
        </div>

        <div class="card-body p-0 mt-4">
            <h5 class="text-center mb-4">Crear Cuenta</h5>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Nombre de usuario" required>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                    </div>
                </div>
                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-primary py-2">
                        <i class="fas fa-user-plus me-2"></i>Crear cuenta
                    </button>
                </div>
            </form>

            <div class="text-center mb-3">
                <p class="mb-0">¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
            <div class="d-grid">
                <a href="login.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
