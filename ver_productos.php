<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Obtener parámetros de filtrado
$categoria = $_GET['categoria'] ?? null;
$busqueda = $_GET['busqueda'] ?? null;

try {
    // Construir consulta base
    $sql = "
    SELECT p.*, u.nombre_usuario,
           (SELECT AVG(v.evaluacion) FROM venta v 
            JOIN carrito c ON v.id_carrito = c.id_carrito
            JOIN carrito_producto cp ON c.id_carrito = cp.id_carrito
            WHERE cp.id_producto = p.id_producto) as promedio_valoracion
    FROM producto p
    JOIN emprendedor e ON p.id_emprendedor = e.id_emprendedor
    JOIN usuario u ON e.id_emprendedor = u.id_usuario
    WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($categoria) && $categoria !== 'todas') {
        $sql .= " AND p.categoria = ?";
        $params[] = $categoria;
    }
    
    if (!empty($busqueda)) {
        $sql .= " AND (p.nombre_producto LIKE ? OR p.descripcion_producto LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    $sql .= " ORDER BY p.fecha_publicacion DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();
    
    // Obtener categorías
    $stmt_categorias = $pdo->query("SELECT DISTINCT categoria FROM producto WHERE categoria IS NOT NULL");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
}
?> 

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="assets/logo.png" type="image/png">
    <style>
        :root {
            --color-primario: #006064;
            --color-secundario: #00838f;
            --color-fondo: #f8f9fa;
            --color-texto: #333;
            --color-borde: #e0e0e0;
            --sombra: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            padding: 30px 0;
        }
        
        .header-catalogo {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }
        
        .header-catalogo h1 {
            color: var(--color-primario);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .header-catalogo p {
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 0 20px;
        }
        
        .producto-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--sombra);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .producto-imagen-container {
            height: 220px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f5f5f5;
            position: relative;
            overflow: hidden;
        }
        
        .producto-imagen {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .producto-card:hover .producto-imagen {
            transform: scale(1.05);
        }
        
        .icono-predeterminado {
            width: 80px;
            height: 80px;
            opacity: 0.3;
        }
        
        .producto-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .producto-titulo {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--color-primario);
            margin-bottom: 10px;
        }
        
        .producto-descripcion {
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .producto-precio {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 15px;
        }
        
        .valoracion-promedio {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .estrella {
            width: 18px;
            height: 18px;
            margin-right: 2px;
        }
        
        .valoracion-texto {
            margin-left: 8px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .producto-form {
            margin-top: auto;
        }
        
        .cantidad-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .cantidad-label {
            margin-right: 10px;
            font-weight: 500;
        }
        
        .cantidad-input {
            width: 70px;
            text-align: center;
            padding: 8px;
            border: 1px solid var(--color-borde);
            border-radius: 6px;
        }
        
        .btn-comprar {
            width: 100%;
            background-color: var(--color-primario);
            border: none;
            padding: 10px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-comprar:hover {
            background-color: var(--color-secundario);
        }
        
        .footer-catalogo {
            text-align: center;
            margin-top: 50px;
            padding: 0 20px;
        }
        
        .sin-productos {
            text-align: center;
            padding: 40px;
            grid-column: 1 / -1;
        }
        
        @media (max-width: 768px) {
            .productos-grid {
                grid-template-columns: 1fr;
            }
            
            .header-catalogo h1 {
                font-size: 1.8rem;
            }
        }
        /* Estilos para filtros - manteniendo tu paleta */
.input-group {
    box-shadow: var(--sombra);
    border-radius: 6px;
    overflow: hidden;
}

.form-control, .form-select {
    border: 1px solid var(--color-borde);
    padding: 10px 15px;
    transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
    border-color: var(--color-secundario);
    box-shadow: 0 0 0 0.2rem rgba(0, 131, 143, 0.25);
}

.btn-primary {
    background-color: var(--color-primario);
    border: none;
    padding: 10px 15px;
}

.btn-primary:hover {
    background-color: var(--color-secundario);
}

.badge {
    background-color: var(--color-primario);
    font-weight: 500;
    padding: 5px 10px;
    border-radius: 4px;
}

.text-secondary {
    color: var(--color-secundario);
}

/* Ajustes para responsive */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 15px;
    }
}
        /* Estilos para los filtros */
.filter-section {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: var(--sombra);
    margin-bottom: 30px;
}

.badge {
    font-weight: 500;
    padding: 5px 10px;
}

.input-group-text {
    background-color: var(--color-primario);
    color: white;
    border: none;
}

.form-select, .form-control {
    border: 1px solid var(--color-borde);
    padding: 10px 15px;
}

.form-select:focus, .form-control:focus {
    border-color: var(--color-primario);
    box-shadow: 0 0 0 0.25rem rgba(0, 96, 100, 0.25);
}

.btn-filter {
    background-color: var(--color-primario);
    color: white;
    border: none;
    transition: background-color 0.3s;
}

.btn-filter:hover {
    background-color: var(--color-secundario);

    </style>
</head>
<body>
    <div class="header-catalogo">
        <h1>Descubre Nuestros Productos</h1>
        <p>Explora nuestra selección de productos de emprendedores locales y encuentra lo que necesitas.</p>
    </div>

    <!-- Sección de Filtros -->
    <div class="container mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="busqueda" class="form-label mb-1">Buscar productos</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           placeholder="Nombre o descripción..." value="<?= htmlspecialchars($busqueda ?? '') ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-6">
                <label for="categoria" class="form-label mb-1">Categoría</label>
                <div class="input-group">
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="todas" <?= empty($categoria) || $categoria === 'todas' ? 'selected' : '' ?>>Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                <?= $categoria === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($cat)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Filtros activos -->
        <?php if (!empty($busqueda) || (!empty($categoria) && $categoria !== 'todas')): ?>
        <div class="mt-3">
            <p class="mb-1 text-muted">
                Filtros aplicados:
                <?php if (!empty($busqueda)): ?>
                    <span class="badge bg-primary me-1">"<?= htmlspecialchars($busqueda) ?>"</span>
                <?php endif; ?>
                <?php if (!empty($categoria) && $categoria !== 'todas'): ?>
                    <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($categoria)) ?></span>
                <?php endif; ?>
                <a href="ver_productos.php" class="text-secondary ms-2">
                    <small><i class="fas fa-times"></i> Limpiar</small>
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
    </div>
        <div class="container">
        <?php if ($productos && (count($productos) > 0)): ?>
            <p class="text-muted mb-3">
                Mostrando <?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?>
                <?php if (!empty($busqueda) || (!empty($categoria) && $categoria !== 'todas')): ?>
                    que coinciden con tus filtros
                <?php endif; ?>
            </p>
            
            <div class="productos-grid">
                <!-- El resto de tu código del grid de productos -->
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x mb-3" style="color: #ccc;"></i>
                <h3>No se encontraron productos</h3>
                <p class="text-muted">
                    <?php if (!empty($busqueda) || (!empty($categoria) && $categoria !== 'todas')): ?>
                        No hay productos que coincidan con tus criterios de búsqueda.
                        <a href="ver_productos.php" class="text-primary">Mostrar todos los productos</a>
                    <?php else: ?>
                        No hay productos disponibles en este momento.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    <div class="container">
        <div class="productos-grid">
            <?php if ($productos): ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="producto-card">
                        <div class="producto-imagen-container">
                            <?php if (!empty($producto['imagen_url']) && file_exists($producto['imagen_url'])): ?>
                                <img src="<?= htmlspecialchars($producto['imagen_url']) ?>" 
                                     class="producto-imagen" 
                                     alt="<?= htmlspecialchars($producto['nombre_producto']) ?>">
                            <?php else: ?>
                                <img src="assets/no-image.png" 
                                     class="icono-predeterminado" 
                                     alt="Imagen no disponible">
                            <?php endif; ?>

                        </div>
                        
                        <div class="producto-info">
                            <h3 class="producto-titulo"><?= htmlspecialchars($producto['nombre_producto']) ?></h3>
                            <p class="producto-descripcion"><?= htmlspecialchars($producto['descripcion_producto']) ?></p>
                            
                            <?php if ($producto['promedio_valoracion']): ?>
                                <div class="valoracion-promedio">
                                    <?php 
                                    $valoracion_redondeada = round($producto['promedio_valoracion']);
                                    for ($i = 1; $i <= 5; $i++): ?>
                                        <img src="imágenes/<?= ($i <= $valoracion_redondeada) ? 'estrella_amarilla.png' : 'estrella.png' ?>" 
                                             class="estrella" 
                                             alt="Estrella">
                                    <?php endfor; ?>
                                    <span class="valoracion-texto">(<?= number_format($producto['promedio_valoracion'], 1) ?>)</span>
                                </div>
                            <?php else: ?>
                                <div class="valoracion-promedio">
                                    <span class="valoracion-texto">Sin valoraciones aún</span>
                                </div>
                            <?php endif; ?>
                            
                            <p class="producto-precio">S/ <?= number_format($producto['precio_producto'], 2) ?></p>
                                <p class="producto-emprendedor">
                                    Publicado por: 
                                    <a href="perfil_emprendedor.php?id=<?= $producto['id_emprendedor'] ?>">
                                        <?= htmlspecialchars($producto['nombre_usuario']) ?>
                                    </a>
                                </p>

                            <form method="POST" action="comprar.php" class="producto-form">
                                <input type="hidden" name="id_producto" value="<?= htmlspecialchars($producto['id_producto']) ?>">
                                
                                <div class="cantidad-container">
                                    <span class="cantidad-label">Cantidad:</span>
                                    <input type="number" 
                                           name="cantidad" 
                                           value="1" 
                                           min="1" 
                                           max="20" 
                                           class="cantidad-input"
                                           required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-comprar">
                                    <i class="fas fa-cart-plus"></i> Añadir al carrito
                                </button>

                                <a href="probar_virtualmente.php?id_producto=<?= $producto['id_producto'] ?>" class="btn btn-outline-secondary mt-2">
    <i class="fas fa-search"></i> Probar virtualmente
</a>

                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="sin-productos alert alert-warning">
                    No hay productos disponibles en este momento. Por favor, revisa más tarde.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer-catalogo">
        <a href="logout.php" class="btn btn-outline-danger">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </div>

    <script>
        // Mejorar la experiencia del input de cantidad
        document.querySelectorAll('.cantidad-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) this.value = 1;
                if (this.value > 20) this.value = 20;
            });
            
            input.addEventListener('keydown', function(e) {
                // Prevenir entrada de caracteres no numéricos
                if (['e', 'E', '+', '-'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        });
    </script>

</body>
</html>

<script>
    // Mejorar la experiencia de filtrado
    document.getElementById('categoria').addEventListener('change', function() {
        // Auto-submit al cambiar categoría si hay resultados
        if (<?= !empty($productos) && count($productos) > 0 ? 'true' : 'false' ?>) {
            this.form.submit();
        }
    });
    
    // Limpiar búsqueda
    document.querySelectorAll('.clear-search').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('busqueda').value = '';
            this.form.submit();
        });
    });
</script>