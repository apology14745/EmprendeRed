<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Verifica si es cliente
$stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ?");
$stmt->execute([$id_usuario]);
$cliente = $stmt->fetch();

if (!$cliente) {
    die("Solo los clientes pueden comprar productos.");
}

$id_cliente = $cliente['id_cliente'];

if (isset($_POST['id_producto']) && isset($_POST['cantidad'])) {
    $id_producto = $_POST['id_producto'];
    $cantidad = $_POST['cantidad'];

    if (!empty($id_producto) && !empty($cantidad)) {
        try {
            // Buscar carrito activo
            $stmt = $pdo->prepare("SELECT id_carrito FROM carrito WHERE id_cliente = ? AND estado = 'activo'");
            $stmt->execute([$id_cliente]);
            $carrito = $stmt->fetch();

            if ($carrito) {
                $id_carrito = $carrito['id_carrito'];
            } else {
                // Crear nuevo carrito activo
                $stmt = $pdo->prepare("INSERT INTO carrito (id_cliente, fecha_creacion, estado) VALUES (?, NOW(), 'activo')");
                $stmt->execute([$id_cliente]);
                $id_carrito = $pdo->lastInsertId();
            }

            // Verifica si ya existe el producto en el carrito
            $stmt = $pdo->prepare("SELECT cantidad FROM carrito_producto WHERE id_carrito = ? AND id_producto = ?");
            $stmt->execute([$id_carrito, $id_producto]);
            $existente = $stmt->fetch();

            if ($existente) {
                // Sumar cantidad
                $nuevaCantidad = $existente['cantidad'] + $cantidad;
                $stmt = $pdo->prepare("UPDATE carrito_producto SET cantidad = ? WHERE id_carrito = ? AND id_producto = ?");
                $stmt->execute([$nuevaCantidad, $id_carrito, $id_producto]);
            } else {
                // Agregar nuevo producto
                $stmt = $pdo->prepare("INSERT INTO carrito_producto (id_carrito, id_producto, cantidad) VALUES (?, ?, ?)");
                $stmt->execute([$id_carrito, $id_producto, $cantidad]);
            }

            // Redirigir al carrito
            header("Location: ver_carrito.php");
            exit();

        } catch (PDOException $e) {
            echo "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        echo "Faltan datos para agregar al carrito.";
    }
} else {
    echo "Datos del producto no enviados correctamente.";
}
?>
