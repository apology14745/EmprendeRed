<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $pass = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT u.*, 
                              CASE 
                                WHEN u.tipo_usuario = 'cliente' THEN c.direccion_envio
                                WHEN u.tipo_usuario = 'emprendedor' THEN e.ubicacion
                                ELSE NULL
                              END AS info_adicional
                              FROM usuario u
                              LEFT JOIN cliente c ON u.id_usuario = c.id_cliente
                              LEFT JOIN emprendedor e ON u.id_usuario = e.id_emprendedor
                              WHERE u.correo_usuario = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Verificar si el usuario está bloqueado
            if ($usuario['bloqueado'] == 1) {
                $error = "Tu cuenta ha sido bloqueada. Contacta al administrador.";
            } elseif (password_verify($pass, $usuario['contraseña'])) {
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
                
                // Redirección según tipo de usuario
                switch ($usuario['tipo_usuario']) {
                    case 'cliente':
                        header("Location: dashboard_cliente.php");
                        break;
                    case 'emprendedor':
                        header("Location: dashboard_emprendedor.php");
                        break;
                    case 'administrador':
                        header("Location: admin/dashboard.php");
                        break;
                    default:
                        $error = "Tipo de usuario no válido.";
                }
                exit();
            } else {
                $error = "Correo o contraseña incorrectos.";
            }
        } else {
            $error = "Correo o contraseña incorrectos.";
        }
    } catch (PDOException $e) {
        $error = "Error en la base de datos: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - EmprendeRed</title>
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
            background: linear-gradient(to right, #f3f4f6, #e0e7ff);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .contenedor {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .logo {
            height: 80px;
            margin-bottom: 1.5rem;
        }

        h1 {
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .campo {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s;
        }

        .campo:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        .campo img {
            width: 24px;
            height: 24px;
            margin-right: 0.75rem;
            opacity: 0.7;
        }

        .campo input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 1rem;
        }

        button {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: var(--primary-dark);
        }

        .register-link {
            margin-top: 1.5rem;
            color: var(--dark);
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: var(--danger);
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <img src="imágenes/logo-unsaac.png" alt="Logo UNSAAC" class="logo">
        <h1>Iniciar sesión</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="campo">
                <img src="imágenes/icono-correo.png" alt="Correo">
                <input type="email" name="correo" placeholder="Correo" required>
            </div>
            <div class="campo">
                <img src="imágenes/icono-usuario.png" alt="Contraseña">
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            <button type="submit">Iniciar sesión</button>
        </form>
        <p class="register-link">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>