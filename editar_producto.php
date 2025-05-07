<?php
session_start();
include 'includes/db.php';

if ($_SESSION['tipo_usuario'] !== 'emprendedor') {
    header("Location: index.php");
    exit();
}

$id_emprendedor = $_SESSION['id_usuario'];
$id_producto = $_GET['id'] ?? 0;

// Verificar que el producto pertenece al emprendedor
$stmt_verificar = $pdo->prepare("SELECT * FROM producto WHERE id_producto = ? AND id_emprendedor = ?");
$stmt_verificar->execute([$id_producto, $id_emprendedor]);
$producto = $stmt_verificar->fetch();

if (!$producto) {
    header("Location: dashboard_emprendedor.php");
    exit();
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre_producto'];
    $descripcion = $_POST['descripcion_producto'];
    $precio = $_POST['precio_producto'];
    $categoria = $_POST['categoria'];
    
    // Mantener la imagen actual por defecto
    $imagen_url = $producto['imagen_url'];

    if (!empty($_FILES['imagen']['name'])) {
        $target_dir = "uploads/productos/";
        $filename = uniqid() . "_" . basename($_FILES["imagen"]["name"]); // nombre único
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validar si es una imagen real
        $check = getimagesize($_FILES["imagen"]["tmp_name"]);
        if ($check !== false) {
            // Intentar mover la imagen subida
            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
                $imagen_url = $target_file; // ruta relativa para el <img src="">
            } else {
                $_SESSION['error'] = "Error al subir la imagen.";
                header("Location: editar_producto.php?id=$id_producto");
                exit();
            }
        } else {
            $_SESSION['error'] = "El archivo no es una imagen válida.";
            header("Location: editar_producto.php?id=$id_producto");
            exit();
        }
    }

    // Actualizar el producto
    $stmt_actualizar = $pdo->prepare("
        UPDATE producto SET 
            nombre_producto = ?, 
            descripcion_producto = ?, 
            precio_producto = ?, 
            imagen_url = ?, 
            categoria = ?
        WHERE id_producto = ? AND id_emprendedor = ?
    ");
    
    $stmt_actualizar->execute([
        $nombre,
        $descripcion,
        $precio,
        $imagen_url,
        $categoria,
        $id_producto,
        $id_emprendedor
    ]);

    $_SESSION['mensaje'] = "Producto actualizado correctamente";
    header("Location: dashboard_emprendedor.php");
    exit();
}

?>

<?php include 'includes/header.php'; ?>

<style>
    .form-container {
        max-width: 600px;
        margin: 30px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .form-group {
        margin-bottom: 15px;
    }
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    input[type="text"],
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    textarea {
        height: 100px;
    }
    .btn-submit {
        background-color: #006064;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-submit:hover {
        background-color: #00838f;
    }
    .current-image {
        max-width: 200px;
        margin: 10px 0;
    }
</style>

<div class="form-container">
    <h2>Editar Producto</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="nombre_producto">Nombre del Producto</label>
            <input type="text" id="nombre_producto" name="nombre_producto" 
                   value="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="descripcion_producto">Descripción</label>
            <textarea id="descripcion_producto" name="descripcion_producto" required><?php 
                echo htmlspecialchars($producto['descripcion_producto']); 
            ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="precio_producto">Precio (S/)</label>
            <input type="number" id="precio_producto" name="precio_producto" step="0.01" min="0"
                   value="<?php echo htmlspecialchars($producto['precio_producto']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="categoria">Categoría</label>
            <input type="text" id="categoria" name="categoria" 
                   value="<?php echo htmlspecialchars($producto['categoria']); ?>">
        </div>
        
        <div class="form-group">
            <label for="imagen">Imagen del Producto</label>
            <?php if ($producto['imagen_url']): ?>
                <p>Imagen actual:</p>
                <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" 
                     alt="Imagen actual del producto" class="current-image">
            <?php endif; ?>
            <input type="file" id="imagen" name="imagen" accept="image/*">
        </div>
        
        <button type="submit" class="btn-submit">Guardar Cambios</button>
        <a href="dashboard_emprendedor.php" class="btn btn-danger">Cancelar</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>