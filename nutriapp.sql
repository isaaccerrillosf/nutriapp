-- Base de datos para el sistema NutriApp
CREATE DATABASE IF NOT EXISTS nutriapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nutriapp;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'nutriologo', 'cliente') NOT NULL DEFAULT 'cliente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    telefono VARCHAR(20) NOT NULL
);

-- Usuario admin inicial (contraseña: admin123, cámbiala después)
INSERT INTO usuarios (nombre, email, password, rol) VALUES (
    'Administrador',
    'admin@nutriapp.com',
    '$2y$10$vN.zY69lcqdhcrSccAgc2.WEAEe5rh875bkb8b2Mebna5WMPY4mKS',
    'admin'
);

-- Tabla para asignar clientes a nutriólogos
CREATE TABLE IF NOT EXISTS nutriologo_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nutriologo_id INT NOT NULL,
    cliente_id INT NOT NULL,
    FOREIGN KEY (nutriologo_id) REFERENCES usuarios(id),
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
);

-- Tabla de ejercicios
CREATE TABLE IF NOT EXISTS ejercicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    grupo_muscular VARCHAR(100) NOT NULL,
    descripcion TEXT,
    youtube_url VARCHAR(255) DEFAULT NULL
);

-- Tabla de alimentos
CREATE TABLE IF NOT EXISTS alimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    calorias INT NOT NULL
);

-- Tabla de rutinas de ejercicio por cliente
CREATE TABLE IF NOT EXISTS rutinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nutriologo_id INT NOT NULL,
    dia_semana ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (nutriologo_id) REFERENCES usuarios(id)
);

-- Relación de ejercicios en cada rutina
CREATE TABLE IF NOT EXISTS rutina_ejercicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rutina_id INT NOT NULL,
    ejercicio_id INT NOT NULL,
    FOREIGN KEY (rutina_id) REFERENCES rutinas(id),
    FOREIGN KEY (ejercicio_id) REFERENCES ejercicios(id)
);

-- Agregar columnas de series y repeticiones a la tabla rutina_ejercicios
ALTER TABLE rutina_ejercicios
ADD COLUMN series INT NOT NULL DEFAULT 3,
ADD COLUMN repeticiones INT NOT NULL DEFAULT 10;

-- Tabla de planes nutricionales por cliente
CREATE TABLE IF NOT EXISTS planes_nutricionales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nutriologo_id INT NOT NULL,
    fecha DATE NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (nutriologo_id) REFERENCES usuarios(id)
);

-- Relación de alimentos en cada plan nutricional (desayuno, comida, cena)
CREATE TABLE IF NOT EXISTS plan_alimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    alimento_id INT NOT NULL,
    tipo_comida ENUM('Desayuno','Snack1','Comida','Snack2','Cena') NOT NULL,
    FOREIGN KEY (plan_id) REFERENCES planes_nutricionales(id),
    FOREIGN KEY (alimento_id) REFERENCES alimentos(id)
);

-- Agregar columna para cantidad en plan_alimentos
ALTER TABLE plan_alimentos ADD COLUMN cantidad VARCHAR(10) NOT NULL DEFAULT '100g';

-- Tabla para links de videos de ejercicios
CREATE TABLE IF NOT EXISTS links_ejercicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    youtube_url VARCHAR(255) NOT NULL
);

-- Tabla para resultados de medidas, peso y grasa corporal
CREATE TABLE IF NOT EXISTS resultados_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nutriologo_id INT NOT NULL,
    fecha DATE NOT NULL,
    peso DECIMAL(5,2),
    grasa_corporal DECIMAL(4,2),
    cintura DECIMAL(5,2),
    cadera DECIMAL(5,2),
    brazo DECIMAL(4,2),
    muslo DECIMAL(4,2),
    notas TEXT,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (nutriologo_id) REFERENCES usuarios(id)
);

-- Agregar columna para tipo de comida en plan_alimentos
ALTER TABLE plan_alimentos 
MODIFY COLUMN tipo_comida ENUM('Desayuno','Snack1','Comida','Snack2','Cena') NOT NULL;

-- Tabla para citas entre nutriólogo y cliente
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nutriologo_id INT NOT NULL,
    fecha_cita DATETIME NOT NULL,
    confirmada TINYINT(1) DEFAULT 0,
    fecha_confirmacion DATETIME DEFAULT NULL,
    notas VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (nutriologo_id) REFERENCES usuarios(id)
); 