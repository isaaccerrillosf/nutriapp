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
// Obtener ejercicios agrupados por categoría
$ejercicios = [];
$result = $conn->query("SELECT id, nombre, descripcion, grupo_muscular FROM ejercicios ORDER BY grupo_muscular, nombre");
while ($row = $result->fetch_assoc()) {
    $ejercicios[] = $row;
}
$ejercicios_por_categoria = [];
foreach ($ejercicios as $e) {
    $ejercicios_por_categoria[$e['grupo_muscular']][] = $e;
}
$categorias = [
    'Cardio',
    'Fuerza',
    'Flexibilidad',
    'Equilibrio',
    'Funcional'
];
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
// Obtener rutinas del cliente seleccionado
$cliente_filtro = $_GET['cliente_id'] ?? '';
$rutinas = [];
if ($cliente_filtro) {
    $stmt = $conn->prepare('SELECT id, fecha, instrucciones, grupo_muscular_1, grupo_muscular_2 FROM rutinas_ejercicio WHERE cliente_id = ? AND nutriologo_id = ? ORDER BY fecha DESC');
    $stmt->bind_param('ii', $cliente_filtro, $nutriologo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rutinas[] = $row;
    }
    $stmt->close();
}
// Obtener rutina específica a editar
$rutina_id = $_GET['rutina_id'] ?? '';
$rutina_actual = null;
$ejercicios_rutina = [];
if ($rutina_id) {
    $stmt = $conn->prepare('SELECT r.*, u.nombre as cliente_nombre FROM rutinas_ejercicio r INNER JOIN usuarios u ON r.cliente_id = u.id WHERE r.id = ? AND r.nutriologo_id = ?');
    $stmt->bind_param('ii', $rutina_id, $nutriologo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rutina_actual = $result->fetch_assoc();
    $stmt->close();
    if ($rutina_actual) {
        $stmt = $conn->prepare('SELECT re.*, e.nombre, e.descripcion FROM rutina_ejercicios re INNER JOIN ejercicios e ON re.ejercicio_id = e.id WHERE re.rutina_id = ?');
        $stmt->bind_param('i', $rutina_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ejercicios_rutina[] = $row;
        }
        $stmt->close();
    }
}
// Actualizar rutina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_rutina'])) {
    $rutina_id = intval($_POST['rutina_id']);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $instrucciones = $_POST['instrucciones'] ?? '';
    $grupo_muscular_1 = $_POST['grupo_muscular_1'] ?? '';
    $grupo_muscular_2 = $_POST['grupo_muscular_2'] ?? '';
    $dias_semana = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
    if ($rutina_id && $fecha) {
        // Actualizar datos de la rutina
        $stmt = $conn->prepare('UPDATE rutinas_ejercicio SET fecha = ?, instrucciones = ?, grupo_muscular_1 = ?, grupo_muscular_2 = ? WHERE id = ? AND nutriologo_id = ?');
        $stmt->bind_param('ssssii', $fecha, $instrucciones, $grupo_muscular_1, $grupo_muscular_2, $rutina_id, $nutriologo_id);
        $stmt->execute();
        $stmt->close();
        // Eliminar ejercicios actuales de la rutina
        $stmt = $conn->prepare('DELETE FROM rutina_ejercicios WHERE rutina_id = ?');
        $stmt->bind_param('i', $rutina_id);
        $stmt->execute();
        $stmt->close();
        // Insertar nuevos ejercicios
        foreach ($dias_semana as $dia) {
            $ejercicios_dia = $_POST[$dia] ?? [];
            foreach ($ejercicios_dia as $ejercicio_id) {
                $series = $_POST['series_' . $dia . '_' . $ejercicio_id] ?? '3';
                $repeticiones = $_POST['repeticiones_' . $dia . '_' . $ejercicio_id] ?? '10';
                $stmt = $conn->prepare('INSERT INTO rutina_ejercicios (rutina_id, ejercicio_id, dia_semana, series, repeticiones) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('iissi', $rutina_id, $ejercicio_id, ucfirst($dia), $series, $repeticiones);
                $stmt->execute();
                $stmt->close();
            }
        }
        $mensaje = 'Rutina de ejercicio actualizada correctamente.';
        // Recargar datos de la rutina
        $stmt = $conn->prepare('SELECT re.*, e.nombre, e.descripcion FROM rutina_ejercicios re INNER JOIN ejercicios e ON re.ejercicio_id = e.id WHERE re.rutina_id = ?');
        $stmt->bind_param('i', $rutina_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $ejercicios_rutina = [];
        while ($row = $result->fetch_assoc()) {
            $ejercicios_rutina[] = $row;
        }
        $stmt->close();
    } else {
        $mensaje = 'Error al actualizar la rutina.';
    }
}
// Manejo de borrado de rutina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_rutina'])) {
    $rutina_id = intval($_POST['rutina_id']);
    // Eliminar ejercicios asociados
    $stmt = $conn->prepare('DELETE FROM rutina_ejercicios WHERE rutina_id = ?');
    $stmt->bind_param('i', $rutina_id);
    $stmt->execute();
    $stmt->close();
    // Eliminar la rutina
    $stmt = $conn->prepare('DELETE FROM rutinas_ejercicio WHERE id = ? AND nutriologo_id = ?');
    $stmt->bind_param('ii', $rutina_id, $nutriologo_id);
    $stmt->execute();
    $stmt->close();
    // Redirigir a la lista de rutinas del cliente
    header('Location: nutriologo_editar_rutina.php?cliente_id=' . urlencode($cliente_filtro));
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Editar Rutina de Ejercicio | Nutriólogo</title>
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
        .btn-editar { background: #0074D9; color: white; padding: 8px 16px; border: none; border-radius: 6px; text-decoration: none; font-size: 0.9em; }
        .btn-editar:hover { background: #0056b3; }
        .categoria-buttons { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
        .categoria-btn { padding: 8px 16px; border: 1px solid #0074D9; background: #fff; color: #0074D9; border-radius: 6px; cursor: pointer; font-size: 0.9em; }
        .categoria-btn.active { background: #0056b3 !important; color: #fff !important; border-color: #003d80 !important; box-shadow: 0 2px 8px rgba(0,64,255,0.10); font-weight: 700; }
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
<main class="nutriologo-main" style="margin-left:100px;">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;text-align:center;">Editar Rutina de Ejercicio</h1>
    <?php if ($mensaje): ?>
        <div class="mensaje" style="background:#e6f2fb;color:#0074D9;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-weight:600;text-align:center;"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>
    <form class="filtros-resultados" method="get">
        <select name="cliente_id" onchange="this.form.submit()">
            <option value="">Selecciona cliente</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $cliente_filtro==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if ($cliente_filtro && !$rutina_id): ?>
    <div class="rutinas-grid">
        <?php foreach ($rutinas as $rutina): ?>
        <div class="rutina-card">
            <div class="rutina-fecha">Fecha: <?= date('d/m/Y', strtotime($rutina['fecha'])) ?></div>
            <div class="rutina-info">
                <div class="grupos-musculares-info">
                    🏋️ <?= htmlspecialchars($rutina['grupo_muscular_1']) ?> + <?= htmlspecialchars($rutina['grupo_muscular_2']) ?>
                </div>
                <?php if ($rutina['instrucciones']): ?>
                <div class="instrucciones-info">
                    📝 <?= htmlspecialchars($rutina['instrucciones']) ?>
                </div>
                <?php endif; ?>
            </div>
            <a href="?cliente_id=<?= $cliente_filtro ?>&rutina_id=<?= $rutina['id'] ?>" class="btn-editar">Editar Rutina</a>
            <form method="post" onsubmit="return confirm('¿Seguro que deseas borrar esta rutina? Esta acción no se puede deshacer.');" style="display:inline-block;margin-left:8px;">
                <input type="hidden" name="rutina_id" value="<?= $rutina['id'] ?>">
                <button type="submit" name="borrar_rutina" class="btn-eliminar" style="font-size:0.9em;padding:8px 16px;">Borrar</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($rutina_actual): ?>
    <form class="form-rutina" method="post">
        <h2 style="color:#0074D9;font-size:1.1em;margin-bottom:12px;">Editar Rutina - <?= htmlspecialchars($rutina_actual['cliente_nombre']) ?></h2>
        <input type="hidden" name="rutina_id" value="<?= $rutina_actual['id'] ?>">
        <label>Fecha:
            <input type="date" name="fecha" required value="<?= $rutina_actual['fecha'] ?>">
        </label>
        <div class="grupos-musculares">
            <label>Grupo Muscular Principal:
                <select name="grupo_muscular_1" required>
                    <option value="">Selecciona grupo muscular</option>
                    <?php foreach ($grupos_musculares as $grupo): ?>
                        <option value="<?= $grupo ?>" <?= $rutina_actual['grupo_muscular_1']==$grupo?'selected':'' ?>><?= $grupo ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Grupo Muscular Secundario:
                <select name="grupo_muscular_2" required>
                    <option value="">Selecciona grupo muscular</option>
                    <?php foreach ($grupos_musculares as $grupo): ?>
                        <option value="<?= $grupo ?>" <?= $rutina_actual['grupo_muscular_2']==$grupo?'selected':'' ?>><?= $grupo ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Instrucciones Generales:
            <textarea name="instrucciones" placeholder="Escribe las instrucciones generales para la rutina..."><?= htmlspecialchars($rutina_actual['instrucciones']) ?></textarea>
        </label>
        <?php
        $dias_semana = [
            'lunes' => '📅 <span style="color:#23272f;">Lunes</span>',
            'martes' => '📅 <span style="color:#23272f;">Martes</span>',
            'miercoles' => '📅 <span style="color:#23272f;">Miércoles</span>',
            'jueves' => '📅 <span style="color:#23272f;">Jueves</span>',
            'viernes' => '📅 <span style="color:#23272f;">Viernes</span>',
            'sabado' => '📅 <span style="color:#23272f;">Sábado</span>',
            'domingo' => '📅 <span style="color:#23272f;">Domingo</span>'
        ];
        // Crear array de ejercicios seleccionados por día
        $ejercicios_seleccionados = [];
        foreach ($ejercicios_rutina as $ejercicio) {
            $dia = strtolower($ejercicio['dia_semana']);
            $ejercicios_seleccionados[$dia][$ejercicio['ejercicio_id']] = [
                'series' => $ejercicio['series'],
                'repeticiones' => $ejercicio['repeticiones']
            ];
        }
        foreach ($dias_semana as $dia => $label): ?>
        <div class="form-group" style="margin-bottom: 20px;">
            <label><?= $label ?>:</label>
            <div class="ejercicio-selector">
                <div class="categoria-buttons">
                    <?php foreach ($categorias as $index => $cat): ?>
                        <button type="button" class="categoria-btn" data-categoria="<?= $index ?>" data-dia="<?= $dia ?>">
                            <?= $cat ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="ejercicio-row">
                    <select class="ejercicio-select" data-dia="<?= $dia ?>">
                        <option value="">Selecciona ejercicio</option>
                    </select>
                    <input type="text" class="series-input" placeholder="Series" value="3">
                    <input type="text" class="repeticiones-input" placeholder="Repeticiones" value="10">
                    <button type="button" class="btn-agregar" data-dia="<?= $dia ?>">Agregar</button>
                </div>
                <div class="ejercicios-agregados" id="ejercicios-<?= $dia ?>">
                    <?php if (isset($ejercicios_seleccionados[$dia])): ?>
                        <?php foreach ($ejercicios_seleccionados[$dia] as $ejercicio_id => $datos): ?>
                            <?php 
                            $ejercicio_info = null;
                            foreach ($ejercicios as $e) {
                                if ($e['id'] == $ejercicio_id) {
                                    $ejercicio_info = $e;
                                    break;
                                }
                            }
                            if ($ejercicio_info): ?>
                            <div class="ejercicio-item">
                                <div class="ejercicio-info">
                                    <div class="ejercicio-nombre"><?= htmlspecialchars($ejercicio_info['nombre']) ?></div>
                                    <div class="ejercicio-series"><?= $datos['series'] ?> series x <?= $datos['repeticiones'] ?> rep</div>
                                </div>
                                <button type="button" class="btn-eliminar">Eliminar</button>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" name="actualizar_rutina" class="btn-mini">Actualizar Rutina</button>
        <button type="submit" name="borrar_rutina" class="btn-eliminar" style="margin-left:10px;font-size:1em;padding:12px 24px;" onclick="return confirm('¿Seguro que deseas borrar esta rutina? Esta acción no se puede deshacer.');">Borrar Rutina</button>
        <a href="?cliente_id=<?= $cliente_filtro ?>" class="btn-mini" style="background:#6c757d;margin-left:10px;">Cancelar</a>
    </form>
    <?php endif; ?>
</main>

<script>
// Datos de ejercicios por categoría
const ejerciciosPorCategoria = <?= json_encode($ejercicios_por_categoria) ?>;
const categorias = <?= json_encode($categorias) ?>;

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

// Event listeners para botones de categoría
document.querySelectorAll('.categoria-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const categoriaIndex = this.dataset.categoria;
        const dia = this.dataset.dia;
        
        // Remover clase active de todos los botones del mismo día
        document.querySelectorAll(`.categoria-btn[data-dia="${dia}"]`).forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        cargarEjercicios(categoriaIndex, dia);
    });
});

// Event listeners para botones agregar
document.querySelectorAll('.btn-agregar').forEach(btn => {
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

// Event listeners para botones eliminar existentes
document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', function() {
        this.closest('.ejercicio-item').remove();
    });
});
</script>
</body>
</html> 