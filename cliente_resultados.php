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

// Obtener resultados históricos
$stmt = $conn->prepare('SELECT fecha, peso, grasa_corporal, cintura, cadera, brazo, muslo, notas FROM resultados_cliente WHERE cliente_id = ? ORDER BY fecha DESC LIMIT 10');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$resultados_result = $stmt->get_result();
$resultados = [];
while ($row = $resultados_result->fetch_assoc()) {
    $resultados[] = $row;
}
$stmt->close();

// Cálculo volumen semanal
$volumenSemanal = [];
$labelsSem = [];
$connSeg=new mysqli($host,$user,$pass,$db);
if(!$connSeg->connect_error){
    $cliente=$cliente_id ?? $_SESSION['usuario_id'];
    $stmt=$connSeg->prepare('SELECT fecha_ejercicio, series_completadas FROM seguimiento_ejercicios WHERE cliente_id=? AND completado=1 ORDER BY fecha_ejercicio DESC LIMIT 500');
    $stmt->bind_param('i',$cliente);
    $stmt->execute();$res=$stmt->get_result();
    while($r=$res->fetch_assoc()){
        $week=date('o-W',strtotime($r['fecha_ejercicio']));
        $volumenSemanal[$week]=($volumenSemanal[$week]??0)+(int)$r['series_completadas'];
    }
    ksort($volumenSemanal);
    foreach($volumenSemanal as $sem=>$val){$labelsSem[]=$sem;}
    $stmt->close();$connSeg->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Mis Resultados</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/cliente.css">
</head>
<body>
    <?php include 'header_cliente.php'; ?>
    <?php include 'menu_lateral_cliente.php'; ?>
    
    <main class="cliente-main">
        <div class="card-section">
            <h2>Mis Resultados</h2>
            <h3 style="margin: 16px; color: #27ae60;">Tu Progreso</h3>

            <!-- Gráfica de peso -->
            <canvas id="graficaPeso" style="max-width:100%;height:260px;margin-bottom:24px;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);"></canvas>

            <!-- Gráfica de volumen -->
            <canvas id="graficaVolumen" style="max-width:100%;height:260px;margin-bottom:24px;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);"></canvas>

            <?php if (!empty($resultados)): ?>
                <?php foreach ($resultados as $resultado): ?>
                    <div class="resultado-item">
                        <div class="resultado-fecha"><?= date('d/m/Y', strtotime($resultado['fecha'])) ?></div>
                        <div class="resultado-medidas">
                            <?php if ($resultado['peso']): ?>
                                <div>Peso: <?= $resultado['peso'] ?> kg</div>
                            <?php endif; ?>
                            <?php if ($resultado['grasa_corporal']): ?>
                                <div>Grasa: <?= $resultado['grasa_corporal'] ?>%</div>
                            <?php endif; ?>
                            <?php if ($resultado['cintura']): ?>
                                <div>Cintura: <?= $resultado['cintura'] ?> cm</div>
                            <?php endif; ?>
                            <?php if ($resultado['cadera']): ?>
                                <div>Cadera: <?= $resultado['cadera'] ?> cm</div>
                            <?php endif; ?>
                            <?php if ($resultado['brazo']): ?>
                                <div>Brazo: <?= $resultado['brazo'] ?> cm</div>
                            <?php endif; ?>
                            <?php if ($resultado['muslo']): ?>
                                <div>Muslo: <?= $resultado['muslo'] ?> cm</div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($resultado['notas'])): ?>
                            <div style="margin-top: 8px; color: #b6c2d1; font-size: 0.9em;">
                                <?= htmlspecialchars($resultado['notas']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aún no hay resultados registrados.</p>
                    <p>Tu nutriólogo registrará tus medidas en la próxima consulta.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleMenu() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('menu-abierto');
        }

        const datosPeso = <?php
            $pesos = [];
            $fechas = [];
            foreach (array_reverse($resultados) as $r) {
                if (!empty($r['peso'])) {
                    $pesos[] = (float)$r['peso'];
                    $fechas[] = date('d/m', strtotime($r['fecha']));
                }
            }
            echo json_encode(['fechas'=>$fechas,'pesos'=>$pesos]);
        ?>;
        if (datosPeso.pesos.length){
            const ctx = document.getElementById('graficaPeso').getContext('2d');
            new Chart(ctx,{type:'line',data:{labels:datosPeso.fechas,datasets:[{label:'Peso (kg)',data:datosPeso.pesos,fill:false,borderColor:'#0074D9',tension:0.3}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:false}}}});
        }

        // Volumen semanal chart
        const datosVol = {labels:<?= json_encode(array_values($labelsSem)) ?>, data:<?= json_encode(array_values($volumenSemanal)) ?>};
        if (datosVol.labels.length){
            const ctxVol=document.getElementById('graficaVolumen').getContext('2d');
            new Chart(ctxVol,{type:'bar',data:{labels:datosVol.labels,datasets:[{label:'Series completadas',data:datosVol.data,backgroundColor:'#27ae60'}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
        }
    </script>
</body>
</html> 