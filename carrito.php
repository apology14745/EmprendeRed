<?php
session_start();
include 'includes/db.php'; // Asegúrate de que $pdo esté correctamente definido

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['id_usuario'];

try {
    // Obtener los productos del carrito
    $stmt = $pdo->prepare("
        SELECT p.nombre_producto, cp.cantidad, p.precio_producto, (cp.cantidad * p.precio_producto) AS total
        FROM carrito c
        JOIN carrito_producto cp ON c.id_carrito = cp.id_carrito
        JOIN producto p ON cp.id_producto = p.id_producto
        WHERE c.id_cliente = ? AND c.estado = 'comprado'
    ");
    $stmt->execute([$id_cliente]);
    $productos = $stmt->fetchAll();

    // Obtener el total de la compra
    $stmt = $pdo->prepare("
        SELECT SUM(cp.cantidad * p.precio_producto) AS total_compra
        FROM carrito c
        JOIN carrito_producto cp ON c.id_carrito = cp.id_carrito
        JOIN producto p ON cp.id_producto = p.id_producto
        WHERE c.id_cliente = ? AND c.estado = 'comprado'
    ");
    $stmt->execute([$id_cliente]);
    $total_compra = $stmt->fetchColumn();

} catch (PDOException $e) {
    echo "Error al obtener los productos del carrito: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <style>
        /* Agregar estilos básicos para la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        .total-compra {
            font-size: 1.2em;
            margin-top: 20px;
            font-weight: bold;
        }
        .checkout-button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1em;
        }
        .checkout-button:hover {
            background-color: #45a049;
        }
        .cerrar-sesion {
            margin-top: 20px;
            display: inline-block;
            padding: 10px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .cerrar-sesion:hover {
            background-color: #e53935;
        }
    </style>
</head>
<body>

<h1>Tu Carrito de Compras</h1>

<?php if ($productos): ?>
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

    <div class="total-compra">
        <h3>Total de la compra: S/ <?php echo number_format($total_compra, 2); ?></h3>
    </div>

    <div>
        <a href="checkout.php" class="checkout-button">Proceder a la compra</a> <!-- Cambia por tu ruta de checkout -->
    </div>

<?php else: ?>
    <p>No hay productos en tu carrito.</p>
<?php endif; ?>

<!-- Enlace para cerrar sesión -->
<a href="logout.php" class="cerrar-sesion">Cerrar sesión</a>

</body>
</html>
