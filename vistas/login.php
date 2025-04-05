<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verificar si el usuario existe en la base de datos
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Si el usuario existe, verificar la contraseña
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: inventario.php");  // Redirigir al inventario
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
</head>
<body>
    <div class = "container mt-5">
        <h1>Iniciar sesión</h1>
        <?php if (isset($error)):?>
        <div class="alert alert-danger"><?php echo "<p>$error</p>"; ?></div>
        <?php endif;?>
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label" for="username">Usuario:</label><br>
                <input class="form-control" type="text" name="username" required><br>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña:</label><br>
                <input class="form-control" type="password" name="password" required><br><br>
            </div>
            <button type="submit" class="btn btn-primary">Iniciar sesión</button>
        </form>
        <p>¿No tienes una cuenta? <a href="crear_usuario.php">Crea una cuenta aquí</a></p>
    </div>
</body>
</html>
