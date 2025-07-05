<?php
if (!isset($_SESSION)) session_start();
$nombre = $_SESSION['usuario_nombre'] ?? '';
$nutriologo_id = $_SESSION['usuario_id'] ?? null;
$logo_path = null;
if ($nutriologo_id) {
    foreach (["png","jpg","jpeg","gif"] as $ext) {
        $try = "logo_nutriologo_{$nutriologo_id}.{$ext}";
        if (file_exists($try)) { $logo_path = $try; break; }
    }
}
?>
<style>
@media (min-width: 901px) {
  .header-saludo-nutriologo { display: none !important; }
}
</style>
<header class="header-nutriologo">
    <div class="header-content-nutriologo">
        <?php if ($logo_path): ?>
            <div class="header-logo-nutriologo">
                <img src="<?= $logo_path ?>" alt="Logo Nutriólogo" class="logo-nutriologo" />
            </div>
        <?php endif; ?>
        <div class="header-saludo-nutriologo">
            <span class="saludo-texto-nutriologo">¡Hola, <?= htmlspecialchars($nombre) ?>!</span>
        </div>
        <?php include 'nutriologo_carrusel.php'; ?>
    </div>
</header> 