<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Error: ID de emprendedor no proporcionado.";
    exit();
}

$id_emprendedor = $_GET['id'];

try {
    // Datos del emprendedor
    $stmt = $pdo->prepare("
        SELECT e.*, u.nombre_usuario, u.correo_usuario AS email_usuario
        FROM emprendedor e
        JOIN usuario u ON e.id_emprendedor = u.id_usuario
        WHERE e.id_emprendedor = :id_emprendedor
    ");
    $stmt->bindParam(':id_emprendedor', $id_emprendedor, PDO::PARAM_INT);
    $stmt->execute();
    $emprendedor = $stmt->fetch();

    if (!$emprendedor) {
        echo "Emprendedor no encontrado.";
        exit();
    }

    // Productos del emprendedor
    $stmt_productos = $pdo->prepare("SELECT * FROM producto WHERE id_emprendedor = :id_emprendedor");
    $stmt_productos->bindParam(':id_emprendedor', $id_emprendedor, PDO::PARAM_INT);
    $stmt_productos->execute();
    $productos = $stmt_productos->fetchAll();
} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil del Emprendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <style>
        :root {
            --color-primario: #006064;
            --color-secundario: #00838f;
            --color-acento: #004d40;
            --color-texto: #333;
            --color-texto-claro: #666;
            --color-fondo: #f8f9fa;
            --sombra: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transicion: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0f7fa 0%, #f8f9fa 100%);
            color: var(--color-texto);
            padding: 2rem;
        }

        .breadcrumb a {
            color: var(--color-primario);
            text-decoration: none;
        }

        .card.emprendedor-info {
            background-color: white;
            box-shadow: var(--sombra);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--transicion);
        }

        .card.emprendedor-info:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 96, 100, 0.2);
        }

        .emprendedor-info h2 {
            color: var(--color-primario);
            font-weight: bold;
        }

        .productos-container h3 {
            color: var(--color-acento);
            margin-bottom: 1.5rem;
        }

        .card.producto {
            border: none;
            border-radius: 12px;
            box-shadow: var(--sombra);
            transition: var(--transicion);
        }

        .card.producto:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 131, 143, 0.2);
        }

        .card-img-top {
            height: 220px;
            object-fit: cover;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .btn-ver-detalles {
            background-color: var(--color-primario);
            color: white;
            border-radius: 8px;
            transition: var(--transicion);
        }

        .btn-ver-detalles:hover {
            background-color: var(--color-secundario);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 96, 100, 0.3);
        }

        @media (max-width: 768px) {
            .card-img-top {
                height: 180px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="ver_productos.php">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Perfil del Emprendedor</li>
        </ol>
    </nav>

    <!-- Perfil del emprendedor -->
    <div class="card emprendedor-info">
        <div class="card-body">
            <h2><?= htmlspecialchars($emprendedor['nombre_usuario']) ?></h2>
            <p><strong>Descripción:</strong> <br><?= $emprendedor['descripcion_emprendedor'] ? htmlspecialchars($emprendedor['descripcion_emprendedor']) : 'No disponible' ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($emprendedor['email_usuario']) ?></p>
        </div>
    </div>

    <!-- Productos -->
    <div class="productos-container">
        <h3>Productos del Emprendedor</h3>
        <div class="row g-4">
            <?php if (count($productos) > 0): ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="col-md-4">
                        <div class="card producto h-100">
                            <img src="<?= htmlspecialchars($producto['imagen_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($producto['nombre_producto']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($producto['nombre_producto']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($producto['descripcion_producto']) ?></p>
                                <p><strong>Precio:</strong> S/ <?= number_format($producto['precio_producto'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Este emprendedor aún no ha registrado productos.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
