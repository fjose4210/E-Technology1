<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Función para obtener datos según el período
function obtenerDatosPeriodo($pdo, $fecha_inicio, $fecha_fin) {
    $datos = [
        'ventas' => [],
        'compras' => [],
        'total_ventas' => 0,
        'total_compras' => 0,
        'ganancia_neta' => 0,
        'productos_vendidos' => 0,
        'productos_comprados' => 0
    ];
    
    // Obtener ventas
    $stmt = $pdo->prepare("
        SELECT v.*, p.nombre as nombre_producto, p.precio_compra,
               (v.total - (p.precio_compra * v.cantidad)) as ganancia
        FROM ventas v
        JOIN productos p ON v.id_producto = p.id
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        ORDER BY v.fecha DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos['ventas'] = $stmt->fetchAll();
    
    // Obtener compras
    $stmt = $pdo->prepare("
        SELECT c.*, p.nombre as nombre_producto
        FROM compras c
        JOIN productos p ON c.id_producto = p.id
        WHERE DATE(c.fecha) BETWEEN ? AND ?
        ORDER BY c.fecha DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos['compras'] = $stmt->fetchAll();
    
    // Calcular totales
    foreach ($datos['ventas'] as $venta) {
        $datos['total_ventas'] += $venta['total'];
        $datos['productos_vendidos'] += $venta['cantidad'];
    }
    
    foreach ($datos['compras'] as $compra) {
        $datos['total_compras'] += $compra['total'];
        $datos['productos_comprados'] += $compra['cantidad'];
    }
    
    $datos['ganancia_neta'] = $datos['total_ventas'] - $datos['total_compras'];
    
    return $datos;
}

// Procesar parámetros
$fecha_inicio = '';
$fecha_fin = '';
$titulo_periodo = '';

if (isset($_GET['quick'])) {
    // Reportes rápidos
    switch ($_GET['quick']) {
        case 'semana':
            $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
            $fecha_fin = date('Y-m-d', strtotime('sunday this week'));
            $titulo_periodo = 'Semana del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin));
            break;
        case 'mes':
            $fecha_inicio = date('Y-m-01');
            $fecha_fin = date('Y-m-t');
            $titulo_periodo = 'Mes de ' . date('F Y');
            break;
        case 'año':
            $fecha_inicio = date('Y-01-01');
            $fecha_fin = date('Y-12-31');
            $titulo_periodo = 'Año ' . date('Y');
            break;
    }
} else {
    // Reportes personalizados
    $periodo_tipo = $_POST['periodo_tipo'] ?? 'semanal';
    
    switch ($periodo_tipo) {
        case 'semanal':
            $fecha_inicio = $_POST['fecha_inicio_semana'];
            $fecha_fin = $_POST['fecha_fin_semana'];
            $titulo_periodo = 'Semana del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin));
            break;
        case 'mensual':
            $mes = $_POST['mes'];
            $año = $_POST['año_mensual'];
            $fecha_inicio = "$año-$mes-01";
            $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));
            $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $titulo_periodo = $meses[$mes] . ' de ' . $año;
            break;
        case 'anual':
            $año = $_POST['año_anual'];
            $fecha_inicio = "$año-01-01";
            $fecha_fin = "$año-12-31";
            $titulo_periodo = 'Año ' . $año;
            break;
    }
}

$datos = obtenerDatosPeriodo($pdo, $fecha_inicio, $fecha_fin);

