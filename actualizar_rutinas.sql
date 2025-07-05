-- Script para actualizar la base de datos NutriApp con la nueva funcionalidad de rutinas
-- Ejecutar este script en tu servidor MySQL

USE nutriapp;

-- 1. Crear nueva tabla para rutinas de ejercicio
CREATE TABLE IF NOT EXISTS rutinas_ejercicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nutriologo_id INT NOT NULL,
    fecha DATE NOT NULL,
    instrucciones TEXT NULL,
    grupo_muscular_1 VARCHAR(50) NULL,
    grupo_muscular_2 VARCHAR(50) NULL,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (nutriologo_id) REFERENCES usuarios(id)
);

-- 2. Crear nueva tabla para ejercicios en rutinas
CREATE TABLE IF NOT EXISTS rutina_ejercicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rutina_id INT NOT NULL,
    ejercicio_id INT NOT NULL,
    dia_semana ENUM('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') NOT NULL,
    series VARCHAR(10) NOT NULL DEFAULT '3',
    repeticiones INT NOT NULL DEFAULT 10,
    FOREIGN KEY (rutina_id) REFERENCES rutinas_ejercicio(id),
    FOREIGN KEY (ejercicio_id) REFERENCES ejercicios(id)
);

-- 3. Actualizar tabla de ejercicios para usar categorías en lugar de grupo_muscular
-- Primero agregar la columna categoria
ALTER TABLE ejercicios ADD COLUMN categoria ENUM('Cardio','Fuerza','Flexibilidad','Equilibrio','Funcional') NOT NULL DEFAULT 'Fuerza';

-- 4. Crear tabla para logos de nutriólogos
CREATE TABLE IF NOT EXISTS logos_nutriologo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nutriologo_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nutriologo_id) REFERENCES usuarios(id)
);

-- 5. Insertar algunos ejercicios de ejemplo por categoría
INSERT INTO ejercicios (nombre, descripcion, categoria) VALUES
-- Cardio
('Correr', 'Ejercicio cardiovascular de alta intensidad', 'Cardio'),
('Caminar', 'Ejercicio cardiovascular de baja intensidad', 'Cardio'),
('Bicicleta', 'Ejercicio cardiovascular de impacto bajo', 'Cardio'),
('Saltar la cuerda', 'Ejercicio cardiovascular completo', 'Cardio'),
('Burpees', 'Ejercicio cardiovascular funcional', 'Cardio'),

-- Fuerza
('Press de banca', 'Ejercicio para pecho y tríceps', 'Fuerza'),
('Sentadillas', 'Ejercicio para piernas y glúteos', 'Fuerza'),
('Peso muerto', 'Ejercicio para espalda y piernas', 'Fuerza'),
('Press militar', 'Ejercicio para hombros', 'Fuerza'),
('Curl de bíceps', 'Ejercicio para bíceps', 'Fuerza'),
('Extensiones de tríceps', 'Ejercicio para tríceps', 'Fuerza'),
('Remo con barra', 'Ejercicio para espalda', 'Fuerza'),
('Zancadas', 'Ejercicio para piernas', 'Fuerza'),

-- Flexibilidad
('Estiramiento de isquiotibiales', 'Mejora la flexibilidad de piernas', 'Flexibilidad'),
('Estiramiento de cuádriceps', 'Mejora la flexibilidad de muslos', 'Flexibilidad'),
('Estiramiento de espalda', 'Mejora la flexibilidad de la columna', 'Flexibilidad'),
('Yoga básico', 'Mejora la flexibilidad general', 'Flexibilidad'),
('Pilates', 'Mejora la flexibilidad y control', 'Flexibilidad'),

-- Equilibrio
('Pose del árbol', 'Mejora el equilibrio y estabilidad', 'Equilibrio'),
('Sentadilla en una pierna', 'Mejora el equilibrio y fuerza', 'Equilibrio'),
('Plancha lateral', 'Mejora el equilibrio del core', 'Equilibrio'),
('Ejercicios con bosu', 'Mejora el equilibrio dinámico', 'Equilibrio'),

-- Funcional
('Plancha', 'Ejercicio funcional para core', 'Funcional'),
('Mountain climbers', 'Ejercicio funcional completo', 'Funcional'),
('Push-ups', 'Ejercicio funcional para tren superior', 'Funcional'),
('Pull-ups', 'Ejercicio funcional para espalda', 'Funcional'),
('Kettlebell swings', 'Ejercicio funcional explosivo', 'Funcional');

-- Mensaje de confirmación
SELECT 'Base de datos actualizada exitosamente. Nueva funcionalidad de rutinas lista para usar.' AS mensaje; 