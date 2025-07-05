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

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$cliente_id = $_SESSION['usuario_id'];

// Obtener teléfono del nutriólogo asignado
$telefono_nutriologo = null;
$stmt = $conn->prepare('SELECT u.telefono FROM usuarios u INNER JOIN nutriologo_cliente nc ON u.id = nc.nutriologo_id WHERE nc.cliente_id = ? AND u.rol = "nutriologo"');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $telefono_nutriologo = $row['telefono'];
}
$stmt->close();

// Obtener próxima cita
$proxima_cita = null;
$stmt = $conn->prepare('SELECT * FROM citas WHERE cliente_id = ? AND fecha_cita >= NOW() ORDER BY fecha_cita ASC LIMIT 1');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $proxima_cita = $row;
}
$stmt->close();

// Obtener estadísticas rápidas
$fecha_actual = date('Y-m-d');
$dia_semana = date('l');
$dias_espanol = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
];
$dia_actual = $dias_espanol[$dia_semana];

// Verificar si tiene rutina para hoy
$tiene_rutina_hoy = false;
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM rutinas r WHERE r.cliente_id = ? AND r.dia_semana = ?');
$stmt->bind_param('is', $cliente_id, $dia_actual);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $tiene_rutina_hoy = $row['count'] > 0;
}
$stmt->close();

// Verificar si tiene plan nutricional para hoy
$tiene_plan_hoy = false;
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM planes_nutricionales p WHERE p.cliente_id = ? AND p.fecha = ?');
$stmt->bind_param('is', $cliente_id, $fecha_actual);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $tiene_plan_hoy = $row['count'] > 0;
}
$stmt->close();

// -------- NUEVAS CONSULTAS PARA TARJETAS DEL DASHBOARD --------
// 1) Próximo entrenamiento
$lista_dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$dias_indices = array_flip($lista_dias); // 'Lunes' =>0, etc.
$next_workout = 'No programado';

// obtener dias con rutina
$stmt = $conn->prepare('SELECT DISTINCT dia_semana FROM rutinas WHERE cliente_id = ?');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
$dias_rutina = [];
while ($row = $result->fetch_assoc()) {
    $dias_rutina[] = $row['dia_semana'];
}
$stmt->close();

if (!empty($dias_rutina)) {
    $hoy_idx = $dias_indices[$dia_actual];
    for ($i = 0; $i < 7; $i++) {
        $dia_idx = ($hoy_idx + $i) % 7;
        $dia_nombre = $lista_dias[$dia_idx];
        if (in_array($dia_nombre, $dias_rutina)) {
            $next_workout = $i === 0 ? 'Hoy' : ($i === 1 ? 'Mañana' : $dia_nombre);
            break;
        }
    }
}

// 2) Calorías consumidas hoy (placeholder si no existe tabla seguimiento de calorías)
$calorias_hoy = '-';
$stmt = $conn->prepare('SELECT SUM(calorias) as total FROM registro_calorias WHERE cliente_id=? AND fecha=?');
if ($stmt) {
    $stmt->bind_param('is', $cliente_id, $fecha_actual);
    if ($stmt->execute()) {
        $resCal = $stmt->get_result();
        if ($row = $resCal->fetch_assoc()) {
            $calorias_hoy = $row['total'] ? (int)$row['total'] : 0;
        }
    }
    $stmt->close();
}

// 3) Progreso de peso corporal (último vs anterior)
$peso_actual = null;
$peso_anterior = null;
$stmt = $conn->prepare('SELECT peso FROM resultados_cliente WHERE cliente_id=? AND peso IS NOT NULL ORDER BY fecha DESC LIMIT 2');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$resPeso = $stmt->get_result();
$pesos = [];
while ($row = $resPeso->fetch_assoc()) { $pesos[] = $row['peso']; }
$stmt->close();
if (count($pesos) > 0) { $peso_actual = $pesos[0]; }
if (count($pesos) > 1) { $peso_anterior = $pesos[1]; }
$peso_diff = null;
if ($peso_actual !== null && $peso_anterior !== null) {
    $peso_diff = $peso_actual - $peso_anterior;
}

// 4) Racha de días activos (seguimiento ejercicios)
$racha_dias = 0;
$stmt = $conn->prepare('SELECT DISTINCT fecha_ejercicio FROM seguimiento_ejercicios WHERE cliente_id=? AND completado=1 AND fecha_ejercicio <= ? ORDER BY fecha_ejercicio DESC LIMIT 30');
$stmt->bind_param('is', $cliente_id, $fecha_actual);
$stmt->execute();
$resSeg = $stmt->get_result();
$fechas = [];
while ($row = $resSeg->fetch_assoc()) { $fechas[] = $row['fecha_ejercicio']; }
$stmt->close();

