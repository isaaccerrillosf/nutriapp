<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'nutriologo') {
    header('Location: login.php');
    exit();
}
$nutriologo_id = $_SESSION['usuario_id'];
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
$mensaje = '';
// Obtener clientes asignados
$clientes = [];
$result = $conn->query("SELECT u.id, u.nombre FROM usuarios u INNER JOIN nutriologo_cliente nc ON u.id = nc.cliente_id WHERE nc.nutriologo_id = $nutriologo_id ORDER BY u.nombre");
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}
// Registrar nuevo resultado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_resultado'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $peso = $_POST['peso'] ?? null;
    $grasa = $_POST['grasa'] ?? null;
    $cintura = $_POST['cintura'] ?? null;
    $cadera = $_POST['cadera'] ?? null;
    $brazo = $_POST['brazo'] ?? null;
    $muslo = $_POST['muslo'] ?? null;
    $notas = $_POST['notas'] ?? '';
    if ($cliente_id && $fecha) {
        $stmt = $conn->prepare('INSERT INTO resultados_cliente (cliente_id, nutriologo_id, fecha, peso, grasa_corporal, cintura, cadera, brazo, muslo, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iisdssssds', $cliente_id, $nutriologo_id, $fecha, $peso, $grasa, $cintura, $cadera, $brazo, $muslo, $notas);
        if ($stmt->execute()) {
            $mensaje = 'Resultado registrado correctamente.';
        } else {
            $mensaje = 'Error al registrar resultado: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = 'Selecciona cliente y fecha.';
    }
}
// Filtro por cliente
$cliente_filtro = $_GET['cliente_id'] ?? '';
$sql = "SELECT r.*, u.nombre as cliente_nombre FROM resultados_cliente r INNER JOIN usuarios u ON r.cliente_id = u.id WHERE r.nutriologo_id = $nutriologo_id";
if ($cliente_filtro) {
    $sql .= " AND r.cliente_id = " . intval($cliente_filtro);
}
$sql .= " ORDER BY r.fecha DESC";
$result = $conn->query($sql);
$resultados = [];
while ($row = $result->fetch_assoc()) {
    $resultados[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Resultados | Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .resultados-table th, .resultados-table td { padding: 10px 6px; }
        .resultados-table th { background: #0074D9; color: #fff; font-weight: 600; }
        .resultados-table td { color: #23272f; font-size: 0.98em; }
        .resultados-table tr:hover { background: #f8f9fa; }
        .form-resultado { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1.5px solid #e6ecf3; padding: 18px 14px 14px 14px; margin-bottom: 24px; max-width: 600px; }
        .form-resultado label { font-weight: 600; color: #23272f; margin-bottom: 6px; display: block; }
        .form-resultado input, .form-resultado select, .form-resultado textarea { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e6ecf3; font-size: 1em; margin-bottom: 12px; }
        .form-resultado textarea { min-height: 60px; }
        .filtros-resultados { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; align-items: center; }
        .filtros-resultados select { padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e6ecf3; font-size: 1em; }
        @media (max-width: 900px) { .resultados-table { font-size: 0.98em; } }
    </style>
</head>
<body>
<?php include 'header_nutriologo.php'; ?>
<?php include 'menu_lateral_nutriologo.php'; ?>
<?php include 'nutriologo_carrusel.php'; ?>
<main class="nutriologo-main">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;">Resultados de Clientes</h1>
    <?php if ($mensaje): ?>
        <div class="mensaje" style="background:#e6f2fb;color:#0074D9;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-weight:600;text-align:center;"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>
    <form class="form-resultado" method="post">
        <h2 style="color:#0074D9;font-size:1.1em;margin-bottom:12px;">Registrar nuevo resultado</h2>
        <label>Cliente:
            <select name="cliente_id" required>
                <option value="">Selecciona cliente</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Fecha:
            <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>">
        </label>
        <label>Peso (kg):
            <input type="number" step="0.01" name="peso" required>
        </label>
        <label>Grasa corporal (%):
            <input type="number" step="0.01" name="grasa">
        </label>
        <label>Cintura (cm):
            <input type="number" step="0.01" name="cintura">
        </label>
        <label>Cadera (cm):
            <input type="number" step="0.01" name="cadera">
        </label>
        <label>Brazo (cm):
            <input type="number" step="0.01" name="brazo">
        </label>
        <label>Muslo (cm):
            <input type="number" step="0.01" name="muslo">
        </label>
        <label>Notas:
            <textarea name="notas" placeholder="Notas (opcional)"></textarea>
        </label>
        <button type="submit" name="nuevo_resultado" class="btn-mini">Registrar Resultado</button>
    </form>
    <form class="filtros-resultados" method="get">
        <select name="cliente_id" onchange="this.form.submit()">
            <option value="">Ver todos los clientes</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $cliente_filtro==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <table class="resultados-table" style="width:100%;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);background:#fff;">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Peso (kg)</th>
                <th>Grasa (%)</th>
                <th>Cintura</th>
                <th>Cadera</th>
                <th>Brazo</th>
                <th>Muslo</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resultados as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['cliente_nombre']) ?></td>
                <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                <td><?= htmlspecialchars($r['peso']) ?></td>
                <td><?= htmlspecialchars($r['grasa_corporal']) ?></td>
                <td><?= htmlspecialchars($r['cintura']) ?></td>
                <td><?= htmlspecialchars($r['cadera']) ?></td>
                <td><?= htmlspecialchars($r['brazo']) ?></td>
                <td><?= htmlspecialchars($r['muslo']) ?></td>
                <td><?= htmlspecialchars($r['notas']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html> 