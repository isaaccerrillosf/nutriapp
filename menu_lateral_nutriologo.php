<?php
if (!isset($_SESSION)) session_start();
$nombre = $_SESSION['usuario_nombre'] ?? '';
?>
<nav class="sidebar-nutriologo" id="sidebar">
    <?php if (file_exists('logo.png')): ?>
        <img src="logo.png" alt="Logo" style="max-width:80px;max-height:40px;display:block;margin:0 auto 0 auto;" />
    <?php endif; ?>
    <div class="menu-nutriologo" style="margin-top:4px;">
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_dashboard.php'?' active':'' ?>" href="nutriologo_dashboard.php"><span>🏠</span><span>Inicio</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_clientes.php'?' active':'' ?>" href="nutriologo_clientes.php"><span>👥</span><span>Clientes</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_citas.php'?' active':'' ?>" href="nutriologo_citas.php"><span>📅</span><span>Citas</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_resultados.php'?' active':'' ?>" href="nutriologo_resultados.php"><span>📈</span><span>Resultados</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_plan.php'?' active':'' ?>" href="nutriologo_plan.php"><span>🍽️</span><span>Plan Nutricional</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_rutina.php'?' active':'' ?>" href="nutriologo_rutina.php"><span>💪</span><span>Rutina</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_editar_plan.php'?' active':'' ?>" href="nutriologo_editar_plan.php"><span>✏️</span><span>Editar Plan</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_editar_rutina.php'?' active':'' ?>" href="nutriologo_editar_rutina.php"><span>✏️</span><span>Editar Rutina</span></a>
        <a class="menu-link<?= basename($_SERVER['PHP_SELF'])=='nutriologo_ejercicios.php'?' active':'' ?>" href="nutriologo_ejercicios.php"><span>🏋️</span><span>Ejercicios</span></a>
        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'nutriologo'): ?>
            <a href="logo_nutriologo.php" class="menu-link"><span>🖼️</span><span>Logo</span></a>
        <?php endif; ?>
        <a class="logout-link menu-link" href="logout.php"><span>🚪</span><span>Salir</span></a>
    </div>
</nav> 