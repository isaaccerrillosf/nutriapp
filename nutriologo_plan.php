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
// Asignar plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_plan'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $tipos_comida = ['desayuno','snack1','comida','snack2','cena'];
    if ($cliente_id && $fecha) {
        $stmt = $conn->prepare('INSERT INTO planes_nutricionales (cliente_id, nutriologo_id, fecha) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $cliente_id, $nutriologo_id, $fecha);
        $stmt->execute();
        $plan_id = $stmt->insert_id;
        $stmt->close();
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
        $mensaje = 'Plan nutricional asignado correctamente.';
    } else {
        $mensaje = 'Selecciona cliente y fecha.';
    }
}
// Filtro de cliente para ver planes
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
    <title>Plan Nutricional | Nutriólogo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/nutriologo.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'header_nutriologo.php'; ?>
<?php include 'menu_lateral_nutriologo.php'; ?>
<?php include 'nutriologo_carrusel.php'; ?>
<main class="nutriologo-main">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;margin-bottom:18px;text-align:center;">Asignar Plan Nutricional</h1>
    <?php if ($mensaje): ?>
        <div class="mensaje" style="background:#e6f2fb;color:#0074D9;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-weight:600;text-align:center;"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>
    <form class="form-plan" method="post">
        <h2 style="color:#0074D9;font-size:1.1em;margin-bottom:12px;">Nuevo Plan</h2>
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
        
        <!-- Sistema de Pestañas -->
        <div class="tabs-container">
            <div class="tabs-nav">
                <button type="button" class="tab-btn active" data-tab="desayuno">🍳 Desayuno</button>
                <button type="button" class="tab-btn" data-tab="snack1">🥛 Snack 1</button>
                <button type="button" class="tab-btn" data-tab="comida">🍲 Comida</button>
                <button type="button" class="tab-btn" data-tab="snack2">🍏 Snack 2</button>
                <button type="button" class="tab-btn" data-tab="cena">🍽️ Cena</button>
            </div>
            
            <?php
            $tipos_comida = [
                'desayuno' => '🍳 Desayuno',
                'snack1' => '🥛 Snack 1',
                'comida' => '🍲 Comida',
                'snack2' => '🍏 Snack 2',
                'cena' => '🍽️ Cena'
            ];
            foreach ($tipos_comida as $tipo => $label): ?>
            <div class="tab-content <?= $tipo === 'desayuno' ? 'active' : '' ?>" id="tab-<?= $tipo ?>">
                <h3 style="color:#0074D9;font-size:1.2em;margin-bottom:15px;"><?= $label ?></h3>
                                <div class="alimento-selector">
                    <div class="categoria-grid">
                        <div class="categoria-item" data-categoria="0" data-tipo="<?= $tipo ?>">
                            <svg width="48" height="48" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Manzana -->
                                <circle cx="12" cy="14" r="3" fill="#e74c3c"/>
                                <path d="M12 11l0-2" stroke="#2c3e50" stroke-width="1" stroke-linecap="round"/>
                                <!-- Naranja -->
                                <circle cx="20" cy="12" r="2.5" fill="#f39c12"/>
                                <path d="M20 9.5l0-1.5" stroke="#2c3e50" stroke-width="1" stroke-linecap="round"/>
                                <!-- Zanahoria -->
                                <path d="M8 20l2-4 2 4" stroke="#e67e22" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 18l1-2" stroke="#27ae60" stroke-width="1" stroke-linecap="round"/>
                                <!-- Lechuga -->
                                <path d="M24 18c0 2-1 4-3 4s-3-2-3-4 1-4 3-4 3 2 3 4z" fill="#27ae60"/>
                                <path d="M22 16l-2 2" stroke="#2c3e50" stroke-width="0.5"/>
                            </svg>
                            <span>Frutas y Verduras</span>
                        </div>
                        <div class="categoria-item" data-categoria="1" data-tipo="<?= $tipo ?>">
                            <svg width="48" height="48" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Pan -->
                                <ellipse cx="16" cy="18" rx="8" ry="4" fill="#f1c40f"/>
                                <path d="M8 18c0-2 3.5-4 8-4s8 2 8 4" stroke="#d68910" stroke-width="1" fill="none"/>
                                <!-- Granos -->
                                <circle cx="10" cy="14" r="1" fill="#8b4513"/>
                                <circle cx="14" cy="13" r="1" fill="#8b4513"/>
                                <circle cx="18" cy="14" r="1" fill="#8b4513"/>
                                <circle cx="22" cy="13" r="1" fill="#8b4513"/>
                                <circle cx="12" cy="16" r="1" fill="#8b4513"/>
                                <circle cx="16" cy="15" r="1" fill="#8b4513"/>
                                <circle cx="20" cy="16" r="1" fill="#8b4513"/>
                            </svg>
                            <span>Cereales</span>
                        </div>
                        <div class="categoria-item" data-categoria="2" data-tipo="<?= $tipo ?>">
                            <svg width="48" height="48" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Pollo -->
                                <ellipse cx="16" cy="20" rx="10" ry="6" fill="#e67e22"/>
                                <path d="M6 20c0-3 4.5-6 10-6s10 3 10 6" stroke="#d35400" stroke-width="1" fill="none"/>
                                <!-- Hueso -->
                                <path d="M12 16l2-2 2 2 2-2" stroke="#ecf0f1" stroke-width="1.5" stroke-linecap="round"/>
                                <!-- Patas -->
                                <path d="M14 26l0 2" stroke="#2c3e50" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M18 26l0 2" stroke="#2c3e50" stroke-width="1.5" stroke-linecap="round"/>
                                <!-- Pico -->
                                <path d="M16 14l0-2" stroke="#e74c3c" stroke-width="1" stroke-linecap="round"/>
                            </svg>
                            <span>Carnes</span>
                        </div>
                        <div class="categoria-item" data-categoria="3" data-tipo="<?= $tipo ?>">
                            <svg width="48" height="48" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Botella de leche -->
                                <path d="M10 8l2-2h8l2 2v16c0 1-1 2-2 2h-8c-1 0-2-1-2-2v-16z" stroke="#3498db" stroke-width="1.5" fill="none"/>
                                <path d="M12 6h8" stroke="#3498db" stroke-width="1.5"/>
                                <!-- Gotas de leche -->
                                <path d="M14 12l1-2 1 2" stroke="#ecf0f1" stroke-width="1" stroke-linecap="round"/>
                                <path d="M18 12l1-2 1 2" stroke="#ecf0f1" stroke-width="1" stroke-linecap="round"/>
                                <!-- Queso -->
                                <path d="M20 18l-8 0 0 6 8 0z" fill="#f1c40f"/>
                                <path d="M16 18l0 6" stroke="#d68910" stroke-width="0.5"/>
                            </svg>
                            <span>Lácteos</span>
                        </div>
                        <div class="categoria-item" data-categoria="4" data-tipo="<?= $tipo ?>">
                            <svg width="48" height="48" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Papas fritas -->
                                <path d="M8 12l2-2 2 2 2-2 2 2 2-2 2 2 2-2 2 2" stroke="#f39c12" stroke-width="1" stroke-linecap="round"/>
                                <path d="M8 16l2-2 2 2 2-2 2 2 2-2 2 2 2-2 2 2" stroke="#f39c12" stroke-width="1" stroke-linecap="round"/>
                                <path d="M8 20l2-2 2 2 2-2 2 2 2-2 2 2 2-2 2 2" stroke="#f39c12" stroke-width="1" stroke-linecap="round"/>
                                <!-- Bolsa -->
                                <path d="M6 10l4-2 12 0 4 2v12l-4 2h-12l-4-2z" stroke="#e74c3c" stroke-width="1.5" fill="none"/>
                                <!-- Chips -->
                                <ellipse cx="12" cy="14" rx="1" ry="0.5" fill="#f39c12"/>
                                <ellipse cx="18" cy="14" rx="1" ry="0.5" fill="#f39c12"/>
                                <ellipse cx="15" cy="18" rx="1" ry="0.5" fill="#f39c12"/>
                            </svg>
                            <span>Snacks</span>
                        </div>
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
                        <button type="button" class="btn-azul" data-tipo="<?= $tipo ?>">Agregar</button>
                    </div>
                    <div class="alimentos-agregados" id="alimentos-<?= $tipo ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button type="submit" name="asignar_plan" class="btn-azul" style="margin-top: 20px;">Asignar Plan</button>
    </form>
    <form class="filtros-resultados" method="get">
        <select name="cliente_id" onchange="this.form.submit()">
            <option value="">Ver planes de todos los clientes</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $cliente_filtro==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
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
                ?>
                <?php foreach ($comidas as $tipo => $alimentos_tipo): ?>
                    <?php if (!empty($alimentos_tipo)): ?>
                    <div class="comida-seccion">
                        <div class="comida-titulo"><?= $tipo ?>:</div>
                        <ul class="alimento-lista">
                            <?php foreach ($alimentos_tipo as $alimento): ?>
                            <li><?= htmlspecialchars($alimento['nombre']) ?> (<?= $alimento['cantidad'] ?>) - <?= $alimento['calorias'] ?> cal</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
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
        const tipoComida = this.dataset.tipo;
        
        // Remover clase active de todos los elementos del mismo tipo
        document.querySelectorAll(`.categoria-item[data-tipo="${tipoComida}"]`).forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        
        cargarAlimentos(categoriaIndex, tipoComida);
    });
});

// Event listeners para botones agregar
document.querySelectorAll('.btn-azul').forEach(btn => {
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

// Inicializar primera categoría en cada pestaña
document.addEventListener('DOMContentLoaded', function() {
    // Activar primera categoría en cada pestaña
    document.querySelectorAll('.tab-content').forEach(tab => {
        const tipoComida = tab.id.replace('tab-', '');
        const primeraCategoria = document.querySelector(`.categoria-item[data-tipo="${tipoComida}"]`);
        if (primeraCategoria) {
            primeraCategoria.click();
        }
    });
});
</script>
</body>
</html> 