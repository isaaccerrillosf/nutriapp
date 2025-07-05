<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';
$mensaje = '';

// Definir categorías
$categorias = [
    1 => 'Frutas y verduras',
    2 => 'Cereales y derivados',
    3 => 'Carnes, pescados y huevos',
    4 => 'Lácteos y derivados',
    5 => 'Grasas, snacks y otros'
];

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar_alimento']) && !isset($_POST['importar_csv'])) {
    $nombre = $_POST['nombre'] ?? '';
    $calorias = $_POST['calorias'] ?? '';
    $gramos = $_POST['gramos'] ?? '';
    $categoria = $_POST['categoria'] ?? 'Frutas y verduras';
    if ($nombre && is_numeric($calorias) && is_numeric($gramos) && in_array($categoria, $categorias)) {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die('Error de conexión: ' . $conn->connect_error);
        }
        $stmt = $conn->prepare('INSERT INTO alimentos (nombre, calorias, gramos, categoria) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('siis', $nombre, $calorias, $gramos, $categoria);
        if ($stmt->execute()) {
            $mensaje = 'Alimento registrado correctamente.';
        } else {
            $mensaje = 'Error al registrar alimento: ' . $stmt->error;
        }
        $stmt->close();
        $conn->close();
    } else {
        $mensaje = 'Todos los campos son obligatorios y la categoría debe ser válida.';
    }
}

// Procesar importación CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar_csv'])) {
    if (isset($_FILES['csv']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['csv']['tmp_name'];
        $handle = fopen($archivo, 'r');
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die('Error de conexión: ' . $conn->connect_error);
        }
        $importados = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if (count($data) >= 3) {
                $nombre = $data[0];
                $calorias = is_numeric($data[1]) ? (int)$data[1] : 0;
                $gramos = is_numeric($data[2]) ? (int)$data[2] : 100;
                $categoria = 'Frutas y verduras';
                if (isset($data[3]) && is_numeric($data[3]) && isset($categorias[(int)$data[3]])) {
                    $categoria = $categorias[(int)$data[3]];
                }
                if ($nombre && $calorias && $gramos && in_array($categoria, $categorias)) {
                    $stmt = $conn->prepare('INSERT INTO alimentos (nombre, calorias, gramos, categoria) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('siis', $nombre, $calorias, $gramos, $categoria);
                    $stmt->execute();
                    $stmt->close();
                    $importados++;
                }
            }
        }
        fclose($handle);
        $conn->close();
        $mensaje = "Importación completada. Alimentos importados: $importados.";
    } else {
        $mensaje = 'Error al subir el archivo CSV.';
    }
}

// Procesar edición de alimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_alimento'])) {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $calorias = $_POST['calorias'] ?? '';
    $gramos = $_POST['gramos'] ?? '';
    $categoria = $_POST['categoria'] ?? 'Frutas y verduras';
    if ($id && $nombre && is_numeric($calorias) && is_numeric($gramos) && in_array($categoria, $categorias)) {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die('Error de conexión: ' . $conn->connect_error);
        }
        $stmt = $conn->prepare('UPDATE alimentos SET nombre = ?, calorias = ?, gramos = ?, categoria = ? WHERE id = ?');
        $stmt->bind_param('siisi', $nombre, $calorias, $gramos, $categoria, $id);
        if ($stmt->execute()) {
            $mensaje = 'Alimento actualizado correctamente.';
        } else {
            $mensaje = 'Error al actualizar alimento: ' . $stmt->error;
        }
        $stmt->close();
        $conn->close();
    } else {
        $mensaje = 'Datos incompletos para actualizar.';
    }
}

// Procesar borrado masivo de alimentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_todo'])) {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die('Error de conexión: ' . $conn->connect_error);
    }
    $conn->query('DELETE FROM alimentos');
    $conn->close();
    $mensaje = 'Todos los alimentos han sido eliminados.';
}