// Obtener información del inventario actual
$stmt = $pdo->query("
    SELECT p.*, 
           (p.cantidad * p.precio_compra) as valor_inventario_compra,
           (p.cantidad * p.precio) as valor_inventario_venta
    FROM productos p 
    ORDER BY p.nombre
");
$inventario = $stmt->fetchAll();

$valor_total_inventario_compra = 0;
$valor_total_inventario_venta = 0;
foreach ($inventario as $producto) {
    $valor_total_inventario_compra += $producto['valor_inventario_compra'];
    $valor_total_inventario_venta += $producto['valor_inventario_venta'];
}

$incluir_ventas = isset($_POST['incluir_ventas']) || isset($_GET['quick']);
$incluir_compras = isset($_POST['incluir_compras']) || isset($_GET['quick']);
$incluir_inventario = isset($_POST['incluir_inventario']) || isset($_GET['quick']);
$accion = $_POST['accion'] ?? 'ver';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte - <?= $titulo_periodo ?> - e-technology</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: white;
            color: #333;
        }
        
        .report-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .company-logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .report-period {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .summary-card {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .positive {
            color: #28a745;
            font-weight: 600;
        }
        
        .negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        .neutral {
            color: #6c757d;
        }
        
        .section-title {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            margin: 30px 0 20px 0;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .table-custom {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .table-custom thead {
            background: #f8f9fa;
        }
        
        .venta-row {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 3px solid #28a745;
        }
        
        .compra-row {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 3px solid #dc3545;
        }
        
        .footer-info {
            background: #f8f9fa;
            padding: 20px;
            margin-top: 40px;
            border-radius: 8px;
            text-align: center;
            color: #6c757d;
        }
        
        @media print {
            body {
                background: white !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .report-header {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .section-title {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <!-- Botones de acción -->
    <div class="container-fluid no-print">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3">
                    <a href="reportes.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Reportes
                    </a>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary me-2">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <button onclick="window.close()" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header del Reporte -->
    <div class="report-header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <div class="company-logo">
                        <i class="fas fa-microchip me-3"></i>e-technology
                    </div>
                    <div class="report-title">Reporte Financiero</div>
                    <div class="report-period"><?= $titulo_periodo ?></div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="mt-3">
                        <strong>Fecha de Generación:</strong><br>
                        <?= date('d/m/Y H:i:s') ?>
                    </div>
                    <div class="mt-2">
                        <strong>Generado por:</strong><br>
                        <?= $_SESSION['username'] ?? 'Sistema' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Resumen Ejecutivo -->
        <div class="summary-card">
            <h4 class="mb-3">
                <i class="fas fa-chart-pie me-2"></i>Resumen Ejecutivo
            </h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="summary-item">
                        <span><i class="fas fa-arrow-up text-success me-2"></i>Total Ventas:</span>
                        <span class="positive">$<?= number_format($datos['total_ventas'], 2) ?></span>
                    </div>
                    <div class="summary-item">
                        <span><i class="fas fa-arrow-down text-danger me-2"></i>Total Compras:</span>
                        <span class="negative">$<?= number_format($datos['total_compras'], 2) ?></span>
                    </div>
                    <div class="summary-item">
                        <span><i class="fas fa-chart-line me-2"></i>Ganancia Neta:</span>
                        <span class="<?= $datos['ganancia_neta'] >= 0 ? 'positive' : 'negative' ?>">
                            $<?= number_format(abs($datos['ganancia_neta']), 2) ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="summary-item">
                        <span><i class="fas fa-box text-info me-2"></i>Productos Vendidos:</span>
                        <span class="neutral"><?= $datos['productos_vendidos'] ?> unidades</span>
                    </div>
                    <div class="summary-item">
                        <span><i class="fas fa-shopping-cart text-warning me-2"></i>Productos Comprados:</span>
                        <span class="neutral"><?= $datos['productos_comprados'] ?> unidades</span>
                    </div>
                    <div class="summary-item">
                        <span><i class="fas fa-percentage me-2"></i>Margen de Ganancia:</span>
                        <span class="<?= $datos['ganancia_neta'] >= 0 ? 'positive' : 'negative' ?>">
                            <?= $datos['total_ventas'] > 0 ? number_format(($datos['ganancia_neta'] / $datos['total_ventas']) * 100, 1) : 0 ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($incluir_ventas && count($datos['ventas']) > 0): ?>
        <!-- Detalle de Ventas -->
        <div class="section-title">
            <i class="fas fa-arrow-up me-2"></i>Detalle de Ventas
        </div>
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                        <th>Ganancia</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['ventas'] as $venta): ?>
                    <tr class="venta-row">
                        <td><?= $venta['id'] ?></td>
                        <td><?= $venta['nombre_producto'] ?></td>
                        <td><?= $venta['cantidad'] ?></td>
                        <td>$<?= number_format($venta['total'] / $venta['cantidad'], 2) ?></td>
                        <td class="positive">$<?= number_format($venta['total'], 2) ?></td>
                        <td class="<?= $venta['ganancia'] >= 0 ? 'positive' : 'negative' ?>">
                            $<?= number_format($venta['ganancia'], 2) ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-success">
                        <th colspan="4">TOTAL VENTAS</th>
                        <th class="positive">$<?= number_format($datos['total_ventas'], 2) ?></th>
                        <th class="positive">$<?= number_format(array_sum(array_column($datos['ventas'], 'ganancia')), 2) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($incluir_compras && count($datos['compras']) > 0): ?>
        <!-- Detalle de Compras -->
        <div class="section-title page-break">
            <i class="fas fa-arrow-down me-2"></i>Detalle de Compras
        </div>
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['compras'] as $compra): ?>
                    <tr class="compra-row">
                        <td><?= $compra['id'] ?></td>
                        <td><?= $compra['nombre_producto'] ?></td>
                        <td><?= $compra['cantidad'] ?></td>
                        <td>$<?= number_format($compra['precio_unitario'], 2) ?></td>
                        <td class="negative">$<?= number_format($compra['total'], 2) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($compra['fecha'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-danger">
                        <th colspan="4">TOTAL COMPRAS</th>
                        <th class="negative">$<?= number_format($datos['total_compras'], 2) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($incluir_inventario): ?>
        <!-- Estado del Inventario -->
        <div class="section-title page-break">
            <i class="fas fa-boxes me-2"></i>Estado Actual del Inventario
        </div>
        <div class="summary-card mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="summary-item">
                        <span>Total Productos:</span>
                        <span class="neutral"><?= count($inventario) ?> tipos</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <span>Valor Inventario (Compra):</span>
                        <span class="negative">$<?= number_format($valor_total_inventario_compra, 2) ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <span>Valor Inventario (Venta):</span>
                        <span class="positive">$<?= number_format($valor_total_inventario_venta, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>P. Compra</th>
                        <th>P. Venta</th>
                        <th>Valor Stock (Compra)</th>
                        <th>Valor Stock (Venta)</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventario as $producto): ?>
                    <tr>
                        <td><?= $producto['nombre'] ?></td>
                        <td><?= $producto['categoria'] ?></td>
                        <td>
                            <?= $producto['cantidad'] ?>
                            <?php if ($producto['cantidad'] < 5): ?>
                                <i class="fas fa-exclamation-triangle text-warning ms-1" title="Stock bajo"></i>
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format($producto['precio_compra'], 2) ?></td>
                        <td>$<?= number_format($producto['precio'], 2) ?></td>
                        <td class="negative">$<?= number_format($producto['valor_inventario_compra'], 2) ?></td>
                        <td class="positive">$<?= number_format($producto['valor_inventario_venta'], 2) ?></td>
                        <td>
                            <?php if ($producto['cantidad'] == 0): ?>
                                <span class="badge bg-danger">Sin Stock</span>
                            <?php elseif ($producto['cantidad'] < 5): ?>
                                <span class="badge bg-warning">Stock Bajo</span>
                            <?php else: ?>
                                <span class="badge bg-success">Normal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer-info">
            <div class="row">
                <div class="col-md-6">
                    <strong>e-technology</strong><br>
                    Sistema de Gestión de Inventario<br>
                    Reporte generado automáticamente
                </div>
                <div class="col-md-6 text-end">
                    <strong>Contacto:</strong><br>
                    info@e-technology.com<br>
                    www.e-technology.com
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-imprimir si se solicita
        <?php if ($accion === 'imprimir'): ?>
        window.onload = function() {
            window.print();
        };
        <?php endif; ?>
    </script>
</body>
</html>
