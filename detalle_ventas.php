<?php
session_start();
require_once 'includes/db.php';

// Verificar si es administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 'administrador') {
    header("Location: login.php");
    exit;
}

// Verificar que se pasó un ID
if (!isset($_GET['id'])) {
    echo "ID de venta no proporcionado.";
    exit;
}

$id_venta = $_GET['id'];

// Obtener los reportes relacionados a la venta
$stmt = $pdo->prepare("
    SELECT descripcion, imagen_url, fecha_reporte
    FROM reporte
    WHERE id_venta = ?
    ORDER BY fecha_reporte DESC
");
$stmt->execute([$id_venta]);
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detalle de Venta - Reportes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Historial de Reportes - Venta #<?php echo htmlspecialchars($id_venta); ?></h2>
    <a href="admin/ventas.php" class="btn btn-secondary mb-3">← Volver a ventas</a>

    <?php if (count($reportes) === 0): ?>
        <div class="alert alert-info">No hay reportes para esta venta.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($reportes as $reporte): ?>
                <div class="list-group-item">
                    <h5 class="mb-1">Fecha: <?php echo htmlspecialchars($reporte['fecha_reporte']); ?></h5>
                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($reporte['descripcion'])); ?></p>
                    <?php if (!empty($reporte['imagen_url'])): ?>
                        <img src="<?php echo htmlspecialchars($reporte['imagen_url']); ?>" 
                             alt="Imagen del reporte" class="img-fluid mt-2" style="max-width: 400px;">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
