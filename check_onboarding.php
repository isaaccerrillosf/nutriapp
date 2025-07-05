<?php
if(!isset($_SESSION))session_start();
$cliente_id=$_SESSION['usuario_id']??0;
if($_SESSION['usuario_rol']??''!=='cliente')return;
$host='localhost';$db='nutriapp';$user='nutri_admin';$pass='_Mary190577_';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) return;
$conn->query("CREATE TABLE IF NOT EXISTS onboarding_cliente (cliente_id INT PRIMARY KEY, objetivo VARCHAR(50), dias INT, equipo VARCHAR(255), nivel VARCHAR(50), completo TINYINT(1) DEFAULT 0)");
$onb=0;$res=$conn->query("SELECT completo FROM onboarding_cliente WHERE cliente_id=$cliente_id");
if($row=$res->fetch_assoc()){$onb=$row['completo'];}
$conn->close();
if(!$onb && basename($_SERVER['PHP_SELF'])!=='cliente_onboarding.php'){
    header('Location: cliente_onboarding.php');exit();
}
?>