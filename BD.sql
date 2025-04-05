-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS etecnology;

USE etecnology;

-- Tabla de usuarios 
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'vendedor') NOT NULL
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    cantidad INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL
);

-- Crear tabla de ventas (para registrar las ventas)
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT,
    cantidad INT NOT NULL,
    fecha DATE NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES productos(id)
);

-- Tabla de egresos 
CREATE TABLE IF NOT EXISTS egresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(255) NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    fecha DATE NOT NULL
);
