<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'cliente') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$cliente_id = $_SESSION['usuario_id'];
$rutina_id = intval($_POST['rutina_id'] ?? 0);
$ejercicio_id = intval($_POST['ejercicio_id'] ?? 0);
$completado = intval($_POST['completado'] ?? 0);
$series_completadas = intval($_POST['series_completadas'] ?? 0);
$repeticiones_completadas = intval($_POST['repeticiones_completadas'] ?? 0);
$fecha_ejercicio = $_POST['fecha_ejercicio'] ?? date('Y-m-d');

if (!$rutina_id || !$ejercicio_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

try {
    // Verificar si ya existe un registro para este ejercicio en esta fecha
    $stmt = $conn->prepare('SELECT id FROM seguimiento_ejercicios WHERE cliente_id = ? AND rutina_id = ? AND ejercicio_id = ? AND fecha_ejercicio = ?');
    $stmt->bind_param('iiis', $cliente_id, $rutina_id, $ejercicio_id, $fecha_ejercicio);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->fetch_assoc();
    $stmt->close();

    if ($existe) {
        // Actualizar registro existente
        $stmt = $conn->prepare('UPDATE seguimiento_ejercicios SET completado = ?, fecha_completado = ?, series_completadas = ?, repeticiones_completadas = ? WHERE id = ?');
        $fecha_completado = $completado ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param('issii', $completado, $fecha_completado, $series_completadas, $repeticiones_completadas, $existe['id']);
    } else {
        // Crear nuevo registro
        $stmt = $conn->prepare('INSERT INTO seguimiento_ejercicios (cliente_id, rutina_id, ejercicio_id, fecha_ejercicio, completado, fecha_completado, series_completadas, repeticiones_completadas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $fecha_completado = $completado ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param('iiisisii', $cliente_id, $rutina_id, $ejercicio_id, $fecha_ejercicio, $completado, $fecha_completado, $series_completadas, $repeticiones_completadas);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $completado ? 'Ejercicio marcado como completado' : 'Ejercicio marcado como pendiente',
            'completado' => $completado
        ]);
    } else {
        throw new Exception('Error al actualizar seguimiento');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}

$conn->close();
?> 