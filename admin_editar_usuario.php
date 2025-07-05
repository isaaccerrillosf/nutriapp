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
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = '';
$roles = ['cliente' => 'Cliente', 'nutriologo' => 'Nutriólogo', 'admin' => 'Administrador'];

if ($id <= 0) {
    header('Location: admin_usuarios.php');
    exit();
}

// Obtener datos actuales
$stmt = $conn->prepare('SELECT nombre, email, telefono, rol FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($nombre, $email, $telefono, $rol);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_nombre = trim($_POST['nombre'] ?? '');
    $nuevo_email = trim($_POST['email'] ?? '');
    $nuevo_telefono = trim($_POST['telefono'] ?? '');
    $nuevo_rol = $_POST['rol'] ?? '';
    $nueva_password = $_POST['password'] ?? '';
    
    if ($nuevo_nombre && $nuevo_email && $nuevo_telefono && in_array($nuevo_rol, array_keys($roles))) {
        // Verificar que el email no exista en otro usuario
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
        $stmt->bind_param('si', $nuevo_email, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $mensaje = 'Ya existe otro usuario con ese email.';
        } else {
            if ($nueva_password) {
                $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE usuarios SET nombre=?, email=?, telefono=?, rol=?, password=? WHERE id=?');
                $stmt->bind_param('sssssi', $nuevo_nombre, $nuevo_email, $nuevo_telefono, $nuevo_rol, $hash, $id);
            } else {
                $stmt = $conn->prepare('UPDATE usuarios SET nombre=?, email=?, telefono=?, rol=? WHERE id=?');
                $stmt->bind_param('ssssi', $nuevo_nombre, $nuevo_email, $nuevo_telefono, $nuevo_rol, $id);
            }
            if ($stmt->execute()) {
                $mensaje = 'Usuario actualizado correctamente.';
            } else {
                $mensaje = 'Error al actualizar usuario.';
            }
        }
        $stmt->close();
    } else {
        $mensaje = 'Todos los campos son obligatorios.';
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .form-section { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.10); border: 1.5px solid #e6ecf3; padding: 32px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; color: #23272f; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1.5px solid #e6ecf3; border-radius: 8px; font-size: 1em; background: #fff; color: #23272f; box-sizing: border-box; }
        .btn-submit { background: #0074D9; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; cursor: pointer; margin-top: 12px; }
        .btn-submit:hover { background: #0056b3; }
        .mensaje { text-align: center; margin-bottom: 18px; color: #0074D9; font-weight: 600; }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<?php include 'menu_lateral_admin.php'; ?>
<?php include 'admin_carrusel.php'; ?>
<main class="admin-main">
    <h1 style="color:#0074D9;text-align:center;margin-bottom:24px;">Editar Usuario</h1>
    <section class="form-section">
        <?php if ($mensaje): ?>
            <div class="mensaje"> <?= htmlspecialchars($mensaje) ?> </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Nombre completo:</label>
                <input type="text" name="nombre" required maxlength="80" value="<?= htmlspecialchars($nombre) ?>">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required maxlength="80" value="<?= htmlspecialchars($email) ?>">
            </div>
            <div class="form-group">
                <label>Teléfono:</label>
                <input type="text" name="telefono" required maxlength="20" value="<?= htmlspecialchars($telefono) ?>">
            </div>
            <div class="form-group">
                <label>Rol:</label>
                <select name="rol" required>
                    <?php foreach ($roles as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $rol === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nueva contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" name="password" minlength="6">
            </div>
            <button type="submit" class="btn-submit">Guardar Cambios</button>
        </form>
    </section>
    <div style="text-align:center;margin-top:24px;">
        <a href="admin_usuarios.php" style="color:#0074D9;font-weight:600;text-decoration:underline;">Volver al listado de usuarios</a>
    </div>
</main>
</body>
</html> 