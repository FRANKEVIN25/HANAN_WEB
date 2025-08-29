-- Crear base de datos
CREATE DATABASE IF NOT EXISTS freelancer_platform;
USE freelancer_platform;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    specialization VARCHAR(50) NOT NULL,
    experience VARCHAR(10) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    registration_date DATETIME NOT NULL,
    last_login DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de perfiles de freelancers
CREATE TABLE IF NOT EXISTS freelancer_profiles (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    bio TEXT,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    skills VARCHAR(500),
    languages VARCHAR(255),
    location VARCHAR(100) DEFAULT 'Lima, Perú',
    linkedin_url VARCHAR(255),
    portfolio_url VARCHAR(255),
    professional_title VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de CVs
CREATE TABLE IF NOT EXISTS user_cvs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT(11) NOT NULL,
    upload_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de proyectos
CREATE TABLE IF NOT EXISTS projects (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    project_name VARCHAR(200) NOT NULL,
    description TEXT,
    client_name VARCHAR(100),
    project_date DATE,
    amount DECIMAL(10,2),
    status ENUM('completed', 'in_progress', 'pending') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de logs de login
CREATE TABLE IF NOT EXISTS login_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    login_time DATETIME NOT NULL,
    ip_address VARCHAR(45),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para mejorar el rendimiento
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_user_id ON freelancer_profiles(user_id);
CREATE INDEX idx_user_cvs ON user_cvs(user_id);
CREATE INDEX idx_user_projects ON projects(user_id);