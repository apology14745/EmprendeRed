<?php
session_start();
include 'includes/db.php'; // Asegúrate de que $pdo esté correctamente definido

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['id_usuario'];

try {
    // Obtener el carrito y los productos asociados
    $stmt = $pdo->prepare("
        SELECT c.id_carrito, p.nombre_producto, cp.cantidad, p.precio_producto, (cp.cantidad * p.precio_producto) AS total
        FROM carrito c
        JOIN carrito_producto cp ON c.id_carrito = cp.id_carrito
        JOIN producto p ON cp.id_producto = p.id_producto
        WHERE c.id_cliente = ? AND c.estado = 'comprado'
    ");
    $stmt->execute([$id_cliente]);
    $productos = $stmt->fetchAll();

    // Calcular el total de la compra
    $total_compra = 0;
    foreach ($productos as $producto) {
        $total_compra += $producto['total'];
    }

    // Insertar la venta en la base de datos
    $stmt = $pdo->prepare("
        INSERT INTO venta (id_cliente, id_carrito, evaluacion, total_venta)
        VALUES (?, ?, ?, ?)
    ");
    // Usamos el ID del carrito y dejamos evaluacion vacío para este ejemplo
    $stmt->execute([$id_cliente, $productos[0]['id_carrito'], '', $total_compra]);

    // Obtener el ID de la venta insertada
    $id_venta = $pdo->lastInsertId();

    // Opcional: Puedes actualizar el estado del carrito para reflejar que la compra ha sido procesada
    $stmt = $pdo->prepare("UPDATE carrito SET estado = 'procesado' WHERE id_carrito = ?");
    $stmt->execute([$productos[0]['id_carrito']]);

} catch (PDOException $e) {
    echo "Error al procesar la compra: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Compra</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<h1>Compra Procesada Exitosamente</h1>

<p>Gracias por tu compra, los detalles de tu transacción son los siguientes:</p>

<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($productos as $producto): ?>
            <tr>
                <td><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                <td><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                <td>S/ <?php echo number_format($producto['precio_producto'], 2); ?></td>
                <td>S/ <?php echo number_format($producto['total'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div>
    <h3>Total de la compra: S/ <?php echo number_format($total_compra, 2); ?></h3>
</div>

<div>
    <a href="dashboard_cliente.php">Volver al inicio</a> <!-- O cualquier otra página relevante -->
</div>

</body>
</html>
