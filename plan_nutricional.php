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
$planes = [];
$stmt = $conn->prepare('SELECT id, fecha FROM planes_nutricionales WHERE cliente_id = ? ORDER BY fecha DESC');
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $planes[] = $row;
}
$stmt->close();
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
    <title>Plan Nutricional</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
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
    @media (min-width: 900px) {
      .menu-inferior-dieta { position: static; box-shadow: none; border-top: none; }
    }
    </style>
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
            <h2 style="color:#0074D9;">Plan Nutricional de <?= htmlspecialchars($cliente_nombre) ?></h2>
            <div class="menu-inferior-dieta">
              <button class="tab-dieta" onclick="mostrarComida('Desayuno')" id="tab-Desayuno"><span>🍳</span><br>Desayuno</button>
              <button class="tab-dieta" onclick="mostrarComida('Snack1')" id="tab-Snack1"><span>🥛</span><br>Snack 1</button>
              <button class="tab-dieta" onclick="mostrarComida('Comida')" id="tab-Comida"><span>🍲</span><br>Comida</button>
              <button class="tab-dieta" onclick="mostrarComida('Snack2')" id="tab-Snack2"><span>🍏</span><br>Snack 2</button>
              <button class="tab-dieta" onclick="mostrarComida('Cena')" id="tab-Cena"><span>🍽️</span><br>Cena</button>
            </div>
            <div style="overflow-x:auto;">
            <table class="plan-table">
                <tr>
                    <th>Fecha</th>
                    <th>Desayuno</th>
                    <th>Comida</th>
                    <th>Cena</th>
                </tr>
                <?php foreach ($planes as $plan): ?>
                <tr>
                    <td><?= htmlspecialchars($plan['fecha']) ?></td>
                    <td>
                        <?php
                        if (!empty($alimentos_plan[$plan['id']])) {
                            $desayuno = array_filter($alimentos_plan[$plan['id']], function($a) { return $a['tipo_comida'] === 'Desayuno'; });
                            echo implode(', ', array_map(function($a) { return htmlspecialchars($a['nombre']) . ' (' . $a['calorias'] . ' cal)'; }, $desayuno));
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($alimentos_plan[$plan['id']])) {
                            $comida = array_filter($alimentos_plan[$plan['id']], function($a) { return $a['tipo_comida'] === 'Comida'; });
                            echo implode(', ', array_map(function($a) { return htmlspecialchars($a['nombre']) . ' (' . $a['calorias'] . ' cal)'; }, $comida));
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($alimentos_plan[$plan['id']])) {
                            $cena = array_filter($alimentos_plan[$plan['id']], function($a) { return $a['tipo_comida'] === 'Cena'; });
                            echo implode(', ', array_map(function($a) { return htmlspecialchars($a['nombre']) . ' (' . $a['calorias'] . ' cal)'; }, $cena));
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>
        </div>
    </main>
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
    <div id="comida-<?= $tipo ?>" style="display:none;">
      <h3><?= $label ?> (<?= htmlspecialchars($plan['fecha']) ?>)</h3>
      <ul>
        <?php
        $total = 0;
        foreach ($comidas[$tipo] as $a) {
          $cantidad = htmlspecialchars($a['cantidad']);
          $cal = (int)$a['calorias'] * (is_numeric($cantidad) ? $cantidad : 1);
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
    <script>
    function mostrarComida(tipo) {
      const tipos = ['Desayuno','Snack1','Comida','Snack2','Cena'];
      tipos.forEach(t => {
        var el = document.getElementById('comida-'+t);
        if (el) el.style.display = (t === tipo) ? 'block' : 'none';
        var tab = document.getElementById('tab-'+t);
        if (tab) tab.classList.toggle('active', t === tipo);
      });
    }
    window.onload = function() { mostrarComida('Desayuno'); };
    </script>
</body>
</html> 