if (!empty($fechas)) {
    $fecha_iter = new DateTime($fecha_actual);
    foreach ($fechas as $fecha_db) {
        if ($fecha_iter->format('Y-m-d') === $fecha_db) {
            $racha_dias++;
            $fecha_iter->modify('-1 day');
        } else {
            break;
        }
    }
}
// -------- FIN NUEVAS CONSULTAS --------

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
    <title>Dashboard Cliente</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        body, .cliente-main, .card-section, h1, h2, ul, li, a {
            font-family: 'Montserrat', Arial, sans-serif !important;
        }
        .cliente-main {
            background: #f4f8fb;
            min-height: 100vh;
            padding-top: 40px;
        }
        .card-section.panel-control {
            max-width: 500px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            border: 1.5px solid #e6ecf3;
            padding: 36px 32px 32px 32px;
        }
        .panel-control h2 {
            color: #23272f;
            font-size: 1.3em;
            margin-bottom: 18px;
            font-weight: 700;
        }
        .panel-control ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .panel-control li {
            margin-bottom: 16px;
        }
        .panel-control a {
            color: #23272f;
            font-weight: 600;
            text-decoration: none;
            font-size: 1.08em;
            transition: color 0.2s;
        }
        .panel-control a:hover {
            color: #0074D9;
            text-decoration: underline;
        }
        .bienvenida-cliente {
            color: #23272f;
            text-align: center;
            margin-bottom: 18px;
            font-size: 2.1em;
            font-weight: 700;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        body {
            background: #f4f8fb;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .cliente-main {
            margin-left: 250px;
            flex: 1;
            padding: 80px 18px 18px 18px;
            background: #f4f8fb;
            min-height: 100vh;
            transition: margin-left 0.3s;
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
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
            border-right: 2px solid #e6ecf3;
        }
        .sidebar-cliente.menu-abierto {
            transform: translateX(0);
        }
        .sidebar-cliente {
            transform: translateX(0);
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
            padding: 18px 24px;
            color: #0074D9;
            text-decoration: none;
            font-size: 1.15em;
            border-radius: 10px;
            transition: background 0.2s, color 0.2s;
            font-weight: 600;
        }
        .sidebar-cliente .menu-link:hover, .sidebar-cliente .menu-link.active {
            background: #e6f2fb;
            color: #0056b3;
        }
        .sidebar-cliente .logout-link {
            color: #e74c3c;
            font-weight: 700;
            margin-top: 24px;
        }
        .form-section, .card-section {
            background: #fff;
            border-radius: 18px;
            padding: 28px 18px;
            margin-bottom: 28px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            border: 1.5px solid #e6ecf3;
        }
        .form-section h2, .card-section h2 {
            color: #0074D9;
            margin-bottom: 20px;
            font-size: 1.3em;
        }
        .empty-state {
            text-align: center;
            color: #bfc9d1;
            padding: 40px 20px;
        }
        .panel-btn, .btn, button {
            font-size: 1.15em;
            padding: 18px 0;
            border-radius: 12px;
            width: 100%;
            margin: 16px 0 0 0;
        }
        .navbar-fixed {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 56px;
            background: #fff;
            z-index: 10000;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        .navbar-nombre {
            position: fixed;
            top: 56px; left: 0; width: 100vw;
            background: #fff;
            z-index: 9999;
            text-align: center;
            padding: 8px 0 6px 0;
            font-family: 'Montserrat',Arial,sans-serif;
            font-size: 1.15em;
            font-weight: 600;
            color: #0074D9;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        @media (max-width: 900px) {
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
                padding: 18px 0;
            }
            .cliente-main {
                margin-left: 64px;
            }
        }
        @media (max-width: 600px) {
            .navbar-fixed {
                height: 56px;
                font-size: 1.1em;
            }
            .navbar-nombre {
                top: 56px;
                font-size: 1.1em;
            }
            .hamburger-menu { display: none !important; }
            .sidebar-cliente {
                display: none !important;
            }
            .menu-carrusel {
                position: fixed;
                top: 104px;
                left: 0;
                width: 100vw;
                background: #fff;
                z-index: 9998;
                display: flex;
                flex-direction: row;
                overflow-x: auto;
                gap: 8px;
                padding: 10px 0 6px 0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                border-bottom: 1.5px solid #e6ecf3;
                scrollbar-width: none;
            }
            .menu-carrusel::-webkit-scrollbar {
                display: none;
            }
            .carrusel-item {
                flex: 0 0 auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 80px;
                margin: 0 2px;
                text-decoration: none;
                color: #0074D9;
                font-family: 'Montserrat', Arial, sans-serif;
                font-size: 0.98em;
                font-weight: 600;
                border-radius: 16px;
                padding: 6px 0 0 0;
                transition: background 0.18s, color 0.18s;
            }
            .carrusel-item:active, .carrusel-item:focus, .carrusel-item:hover {
                background: #e6f2fb;
                color: #0056b3;
            }
            .carrusel-icon {
                font-size: 2.1em;
                margin-bottom: 2px;
                display: block;
            }
            .carrusel-text {
                font-size: 0.98em;
                margin-top: 2px;
                text-align: center;
                white-space: nowrap;
            }
            .cliente-main {
                padding-top: 160px !important;
            }
            .tabs, .tab-btn { display: none !important; }
        }
        /* Estilos para el icono de WhatsApp en el carrusel */
        .carrusel-item[href*="wa.me"] {
            color: #25D366;
        }
        .carrusel-item[href*="wa.me"]:hover {
            color: #128C7E;
        }
        .food-btn-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1 1 0;
        }
        .food-icon {
            background: #fff;
            border: 2px solid #e6ecf3;
            border-radius: 50%;
            width: 54px;
            height: 54px;
            font-size: 2em;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
            cursor: pointer;
            color: #23272f;
        }
        .food-icon.active, .food-icon:focus, .food-icon:hover {
            border: 2.5px solid #0074D9;
            box-shadow: 0 4px 16px rgba(0,116,217,0.10);
        }
        .food-label {
            font-size: 0.98em;
            color: #0074D9;
            font-weight: 600;
            text-align: center;
            margin-top: 0px;
            margin-bottom: 2px;
            letter-spacing: 0.2px;
        }
        @media (max-width: 600px) {
            .bottom-bar { background: #fff !important; }
            .bottom-bar-icons { gap: 2px !important; background: #fff !important; }
            .food-icon { width: 44px; height: 44px; font-size: 1.5em; }
            .food-label { font-size: 0.92em; }
        }
        /* Estilos tarjetas dashboard */
        .dashboard-cards { display:flex; flex-wrap:wrap; gap:20px; justify-content:center; margin-bottom:32px; }
        .dashboard-card { background:#fff; border-radius:18px; padding:22px 20px; width:220px; box-shadow:0 4px 16px rgba(0,0,0,0.08); border:1.5px solid #e6ecf3; text-align:center; }
        .card-title { font-size:1em; font-weight:600; color:#0074D9; margin-bottom:8px; }
        .card-value { font-size:1.9em; font-weight:700; color:#23272f; }
        .card-sub { font-size:0.8em; }
        @media(max-width:600px){ .dashboard-card{width:160px;padding:18px 12px;} .card-value{font-size:1.5em;} }
    </style>
</head>
<body>
    <?php include 'header_cliente.php'; ?>
    <?php include 'menu_lateral_cliente.php'; ?>
    
    <main class="cliente-main">
        <div class="bienvenida-cliente">Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></div>

        <!-- Tarjetas de resumen -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <div class="card-title">Próximo entrenamiento</div>
                <div class="card-value"><?= htmlspecialchars($next_workout) ?></div>
            </div>
            <div class="dashboard-card">
                <div class="card-title">Calorías de hoy</div>
                <div class="card-value"><?= is_numeric($calorias_hoy) ? $calorias_hoy.' kcal' : $calorias_hoy ?></div>
            </div>
            <div class="dashboard-card">
                <div class="card-title">Peso corporal</div>
                <div class="card-value">
                    <?php if($peso_actual !== null): ?>
                        <?= $peso_actual ?> kg
                        <?php if($peso_diff !== null): ?>
                            <span class="card-sub" style="display:block; color:<?= $peso_diff==0?'#9aa5b1':($peso_diff<0?'#27ae60':'#e74c3c'); ?>; font-size:0.75em;">
                                <?= $peso_diff>0?'+':'' ?><?= number_format($peso_diff,1) ?> kg desde última medición
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-title">Racha activa</div>
                <div class="card-value"><?= $racha_dias ?> días</div>
            </div>
        </div>

        <section class="card-section panel-control">
            <h2>Panel de Control</h2>
            <ul>
                <li><a href="cliente_dieta.php">🍽️ Ver Dieta</a></li>
                <li><a href="cliente_rutina.php">💪 Ver Rutina</a></li>
                <li><a href="cliente_resultados.php">📊 Ver Resultados</a></li>
            </ul>
        </section>
    </main>

    <script>
        function toggleMenu() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('menu-abierto');
        }
    </script>
</body>
</html> 