<?php /* Carrusel menú móvil para admin */ ?>
<?php if (file_exists('logo.png')): ?>
    <div style="text-align:center;margin-bottom:8px;">
        <img src="logo.png" alt="Logo" style="max-width:90px;max-height:40px;object-fit:contain;display:block;margin:0 auto;" />
    </div>
<?php endif; ?>
<div class="menu-carrusel-admin" id="menu-carrusel-admin">
    <a href="admin_dashboard.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🏠</span><span class="carrusel-text-admin">Dashboard</span></a>
    <a href="admin_altas.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">➕</span><span class="carrusel-text-admin">Alta Usuario</span></a>
    <a href="admin_usuarios.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🧑‍🤝‍🧑</span><span class="carrusel-text-admin">Usuarios</span></a>
    <a href="admin_alimentos.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🍎</span><span class="carrusel-text-admin">Alimentos</span></a>
    <a href="admin_ejercicios.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🏋️‍♂️</span><span class="carrusel-text-admin">Ejercicios</span></a>
    <a href="admin_calorias.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🔥</span><span class="carrusel-text-admin">Calorías</span></a>
    <a href="admin_links.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🔗</span><span class="carrusel-text-admin">Links</span></a>
    <a href="admin_asignar.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🤝</span><span class="carrusel-text-admin">Asignar</span></a>
    <a href="logout.php" class="carrusel-item-admin"><span class="carrusel-icon-admin">🚪</span><span class="carrusel-text-admin">Salir</span></a>
</div> 