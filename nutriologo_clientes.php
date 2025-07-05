<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'nutriologo') {
    header('Location: login.php');
    exit();
}
$nutriologo_id = $_SESSION['usuario_id'];
$nombre = $_SESSION['usuario_nombre'] ?? '';
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
// Filtros y búsqueda
$busqueda = $_GET['busqueda'] ?? '';
$filtro = $_GET['filtro'] ?? '';
$sql = "SELECT u.id, u.nombre, u.email, u.telefono, u.fecha_registro FROM usuarios u INNER JOIN nutriologo_cliente nc ON u.id = nc.cliente_id WHERE nc.nutriologo_id = ?";
$params = [$nutriologo_id];
$types = 'i';
if ($busqueda) {
    $sql .= " AND (u.nombre LIKE ? OR u.email LIKE ? OR u.telefono LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param]);
    $types .= 'sss';
}
if ($filtro === 'recientes') {
    $sql .= " ORDER BY u.fecha_registro DESC";
} else {
    $sql .= " ORDER BY u.nombre";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$clientes = [];
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Clientes | Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .filtros-clientes { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; align-items: center; }
        .filtros-clientes input[type='text'] { padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e6ecf3; font-size: 1em; }
        .filtros-clientes select { padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e6ecf3; font-size: 1em; }
        .clientes-table th, .clientes-table td { padding: 12px 8px; }
        .clientes-table th { background: #0074D9; color: #fff; font-weight: 600; }
        .clientes-table td { color: #23272f; }
        .clientes-table tr:hover { background: #f8f9fa; }
        .acciones-btns { display: flex; gap: 8px; }
        .btn-mini { background: #0074D9; color: #fff; border: none; border-radius: 8px; padding: 7px 12px; font-size: 0.98em; font-weight: 600; cursor: pointer; transition: background 0.18s; }
        .btn-mini:hover { background: #0056b3; }
        @media (max-width: 900px) {
            .clientes-table { display: none; }
            .clientes-cards { display: block; }
        }
        @media (min-width: 901px) {
            .clientes-cards { display: none; }
        }
        .cliente-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1.5px solid #e6ecf3; padding: 18px 14px 14px 14px; margin-bottom: 16px; }
        .cliente-card .nombre { color: #0074D9; font-weight: 700; font-size: 1.1em; margin-bottom: 4px; }
        .cliente-card .dato { color: #23272f; font-size: 0.98em; margin-bottom: 2px; }
        .cliente-card .acciones-btns { margin-top: 10px; }
    </style>
</head>
<body>
<?php include 'header_nutriologo.php'; ?>
<?php include 'menu_lateral_nutriologo.php'; ?>
<?php include 'nutriologo_carrusel.php'; ?>
<main class="nutriologo-main">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;">Mis Clientes</h1>
    <form class="filtros-clientes" method="get">
        <input type="text" name="busqueda" placeholder="Buscar por nombre, email o teléfono" value="<?= htmlspecialchars($busqueda) ?>">
        <select name="filtro">
            <option value="">Ordenar por nombre</option>
            <option value="recientes" <?= $filtro==='recientes'?'selected':'' ?>>Más recientes</option>
        </select>
        <button type="submit" class="btn-mini">Buscar</button>
    </form>
    <!-- Vista de escritorio -->
    <table class="clientes-table" style="width:100%;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);background:#fff;">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Fecha de alta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['nombre']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['telefono']) ?></td>
                <td><?= date('d/m/Y', strtotime($c['fecha_registro'])) ?></td>
                <td class="acciones-btns">
                    <a href="nutriologo_plan.php?cliente_id=<?= $c['id'] ?>" class="btn-mini">Plan</a>
                    <a href="nutriologo_resultados.php?cliente_id=<?= $c['id'] ?>" class="btn-mini">Resultados</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Vista móvil -->
    <div class="clientes-cards">
        <?php foreach ($clientes as $c): ?>
        <div class="cliente-card">
            <div class="nombre"><?= htmlspecialchars($c['nombre']) ?></div>
            <div class="dato"><strong>Email:</strong> <?= htmlspecialchars($c['email']) ?></div>
            <div class="dato"><strong>Tel:</strong> <?= htmlspecialchars($c['telefono']) ?></div>
            <div class="dato"><strong>Alta:</strong> <?= date('d/m/Y', strtotime($c['fecha_registro'])) ?></div>
            <div class="acciones-btns">
                <a href="nutriologo_plan.php?cliente_id=<?= $c['id'] ?>" class="btn-mini">Plan</a>
                <a href="nutriologo_resultados.php?cliente_id=<?= $c['id'] ?>" class="btn-mini">Resultados</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html> 