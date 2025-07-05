<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
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

// Obtener alimentos y suma de calorías
$alimentos = [];
$total_calorias = 0;
$result = $conn->query('SELECT nombre, calorias FROM alimentos');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $alimentos[] = $row;
        $total_calorias += (int)$row['calorias'];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Tabla de Calorías</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .admin-main {
            padding-top: 80px;
        }
        @media (max-width: 600px) {
            .admin-main {
                padding-top: 100px;
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
        <h1>Lista de Alimentos y Calorías Totales</h1>
        <table class="table-dark">
            <tr>
                <th>Nombre</th>
                <th>Calorías</th>
            </tr>
            <?php foreach ($alimentos as $alimento): ?>
            <tr>
                <td><?= htmlspecialchars($alimento['nombre']) ?></td>
                <td><?= htmlspecialchars($alimento['calorias']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <th>Total</th>
                <th><?= $total_calorias ?></th>
            </tr>
        </table>
    </div>
</main>
</body>
</html> 