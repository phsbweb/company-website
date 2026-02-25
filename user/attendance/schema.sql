-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS phsb;
USE phsb;

-- Create employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    check_in TIMESTAMP NULL DEFAULT NULL,
    check_out TIMESTAMP NULL DEFAULT NULL,
    location_in VARCHAR(255) NULL,
    location_out VARCHAR(255) NULL,
    status ENUM('checked_in', 'checked_out') NOT NULL DEFAULT 'checked_out',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Create device_tokens table for persistent login
CREATE TABLE IF NOT EXISTS device_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Note: Passwords are hashed using password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO employees (username, password, full_name) VALUES 
('admin', '$2y$10$gojZC56gwgcaRqMjNabb8OfUD7suQycRn/bg49vxUZO2/YAJQlwF6', 'Test Admin'),
('employee1', '$2y$10$gojZC56gwgcaRqMjNabb8OfUD7suQycRn/bg49vxUZO2/YAJQlwF6', 'John Doe');
