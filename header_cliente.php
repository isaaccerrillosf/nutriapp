<?php
if (!isset($_SESSION)) session_start();
$nombre = $_SESSION['usuario_nombre'] ?? '';
?>
<header class="header-cliente">
    <div class="header-content-cliente">
        <div class="header-logo-cliente">
            <?php if (file_exists('logo.png')): ?>
                <img src="logo.png" alt="Logo" class="logo-cliente" />
            <?php endif; ?>
        </div>
        <div class="header-saludo-cliente">
            <span class="saludo-texto-cliente">¡Hola, <?= htmlspecialchars($nombre) ?>!</span>
        </div>
        <?php include 'cliente_carrusel.php'; ?>
    </div>
</header>

<!-- Botón de menú hamburguesa para móvil -->
<button class="hamburger-menu" onclick="toggleMenu()" style="display: none;">
    ☰
</button>

<link rel="stylesheet" href="css/cliente-ui.css">

<!-- Bottom navigation for mobile -->
<nav class="bottom-bar">
    <div class="bottom-bar-icons">
        <a href="cliente_dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='cliente_dashboard.php'?'active':'' ?>">🏠</a>
        <a href="cliente_rutina.php" class="<?= basename($_SERVER['PHP_SELF'])=='cliente_rutina.php'?'active':'' ?>">💪</a>
        <a href="cliente_dieta.php" class="<?= basename($_SERVER['PHP_SELF'])=='cliente_dieta.php'?'active':'' ?>">🍽️</a>
        <a href="cliente_resultados.php" class="<?= basename($_SERVER['PHP_SELF'])=='cliente_resultados.php'?'active':'' ?>">📊</a>
        <a href="cliente_ejercicios.php" class="<?= basename($_SERVER['PHP_SELF'])=='cliente_ejercicios.php'?'active':'' ?>">📚</a>
    </div>
</nav> 