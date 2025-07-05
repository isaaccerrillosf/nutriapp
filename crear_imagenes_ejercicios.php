<?php
// Script para crear imágenes por defecto para los grupos musculares
// Este script crea imágenes simples con texto para representar cada grupo muscular

// Crear la carpeta si no existe
if (!is_dir('fotos_ejercicios')) {
    mkdir('fotos_ejercicios', 0755, true);
    echo "✅ Carpeta fotos_ejercicios creada<br>";
}

// Función para crear una imagen simple con texto
function crearImagen($texto, $archivo, $color_fondo = '#0074D9', $color_texto = '#FFFFFF') {
    $ancho = 200;
    $alto = 200;
    
    // Crear imagen
    $imagen = imagecreatetruecolor($ancho, $alto);
    
    // Definir colores
    $fondo = imagecolorallocate($imagen, 
        hexdec(substr($color_fondo, 1, 2)), 
        hexdec(substr($color_fondo, 3, 2)), 
        hexdec(substr($color_fondo, 5, 2))
    );
    $texto_color = imagecolorallocate($imagen, 
        hexdec(substr($color_texto, 1, 2)), 
        hexdec(substr($color_texto, 3, 2)), 
        hexdec(substr($color_texto, 5, 2))
    );
    
    // Rellenar fondo
    imagefill($imagen, 0, 0, $fondo);
    
    // Agregar texto
    $fuente = 5; // Fuente del sistema
    $texto_ancho = strlen($texto) * imagefontwidth($fuente);
    $texto_alto = imagefontheight($fuente);
    $x = ($ancho - $texto_ancho) / 2;
    $y = ($alto - $texto_alto) / 2;
    
    imagestring($imagen, $fuente, $x, $y, $texto, $texto_color);
    
    // Guardar imagen
    imagejpeg($imagen, $archivo, 90);
    imagedestroy($imagen);
}

// Definir grupos musculares con colores
$grupos_musculares = [
    'pecho' => '#ff6b6b',
    'espalda' => '#4ecdc4', 
    'hombros' => '#45b7d1',
    'biceps' => '#96ceb4',
    'triceps' => '#feca57',
    'piernas' => '#ff9ff3',
    'gluteos' => '#54a0ff',
    'abdomen' => '#5f27cd',
    'antebrazos' => '#00d2d3',
    'pantorrillas' => '#ff9f43',
    'default' => '#95a5a6'
];

echo "<h2>Creando imágenes para grupos musculares...</h2>";

foreach ($grupos_musculares as $grupo => $color) {
    $archivo = "fotos_ejercicios/{$grupo}.jpg";
    if (!file_exists($archivo)) {
        crearImagen(strtoupper($grupo), $archivo, $color);
        echo "✅ Imagen creada: {$archivo}<br>";
    } else {
        echo "ℹ️ Imagen ya existe: {$archivo}<br>";
    }
}

echo "<br><strong>✅ Todas las imágenes han sido creadas en la carpeta fotos_ejercicios/</strong><br>";
echo "<p>Ahora puedes reemplazar estas imágenes por defecto con tus propias imágenes de grupos musculares.</p>";
echo "<p>Nombres de archivos esperados:</p>";
echo "<ul>";
foreach (array_keys($grupos_musculares) as $grupo) {
    echo "<li>fotos_ejercicios/{$grupo}.jpg</li>";
}
echo "</ul>";
?> 