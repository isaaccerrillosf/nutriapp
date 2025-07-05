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

$rol_filtro = $_GET['rol'] ?? '';
$roles = ['cliente' => 'Cliente', 'nutriologo' => 'Nutriólogo', 'admin' => 'Administrador'];
$sql = 'SELECT id, nombre, email, telefono, rol FROM usuarios';
$params = [];
if ($rol_filtro && isset($roles[$rol_filtro])) {
    $sql .= ' WHERE rol = ?';
    $params[] = $rol_filtro;
}
$sql .= ' ORDER BY nombre';
$stmt = $conn->prepare($rol_filtro && isset($roles[$rol_filtro]) ? $sql : 'SELECT id, nombre, email, telefono, rol FROM usuarios ORDER BY nombre');
if ($rol_filtro && isset($roles[$rol_filtro])) {
    $stmt->bind_param('s', $rol_filtro);
}
$stmt->execute();
$result = $stmt->get_result();
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Registrados</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        body, .usuarios-section, .usuarios-table, h1, h2, ul, li, a {
            font-family: 'Montserrat', Arial, sans-serif !important;
        }
        .usuarios-section {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            border: 1.5px solid #e6ecf3;
            padding: 32px;
        }
        .usuarios-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .usuarios-table th, .usuarios-table td {
            padding: 16px;
            border-bottom: 1px solid #e6ecf3;
            text-align: left;
        }
        .usuarios-table th {
            background: #0074D9;
            color: white;
            font-weight: 600;
        }
        .usuarios-table td {
            color: #23272f;
        }
        .usuarios-table tr:hover {
            background: #f8f9fa;
        }
        .btn-editar {
            background: #0074D9;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-editar:hover { background: #0056b3; }
        .filtro-roles { margin-bottom: 18px; }
        .filtro-roles select { padding: 8px 12px; border-radius: 6px; border: 1.5px solid #e6ecf3; font-size: 1em; }
        /* Tarjetas móviles */
        .usuarios-cards-mobile { display: none; }
        @media (max-width: 700px) {
            .usuarios-table { display: none; }
            .usuarios-cards-mobile { display: block; }
            .usuario-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                border: 1.5px solid #e6ecf3;
                padding: 18px 14px 14px 14px;
                margin-bottom: 18px;
            }
            .usuario-nombre { font-size: 1.1em; color: #0074D9; font-weight: 700; margin-bottom: 6px; }
            .usuario-email { color: #23272f; margin-bottom: 4px; }
            .usuario-telefono { color: #666; margin-bottom: 4px; }
            .usuario-rol { color: #0074D9; font-weight: 600; margin-bottom: 4px; }
            .btn-editar { width: 100%; margin-top: 8px; }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<?php include 'menu_lateral_admin.php'; ?>
<?php include 'admin_carrusel.php'; ?>
<main class="admin-main">
    <h1 style="color:#0074D9;text-align:center;margin-bottom:24px;">Usuarios Registrados</h1>
    <section class="usuarios-section">
        <form class="filtro-roles" method="GET" style="text-align:right;">
            <label for="rol">Filtrar por rol: </label>
            <select name="rol" id="rol" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $rol_filtro === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <!-- Tabla escritorio -->
        <table class="usuarios-table">
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                <td><?= htmlspecialchars($usuario['email']) ?></td>
                <td><?= htmlspecialchars($usuario['telefono']) ?></td>
                <td><?= ucfirst($roles[$usuario['rol']]) ?></td>
                <td><a href="admin_editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn-editar">Editar</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
            <tr><td colspan="5" style="text-align:center;color:#888;">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
        </table>
        <!-- Tarjetas móvil -->
        <div class="usuarios-cards-mobile">
            <?php foreach ($usuarios as $usuario): ?>
            <div class="usuario-card">
                <div class="usuario-nombre"><b><?= htmlspecialchars($usuario['nombre']) ?></b></div>
                <div class="usuario-email">📧 <?= htmlspecialchars($usuario['email']) ?></div>
                <div class="usuario-telefono">📱 <?= htmlspecialchars($usuario['telefono']) ?></div>
                <div class="usuario-rol">Rol: <?= ucfirst($roles[$usuario['rol']]) ?></div>
                <a href="admin_editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn-editar">Editar</a>
            </div>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
            <div style="text-align:center;color:#888;">No hay usuarios registrados.</div>
            <?php endif; ?>
        </div>
    </section>
    <div style="text-align:center;margin-top:24px;">
        <a href="admin_altas.php" style="color:#0074D9;font-weight:600;text-decoration:underline;">Registrar nuevo usuario</a>
    </div>
</main>
</body>
</html> 