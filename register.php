<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$mensaje = "";
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $nombre = trim($_POST['nombre_usuario']);
    $correo = filter_var(trim($_POST['correo_usuario']), FILTER_SANITIZE_EMAIL);
    $pass = $_POST['contraseña'];
    $confirm_pass = $_POST['confirmar_contraseña'];
    $telefono = trim($_POST['telefono']);
    $tipo = $_POST['tipo_usuario'];

    // Validaciones
    if (empty($nombre)) {
        $errores[] = "El nombre es requerido";
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    } else {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE correo_usuario = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $errores[] = "Este correo electrónico ya está registrado";
        }
    }

    if (strlen($pass) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    }

    if ($pass !== $confirm_pass) {
        $errores[] = "Las contraseñas no coinciden";
    }

    if (empty($telefono)) {
        $errores[] = "El teléfono es requerido";
    }

    if (empty($tipo)) {
        $errores[] = "Debes seleccionar un tipo de usuario";
    }

    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO usuario (nombre_usuario, correo_usuario, contraseña, telefono, tipo_usuario) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $correo, $pass_hash, $telefono, $tipo])) {
                $id_usuario = $pdo->lastInsertId();

                if ($tipo === 'cliente') {
                    $direccion = trim($_POST['direccion_envio']);
                    if (empty($direccion)) {
                        $errores[] = "La dirección de envío es requerida";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO cliente (id_cliente, direccion_envio) VALUES (?, ?)");
                        $stmt->execute([$id_usuario, $direccion]);
                    }
                } elseif ($tipo === 'emprendedor') {
                    $ubicacion = trim($_POST['ubicacion']);
                    if (empty($ubicacion)) {
                        $errores[] = "La ubicación es requerida";
                    } else {
                        $fecha = date('Y-m-d');
                        $stmt = $pdo->prepare("INSERT INTO emprendedor (id_emprendedor, ubicacion, fecha_registro) VALUES (?, ?, ?)");
                        $stmt->execute([$id_usuario, $ubicacion, $fecha]);
                    }
                }
                
                if (empty($errores)) {
                    $pdo->commit();
                    $_SESSION['registro_exitoso'] = true;
                    header('Location: login.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
    
    if (!empty($errores)) {
        $mensaje = implode("<br>", $errores);
    }
}
?>

<?php include 'includes/header.php'; ?>

<style>
    :root {
        --color-primario: #006064;
        --color-secundario: #00838f;
        --color-acento: #004d40;
        --color-error: #dc3545;
        --color-exito: #28a745;
        --color-fondo: #f8f9fa;
        --color-texto: #333;
        --color-texto-claro: #666;
        --sombra: 0 10px 30px rgba(0, 0, 0, 0.1);
        --transicion: all 0.3s ease;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--color-fondo);
        color: var(--color-texto);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .registro-container {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-grow: 1;
        padding: 2rem;
    }
    
    .registro-card {
        background: white;
        border-radius: 12px;
        box-shadow: var(--sombra);
        width: 100%;
        max-width: 600px;
        padding: 2.5rem;
        transition: var(--transicion);
    }
    
    .registro-card:hover {
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    .registro-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .registro-header h2 {
        color: var(--color-primario);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .registro-header p {
        color: var(--color-texto-claro);
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
        border: 1px solid #ced4da;
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transicion);
    }
    
    .form-control:focus {
        border-color: var(--color-primario);
        box-shadow: 0 0 0 3px rgba(0, 96, 100, 0.1);
        outline: none;
    }
    
    .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #ced4da;
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transicion);
    }
    
    .form-select:focus {
        border-color: var(--color-primario);
        box-shadow: 0 0 0 3px rgba(0, 96, 100, 0.1);
        outline: none;
    }
    
    .btn-registro {
        width: 100%;
        padding: 0.9rem;
        background-color: var(--color-primario);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transicion);
    }
    
    .btn-registro:hover {
        background-color: var(--color-secundario);
        transform: translateY(-2px);
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .alert-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: var(--color-error);
        border: 1px solid var(--color-error);
    }
    
    .campo-adicional {
        animation: fadeIn 0.5s ease;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px dashed #ddd;
    }
    
    .password-strength {
        margin-top: 0.5rem;
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .strength-bar {
        height: 100%;
        width: 0%;
        transition: width 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (max-width: 768px) {
        .registro-card {
            padding: 1.5rem;
        }
    }
</style>

<div class="registro-container">
    <div class="registro-card">
        <div class="registro-header">
            <h2>Únete a EmprendeRed</h2>
            <p>Crea tu cuenta y comienza a disfrutar de nuestros servicios</p>
        </div>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-danger"><?= $mensaje ?></div>
        <?php endif; ?>
        
        <form method="POST" id="registroForm">
            <div class="form-group">
                <label for="nombre_usuario" class="form-label">Nombre completo</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-control" 
                       value="<?= isset($_POST['nombre_usuario']) ? htmlspecialchars($_POST['nombre_usuario']) : '' ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="correo_usuario" class="form-label">Correo electrónico</label>
                <input type="email" id="correo_usuario" name="correo_usuario" class="form-control" 
                       value="<?= isset($_POST['correo_usuario']) ? htmlspecialchars($_POST['correo_usuario']) : '' ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" class="form-control" 
                       value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="contraseña" class="form-label">Contraseña</label>
                <input type="password" id="contraseña" name="contraseña" class="form-control" required>
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <small class="text-muted">Mínimo 8 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirmar_contraseña" class="form-label">Confirmar contraseña</label>
                <input type="password" id="confirmar_contraseña" name="confirmar_contraseña" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="tipo_usuario" class="form-label">Tipo de usuario</label>
                <select id="tipo_usuario" name="tipo_usuario" class="form-select" required>
                    <option value="">Seleccione una opción</option>
                    <option value="cliente" <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'cliente') ? 'selected' : '' ?>>Cliente</option>
                    <option value="emprendedor" <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'emprendedor') ? 'selected' : '' ?>>Emprendedor</option>
                </select>
            </div>
            
            <div id="campos_cliente" class="campo-adicional" style="<?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'cliente') ? '' : 'display: none;' ?>">
                <div class="form-group">
                    <label for="direccion_envio" class="form-label">Dirección de envío</label>
                    <textarea id="direccion_envio" name="direccion_envio" class="form-control" rows="3"><?= isset($_POST['direccion_envio']) ? htmlspecialchars($_POST['direccion_envio']) : '' ?></textarea>
                </div>
            </div>
            
            <div id="campos_emprendedor" class="campo-adicional" style="<?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'emprendedor') ? '' : 'display: none;' ?>">
                <div class="form-group">
                    <label for="ubicacion" class="form-label">Ubicación de tu negocio</label>
                    <input type="text" id="ubicacion" name="ubicacion" class="form-control" 
                           value="<?= isset($_POST['ubicacion']) ? htmlspecialchars($_POST['ubicacion']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 2rem;">
                <button type="submit" class="btn btn-registro">Crear cuenta</button>
            </div>
            
            <div class="text-center" style="margin-top: 1.5rem;">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </form>
    </div>
