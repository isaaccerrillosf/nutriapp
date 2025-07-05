<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'nutriologo') {
    header('Location: login.php');
    exit();
}
$nutriologo_id = $_SESSION['usuario_id'];
$nombre = $_SESSION['usuario_nombre'] ?? 'Nutriólogo';
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
// Obtener clientes asignados
$clientes = [];
$result = $conn->query("SELECT u.id, u.nombre, u.foto, u.altura, u.peso, u.edad FROM usuarios u INNER JOIN nutriologo_cliente nc ON u.id = nc.cliente_id WHERE nc.nutriologo_id = $nutriologo_id ORDER BY u.nombre");
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}
$total_clientes = count($clientes);
// Paginación por query param
$cliente_idx = isset($_GET['cliente']) ? intval($_GET['cliente']) : 0;
if ($cliente_idx < 0) $cliente_idx = 0;
if ($cliente_idx >= $total_clientes) $cliente_idx = $total_clientes-1;
$cliente = $clientes[$cliente_idx] ?? null;
$objetivos = [];
$pendientes = [];
$seguimiento_ejercicios = [];

if ($cliente) {
    // Obtener seguimiento de ejercicios del cliente para hoy
    $fecha_hoy = date('Y-m-d');
    $stmt = $conn->prepare('
        SELECT se.ejercicio_id, se.completado, se.fecha_completado, e.nombre as ejercicio_nombre
        FROM seguimiento_ejercicios se
        INNER JOIN ejercicios e ON se.ejercicio_id = e.id
        WHERE se.cliente_id = ? AND se.fecha_ejercicio = ?
        ORDER BY se.fecha_completado DESC
    ');
    $stmt->bind_param('is', $cliente['id'], $fecha_hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $seguimiento_ejercicios[] = $row;
    }
    $stmt->close();
    
    // Obtener estadísticas de ejercicios de la semana
    $fecha_inicio_semana = date('Y-m-d', strtotime('monday this week'));
    $fecha_fin_semana = date('Y-m-d', strtotime('sunday this week'));
    
    $stmt = $conn->prepare('
        SELECT COUNT(*) as total_ejercicios, SUM(se.completado) as ejercicios_completados
        FROM seguimiento_ejercicios se
        WHERE se.cliente_id = ? AND se.fecha_ejercicio BETWEEN ? AND ?
    ');
    $stmt->bind_param('iss', $cliente['id'], $fecha_inicio_semana, $fecha_fin_semana);
    $stmt->execute();
    $stats_semana = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Generar objetivos basados en el progreso real
    $total_semana = $stats_semana['total_ejercicios'] ?? 0;
    $completados_semana = $stats_semana['ejercicios_completados'] ?? 0;
    $porcentaje_semana = $total_semana > 0 ? round(($completados_semana / $total_semana) * 100) : 0;
    
    $objetivos = [
        'Ejercicios esta semana' => $completados_semana . ' / ' . $total_semana,
        'Progreso semanal' => $porcentaje_semana . '%',
        'Consistencia' => $porcentaje_semana >= 80 ? 'Excelente' : ($porcentaje_semana >= 60 ? 'Buena' : 'Necesita mejorar')
    ];
    
    // Generar actividades pendientes basadas en ejercicios no completados hoy
    $pendientes = [];
    foreach ($seguimiento_ejercicios as $ejercicio) {
        if (!$ejercicio['completado']) {
            $pendientes[] = [
                'actividad' => $ejercicio['ejercicio_nombre'],
                'fecha' => 'Hoy',
                'completado' => false
            ];
        }
    }
    
    // Si no hay ejercicios pendientes, mostrar mensaje
    if (empty($pendientes)) {
        $pendientes = [
            ['actividad' => '¡Todos los ejercicios completados!', 'fecha' => 'Hoy', 'completado' => true]
        ];
    }
} else {
    // Valores por defecto si no hay cliente seleccionado
    $objetivos = [
        'Running' => '7km / 10km',
        'Perder peso' => '2kg / 5kg',
        'Meditación' => '24 días / 30 días'
    ];
    $pendientes = [
        ['actividad' => 'Cardio HIIT', 'fecha' => '22-04-2024 08:00 am', 'completado' => true],
        ['actividad' => 'Natación', 'fecha' => '23-04-2024 16:00 pm', 'completado' => false]
    ];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Nutriólogo</title>
    <?php include 'includes/pwa-head.php'; ?>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
</head>
<body class="nutriologo-bg">
    <div class="nutriologo-layout">
        <?php include 'menu_lateral_nutriologo.php'; ?>
        <main class="nutriologo-main-dashboard">
            <header class="nutriologo-header-dashboard nutriologo-header-appmovil" style="background:none;">
                <div class="header-movil-content" style="display:flex;align-items:center;gap:16px;">
                    <img src="logo.png" alt="Logo" style="height:38px;width:auto;max-width:90px;object-fit:contain;">
                    <h1 style="margin:0;">Hola, <?= htmlspecialchars($nombre) ?></h1>
                </div>
                <p class="nutriologo-bienvenida" style="color:#fff;margin-left:54px;">¡Es un buen día para ayudar a tus clientes!</p>
            </header>
            <section class="nutriologo-panels-row nutriologo-panels-row-movil">
                <div class="nutriologo-panel-card">
                    <div class="panel-card-title">Clientes</div>
                    <div class="panel-card-value"><?= $total_clientes ?></div>
                </div>
                <div class="nutriologo-panel-card">
                    <div class="panel-card-title">Citas próximas</div>
                    <div class="panel-card-value">3</div>
                </div>
                <div class="nutriologo-panel-card">
                    <div class="panel-card-title">Planes activos</div>
                    <div class="panel-card-value">8</div>
                </div>
                <div class="nutriologo-panel-card">
                    <div class="panel-card-title">Rutinas activas</div>
                    <div class="panel-card-value">6</div>
                </div>
            </section>
            <section class="nutriologo-panels-row">
                <div class="nutriologo-panel-wide">
                    <div class="panel-wide-title">Progreso de clientes</div>
                    <canvas id="graficaProgreso" height="120"></canvas>
                </div>
                <div class="nutriologo-panel-wide">
                    <div class="panel-wide-title">Estadísticas generales</div>
                    <div class="panel-wide-stats">
                        <div class="stat-item"><span class="stat-label">Planes asignados</span><span class="stat-value">24</span></div>
                        <div class="stat-item"><span class="stat-label">Rutinas asignadas</span><span class="stat-value">19</span></div>
                        <div class="stat-item"><span class="stat-label">Resultados subidos</span><span class="stat-value">15</span></div>
                    </div>
                </div>
            </section>
            <?php include 'nutriologo_carrusel.php'; ?>
        </main>
        <aside class="nutriologo-aside">
            <?php if ($cliente): ?>
            <div class="aside-cliente-card">
                <div style="display:flex;justify-content:space-between;width:100%;align-items:center;margin-bottom:8px;">
                    <a href="?cliente=<?= $cliente_idx-1 ?>" class="btn-paginacion" style="<?= $cliente_idx<=0?'visibility:hidden;':'' ?>">&#8592;</a>
                    <span style="font-size:0.98em;color:#2563eb;opacity:0.7;">Cliente <?= $cliente_idx+1 ?> de <?= $total_clientes ?></span>
                    <a href="?cliente=<?= $cliente_idx+1 ?>" class="btn-paginacion" style="<?= $cliente_idx>=($total_clientes-1)?'visibility:hidden;':'' ?>">&#8594;</a>
                </div>
                <img src="<?= $cliente['foto'] ? htmlspecialchars($cliente['foto']) : 'https://randomuser.me/api/portraits/men/32.jpg' ?>" alt="Foto cliente" class="aside-cliente-foto">
                <div class="aside-cliente-nombre"><?= htmlspecialchars($cliente['nombre']) ?></div>
                <div class="aside-cliente-datos">
                    <span>Altura: <b><?= htmlspecialchars($cliente['altura'] ?? '-') ?></b></span>
                    <span>Peso: <b><?= htmlspecialchars($cliente['peso'] ?? '-') ?></b></span>
                    <span>Edad: <b><?= htmlspecialchars($cliente['edad'] ?? '-') ?></b></span>
                </div>
                <div class="aside-cliente-botones">
                    <a href="nutriologo_plan.php?cliente_id=<?= $cliente['id'] ?>" class="btn-cliente-funcion">Plan</a>
                    <a href="nutriologo_rutina.php?cliente_id=<?= $cliente['id'] ?>" class="btn-cliente-funcion">Rutina</a>
                    <a href="nutriologo_resultados.php?cliente_id=<?= $cliente['id'] ?>" class="btn-cliente-funcion">Resultados</a>
                </div>
                <div class="aside-cliente-objetivos">
                    <div class="objetivos-title">Objetivos Mensuales</div>
                    <?php foreach ($objetivos as $k=>$v): ?>
                        <div class="objetivo-item"><span><?= $k ?></span><span><?= $v ?></span></div>
                    <?php endforeach; ?>
                </div>
                <div class="aside-cliente-pendientes">
                    <div class="pendientes-title">Actividades Pendientes</div>
                    <?php foreach ($pendientes as $p): ?>
                        <div class="pendiente-item">
                            <input type="checkbox" <?= $p['completado']?'checked':'' ?> disabled> <?= htmlspecialchars($p['actividad']) ?> <span class="pendiente-fecha"><?= $p['fecha'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($seguimiento_ejercicios)): ?>
                <div class="aside-cliente-progreso">
                    <div class="progreso-title">Progreso de Ejercicios - Hoy</div>
                    <?php 
                    $total_ejercicios_hoy = count($seguimiento_ejercicios);
                    $ejercicios_completados_hoy = 0;
                    foreach ($seguimiento_ejercicios as $ejercicio) {
                        if ($ejercicio['completado']) {
                            $ejercicios_completados_hoy++;
                        }
                    }
                    $porcentaje_hoy = $total_ejercicios_hoy > 0 ? round(($ejercicios_completados_hoy / $total_ejercicios_hoy) * 100) : 0;
                    ?>
                    <div class="progreso-stats">
                        <div class="progreso-stat">
                            <span class="progreso-label">Completados:</span>
                            <span class="progreso-value"><?= $ejercicios_completados_hoy ?>/<?= $total_ejercicios_hoy ?></span>
                        </div>
                        <div class="progreso-stat">
                            <span class="progreso-label">Progreso:</span>
                            <span class="progreso-value"><?= $porcentaje_hoy ?>%</span>
                        </div>
                    </div>
                    <div class="progreso-bar">
                        <div class="progreso-fill" style="width: <?= $porcentaje_hoy ?>%"></div>
                    </div>
                    <div class="ejercicios-detalle">
                        <?php foreach ($seguimiento_ejercicios as $ejercicio): ?>
                            <div class="ejercicio-item-progreso">
                                <span class="ejercicio-nombre-progreso"><?= htmlspecialchars($ejercicio['ejercicio_nombre']) ?></span>
                                <span class="ejercicio-estado <?= $ejercicio['completado'] ? 'completado' : 'pendiente' ?>">
                                    <?= $ejercicio['completado'] ? '✅' : '⏳' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <button class="btn-nueva-actividad">Registrar Nueva Actividad</button>
            </div>
            <?php else: ?>
                <div style="text-align:center;color:#888;font-size:1.1em;padding:40px 0;">No tienes clientes asignados.</div>
            <?php endif; ?>
        </aside>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Ejemplo de gráfica con Chart.js
    const ctx = document.getElementById('graficaProgreso').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Progreso',
                data: [60, 70, 80, 90, 100, 110, 120],
                backgroundColor: '#0074D9',
                borderRadius: 8
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    
    // Funcionalidad del carrusel móvil
    document.addEventListener('DOMContentLoaded', function() {
        // Marcar elemento activo en el carrusel
        const currentPage = window.location.pathname.split('/').pop();
        const carruselItems = document.querySelectorAll('.carrusel-item-nutri');
        
        carruselItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && href.includes(currentPage)) {
                item.classList.add('active');
            }
        });
        
        // Scroll suave del carrusel
        const carrusel = document.querySelector('.menu-carrusel-nutri');
        if (carrusel) {
            // Scroll automático al elemento activo
            const activeItem = carrusel.querySelector('.active');
            if (activeItem) {
                activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }
    });
    </script>
    <?php include 'includes/pwa-scripts.php'; ?>
</body>
</html> 