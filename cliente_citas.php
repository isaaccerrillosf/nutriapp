<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
}
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';
$cliente_id = $_SESSION['usuario_id'];
$mensaje = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
// Confirmar cita
if (isset($_POST['confirmar_cita_id'])) {
    $cita_id = intval($_POST['confirmar_cita_id']);
    $stmt = $conn->prepare('UPDATE citas SET confirmada = 1, fecha_confirmacion = NOW() WHERE id = ? AND cliente_id = ?');
    $stmt->bind_param('ii', $cita_id, $cliente_id);
    if ($stmt->execute()) {
        $mensaje = '¡Cita confirmada correctamente!';
    }
    $stmt->close();
}
// Obtener próximas citas (futuras)
$proximas_citas = [];
$stmt = $conn->prepare('SELECT * FROM citas WHERE cliente_id = ? AND fecha_cita >= NOW() ORDER BY fecha_cita ASC');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $proximas_citas[] = $row;
}
$stmt->close();
// Obtener historial de citas (pasadas)
$historial_citas = [];
$stmt = $conn->prepare('SELECT * FROM citas WHERE cliente_id = ? AND fecha_cita < NOW() ORDER BY fecha_cita DESC');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $historial_citas[] = $row;
}
$stmt->close();
$conn->close();
function dias_restantes($fecha_cita) {
    $ahora = new DateTime();
    $fecha = new DateTime($fecha_cita);
    $diff = $ahora->diff($fecha);
    return $diff->invert ? 0 : $diff->days;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Mis Citas</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/cliente.css">
    <style>
        body {
            background: #f4f8fb;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .cliente-main {
            margin-left: 250px;
            flex: 1;
            padding: 32px 18px 18px 18px;
            background: #f4f8fb;
            min-height: 100vh;
            padding-top: 80px;
        }
        .sidebar-cliente {
            width: 250px;
            background: #fff;
            color: #0074D9;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 24px;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 2000;
            box-shadow: 2px 0 12px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border-right: 2px solid #e6ecf3;
        }
        .sidebar-cliente img {
            max-height: 60px;
            max-width: 120px;
            margin-bottom: 24px;
        }
        .sidebar-cliente .menu-cliente {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .sidebar-cliente .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            color: #0074D9;
            text-decoration: none;
            font-size: 1.08em;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
            font-weight: 500;
        }
        .sidebar-cliente .menu-link:hover, .sidebar-cliente .menu-link.active {
            background: #e6f2fb;
            color: #0056b3;
        }
        .sidebar-cliente .logout-link {
            color: #e74c3c;
            font-weight: 600;
            margin-top: 24px;
        }
        .citas-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1.5px solid #e6ecf3;
            margin-bottom: 24px;
        }
        .citas-table th, .citas-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #e6ecf3;
        }
        .citas-table th {
            background: #e6f2fb;
            color: #0074D9;
            font-weight: 700;
            font-size: 1.08em;
        }
        .citas-table td {
            color: #23272f;
            font-size: 1em;
        }
        .citas-table tr:hover {
            background: #e6f2fb;
        }
        .cita-row.confirmada {
            background: #eafaf1;
        }
        .cita-row.pendiente {
            background: #fffbe6;
        }
        .cita-confirmada {
            color: #27ae60;
            font-weight: 700;
        }
        .cita-pendiente {
            color: #e67e22;
            font-weight: 700;
        }
        .contador-cita {
            color: #0074D9;
            font-weight: 600;
            margin-left: 8px;
            font-size: 0.98em;
        }
        .card-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            padding: 28px 24px;
            margin: 0 auto 32px auto;
            max-width: 700px;
        }
        @media (max-width: 900px) {
            .cliente-main {
                margin-left: 64px;
            }
            .sidebar-cliente {
                width: 64px;
                padding-top: 12px;
            }
            .sidebar-cliente img {
                max-width: 40px;
                max-height: 40px;
                margin-bottom: 12px;
            }
            .sidebar-cliente .menu-link span {
                display: none;
            }
            .sidebar-cliente .menu-link {
                justify-content: center;
                padding: 14px 0;
            }
        }
        @media (max-width: 600px) {
            .citas-table th, .citas-table td {
                padding: 8px 6px;
                font-size: 0.97em;
            }
            .card-section {
                border-radius: 0;
                box-shadow: none;
                margin-bottom: 16px;
                padding: 18px 6px;
                max-width: 98vw;
            }
            .cliente-main {
                margin-left: 0;
                padding-top: 100px;
                padding-left: 0;
                padding-right: 0;
            }
            .sidebar-cliente {
                transform: translateX(-100%);
                width: 85vw;
                max-width: 340px;
                padding-top: 48px;
                left: 0;
                top: 0;
                bottom: 0;
                height: 100vh;
                position: fixed;
                transition: transform 0.3s cubic-bezier(.4,0,.2,1);
                box-shadow: 2px 0 16px rgba(0,0,0,0.18);
                z-index: 3000;
                background: #fff;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                overflow-y: auto;
            }
            .sidebar-cliente.menu-abierto {
                transform: translateX(0);
            }
            .sidebar-cliente .menu-cliente {
                width: 100%;
                margin-top: 0;
                display: flex;
                flex-direction: column;
                gap: 0;
                padding: 0;
                height: 100vh;
                flex: 1 1 auto;
                overflow-y: auto;
            }
            .sidebar-cliente .menu-link {
                width: 100%;
                padding: 18px 28px;
                font-size: 1.1em;
                border-radius: 0;
                border-left: 4px solid transparent;
                color: #0074D9 !important;
                background: none !important;
                text-align: left;
                transition: background 0.2s, color 0.2s;
                border-top: none !important;
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .sidebar-cliente .menu-link span {
                display: inline !important;
                color: #23272f !important;
                font-weight: 600;
                font-size: 1.1em;
                vertical-align: middle;
            }
            .sidebar-cliente .menu-link.active, .sidebar-cliente .menu-link:hover {
                background: #e6f2fb;
                color: #0056b3 !important;
                border-left: 4px solid #0074D9;
            }
            .sidebar-cliente .logout-link {
                color: #e74c3c;
                font-weight: 600;
                width: 100%;
                padding: 18px 28px;
                border-radius: 0;
                background: none;
                border-left: 4px solid transparent;
            }
        }
    </style>
</head>
<body>
<?php include 'header_cliente.php'; ?>

<?php include 'menu_lateral_cliente.php'; ?>
<?php include 'cliente_carrusel.php'; ?>
<main class="cliente-main">
    <h1 style="color:#0074D9;font-size:2.3rem;font-weight:700;margin-bottom:28px;">Mis Citas</h1>
    <div class="card-section">
        <?php if ($mensaje): ?>
            <p style="color:#27ae60; text-align: center; margin-bottom: 20px;"> <?= $mensaje ?> </p>
        <?php endif; ?>
        <h2 style="color:#0074D9;">Próximas Citas</h2>
        <div style="overflow-x:auto;">
        <table class="citas-table">
            <tr>
                <th>Fecha</th>
                <th>Notas</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
            <?php foreach ($proximas_citas as $cita): ?>
            <tr class="cita-row <?= $cita['confirmada'] ? 'confirmada' : 'pendiente' ?>">
                <td><?= date('d/m/Y H:i', strtotime($cita['fecha_cita'])) ?>
                    <?php $dias = dias_restantes($cita['fecha_cita']);
                    if ($dias > 0 && $dias <= 10): ?>
                        <span class="contador-cita">Faltan <?= $dias ?> día<?= $dias==1?'':'s' ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($cita['notas']) ?></td>
                <td>
                    <?php if ($cita['confirmada']): ?>
                        <span class="cita-confirmada">Confirmada<?= $cita['fecha_confirmacion'] ? ' el '.date('d/m/Y H:i', strtotime($cita['fecha_confirmacion'])) : '' ?></span>
                    <?php else: ?>
                        <span class="cita-pendiente">Pendiente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$cita['confirmada']): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="confirmar_cita_id" value="<?= $cita['id'] ?>">
                            <button type="submit" class="btn-submit">Confirmar cita</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
    <div class="card-section">
        <h2 style="color:#0074D9;">Historial de Citas</h2>
        <div style="overflow-x:auto;">
        <table class="citas-table">
            <tr>
                <th>Fecha</th>
                <th>Notas</th>
                <th>Estado</th>
            </tr>
            <?php foreach ($historial_citas as $cita): ?>
            <tr class="cita-row <?= $cita['confirmada'] ? 'confirmada' : 'pendiente' ?>">
                <td><?= date('d/m/Y H:i', strtotime($cita['fecha_cita'])) ?></td>
                <td><?= htmlspecialchars($cita['notas']) ?></td>
                <td>
                    <?php if ($cita['confirmada']): ?>
                        <span class="cita-confirmada">Confirmada<?= $cita['fecha_confirmacion'] ? ' el '.date('d/m/Y H:i', strtotime($cita['fecha_confirmacion'])) : '' ?></span>
                    <?php else: ?>
                        <span class="cita-pendiente">Pendiente</span>
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