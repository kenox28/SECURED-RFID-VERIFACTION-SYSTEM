<?php
// config/database.php

$host = 'localhost';
$dbname = 'rfid_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
    $pdo->exec("USE `$dbname`");
    $pdo->exec("SET NAMES utf8");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        fullname VARCHAR(100) NOT NULL,
        role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
        department_id INT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        department_name VARCHAR(100) NOT NULL,
        department_code VARCHAR(50) UNIQUE NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
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
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        activity TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_sessions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        department_id INT NULL,
        session_name VARCHAR(100) NOT NULL,
        attendance_type ENUM('IN', 'OUT') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'INACTIVE',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_logs (
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
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS unknown_scans (
        id INT PRIMARY KEY AUTO_INCREMENT,
        scanned_value VARCHAR(255) NOT NULL,
        scan_method ENUM('RFID', 'QR') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $columnExists = function (string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetch();
    };

    if (!$columnExists('admins', 'role')) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin'");
    }
    if (!$columnExists('admins', 'department_id')) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN department_id INT NULL");
    }
    if (!$columnExists('admins', 'status')) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }
    if (!$columnExists('students', 'department_id')) {
        $pdo->exec("ALTER TABLE students ADD COLUMN department_id INT NULL");
    }
    if (!$columnExists('attendance_sessions', 'department_id')) {
        $pdo->exec("ALTER TABLE attendance_sessions ADD COLUMN department_id INT NULL");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admins (username, password, fullname, role, status) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['admin', $hashedPassword, 'System Administrator', 'super_admin', 'active']);
    } else {
        // Ensure default admin remains super_admin and active
        $update = $pdo->prepare("UPDATE admins SET role = 'super_admin', status = 'active' WHERE username = 'admin'");
        $update->execute();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_code = 'GENERAL'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO departments (department_name, department_code) VALUES (?, ?)");
        $insert->execute(['General', 'GENERAL']);
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>