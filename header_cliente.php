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