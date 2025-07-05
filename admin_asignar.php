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

// Asignar cliente a nutriólogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'], $_POST['nutriologo_id'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $nutriologo_id = intval($_POST['nutriologo_id']);
    // Eliminar asignación previa si existe
    $conn->query("DELETE FROM nutriologo_cliente WHERE cliente_id = $cliente_id");
    // Insertar nueva asignación
    $stmt = $conn->prepare('INSERT INTO nutriologo_cliente (cliente_id, nutriologo_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $cliente_id, $nutriologo_id);
    if ($stmt->execute()) {
        $mensaje = 'Cliente asignado correctamente.';
    } else {
        $mensaje = 'Error al asignar cliente: ' . $stmt->error;
    }
    $stmt->close();
}

// Obtener clientes y nutriólogos
$clientes = [];
$result = $conn->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'cliente' ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}
$nutriologos = [];
$result = $conn->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'nutriologo' ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $nutriologos[] = $row;
}
// Obtener asignaciones actuales
$asignaciones = [];
$result = $conn->query("SELECT c.id as cliente_id, c.nombre as cliente, c.email as cliente_email, n.id as nutriologo_id, n.nombre as nutriologo, n.email as nutriologo_email FROM usuarios c LEFT JOIN nutriologo_cliente nc ON c.id = nc.cliente_id LEFT JOIN usuarios n ON nc.nutriologo_id = n.id WHERE c.rol = 'cliente' ORDER BY c.nombre");
while ($row = $result->fetch_assoc()) {
    $asignaciones[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Clientes a Nutriólogos</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .asignar-panel {
            max-width: 500px;
            margin: 40px auto 32px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            border: 1.5px solid #e6ecf3;
            padding: 36px 32px 32px 32px;
        }
        .asignar-panel h2 {
            color: #23272f;
            font-size: 1.3em;
            margin-bottom: 18px;
            font-weight: 700;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; color: #23272f; font-weight: 600; }
        .form-group select { width: 100%; padding: 12px; border: 1.5px solid #e6ecf3; border-radius: 8px; font-size: 1em; background: #fff; color: #23272f; box-sizing: border-box; }
        .btn-submit { background: #0074D9; color: #fff; border: none; padding: 14px 0; border-radius: 12px; width: 100%; margin: 18px 0 0 0; font-size: 1.15em; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; transition: background 0.2s; display: block; }
        .btn-submit:hover { background: #0056b3; }
        .msg-success { background: #e6f2fb; color: #0074D9; border-radius: 8px; padding: 10px 16px; margin-bottom: 16px; font-weight: 600; text-align: center; }
        .asignaciones-table { width: 100%; border-collapse: collapse; margin-top: 32px; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .asignaciones-table th, .asignaciones-table td { padding: 12px 8px; border-bottom: 1px solid #e6ecf3; text-align: left; }
        .asignaciones-table th { background: #0074D9; color: #fff; font-weight: 600; }
        .asignaciones-table td { color: #23272f; }
        .asignaciones-table tr:hover { background: #f8f9fa; }
        @media (max-width: 700px) {
            .asignar-panel { padding: 18px 6vw 18px 6vw; }
            .asignaciones-table th, .asignaciones-table td { padding: 8px 4px; font-size: 0.98em; }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<?php include 'menu_lateral_admin.php'; ?>
<?php include 'admin_carrusel.php'; ?>
<main class="admin-main">
    <section class="asignar-panel">
        <h2>Asignar Cliente a Nutriólogo</h2>
        <?php if ($mensaje): ?>
            <div class="msg-success"> <?= htmlspecialchars($mensaje) ?> </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Cliente:</label>
                <select name="cliente_id" required>
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nutriólogo:</label>
                <select name="nutriologo_id" required>
                    <option value="">Selecciona un nutriólogo</option>
                    <?php foreach ($nutriologos as $n): ?>
                        <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['nombre']) ?> (<?= htmlspecialchars($n['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">Asignar</button>
        </form>
    </section>
    <section class="asignar-panel" style="margin-top:0;">
        <h2>Asignaciones Actuales</h2>
        <table class="asignaciones-table">
            <tr>
                <th>Cliente</th>
                <th>Nutriólogo Asignado</th>
            </tr>
            <?php foreach ($asignaciones as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['cliente']) ?><br><span style="color:#888;font-size:0.95em;">(<?= htmlspecialchars($a['cliente_email']) ?>)</span></td>
                <td>
                    <?php if ($a['nutriologo']): ?>
                        <?= htmlspecialchars($a['nutriologo']) ?><br><span style="color:#888;font-size:0.95em;">(<?= htmlspecialchars($a['nutriologo_email']) ?>)</span>
                    <?php else: ?>
                        <span style="color:#e74c3c;">Sin asignar</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </section>
</main>
</body>
</html> 