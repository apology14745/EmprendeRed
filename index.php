<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EmprendeRed - Conectando emprendedores y clientes</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0f7fa 0%, #f8f9fa 100%);
            color: var(--color-texto);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            line-height: 1.6;
        }
        
        .hero-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .hero-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 2rem;
        }
        
        .hero-text {
            flex: 1;
            min-width: 300px;
            text-align: left;
            padding: 2rem;
            animation: fadeInLeft 0.8s ease;
        }
        
        .hero-image {
            flex: 1;
            min-width: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInRight 0.8s ease;
        }
        
        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: var(--sombra);
        }
        
        .welcome-title {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .highlight {
            color: var(--color-acento);
            position: relative;
            display: inline-block;
        }
        
        .highlight::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 8px;
            background-color: rgba(0, 150, 136, 0.3);
            z-index: -1;
            border-radius: 4px;
        }
        
        .intro-text {
            font-size: 1.1rem;
            color: var(--color-texto-claro);
            margin-bottom: 2rem;
            max-width: 600px;
        }
        
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            flex: 1 1 200px;
        }
        
        .feature-icon {
            color: var(--color-primario);
            font-size: 1.4rem;
            margin-top: 3px;
        }
        
        .feature-text {
            font-size: 0.95rem;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.9rem 2rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: var(--transicion);
            box-shadow: 0 4px 12px rgba(0, 96, 100, 0.2);
        }
        
        .btn-login {
            background-color: var(--color-primario);
            color: white;
        }
        
        .btn-login:hover {
            background-color: var(--color-secundario);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 96, 100, 0.3);
        }
        
        .btn-register {
            background-color: white;
            color: var(--color-primario);
            border: 2px solid var(--color-primario);
        }
        
        .btn-register:hover {
            background-color: var(--color-primario);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 96, 100, 0.3);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        footer {
            margin-top: 4rem;
            text-align: center;
            color: var(--color-texto-claro);
            font-size: 0.9rem;
            width: 100%;
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-text {
                text-align: center;
                padding: 1rem;
            }
            
            .welcome-title {
                font-size: 2.2rem;
            }
            
            .intro-text {
                margin-left: auto;
                margin-right: auto;
            }
            
            .features {
                justify-content: center;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="welcome-title">
                    Conecta con <span class="highlight">EmprendeRed</span>
                </h1>
                <p class="intro-text">
                    La plataforma que une a emprendedores talentosos con clientes que buscan productos únicos y de calidad. 
                    Descubre, compra y apoya el comercio local en un solo lugar.
                </p>
                
                <div class="features">
                    <div class="feature-item">
                        <span class="feature-icon"><i class="fas fa-store"></i></span>
                        <span class="feature-text">Descubre productos únicos de emprendedores locales</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><i class="fas fa-star"></i></span>
                        <span class="feature-text">Sistema de valoraciones transparente</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><i class="fas fa-shield-alt"></i></span>
                        <span class="feature-text">Compras seguras y protegidas</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><i class="fas fa-truck"></i></span>
                        <span class="feature-text">Envíos rápidos y confiables</span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="login.php" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                    </a>
                    <a href="register.php" class="btn btn-register">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </a>
                </div>
            </div>
            
            <div class="hero-image">
                <img src="assets/hero-image.png" alt="Emprendedores mostrando sus productos">
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?= date('Y') ?> EmprendeRed. Todos los derechos reservados.</p>
    </footer>

    <script>
        // Efecto hover suave para los botones
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transition = 'all 0.2s ease';
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transition = 'all 0.3s ease';
            });
        });
    </script>
</body>
</html>