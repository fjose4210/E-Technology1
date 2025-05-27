<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Venta
    if (isset($_POST['venta'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad = $_POST['cantidad'];

        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch();

        if ($producto && $producto['cantidad'] >= $cantidad) {
            $nuevo_stock = $producto['cantidad'] - $cantidad;

            //Actualizar el inventario
            $pdo->prepare("UPDATE productos SET cantidad = ? WHERE id = ?")->execute([$nuevo_stock, $id_producto]);

            //Registrar la venta
            $total = $producto['precio'] * $cantidad;
            $stmt = $pdo->prepare("INSERT INTO ventas (id_producto, id_usuario, cantidad, fecha, total) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_producto, $_SESSION['user_id'], $cantidad, date('Y-m-d H:i:s'), $total]);
            
            $success = "Venta registrada exitosamente.";
        } else {
            $error = "No hay suficiente stock para esta venta.";
        }
    }

    // Agregar un nuevo producto al inventario
    if (isset($_POST['agregar_producto'])) {
        $nombre = $_POST['nombre'];
        $categoria = $_POST['categoria'];
        $cantidad = $_POST['cantidad_producto'];
        $precio = $_POST['precio'];
        $precio_compra = $_POST['precio_compra'];

        // Insertar el nuevo producto en la base de datos
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, categoria, cantidad, precio, precio_compra) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $categoria, $cantidad, $precio, $precio_compra]);
        
        $producto_id = $pdo->lastInsertId();
        
        // Registrar la compra inicial
        $total_compra = $precio_compra * $cantidad;
        $stmt = $pdo->prepare("INSERT INTO compras (id_producto, id_usuario, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$producto_id, $_SESSION['user_id'], $cantidad, $precio_compra, $total_compra]);

        $success = "Producto agregado exitosamente. Compra registrada: $" . number_format($total_compra, 2);
    }

    // Realizar Restock (reposicion de productos)
    if (isset($_POST['restock'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad_restock = $_POST['cantidad_restock'];
        $precio_compra_restock = $_POST['precio_compra_restock'];

        // Obtener el producto
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch();

        if ($producto) {
            $nuevo_stock = $producto['cantidad'] + $cantidad_restock;
            $total_compra = $precio_compra_restock * $cantidad_restock;

            // Actualizar la cantidad del producto y el precio de compra promedio
            $stmt = $pdo->prepare("UPDATE productos SET cantidad = ?, precio_compra = ? WHERE id = ?");
            $stmt->execute([$nuevo_stock, $precio_compra_restock, $id_producto]);
            
            // Registrar la compra
            $stmt = $pdo->prepare("INSERT INTO compras (id_producto, id_usuario, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_producto, $_SESSION['user_id'], $cantidad_restock, $precio_compra_restock, $total_compra]);

            $success_restock = "Stock actualizado exitosamente. Gasto en compra: $" . number_format($total_compra, 2);
        }
    }
}

$productos = $pdo->query("SELECT * FROM productos")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Sistema de Inventario</title>
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
    
    .profit-info {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        color: white;
    }
    
    .profit-positive {
        color: #32cd32;
        font-weight: bold;
    }
    
    .profit-negative {
        color: #ff6b6b;
        font-weight: bold;
    }
</style>
</head>
<body>
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
                        <a class="nav-link active" href="inventario.php">
                            <i class="fas fa-boxes me-1"></i> Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ventas.php">
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
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_restock)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_restock; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!--Columna izquierda: Formularios-->
            <div class="col-lg-4">
                <!--Formulario para agregar nuevo producto-->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Agregar Producto</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre:</label>
                                <input type="text" class="form-control" name="nombre" id="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría:</label>
                                <input type="text" class="form-control" name="categoria" id="categoria" required>
                            </div>
                            <div class="mb-3">
                                <label for="cantidad_producto" class="form-label">Cantidad:</label>
                                <input type="number" class="form-control" name="cantidad_producto" id="cantidad_producto" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="precio_compra" class="form-label">Precio de Compra (Unitario):</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="precio_compra" id="precio_compra" step="0.01" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio de Venta:</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="precio" id="precio" step="0.01" required>
                                </div>
                            </div>
                            <div class="profit-info">
                                <small>
                                    <i class="fas fa-calculator me-1"></i>
                                    <span id="ganancia-unitaria">Ganancia por unidad: $0.00</span>
                                </small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="agregar_producto" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Agregar Producto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!--Formulario para Restock-->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Reposición de Stock</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="id_producto" class="form-label">Producto:</label>
                                <select name="id_producto" id="id_producto" class="form-select" required>
                                    <option value="">Seleccionar producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $producto['id'] ?>" data-precio-compra="<?= $producto['precio_compra'] ?>">
                                            <?= $producto['nombre'] ?> (Stock actual: <?= $producto['cantidad'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="cantidad_restock" class="form-label">Cantidad a Reponer:</label>
                                <input type="number" class="form-control" name="cantidad_restock" id="cantidad_restock" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="precio_compra_restock" class="form-label">Precio de Compra (Unitario):</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="precio_compra_restock" id="precio_compra_restock" step="0.01" required>
                                </div>
                            </div>
                            <div class="profit-info">
                                <small>
                                    <i class="fas fa-calculator me-1"></i>
                                    <span id="total-compra">Total de compra: $0.00</span>
                                </small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="restock" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Reponer Stock
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!--Columna derecha: Inventario-->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Productos en Inventario</h5>
                        <form method="GET" class="d-flex">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Buscar producto..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th>Cantidad</th>
                                        <th>P. Compra</th>
                                        <th>P. Venta</th>
                                        <th>Ganancia/U</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($productos) > 0): ?>
                                        <?php foreach ($productos as $producto): ?>
                                            <?php 
                                            $ganancia_unitaria = $producto['precio'] - $producto['precio_compra'];
                                            $ganancia_class = $ganancia_unitaria > 0 ? 'profit-positive' : 'profit-negative';
                                            ?>
                                            <tr>
                                                <td><?= $producto['nombre'] ?></td>
                                                <td><?= $producto['categoria'] ?></td>
                                                <td class="<?= $producto['cantidad'] < 5 ? 'low-stock' : '' ?>">
                                                    <?= $producto['cantidad'] ?>
                                                    <?php if ($producto['cantidad'] < 5): ?>
                                                        <i class="fas fa-exclamation-triangle ms-1" title="Stock bajo"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>$<?= number_format($producto['precio_compra'], 2) ?></td>
                                                <td>$<?= number_format($producto['precio'], 2) ?></td>
                                                <td class="<?= $ganancia_class ?>">
                                                    $<?= number_format($ganancia_unitaria, 2) ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ventaModal<?= $producto['id'] ?>">
                                                        <i class="fas fa-shopping-cart me-1"></i> Vender
                                                    </button>
                                                    
                                                    <!--Modal de Venta-->
                                                    <div class="modal fade" id="ventaModal<?= $producto['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Vender <?= $producto['nombre'] ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="id_producto" value="<?= $producto['id'] ?>">
                                                                        <p>Stock disponible: <strong><?= $producto['cantidad'] ?></strong></p>
                                                                        <p>Precio unitario: <strong>$<?= number_format($producto['precio'], 2) ?></strong></p>
                                                                        <p>Costo unitario: <strong>$<?= number_format($producto['precio_compra'], 2) ?></strong></p>
                                                                        <p class="<?= $ganancia_class ?>">Ganancia por unidad: <strong>$<?= number_format($ganancia_unitaria, 2) ?></strong></p>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="cantidad<?= $producto['id'] ?>" class="form-label">Cantidad a vender:</label>
                                                                            <input type="number" class="form-control cantidad-venta" id="cantidad<?= $producto['id'] ?>" name="cantidad" min="1" max="<?= $producto['cantidad'] ?>" data-ganancia="<?= $ganancia_unitaria ?>" required>
                                                                        </div>
                                                                        
                                                                        <div class="profit-info">
                                                                            <small>
                                                                                <span id="ganancia-total<?= $producto['id'] ?>">Ganancia total: $0.00</span>
                                                                            </small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                        <button type="submit" name="venta" class="btn btn-primary">Confirmar Venta</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-3">No se encontraron productos</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calcular ganancia unitaria en tiempo real
        document.getElementById('precio_compra').addEventListener('input', calcularGanancia);
        document.getElementById('precio').addEventListener('input', calcularGanancia);
        
        function calcularGanancia() {
            const precioCompra = parseFloat(document.getElementById('precio_compra').value) || 0;
            const precioVenta = parseFloat(document.getElementById('precio').value) || 0;
            const ganancia = precioVenta - precioCompra;
            
            const gananciaElement = document.getElementById('ganancia-unitaria');
            gananciaElement.textContent = `Ganancia por unidad: $${ganancia.toFixed(2)}`;
            gananciaElement.className = ganancia > 0 ? 'profit-positive' : 'profit-negative';
        }
        
        // Calcular total de compra en restock
        document.getElementById('cantidad_restock').addEventListener('input', calcularTotalCompra);
        document.getElementById('precio_compra_restock').addEventListener('input', calcularTotalCompra);
        
        function calcularTotalCompra() {
            const cantidad = parseFloat(document.getElementById('cantidad_restock').value) || 0;
            const precio = parseFloat(document.getElementById('precio_compra_restock').value) || 0;
            const total = cantidad * precio;
            
            document.getElementById('total-compra').textContent = `Total de compra: $${total.toFixed(2)}`;
        }
        
        // Autocompletar precio de compra en restock
        document.getElementById('id_producto').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const precioCompra = selectedOption.getAttribute('data-precio-compra');
            if (precioCompra) {
                document.getElementById('precio_compra_restock').value = precioCompra;
                calcularTotalCompra();
            }
        });
        
        // Calcular ganancia total en modal de venta
        document.querySelectorAll('.cantidad-venta').forEach(input => {
            input.addEventListener('input', function() {
                const cantidad = parseFloat(this.value) || 0;
                const gananciaUnitaria = parseFloat(this.getAttribute('data-ganancia')) || 0;
                const gananciaTotal = cantidad * gananciaUnitaria;
                const productId = this.id.replace('cantidad', '');
                
                const gananciaElement = document.getElementById('ganancia-total' + productId);
                gananciaElement.textContent = `Ganancia total: $${gananciaTotal.toFixed(2)}`;
                gananciaElement.className = gananciaTotal > 0 ? 'profit-positive' : 'profit-negative';
            });
        });
    </script>
</body>
</html>
