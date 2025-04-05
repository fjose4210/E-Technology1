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
            $stmt = $pdo->prepare("INSERT INTO ventas (id_producto, cantidad, fecha, total) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_producto, $cantidad, date('Y-m-d H:i:s'), $total]);
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

        // Insertar el nuevo producto en la base de datos
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, categoria, cantidad, precio) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $categoria, $cantidad, $precio]);

        $success = "Producto agregado exitosamente.";
    }

    // Realizar Restock (reposicion de productos)
    if (isset($_POST['restock'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad_restock = $_POST['cantidad_restock'];

        // Obtener el producto
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch();

        if ($producto) {
            $nuevo_stock = $producto['cantidad'] + $cantidad_restock;

            // Actualizar la cantidad del producto
            $stmt = $pdo->prepare("UPDATE productos SET cantidad = ? WHERE id = ?");
            $stmt->execute([$nuevo_stock, $id_producto]);

            $success_restock = "Stock actualizado exitosamente.";
        }
    }
}

$productos = $pdo->query("SELECT * FROM productos")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
</head>
<body>
    <h1>Inventario</h1>

    <?php if (isset($error)):?>
    <div class="alert alert-danger"><?php echo "<p>$error</p>"; ?></div>
    <?php endif;?>
    <?php if (isset($success)):?>
    <div class="alert alert-danger"><?php echo "<p>$success</p>"; ?></div>
    <?php endif;?>
    <?php if (isset($success_restock)):?>
    <div class="alert alert-danger"><?php echo "<p>$success_restock</p>"; ?></div>
    <?php endif;?>
    <!-- Formulario para agregar nuevo producto -->
    <div class = "container mt-5">
    <h2>Agregar Producto</h2>
    <form method="POST">
        <label class="form-label" for="nombre">Nombre:</label><br>
        <input class="form-control" type="text" name="nombre" required><br>
        <label class="form-label" for="categoria">Categoría:</label><br>
        <input class="form-control" type="text" name="categoria" required><br>
        <label class="form-label" for="cantidad_producto">Cantidad:</label><br>
        <input class="form-control" type="number" name="cantidad_producto" min="1" required><br>
        <label class="form-label" for="precio">Precio:</label><br>
        <input class="form-control" type="number" name="precio" step="0.01" required><br><br>
        <button class="btn btn-primary" type="submit" name="agregar_producto">Agregar Producto</button>
    </form>
    </div>

    <!-- Formulario para Restock (reposicion de productos) -->
    <div class = "container mt-5">
    <h2>Restock (Reposición de Stock)</h2>
    <form method="POST">
        <label class="form-label" for="id_producto">Producto:</label><br>
        <select class="form-control" name="id_producto" required>
            <?php foreach ($productos as $producto): ?>
                <option value="<?= $producto['id'] ?>"><?= $producto['nombre'] ?></option>
            <?php endforeach; ?>
        </select><br>
        <label class="form-label" for="cantidad_restock">Cantidad a Reponer:</label><br>
        <input class="form-control" type="number" name="cantidad_restock" min="1" required><br><br>
        <button class="btn btn-primary" type="submit" name="restock">Reponer Stock</button>
    </form>
    </div>

    <div class = "container mt-5">
    <h2>Buscar Producto</h2>
    <form method="GET">
        <input class="form-control" type="text" name="search" placeholder="Buscar..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
        <button class="btn btn-primary" type="submit">Buscar</button>
    </form>

    <h2>Productos en Inventario</h2>
    <table border="1" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?= $producto['nombre'] ?></td>
                    <td><?= $producto['categoria'] ?></td>
                    <td><?= $producto['cantidad'] ?></td>
                    <td>$<?= number_format($producto['precio'], 2) ?></td>
                    <td>
                        <form method="POST" action="">
                            <input class="form-control" type="hidden" name="id_producto" value="<?= $producto['id'] ?>">
                            <label class="form-label" for="cantidad">Cantidad:</label>
                            <input class="form-control" type="number" name="cantidad" min="1" required>
                            <button class="btn btn-primary" type="submit" name="venta">Vender</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div class = "container mt-5">
    <a href="ventas.php">Ir a la sección de ventas</a>
    </div>
</body>
</html>
