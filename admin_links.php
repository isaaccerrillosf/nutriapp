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
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Agregar link
if (isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'] ?? '';
    $youtube_url = $_POST['youtube_url'] ?? '';
    if ($nombre && $youtube_url) {
        $stmt = $conn->prepare('INSERT INTO links_ejercicios (nombre, youtube_url) VALUES (?, ?)');
        $stmt->bind_param('ss', $nombre, $youtube_url);
        if ($stmt->execute()) {
            $mensaje = 'Video agregado correctamente.';
        } else {
            $mensaje = 'Error al agregar: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = 'Todos los campos son obligatorios.';
    }
}
// Eliminar link
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conn->query('DELETE FROM links_ejercicios WHERE id = ' . $id);
    $mensaje = 'Video eliminado.';
}
// Editar link
if (isset($_POST['editar'])) {
    $id = intval($_POST['id']);
    $nombre = $_POST['nombre'] ?? '';
    $youtube_url = $_POST['youtube_url'] ?? '';
    if ($nombre && $youtube_url) {
        $stmt = $conn->prepare('UPDATE links_ejercicios SET nombre=?, youtube_url=? WHERE id=?');
        $stmt->bind_param('ssi', $nombre, $youtube_url, $id);
        if ($stmt->execute()) {
            $mensaje = 'Video actualizado.';
        } else {
            $mensaje = 'Error al actualizar: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = 'Todos los campos son obligatorios.';
    }
}
// Obtener todos los links
$links = [];
$result = $conn->query('SELECT * FROM links_ejercicios ORDER BY id DESC');
while ($row = $result->fetch_assoc()) {
    $links[] = $row;
}
// Si se va a editar
$link_editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $res = $conn->query('SELECT * FROM links_ejercicios WHERE id = ' . $id);
    $link_editar = $res->fetch_assoc();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestión de Links</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .admin-main {
            padding-top: 80px;
        }
        @media (max-width: 600px) {
            .admin-main {
                padding-top: 100px;
            }
        }
        /* Mejora de legibilidad en inputs y textarea */
        input[type="text"],
        input[type="url"],
        textarea {
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
        input[type="text"]::placeholder,
        input[type="url"]::placeholder,
        textarea::placeholder {
            color: #b0b8c1 !important;
            opacity: 1;
        }
        label {
            color: #23272f !important;
            font-weight: 600 !important;
            font-size: 1.08em !important;
            margin-bottom: 8px !important;
            display: block;
        }
        button, .btn {
            background: #0074D9 !important;
            color: #fff !important;
            border: none !important;
            padding: 14px 0 !important;
            border-radius: 12px !important;
            width: 100% !important;
            margin: 18px 0 0 0 !important;
            font-size: 1.08em !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
            text-align: center !important;
            transition: background 0.2s;
            display: block;
        }
        button:hover, .btn:hover {
            background: #0056b3 !important;
        }
        table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        th, td {
            color: #23272f;
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>

<?php include 'menu_lateral_admin.php'; ?>
<?php include 'admin_carrusel.php'; ?>
<main class="admin-main">
    <h1>Videos de Ejercicios (YouTube)</h1>
    <?php if ($mensaje): ?>
        <p style="color:green;"> <?= $mensaje ?> </p>
    <?php endif; ?>
    <?php if ($link_editar): ?>
        <h2>Editar Video</h2>
        <form method="POST" action="admin_links.php">
            <input type="hidden" name="id" value="<?= $link_editar['id'] ?>">
            <label>Nombre del ejercicio:
                <input type="text" name="nombre" value="<?= htmlspecialchars($link_editar['nombre']) ?>" required>
            </label>
            <label>Link de YouTube:
                <input type="url" name="youtube_url" value="<?= htmlspecialchars($link_editar['youtube_url']) ?>" required>
            </label>
            <button type="submit" name="editar">Guardar cambios</button>
            <a class="btn" href="admin_links.php">Cancelar</a>
        </form>
    <?php else: ?>
        <h2>Agregar Video</h2>
        <form method="POST" action="admin_links.php">
            <label>Nombre del ejercicio:
                <input type="text" name="nombre" required>
            </label>
            <label>Link de YouTube:
                <input type="url" name="youtube_url" required placeholder="https://www.youtube.com/watch?v=...">
            </label>
            <button type="submit" name="agregar">Agregar video</button>
        </form>
    <?php endif; ?>
    <h2>Lista de Videos</h2>
    <table border="1" cellpadding="5" style="width:100%;max-width:600px;margin:auto;">
        <tr>
            <th>Nombre</th>
            <th>Link</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($links as $l): ?>
        <tr>
            <td><?= htmlspecialchars($l['nombre']) ?></td>
            <td><a href="<?= htmlspecialchars($l['youtube_url']) ?>" target="_blank">Ver video</a></td>
            <td>
                <a href="admin_links.php?editar=<?= $l['id'] ?>">Editar</a> |
                <a href="admin_links.php?eliminar=<?= $l['id'] ?>" onclick="return confirm('¿Eliminar este video?')">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</main>
</body>
</html> 