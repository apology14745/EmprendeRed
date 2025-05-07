<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'emprendedor') {
    header("Location: index.php");
    exit();
}

$id_emprendedor = $_SESSION['id_usuario'];
$mensaje = "";
$clase_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $categoria = trim($_POST['categoria']);

    // Validación básica
    if (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($categoria)) {
        $mensaje = "❌ Por favor completa todos los campos correctamente.";
        $clase_mensaje = "error";
    } 
    // Subida de imagen
    elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $nombreArchivo = $_FILES['imagen']['name'];
        $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensiones_permitidas)) {
            $mensaje = "❌ Formato de imagen no válido. Usa JPG, PNG o WEBP.";
            $clase_mensaje = "error";
        } else {
            // Crear directorio si no existe
            $carpetaDestino = 'uploads/productos/';
            if (!file_exists($carpetaDestino)) {
                mkdir($carpetaDestino, 0777, true);
            }
            
            // Generar nombre único para la imagen
            $nombreUnico = uniqid('prod_', true) . '.' . $extension;
            $rutaFinal = $carpetaDestino . $nombreUnico;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaFinal)) {
                $stmt = $pdo->prepare("INSERT INTO producto 
                    (nombre_producto, descripcion_producto, precio_producto, imagen_url, categoria, fecha_publicacion, id_emprendedor) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                
                if ($stmt->execute([$nombre, $descripcion, $precio, $rutaFinal, $categoria, $id_emprendedor])) {
                    // Redirigir al dashboard después de éxito
                    $_SESSION['mensaje_exito'] = "✅ Producto agregado exitosamente.";
                    header("Location: dashboard_emprendedor.php");
                    exit();
                } else {
                    $mensaje = "❌ Error al guardar el producto en la base de datos.";
                    $clase_mensaje = "error";
                }
            } else {
                $mensaje = "❌ Error al subir la imagen.";
                $clase_mensaje = "error";
            }
        }
    } else {
        $mensaje = "❌ Por favor selecciona una imagen válida.";
        $clase_mensaje = "error";
    }
}
?>

<?php include 'includes/header.php';
 ?>

<style>
    :root {
        --color-primario: #006064;
        --color-secundario: #00838f;
        --color-exito: #4caf50;
        --color-error: #f44336;
        --color-fondo: #f5f7fa;
        --color-texto: #333;
        --color-borde: #e0e0e0;
        --sombra: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--color-fondo);
        color: var(--color-texto);
        line-height: 1.6;
    }
    
    .form-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 2rem;
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: var(--sombra);
        width: 100%;
        max-width: 600px;
        padding: 2.5rem;
        transition: transform 0.3s ease;
    }
    
    .form-card:hover {
        transform: translateY(-5px);
    }
    
    .form-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .form-header h2 {
        color: var(--color-primario);
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .form-header p {
        color: #666;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--color-primario);
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--color-borde);
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--color-primario);
        box-shadow: 0 0 0 3px rgba(0, 96, 100, 0.1);
    }
    
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }
    
    .btn {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background-color: var(--color-primario);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.3s;
        width: 100%;
    }
    
    .btn:hover {
        background-color: var(--color-secundario);
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    .exito {
        background-color: rgba(76, 175, 80, 0.1);
        color: var(--color-exito);
        border: 1px solid var(--color-exito);
    }
    
    .error {
        background-color: rgba(244, 67, 54, 0.1);
        color: var(--color-error);
        border: 1px solid var(--color-error);
    }
    
    .file-upload {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .file-upload-input {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    
    .file-upload-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        border: 2px dashed var(--color-borde);
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .file-upload-label:hover {
        border-color: var(--color-primario);
        background-color: rgba(0, 96, 100, 0.05);
    }
    
    .file-upload-icon {
        font-size: 2rem;
        color: var(--color-primario);
        margin-bottom: 0.5rem;
    }
    
    .file-upload-text {
        color: #666;
    }
    
    .file-name {
        margin-top: 0.5rem;
        font-size: 0.9rem;
        color: var(--color-primario);
        font-weight: 500;
    }
    
    .preview-container {
        margin-top: 1rem;
        text-align: center;
    }
    
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        border: 1px solid var(--color-borde);
        display: none;
    }
    
    @media (max-width: 768px) {
        .form-card {
            padding: 1.5rem;
        }
    }
<head>

</style>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Agregar Nuevo Producto</h2>
            <p>Completa los detalles de tu producto para publicarlo</p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert <?= $clase_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="productoForm">
            <div class="form-group">
                <label for="nombre" class="form-label">Nombre del Producto</label>
                <input type="text" id="nombre" name="nombre" class="form-control" 
                       value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="4" required><?= isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '' ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="precio" class="form-label">Precio (S/)</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0.01" 
                       value="<?= isset($_POST['precio']) ? htmlspecialchars($_POST['precio']) : '' ?>" 
                       class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="categoria" class="form-label">Categoría</label>
                <input type="text" id="categoria" name="categoria" 
                       value="<?= isset($_POST['categoria']) ? htmlspecialchars($_POST['categoria']) : '' ?>" 
                       class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Imagen del Producto</label>
                <div class="file-upload">
                    <input type="file" id="imagen" name="imagen" class="file-upload-input" accept="image/*" required>
                    <label for="imagen" class="file-upload-label" id="fileUploadLabel">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="file-upload-text">Haz clic para subir una imagen o arrástrala aquí</div>
                        <div class="file-name" id="fileName"></div>
                    </label>
                </div>
                <div class="preview-container">
                    <img id="previewImage" class="preview-image" alt="Vista previa de la imagen">
                </div>
            </div>
            
            <button type="submit" class="btn">Publicar Producto</button>
        </form>
    </div>
</div>

<script>
    // Mostrar nombre del archivo y vista previa
    document.getElementById('imagen').addEventListener('change', function(e) {
        const fileName = document.getElementById('fileName');
        const previewImage = document.getElementById('previewImage');
        const fileUploadLabel = document.getElementById('fileUploadLabel');
        
        if (this.files.length > 0) {
            const file = this.files[0];
            fileName.textContent = file.name;
            
            // Mostrar vista previa
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                fileUploadLabel.style.padding = '1rem';
            }
            reader.readAsDataURL(file);
        } else {
            fileName.textContent = '';
            previewImage.style.display = 'none';
            fileUploadLabel.style.padding = '2rem';
        }
    });
    
    // Validación de formulario
    document.getElementById('productoForm').addEventListener('submit', function(e) {
        const precio = parseFloat(document.getElementById('precio').value);
        if (precio <= 0) {
            alert('El precio debe ser mayor que cero');
            e.preventDefault();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>