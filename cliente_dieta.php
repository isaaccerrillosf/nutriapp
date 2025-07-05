<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
}

$cliente_id = $_SESSION['usuario_id'];
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Obtener planes nutricionales del cliente
$planes = [];
$stmt = $conn->prepare('SELECT id, fecha FROM planes_nutricionales WHERE cliente_id = ? ORDER BY fecha DESC');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $planes[] = $row;
}
$stmt->close();

// Obtener alimentos de cada plan
$alimentos_plan = [];
foreach ($planes as $plan) {
    $stmt = $conn->prepare('SELECT a.nombre, a.calorias, pa.tipo_comida, pa.cantidad FROM plan_alimentos pa INNER JOIN alimentos a ON pa.alimento_id = a.id WHERE pa.plan_id = ?');
    $stmt->bind_param('i', $plan['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $alimentos_plan[$plan['id']][] = $row;
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
    <title>Mi Plan Nutricional</title>
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
        .menu-inferior-dieta {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #fff;
            border-top: 2px solid #e6ecf3;
            display: flex;
            justify-content: space-around;
            z-index: 1000;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.04);
        }
        .tab-dieta {
            flex: 1;
            border: none;
            background: none;
            font-size: 1em;
            color: #0074D9;
            padding: 8px 0 4px 0;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .tab-dieta.active, .tab-dieta:focus {
            background: #e6f2fb;
            color: #0056b3;
        }
        .tab-dieta span {
            font-size: 1.7em;
            display: block;
        }
        .planes-grid {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        .plan-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e6ecf3;
        }
        .plan-fecha {
            font-weight: 700;
            color: #0074D9;
            font-size: 1.1em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e6f2fb;
        }
        .comida-seccion {
            margin-bottom: 15px;
        }
        .comida-titulo {
            font-weight: 600;
            color: #23272f;
            margin-bottom: 8px;
            font-size: 1em;
        }
        .alimento-lista {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .alimento-lista li {
            padding: 6px 0;
            color: #555;
            border-bottom: 1px solid #f0f0f0;
        }
        .alimento-lista li:last-child {
            border-bottom: none;
        }
        .comida-detalle {
            display: none;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        .comida-detalle.active {
            display: block;
        }
        .total-calorias {
            font-weight: 700;
            color: #0074D9;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #e6f2fb;
        }
        .sin-planes {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        @media (min-width: 900px) {
            .menu-inferior-dieta { 
                position: static; 
                box-shadow: none; 
                border-top: none; 
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header_cliente.php'; ?>
    <?php include 'menu_lateral_cliente.php'; ?>
    <?php include 'cliente_carrusel.php'; ?>
    
    <main class="cliente-main">
        <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;text-align:center;">Mi Plan Nutricional</h1>
        
        <?php if (empty($planes)): ?>
            <div class="sin-planes">
                <h3>🍽️ No tienes planes nutricionales asignados</h3>
                <p>Tu nutriólogo aún no te ha asignado un plan nutricional personalizado.</p>
                <p>Contacta con tu nutriólogo para que te asigne tu plan.</p>
            </div>
        <?php else: ?>
            <div class="menu-inferior-dieta">
                <button class="tab-dieta active" onclick="mostrarComida('Desayuno')" id="tab-Desayuno">
                    <span>🍳</span><br>Desayuno
                </button>
                <button class="tab-dieta" onclick="mostrarComida('Snack1')" id="tab-Snack1">
                    <span>🥛</span><br>Snack 1
                </button>
                <button class="tab-dieta" onclick="mostrarComida('Comida')" id="tab-Comida">
                    <span>🍲</span><br>Comida
                </button>
                <button class="tab-dieta" onclick="mostrarComida('Snack2')" id="tab-Snack2">
                    <span>🍏</span><br>Snack 2
                </button>
                <button class="tab-dieta" onclick="mostrarComida('Cena')" id="tab-Cena">
                    <span>🍽️</span><br>Cena
                </button>
            </div>
            
            <div class="planes-grid">
                <?php foreach ($planes as $plan): ?>
                <div class="plan-card">
                    <div class="plan-fecha">Fecha: <?= date('d/m/Y', strtotime($plan['fecha'])) ?></div>
                    
                    <?php if (isset($alimentos_plan[$plan['id']])): ?>
                        <?php
                        $comidas = ['Desayuno' => [], 'Snack1' => [], 'Comida' => [], 'Snack2' => [], 'Cena' => []];
                        foreach ($alimentos_plan[$plan['id']] as $alimento) {
                            $comidas[$alimento['tipo_comida']][] = $alimento;
                        }
                        $total_diario = 0;
                        ?>
                        
                        <?php foreach ($comidas as $tipo => $alimentos_tipo): ?>
                            <?php if (!empty($alimentos_tipo)): ?>
                            <div class="comida-seccion">
                                <div class="comida-titulo"><?= $tipo ?>:</div>
                                <ul class="alimento-lista">
                                    <?php 
                                    $total_comida = 0;
                                    foreach ($alimentos_tipo as $alimento): 
                                        $cantidad = htmlspecialchars($alimento['cantidad']);
                                        $calorias = (int)$alimento['calorias'];
                                        $total_comida += $calorias;
                                    ?>
                                    <li><?= htmlspecialchars($alimento['nombre']) ?> (<?= $cantidad ?>) - <?= $calorias ?> cal</li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="total-calorias">Total <?= $tipo ?>: <?= $total_comida ?> cal</div>
                                <?php $total_diario += $total_comida; ?>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="total-calorias" style="font-size: 1.2em; text-align: center; margin-top: 20px;">
                            🎯 Total diario: <?= $total_diario ?> cal
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php
            $tipos = [
                'Desayuno' => '🍳 Desayuno',
                'Snack1' => '🥛 Snack 1',
                'Comida' => '🍲 Comida',
                'Snack2' => '🍏 Snack 2',
                'Cena' => '🍽️ Cena'
            ];
            foreach ($planes as $plan):
                $alimentos = $alimentos_plan[$plan['id']] ?? [];
                $comidas = ['Desayuno'=>[], 'Snack1'=>[], 'Comida'=>[], 'Snack2'=>[], 'Cena'=>[]];
                foreach ($alimentos as $a) $comidas[$a['tipo_comida']][] = $a;
                $total_diario = 0;
                foreach ($tipos as $tipo => $label):
            ?>
            <div id="comida-<?= $tipo ?>" class="comida-detalle">
                <h3><?= $label ?> (<?= htmlspecialchars($plan['fecha']) ?>)</h3>
                <ul>
                    <?php
                    $total = 0;
                    foreach ($comidas[$tipo] as $a) {
                        $cantidad = htmlspecialchars($a['cantidad']);
                        $cal = (int)$a['calorias'];
                        $total += $cal;
                        echo "<li>{$a['nombre']} ({$cantidad}) - {$cal} cal</li>";
                    }
                    $total_diario += $total;
                    ?>
                </ul>
                <strong>Total: <?= $total ?> cal</strong>
            </div>
            <?php endforeach; ?>
            <div style="margin:16px 0;"><strong>Total diario: <?= $total_diario ?> cal</strong></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
    function mostrarComida(tipo) {
        const tipos = ['Desayuno','Snack1','Comida','Snack2','Cena'];
        tipos.forEach(t => {
            var el = document.getElementById('comida-'+t);
            if (el) el.classList.toggle('active', t === tipo);
            var tab = document.getElementById('tab-'+t);
            if (tab) tab.classList.toggle('active', t === tipo);
        });
    }
    
    window.onload = function() { 
        mostrarComida('Desayuno'); 
    };
    </script>
</body>
</html> 