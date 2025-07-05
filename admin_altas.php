<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png'])) {
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            $nombre_archivo = uniqid('foto_') . '.' . $ext;
            $ruta = 'uploads/' . $nombre_archivo;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta)) {
                $foto = $ruta;
            }
        }
    }
    if ($nombre && $email && $telefono && $password && in_array($rol, ['cliente','nutriologo','admin'])) {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die('Error de conexión: ' . $conn->connect_error);
        }
        // Verificar que el email no exista
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $mensaje = 'Ya existe un usuario con ese email.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($foto) {
                $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, telefono, password, rol, foto) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssss', $nombre, $email, $telefono, $hash, $rol, $foto);
            } else {
                $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, telefono, password, rol) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $nombre, $email, $telefono, $hash, $rol);
            }
            if ($stmt->execute()) {
                $mensaje = 'Usuario registrado correctamente.';
            } else {
                $mensaje = 'Error al registrar usuario.';
            }
        }
        $stmt->close();
        $conn->close();
    } else {
        $mensaje = 'Todos los campos son obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de Usuario</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        body, .admin-main, .form-section, h1, h2, ul, li, a {
            font-family: 'Montserrat', Arial, sans-serif !important;
        }
        .admin-main {
            background: #f4f8fb;
            min-height: 100vh;
            padding-top: 40px;
        }
        .form-card {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            border: 1.5px solid #e6ecf3;
            padding: 36px 32px 32px 32px;
        }
        .form-card h2 {
            color: #23272f;
            font-size: 1.3em;
            margin-bottom: 18px;
            font-weight: 700;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            color: #23272f !important;
            font-weight: 600 !important;
            font-size: 1.08em !important;
            margin-bottom: 8px !important;
            display: block;
        }
        .form-group input, .form-group select {
            color: #23272f !important;
            background: #f4f8fb !important;
            border: 2px solid #e6ecf3 !important;
            border-radius: 8px !important;
            padding: 12px 16px !important;
            font-size: 1em !important;
            margin-bottom: 14px !important;
            width: 100%;
            box-sizing: border-box;
        }
        .form-group input::placeholder {
            color: #b0b8c1 !important;
            opacity: 1;
        }
        .btn-submit {
            background: #0074D9 !important;
            color: #fff !important;
            border: none !important;
            padding: 16px 0 !important;
            border-radius: 12px !important;
            width: 100% !important;
            margin: 18px 0 0 0 !important;
            font-size: 1.15em !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
            text-align: center !important;
            transition: background 0.2s;
            display: block;
        }
        .btn-submit:hover { background: #0056b3 !important; }
        .msg-success {
            background: #e6f2fb;
            color: #0074D9;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 16px;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<?php include 'menu_lateral_admin.php'; ?>
<?php include 'admin_carrusel.php'; ?>
<main class="admin-main">
    <section class="form-card">
        <h2>Alta de Usuario</h2>
        <?php if ($mensaje): ?>
            <div class="msg-success"> <?= htmlspecialchars($mensaje) ?> </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nombre completo:</label>
                <input type="text" name="nombre" required maxlength="80">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required maxlength="80">
            </div>
            <div class="form-group">
                <label>Teléfono:</label>
                <input type="text" name="telefono" required maxlength="20">
            </div>
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Rol:</label>
                <select name="rol" required>
                    <option value="">Selecciona un rol</option>
                    <option value="cliente">Cliente</option>
                    <option value="nutriologo">Nutriólogo</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="form-group">
                <label>Foto (opcional, JPG o PNG):</label>
                <input type="file" name="foto" accept="image/jpeg,image/png">
            </div>
            <button type="submit" class="btn-submit">Registrar Usuario</button>
        </form>
        <div style="text-align:center;margin-top:24px;">
            <a href="admin_usuarios.php" style="color:#0074D9;font-weight:600;text-decoration:underline;">Ver usuarios registrados</a>
        </div>
    </section>
</main>
</body>
</html> 