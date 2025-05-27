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

// Obtener ventas
$query = "
    SELECT ventas.*, productos.nombre AS nombre_producto, productos.precio_compra,
           (ventas.total - (productos.precio_compra * ventas.cantidad)) AS ganancia
    FROM ventas
    JOIN productos ON ventas.id_producto = productos.id
    $where
    ORDER BY ventas.fecha DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// Obtener compras
$query_compras = "
    SELECT compras.*, productos.nombre AS nombre_producto
    FROM compras
    JOIN productos ON compras.id_producto = productos.id
    $where
    ORDER BY compras.fecha DESC
";
$stmt = $pdo->prepare($query_compras);
$stmt->execute($params);
$compras = $stmt->fetchAll();

// Calcular totales de ventas
$queryTotal = "SELECT SUM(total) AS total_ventas, SUM(cantidad) AS total_productos_vendidos FROM ventas $where";
$stmt = $pdo->prepare($queryTotal);
$stmt->execute($params);
$result = $stmt->fetch();
$total_ventas = $result['total_ventas'] ?? 0;
$total_productos_vendidos = $result['total_productos_vendidos'] ?? 0;

// Calcular totales de compras
$queryTotalCompras = "SELECT SUM(total) AS total_compras, SUM(cantidad) AS total_productos_comprados FROM compras $where";
$stmt = $pdo->prepare($queryTotalCompras);
$stmt->execute($params);
$result = $stmt->fetch();
$total_compras = $result['total_compras'] ?? 0;
$total_productos_comprados = $result['total_productos_comprados'] ?? 0;

// Calcular ganancias
$ganancia_neta = $total_ventas - $total_compras;

// Combinar ventas y compras para mostrar en una sola tabla
$transacciones = [];

foreach ($ventas as $venta) {
    $transacciones[] = [
        'tipo' => 'venta',
        'id' => $venta['id'],
        'producto' => $venta['nombre_producto'],
        'cantidad' => $venta['cantidad'],
        'precio_unitario' => $venta['total'] / $venta['cantidad'],
        'total' => $venta['total'],
        'ganancia' => $venta['ganancia'],
        'fecha' => $venta['fecha']
    ];
}

foreach ($compras as $compra) {
    $transacciones[] = [
        'tipo' => 'compra',
        'id' => $compra['id'],
        'producto' => $compra['nombre_producto'],
        'cantidad' => $compra['cantidad'],
        'precio_unitario' => $compra['precio_unitario'],
        'total' => $compra['total'],
        'ganancia' => null,
        'fecha' => $compra['fecha']
    ];
}

// Ordenar por fecha
usort($transacciones, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas y Compras - Sistema de Inventario</title>
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
        
        .venta-row {
            background-color: rgba(50, 205, 50, 0.1) !important;
            border-left: 4px solid #32cd32;
        }
        
        .compra-row {
            background-color: rgba(255, 182, 193, 0.2) !important;
            border-left: 4px solid #ffb6c1;
        }
        
        .ganancia-positiva {
            color: #32cd32;
            font-weight: bold;
        }
        
        .ganancia-negativa {
            color: #ff6b6b;
            font-weight: bold;
        }
        
        .card-ganancia {
            background: linear-gradient(135deg, #32cd32, #228b22);
        }
        
        .card-perdida {
            background: linear-gradient(135deg, #ff6b6b, #dc143c);
        }
        
        .card-compras {
            background: linear-gradient(135deg, #ffb6c1, #ff69b4);
        }
        
        .card-ventas {
            background: linear-gradient(135deg, #32cd32, #00ff00);
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
                        <a class="nav-link" href="perfil.php">
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
        <!-- Resumen financiero -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-ventas text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Total Ventas</h6>
                                <h4 class="mb-0">$<?= number_format($total_ventas, 2) ?></h4>
                                <small><?= $total_productos_vendidos ?> productos</small>
                            </div>
                            <div class="text-white">
                                <i class="fas fa-arrow-up fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-compras text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Total Compras</h6>
                                <h4 class="mb-0">$<?= number_format($total_compras, 2) ?></h4>
                                <small><?= $total_productos_comprados ?> productos</small>
                            </div>
                            <div class="text-white">
                                <i class="fas fa-arrow-down fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?= $ganancia_neta >= 0 ? 'card-ganancia' : 'card-perdida' ?> text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50"><?= $ganancia_neta >= 0 ? 'Ganancia' : 'Pérdida' ?></h6>
                                <h4 class="mb-0">$<?= number_format(abs($ganancia_neta), 2) ?></h4>
                                <small><?= $ganancia_neta >= 0 ? 'Beneficio' : 'Déficit' ?></small>
                            </div>
                            <div class="text-white">
                                <i class="fas fa-<?= $ganancia_neta >= 0 ? 'chart-line' : 'chart-line-down' ?> fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Margen</h6>
                                <h4 class="mb-0"><?= $total_ventas > 0 ? number_format(($ganancia_neta / $total_ventas) * 100, 1) : 0 ?>%</h4>
                                <small>Rentabilidad</small>
                            </div>
                            <div class="text-white">
                                <i class="fas fa-percentage fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtro de fechas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtrar Transacciones</h5>
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

        <!-- Historial de transacciones -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Transacciones</h5>
                <small class="text-muted">
                    <span class="badge" style="background-color: #32cd32;">Verde</span> = Ventas | 
                    <span class="badge" style="background-color: #ffb6c1;">Rosa</span> = Compras
                </small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>P. Unitario</th>
                                <th>Total</th>
                                <th>Ganancia</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transacciones) > 0): ?>
                                <?php foreach ($transacciones as $transaccion): ?>
                                    <tr class="<?= $transaccion['tipo'] ?>-row">
                                        <td>
                                            <?php if ($transaccion['tipo'] == 'venta'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-arrow-up me-1"></i>Venta
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background-color: #ffb6c1; color: #000;">
                                                    <i class="fas fa-arrow-down me-1"></i>Compra
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $transaccion['id'] ?></td>
                                        <td><?= $transaccion['producto'] ?></td>
                                        <td><?= $transaccion['cantidad'] ?></td>
                                        <td>$<?= number_format($transaccion['precio_unitario'], 2) ?></td>
                                        <td>
                                            <?php if ($transaccion['tipo'] == 'venta'): ?>
                                                <span class="text-success">+$<?= number_format($transaccion['total'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-danger">-$<?= number_format($transaccion['total'], 2) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transaccion['tipo'] == 'venta' && $transaccion['ganancia'] !== null): ?>
                                                <span class="<?= $transaccion['ganancia'] >= 0 ? 'ganancia-positiva' : 'ganancia-negativa' ?>">
                                                    $<?= number_format($transaccion['ganancia'], 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($transaccion['fecha'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3">No se encontraron transacciones</td>
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
