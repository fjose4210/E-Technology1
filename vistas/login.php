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
</head>
<body>
    <h1>Iniciar sesión</h1>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <form action="" method="POST">
        <label for="username">Usuario:</label><br>
        <input type="text" name="username" required><br>
        <label for="password">Contraseña:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Iniciar sesión</button>
    </form>

    <p>¿No tienes una cuenta? <a href="crear_usuario.php">Crea una cuenta aquí</a></p>
</body>
</html>
