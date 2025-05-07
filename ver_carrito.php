<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener ID de cliente
$stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ?");
$stmt->execute([$id_usuario]);
$cliente = $stmt->fetch();

if (!$cliente) {
    die("<div class='alert alert-danger'>Solo los clientes pueden ver el carrito.</div>");
}

$id_cliente = $cliente['id_cliente'];

// Buscar carrito activo
$stmt = $pdo->prepare("SELECT id_carrito FROM carrito WHERE id_cliente = ? AND estado = 'activo'");
$stmt->execute([$id_cliente]);
$carrito = $stmt->fetch();

if (!$carrito) {
    echo "<div class='container mt-5'><div class='alert alert-info'>No tienes productos en tu carrito.</div></div>";
    exit();
}

$id_carrito = $carrito['id_carrito'];

// Eliminar producto si se solicita
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_producto_eliminar = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM carrito_producto WHERE id_carrito = ? AND id_producto = ?");
    $stmt->execute([$id_carrito, $id_producto_eliminar]);
    header("Location: ver_carrito.php");
    exit();
}

// Confirmar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    // Calcular total antes de confirmar
    $stmt = $pdo->prepare("
        SELECT SUM(p.precio_producto * cp.cantidad) AS total
        FROM carrito_producto cp
        JOIN producto p ON cp.id_producto = p.id_producto
        WHERE cp.id_carrito = ?
    ");
    $stmt->execute([$id_carrito]);
    $total = $stmt->fetchColumn();

    try {
        // Actualizar carrito
        $stmt = $pdo->prepare("UPDATE carrito SET estado = 'comprado' WHERE id_carrito = ?");
        $stmt->execute([$id_carrito]);

        // Insertar venta
        $stmt = $pdo->prepare("INSERT INTO venta (id_cliente, id_carrito, evaluacion, total_venta) VALUES (?, ?, '', ?)");
        $stmt->execute([$id_cliente, $id_carrito, $total]);

        header("Location: dashboard_cliente.php");
        exit();
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error al confirmar compra: " . $e->getMessage() . "</div>";
    }
}

// Obtener productos del carrito
$stmt = $pdo->prepare("
    SELECT cp.id_producto, p.nombre_producto, p.precio_producto, cp.cantidad,
           (p.precio_producto * cp.cantidad) AS subtotal
    FROM carrito_producto cp
    JOIN producto p ON cp.id_producto = p.id_producto
    WHERE cp.id_carrito = ?
");
$stmt->execute([$id_carrito]);
$productos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras</title>
    <link rel="icon" href="assets/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Tu Carrito</h2>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Producto</th>
                <th>Precio (S/)</th>
                <th>Cantidad</th>
                <th>Subtotal (S/)</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $total = 0;
        foreach ($productos as $producto):
            $total += $producto['subtotal'];
        ?>
            <tr>
                <td><?= htmlspecialchars($producto['nombre_producto']) ?></td>
                <td><?= number_format($producto['precio_producto'], 2) ?></td>
                <td><?= $producto['cantidad'] ?></td>
                <td><?= number_format($producto['subtotal'], 2) ?></td>
                <td>
                    <a href="ver_carrito.php?eliminar=<?= $producto['id_producto'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Seguro que deseas eliminar este producto?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center">
        <h4>Total: S/ <?= number_format($total, 2) ?></h4>
        <form method="post">
            <button type="submit" name="confirmar" class="btn btn-success">Confirmar Compra</button>
        </form>
    </div>

    <a href="ver_productos.php" class="btn btn-secondary mt-3">Seguir comprando</a>
</div>
</body>
</html>
