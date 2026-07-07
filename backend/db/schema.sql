SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS edus_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edus_web;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identification VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('patient', 'admin') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identification VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    specialty VARCHAR(150) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    schedule_id INT NOT NULL,
    status ENUM('scheduled', 'cancelled', 'completed') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
);

-- Usuario administrador inicial. La contraseña es 'admin123'.
INSERT IGNORE INTO users (identification, first_name, last_name, email, password_hash, role) VALUES 
('000000000', 'Administrador', 'Sistema', 'admin@edus.ccss.sa.cr', '$2y$10$MtNr7/BB.0VdlTMGT1klaegstHH6T85ZX.WbK4QUlsU0EF/ncP6b.', 'admin');

INSERT IGNORE INTO doctors (id, identification, first_name, last_name, specialty, status) VALUES
(1, 'MED001', 'Laura', 'Mora', 'Medicina General', 'active'),
(2, 'MED002', 'Carlos', 'Vargas', 'Cardiología', 'active'),
(3, 'MED003', 'Sofía', 'Rojas', 'Pediatría', 'active');

UPDATE doctors SET first_name = 'Carlos', last_name = 'Vargas', specialty = 'Cardiología' WHERE id = 2;
UPDATE doctors SET first_name = 'Sofía', last_name = 'Rojas', specialty = 'Pediatría' WHERE id = 3;

INSERT IGNORE INTO schedules (id, doctor_id, schedule_date, start_time, end_time, is_available) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', '08:30:00', 1),
(2, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', '09:30:00', 1),
(3, 2, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '10:00:00', '10:30:00', 1),
(4, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '13:00:00', '13:30:00', 1);
