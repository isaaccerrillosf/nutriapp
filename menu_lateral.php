<?php
if (!isset($_SESSION)) session_start();
$rol = $_SESSION['usuario_rol'] ?? '';
$nombre = $_SESSION['usuario_nombre'] ?? '';

function dias_restantes($fecha_cita) {
    $ahora = new DateTime();
    $fecha = new DateTime($fecha_cita);
    $diff = $ahora->diff($fecha);
    return $diff->invert ? 0 : $diff->days;
}

// Obtener teléfono del nutriólogo si es cliente
$telefono_nutriologo = null;
if ($rol === 'cliente' && isset($_SESSION['usuario_id'])) {
    $host = 'localhost';
    $db = 'nutriapp';
    $user = 'nutri_admin';
    $pass = '_Mary190577_';
    
    $conn = new mysqli($host, $user, $pass, $db);
    if (!$conn->connect_error) {
        $cliente_id = $_SESSION['usuario_id'];
        $stmt = $conn->prepare('SELECT u.telefono FROM usuarios u INNER JOIN nutriologo_cliente nc ON u.id = nc.nutriologo_id WHERE nc.cliente_id = ? AND u.rol = "nutriologo"');
        $stmt->bind_param('i', $cliente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $telefono_nutriologo = $row['telefono'];
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!-- Barra superior solo en móvil -->
<div class="navbar-fixed" style="position:fixed;top:0;left:0;width:100vw;height:56px;background:#fff;z-index:10000;display:flex;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,0.10);">
    <?php if (file_exists('logo.png')): ?>
        <img src="logo.png" alt="Logo" class="logo-movil" style="max-height:48px;max-width:110px;margin-left:10px;margin-right:10px;">
    <?php endif; ?>
    <span class="nombre-movil" style="font-size:1.12em;font-weight:600;color:#0074D9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"> <?= htmlspecialchars($nombre) ?> </span>
</div>
<?php if ($rol === 'cliente' || $rol === 'nutriologo'): ?>
<div class="navbar-nombre" style="position:fixed;top:56px;left:0;width:100vw;background:#fff;z-index:9999;text-align:center;padding:8px 0 6px 0;font-family:'Montserrat',Arial,sans-serif;font-size:1.15em;font-weight:600;color:#0074D9;letter-spacing:0.5px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
    <?php
    // Mostrar cuenta regresiva si existe próxima cita
    if (isset($proxima_cita)) {
        $dias = dias_restantes($proxima_cita['fecha_cita']);
        echo 'Próxima en ' . $dias . ' día' . ($dias==1?'':'s');
    }
    ?>
</div>
<?php endif; ?>
<?php if ($rol === 'cliente'): ?>
<nav class="sidebar-cliente" id="sidebar">
    <?php if (file_exists('logo.png')): ?>
        <img src="logo.png" alt="Logo" style="max-width:120px;max-height:60px;display:block;margin:0 auto 8px auto;" />
    <?php endif; ?>
    <div class="menu-cliente">
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='cliente_dashboard.php'?' active':'' ?>" href="cliente_dashboard.php"><span>🏠</span><span>Inicio</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='cliente_citas.php'?' active':'' ?>" href="cliente_citas.php"><span>📅</span><span>Mis Citas</span></a>
        <a class="menu-link" href="#" onclick="showSeccion('mensajes');return false;"><span>📨</span><span>Mensajes</span></a>
        <a class="menu-link" href="#" onclick="showSeccion('contacto');return false;"><span>☎️</span><span>Contacto</span></a>
        <?php if ($telefono_nutriologo): ?>
        <a class="menu-link whatsapp-link" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $telefono_nutriologo) ?>?text=Hola, necesito ayuda con mi plan nutricional" target="_blank"><span>💬</span><span>WhatsApp</span></a>
        <?php endif; ?>
        <a class="menu-link" href="#" onclick="showSeccion('recetas');return false;"><span>🥗</span><span>Recetas</span></a>
        <a class="logout-link menu-link" href="logout.php"><span>🚪</span><span>Cerrar sesión</span></a>
    </div>
</nav>
<?php elseif ($rol === 'nutriologo'): ?>
<nav class="sidebar-nutriologo" id="sidebar">
    <?php if (file_exists('logo.png')): ?>
        <img src="logo.png" alt="Logo" style="max-width:120px;max-height:60px;display:block;margin:0 auto 8px auto;" />
    <?php endif; ?>
    <div class="menu-nutriologo">
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_dashboard.php'?' active':'' ?>" href="nutriologo_dashboard.php"><span>👥</span><span>Mis Clientes</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_citas.php'?' active':'' ?>" href="nutriologo_citas.php"><span>📅</span><span>Citas</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_resultados.php'?' active':'' ?>" href="nutriologo_resultados.php"><span>📊</span><span>Resultados</span></a>
        <a class="logout-link menu-link" href="logout.php"><span>🚪</span><span>Cerrar sesión</span></a>
    </div>
</nav>
<?php elseif ($rol === 'admin'): ?>
<nav class="sidebar-admin" id="sidebar">
    <?php if (file_exists('logo.png')): ?>
        <img src="logo.png" alt="Logo" style="max-width:120px;max-height:60px;display:block;margin:0 auto 8px auto;" />
    <?php endif; ?>
    <div class="menu-admin">
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_dashboard.php'?' active':'' ?>" href="admin_dashboard.php"><span>👤</span><span>Registrar usuarios</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_dashboard.php' && ($_GET['seccion'] ?? '')=='editar_usuarios'?' active':'' ?>" href="admin_dashboard.php?seccion=editar_usuarios"><span>✏️</span><span>Gestionar usuarios</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_ejercicios.php'?' active':'' ?>" href="admin_ejercicios.php"><span>🏋️‍♂️</span><span>Registrar ejercicios</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_alimentos.php'?' active':'' ?>" href="admin_alimentos.php"><span>🍎</span><span>Registrar alimentos</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_asignar.php'?' active':'' ?>" href="admin_asignar.php"><span>🤝</span><span>Asignar clientes</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_calorias.php'?' active':'' ?>" href="admin_calorias.php"><span>🔥</span><span>Ver calorías</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_links.php'?' active':'' ?>" href="admin_links.php"><span>🖼️</span><span>Cambiar logo</span></a>
        <a class="logout-link menu-link" href="logout.php">Cerrar sesión</a>
    </div>
</nav>
<?php endif; ?>
<script>
// Mostrar hamburguesa solo en móvil
function mostrarHamburguesa() {
    var btn = document.getElementById('hamburger-btn');
    if (window.innerWidth <= 900) {
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }
}
window.addEventListener('resize', mostrarHamburguesa);
document.addEventListener('DOMContentLoaded', mostrarHamburguesa);
function toggleMenu() {
    var sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('menu-abierto');
}
document.addEventListener('click', function(event) {
    var sidebar = document.getElementById('sidebar');
    var hamburger = document.getElementById('hamburger-btn');
    if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
        sidebar.classList.remove('menu-abierto');
    }
});
</script>
<style>
@media (max-width: 600px) {
    .logo-movil { max-height: 60px !important; max-width: 140px !important; }
    .nombre-movil { font-size: 1.08em !important; margin-left: 6px; }
    .sidebar-admin, .sidebar-cliente, .sidebar-nutriologo { display: none !important; }
}

/* Estilos para el botón de WhatsApp en el menú lateral */
.whatsapp-link {
    color: #25D366 !important;
}

.whatsapp-link:hover {
    background: #e8f5e8 !important;
    color: #128C7E !important;
}

.whatsapp-link.active {
    background: #e8f5e8 !important;
    color: #128C7E !important;
}
</style> 