<?php
session_start();
include '../config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch();

// Inicializar variables para mensajes
$success = $error = '';

// Procesar actualización de información personal
if (isset($_POST['actualizar_info'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    
    // Validar email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } else {
        // Verificar si el email ya existe para otro usuario
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error = "Este correo electrónico ya está en uso por otro usuario.";
            }
        }
        
        if (empty($error)) {
            // Actualizar información del usuario
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $email, $telefono, $user_id]);
            
            // Actualizar la información en la sesión actual
            $success = "Información personal actualizada correctamente.";
            
            // Refrescar los datos del usuario
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch();
        }
    }
}

// Procesar cambio de contraseña
if (isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $password_nuevo = $_POST['password_nuevo'];
    $password_confirmar = $_POST['password_confirmar'];
    
    // Verificar que la contraseña actual sea correcta
    if (!password_verify($password_actual, $usuario['password'])) {
        $error = "La contraseña actual no es correcta.";
    } 
    // Verificar que las nuevas contraseñas coincidan
    elseif ($password_nuevo !== $password_confirmar) {
        $error = "Las nuevas contraseñas no coinciden.";
    } 
    // Verificar longitud mínima
    elseif (strlen($password_nuevo) < 6) {
        $error = "La nueva contraseña debe tener al menos 6 caracteres.";
    } 
    else {
        // Actualizar la contraseña
        $hashed_password = password_hash($password_nuevo, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        $success = "Contraseña actualizada correctamente.";
    }
}

// Procesar subida de foto de perfil
if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    $file_type = $_FILES['foto_perfil']['type'];
    $file_size = $_FILES['foto_perfil']['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        $error = "Solo se permiten archivos de imagen (JPEG, PNG, GIF).";
    } elseif ($file_size > $max_size) {
        $error = "El tamaño de la imagen no debe exceder 2MB.";
    } else {
        // Crear directorio si no existe
        $upload_dir = 'uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generar nombre único para el archivo
        $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $file_name = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        // Mover el archivo subido
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
            // Actualizar la ruta de la foto en la base de datos
            $stmt = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
            $stmt->execute([$file_name, $user_id]);
            
            $success = "Foto de perfil actualizada correctamente.";
            
            // Refrescar los datos del usuario
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch();
        } else {
            $error = "Hubo un error al subir la imagen. Inténtalo de nuevo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles/main.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f2027, #2c5364, #00c9a7);
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
        }
        
        .profile-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            padding: 30px;
            margin-bottom: 20px;
            color: white;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .profile-info {
            padding-left: 20px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            color: white;
        }
        
        .card-header {
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .nav-pills .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .nav-pills .nav-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-control:disabled {
            background: rgba(0, 0, 0, 0.1);
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-text {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn-primary {
            background-color: #ffffff;
            color: #2c5364;
            font-weight: 600;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #f1f1f1;
            color: #0f2027;
        }
        
        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .table {
            color: white;
        }
        
        .table th {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .table td {
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .alert {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            color: white;
        }
        
        .alert-success {
            background: rgba(28, 200, 138, 0.2);
        }
        
        .alert-danger {
            background: rgba(231, 74, 59, 0.2);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            color: white;
        }
        
        .modal-header, .modal-footer {
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="inventario.php">
                <i class="fas fa-box-open me-2"></i>Sistema de Inventario
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="inventario.php">
                            <i class="fas fa-boxes me-1"></i> Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ventas.php">
                            <i class="fas fa-shopping-cart me-1"></i> Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes.php">
                            <i class="fas fa-chart-bar me-1"></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="perfil.php">
                            <i class="fas fa-user me-1"></i> Perfil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php?logout=1">
                            <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Alertas -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Encabezado del perfil -->
        <div class="profile-header d-flex flex-column flex-md-row align-items-center">
            <div class="text-center">
                <?php 
                $foto_path = 'uploads/profiles/' . ($usuario['foto'] ?? 'default.jpg');
                if (!file_exists($foto_path)) {
                    $foto_path = 'uploads/profiles/default.jpg';
                }
                ?>
                <img src="<?php echo $foto_path; ?>" alt="Foto de perfil" class="profile-picture mb-3">
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#cambiarFotoModal">
                        <i class="fas fa-camera me-1"></i> Cambiar foto
                    </button>
                </div>
            </div>
            <div class="profile-info mt-4 mt-md-0">
                <h3><?php echo !empty($usuario['nombre']) ? $usuario['nombre'] . ' ' . $usuario['apellido'] : $usuario['username']; ?></h3>
                <p class="mb-1">
                    <i class="fas fa-user-tag me-1"></i> <?php echo ucfirst($usuario['rol']); ?>
                </p>
                <p class="mb-1">
                    <i class="fas fa-envelope me-1"></i> <?php echo !empty($usuario['email']) ? $usuario['email'] : 'No especificado'; ?>
                </p>
                <p class="mb-1">
                    <i class="fas fa-phone me-1"></i> <?php echo !empty($usuario['telefono']) ? $usuario['telefono'] : 'No especificado'; ?>
                </p>
                <p class="mb-0">
                    <i class="fas fa-calendar-alt me-1"></i> Miembro desde: <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'] ?? 'now')); ?>
                </p>
            </div>
        </div>

        <!-- Contenido del perfil -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Navegación</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active text-start py-3 px-4" id="v-pills-info-tab" data-bs-toggle="pill" data-bs-target="#v-pills-info" type="button" role="tab">
                                <i class="fas fa-user-edit me-2"></i> Información Personal
                            </button>
                            <button class="nav-link text-start py-3 px-4" id="v-pills-security-tab" data-bs-toggle="pill" data-bs-target="#v-pills-security" type="button" role="tab">
                                <i class="fas fa-lock me-2"></i> Seguridad
                            </button>
                            <button class="nav-link text-start py-3 px-4" id="v-pills-activity-tab" data-bs-toggle="pill" data-bs-target="#v-pills-activity" type="button" role="tab">
                                <i class="fas fa-chart-line me-2"></i> Actividad Reciente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-content" id="v-pills-tabContent">
                            <!-- Información Personal -->
                            <div class="tab-pane fade show active" id="v-pills-info" role="tabpanel">
                                <h4 class="mb-4">Información Personal</h4>
                                <form method="POST" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $usuario['nombre'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="apellido" class="form-label">Apellido</label>
                                            <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo $usuario['apellido'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nombre de usuario</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo $usuario['username']; ?>" disabled>
                                        <div class="form-text">El nombre de usuario no se puede cambiar.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Correo electrónico</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $usuario['email'] ?? ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo $usuario['telefono'] ?? ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="rol" class="form-label">Rol</label>
                                        <input type="text" class="form-control" id="rol" value="<?php echo ucfirst($usuario['rol']); ?>" disabled>
                                    </div>
                                    <button type="submit" name="actualizar_info" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Seguridad -->
                            <div class="tab-pane fade" id="v-pills-security" role="tabpanel">
                                <h4 class="mb-4">Cambiar Contraseña</h4>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="password_actual" class="form-label">Contraseña Actual</label>
                                        <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" required>
                                        <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" required>
                                    </div>
                                    <button type="submit" name="cambiar_password" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Actividad Reciente -->
                            <div class="tab-pane fade" id="v-pills-activity" role="tabpanel">
                                <h4 class="mb-4">Actividad Reciente</h4>
                                
                                <?php
                                // Obtener las últimas ventas realizadas por este usuario
                                $stmt = $pdo->prepare("
                                    SELECT v.*, p.nombre as nombre_producto 
                                    FROM ventas v 
                                    LEFT JOIN productos p ON v.id_producto = p.id 
                                    ORDER BY v.fecha DESC 
                                    LIMIT 10
                                ");
                                $stmt->execute();
                                $actividades = $stmt->fetchAll();
                                ?>
                                
                                <?php if (count($actividades) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Total</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($actividades as $actividad): ?>
                                                    <tr>
                                                        <td><?= $actividad['nombre_producto'] ?? 'Producto #'.$actividad['id_producto'] ?></td>
                                                        <td><?= $actividad['cantidad'] ?></td>
                                                        <td>$<?= number_format($actividad['total'], 2) ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($actividad['fecha'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>No hay actividad reciente para mostrar.
                                    </div>
                                <?php endif; ?>
                                
                                <div class="text-end mt-3">
                                    <a href="ventas.php" class="btn btn-primary">
                                        <i class="fas fa-history me-2"></i>Ver Todo el Historial
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar foto de perfil -->
    <div class="modal fade" id="cambiarFotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Foto de Perfil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="foto_perfil" class="form-label">Seleccionar nueva imagen</label>
                            <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" accept="image/*" required>
                            <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.</div>
                        </div>
                        <div class="text-center mt-3" id="preview-container" style="display: none;">
                            <h6>Vista previa:</h6>
                            <img id="preview-image" src="#" alt="Vista previa" style="max-width: 100%; max-height: 200px; border-radius: 5px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para vista previa de imagen -->
    <script>
        document.getElementById('foto_perfil').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('preview-image').src = event.target.result;
                    document.getElementById('preview-container').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
