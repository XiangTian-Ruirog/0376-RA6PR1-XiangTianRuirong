CREATE DATABASE IF NOT EXISTS horapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE horapp;

CREATE TABLE usuaris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    cognoms VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','empleat') NOT NULL DEFAULT 'empleat',
    actiu TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE projectes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    descripcio TEXT,
    hores_estimades DECIMAL(8,2) DEFAULT 0,
    actiu TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE registres_hores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuari_id INT NOT NULL,
    projecte_id INT NOT NULL,
    hora_entrada DATETIME NOT NULL,
    hora_sortida DATETIME DEFAULT NULL,
    total_hores DECIMAL(5,2) DEFAULT NULL,
    notes TEXT,
    data DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuari_id) REFERENCES usuaris(id),
    FOREIGN KEY (projecte_id) REFERENCES projectes(id)
);

INSERT INTO usuaris (nom, cognoms, email, password_hash, rol) VALUES
('Admin', 'Principal', 'admin@horapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Joan', 'Garcia López', 'joan@horapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleat'),
('Maria', 'Puig Martí', 'maria@horapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleat'),
('Pere', 'Soler Vila', 'pere@horapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleat');

INSERT INTO projectes (nom, descripcio, hores_estimades) VALUES
('Projecte Alpha', 'Desenvolupament web client A', 100.00),
('Projecte Beta', 'App mòbil client B', 200.00),
('Projecte Gamma', 'Manteniment sistemes interns', 50.00);