// Obtener lista de alimentos
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$alimentos = [];
$result = $conn->query('SELECT id, nombre, calorias, gramos, categoria FROM alimentos ORDER BY nombre');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $alimentos[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestión de Alimentos</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
    /* Botón azul moderno y alineación */
    .form-card button, .panel-btn {
        background: #0074D9 !important;
        color: #fff !important;
        border: none !important;
        padding: 16px 0 !important;
        border-radius: 12px !important;
        width: 100% !important;
        margin: 18px 0 0 0 !important;
        font-size: 1.15em !important;
        font-weight: 700 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
        text-align: center !important;
        transition: background 0.2s;
        display: block;
    }
    .form-card button:hover, .panel-btn:hover {
        background: #0056b3 !important;
    }
    /* Etiquetas y campos más oscuros */
    .form-card label {
        color: #23272f !important;
        font-weight: 600 !important;
        font-size: 1.08em !important;
        margin-bottom: 8px !important;
        display: block;
    }
    .form-card input[type="text"],
    .form-card input[type="number"] {
        color: #23272f !important;
        background: #f4f8fb !important;
        border: 2px solid #e6ecf3 !important;
        border-radius: 8px !important;
        padding: 12px 16px !important;
        font-size: 1em !important;
        margin-bottom: 14px !important;
        width: 100%;
        box-sizing: border-box;
    }
    .form-card input[type="text"]::placeholder,
    .form-card input[type="number"]::placeholder {
        color: #b0b8c1 !important;
        opacity: 1;
    }
    .msg-success {
        background: #e6f2fb;
        color: #0074D9;
        border-radius: 8px;
        padding: 10px 16px;
        margin-bottom: 16px;
        font-weight: 600;
        text-align: center;
    }
    
    /* Estilos para la tabla de alimentos */
    .alimentos-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .alimentos-table th {
        background: #0074D9;
        color: white;
        padding: 16px;
        text-align: left;
        font-weight: 600;
    }
    .alimentos-table td {
        padding: 16px;
        border-bottom: 1px solid #e6ecf3;
        color: #23272f;
    }
    .alimentos-table tr:hover {
        background: #f8f9fa;
    }
    .btn-editar {
        background: #0074D9;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background 0.2s;
    }
    .btn-editar:hover {
        background: #0056b3;
    }
    
    /* Modal de edición */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .modal-title {
        color: #0074D9;
        font-size: 1.5em;
        font-weight: 700;
        margin: 0;
    }
    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }
    .close:hover {
        color: #23272f;
    }
    .modal-form {
        margin-top: 20px;
    }
    .modal-form label {
        color: #23272f;
        font-weight: 600;
        font-size: 1.08em;
        margin-bottom: 8px;
        display: block;
    }
    .modal-form input {
        color: #23272f;
        background: #f4f8fb;
        border: 2px solid #e6ecf3;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 1em;
        margin-bottom: 14px;
        width: 100%;
        box-sizing: border-box;
    }
    .modal-buttons {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }
    .btn-guardar {
        background: #0074D9;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        flex: 1;
    }
    .btn-cancelar {
        background: #6c757d;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        flex: 1;
    }
    .btn-guardar:hover {
        background: #0056b3;
    }
    .btn-cancelar:hover {
        background: #5a6268;
    }
    
    /* Vista móvil para la tabla */
    .alimentos-mobile {
        display: none;
    }
    .alimento-card {
        background: white;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .alimento-card h3 {
        color: #0074D9;
        margin: 0 0 8px 0;
        font-size: 1.2em;
    }
    .alimento-card p {
        color: #23272f;
        margin: 4px 0;
    }
    .alimento-card .btn-editar {
        width: 100%;
        margin-top: 12px;
        padding: 12px;
        font-size: 1em;
    }
    
    @media (max-width: 768px) {
        .alimentos-table {
            display: none;
        }
        .alimentos-mobile {
            display: block;
        }
        .modal-content {
            margin: 10% auto;
            width: 95%;
            padding: 20px;
        }
    }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>

<?php include 'menu_lateral_admin.php'; ?>
<?php include 'admin_carrusel.php'; ?>
<main class="admin-main">
    <div class="dashboard">
        <h1>Gestionar Alimentos</h1>
        <?php if ($mensaje): ?>
            <p class="msg-success"> <?= $mensaje ?> </p>
        <?php endif; ?>
        
        <!-- Formulario de registro -->
        <div class="form-card">
            <h2>Registrar Nuevo Alimento</h2>
            <form method="POST">
                <label>Nombre:
                    <input type="text" name="nombre" required>
                </label>
                <label>Calorías:
                    <input type="number" name="calorias" required>
                </label>
                <label>Gramos:
                    <input type="number" name="gramos" required min="1" value="100">
                </label>
                <label>Categoría:
                    <select name="categoria" required>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Registrar alimento</button>
            </form>
        </div>
        
        <!-- Formulario de importación CSV -->
        <div class="form-card">
            <h2>Importar Alimentos desde CSV</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="csv" accept=".csv" required>
                <input type="hidden" name="importar_csv" value="1">
                <button type="submit">Importar CSV</button>
            </form>
            <p style="font-size:0.98em;color:#888;margin-top:8px;">El archivo debe tener columnas: nombre, calorías, gramos, categoría (número del 1 al 5, opcional). Si no se indica, se usará "Frutas y verduras".</p>
            <ul style="font-size:0.97em;color:#888;margin-top:4px;">
                <li>1 = Frutas y verduras</li>
                <li>2 = Cereales y derivados</li>
                <li>3 = Carnes, pescados y huevos</li>
                <li>4 = Lácteos y derivados</li>
                <li>5 = Grasas, snacks y otros</li>
            </ul>
        </div>
        
        <!-- Lista de alimentos -->
        <div class="form-card">
            <h2>Alimentos Registrados</h2>
            <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas borrar todos los alimentos? Esta acción no se puede deshacer.');">
                <button type="submit" name="borrar_todo" class="panel-btn" style="background:#e74c3c;margin-bottom:18px;">🗑️ Borrar todo</button>
            </form>
            
            <!-- Vista de escritorio -->
            <table class="alimentos-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Calorías</th>
                        <th>Gramos</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alimentos as $alimento): ?>
                    <tr>
                        <td><?= htmlspecialchars($alimento['nombre']) ?></td>
                        <td><?= $alimento['calorias'] ?> cal</td>
                        <td><?= $alimento['gramos'] ?> g</td>
                        <td><?= htmlspecialchars($alimento['categoria']) ?></td>
                        <td>
                            <button class="btn-editar" onclick="editarAlimento(<?= $alimento['id'] ?>, '<?= htmlspecialchars($alimento['nombre']) ?>', <?= $alimento['calorias'] ?>, <?= $alimento['gramos'] ?>, '<?= htmlspecialchars($alimento['categoria']) ?>')">
                                ✏️ Editar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Vista móvil -->
            <div class="alimentos-mobile">
                <?php foreach ($alimentos as $alimento): ?>
                <div class="alimento-card">
                    <h3><?= htmlspecialchars($alimento['nombre']) ?></h3>
                    <p><strong>Calorías:</strong> <?= $alimento['calorias'] ?> cal</p>
                    <p><strong>Gramos:</strong> <?= $alimento['gramos'] ?> g</p>
                    <p><strong>Categoría:</strong> <?= htmlspecialchars($alimento['categoria']) ?></p>
                    <button class="btn-editar" onclick="editarAlimento(<?= $alimento['id'] ?>, '<?= htmlspecialchars($alimento['nombre']) ?>', <?= $alimento['calorias'] ?>, <?= $alimento['gramos'] ?>, '<?= htmlspecialchars($alimento['categoria']) ?>')">
                        ✏️ Editar Alimento
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<!-- Modal de edición -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Editar Alimento</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="editar_alimento" value="1">
            <input type="hidden" id="edit_id" name="id">
            
            <label>Nombre:
                <input type="text" id="edit_nombre" name="nombre" required>
            </label>
            <label>Calorías:
                <input type="number" id="edit_calorias" name="calorias" required>
            </label>
            <label>Gramos:
                <input type="number" id="edit_gramos" name="gramos" required min="1">
            </label>
            <label>Categoría:
                <select id="edit_categoria" name="categoria" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat ?>"><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            
            <div class="modal-buttons">
                <button type="submit" class="btn-guardar">Guardar Cambios</button>
                <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarAlimento(id, nombre, calorias, gramos, categoria) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_calorias').value = calorias;
    document.getElementById('edit_gramos').value = gramos;
    document.getElementById('edit_categoria').value = categoria;
    document.getElementById('modalEditar').style.display = 'block';
}

function cerrarModal() {
    document.getElementById('modalEditar').style.display = 'none';
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalEditar');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
</body>
</html> 