</div>

<script>
    // Mostrar campos adicionales según tipo de usuario
    document.getElementById('tipo_usuario').addEventListener('change', function() {
        const tipo = this.value;
        document.getElementById('campos_cliente').style.display = (tipo === 'cliente') ? 'block' : 'none';
        document.getElementById('campos_emprendedor').style.display = (tipo === 'emprendedor') ? 'block' : 'none';
    });
    
    // Validación de fortaleza de contraseña
    document.getElementById('contraseña').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('strengthBar');
        let strength = 0;
        
        if (password.length >= 8) strength += 1;
        if (password.match(/[a-z]/)) strength += 1;
        if (password.match(/[A-Z]/)) strength += 1;
        if (password.match(/[0-9]/)) strength += 1;
        if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
        
        // Actualizar barra de fortaleza
        const width = (strength / 5) * 100;
        strengthBar.style.width = width + '%';
        
        // Cambiar color según fortaleza
        if (strength <= 2) {
            strengthBar.style.backgroundColor = '#dc3545'; // Rojo
        } else if (strength <= 4) {
            strengthBar.style.backgroundColor = '#ffc107'; // Amarillo
        } else {
            strengthBar.style.backgroundColor = '#28a745'; // Verde
        }
    });
    
    // Validación de confirmación de contraseña
    document.getElementById('registroForm').addEventListener('submit', function(e) {
        const password = document.getElementById('contraseña').value;
        const confirmPassword = document.getElementById('confirmar_contraseña').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 8 caracteres');
            return false;
        }
        
        const tipoUsuario = document.getElementById('tipo_usuario').value;
        if (tipoUsuario === 'cliente') {
            const direccion = document.getElementById('direccion_envio').value.trim();
            if (direccion === '') {
                e.preventDefault();
                alert('Por favor ingresa tu dirección de envío');
                return false;
            }
        } else if (tipoUsuario === 'emprendedor') {
            const ubicacion = document.getElementById('ubicacion').value.trim();
            if (ubicacion === '') {
                e.preventDefault();
                alert('Por favor ingresa la ubicación de tu negocio');
                return false;
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>