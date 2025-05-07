
<?php
session_start();
include 'includes/db.php';

if ($_SESSION['tipo_usuario'] !== 'emprendedor') {
    header("Location: index.php");
    exit();
}

$id_emprendedor = $_SESSION['id_usuario'];

// Obtener información del emprendedor
$stmt_emp = $pdo->prepare("SELECT nombre_usuario FROM usuario WHERE id_usuario = ?");
$stmt_emp->execute([$id_emprendedor]);
$emprendedor = $stmt_emp->fetch();

// Obtener productos del emprendedor
$stmt_productos = $pdo->prepare("
    SELECT p.*, 
           (SELECT AVG(v.evaluacion) FROM venta v 
            JOIN carrito c ON v.id_carrito = c.id_carrito
            JOIN carrito_producto cp ON c.id_carrito = cp.id_carrito
            WHERE cp.id_producto = p.id_producto) as promedio_valoracion
    FROM producto p
    WHERE p.id_emprendedor = ?
    ORDER BY p.fecha_publicacion DESC
");
$stmt_productos->execute([$id_emprendedor]);
$productos = $stmt_productos->fetchAll();
// Obtener pedidos realizados a productos del emprendedor
$stmt_pedidos = $pdo->prepare("
    SELECT v.id_venta, u.nombre_usuario AS cliente, v.total_venta, v.evaluacion, c.fecha_creacion
    FROM venta v
    JOIN carrito c ON v.id_carrito = c.id_carrito
    JOIN carrito_producto cp ON cp.id_carrito = c.id_carrito
    JOIN producto p ON p.id_producto = cp.id_producto
    JOIN usuario u ON u.id_usuario = v.id_cliente
    WHERE p.id_emprendedor = ?
    GROUP BY v.id_venta
    ORDER BY c.fecha_creacion DESC
");
$stmt_pedidos->execute([$id_emprendedor]);
$pedidos = $stmt_pedidos->fetchAll();


// Eliminar producto si se recibe la solicitud


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_producto'])) {
    $id_producto = $_POST['id_producto'];

    // Verificar que el producto pertenece al emprendedor
    $stmt_verificar = $pdo->prepare("SELECT id_emprendedor FROM producto WHERE id_producto = ?");
    $stmt_verificar->execute([$id_producto]);
    $producto = $stmt_verificar->fetch();

    if ($producto && $producto['id_emprendedor'] == $id_emprendedor) {
        try {
            // Iniciar transacción
            $pdo->beginTransaction();

            // 1. Eliminar de carrito_producto
            $stmt1 = $pdo->prepare("DELETE FROM carrito_producto WHERE id_producto = ?");
            $stmt1->execute([$id_producto]);

            // 2. (Opcional) Eliminar ventas asociadas si fuera necesario
            // Solo si tu lógica lo requiere. Esto puede eliminar muchas cosas si no se controla.
            // Si quieres hacerlo, primero obtén los id_carrito relacionados al producto.
            // Por ejemplo: buscar carritos que solo contienen ese producto.

            // 3. Eliminar el producto
            $stmt2 = $pdo->prepare("DELETE FROM producto WHERE id_producto = ?");
            $stmt2->execute([$id_producto]);

            $pdo->commit();
            $_SESSION['mensaje'] = "Producto eliminado correctamente";
            header("Location: dashboard_emprendedor.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error al eliminar producto: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "No tienes permiso para eliminar este producto.";
    }
}

?>

<?php include 'includes/header.php'; ?>

<style>
    body {
        background-color: #f0f2f5;
        font-family: 'Poppins', sans-serif;
    }

    .dashboard-container {
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .productos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .producto-card {
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }

    .producto-card:hover {
        transform: translateY(-5px);
    }

    .producto-imagen {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .producto-info {
        padding: 15px;
    }

    .producto-titulo {
        font-size: 18px;
        margin-bottom: 10px;
        color: #333;
    }

    .producto-precio {
        font-weight: bold;
        color: #006064;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .producto-acciones {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
    gap: 10px;
}

.producto-acciones form {
    margin: 0;
}

.producto-acciones a.btn {
    flex: 1;
    text-align: center;
}

.producto-acciones button.btn {
    flex: 1;
    width: 100%;
}

    .btn {
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
        border: none;
        cursor: pointer;
    }

    .btn-danger:hover {
        background-color: #c82333;
    }

    .btn-primary {
        background-color: #006064;
        color: white;
    }

    .btn-primary:hover {
        background-color: #00838f;
    }

    .valoracion-promedio {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .estrella {
        width: 20px;
        height: 20px;
        margin-right: 2px;
    }
    .formulario-reporte {
    margin-top: 10px;
    background: #fff9f9;
    border: 1px solid #f5c6cb;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.formulario-reporte textarea {
    width: 100%;
    padding: 10px;
    resize: vertical;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 8px;
}

.formulario-reporte input[type="file"] {
    margin-bottom: 10px;
}

.formulario-reporte button {
    background-color: #3498db;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 5px;
    font-weight: 500;
    cursor: pointer;
}

.formulario-reporte button:hover {
    background-color: #2980b9;
}


.reporte-btn {
    background-color: #e74c3c;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 5px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 6px;
}

.reporte-btn:hover {
    background-color: #c0392b;
}

    .sin-productos {
        text-align: center;
        padding: 40px;
        color: #666;
        font-size: 18px;
    }

    .acciones-principales {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
    }

    @media (max-width: 768px) {
        .productos-grid {
            grid-template-columns: 1fr;
        }
        
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .acciones-principales {
            flex-direction: column;
        }
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h2>Bienvenido, <?php echo htmlspecialchars($emprendedor['nombre_usuario']); ?></h2>
        <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
    </div>

    <div class="acciones-principales">
        <a href="agregar_producto.php" class="btn btn-primary">Subir nuevo producto</a>
    </div>

    <h3>Mis Productos</h3>
    
    <?php if (count($productos) > 0): ?>
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="producto-card">
                    <?php
$imagen = !empty($producto['imagen_url']) ? $producto['imagen_url'] : 'assets/no-image.png';
?>
<img src="<?php echo htmlspecialchars($imagen); ?>" alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" class="producto-imagen">

                    <div class="producto-info">
                        <h3 class="producto-titulo"><?php echo htmlspecialchars($producto['nombre_producto']); ?></h3>
                        
                        <?php if ($producto['promedio_valoracion']): ?>
                            <div class="valoracion-promedio">
                                <?php 
                                $valoracion_redondeada = round($producto['promedio_valoracion']);
                                for ($i = 1; $i <= 5; $i++): ?>
                                    <img src="imágenes/<?php echo ($i <= $valoracion_redondeada) ? 'estrella_amarilla.png' : 'estrella.png'; ?>" 
                                         class="estrella" 
                                         alt="Estrella">
                                <?php endfor; ?>
                                <span>(<?php echo number_format($producto['promedio_valoracion'], 1); ?>)</span>
                            </div>
                        <?php else: ?>
                            <div class="valoracion-promedio">
                                <span>Sin valoraciones aún</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="producto-precio">S/ <?php echo number_format($producto['precio_producto'], 2); ?></div>
                        <p><?php echo htmlspecialchars($producto['descripcion_producto']); ?></p>
                        
                        <div class="producto-acciones">
    <a href="editar_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-primary">Editar</a>
    <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este producto?');" style="display: inline;">
        <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
        <button type="submit" name="eliminar_producto" class="btn btn-danger">Eliminar</button>
    </form>
</div>


                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="sin-productos">
            <p>No has subido ningún producto aún.</p>
            <a href="agregar_producto.php" class="btn btn-primary">Subir mi primer producto</a>
        </div>
    <?php endif; ?>


    <h3>Pedidos Realizados</h3>

<?php if (count($pedidos) > 0): ?>
    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <thead>
            <tr style="background-color:#006064; color:white;">
                <th style="padding:10px;">Cliente</th>
                <th style="padding:10px;">Total</th>
                <th style="padding:10px;">Evaluación</th>
                <th style="padding:10px;">Fecha</th>
                <th style="padding:10px;">Acción</th> <!-- Nueva columna -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr style="border-bottom:1px solid #ccc;">
                    <td style="padding:10px;"><?php echo htmlspecialchars($pedido['cliente']); ?></td>
                    <td style="padding:10px;">S/ <?php echo number_format($pedido['total_venta'], 2); ?></td>
                    <td style="padding:10px;"><?php echo $pedido['evaluacion'] ? $pedido['evaluacion'] . ' / 5' : 'Sin evaluar'; ?></td>
                    <td style="padding:10px;"><?php echo date("d/m/Y H:i", strtotime($pedido['fecha_creacion'])); ?></td>
                    <td style="padding:10px;">
                        <!-- Botón que muestra/oculta el formulario -->
                        <button class="reporte-btn" onclick="toggleReporte(<?php echo $pedido['id_venta']; ?>)">Reportar</button>

                        <!-- Formulario oculto inicialmente -->
                        <div class="formulario-reporte" id="reporte-<?php echo $pedido['id_venta']; ?>" style="display:none; margin-top:10px;">
                            <form action="registrar_reporteE.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id_venta" value="<?php echo $pedido['id_venta']; ?>">
                                <textarea name="descripcion" placeholder="Describe tu queja..." required style="width:100%;"></textarea><br>
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
    <p class="sin-productos">Aún no se han realizado pedidos a tus productos.</p>
<?php endif; ?>

</div>


<?php include 'includes/footer.php'; ?>

<script>
// Esta función muestra u oculta el formulario de reporte para una venta específica
function toggleReporte(idVenta) {
    const form = document.getElementById(`reporte-${idVenta}`);
    if (form) {
        form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
    }
}
</script>
