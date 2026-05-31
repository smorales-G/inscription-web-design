CREATE DATABASE IF NOT EXISTS nuevo_cuyo;
USE nuevo_cuyo;

CREATE TABLE IF NOT EXISTS inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    dni VARCHAR(20) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    carrera VARCHAR(100) NOT NULL,
    titulo_secundario BOOLEAN NOT NULL,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
