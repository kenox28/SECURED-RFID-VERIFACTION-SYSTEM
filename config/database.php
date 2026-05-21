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

    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    /* =========================
       TABLES
    ========================= */

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        fullname VARCHAR(100) NOT NULL,
        role ENUM('super_admin','admin') DEFAULT 'admin',
        department_id INT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department_name VARCHAR(100) NOT NULL,
        department_code VARCHAR(50) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        course VARCHAR(100),
        year_level VARCHAR(10),
        section VARCHAR(20),
        contact_number VARCHAR(15),
        email VARCHAR(100),
        address TEXT,
        rfid_uid VARCHAR(50) UNIQUE,
        photo VARCHAR(255),
        status ENUM('Active','Inactive') DEFAULT 'Active',
        department_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        activity TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department_id INT NULL,
        session_name VARCHAR(100),
        attendance_type ENUM('IN','OUT'),
        start_time TIME,
        end_time TIME,
        status ENUM('ACTIVE','INACTIVE') DEFAULT 'INACTIVE',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        rfid_uid VARCHAR(50),
        attendance_type ENUM('IN','OUT'),
        session_id INT,
        scan_method ENUM('RFID','QR'),
        scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS unknown_scans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scanned_value VARCHAR(255),
        scan_method ENUM('RFID','QR'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS otp_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        otp_code VARCHAR(10) NOT NULL,
        purpose VARCHAR(50) NOT NULL,
        expires_at DATETIME NOT NULL,
        status ENUM('pending','used','expired') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (student_id)
    )");

    /* =========================
       SAFE ALTER FIX (IMPORTANT)
       Prevents "column already exists" errors
    ========================= */

    function addColumnIfNotExists($pdo, $table, $column, $definition)
    {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);

        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }

    // APPLY SAFE ALTERATIONS
    addColumnIfNotExists($pdo, 'admins', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    addColumnIfNotExists($pdo, 'departments', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    addColumnIfNotExists($pdo, 'students', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    addColumnIfNotExists($pdo, 'activity_logs', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    addColumnIfNotExists($pdo, 'attendance_sessions', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    addColumnIfNotExists($pdo, 'unknown_scans', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    addColumnIfNotExists($pdo, 'students', 'password', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'students', 'is_activated', "ENUM('0','1') DEFAULT '0'");
    addColumnIfNotExists($pdo, 'students', 'activated_at', 'DATETIME NULL');
    addColumnIfNotExists($pdo, 'students', 'last_login', 'DATETIME NULL');

    /* =========================
       DEFAULT ADMIN
    ========================= */

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {
        $hashed = password_hash('admin123', PASSWORD_DEFAULT);

        $pdo->prepare("
            INSERT INTO admins (username,password,fullname,role,status)
            VALUES (?,?,?,?,?)
        ")->execute(['admin', $hashed, 'System Administrator', 'super_admin', 'active']);
    }

    /* =========================
       DEFAULT DEPARTMENT
    ========================= */

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_code = 'GENERAL'");
    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("
            INSERT INTO departments (department_name, department_code)
            VALUES (?, ?)
        ")->execute(['General', 'GENERAL']);
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>