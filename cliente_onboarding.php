<?php
session_start();
if(!isset($_SESSION['usuario_rol'])||$_SESSION['usuario_rol']!=='cliente'){
  header('Location: login.php');exit();}
$host='localhost';$db='nutriapp';$user='nutri_admin';$pass='_Mary190577_';
$conn=new mysqli($host,$user,$pass,$db);
$conn->query("CREATE TABLE IF NOT EXISTS onboarding_cliente (cliente_id INT PRIMARY KEY, objetivo VARCHAR(50), dias INT, equipo VARCHAR(255), nivel VARCHAR(50), completo TINYINT(1) DEFAULT 0)");
$cliente_id=$_SESSION['usuario_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $objetivo=$_POST['objetivo']??'';$dias=intval($_POST['dias']??3);$equipo=implode(',',$_POST['equipo']??[]);$nivel=$_POST['nivel']??'';
  $stmt=$conn->prepare('REPLACE INTO onboarding_cliente (cliente_id,objetivo,dias,equipo,nivel,completo) VALUES (?,?,?,?,?,1)');
  $stmt->bind_param('isiss',$cliente_id,$objetivo,$dias,$equipo,$nivel);
  if($stmt->execute()){
    header('Location: cliente_dashboard.php');exit();
  }
}
$conn->close();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Bienvenido a NutriApp</title><link rel="stylesheet" href="css/cliente-ui.css"><style>.container{max-width:500px;margin:60px auto;background:#fff;border-radius:18px;padding:32px;box-shadow:var(--shadow-card);}h1{text-align:center;margin-bottom:24px;color:var(--color-primary);}label{font-weight:600;display:block;margin:12px 0 4px;}input,select{width:100%;padding:10px;border:1px solid #ccd5e1;border-radius:10px;} .equipos{display:flex;flex-wrap:wrap;gap:8px} .equipos label{font-weight:400} .btn{margin-top:24px}</style></head><body><div class="container card"><h1>Configura tu plan</h1><form method="post"><label>Objetivo principal</label><select name="objetivo" required><option value="Perder grasa">Perder grasa</option><option value="Ganar músculo">Ganar músculo</option><option value="Mantenerme">Mantenerme</option></select><label>Días disponibles para entrenar por semana</label><select name="dias"><?php for($i=2;$i<=6;$i++):?><option value="<?= $i ?>"><?= $i ?> días</option><?php endfor;?></select><label>Equipo disponible</label><div class="equipos"><label><input type="checkbox" name="equipo[]" value="Mancuernas"> Mancuernas</label><label><input type="checkbox" name="equipo[]" value="Barra"> Barra</label><label><input type="checkbox" name="equipo[]" value="Bandas"> Bandas</label><label><input type="checkbox" name="equipo[]" value="Máquinas"> Máquinas</label></div><label>Nivel actual</label><select name="nivel"><option value="Principiante">Principiante</option><option value="Intermedio">Intermedio</option><option value="Avanzado">Avanzado</option></select><button class="btn btn-primary" type="submit">Guardar y continuar</button></form></div></body></html>