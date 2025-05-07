<?php
session_start();
require_once 'includes/db.php';

// Verificar si es administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 'administrador') {
    header("Location: login.php");
    exit;
}

// Obtener lista de productos con información del emprendedor
$stmt = $pdo->prepare("
    SELECT p.*, u.nombre_usuario as nombre_emprendedor 
    FROM producto p
    JOIN emprendedor e ON p.id_emprendedor = e.id_emprendedor
    JOIN usuario u ON e.id_emprendedor = u.id_usuario
    ORDER BY p.fecha_publicacion DESC
");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar eliminación de producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_producto'])) {
    $id_producto = $_POST['id_producto'];

    try {
        // Iniciar transacción
        $pdo->beginTransaction();

        // Eliminar dependencias primero
        $stmt1 = $pdo->prepare("DELETE FROM carrito_producto WHERE id_producto = ?");
        $stmt1->execute([$id_producto]);

        // Eliminar producto
        $stmt2 = $pdo->prepare("DELETE FROM producto WHERE id_producto = ?");
        $stmt2->execute([$id_producto]);

        // Confirmar transacción
        $pdo->commit();

        $_SESSION['mensaje'] = "Producto eliminado correctamente";
        header("Location: productos.php");
        exit;
    } catch (PDOException $e) {
        // Revertir cambios en caso de error
        $pdo->rollBack();
        $_SESSION['error'] = "Error al eliminar producto: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <style>
        :root {
                --primary: #4f46e5;
                --primary-dark: #4338ca;
                --secondary: #f97316;
                --dark: #1f2937;
                --light: #f3f4f6;
                --danger: #ef4444;
                --success: #10b981;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            body {
                background-color: #f5f7fa;
            }

            .admin-container {
                display: grid;
                grid-template-columns: 250px 1fr;
                min-height: 100vh;
            }

            .sidebar {
                background-color: var(--dark);
                color: white;
                padding: 1.5rem;
            }

            .sidebar-header {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }

            .sidebar-header img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
            }

            .sidebar-nav {
                list-style: none;
            }

            .sidebar-nav li {
                margin-bottom: 0.5rem;
            }

            .sidebar-nav a {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                transition: all 0.3s;
            }

            .sidebar-nav a:hover, .sidebar-nav a.active {
                background-color: var(--primary);
            }

            .sidebar-nav i {
                width: 24px;
                text-align: center;
            }

            .main-content {
                padding: 2rem;
            }

            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
            }

            .header h1 {
                color: var(--dark);
            }

            .user-info {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .user-info img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
            }

            .card {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid #eee;
            }

            .card-header h2 {
                color: var(--dark);
                font-size: 1.25rem;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th, td {
                padding: 0.75rem;
                text-align: left;
                border-bottom: 1px solid #eee;
            }

            th {
                background-color: #f9fafb;
                color: var(--dark);
                font-weight: 600;
            }

            tr:hover {
                background-color: #f9fafb;
            }

            .badge {
                display: inline-block;
                padding: 0.25rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
            }

            .badge-primary {
                background-color: #e0e7ff;
                color: var(--primary);
            }

            .badge-success {
                background-color: #d1fae5;
                color: var(--success);
            }

            .badge-danger {
                background-color: #fee2e2;
                color: var(--danger);
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 1rem;
                border-radius: 6px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s;
                border: none;
                font-size: 0.875rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .btn-danger {
                background-color: var(--danger);
                color: white;
            }

            .btn-danger:hover {
                background-color: #dc2626;
            }

            .btn-success {
                background-color: var(--success);
                color: white;
            }

            .btn-success:hover {
                background-color: #059669;
            }

            .alert {
                padding: 0.75rem 1rem;
                border-radius: 6px;
                margin-bottom: 1.5rem;
                font-size: 0.875rem;
            }

            .alert-success {
                background-color: #d1fae5;
                color: var(--success);
                border: 1px solid #6ee7b7;
            }

            .alert-danger {
                background-color: #fee2e2;
                color: var(--danger);
                border: 1px solid #fca5a5;
            }

        .producto-imagen {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar (igual que en tu código) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="imágenes/admin.png" alt="Logo">
                <h3>Panel Admin</h3>
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin/usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                    <li><a href="productos.php" class="active"><i class="fas fa-box-open"></i> Productos</a></li>
                    <li><a href="admin/ventas.php"><i class="fas fa-chart-line"></i> Ventas</a></li>
                    <li><a href="admin/configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Administración de Productos</h1>
                <div class="user-info">
                    <span><?php echo $_SESSION['nombre_usuario']; ?></span>
                    <img src="imágenes/icono-usuario.png" alt="Usuario">
                </div>
            </div>

            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Lista de Productos</h2>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Categoría</th>
                                <th>Emprendedor</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo $producto['id_producto']; ?></td>
                                <td>
                                    <img src="<?php echo !empty($producto['imagen_url']) ? htmlspecialchars($producto['imagen_url']) : 'assets/no-image.png'; ?>" 

                                         class="producto-imagen" alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>">
                                </td>
                                <td><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                                <td><?php echo substr(htmlspecialchars($producto['descripcion_producto']), 0, 50) . '...'; ?></td>
                                <td>S/ <?php echo number_format($producto['precio_producto'], 2); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre_emprendedor']); ?></td>
                                <td><?php echo date("d/m/Y", strtotime($producto['fecha_publicacion'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este producto?');">
                                        <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                                        <button type="submit" name="eliminar_producto" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>