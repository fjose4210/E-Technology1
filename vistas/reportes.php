<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Obtener datos para los selectores
$current_year = date('Y');
$years = [];
for ($i = $current_year - 5; $i <= $current_year + 1; $i++) {
    $years[] = $i;
}

$months = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Obtener estadísticas rápidas
$stmt = $pdo->query("SELECT COUNT(*) as total_productos FROM productos");
$total_productos = $stmt->fetch()['total_productos'];

$stmt = $pdo->query("SELECT SUM(total) as total_ventas FROM ventas WHERE YEAR(fecha) = YEAR(CURDATE())");
$ventas_año = $stmt->fetch()['total_ventas'] ?? 0;

$stmt = $pdo->query("SELECT SUM(total) as total_compras FROM compras WHERE YEAR(fecha) = YEAR(CURDATE())");
$compras_año = $stmt->fetch()['total_compras'] ?? 0;

$ganancia_año = $ventas_año - $compras_año;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema de Inventario</title>
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
        
        .report-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            color: white;
            transition: transform 0.3s ease;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-select option {
            background: #2c5364;
            color: white;
        }
        
        .btn-report {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            padding: 20px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .period-selector {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
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
                        <a class="nav-link active" href="reportes.php">
                            <i class="fas fa-chart-bar me-1"></i> Reportes
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
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="text-white mb-3">
                <i class="fas fa-chart-line me-3"></i>Centro de Reportes
            </h1>
            <p class="text-white-50">Genera reportes detallados de tu negocio</p>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-boxes fa-2x mb-2"></i>
                    <h4><?= $total_productos ?></h4>
                    <small>Productos en Inventario</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                    <h4>$<?= number_format($ventas_año, 2) ?></h4>
                    <small>Ventas del Año</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-arrow-down fa-2x mb-2"></i>
                    <h4>$<?= number_format($compras_año, 2) ?></h4>
                    <small>Compras del Año</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h4 class="<?= $ganancia_año >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format(abs($ganancia_año), 2) ?>
                    </h4>
                    <small><?= $ganancia_año >= 0 ? 'Ganancia' : 'Pérdida' ?> del Año</small>
                </div>
            </div>
        </div>

        <!-- Generador de Reportes -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="report-card">
                    <div class="card-header bg-transparent border-0 text-center py-4">
                        <h3 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Generador de Reportes
                        </h3>
                        <p class="mb-0 text-white-50">Selecciona el período y tipo de reporte</p>
                    </div>
                    <div class="card-body p-4">
                        <form id="reportForm" action="generar_reporte.php" method="POST" target="_blank">
                            <!-- Tipo de Período -->
                            <div class="period-selector">
                                <h5 class="text-white mb-3">
                                    <i class="fas fa-calendar-alt me-2"></i>Período del Reporte
                                </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="periodo_tipo" id="semanal" value="semanal" checked>
                                            <label class="form-check-label text-white" for="semanal">
                                                <i class="fas fa-calendar-week me-1"></i>Semanal
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="periodo_tipo" id="mensual" value="mensual">
                                            <label class="form-check-label text-white" for="mensual">
                                                <i class="fas fa-calendar-alt me-1"></i>Mensual
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="periodo_tipo" id="anual" value="anual">
                                            <label class="form-check-label text-white" for="anual">
                                                <i class="fas fa-calendar me-1"></i>Anual
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selectores de Fecha -->
                            <div class="row mb-4">
                                <!-- Selector Semanal -->
                                <div id="selector-semanal" class="col-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="fecha_inicio_semana" class="form-label text-white">
                                                <i class="fas fa-calendar-day me-1"></i>Fecha Inicio
                                            </label>
                                            <input type="date" class="form-control" id="fecha_inicio_semana" name="fecha_inicio_semana" value="<?= date('Y-m-d', strtotime('monday this week')) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fecha_fin_semana" class="form-label text-white">
                                                <i class="fas fa-calendar-day me-1"></i>Fecha Fin
                                            </label>
                                            <input type="date" class="form-control" id="fecha_fin_semana" name="fecha_fin_semana" value="<?= date('Y-m-d', strtotime('sunday this week')) ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Selector Mensual -->
                                <div id="selector-mensual" class="col-12" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="mes" class="form-label text-white">
                                                <i class="fas fa-calendar-alt me-1"></i>Mes
                                            </label>
                                            <select class="form-select" id="mes" name="mes">
                                                <?php foreach ($months as $num => $nombre): ?>
                                                    <option value="<?= $num ?>" <?= $num == date('n') ? 'selected' : '' ?>><?= $nombre ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="año_mensual" class="form-label text-white">
                                                <i class="fas fa-calendar me-1"></i>Año
                                            </label>
                                            <select class="form-select" id="año_mensual" name="año_mensual">
                                                <?php foreach ($years as $year): ?>
                                                    <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>><?= $year ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selector Anual -->
                                <div id="selector-anual" class="col-12" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6 mx-auto">
                                            <label for="año_anual" class="form-label text-white">
                                                <i class="fas fa-calendar me-1"></i>Año
                                            </label>
                                            <select class="form-select" id="año_anual" name="año_anual">
                                                <?php foreach ($years as $year): ?>
                                                    <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>><?= $year ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo de Reporte -->
                            <div class="period-selector">
                                <h5 class="text-white mb-3">
                                    <i class="fas fa-file-chart-line me-2"></i>Tipo de Reporte
                                </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="incluir_ventas" id="incluir_ventas" value="1" checked>
                                            <label class="form-check-label text-white" for="incluir_ventas">
                                                <i class="fas fa-arrow-up me-1 text-success"></i>Ventas
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="incluir_compras" id="incluir_compras" value="1" checked>
                                            <label class="form-check-label text-white" for="incluir_compras">
                                                <i class="fas fa-arrow-down me-1 text-danger"></i>Compras
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="incluir_inventario" id="incluir_inventario" value="1" checked>
                                            <label class="form-check-label text-white" for="incluir_inventario">
                                                <i class="fas fa-boxes me-1 text-info"></i>Inventario
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="text-center">
                                <button type="submit" name="accion" value="ver" class="btn btn-report me-3">
                                    <i class="fas fa-eye me-2"></i>Ver Reporte
                                </button>
                                <button type="submit" name="accion" value="imprimir" class="btn btn-report">
                                    <i class="fas fa-print me-2"></i>Imprimir Reporte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reportes Rápidos -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-white text-center mb-4">
                    <i class="fas fa-bolt me-2"></i>Reportes Rápidos
                </h3>
            </div>
            <div class="col-md-4">
                <div class="report-card text-center p-4">
                    <i class="fas fa-calendar-week fa-3x mb-3 text-primary"></i>
                    <h5>Reporte Semanal</h5>
                    <p class="text-white-50">Semana actual</p>
                    <a href="generar_reporte.php?quick=semana" target="_blank" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-download me-1"></i>Generar
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-card text-center p-4">
                    <i class="fas fa-calendar-alt fa-3x mb-3 text-success"></i>
                    <h5>Reporte Mensual</h5>
                    <p class="text-white-50">Mes actual</p>
                    <a href="generar_reporte.php?quick=mes" target="_blank" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-download me-1"></i>Generar
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-card text-center p-4">
                    <i class="fas fa-calendar fa-3x mb-3 text-warning"></i>
                    <h5>Reporte Anual</h5>
                    <p class="text-white-50">Año actual</p>
                    <a href="generar_reporte.php?quick=año" target="_blank" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-download me-1"></i>Generar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manejar cambios en el tipo de período
        document.querySelectorAll('input[name="periodo_tipo"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Ocultar todos los selectores
                document.getElementById('selector-semanal').style.display = 'none';
                document.getElementById('selector-mensual').style.display = 'none';
                document.getElementById('selector-anual').style.display = 'none';
                
                // Mostrar el selector correspondiente
                document.getElementById('selector-' + this.value).style.display = 'block';
            });
        });

        // Calcular automáticamente el fin de semana
        document.getElementById('fecha_inicio_semana').addEventListener('change', function() {
            const fechaInicio = new Date(this.value);
            const fechaFin = new Date(fechaInicio);
            fechaFin.setDate(fechaInicio.getDate() + 6);
            
            document.getElementById('fecha_fin_semana').value = fechaFin.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
