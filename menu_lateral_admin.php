<?php
if (!isset($_SESSION)) session_start();
$nombre = $_SESSION['usuario_nombre'] ?? '';
?>
<nav class="sidebar-admin" id="sidebar">
    <?php if (file_exists('logo.png')): ?>
        <div style="text-align:center;margin-bottom:18px;">
            <img src="logo.png" alt="Logo" style="max-width:120px;max-height:60px;object-fit:contain;" />
        </div>
    <?php endif; ?>
    <div class="menu-admin">
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_dashboard.php'?' active':'' ?>" href="admin_dashboard.php"><span>🏠</span><span>Inicio</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_altas.php'?' active':'' ?>" href="admin_altas.php"><span>➕</span><span>Alta Usuario</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_usuarios.php'?' active':'' ?>" href="admin_usuarios.php"><span>👥</span><span>Usuarios</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_alimentos.php'?' active':'' ?>" href="admin_alimentos.php"><span>🍎</span><span>Alimentos</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_ejercicios.php'?' active':'' ?>" href="admin_ejercicios.php"><span>🏋️‍♂️</span><span>Ejercicios</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_calorias.php'?' active':'' ?>" href="admin_calorias.php"><span>🔥</span><span>Calorías</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='admin_links.php'?' active':'' ?>" href="admin_links.php"><span>🔗</span><span>Links</span></a>
        <a href="admin_asignar.php" class="menu-link"><span>🤝</span> Asignar Clientes</a>
        <a class="logout-link menu-link" href="logout.php"><span>🚪</span><span>Salir</span></a>
    </div>
</nav> 