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

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar_ejercicio'])) {
    $nombre = $_POST['nombre'] ?? '';
    $grupo = $_POST['grupo_muscular'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $youtube_url = $_POST['youtube_url'] ?? '';
    $foto = $_FILES['foto'] ?? null;
    $foto_nombre = null;
    if ($foto && $foto['tmp_name']) {
        $ext = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $foto_nombre = uniqid('ej_', true) . '.' . $ext;
        if (!is_dir('fotos_ejercicios')) mkdir('fotos_ejercicios');
        move_uploaded_file($foto['tmp_name'], 'fotos_ejercicios/' . $foto_nombre);
    }
    if ($nombre && $grupo) {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die('Error de conexión: ' . $conn->connect_error);
        }
        $stmt = $conn->prepare('INSERT INTO ejercicios (nombre, grupo_muscular, descripcion, youtube_url, foto) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $nombre, $grupo, $descripcion, $youtube_url, $foto_nombre);
        if ($stmt->execute()) {
            $mensaje = 'Ejercicio registrado correctamente.';
        } else {
            $mensaje = 'Error al registrar ejercicio: ' . $stmt->error;
        }
        $stmt->close();
        $conn->close();
    } else {
        $mensaje = 'Nombre y grupo muscular son obligatorios.';
    }
}

// Procesar edición de ejercicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_ejercicio'])) {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $grupo = $_POST['grupo_muscular'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $youtube_url = $_POST['youtube_url'] ?? '';
    $foto = $_FILES['foto'] ?? null;
    $foto_nombre = null;
    if ($foto && $foto['tmp_name']) {
        $ext = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $foto_nombre = uniqid('ej_', true) . '.' . $ext;
        if (!is_dir('fotos_ejercicios')) mkdir('fotos_ejercicios');
        move_uploaded_file($foto['tmp_name'], 'fotos_ejercicios/' . $foto_nombre);
    }
    if ($id && $nombre && $grupo) {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die('Error de conexión: ' . $conn->connect_error);
        }
        if ($foto_nombre) {
            $stmt = $conn->prepare('UPDATE ejercicios SET nombre = ?, grupo_muscular = ?, descripcion = ?, youtube_url = ?, foto = ? WHERE id = ?');
            $stmt->bind_param('sssssi', $nombre, $grupo, $descripcion, $youtube_url, $foto_nombre, $id);
        } else {
            $stmt = $conn->prepare('UPDATE ejercicios SET nombre = ?, grupo_muscular = ?, descripcion = ?, youtube_url = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $nombre, $grupo, $descripcion, $youtube_url, $id);
        }
        if ($stmt->execute()) {
            $mensaje = 'Ejercicio actualizado correctamente.';
        } else {
            $mensaje = 'Error al actualizar ejercicio: ' . $stmt->error;
        }
        $stmt->close();
        $conn->close();
    } else {
        $mensaje = 'Datos incompletos para actualizar.';
    }
}

// Procesar borrado masivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_todos'])) {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die('Error de conexión: ' . $conn->connect_error);
    }
    // Primero borra las relaciones de seguimiento
    $conn->query('DELETE FROM seguimiento_ejercicios');
    // Luego borra las relaciones de rutina
    $conn->query('DELETE FROM rutina_ejercicios');
    // Luego borra los ejercicios
    $conn->query('DELETE FROM ejercicios');
    $conn->close();
    $mensaje = 'Todos los ejercicios han sido eliminados.';
}

