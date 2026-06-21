-- Script de inicialización de la base de datos
-- Se ejecuta automáticamente cuando el contenedor MySQL inicia por primera vez

CREATE DATABASE IF NOT EXISTS gestion_tareas_ci;
USE gestion_tareas_ci;

CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'en_progreso', 'completada') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Datos de ejemplo
INSERT INTO tareas (titulo, descripcion, estado) VALUES
('Tarea de prueba 1', 'Descripcion de la tarea de prueba 1', 'pendiente'),
('Tarea de prueba 2', 'Descripcion de la tarea de prueba 2', 'en_progreso');