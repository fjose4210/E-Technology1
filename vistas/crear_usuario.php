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
    <title>Crear Cuenta</title>
</head>
<body>
    <h1>Crear Cuenta</h1>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <?php if (isset($success)) echo "<p>$success</p>"; ?>

    <form action="" method="POST">
        <label for="username">Nombre de usuario:</label><br>
        <input type="text" name="username" required><br>
        <label for="password">Contraseña:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Crear cuenta</button>
    </form>
    <p>Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
</body>
</html>
