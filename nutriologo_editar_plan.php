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
// Obtener alimentos agrupados por categoría
$alimentos = [];
$result = $conn->query("SELECT id, nombre, calorias, categoria FROM alimentos ORDER BY categoria, nombre");
while ($row = $result->fetch_assoc()) {
    $alimentos[] = $row;
}
$alimentos_por_categoria = [];
foreach ($alimentos as $a) {
    $alimentos_por_categoria[$a['categoria']][] = $a;
}
$categorias = [
    'Frutas y verduras',
    'Cereales y derivados',
    'Carnes, pescados y huevos',
    'Lácteos y derivados',
    'Grasas, snacks y otros'
];
// Obtener planes del cliente seleccionado
$cliente_filtro = $_GET['cliente_id'] ?? '';
$planes = [];
if ($cliente_filtro) {
    $stmt = $conn->prepare('SELECT id, fecha FROM planes_nutricionales WHERE cliente_id = ? AND nutriologo_id = ? ORDER BY fecha DESC');
    $stmt->bind_param('ii', $cliente_filtro, $nutriologo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $planes[] = $row;
    }
    $stmt->close();
}
// Obtener plan específico a editar
$plan_id = $_GET['plan_id'] ?? '';
$plan_actual = null;
$alimentos_plan = [];
if ($plan_id) {
    $stmt = $conn->prepare('SELECT p.*, u.nombre as cliente_nombre FROM planes_nutricionales p INNER JOIN usuarios u ON p.cliente_id = u.id WHERE p.id = ? AND p.nutriologo_id = ?');
    $stmt->bind_param('ii', $plan_id, $nutriologo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan_actual = $result->fetch_assoc();
    $stmt->close();
    if ($plan_actual) {
        $stmt = $conn->prepare('SELECT pa.*, a.nombre, a.calorias FROM plan_alimentos pa INNER JOIN alimentos a ON pa.alimento_id = a.id WHERE pa.plan_id = ?');
        $stmt->bind_param('i', $plan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $alimentos_plan[] = $row;
        }
        $stmt->close();
    }
}
// Actualizar plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_plan'])) {
    $plan_id = intval($_POST['plan_id']);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $tipos_comida = ['desayuno','snack1','comida','snack2','cena'];
    if ($plan_id && $fecha) {
        // Actualizar fecha del plan
        $stmt = $conn->prepare('UPDATE planes_nutricionales SET fecha = ? WHERE id = ? AND nutriologo_id = ?');
        $stmt->bind_param('sii', $fecha, $plan_id, $nutriologo_id);
        $stmt->execute();
        $stmt->close();
        // Eliminar alimentos actuales del plan
        $stmt = $conn->prepare('DELETE FROM plan_alimentos WHERE plan_id = ?');
        $stmt->bind_param('i', $plan_id);
        $stmt->execute();
        $stmt->close();
        // Insertar nuevos alimentos
        foreach ($tipos_comida as $tipo) {
            $alimentos_tipo = $_POST[$tipo] ?? [];
            foreach ($alimentos_tipo as $alimento_id) {
                $cantidad = $_POST['cantidad_' . $tipo . '_' . $alimento_id] ?? '100g';
                $stmt = $conn->prepare('INSERT INTO plan_alimentos (plan_id, alimento_id, tipo_comida, cantidad) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('iiss', $plan_id, $alimento_id, ucfirst($tipo), $cantidad);
                $stmt->execute();
                $stmt->close();
            }
        }
        $mensaje = 'Plan nutricional actualizado correctamente.';
        // Recargar datos del plan
        $stmt = $conn->prepare('SELECT pa.*, a.nombre, a.calorias FROM plan_alimentos pa INNER JOIN alimentos a ON pa.alimento_id = a.id WHERE pa.plan_id = ?');
        $stmt->bind_param('i', $plan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $alimentos_plan = [];
        while ($row = $result->fetch_assoc()) {
            $alimentos_plan[] = $row;
        }
        $stmt->close();
    } else {
        $mensaje = 'Error al actualizar el plan.';
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Editar Plan Nutricional | Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
    <style>
        .form-plan { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1.5px solid #e6ecf3; padding: 18px 14px 14px 14px; margin-bottom: 24px; max-width: 900px; }
        .form-plan label { font-weight: 600; color: #23272f; margin-bottom: 6px; display: block; }
        .form-plan input, .form-plan select { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e6ecf3; font-size: 1em; margin-bottom: 12px; }
        .planes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .plan-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e6ecf3; }
        .plan-fecha { color: #0074D9; font-weight: 600; margin-bottom: 12px; font-size: 1.1em; }
        .comida-seccion { margin-bottom: 12px; }
        .comida-titulo { color: #23272f; font-weight: 600; margin-bottom: 6px; }
        .alimento-lista { list-style: none; padding: 0; margin: 0; }
        .alimento-lista li { padding: 3px 0; color: #666; }
        .btn-editar { background: #0074D9; color: white; padding: 8px 16px; border: none; border-radius: 6px; text-decoration: none; font-size: 0.9em; }
        .btn-editar:hover { background: #0056b3; }
        .categoria-buttons { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
        .categoria-btn { padding: 8px 16px; border: 1px solid #0074D9; background: #fff; color: #0074D9; border-radius: 6px; cursor: pointer; font-size: 0.9em; }
        .categoria-btn.active { background: #0074D9; color: #fff; }
        .categoria-btn:hover { background: #e6f2fb; }
        .categoria-btn.active:hover { background: #0056b3; }
        .alimento-selector { background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e6ecf3; margin-bottom: 10px; }
        .alimento-row { display: grid; grid-template-columns: 1fr 120px 80px; gap: 8px; align-items: center; margin-bottom: 8px; }
        .alimento-row select, .alimento-row input { margin: 0; }
        .btn-agregar { background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 0.9em; }
        .btn-agregar:hover { background: #218838; }
        .btn-eliminar { background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8em; }
        .btn-eliminar:hover { background: #c82333; }
        .alimentos-agregados { margin-top: 10px; }
        .alimento-item { background: #fff; padding: 8px; border-radius: 6px; border: 1px solid #e6ecf3; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
        .alimento-info { flex: 1; }
        .alimento-nombre { font-weight: 600; color: #23272f; }
        .alimento-cantidad { color: #0074D9; font-size: 0.9em; }
    </style>
</head>
<body>
<?php include 'header_nutriologo.php'; ?>
<?php include 'menu_lateral_nutriologo.php'; ?>
<?php include 'nutriologo_carrusel.php'; ?>
<main class="nutriologo-main">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;">Editar Plan Nutricional</h1>
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
    <?php if ($cliente_filtro && !$plan_id): ?>
    <div class="planes-grid">
        <?php foreach ($planes as $plan): ?>
        <div class="plan-card">
            <div class="plan-fecha">Fecha: <?= date('d/m/Y', strtotime($plan['fecha'])) ?></div>
            <a href="?cliente_id=<?= $cliente_filtro ?>&plan_id=<?= $plan['id'] ?>" class="btn-editar">Editar Plan</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($plan_actual): ?>
    <form class="form-plan" method="post">
        <h2 style="color:#0074D9;font-size:1.1em;margin-bottom:12px;">Editar Plan - <?= htmlspecialchars($plan_actual['cliente_nombre']) ?></h2>
        <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
        <label>Fecha:
            <input type="date" name="fecha" required value="<?= $plan_actual['fecha'] ?>">
        </label>
        <?php
        $tipos_comida = [
            'desayuno' => '🍳 <span style="color:#23272f;">Desayuno</span>',
            'snack1' => '🥛 <span style="color:#23272f;">Snack 1</span>',
            'comida' => '🍲 <span style="color:#23272f;">Comida</span>',
            'snack2' => '🍏 <span style="color:#23272f;">Snack 2</span>',
            'cena' => '🍽️ <span style="color:#23272f;">Cena</span>'
        ];
        // Crear array de alimentos seleccionados por tipo
        $alimentos_seleccionados = [];
        foreach ($alimentos_plan as $alimento) {
            $tipo = strtolower($alimento['tipo_comida']);
            $alimentos_seleccionados[$tipo][$alimento['alimento_id']] = $alimento['cantidad'];
        }
        foreach ($tipos_comida as $tipo => $label): ?>
        <div class="form-group" style="margin-bottom: 20px;">
            <label><?= $label ?>:</label>
            <div class="alimento-selector">
                <div class="categoria-buttons">
                    <?php foreach ($categorias as $index => $cat): ?>
                        <button type="button" class="categoria-btn" data-categoria="<?= $index ?>" data-tipo="<?= $tipo ?>">
                            <?= $cat ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="alimento-row">
                    <select class="alimento-select" data-tipo="<?= $tipo ?>">
                        <option value="">Selecciona alimento</option>
                    </select>
                    <select class="cantidad-select">
                        <option value="100g">100g</option>
                        <option value="200g">200g</option>
                        <option value="300g">300g</option>
                        <option value="500g">500g</option>
                        <option value="1 pieza">1 pieza</option>
                    </select>
                    <button type="button" class="btn-agregar" data-tipo="<?= $tipo ?>">Agregar</button>
                </div>
                <div class="alimentos-agregados" id="alimentos-<?= $tipo ?>">
                    <?php if (isset($alimentos_seleccionados[$tipo])): ?>
                        <?php foreach ($alimentos_seleccionados[$tipo] as $alimento_id => $cantidad): ?>
                            <?php 
                            $alimento_info = null;
                            foreach ($alimentos as $a) {
                                if ($a['id'] == $alimento_id) {
                                    $alimento_info = $a;
                                    break;
                                }
                            }
                            if ($alimento_info): ?>
                            <div class="alimento-item">
                                <div class="alimento-info">
                                    <div class="alimento-nombre"><?= htmlspecialchars($alimento_info['nombre']) ?> (<?= $alimento_info['calorias'] ?> cal)</div>
                                    <div class="alimento-cantidad"><?= $cantidad ?></div>
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
        <button type="submit" name="actualizar_plan" class="btn-mini">Actualizar Plan</button>
        <a href="?cliente_id=<?= $cliente_filtro ?>" class="btn-mini" style="background:#6c757d;margin-left:10px;">Cancelar</a>
    </form>
    <?php endif; ?>
</main>

<script>
// Datos de alimentos por categoría
const alimentosPorCategoria = <?= json_encode($alimentos_por_categoria) ?>;
const categorias = <?= json_encode($categorias) ?>;

// Función para cargar alimentos de una categoría
function cargarAlimentos(categoriaIndex, tipoComida) {
    const select = document.querySelector(`.alimento-select[data-tipo="${tipoComida}"]`);
    const categoria = categorias[categoriaIndex];
    const alimentos = alimentosPorCategoria[categoria] || [];
    
    select.innerHTML = '<option value="">Selecciona alimento</option>';
    alimentos.forEach(alimento => {
        const option = document.createElement('option');
        option.value = alimento.id;
        option.textContent = `${alimento.nombre} (${alimento.calorias} cal)`;
        select.appendChild(option);
    });
}

// Event listeners para botones de categoría
document.querySelectorAll('.categoria-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const categoriaIndex = this.dataset.categoria;
        const tipoComida = this.dataset.tipo;
        
        // Remover clase active de todos los botones del mismo tipo
        document.querySelectorAll(`.categoria-btn[data-tipo="${tipoComida}"]`).forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        cargarAlimentos(categoriaIndex, tipoComida);
    });
});

// Event listeners para botones agregar
document.querySelectorAll('.btn-agregar').forEach(btn => {
    btn.addEventListener('click', function() {
        const tipoComida = this.dataset.tipo;
        const row = this.closest('.alimento-row');
        const selectAlimento = row.querySelector('.alimento-select');
        const selectCantidad = row.querySelector('.cantidad-select');
        
        if (selectAlimento.value) {
            const alimentoId = selectAlimento.value;
            const alimentoText = selectAlimento.options[selectAlimento.selectedIndex].text;
            const cantidad = selectCantidad.value;
            
            // Crear elemento de alimento agregado
            const alimentoDiv = document.createElement('div');
            alimentoDiv.className = 'alimento-item';
            alimentoDiv.innerHTML = `
                <div class="alimento-info">
                    <div class="alimento-nombre">${alimentoText}</div>
                    <div class="alimento-cantidad">${cantidad}</div>
                </div>
                <button type="button" class="btn-eliminar">Eliminar</button>
            `;
            
            // Agregar inputs hidden al formulario
            const inputAlimento = document.createElement('input');
            inputAlimento.type = 'hidden';
            inputAlimento.name = `${tipoComida}[]`;
            inputAlimento.value = alimentoId;
            
            const inputCantidad = document.createElement('input');
            inputCantidad.type = 'hidden';
            inputCantidad.name = `cantidad_${tipoComida}_${alimentoId}`;
            inputCantidad.value = cantidad;
            
            document.querySelector('.form-plan').appendChild(inputAlimento);
            document.querySelector('.form-plan').appendChild(inputCantidad);
            
            // Agregar al contenedor
            document.getElementById(`alimentos-${tipoComida}`).appendChild(alimentoDiv);
            
            // Limpiar selects
            selectAlimento.value = '';
            selectCantidad.value = '100g';
            
            // Event listener para eliminar
            alimentoDiv.querySelector('.btn-eliminar').addEventListener('click', function() {
                alimentoDiv.remove();
                inputAlimento.remove();
                inputCantidad.remove();
            });
        }
    });
});

// Event listeners para botones eliminar existentes
document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', function() {
        this.closest('.alimento-item').remove();
    });
});
</script>
</body>
</html> 