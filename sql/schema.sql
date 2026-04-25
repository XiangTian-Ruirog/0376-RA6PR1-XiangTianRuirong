-- ============================================
-- TimeTracker - Esquema de base de datos
-- Sistema de gestió i seguiment d'hores de treball
-- ============================================

-- Crear la base de dades
CREATE DATABASE IF NOT EXISTS timetracker
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE timetracker;

-- ============================================
-- Taula: usuaris
-- Emmagatzema la informació dels usuaris del sistema
-- ============================================
CREATE TABLE IF NOT EXISTS usuaris (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    cognoms VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleat') NOT NULL DEFAULT 'empleat',
    actiu TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_actiu (actiu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Taula: projectes
-- Emmagatzema la informació dels projectes
-- ============================================
CREATE TABLE IF NOT EXISTS projectes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    descripcio TEXT,
    hores_estimades DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    actiu TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actiu (actiu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Taula: registres_hores
-- Emmagatzema els registres d'hores dels usuaris
-- ============================================
CREATE TABLE IF NOT EXISTS registres_hores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuari_id INT UNSIGNED NOT NULL,
    projecte_id INT UNSIGNED NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_sortida TIME NOT NULL,
    total_hores DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    data DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuari_id) REFERENCES usuaris(id) ON DELETE CASCADE,
    FOREIGN KEY (projecte_id) REFERENCES projectes(id) ON DELETE CASCADE,
    INDEX idx_usuari (usuari_id),
    INDEX idx_projecte (projecte_id),
    INDEX idx_data (data),
    INDEX idx_usuari_data (usuari_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DADES DE PROVA
-- ============================================

-- Usuaris (contrasenyes encriptades amb password_hash de PHP)
-- Admin: admin123
-- Empleats: empleat123
INSERT INTO usuaris (nom, cognoms, email, password_hash, rol, actiu, created_at) VALUES
('Administrador', 'Sistema', 'admin@timetracker.cat', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW()),
('Anna', 'Garcia Martinez', 'anna.garcia@timetracker.cat', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleat', 1, NOW()),
('Marc', 'Lopez Fernandez', 'marc.lopez@timetracker.cat', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleat', 1, NOW()),
('Laura', 'Sanchez Ruiz', 'laura.sanchez@timetracker.cat', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleat', 1, NOW());

-- Projectes
INSERT INTO projectes (nom, descripcio, hores_estimades, actiu, created_at) VALUES
('Desenvolupament Web', 'Projecte de desenvolupament d\'aplicació web corporativa', 160.00, 1, NOW()),
('Manteniment Sistema', 'Tasques de manteniment i suport del sistema intern', 80.00, 1, NOW()),
('Migració Base de Dades', 'Migració i optimització de la base de dades principal', 40.00, 1, NOW());

-- Registres d'hores de prova (últims 5 dies)
INSERT INTO registres_hores (usuari_id, projecte_id, hora_entrada, hora_sortida, total_hores, notes, data, created_at) VALUES
(2, 1, '09:00:00', '13:00:00', 4.00, 'Desenvolupament del mòdul d\'usuaris', CURDATE() - INTERVAL 1 DAY, NOW()),
(2, 1, '14:00:00', '18:00:00', 4.00, 'Implementació del login i registre', CURDATE() - INTERVAL 1 DAY, NOW()),
(3, 2, '08:00:00', '12:00:00', 4.00, 'Revisió de servidors i backups', CURDATE() - INTERVAL 1 DAY, NOW()),
(3, 3, '13:00:00', '17:00:00', 4.00, 'Anàlisi de l\'estructura actual de BD', CURDATE() - INTERVAL 1 DAY, NOW()),
(4, 1, '09:30:00', '13:30:00', 4.00, 'Disseny de la interfície d\'usuari', CURDATE() - INTERVAL 2 DAY, NOW()),
(4, 2, '14:30:00', '17:30:00', 3.00, 'Suport tècnic a usuaris', CURDATE() - INTERVAL 2 DAY, NOW()),
(2, 1, '09:00:00', '13:00:00', 4.00, 'Desenvolupament del dashboard', CURDATE() - INTERVAL 2 DAY, NOW()),
(2, 1, '14:00:00', '18:00:00', 4.00, 'Integració amb API externa', CURDATE() - INTERVAL 2 DAY, NOW()),
(3, 2, '08:00:00', '12:00:00', 4.00, 'Actualització de certificats SSL', CURDATE() - INTERVAL 3 DAY, NOW()),
(3, 3, '13:00:00', '16:00:00', 3.00, 'Script de migració de dades', CURDATE() - INTERVAL 3 DAY, NOW());