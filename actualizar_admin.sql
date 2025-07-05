-- Script para actualizar el hash del usuario administrador
-- Ejecutar este script en tu servidor MySQL para corregir la contraseña del admin

USE nutriapp;

-- Actualizar el hash del usuario administrador (contraseña: admin123)
UPDATE usuarios 
SET password = '$2y$10$vN.zY69lcqdhcrSccAgc2.WEAEe5rh875bkb8b2Mebna5WMPY4mKS' 
WHERE email = 'admin@nutriapp.com';

-- Verificar que se actualizó correctamente
SELECT id, nombre, email, rol FROM usuarios WHERE email = 'admin@nutriapp.com'; 