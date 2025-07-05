<?php
session_start();

// Configuración de la base de datos
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Conexión a la base de datos
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die('Error de conexión: ' . $conn->connect_error);
    }

    $stmt = $conn->prepare('SELECT id, nombre, password, rol FROM usuarios WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $nombre, $hash, $rol);
        $stmt->fetch();
        if (password_verify($password, $hash)) {
            $_SESSION['usuario_id'] = $id;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_rol'] = $rol;
            // Redirigir según el rol a los dashboards centralizados
            if ($rol === 'admin') {
                header('Location: admin_dashboard.php');
            } elseif ($rol === 'nutriologo') {
                header('Location: nutriologo_dashboard.php');
            } else {
                header('Location: cliente_dashboard.php');
            }
            exit();
        } else {
            $mensaje = 'Contraseña incorrecta.';
        }
    } else {
        $mensaje = 'Usuario no encontrado.';
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0074D9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="NutriApp">
    <meta name="description" content="Tu nutriólogo personal - Seguimiento nutricional y de ejercicios">
    <meta name="keywords" content="nutrición, ejercicios, salud, dieta, rutina">
    <meta name="author" content="NutriApp">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="152x152" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icons/icon-192x192.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="icons/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/icon-72x72.png">
    
    <title>Iniciar Sesión - NutriApp</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#23272f;">
        <form method="POST" action="login.php" style="max-width:370px;box-shadow:0 2px 16px rgba(0,0,0,0.10);border-radius:16px;background:#2d323c;padding:32px 24px;width:100%;">
            <?php if (file_exists('logo.png')): ?>
                <div style="text-align:center;margin-bottom:18px;">
                    <img src="logo.png" alt="Logo" style="max-width:120px;max-height:60px;">
                </div>
            <?php endif; ?>
            <h1 style="text-align:center;margin-bottom:24px;color:#4fd18b;font-family:'Montserrat',Arial,sans-serif;font-weight:700;">Iniciar Sesión</h1>
            <?php if ($mensaje): ?>
                <p style="color:#e74c3c;text-align:center;"> <?= $mensaje ?> </p>
            <?php endif; ?>
            <div style="margin-bottom:18px;">
                <label for="email" style="color:#bfc9d1;font-family:'Montserrat',Arial,sans-serif;display:block;margin-bottom:6px;">Email:</label>
                <input id="email" type="email" name="email" required autocomplete="username" style="background:#23272f;color:#23272f;border:1px solid #23272f;border-radius:8px;padding:12px;font-size:1em;box-sizing:border-box;display:block;width:100%;">
            </div>
            <div style="margin-bottom:22px;">
                <label for="password" style="color:#bfc9d1;font-family:'Montserrat',Arial,sans-serif;display:block;margin-bottom:6px;">Contraseña:</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" style="background:#23272f;color:#23272f;border:1px solid #23272f;border-radius:8px;padding:12px;font-size:1em;box-sizing:border-box;display:block;width:100%;">
            </div>
            <button type="submit" style="background:linear-gradient(90deg,#4fd18b 60%,#3bbf7a 100%);color:#23272f;border:none;padding:14px 0;border-radius:8px;font-size:1.1em;font-weight:600;width:100%;margin:12px 0 0 0;cursor:pointer;box-shadow:0 2px 8px rgba(79,209,139,0.10);transition:background 0.2s,color 0.2s;">Entrar</button>
        </form>
    </div>
    
    <!-- PWA Scripts -->
    <script src="js/pwa-install.js"></script>
    <script>
        // Registrar Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/nutriapp/sw.js')
                    .then(registration => {
                        console.log('SW registrado exitosamente:', registration.scope);
                    })
                    .catch(error => {
                        console.log('SW registro falló:', error);
                    });
            });
        }
        
        // Detectar si es la primera visita
        if (!localStorage.getItem('nutriapp-first-visit')) {
            localStorage.setItem('nutriapp-first-visit', 'true');
            // Mostrar banner de instalación después de 3 segundos
            setTimeout(() => {
                if (window.showPWAInstallBanner) {
                    window.showPWAInstallBanner();
                }
            }, 3000);
        }
    </script>
</body>
</html> 