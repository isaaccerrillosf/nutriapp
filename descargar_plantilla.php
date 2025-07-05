<?php
// Script para descargar la plantilla CSV de ejercicios
$archivo = 'plantilla_ejercicios.csv';

if (file_exists($archivo)) {
    // Configurar headers para forzar la descarga
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plantilla_ejercicios.csv"');
    header('Content-Length: ' . filesize($archivo));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Leer y enviar el archivo
    readfile($archivo);
    exit;
} else {
    // Si el archivo no existe, crear uno básico
    $contenido = "nombre,grupo_muscular,descripcion,youtube_url,foto\n";
    $contenido .= "Press de banca,Pecho,Ejercicio para pectorales,https://www.youtube.com/watch?v=example,https://www.ejemplo.com/imagenes/press_banca.jpg\n";
    $contenido .= "Sentadilla,Piernas,Ejercicio para piernas y glúteos,https://www.youtube.com/watch?v=example2,sentadilla.jpg\n";
    $contenido .= "Peso muerto,Espalda,Ejercicio para espalda y piernas,https://www.youtube.com/watch?v=example3,peso_muerto.jpg\n";
    $contenido .= "Press militar,Hombros,Ejercicio para hombros,https://www.youtube.com/watch?v=example4,press_militar.jpg\n";
    $contenido .= "Curl de bíceps,Bíceps,Ejercicio para bíceps,https://www.youtube.com/watch?v=example5,curl_biceps.jpg\n";
    
    // Configurar headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plantilla_ejercicios.csv"');
    header('Content-Length: ' . strlen($contenido));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Enviar contenido
    echo $contenido;
    exit;
}
?> 