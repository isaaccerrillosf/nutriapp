<?php
if (!isset($_SESSION)) session_start();
$nombre = $_SESSION['usuario_nombre'] ?? '';
?>
<header class="header-admin">
    <div class="header-content-admin">
        <div class="header-saludo-admin">
            <span class="saludo-texto-admin">¡Hola, <?= htmlspecialchars($nombre) ?>!</span>
        </div>
        <?php include 'admin_carrusel.php'; ?>
    </div>
</header> 