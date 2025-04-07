<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

//Capturar fechas del filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$where = "WHERE 1";
$params = [];

if (!empty($fecha_inicio)) {
    $where .= " AND fecha >= ?";
    $params[] = $fecha_inicio . " 00:00:00";
}

if (!empty($fecha_fin)) {
    $where .= " AND fecha <= ?";
    $params[] = $fecha_fin . " 23:59:59";
}

$query = "SELECT * FROM ventas $where ORDER BY fecha DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

//Total de ventas
$queryTotal = "SELECT SUM(total) AS total_ventas FROM ventas $where";
$stmt = $pdo->prepare($queryTotal);
$stmt->execute($params);
$result = $stmt->fetch();
$total_ventas = $result['total_ventas'] ?? 0;

$queryCant = "SELECT SUM(cantidad) AS total_productos FROM ventas $where";
$stmt = $pdo->prepare($queryCant);
$stmt->execute($params);
$result = $stmt->fetch();
$total_productos = $result['total_productos'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Sistema de Inventario</title>
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
                        <a class="nav-link active" href="ventas.php">
                            <i class="fas fa-shopping-cart me-1"></i> Ventas
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
        <!-- Resumen de ventas -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Total de Ventas</h6>
                                <h3 class="mb-0">$<?= number_format($total_ventas, 2) ?></h3>
                            </div>
                            <div class="text-white">
                                <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Productos Vendidos</h6>
                                <h3 class="mb-0"><?= $total_productos ?> unidades</h3>
                            </div>
                            <div class="text-white">
                                <i class="fas fa-box fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtro de fechas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtrar Ventas</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio:</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= $fecha_inicio ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="fecha_fin" class="form-label">Fecha Fin:</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= $fecha_fin ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historial de ventas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Ventas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($ventas) > 0): ?>
                                <?php foreach ($ventas as $venta): ?>
                                    <tr>
                                        <td><?= $venta['id'] ?></td>
                                        <td><?= $venta['nombre_producto'] ?? 'Producto #'.$venta['id_producto'] ?></td>
                                        <td><?= $venta['cantidad'] ?></td>
                                        <td>$<?= number_format($venta['total'], 2) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No se encontraron ventas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="inventario.php" class="btn btn-primary">
                    <i class="fas fa-boxes me-2"></i>Ir a Inventario
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

