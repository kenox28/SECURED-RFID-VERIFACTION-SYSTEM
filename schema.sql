-- RFID Student Registration System Database Schema
-- Create database: CREATE DATABASE rfid_system;

USE rfid_system;


CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
    department_id INT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    course VARCHAR(100) NOT NULL,
    year_level VARCHAR(10) NOT NULL,
    section VARCHAR(20) NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    rfid_uid VARCHAR(50) UNIQUE NOT NULL,
    photo VARCHAR(255),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    department_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    activity TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Attendance Sessions Table
CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NULL,
    session_name VARCHAR(100) NOT NULL,
    attendance_type ENUM('IN', 'OUT') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'INACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Attendance Logs Table
CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    rfid_uid VARCHAR(50) NOT NULL,
    attendance_type ENUM('IN', 'OUT') NOT NULL,
    session_id INT NOT NULL,
    scan_method ENUM('RFID', 'QR') NOT NULL,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE
);

-- Unknown Scans Table
CREATE TABLE IF NOT EXISTS unknown_scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scanned_value VARCHAR(255) NOT NULL,
    scan_method ENUM('RFID', 'QR') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin account
-- Password is hashed using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admins (username, password, fullname, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active');