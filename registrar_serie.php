<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['usuario_rol'])|| $_SESSION['usuario_rol']!=='cliente'){
    http_response_code(403);echo json_encode(['error'=>'No autorizado']);exit();
}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Método no permitido']);exit();}
$host='localhost';$db='nutriapp';$user='nutri_admin';$pass='_Mary190577_';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){http_response_code(500);echo json_encode(['error'=>'Conexión fallida']);exit();}
$cliente_id=$_SESSION['usuario_id'];
$rutina_id=intval($_POST['rutina_id']??0);
$ejercicio_id=intval($_POST['ejercicio_id']??0);
$peso=floatval($_POST['peso']??0);
$reps=intval($_POST['repeticiones']??0);
$fecha=date('Y-m-d');
if(!$rutina_id||!$ejercicio_id||!$reps){http_response_code(400);echo json_encode(['error'=>'Datos incompletos']);exit();}
// crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS registro_series (id INT AUTO_INCREMENT PRIMARY KEY, cliente_id INT, rutina_id INT, ejercicio_id INT, peso DECIMAL(6,2), repeticiones INT, fecha DATE)");
$stmt=$conn->prepare('INSERT INTO registro_series (cliente_id,rutina_id,ejercicio_id,peso,repeticiones,fecha) VALUES (?,?,?,?,?,?)');
$stmt->bind_param('iiidis',$cliente_id,$rutina_id,$ejercicio_id,$peso,$reps,$fecha);
if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{http_response_code(500);echo json_encode(['error'=>'No guardado']);}
$stmt->close();$conn->close();
?>