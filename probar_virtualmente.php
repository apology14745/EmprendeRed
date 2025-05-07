<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id_producto'])) {
    header("Location: ver_productos.php");
    exit();
}

$product_id = $_GET['id_producto'];
$user_id = $_SESSION['id_usuario'];

// Obtener información del producto
$stmt = $pdo->prepare("
    SELECT p.*, u.nombre_usuario 
    FROM producto p
    JOIN emprendedor e ON p.id_emprendedor = e.id_emprendedor
    JOIN usuario u ON e.id_emprendedor = u.id_usuario
    WHERE p.id_producto = ?
");
$stmt->execute([$product_id]);
$producto = $stmt->fetch();

if (!$producto) {
    header("Location: ver_productos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Probar Virtualmente - <?= htmlspecialchars($producto['nombre_producto']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <style>
        .preview-container {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            flex-wrap: wrap;
            gap: 20px;
        }
        .image-box {
            border: 2px dashed #006064;
            width: 100%;
            max-width: 400px;
            height: 400px;
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        .image-box h5 {
            background-color: #006064;
            color: white;
            padding: 10px;
            margin: 0;
            text-align: center;
        }
        .image-box img {
            max-width: 100%;
            max-height: 100%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        #resultCanvas {
            border: 2px solid #006064;
        }
        .instructions {
            background-color: #e0f7fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid #006064;
        }
        .btn-try {
            background-color: #006064;
            color: white;
            padding: 10px 20px;
            font-size: 1.1rem;
        }
        .btn-try:hover {
            background-color: #00838f;
        }
        #loadingSpinner {
            display: none;
        }
        #resultSection {
            display: none;
            text-align: center;
            margin-top: 30px;
        }
        #finalResult {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="text-center mb-4">Probar Virtualmente: <?= htmlspecialchars($producto['nombre_producto']) ?></h1>
    
    <div class="instructions">
        <h3><i class="fas fa-info-circle"></i> ¿Cómo funciona?</h3>
        <ol>
            <li><strong>Sube una foto tuya</strong> donde quieras probar el producto</li>
            <li><strong>Describe la posición</strong> (ej: "en mi cabeza", "sobre mi escritorio")</li>
            <li><strong>Nuestra IA generará</strong> una imagen con el producto superpuesto</li>
        </ol>
    </div>

    <form id="virtualTryForm" enctype="multipart/form-data" class="mb-4">
        <input type="hidden" name="product_id" value="<?= $product_id ?>">
        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <label for="userPhoto" class="form-label">
                    <i class="fas fa-camera"></i> Sube tu foto:
                </label>
                <input class="form-control" type="file" id="userPhoto" name="userPhoto" accept="image/*" required>
                <div class="form-text">Formatos aceptados: JPG, PNG (Máx. 5MB)</div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="placementText" class="form-label">
                    <i class="fas fa-map-marker-alt"></i> ¿Dónde quieres colocar el producto?
                </label>
                <input type="text" class="form-control" id="placementText" name="placementText" 
                       placeholder="Ej: 'en mi cabeza', 'sobre mi escritorio'" required>
                <div class="form-text">Describe la posición exacta donde quieres ver el producto</div>
            </div>
        </div>
        
        <div class="text-center">
            <button type="submit" class="btn btn-try">
                <i class="fas fa-magic"></i> Generar Prueba Virtual
            </button>
        </div>
    </form>
    
    <div class="preview-container">
        <div class="image-box">
            <h5>Tu foto</h5>
            <img id="userPhotoPreview" src="#" alt="Tu foto" style="display: none;">
            <div id="noUserPhoto" style="text-align: center; margin-top: 50%;">
                <i class="fas fa-user-circle" style="font-size: 50px; color: #ccc;"></i>
                <p>Tu foto aparecerá aquí</p>
            </div>
        </div>
        
        <div class="image-box">
            <h5>Producto</h5>
            <img id="productImage" src="<?= htmlspecialchars($producto['imagen_url']) ?>" 
                 alt="<?= htmlspecialchars($producto['nombre_producto']) ?>">
        </div>
        
        <div class="image-box">
            <h5>Resultado</h5>
            <canvas id="resultCanvas" width="400" height="400"></canvas>
            <div id="noResult" style="text-align: center; margin-top: 50%;">
                <i class="fas fa-image" style="font-size: 50px; color: #ccc;"></i>
                <p>El resultado aparecerá aquí</p>
            </div>
        </div>
    </div>
    
    <div id="loadingSpinner" class="text-center my-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Procesando...</span>
        </div>
        <h4 class="mt-3">Generando imagen con IA...</h4>
        <p>Esto puede tomar unos segundos</p>
    </div>
    
    <div id="resultSection" class="mt-4">
        <h3 class="mb-4">¡Listo! Aquí está tu prueba virtual</h3>
        <img id="finalResult" src='assets/prueba.png' alt="Resultado final" class="img-fluid">
        <div class="mt-4">
            <button id="downloadBtn" class="btn btn-success me-3">
                <i class="fas fa-download"></i> Descargar Imagen
            </button>
            <button id="tryAgainBtn" class="btn btn-outline-primary">
                <i class="fas fa-redo"></i> Intentar con otra foto
            </button>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <a href="ver_productos.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al catálogo
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Mostrar previsualización de la foto subida
    document.getElementById('userPhoto').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo es demasiado grande. Máximo 5MB permitido.');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('userPhotoPreview').src = event.target.result;
                document.getElementById('userPhotoPreview').style.display = 'block';
                document.getElementById('noUserPhoto').style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    });

    // Manejar el envío del formulario
    document.getElementById('virtualTryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Mostrar un mensaje o spinner que indique que estamos esperando
    document.getElementById('loadingSpinner').style.display = 'block';
    document.getElementById('resultSection').style.display = 'none';
    
    // Esperar 10 segundos antes de proceder con el envío del formulario
    setTimeout(function() {
        const formData = new FormData();
        const userPhoto = document.getElementById('userPhoto').files[0];
        
        if (!userPhoto) {
            alert('Por favor sube una foto');
            return;
        }
        
        formData.append('userPhoto', userPhoto);
        formData.append('product_id', document.querySelector('input[name="product_id"]').value);
        formData.append('placementText', document.getElementById('placementText').value);
        formData.append('productImageUrl', document.getElementById('productImage').src);
        
        // Enviar a backend para procesamiento con IA
        axios.post('api/virtual_try.php', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then(function(response) {
            // Ocultar spinner
            document.getElementById('loadingSpinner').style.display = 'none';
            
            if (response.data.success) {
                // Mostrar resultado
                document.getElementById('finalResult').src = 'assets/prueba.png';
                document.getElementById('resultSection').style.display = 'block';
                
                // Configurar botón de descarga
                document.getElementById('downloadBtn').onclick = function() {
                    const link = document.createElement('a');
                    link.href = 'assets/prueba.png'; // La misma imagen de demostración
                    link.download = 'prueba_virtual_demo_' + Date.now() + '.jpg';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                };

                
                // Configurar botón de intentar de nuevo
                document.getElementById('tryAgainBtn').onclick = function() {
                    document.getElementById('userPhoto').value = '';
                    document.getElementById('placementText').value = '';
                    document.getElementById('userPhotoPreview').style.display = 'none';
                    document.getElementById('noUserPhoto').style.display = 'block';
                    document.getElementById('resultSection').style.display = 'none';
                };
            } else {
                alert('Error: ' + (response.data.message || 'No se pudo generar la imagen'));
            }
        })
        .catch(function(error) {
            document.getElementById('loadingSpinner').style.display = 'none';
            alert('Ocurrió un error al procesar tu solicitud. Por favor intenta nuevamente.');
            console.error(error);
        });
    }, 15000); // 10000 milisegundos = 10 segundos
});

</script>


<script>
        // Seleccionar los elementos
        const boton = document.getElementById("redirigirBtn");
        const mensaje = document.getElementById("message");

        // Función para manejar el clic y la redirección con retraso
        boton.addEventListener("click", () => {
            // Mostrar mensaje inicial
            mensaje.textContent = "Redirigiendo... Espera unos segundos.";

            // Temporizador para esperar 3 segundos
            setTimeout(() => {
                // Redirigir a la otra página
                window.location.href = "https://www.nueva-pagina.com"; // Reemplaza con la URL de destino
            }, 3000); // 3000 milisegundos = 3 segundos
        });
</script>



</body>
</html>