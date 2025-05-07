<?php
session_start();
require 'includes/db.php'; // Conexión con PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_venta = $_POST['id_venta'];
    $descripcion = $_POST['descripcion'];
    $imagen_url = null;

    if (!empty($_FILES['imagen']['name'])) {
        $target_dir = "assets/reportes/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $nombre_archivo = uniqid() . "_" . basename($_FILES["imagen"]["name"]);
        $target_file = $target_dir . $nombre_archivo;

        $check = getimagesize($_FILES["imagen"]["tmp_name"]);
        if ($check !== false && move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
            $imagen_url = $target_file;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO reporte (id_venta, descripcion, imagen_url) VALUES (?, ?, ?)");
    $stmt->execute([$id_venta, $descripcion, $imagen_url]);

    $_SESSION['mensaje'] = "Reporte enviado correctamente.";
    header("Location: dashboard_emprendedor.php");
    exit();
}
?>
