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
// Obtener ejercicios agrupados por grupo muscular
$ejercicios = [];
$result = $conn->query("SELECT id, nombre, descripcion, grupo_muscular FROM ejercicios ORDER BY grupo_muscular, nombre");
while ($row = $result->fetch_assoc()) {
    $ejercicios[] = $row;
}
$ejercicios_por_categoria = [];
foreach ($ejercicios as $e) {
    $ejercicios_por_categoria[$e['grupo_muscular']][] = $e;
}
// Grupos musculares
$grupos_musculares = [
    'Pecho',
    'Espalda',
    'Hombros',
    'Bíceps',
    'Tríceps',
    'Piernas',
    'Glúteos',
    'Abdominales',
    'Antebrazos',
    'Pantorrillas'
];
// Asignar rutina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_rutina'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $instrucciones = $_POST['instrucciones'] ?? '';
    $dias_semana = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
    if ($cliente_id && $fecha) {
        $stmt = $conn->prepare('INSERT INTO rutinas_ejercicio (cliente_id, nutriologo_id, fecha, instrucciones) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiss', $cliente_id, $nutriologo_id, $fecha, $instrucciones);
        $stmt->execute();
        $rutina_id = $stmt->insert_id;
        $stmt->close();
        // Guardar ejercicios para grupo 1
        foreach ($dias_semana as $dia) {
            $ejercicios_dia = $_POST[$dia . '_1'] ?? [];
            $grupo_muscular = $_POST['grupo_muscular_' . $dia . '_1'] ?? '';
            foreach ($ejercicios_dia as $ejercicio_id) {
                $series = $_POST['series_' . $dia . '_1_' . $ejercicio_id] ?? '3';
                $repeticiones = $_POST['repeticiones_' . $dia . '_1_' . $ejercicio_id] ?? '10';
                $dia_semana_val = ucfirst($dia);
                $tipo_grupo = 1;
                $stmt = $conn->prepare('INSERT INTO rutina_ejercicios (rutina_id, ejercicio_id, dia_semana, grupo_muscular, series, repeticiones, tipo_grupo) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('iisssii', $rutina_id, $ejercicio_id, $dia_semana_val, $grupo_muscular, $series, $repeticiones, $tipo_grupo);
                $stmt->execute();
                $stmt->close();
            }
        }
        // Guardar ejercicios para grupo 2
        foreach ($dias_semana as $dia) {
            $ejercicios_dia = $_POST[$dia . '_2'] ?? [];
            $grupo_muscular = $_POST['grupo_muscular_' . $dia . '_2'] ?? '';
            foreach ($ejercicios_dia as $ejercicio_id) {
                $series = $_POST['series_' . $dia . '_2_' . $ejercicio_id] ?? '3';
                $repeticiones = $_POST['repeticiones_' . $dia . '_2_' . $ejercicio_id] ?? '10';
                $dia_semana_val = ucfirst($dia);
                $tipo_grupo = 2;
                $stmt = $conn->prepare('INSERT INTO rutina_ejercicios (rutina_id, ejercicio_id, dia_semana, grupo_muscular, series, repeticiones, tipo_grupo) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('iisssii', $rutina_id, $ejercicio_id, $dia_semana_val, $grupo_muscular, $series, $repeticiones, $tipo_grupo);
                $stmt->execute();
                $stmt->close();
            }
        }
        $mensaje = 'Rutina de ejercicio asignada correctamente.';
    } else {
        $mensaje = 'Selecciona cliente y fecha.';
    }
}
// Filtro de cliente para ver rutinas
$cliente_filtro = $_GET['cliente_id'] ?? '';
$rutinas = [];
if ($cliente_filtro) {
    $stmt = $conn->prepare('SELECT id, fecha, instrucciones FROM rutinas_ejercicio WHERE cliente_id = ? AND nutriologo_id = ? ORDER BY fecha DESC');
    $stmt->bind_param('ii', $cliente_filtro, $nutriologo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rutinas[] = $row;
    }
    $stmt->close();
}
// Obtener ejercicios de cada rutina
$ejercicios_rutina = [];
foreach ($rutinas as $rutina) {
    $stmt = $conn->prepare('SELECT e.nombre, e.descripcion, re.dia_semana, re.series, re.repeticiones, re.tipo_grupo, re.grupo_muscular FROM rutina_ejercicios re INNER JOIN ejercicios e ON re.ejercicio_id = e.id WHERE re.rutina_id = ?');
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
    <title>Rutina de Ejercicio | Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .form-rutina { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1.5px solid #e6ecf3; padding: 18px 14px 14px 14px; margin-bottom: 24px; max-width: 900px; }
        .form-rutina label { font-weight: 600; color: #23272f; margin-bottom: 6px; display: block; }
        .form-rutina input, .form-rutina select, .form-rutina textarea { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e6ecf3; font-size: 1em; margin-bottom: 12px; }
        .form-rutina textarea { min-height: 80px; resize: vertical; }
        .grupos-musculares { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .rutinas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .rutina-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e6ecf3; }
        .rutina-fecha { color: #0074D9; font-weight: 600; margin-bottom: 12px; font-size: 1.1em; }
        .rutina-info { background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 12px; }
        .grupos-musculares-info { color: #0074D9; font-weight: 600; margin-bottom: 8px; }
        .instrucciones-info { color: #666; font-style: italic; margin-bottom: 8px; }
        .dia-seccion { margin-bottom: 12px; }
        .dia-titulo { color: #23272f; font-weight: 600; margin-bottom: 6px; }
        .ejercicio-lista { list-style: none; padding: 0; margin: 0; }
        .ejercicio-lista li { padding: 3px 0; color: #666; }
        .categoria-buttons { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
        .categoria-btn { padding: 8px 16px; border: 1px solid #0074D9; background: #fff; color: #0074D9; border-radius: 6px; cursor: pointer; font-size: 0.9em; }
        .categoria-btn.active { background: #0074D9; color: #fff; }
        .categoria-btn:hover { background: #e6f2fb; }
        .categoria-btn.active:hover { background: #0056b3; }
        .ejercicio-selector { background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e6ecf3; margin-bottom: 10px; }
        .ejercicio-row { display: grid; grid-template-columns: 1fr 120px 120px 80px; gap: 8px; align-items: center; margin-bottom: 8px; }
        .ejercicio-row select, .ejercicio-row input { margin: 0; }
        .btn-agregar { background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 0.9em; }
        .btn-agregar:hover { background: #218838; }
        .btn-eliminar { background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8em; }
        .btn-eliminar:hover { background: #c82333; }
        .ejercicios-agregados { margin-top: 10px; }
        .ejercicio-item { background: #fff; padding: 8px; border-radius: 6px; border: 1px solid #e6ecf3; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
        .ejercicio-info { flex: 1; }
        .ejercicio-nombre { font-weight: 600; color: #23272f; }
        .ejercicio-series { color: #0074D9; font-size: 0.9em; }
    </style>
</head>
<body>
<?php include 'header_nutriologo.php'; ?>
<?php include 'menu_lateral_nutriologo.php'; ?>
<?php include 'nutriologo_carrusel.php'; ?>
<main class="nutriologo-main">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;text-align:center;">Asignar Rutina de Ejercicio</h1>
    <?php if ($mensaje): ?>
        <div class="mensaje" style="background:#e6f2fb;color:#0074D9;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-weight:600;text-align:center;"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>
    <form class="form-rutina" method="post">
        <h2 style="color:#0074D9;font-size:1.1em;margin-bottom:12px;">Nueva Rutina</h2>
        <label>Cliente:
            <select name="cliente_id" required>
                <option value="">Selecciona cliente</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $cliente_filtro==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Fecha:
            <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>">
        </label>
        <label>Instrucciones Generales:
            <textarea name="instrucciones" placeholder="Escribe las instrucciones generales para la rutina..."></textarea>
        </label>
        <!-- Rutina para Grupo Muscular Principal -->
        <h3 style="color:#0074D9;margin-top:24px;">Ejercicios para Grupo Muscular Principal</h3>
        <div class="tabs-container grupo1">
            <div class="tabs-nav">
                <button type="button" class="tab-btn active" data-tab="lunes_1">Lunes</button>
                <button type="button" class="tab-btn" data-tab="martes_1">Martes</button>
                <button type="button" class="tab-btn" data-tab="miercoles_1">Miércoles</button>
                <button type="button" class="tab-btn" data-tab="jueves_1">Jueves</button>
                <button type="button" class="tab-btn" data-tab="viernes_1">Viernes</button>
                <button type="button" class="tab-btn" data-tab="sabado_1">Sábado</button>
                <button type="button" class="tab-btn" data-tab="domingo_1">Domingo</button>
            </div>
            <?php
            $dias_semana = [
                'lunes' => 'Lunes',
                'martes' => 'Martes',
                'miercoles' => 'Miércoles',
                'jueves' => 'Jueves',
                'viernes' => 'Viernes',
                'sabado' => 'Sábado',
                'domingo' => 'Domingo'
            ];
            foreach ($dias_semana as $dia => $label): ?>
            <div class="tab-content <?= $dia === 'lunes' ? 'active' : '' ?>" id="tab-<?= $dia ?>_1">
                <h4 style="color:#0074D9;"><?= $label ?></h4>
                <div class="ejercicio-selector">
                    <label style="margin-bottom:8px;display:block;">Grupo muscular para este día:
                        <select class="grupo-muscular-select" name="grupo_muscular_<?= $dia ?>_1">
                            <option value="">Selecciona grupo muscular</option>
                            <?php foreach ($grupos_musculares as $grupo): ?>
                                <option value="<?= $grupo ?>"><?= $grupo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="categoria-grid">
                        <?php foreach ($grupos_musculares as $index => $grupo): ?>
                            <div class="categoria-item" data-categoria="<?= $index ?>" data-dia="<?= $dia ?>_1">
                                <?php
                                // Imágenes para cada grupo muscular
                                $imagen_grupo = '';
                                switch ($grupo) {
                                    case 'Pecho':
                                        $imagen_grupo = 'fotos_ejercicios/pecho.jpg';
                                        break;
                                    case 'Espalda':
                                        $imagen_grupo = 'fotos_ejercicios/espalda.jpg';
                                        break;
                                    case 'Hombros':
                                        $imagen_grupo = 'fotos_ejercicios/hombros.jpg';
                                        break;
                                    case 'Bíceps':
                                        $imagen_grupo = 'fotos_ejercicios/biceps.jpg';
                                        break;
                                    case 'Tríceps':
                                        $imagen_grupo = 'fotos_ejercicios/triceps.jpg';
                                        break;
                                    case 'Piernas':
                                        $imagen_grupo = 'fotos_ejercicios/piernas.jpg';
                                        break;
                                    case 'Glúteos':
                                        $imagen_grupo = 'fotos_ejercicios/gluteos.jpg';
                                        break;
                                    case 'Abdominales':
                                        $imagen_grupo = 'fotos_ejercicios/abdomen.jpg';
                                        break;
                                    case 'Antebrazos':
                                        $imagen_grupo = 'fotos_ejercicios/antebrazos.jpg';
                                        break;
                                    case 'Pantorrillas':
                                        $imagen_grupo = 'fotos_ejercicios/pantorrillas.jpg';
                                        break;
                                    default:
                                        $imagen_grupo = 'fotos_ejercicios/default.jpg';
                                        break;
                                }
                                ?>
                                <img src="<?= $imagen_grupo ?>" alt="<?= $grupo ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:2px solid #e6ecf3;" onerror="this.src='fotos_ejercicios/default.jpg'">
                                <span><?= $grupo ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ejercicio-row">
                        <select class="ejercicio-select" data-dia="<?= $dia ?>_1">
                            <option value="">Selecciona ejercicio</option>
                        </select>
                        <input type="text" class="series-input" placeholder="Series" value="3">
                        <input type="text" class="repeticiones-input" placeholder="Repeticiones" value="10">
                        <button type="button" class="btn-azul" data-dia="<?= $dia ?>_1">Agregar</button>
                    </div>
                    <div class="ejercicios-agregados" id="ejercicios-<?= $dia ?>_1"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Rutina para Grupo Muscular Secundario -->
        <h3 style="color:#0074D9;margin-top:32px;">Ejercicios para Grupo Muscular Secundario</h3>
        <div class="tabs-container grupo2">
            <div class="tabs-nav">
                <button type="button" class="tab-btn active" data-tab="lunes_2">Lunes</button>
                <button type="button" class="tab-btn" data-tab="martes_2">Martes</button>
                <button type="button" class="tab-btn" data-tab="miercoles_2">Miércoles</button>
                <button type="button" class="tab-btn" data-tab="jueves_2">Jueves</button>
                <button type="button" class="tab-btn" data-tab="viernes_2">Viernes</button>
                <button type="button" class="tab-btn" data-tab="sabado_2">Sábado</button>
                <button type="button" class="tab-btn" data-tab="domingo_2">Domingo</button>
            </div>
            <?php foreach ($dias_semana as $dia => $label): ?>
            <div class="tab-content <?= $dia === 'lunes' ? 'active' : '' ?>" id="tab-<?= $dia ?>_2">
                <h4 style="color:#0074D9;"><?= $label ?></h4>
                <div class="ejercicio-selector">
                    <label style="margin-bottom:8px;display:block;">Grupo muscular para este día:
                        <select class="grupo-muscular-select" name="grupo_muscular_<?= $dia ?>_2">
                            <option value="">Selecciona grupo muscular</option>
                            <?php foreach ($grupos_musculares as $grupo): ?>
                                <option value="<?= $grupo ?>"><?= $grupo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="categoria-grid">
                        <?php foreach ($grupos_musculares as $index => $grupo): ?>
                            <div class="categoria-item" data-categoria="<?= $index ?>" data-dia="<?= $dia ?>_2">
                                <?php
                                // Imágenes para cada grupo muscular
                                $imagen_grupo = '';
                                switch ($grupo) {
                                    case 'Pecho':
                                        $imagen_grupo = 'fotos_ejercicios/pecho.jpg';
                                        break;
                                    case 'Espalda':
                                        $imagen_grupo = 'fotos_ejercicios/espalda.jpg';
                                        break;
                                    case 'Hombros':
                                        $imagen_grupo = 'fotos_ejercicios/hombros.jpg';
                                        break;
                                    case 'Bíceps':
                                        $imagen_grupo = 'fotos_ejercicios/biceps.jpg';
                                        break;
                                    case 'Tríceps':
                                        $imagen_grupo = 'fotos_ejercicios/triceps.jpg';
                                        break;
                                    case 'Piernas':
                                        $imagen_grupo = 'fotos_ejercicios/piernas.jpg';
                                        break;
                                    case 'Glúteos':
                                        $imagen_grupo = 'fotos_ejercicios/gluteos.jpg';
                                        break;
                                    case 'Abdominales':
                                        $imagen_grupo = 'fotos_ejercicios/abdomen.jpg';
                                        break;
                                    case 'Antebrazos':
                                        $imagen_grupo = 'fotos_ejercicios/antebrazos.jpg';
                                        break;
                                    case 'Pantorrillas':
                                        $imagen_grupo = 'fotos_ejercicios/pantorrillas.jpg';
                                        break;
                                    default:
                                        $imagen_grupo = 'fotos_ejercicios/default.jpg';
                                        break;
                                }
                                ?>
                                <img src="<?= $imagen_grupo ?>" alt="<?= $grupo ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:2px solid #e6ecf3;" onerror="this.src='fotos_ejercicios/default.jpg'">
                                <span><?= $grupo ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ejercicio-row">
                        <select class="ejercicio-select" data-dia="<?= $dia ?>_2">
                            <option value="">Selecciona ejercicio</option>
                        </select>
                        <input type="text" class="series-input" placeholder="Series" value="3">
                        <input type="text" class="repeticiones-input" placeholder="Repeticiones" value="10">
                        <button type="button" class="btn-azul" data-dia="<?= $dia ?>_2">Agregar</button>
                    </div>
                    <div class="ejercicios-agregados" id="ejercicios-<?= $dia ?>_2"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="asignar_rutina" class="btn-azul" style="margin-top: 20px;">Asignar Rutina</button>
    </form>
    <form class="filtros-resultados" method="get">
        <select name="cliente_id" onchange="this.form.submit()">
            <option value="">Ver rutinas de todos los clientes</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $cliente_filtro==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="rutinas-grid">
        <?php foreach ($rutinas as $rutina): ?>
        <div class="rutina-card">
            <div class="rutina-fecha">Fecha: <?= date('d/m/Y', strtotime($rutina['fecha'])) ?></div>
            <div class="rutina-info">
                <div class="instrucciones-info">
                    📝 <?= htmlspecialchars($rutina['instrucciones']) ?>
                </div>
            </div>
            <?php if (isset($ejercicios_rutina[$rutina['id']])): ?>
                <?php
                // Agrupar por día, tipo_grupo y grupo_muscular
                $dias = ['Lunes' => [], 'Martes' => [], 'Miercoles' => [], 'Jueves' => [], 'Viernes' => [], 'Sabado' => [], 'Domingo' => []];
                foreach ($ejercicios_rutina[$rutina['id']] as $ejercicio) {
                    $tipo = isset($ejercicio['tipo_grupo']) ? $ejercicio['tipo_grupo'] : 1;
                    $grupo = isset($ejercicio['grupo_muscular']) ? $ejercicio['grupo_muscular'] : '';
                    $dias[$ejercicio['dia_semana']][$tipo][$grupo][] = $ejercicio;
                }
                ?>
                <?php foreach ($dias as $dia => $tipos): ?>
                    <?php foreach ([1,2] as $tipo): ?>
                        <?php if (!empty($tipos[$tipo])): ?>
                            <?php foreach ($tipos[$tipo] as $grupo_muscular => $ejercicios_dia): ?>
                                <div class="dia-seccion">
                                    <div class="dia-titulo">
                                        <?= $dia ?>:
                                        <span style="color:#0074D9;font-weight:600;">
                                            <?= $tipo == 1 ? 'Principal' : 'Secundario' ?><?= $grupo_muscular ? ' - ' . htmlspecialchars($grupo_muscular) : '' ?>
                                        </span>
                                    </div>
                                    <ul class="ejercicio-lista">
                                        <?php foreach ($ejercicios_dia as $ejercicio): ?>
                                            <li><?= htmlspecialchars($ejercicio['nombre']) ?> - <?= $ejercicio['series'] ?> series x <?= $ejercicio['repeticiones'] ?> rep</li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
// Datos de ejercicios por categoría
const ejerciciosPorCategoria = <?= json_encode($ejercicios_por_categoria) ?>;
const categorias = <?= json_encode($grupos_musculares) ?>;

// Función para cargar ejercicios de una categoría
function cargarEjercicios(categoriaIndex, dia) {
    const select = document.querySelector(`.ejercicio-select[data-dia="${dia}"]`);
    const categoria = categorias[categoriaIndex];
    const ejercicios = ejerciciosPorCategoria[categoria] || [];
    
    select.innerHTML = '<option value="">Selecciona ejercicio</option>';
    ejercicios.forEach(ejercicio => {
        const option = document.createElement('option');
        option.value = ejercicio.id;
        option.textContent = ejercicio.nombre;
        select.appendChild(option);
    });
}

// Función para cambiar pestañas
function cambiarTab(tabId) {
    // Ocultar todas las pestañas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    // Remover clase active de todos los botones de pestaña
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    // Mostrar pestaña seleccionada
    document.getElementById(`tab-${tabId}`).classList.add('active');
    // Activar botón de pestaña
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
}

// Event listeners para pestañas
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        cambiarTab(tabId);
    });
});

// Event listeners para elementos de categoría
document.querySelectorAll('.categoria-item').forEach(item => {
    item.addEventListener('click', function() {
        const categoriaIndex = this.dataset.categoria;
        const dia = this.dataset.dia;
        // Remover clase active de todos los elementos del mismo día
        document.querySelectorAll(`.categoria-item[data-dia="${dia}"]`).forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        cargarEjercicios(categoriaIndex, dia);
    });
});

// Event listeners para botones agregar
document.querySelectorAll('.btn-azul').forEach(btn => {
    btn.addEventListener('click', function() {
        const dia = this.dataset.dia;
        const row = this.closest('.ejercicio-row');
        const selectEjercicio = row.querySelector('.ejercicio-select');
        const inputSeries = row.querySelector('.series-input');
        const inputRepeticiones = row.querySelector('.repeticiones-input');
        if (selectEjercicio.value) {
            const ejercicioId = selectEjercicio.value;
            const ejercicioText = selectEjercicio.options[selectEjercicio.selectedIndex].text;
            const series = inputSeries.value;
            const repeticiones = inputRepeticiones.value;
            // Crear elemento de ejercicio agregado
            const ejercicioDiv = document.createElement('div');
            ejercicioDiv.className = 'ejercicio-item';
            ejercicioDiv.innerHTML = `
                <div class="ejercicio-info">
                    <div class="ejercicio-nombre">${ejercicioText}</div>
                    <div class="ejercicio-series">${series} series x ${repeticiones} rep</div>
                </div>
                <button type="button" class="btn-eliminar">Eliminar</button>
            `;
            // Agregar inputs hidden al formulario
            const inputEjercicio = document.createElement('input');
            inputEjercicio.type = 'hidden';
            inputEjercicio.name = `${dia}[]`;
            inputEjercicio.value = ejercicioId;
            const inputSeriesHidden = document.createElement('input');
            inputSeriesHidden.type = 'hidden';
            inputSeriesHidden.name = `series_${dia}_${ejercicioId}`;
            inputSeriesHidden.value = series;
            const inputRepeticionesHidden = document.createElement('input');
            inputRepeticionesHidden.type = 'hidden';
            inputRepeticionesHidden.name = `repeticiones_${dia}_${ejercicioId}`;
            inputRepeticionesHidden.value = repeticiones;
            document.querySelector('.form-rutina').appendChild(inputEjercicio);
            document.querySelector('.form-rutina').appendChild(inputSeriesHidden);
            document.querySelector('.form-rutina').appendChild(inputRepeticionesHidden);
            // Agregar al contenedor
            document.getElementById(`ejercicios-${dia}`).appendChild(ejercicioDiv);
            // Limpiar inputs
            selectEjercicio.value = '';
            inputSeries.value = '3';
            inputRepeticiones.value = '10';
            // Event listener para eliminar
            ejercicioDiv.querySelector('.btn-eliminar').addEventListener('click', function() {
                ejercicioDiv.remove();
                inputEjercicio.remove();
                inputSeriesHidden.remove();
                inputRepeticionesHidden.remove();
            });
        }
    });
});

// Inicializar primera categoría en cada pestaña
document.addEventListener('DOMContentLoaded', function() {
    // Activar primera categoría en cada pestaña
    document.querySelectorAll('.tab-content').forEach(tab => {
        const dia = tab.id.replace('tab-', '');
        const primeraCategoria = document.querySelector(`.categoria-item[data-dia="${dia}"]`);
        if (primeraCategoria) {
            primeraCategoria.click();
        }
    });
});
</script>
</body>
</html> 