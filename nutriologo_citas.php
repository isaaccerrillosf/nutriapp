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
// Agregar cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_cita'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $fecha_cita = $_POST['fecha_cita'] ?? '';
    $notas = $_POST['notas'] ?? '';
    if ($cliente_id && $fecha_cita) {
        $stmt = $conn->prepare('INSERT INTO citas (cliente_id, nutriologo_id, fecha_cita, notas) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiss', $cliente_id, $nutriologo_id, $fecha_cita, $notas);
        if ($stmt->execute()) {
            $mensaje = 'Cita agendada correctamente.';
        } else {
            $mensaje = 'Error al agendar cita: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = 'Selecciona cliente y fecha.';
    }
}
// Cancelar cita
if (isset($_GET['cancelar']) && is_numeric($_GET['cancelar'])) {
    $cita_id = intval($_GET['cancelar']);
    $conn->query('DELETE FROM citas WHERE id = ' . $cita_id . ' AND nutriologo_id = ' . $nutriologo_id);
    $mensaje = 'Cita cancelada.';
}
// Obtener clientes asignados
$clientes = [];
$result = $conn->query("SELECT u.id, u.nombre FROM usuarios u INNER JOIN nutriologo_cliente nc ON u.id = nc.cliente_id WHERE nc.nutriologo_id = $nutriologo_id ORDER BY u.nombre");
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}
// Obtener citas
$citas = [];
$result = $conn->query("SELECT c.*, u.nombre as cliente_nombre FROM citas c INNER JOIN usuarios u ON c.cliente_id = u.id WHERE c.nutriologo_id = $nutriologo_id ORDER BY c.fecha_cita DESC");
while ($row = $result->fetch_assoc()) {
    $citas[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Citas | Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .citas-table th, .citas-table td { padding: 12px 8px; }
        .citas-table th { background: #0074D9; color: #fff; font-weight: 600; }
        .citas-table td { color: #23272f; }
        .citas-table tr:hover { background: #f8f9fa; }
        .btn-mini { background: #0074D9; color: #fff; border: none; border-radius: 8px; padding: 7px 12px; font-size: 0.98em; font-weight: 600; cursor: pointer; transition: background 0.18s; }
        .btn-mini:hover { background: #0056b3; }
        .form-cita { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1.5px solid #e6ecf3; padding: 18px 14px 14px 14px; margin-bottom: 24px; max-width: 500px; }
        .form-cita label { font-weight: 600; color: #23272f; margin-bottom: 6px; display: block; }
        .form-cita input, .form-cita select, .form-cita textarea { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e6ecf3; font-size: 1em; margin-bottom: 12px; }
        .form-cita textarea { min-height: 60px; }
        @media (max-width: 900px) { .citas-table { font-size: 0.98em; } }
    </style>
</head>
<body>
<?php include 'header_nutriologo.php'; ?>
<?php include 'menu_lateral_nutriologo.php'; ?>
<?php include 'nutriologo_carrusel.php'; ?>
<main class="nutriologo-main">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;">Citas</h1>
    <?php if ($mensaje): ?>
        <div class="mensaje" style="background:#e6f2fb;color:#0074D9;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-weight:600;text-align:center;"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>
    <form class="form-cita" method="post">
        <h2 style="color:#0074D9;font-size:1.1em;margin-bottom:12px;">Agendar nueva cita</h2>
        <label>Cliente:
            <select name="cliente_id" required>
                <option value="">Selecciona cliente</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Fecha y hora:
            <input type="datetime-local" name="fecha_cita" required>
        </label>
        <label>Notas:
            <textarea name="notas" placeholder="Notas (opcional)"></textarea>
        </label>
        <button type="submit" name="nueva_cita" class="btn-mini">Agendar Cita</button>
    </form>
    <table class="citas-table" style="width:100%;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);background:#fff;">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha y hora</th>
                <th>Notas</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citas as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['cliente_nombre']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($c['fecha_cita'])) ?></td>
                <td><?= htmlspecialchars($c['notas']) ?></td>
                <td>
                    <a href="?cancelar=<?= $c['id'] ?>" class="btn-mini" onclick="return confirm('¿Cancelar esta cita?');">Cancelar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html> 