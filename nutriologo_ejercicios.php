<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'nutriologo') {
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
$mensaje = '';
$grupos_musculares = [
    'Pecho',
    'Espalda',
    'Hombros',
    'Bíceps',
    'Tríceps',
    'Piernas',
    'Glúteos',
    'Abdominales',
    'Antebrazos',
    'Pantorrillas'
];
// Actualizar ejercicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_ejercicio'])) {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = $_POST['categoria'];
    if ($id && $nombre && $categoria) {
        $stmt = $conn->prepare('UPDATE ejercicios SET nombre=?, descripcion=?, categoria=? WHERE id=?');
        $stmt->bind_param('sssi', $nombre, $descripcion, $categoria, $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = 'Ejercicio actualizado correctamente.';
    }
}
// Obtener ejercicios
$ejercicios = [];
$result = $conn->query('SELECT * FROM ejercicios ORDER BY categoria, nombre');
while ($row = $result->fetch_assoc()) {
    $ejercicios[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Editar Ejercicios | Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <style>
        .tabla-ejercicios { width: 100%; border-collapse: collapse; margin-top: 24px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .tabla-ejercicios th, .tabla-ejercicios td { padding: 12px 10px; border-bottom: 1px solid #e6ecf3; text-align: left; }
        .tabla-ejercicios th { background: #f8f9fa; color: #0074D9; font-weight: 700; }
        .tabla-ejercicios tr:last-child td { border-bottom: none; }
        .btn-editar { background: #0074D9; color: #fff; border: none; padding: 7px 16px; border-radius: 6px; cursor: pointer; font-size: 0.95em; }
        .btn-editar:hover { background: #0056b3; }
        .form-inline { display: flex; gap: 8px; align-items: center; }
        .form-inline input, .form-inline select { padding: 6px 10px; border-radius: 5px; border: 1.5px solid #e6ecf3; font-size: 1em; }
        .form-inline input[type="text"] { width: 120px; }
        .form-inline textarea { width: 180px; min-height: 30px; resize: vertical; }
        .btn-guardar { background: #28a745; color: #fff; border: none; padding: 7px 14px; border-radius: 6px; cursor: pointer; font-size: 0.95em; }
        .btn-guardar:hover { background: #218838; }
        .btn-cancelar { background: #6c757d; color: #fff; border: none; padding: 7px 14px; border-radius: 6px; cursor: pointer; font-size: 0.95em; }
        .btn-cancelar:hover { background: #495057; }
    </style>
</head>
<body>
<?php include 'header_nutriologo.php'; ?>
<?php include 'menu_lateral_nutriologo.php'; ?>
<?php include 'nutriologo_carrusel.php'; ?>
<main class="nutriologo-main">
    <h1 style="color:#0074D9;font-size:1.5em;font-weight:700;margin-bottom:18px;">Editar Ejercicios</h1>
    <?php if ($mensaje): ?>
        <div class="mensaje" style="background:#e6f2fb;color:#0074D9;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-weight:600;text-align:center;"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>
    <table class="tabla-ejercicios">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Categoría</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ejercicios as $ej): ?>
            <tr>
                <form method="post" class="form-inline">
                    <td><input type="text" name="nombre" value="<?= htmlspecialchars($ej['nombre']) ?>" required></td>
                    <td><textarea name="descripcion"><?= htmlspecialchars($ej['descripcion']) ?></textarea></td>
                    <td>
                        <select name="categoria" required>
                            <?php foreach ($grupos_musculares as $grupo): ?>
                                <option value="<?= $grupo ?>" <?= $ej['categoria']==$grupo?'selected':'' ?>><?= $grupo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="id" value="<?= $ej['id'] ?>">
                        <button type="submit" name="editar_ejercicio" class="btn-guardar">Guardar</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html> 