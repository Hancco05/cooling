-- Base de datos inicial para Cooling
USE cooling_db;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'user', 'tech') DEFAULT 'user',
    activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de dispositivos (para el sistema de cooling)
CREATE TABLE IF NOT EXISTS dispositivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('aire_acondicionado', 'ventilador', 'refrigerador', 'otro') NOT NULL,
    temperatura_actual DECIMAL(5,2),
    temperatura_ideal DECIMAL(5,2),
    estado ENUM('encendido', 'apagado', 'mantenimiento') DEFAULT 'apagado',
    ubicacion VARCHAR(200),
    usuario_id INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de lecturas de temperatura
CREATE TABLE IF NOT EXISTS lecturas_temperatura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id INT NOT NULL,
    temperatura DECIMAL(5,2) NOT NULL,
    humedad DECIMAL(5,2),
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario admin por defecto para Cooling
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Administrador Cooling', 'admin@cooling.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Técnico Cooling', 'tecnico@cooling.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tech');

-- Insertar dispositivos de ejemplo
INSERT INTO dispositivos (nombre, tipo, temperatura_actual, temperatura_ideal, estado, ubicacion) VALUES
('Aire Acondicionado Principal', 'aire_acondicionado', 22.5, 21.0, 'encendido', 'Oficina Principal'),
('Refrigerador Laboratorio', 'refrigerador', 4.0, 3.5, 'encendido', 'Laboratorio 101'),
('Ventilador Sala Servidores', 'ventilador', 18.0, 17.0, 'encendido', 'Sala de Servidores');

-- Crear índices para mejor rendimiento
CREATE INDEX idx_dispositivos_usuario ON dispositivos(usuario_id);
CREATE INDEX idx_lecturas_dispositivo ON lecturas_temperatura(dispositivo_id);
CREATE INDEX idx_lecturas_fecha ON lecturas_temperatura(fecha_hora);