// Procesar importación CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar_csv'])) {
    if (isset($_FILES['csv']) && $_FILES['csv']['tmp_name']) {
        $archivo = $_FILES['csv']['tmp_name'];
        $handle = fopen($archivo, 'r');
        if ($handle) {
            $header = fgetcsv($handle);
            $col_idx = array_flip($header);
            $conn = new mysqli($host, $user, $pass, $db);
            if ($conn->connect_error) {
                die('Error de conexión: ' . $conn->connect_error);
            }
            $importados = 0;
            while (($data = fgetcsv($handle)) !== false) {
                $nombre = $data[$col_idx['nombre']] ?? '';
                $grupo = $data[$col_idx['grupo_muscular']] ?? '';
                $descripcion = $data[$col_idx['descripcion']] ?? '';
                $youtube_url = $data[$col_idx['youtube_url']] ?? '';
                $foto_csv = $data[$col_idx['foto']] ?? '';
                $foto_nombre = null;
                if ($foto_csv) {
                    if (filter_var($foto_csv, FILTER_VALIDATE_URL)) {
                        $ext = pathinfo(parse_url($foto_csv, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $foto_nombre = uniqid('ej_', true) . '.' . $ext;
                        if (!is_dir('fotos_ejercicios')) mkdir('fotos_ejercicios');
                        file_put_contents('fotos_ejercicios/' . $foto_nombre, file_get_contents($foto_csv));
                    } else {
                        $foto_nombre = $foto_csv;
                    }
                }
                if ($nombre && $grupo) {
                    $stmt = $conn->prepare('INSERT INTO ejercicios (nombre, grupo_muscular, descripcion, youtube_url, foto) VALUES (?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssss', $nombre, $grupo, $descripcion, $youtube_url, $foto_nombre);
                    $stmt->execute();
                    $stmt->close();
                    $importados++;
                }
            }
            $conn->close();
            fclose($handle);
            $mensaje = "Se importaron $importados ejercicios desde el CSV.";
        } else {
            $mensaje = 'No se pudo leer el archivo CSV.';
        }
    } else {
        $mensaje = 'No se seleccionó ningún archivo CSV.';
    }
}

// Obtener lista de ejercicios
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$ejercicios = [];
$result = $conn->query('SELECT id, nombre, grupo_muscular, descripcion, youtube_url, foto FROM ejercicios ORDER BY nombre');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ejercicios[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestión de Ejercicios</title>
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
    .form-card input[type="number"],
    .form-card input[type="url"],
    .form-card textarea {
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
    .form-card textarea {
        min-height: 100px;
        resize: vertical;
    }
    .form-card input[type="text"]::placeholder,
    .form-card input[type="number"]::placeholder,
    .form-card input[type="url"]::placeholder,
    .form-card textarea::placeholder {
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
    
    /* Estilos para la tabla de ejercicios */
    .ejercicios-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .ejercicios-table th {
        background: #0074D9;
        color: white;
        padding: 16px;
        text-align: left;
        font-weight: 600;
    }
    .ejercicios-table td {
        padding: 16px;
        border-bottom: 1px solid #e6ecf3;
        color: #23272f;
    }
    .ejercicios-table tr:hover {
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
    .modal-form input,
    .modal-form textarea {
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
    .modal-form textarea {
        min-height: 100px;
        resize: vertical;
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
    .ejercicios-mobile {
        display: none;
    }
    .ejercicio-card {
        background: white;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .ejercicio-card h3 {
        color: #0074D9;
        margin: 0 0 8px 0;
        font-size: 1.2em;
    }
    .ejercicio-card p {
        color: #23272f;
        margin: 4px 0;
    }
    .ejercicio-card .btn-editar {
        width: 100%;
        margin-top: 12px;
        padding: 12px;
        font-size: 1em;
    }
    
    @media (max-width: 768px) {
        .ejercicios-table {
            display: none;
        }
        .ejercicios-mobile {
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
        <h1>Gestionar Ejercicios</h1>
        <?php if ($mensaje): ?>
            <p class="msg-success"> <?= $mensaje ?> </p>
        <?php endif; ?>
        
        <!-- Formulario de registro -->
        <div class="form-card">
            <h2>Registrar Nuevo Ejercicio</h2>
            <form method="POST" enctype="multipart/form-data">
                <label>Nombre:
                    <input type="text" name="nombre" required>
                </label>
                <label>Grupo muscular:
                    <select name="grupo_muscular" required>
                        <option value="">Selecciona un grupo muscular</option>
                        <option value="Pecho">Pecho</option>
                        <option value="Espalda">Espalda</option>
                        <option value="Hombros">Hombros</option>
                        <option value="Bíceps">Bíceps</option>
                        <option value="Tríceps">Tríceps</option>
                        <option value="Piernas">Piernas</option>
                        <option value="Glúteos">Glúteos</option>
                        <option value="Abdominales">Abdominales</option>
                        <option value="Antebrazos">Antebrazos</option>
                        <option value="Pantorrillas">Pantorrillas</option>
                    </select>
                </label>
                <label>Foto del ejercicio:
                    <input type="file" name="foto" accept="image/*">
                </label>
                <label>Descripción:
                    <textarea name="descripcion"></textarea>
                </label>
                <label>Link de YouTube:
                    <input type="url" name="youtube_url">
                </label>
                <button type="submit">Registrar ejercicio</button>
            </form>
        </div>

        <!-- Formulario de importación CSV -->
        <div class="form-card">
            <h2>Importar ejercicios desde CSV</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="csv" accept=".csv" required>
                <button type="submit" name="importar_csv">Importar CSV</button>
            </form>
            <p style="font-size:0.98em;color:#666;margin-top:8px;">El archivo debe tener las columnas: <b>nombre, grupo_muscular, descripcion, youtube_url, foto</b>. La foto puede ser URL o nombre de archivo local.</p>
            <a href="descargar_plantilla.php" style="color:#0074D9;font-weight:600;">Descargar plantilla de ejemplo CSV</a>
        </div>
        
        <!-- Lista de ejercicios -->
        <div class="form-card">
            <h2>Ejercicios Registrados</h2>
            <form method="POST" onsubmit="return confirm('¿Seguro que deseas borrar TODOS los ejercicios? Esta acción no se puede deshacer.');" style="margin-bottom:18px;">
                <button type="submit" name="borrar_todos" style="background:#e74c3c;color:#fff;padding:10px 22px;border:none;border-radius:8px;font-weight:700;font-size:1em;cursor:pointer;">🗑️ Borrar todos los ejercicios</button>
            </form>
            
            <!-- Vista de escritorio: tabla de ejercicios -->
            <table class="ejercicios-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Grupo Muscular</th>
                        <th>Foto</th>
                        <th>Descripción</th>
                        <th>YouTube</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ejercicios as $ejercicio): ?>
                    <tr>
                        <td><?= htmlspecialchars($ejercicio['nombre']) ?></td>
                        <td><?= htmlspecialchars($ejercicio['grupo_muscular']) ?></td>
                        <td>
                            <?php if (!empty($ejercicio['foto'])): ?>
                                <img src="fotos_ejercicios/<?= htmlspecialchars($ejercicio['foto']) ?>" alt="Foto" style="max-width:60px;max-height:60px;border-radius:8px;">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ejercicio['descripcion']) ?></td>
                        <td>
                            <?php if (!empty($ejercicio['youtube_url'])): ?>
                                <a href="<?= htmlspecialchars($ejercicio['youtube_url']) ?>" target="_blank" style="color: #0074D9;">▶ Ver video</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-editar" onclick="editarEjercicio(<?= $ejercicio['id'] ?>, '<?= htmlspecialchars($ejercicio['nombre']) ?>', '<?= htmlspecialchars($ejercicio['grupo_muscular']) ?>', '<?= htmlspecialchars($ejercicio['descripcion']) ?>', '<?= htmlspecialchars($ejercicio['youtube_url']) ?>')">
                                ✏️ Editar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Vista móvil: tarjetas individuales -->
            <div class="ejercicios-mobile">
                <?php foreach ($ejercicios as $ejercicio): ?>
                <div class="ejercicio-card">
                    <h3><?= htmlspecialchars($ejercicio['nombre']) ?></h3>
                    <p><strong>Grupo Muscular:</strong> <?= htmlspecialchars($ejercicio['grupo_muscular']) ?></p>
                    <?php if (!empty($ejercicio['foto'])): ?>
                        <img src="fotos_ejercicios/<?= htmlspecialchars($ejercicio['foto']) ?>" alt="Foto">
                    <?php endif; ?>
                    <p><strong>Descripción:</strong> <?= htmlspecialchars($ejercicio['descripcion']) ?></p>
                    <?php if (!empty($ejercicio['youtube_url'])): ?>
                        <p><strong>YouTube:</strong> <a href="<?= htmlspecialchars($ejercicio['youtube_url']) ?>" target="_blank" style="color: #0074D9;">▶ Ver video</a></p>
                    <?php endif; ?>
                    <button class="btn-editar" onclick="editarEjercicio(<?= $ejercicio['id'] ?>, '<?= htmlspecialchars($ejercicio['nombre']) ?>', '<?= htmlspecialchars($ejercicio['grupo_muscular']) ?>', '<?= htmlspecialchars($ejercicio['descripcion']) ?>', '<?= htmlspecialchars($ejercicio['youtube_url']) ?>')">
                        ✏️ Editar Ejercicio
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
            <h2 class="modal-title">Editar Ejercicio</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST" class="modal-form" enctype="multipart/form-data">
            <input type="hidden" name="editar_ejercicio" value="1">
            <input type="hidden" id="edit_id" name="id">
            
            <label>Nombre:
                <input type="text" id="edit_nombre" name="nombre" required>
            </label>
            <label>Grupo muscular:
                <select id="edit_grupo_muscular" name="grupo_muscular" required>
                    <option value="">Selecciona un grupo muscular</option>
                    <option value="Pecho">Pecho</option>
                    <option value="Espalda">Espalda</option>
                    <option value="Hombros">Hombros</option>
                    <option value="Bíceps">Bíceps</option>
                    <option value="Tríceps">Tríceps</option>
                    <option value="Piernas">Piernas</option>
                    <option value="Glúteos">Glúteos</option>
                    <option value="Abdominales">Abdominales</option>
                    <option value="Antebrazos">Antebrazos</option>
                    <option value="Pantorrillas">Pantorrillas</option>
                </select>
            </label>
            <label>Foto del ejercicio:
                <input type="file" name="foto" accept="image/*">
            </label>
            <label>Descripción:
                <textarea id="edit_descripcion" name="descripcion"></textarea>
            </label>
            <label>Link de YouTube:
                <input type="url" id="edit_youtube_url" name="youtube_url">
            </label>
            
            <div class="modal-buttons">
                <button type="submit" class="btn-guardar">Guardar Cambios</button>
                <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarEjercicio(id, nombre, grupo_muscular, descripcion, youtube_url) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_grupo_muscular').value = grupo_muscular;
    document.getElementById('edit_descripcion').value = descripcion;
    document.getElementById('edit_youtube_url').value = youtube_url;
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