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
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
if (!$cliente_id) {
    echo '<h2 style="color:red;">No se ha seleccionado un cliente.</h2>';
    exit();
}
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
$stmt = $conn->prepare('SELECT nombre FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$stmt->bind_result($cliente_nombre);
$stmt->fetch();
$stmt->close();
$rutinas = [];
$stmt = $conn->prepare('SELECT id, dia_semana FROM rutinas WHERE cliente_id = ?');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rutinas[] = $row;
}
$stmt->close();
$ejercicios_rutina = [];
foreach ($rutinas as $rutina) {
    $stmt = $conn->prepare('SELECT e.nombre, re.series, re.repeticiones, e.youtube_url FROM rutina_ejercicios re INNER JOIN ejercicios e ON re.ejercicio_id = e.id WHERE re.rutina_id = ?');
    $stmt->bind_param('i', $rutina['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ejercicios_rutina[$rutina['id']][] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Rutina Mensual</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <nav class="sidebar-nutriologo" id="sidebar">
        <?php if (file_exists('logo.png')): ?>
            <img src="logo.png" alt="Logo" style="max-width:120px;max-height:60px;display:block;margin:0 auto 8px auto;" />
        <?php endif; ?>
        <div class="menu-nutriologo">
            <a class="menu-link" href="nutriologo_dashboard.php"><span>👥</span><span>Mis Clientes</span></a>
            <a class="menu-link" href="nutriologo_resultados.php?cliente_id=<?= $cliente_id ?>"><span>📊</span><span>Resultados</span></a>
            <a class="logout-link menu-link" href="logout.php"><span>🚪</span><span>Cerrar sesión</span></a>
        </div>
    </nav>
    <main class="nutriologo-main">
        <div class="card-section">
            <h2 style="color:#0074D9;">Rutina Mensual de <?= htmlspecialchars($cliente_nombre) ?></h2>
            <div style="overflow-x:auto;">
            <table class="rutina-table">
                <tr>
                    <th>Día</th>
                    <th>Ejercicios</th>
                </tr>
                <?php foreach ($rutinas as $rutina): ?>
                <tr>
                    <td><?= htmlspecialchars($rutina['dia_semana']) ?></td>
                    <td>
                        <?php if (!empty($ejercicios_rutina[$rutina['id']])): ?>
                            <ul style="margin:0; padding-left:18px;">
                            <?php foreach ($ejercicios_rutina[$rutina['id']] as $ej): ?>
                                <li>
                                    <?= htmlspecialchars($ej['nombre']) ?> (<?= $ej['series'] ?>x<?= $ej['repeticiones'] ?>)
                                    <?php if (!empty($ej['youtube_url'])): ?>
                                        <a class="play-btn" href="<?= htmlspecialchars($ej['youtube_url']) ?>" target="_blank" title="Ver en YouTube">
                                            ▶
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            Sin ejercicios
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>
        </div>
    </main>
</body>
</html> 