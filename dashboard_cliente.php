<?php
session_start();
include 'includes/db.php';

if ($_SESSION['tipo_usuario'] !== 'cliente') {
    header("Location: index.php");
    exit();
}

$id_cliente = $_SESSION['id_usuario'];

// Consultar datos del usuario
$stmt = $pdo->prepare("SELECT nombre_usuario, correo_usuario, telefono FROM usuario WHERE id_usuario = ?");
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch();

// Actualizar evaluación si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_venta']) && isset($_POST['estrellas'])) {
    $id_venta = $_POST['id_venta'];
    $estrellas = intval($_POST['estrellas']);
    if ($estrellas >= 1 && $estrellas <= 5) {
        $updateStmt = $pdo->prepare("UPDATE venta SET evaluacion = ? WHERE id_venta = ? AND id_cliente = ?");
        $updateStmt->execute([$estrellas, $id_venta, $id_cliente]);
    }
}

// Obtener historial de compras actualizado
$stmt_compras = $pdo->prepare("
    SELECT v.id_venta, v.evaluacion, v.total_venta, v.fecha_venta
    FROM venta v
    JOIN carrito c ON v.id_carrito = c.id_carrito
    WHERE v.id_cliente = ?
    ORDER BY v.fecha_venta DESC
");
$stmt_compras->execute([$id_cliente]);
$compras = $stmt_compras->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Cliente - EmprendeRed</title>
    <link rel="stylesheet" href="assets/estilos_cliente.css">
    <link rel="stylesheet" href="apariencia_aux.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/logo.png" type="image/png">
</head>
<body>
    <div class="contenedor">
        <h2>Bienvenido, <?php echo htmlspecialchars($cliente['nombre_usuario']); ?>!</h2>

        <div class="perfil">
            <h3>Perfil</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($cliente['nombre_usuario']); ?></p>
            <p><strong>Correo:</strong> <?php echo htmlspecialchars($cliente['correo_usuario']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($cliente['telefono']); ?></p>
        </div>

        <div class="historial-compras">
            <h3>Historial de Compras</h3>
            <?php if ($compras): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Venta</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Valoración</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $compra): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($compra['id_venta']); ?></td>
                                <td><?php echo date("d/m/Y", strtotime($compra['fecha_venta'])); ?></td>
                                <td>S/ <?php echo number_format($compra['total_venta'], 2); ?></td>
                                <td>
                                    <form id="form-<?php echo $compra['id_venta']; ?>" method="POST">
                                        <input type="hidden" name="id_venta" value="<?php echo $compra['id_venta']; ?>">
                                        <input type="hidden" name="estrellas" value="<?php echo (int)$compra['evaluacion']; ?>">
                                        <div id="estrellas-<?php echo $compra['id_venta']; ?>" class="estrellas">
                                            <?php 
                                            $evaluacion = (int)$compra['evaluacion'];
                                            for ($i = 1; $i <= 5; $i++): ?>
                                                <img src="imágenes/<?php echo ($i <= $evaluacion) ? 'estrella_amarilla.png' : 'estrella.png'; ?>"
                                                     class="estrella"
                                                     onclick="seleccionarEstrellas(<?php echo $compra['id_venta']; ?>, <?php echo $i; ?>)"
                                                     alt="Estrella" title="<?php echo $i; ?> estrella(s)"
                                                     data-value="<?php echo $i; ?>">
                                            <?php endfor; ?>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <button class="detalles-btn" onclick="toggleDetalles(<?php echo $compra['id_venta']; ?>)">
                                        Ver detalles
                                    </button>

                                    


                                    <div class="detalles-carrito" id="detalles-<?php echo $compra['id_venta']; ?>">
                                        <?php
                                        $stmt_detalles = $pdo->prepare("
                                            SELECT p.nombre_producto, p.precio_producto, cp.cantidad
                                            FROM carrito_producto cp
                                            JOIN producto p ON cp.id_producto = p.id_producto
                                            WHERE cp.id_carrito = (SELECT id_carrito FROM venta WHERE id_venta = ?)
                                        ");
                                        $stmt_detalles->execute([$compra['id_venta']]);
                                        $productos = $stmt_detalles->fetchAll();
                                        ?>
                                        <table class="tabla-detalles">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Precio Unitario</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($productos as $producto): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                                                        <td><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                                                        <td>S/ <?php echo number_format($producto['precio_producto'], 2); ?></td>
                                                        <td>S/ <?php echo number_format($producto['precio_producto'] * $producto['cantidad'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button class="reporte-btn" onclick="toggleReporte(<?php echo $compra['id_venta']; ?>)">
                                        Reportar
                                    </button>
                                    <div class="formulario-reporte" id="reporte-<?php echo $compra['id_venta']; ?>" style="display:none;">
                                        <form action="registrar_reporte.php" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="id_venta" value="<?php echo $compra['id_venta']; ?>">
                                            <textarea name="descripcion" placeholder="Describe tu queja..." required></textarea><br>
                                            <input type="file" name="imagen"><br>
                                            <button type="submit">Enviar Reporte</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sin-compras">No has realizado compras aún.</p>
            <?php endif; ?>
        </div>

        <div class="botones">
            <a href="ver_productos.php" class="btn">Ver productos</a>
            <a href="logout.php" class="btn">Cerrar sesión</a>
        </div>
    </div>
    <script src="script_aux.js" ></script>
    <script src="assets/script_cliente.js" ></script>
</body>
</html>