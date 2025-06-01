<?php
session_start();
include '../config.php';

// Manejar cierre de sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: inventario.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventario</title>
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
            <h5 class="text-center mb-4">Iniciar sesión</h5>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Usuario" required>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar sesión
                    </button>
                </div>
            </form>

            <div class="text-center mt-4">
                <p class="mb-0">¿No tienes una cuenta? <a href="crear_usuario.php">Crear una cuenta</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
