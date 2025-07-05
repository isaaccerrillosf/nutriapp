<?php
session_start();
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
}
$host='localhost';$db='nutriapp';$user='nutri_admin';$pass='_Mary190577_';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){die('Error:'.$conn->connect_error);} 
// obtener grupos musculares
$grupos=[];$res=$conn->query('SELECT DISTINCT grupo_muscular FROM ejercicios ORDER BY grupo_muscular');
while($row=$res->fetch_assoc()){ $grupos[]=$row['grupo_muscular']; }
// filtro
$filtro_grupo=$_GET['grupo']??'';$busqueda=$_GET['q']??'';
$stmt=$conn->prepare('SELECT * FROM ejercicios WHERE ( ? = "" OR grupo_muscular = ?) AND ( ? = "" OR nombre LIKE CONCAT("%", ?, "%")) ORDER BY nombre');
$stmt->bind_param('ssss',$filtro_grupo,$filtro_grupo,$busqueda,$busqueda);
$stmt->execute();$resultado=$stmt->get_result();$ejercicios=[];while($row=$resultado->fetch_assoc()){$ejercicios[]=$row;}
$stmt->close();$conn->close();
function youtube_id($url){ if(!$url) return null; if(preg_match('/(?:v=|be\/)([\w-]+)/',$url,$m)){return $m[1];} return null; }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0,user-scalable=yes"><title>Biblioteca de Ejercicios</title>
<link rel="stylesheet" href="css/estilos.css"><link rel="stylesheet" href="css/cliente.css">
<style>
.cliente-main{margin-left:250px;padding:80px 18px;background:#f4f8fb;min-height:100vh}
.filtros{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px}
.filtros select,.filtros input{padding:10px 14px;border:1px solid #ccd5e1;border-radius:10px;font-size:1em}
.grid-ejercicios{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}
.ej-card{background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border:1.5px solid #e6ecf3;overflow:hidden;display:flex;flex-direction:column}
.ej-card img{width:100%;aspect-ratio:16/9;object-fit:cover}
.ej-card .ej-info{padding:12px 14px;flex:1}
.ej-card h4{margin:0 0 6px 0;color:#0074D9;font-size:1.05em;font-weight:700}
.ej-card p{margin:0;font-size:0.9em;color:#5f6b7a}
.play-overlay{position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.4em;background:rgba(0,0,0,0.2);opacity:0;transition:opacity .2s}
.ej-card:hover .play-overlay{opacity:1}
@media(max-width:900px){.cliente-main{margin-left:64px}}
@media(max-width:600px){.cliente-main{margin-left:0;padding-top:120px}}
</style></head><body>
<?php include 'header_cliente.php'; ?>
<?php include 'menu_lateral_cliente.php'; ?>
<main class="cliente-main">
    <h1 style="color:#0074D9;font-size:1.7em;font-weight:700;text-align:center;margin-bottom:24px">Biblioteca de Ejercicios</h1>
    <form class="filtros" method="get">
        <select name="grupo" onchange="this.form.submit()">
            <option value="">Todos los grupos musculares</option>
            <?php foreach($grupos as $g):?>
                <option value="<?= htmlspecialchars($g) ?>" <?= $g==$filtro_grupo?'selected':''?>><?= htmlspecialchars($g) ?></option>
            <?php endforeach;?>
        </select>
        <input type="text" name="q" placeholder="Buscar ejercicio" value="<?= htmlspecialchars($busqueda) ?>" />
        <button type="submit" style="display:none"></button>
    </form>
    <div class="grid-ejercicios">
        <?php foreach($ejercicios as $ej):
            $yt=youtube_id($ej['youtube_url']);
            $thumb=$yt?"https://img.youtube.com/vi/$yt/mqdefault.jpg":'logo.png';?>
            <div class="ej-card">
                <div style="position:relative">
                    <img src="<?= $thumb ?>" alt="thumb" />
                    <?php if($yt):?>
                    <a class="play-overlay" href="<?= htmlspecialchars($ej['youtube_url']) ?>" target="_blank">▶️</a>
                    <?php endif;?>
                </div>
                <div class="ej-info">
                    <h4><?= htmlspecialchars($ej['nombre']) ?></h4>
                    <p><?= htmlspecialchars($ej['grupo_muscular']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<script>
// filtro busqueda instantáneo
const searchInput=document.querySelector('input[name="q"]');if(searchInput){searchInput.addEventListener('keyup',e=>{if(e.key==='Enter'){e.target.form.submit();}})}
</script>
</body></html>