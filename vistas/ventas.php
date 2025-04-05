<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['venta'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad = $_POST['cantidad'];

        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch();

        if ($producto && $producto['cantidad'] >= $cantidad) {
            $nuevo_stock = $producto['cantidad'] - $cantidad;

            // Actualizar el inventario
            $pdo->prepare("UPDATE productos SET cantidad = ? WHERE id = ?")->execute([$nuevo_stock, $id_producto]);

            // Registrar la venta
            $total = $producto['precio'] * $cantidad;
            $stmt = $pdo->prepare("INSERT INTO ventas (id_producto, cantidad, fecha, total) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_producto, $cantidad, date('Y-m-d H:i:s'), $total]);
        } else {
            $error = "No hay suficiente stock para esta venta.";
        }
    }
}

$ventas = $pdo->query("SELECT * FROM ventas ORDER BY fecha DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
</head>
<body>
    <h1>Ventas</h1>

    <?php if (isset($error)):?>
    <div class="alert alert-danger"><?php echo "<p>$error</p>"; ?></div>
    <?php endif;?>
    <div class = "container mt-5">
    <h2>Historial de Ventas</h2>
    <table border="1" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Total</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ventas as $venta): ?>
                <tr>
                    <td><?= $venta['id_producto'] ?></td>
                    <td><?= $venta['cantidad'] ?></td>
                    <td>$<?= number_format($venta['total'], 2) ?></td>
                    <td><?= $venta['fecha'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="inventario.php">Ir a la sección de inventario</a>
    </div>
</body>
</html>
