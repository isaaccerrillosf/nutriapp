<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Configuración de la base de datos
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';

$mensaje = '';
$seccion_activa = $_GET['seccion'] ?? 'usuarios';

// Lógica de registro de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die('Error de conexión: ' . $conn->connect_error);
    }

    switch ($_POST['accion']) {
        case 'registrar_usuario':
            $nombre = $_POST['nombre'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? 'cliente';
            $telefono = $_POST['telefono'] ?? '';
            
            if ($rol === 'admin') {
                $rol = 'cliente'; // No permitir crear admins desde aquí
            }
            
            if ($nombre && $email && $password && $telefono && in_array($rol, ['nutriologo', 'cliente'])) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, telefono, password, rol) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $nombre, $email, $telefono, $hash, $rol);
                if ($stmt->execute()) {
                    $mensaje = 'Usuario creado correctamente.';
                } else {
                    $mensaje = 'Error al crear usuario: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensaje = 'Datos incompletos o rol inválido.';
            }
            break;

        case 'registrar_ejercicio':
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $grupo_muscular = $_POST['grupo_muscular'] ?? '';
            $video_url = $_POST['video_url'] ?? '';
            
            if ($nombre && $descripcion && $grupo_muscular) {
                $stmt = $conn->prepare('INSERT INTO ejercicios (nombre, descripcion, grupo_muscular, video_url) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $nombre, $descripcion, $grupo_muscular, $video_url);
                if ($stmt->execute()) {
                    $mensaje = 'Ejercicio registrado correctamente.';
                } else {
                    $mensaje = 'Error al registrar ejercicio: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensaje = 'Datos incompletos.';
            }
            break;

        case 'registrar_alimento':
            $nombre = $_POST['nombre'] ?? '';
            $calorias = $_POST['calorias'] ?? '';
            $proteinas = $_POST['proteinas'] ?? '';
            $carbohidratos = $_POST['carbohidratos'] ?? '';
            $grasas = $_POST['grasas'] ?? '';
            $tipo_comida = $_POST['tipo_comida'] ?? '';
            
            if ($nombre && $calorias && $proteinas && $carbohidratos && $grasas && $tipo_comida) {
                $stmt = $conn->prepare('INSERT INTO alimentos (nombre, calorias, proteinas, carbohidratos, grasas, tipo_comida) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sddddd', $nombre, $calorias, $proteinas, $carbohidratos, $grasas, $tipo_comida);
                if ($stmt->execute()) {
                    $mensaje = 'Alimento registrado correctamente.';
                } else {
                    $mensaje = 'Error al registrar alimento: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensaje = 'Datos incompletos.';
            }
            break;

        case 'asignar_cliente':
            $cliente_id = $_POST['cliente_id'] ?? '';
            $nutriologo_id = $_POST['nutriologo_id'] ?? '';
            
            if ($cliente_id && $nutriologo_id) {
                // Primero verificar si ya existe la asignación
                $check_stmt = $conn->prepare('SELECT id FROM nutriologo_cliente WHERE cliente_id = ? AND nutriologo_id = ?');
                $check_stmt->bind_param('ii', $cliente_id, $nutriologo_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows == 0) {
                    // Si no existe, crear la asignación
                    $stmt = $conn->prepare('INSERT INTO nutriologo_cliente (cliente_id, nutriologo_id) VALUES (?, ?)');
                    $stmt->bind_param('ii', $cliente_id, $nutriologo_id);
                    if ($stmt->execute()) {
                        $mensaje = 'Cliente asignado correctamente.';
                    } else {
                        $mensaje = 'Error al asignar cliente: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $mensaje = 'El cliente ya está asignado a este nutriólogo.';
                }
                $check_stmt->close();
            } else {
                $mensaje = 'Datos incompletos.';
            }
            break;

        case 'editar_usuario':
            $usuario_id = $_POST['usuario_id'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $email = $_POST['email'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $rol = $_POST['rol'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($usuario_id && $nombre && $email && $telefono && $rol && in_array($rol, ['nutriologo', 'cliente'])) {
                if ($password) {
                    // Si se proporciona una nueva contraseña, actualizarla también
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, rol = ?, password = ? WHERE id = ?');
                    $stmt->bind_param('sssssi', $nombre, $email, $telefono, $rol, $hash, $usuario_id);
                } else {
                    // Si no se proporciona contraseña, mantener la existente
                    $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, rol = ? WHERE id = ?');
                    $stmt->bind_param('ssssi', $nombre, $email, $telefono, $rol, $usuario_id);
                }
                
                if ($stmt->execute()) {
                    $mensaje = 'Usuario actualizado correctamente.';
                } else {
                    $mensaje = 'Error al actualizar usuario: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensaje = 'Datos incompletos o rol inválido.';
            }
            break;
    }
    
    $conn->close();
}

// Obtener datos para los formularios
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Obtener clientes sin nutriólogo asignado
$clientes_sin_nutriologo = [];
$result = $conn->query("SELECT u.id, u.nombre, u.email FROM usuarios u 
                        LEFT JOIN nutriologo_cliente nc ON u.id = nc.cliente_id 
                        WHERE u.rol = 'cliente' AND nc.nutriologo_id IS NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clientes_sin_nutriologo[] = $row;
    }
}

// Obtener nutriólogos
$nutriologos = [];
$result = $conn->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'nutriologo'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $nutriologos[] = $row;
    }
}

// Obtener todos los usuarios para la lista de edición
$usuarios = [];
$result = $conn->query("SELECT id, nombre, email, telefono, rol, fecha_registro FROM usuarios ORDER BY fecha_registro DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Obtener alimentos para la tabla de calorías
$alimentos = [];
$result = $conn->query("SELECT * FROM alimentos ORDER BY nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $alimentos[] = $row;
    }
}

$conn->close();

// Manejo de subida de logo
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $permitidos = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
    if (in_array($_FILES['logo']['type'], $permitidos)) {
        move_uploaded_file($_FILES['logo']['tmp_name'], 'logo.png');
        $mensaje = 'Logo actualizado correctamente.';
    } else {
        $mensaje = 'Solo se permiten imágenes PNG, JPG, JPEG o GIF.';
    }
}
$mostrar_form_logo = isset($_GET['logo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Dashboard Administrador</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'header_admin.php'; ?>
<?php include 'menu_lateral_admin.php'; ?>
<?php include 'admin_carrusel.php'; ?>
<main class="admin-main">
    <section class="card-section panel-control">
        <h2>Panel de Control</h2>
        <ul>
            <li><a href="admin_altas.php">➕ Alta de Usuario</a></li>
            <li><a href="admin_usuarios.php">👥 Ver Usuarios</a></li>
            <li><a href="admin_alimentos.php">🍎 Gestión de Alimentos</a></li>
            <li><a href="admin_ejercicios.php">🏋️‍♂️ Gestión de Ejercicios</a></li>
            <li><a href="admin_calorias.php">🔥 Gestión de Calorías</a></li>
            <li><a href="admin_asignar.php">🤝 Asignar Clientes a Nutriólogos</a></li>
            <li><a href="admin_links.php">🔗 Links Útiles</a></li>
        </ul>
    </section>
</main>
</body>
</html>
