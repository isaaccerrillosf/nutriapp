<?php
if (!isset($_SESSION)) session_start();
$nombre = $_SESSION['usuario_nombre'] ?? '';
?>
<nav class="sidebar-cliente" id="sidebar">
    <?php if (file_exists('logo.png')): ?>
        <img src="logo.png" alt="Logo" style="max-width:120px;max-height:60px;display:block;margin:0 auto 8px auto;" />
    <?php endif; ?>
    <div class="menu-cliente">
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='cliente_dashboard.php'?' active':'' ?>" href="cliente_dashboard.php"><span>🏠</span><span>Inicio</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='cliente_dieta.php'?' active':'' ?>" href="cliente_dieta.php"><span>🍽️</span><span>Dieta</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='cliente_rutina.php'?' active':'' ?>" href="cliente_rutina.php"><span>💪</span><span>Rutina</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='cliente_resultados.php'?' active':'' ?>" href="cliente_resultados.php"><span>📊</span><span>Resultados</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='cliente_ejercicios.php'?' active':'' ?>" href="cliente_ejercicios.php"><span>📚</span><span>Ejercicios</span></a>
        <a class="logout-link menu-link" href="logout.php"><span>🚪</span><span>Salir</span></a>
    </div>
</nav> 