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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
</head>
<body>
    <div class = "container mt-5">
        <h1>Crear Cuenta</h1>
        <?php if (isset($error)):?>
        <div class="alert alert-danger"><?php echo "<p>$error</p>"; ?></div>
        <?php endif;?>
        <?php if (isset($success)):?>
        <div class="alert alert-success"><?php echo "<p>$success</p>"; ?></div>
        <?php endif;?>
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label" for="username">Nombre de usuario:</label><br>
                <input class="form-control" type="text" name="username" required><br>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña:</label><br>
                <input class="form-control" type="password" name="password" required><br><br>
            </div>
            <button class="btn btn-primary" type="submit">Crear cuenta</button>
        </form>
        <p>Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
</body>
</html>
