<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$mensaje = '';
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';

// Obtener lista de nutriólogos
$conn = new mysqli($host, $user, $pass, $db);
$nutriologos = [];
$result = $conn->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'nutriologo'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $nutriologos[] = $row;
    }
}
$conn->close();

// Crear carpeta de logos si no existe
$logos_dir = 'logos_nutriologos/';
if (!is_dir($logos_dir)) {
    mkdir($logos_dir, 0777, true);
}

// Obtener lista de archivos de logo subidos
$archivos_logo = array_filter(
    scandir($logos_dir),
    function($f) { return preg_match('/\.(png|jpg|jpeg|gif)$/i', $f); }
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Subir archivo de logo
    if (isset($_POST['subir_logo']) && isset($_FILES['nuevo_logo']) && $_FILES['nuevo_logo']['error'] === UPLOAD_ERR_OK) {
        $permitidos = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
        if (in_array($_FILES['nuevo_logo']['type'], $permitidos)) {
            $ext = pathinfo($_FILES['nuevo_logo']['name'], PATHINFO_EXTENSION);
            $nombre_archivo = uniqid('logo_', true) . '.' . strtolower($ext);
            move_uploaded_file($_FILES['nuevo_logo']['tmp_name'], $logos_dir . $nombre_archivo);
            $mensaje = 'Logo subido correctamente.';
        } else {
            $mensaje = 'Solo se permiten imágenes PNG, JPG, JPEG o GIF.';
        }
    }
    // Asignar logo a nutriólogo
    if (isset($_POST['asignar_logo']) && isset($_POST['nutriologo_id']) && isset($_POST['archivo_logo'])) {
        $nutriologo_id = intval($_POST['nutriologo_id']);
        $archivo_logo = basename($_POST['archivo_logo']);
        $ext = pathinfo($archivo_logo, PATHINFO_EXTENSION);
        $destino = "logo_nutriologo_{$nutriologo_id}.{$ext}";
        if (file_exists($logos_dir . $archivo_logo)) {
            copy($logos_dir . $archivo_logo, $destino);
            $mensaje = 'Logo asignado correctamente al nutriólogo.';
        } else {
            $mensaje = 'El archivo seleccionado no existe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir y Asignar Logo a Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .logo-form-panel {
            max-width: 400px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            border: 1.5px solid #e6ecf3;
            padding: 32px 24px 24px 24px;
        }
        .logo-form-panel h2 {
            color: #23272f;
            font-size: 1.2em;
            margin-bottom: 18px;
            font-weight: 700;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; color: #23272f; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1.5px solid #e6ecf3; border-radius: 8px; font-size: 1em; background: #fff; color: #23272f; box-sizing: border-box; }
        .btn-submit { background: #0074D9; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; cursor: pointer; margin-top: 12px; }
        .btn-submit:hover { background: #0056b3; }
        .mensaje { text-align: center; margin-bottom: 18px; color: #0074D9; font-weight: 600; }
        .logo-preview { text-align:center; margin-top:18px; }
        .logo-preview img { max-width:120px; max-height:60px; object-fit:contain; background:#f4f8fb; border-radius:8px; }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<main class="admin-main">
    <section class="logo-form-panel">
        <h2>Subir y Asignar Logo a Nutriólogo</h2>
        <?php if ($mensaje): ?>
            <div class="mensaje"> <?= htmlspecialchars($mensaje) ?> </div>
        <?php endif; ?>
        <!-- Formulario para subir logo -->
        <form method="POST" enctype="multipart/form-data" style="margin-bottom:24px;">
            <div class="form-group">
                <label>Subir nuevo logo (PNG, JPG, JPEG, GIF):</label>
                <input type="file" name="nuevo_logo" accept="image/png,image/jpeg,image/jpg,image/gif" required>
            </div>
            <button type="submit" name="subir_logo" class="btn-submit">Subir Logo</button>
        </form>
        <!-- Formulario para asignar logo -->
        <form method="POST">
            <div class="form-group">
                <label>Selecciona nutriólogo:</label>
                <select name="nutriologo_id" required>
                    <option value="">Selecciona un nutriólogo</option>
                    <?php foreach ($nutriologos as $n): ?>
                        <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['nombre']) ?> (<?= htmlspecialchars($n['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Selecciona archivo de logo subido:</label>
                <select name="archivo_logo" required>
                    <option value="">Selecciona un archivo</option>
                    <?php foreach ($archivos_logo as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="asignar_logo" class="btn-submit">Asignar Logo</button>
        </form>
        <!-- Vista previa del logo seleccionado -->
        <?php if (!empty($_POST['archivo_logo']) && in_array($_POST['archivo_logo'], $archivos_logo)): ?>
            <div class="logo-preview">
                <strong>Vista previa del logo seleccionado:</strong><br>
                <img src="<?= $logos_dir . htmlspecialchars($_POST['archivo_logo']) ?>" alt="Logo Nutriólogo" />
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html> 