<?php /* Carrusel menú móvil para nutriólogo */ ?>
<div class="menu-carrusel-nutri" id="menu-carrusel-nutri">
    <a href="nutriologo_dashboard.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">🏠</span><span class="carrusel-text-nutri">Inicio</span></a>
    <a href="nutriologo_clientes.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">👥</span><span class="carrusel-text-nutri">Clientes</span></a>
    <a href="nutriologo_citas.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">📅</span><span class="carrusel-text-nutri">Citas</span></a>
    <a href="nutriologo_resultados.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">📈</span><span class="carrusel-text-nutri">Resultados</span></a>
    <a href="nutriologo_plan.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">🍽️</span><span class="carrusel-text-nutri">Plan</span></a>
    <a href="nutriologo_rutina.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">💪</span><span class="carrusel-text-nutri">Rutina</span></a>
    <a href="nutriologo_editar_plan.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">✏️</span><span class="carrusel-text-nutri">Editar Plan</span></a>
    <a href="nutriologo_editar_rutina.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">✏️</span><span class="carrusel-text-nutri">Editar Rutina</span></a>
    <a href="nutriologo_ejercicios.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">🏋️</span><span class="carrusel-text-nutri">Ejercicios</span></a>
    <a href="logout.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">🚪</span><span class="carrusel-text-nutri">Salir</span></a>
    <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'nutriologo'): ?>
        <a href="logo_nutriologo.php" class="carrusel-item-nutri"><span class="carrusel-icon-nutri">🖼️</span><span class="carrusel-text-nutri">Logo</span></a>
    <?php endif; ?>
</div> 