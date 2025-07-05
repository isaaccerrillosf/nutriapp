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

$dia_semana = date('l');
$dias_espanol = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
];
$dia_actual = $dias_espanol[$dia_semana];

// Obtener rutina del día
$stmt = $conn->prepare('SELECT id FROM rutinas_ejercicio WHERE cliente_id = ? AND fecha = CURDATE()');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$rutina_result = $stmt->get_result();
$rutina = $rutina_result->fetch_assoc();
$stmt->close();

$ejercicios_rutina = [];
$seguimiento_ejercicios = [];
if ($rutina) {
    // Obtener ejercicios de la rutina
    $stmt = $conn->prepare('SELECT e.id as ejercicio_id, e.nombre, re.series, re.repeticiones, e.youtube_url, re.grupo_muscular, re.tipo_grupo FROM rutina_ejercicios re INNER JOIN ejercicios e ON re.ejercicio_id = e.id WHERE re.rutina_id = ?');
    $stmt->bind_param('i', $rutina['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ejercicios_rutina[] = $row;
    }
    $stmt->close();
    
    // Obtener seguimiento de ejercicios para hoy
    $fecha_hoy = date('Y-m-d');
    $stmt = $conn->prepare('SELECT ejercicio_id, completado, series_completadas, repeticiones_completadas FROM seguimiento_ejercicios WHERE cliente_id = ? AND rutina_id = ? AND fecha_ejercicio = ?');
    $stmt->bind_param('iis', $cliente_id, $rutina['id'], $fecha_hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $seguimiento_ejercicios[$row['ejercicio_id']] = $row;
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
    <title>Mi Rutina de Ejercicios</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .cliente-main {
            margin-left: 250px;
            flex: 1;
            padding: 80px 18px 18px 18px;
            background: #f4f8fb;
            min-height: 100vh;
            transition: margin-left 0.3s;
        }
        .ejercicio-item {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e6ecf3;
        }
        .ejercicio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .ejercicio-grupo {
            font-weight: 700;
            color: #0074D9;
            font-size: 1.1em;
        }
        .ejercicio-completado {
            background: #e8f5e8 !important;
            border: 2px solid #27ae60 !important;
        }
        .checkbox-ejercicio {
            width: 24px;
            height: 24px;
            border: 2px solid #0074D9;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .checkbox-ejercicio:checked {
            background: #0074D9;
            border-color: #0074D9;
        }
        .checkbox-ejercicio:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-weight: bold;
            font-size: 14px;
        }
        .ejercicio-detalles {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 8px 0;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #0074D9;
        }
        .ejercicio-nombre {
            font-weight: 600;
            color: #23272f;
            flex: 1;
        }
        .ejercicio-series {
            color: #666;
            font-size: 0.9em;
            margin-right: 12px;
        }
        .play-btn {
            background: #e74c3c;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8em;
            font-weight: 600;
            transition: background 0.2s;
        }
        .play-btn:hover {
            background: #c0392b;
        }
        .estado-completado {
            color: #27ae60;
            font-weight: 600;
            font-size: 0.9em;
        }
        .estado-pendiente {
            color: #f39c12;
            font-weight: 600;
            font-size: 0.9em;
        }
        .progreso-rutina {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e6ecf3;
        }
        .barra-progreso {
            width: 100%;
            height: 12px;
            background: #e6ecf3;
            border-radius: 6px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progreso-fill {
            height: 100%;
            background: linear-gradient(90deg, #0074D9, #27ae60);
            transition: width 0.3s ease;
        }
        .mensaje-seguimiento {
            padding: 10px 16px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: 600;
            text-align: center;
            display: none;
        }
        .mensaje-exito {
            background: #e8f5e8;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        .mensaje-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        @media (max-width: 900px) {
            .cliente-main {
                margin-left: 64px;
            }
        }
        @media (max-width: 600px) {
            .cliente-main {
                margin-left: 0;
                padding-top: 120px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header_cliente.php'; ?>
    <?php include 'menu_lateral_cliente.php'; ?>
    <?php include 'cliente_carrusel.php'; ?>
    
    <main class="cliente-main">
        <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;text-align:center;">Mi Rutina de Ejercicios</h1>
        
        <div id="mensaje-seguimiento" class="mensaje-seguimiento"></div>
        
        <?php if (!empty($ejercicios_rutina)): ?>
            <?php
            $total_ejercicios = count($ejercicios_rutina);
            $ejercicios_completados = 0;
            foreach ($seguimiento_ejercicios as $seguimiento) {
                if ($seguimiento['completado']) {
                    $ejercicios_completados++;
                }
            }
            $porcentaje_completado = $total_ejercicios > 0 ? round(($ejercicios_completados / $total_ejercicios) * 100) : 0;
            ?>
            
            <div class="progreso-rutina">
                <h3 style="color:#0074D9;margin-bottom:10px;">Progreso de hoy (<?= $dia_actual ?>)</h3>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-weight:600;color:#23272f;"><?= $ejercicios_completados ?> de <?= $total_ejercicios ?> ejercicios completados</span>
                    <span style="font-weight:700;color:#0074D9;"><?= $porcentaje_completado ?>%</span>
                </div>
                <div class="barra-progreso">
                    <div class="progreso-fill" style="width: <?= $porcentaje_completado ?>%"></div>
                </div>
            </div>
            
            <?php
            // Agrupar por grupo_muscular y tipo_grupo
            $grupos = [];
            foreach ($ejercicios_rutina as $ejercicio) {
                $tipo = isset($ejercicio['tipo_grupo']) ? $ejercicio['tipo_grupo'] : 1;
                $grupo = isset($ejercicio['grupo_muscular']) ? $ejercicio['grupo_muscular'] : '';
                $grupos[$tipo][$grupo][] = $ejercicio;
            }
            ?>
            
            <?php foreach ([1,2] as $tipo): ?>
                <?php if (!empty($grupos[$tipo])): ?>
                    <?php foreach ($grupos[$tipo] as $grupo_muscular => $ejercicios): ?>
                        <div class="ejercicio-item">
                            <div class="ejercicio-header">
                                <div class="ejercicio-grupo">
                                    <?= $tipo == 1 ? 'Principal' : 'Secundario' ?><?= $grupo_muscular ? ' - ' . htmlspecialchars($grupo_muscular) : '' ?>
                                </div>
                            </div>
                            <?php foreach ($ejercicios as $ejercicio): ?>
                                <?php 
                                $ejercicio_completado = isset($seguimiento_ejercicios[$ejercicio['ejercicio_id']]) && $seguimiento_ejercicios[$ejercicio['ejercicio_id']]['completado'];
                                $clase_completado = $ejercicio_completado ? 'ejercicio-completado' : '';
                                ?>
                                <div class="ejercicio-detalles <?= $clase_completado ?>">
                                    <div style="display:flex;align-items:center;flex:1;">
                                        <input type="checkbox" 
                                               class="checkbox-ejercicio" 
                                               data-rutina-id="<?= $rutina['id'] ?>"
                                               data-ejercicio-id="<?= $ejercicio['ejercicio_id'] ?>"
                                               data-series="<?= $ejercicio['series'] ?>"
                                               data-repeticiones="<?= $ejercicio['repeticiones'] ?>"
                                               <?= $ejercicio_completado ? 'checked' : '' ?>
                                               onchange="actualizarSeguimiento(this)">
                                        <div style="margin-left:12px;flex:1;">
                                            <div class="ejercicio-nombre">
                                                <?= htmlspecialchars($ejercicio['nombre']) ?>
                                                <?php if (!empty($ejercicio['youtube_url'])): ?>
                                                    <a class="play-btn" href="<?= htmlspecialchars($ejercicio['youtube_url']) ?>" target="_blank">▶️ Ver</a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ejercicio-series">
                                                <?= $ejercicio['series'] ?> series × <?= $ejercicio['repeticiones'] ?> repeticiones
                                            </div>
                                        </div>
                                    </div>
                                    <div class="<?= $ejercicio_completado ? 'estado-completado' : 'estado-pendiente' ?>">
                                        <?= $ejercicio_completado ? '✅ Completado' : '⏳ Pendiente' ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="text-align:center;color:#666;font-style:italic;padding:40px 20px;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <h3>💪 No hay rutina asignada para hoy</h3>
                <p>Tu nutriólogo aún no te ha asignado ejercicios para el día de hoy.</p>
                <p>Contacta con tu nutriólogo para que te asigne tu rutina personalizada.</p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleMenu() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('menu-abierto');
        }
        
        function actualizarSeguimiento(checkbox) {
            const rutinaId = checkbox.dataset.rutinaId;
            const ejercicioId = checkbox.dataset.ejercicioId;
            const series = checkbox.dataset.series;
            const repeticiones = checkbox.dataset.repeticiones;
            const completado = checkbox.checked ? 1 : 0;
            
            // Actualizar visualmente el estado
            const ejercicioDetalles = checkbox.closest('.ejercicio-detalles');
            const estadoElement = ejercicioDetalles.querySelector('.estado-completado, .estado-pendiente');
            
            if (completado) {
                ejercicioDetalles.classList.add('ejercicio-completado');
                estadoElement.className = 'estado-completado';
                estadoElement.textContent = '✅ Completado';
            } else {
                ejercicioDetalles.classList.remove('ejercicio-completado');
                estadoElement.className = 'estado-pendiente';
                estadoElement.textContent = '⏳ Pendiente';
            }
            
            // Enviar datos al servidor
            const formData = new FormData();
            formData.append('rutina_id', rutinaId);
            formData.append('ejercicio_id', ejercicioId);
            formData.append('completado', completado);
            formData.append('series_completadas', series);
            formData.append('repeticiones_completadas', repeticiones);
            formData.append('fecha_ejercicio', new Date().toISOString().split('T')[0]);
            
            fetch('actualizar_seguimiento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarMensaje(data.message, 'exito');
                    actualizarProgreso();
                } else {
                    mostrarMensaje(data.error || 'Error al actualizar seguimiento', 'error');
                    // Revertir el checkbox si hay error
                    checkbox.checked = !checkbox.checked;
                    actualizarSeguimiento(checkbox);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarMensaje('Error de conexión', 'error');
                // Revertir el checkbox si hay error
                checkbox.checked = !checkbox.checked;
                actualizarSeguimiento(checkbox);
            });
        }
        
        function mostrarMensaje(mensaje, tipo) {
            const mensajeElement = document.getElementById('mensaje-seguimiento');
            mensajeElement.textContent = mensaje;
            mensajeElement.className = `mensaje-seguimiento mensaje-${tipo}`;
            mensajeElement.style.display = 'block';
            
            setTimeout(() => {
                mensajeElement.style.display = 'none';
            }, 3000);
        }
        
        function actualizarProgreso() {
            const checkboxes = document.querySelectorAll('.checkbox-ejercicio');
            const totalEjercicios = checkboxes.length;
            const ejerciciosCompletados = document.querySelectorAll('.checkbox-ejercicio:checked').length;
            const porcentaje = totalEjercicios > 0 ? Math.round((ejerciciosCompletados / totalEjercicios) * 100) : 0;
            
            // Actualizar contador
            const contadorElement = document.querySelector('.progreso-rutina span:first-child');
            if (contadorElement) {
                contadorElement.textContent = `${ejerciciosCompletados} de ${totalEjercicios} ejercicios completados`;
            }
            
            // Actualizar porcentaje
            const porcentajeElement = document.querySelector('.progreso-rutina span:last-child');
            if (porcentajeElement) {
                porcentajeElement.textContent = `${porcentaje}%`;
            }
            
            // Actualizar barra de progreso
            const barraProgreso = document.querySelector('.progreso-fill');
            if (barraProgreso) {
                barraProgreso.style.width = `${porcentaje}%`;
            }
        }
    </script>
</body>
</html> 