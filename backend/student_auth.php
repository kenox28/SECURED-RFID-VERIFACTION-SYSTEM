<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/email_service.php';

function get_logged_student(): ?array
{
    if (!isset($_SESSION['student_user_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, student_id, first_name, last_name, middle_name, email, course, year_level, section, contact_number, address, photo, status, is_activated FROM students WHERE id = ?');
    $stmt->execute([$_SESSION['student_user_id']]);
    return $stmt->fetch() ?: null;
}

function ensure_student_session(): void
{
    $student = get_logged_student();
    if (!$student || $student['status'] !== 'Active' || $student['is_activated'] !== '1') {
        header('Location: /student_login.php');
        exit();
    }
}

function student_create_session(array $student): void
{
    session_regenerate_id(true);

    $_SESSION['student_user_id'] = $student['id'];
    $_SESSION['student_student_id'] = $student['student_id'];
    $_SESSION['student_fullname'] = trim($student['first_name'] . ' ' . $student['last_name']);
    $_SESSION['student_student_email'] = $student['email'];
    $_SESSION['student_logged_at'] = time();

    global $pdo;
    $stmt = $pdo->prepare('UPDATE students SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$student['id']]);
}

function student_logout(): void
{
    unset(
        $_SESSION['student_user_id'],
        $_SESSION['student_student_id'],
        $_SESSION['student_fullname'],
        $_SESSION['student_student_email'],
        $_SESSION['student_logged_at'],
        $_SESSION['student_pending_activation'],
        $_SESSION['student_activation_verified']
    );
}

function find_student_by_identifier(string $identifier): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, student_id, first_name, last_name, email, status, is_activated, password FROM students WHERE student_id = ? OR email = ? LIMIT 1');
    $stmt->execute([$identifier, $identifier]);
    return $stmt->fetch() ?: null;
}

function can_request_student_otp(int $studentId, string $purpose): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT created_at FROM otp_verifications WHERE student_id = ? AND purpose = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$studentId, $purpose]);
    $row = $stmt->fetch();

    if (!$row) {
        return true;
    }

    $lastSent = strtotime($row['created_at']);
    return (time() - $lastSent) >= OTP_RESEND_COOLDOWN_SECONDS;
}

function send_student_otp(array $student, string $purpose): array
{
    global $pdo;

    if (empty($student['email'])) {
        return ['success' => false, 'message' => 'No registered email found for this student.'];
    }

    if (!can_request_student_otp($student['id'], $purpose)) {
        return ['success' => false, 'message' => 'Please wait a minute before requesting another code.'];
    }

    $otpCode = strval(random_int(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);

    $stmt = $pdo->prepare('INSERT INTO otp_verifications (student_id, otp_code, purpose, expires_at, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$student['id'], $otpCode, $purpose, $expiresAt, 'pending']);

    if (!send_student_otp_email($student, $otpCode, OTP_EXPIRY_MINUTES, $purpose)) {
        return ['success' => false, 'message' => 'Unable to send verification email. Please contact the administrator.'];
    }

    if ($purpose === 'activation') {
        $_SESSION['student_pending_activation'] = $student['id'];
    } elseif ($purpose === 'password_reset') {
        $_SESSION['student_password_reset'] = $student['id'];
    }

    return ['success' => true, 'message' => 'A verification code was sent to your registered email address.'];
}

function send_student_activation_otp(array $student): array
{
    return send_student_otp($student, 'activation');
}

function send_student_password_reset_otp(array $student): array
{
    return send_student_otp($student, 'password_reset');
}

function student_password_reset_student(): ?array
{
    if (empty($_SESSION['student_password_reset'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, student_id, first_name, last_name, email, status, is_activated FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['student_password_reset']]);
    return $stmt->fetch() ?: null;
}

function student_verify_otp_for_purpose(string $otp, string $purpose): array
{
    if ($purpose === 'activation') {
        $student = student_pending_activation_student();
    } else {
        $student = student_password_reset_student();
    }

    if (!$student) {
        return ['success' => false, 'message' => 'No pending verification found.'];
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, otp_code, expires_at, status FROM otp_verifications WHERE student_id = ? AND purpose = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$student['id'], $purpose]);
    $record = $stmt->fetch();

    if (!$record) {
        return ['success' => false, 'message' => 'No verification code request found.'];
    }

    if ($record['status'] !== 'pending') {
        return ['success' => false, 'message' => 'This code has already been used or expired.'];
    }

    if (strtotime($record['expires_at']) < time()) {
        $stmt = $pdo->prepare('UPDATE otp_verifications SET status = ? WHERE id = ?');
        $stmt->execute(['expired', $record['id']]);
        return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
    }

    if (trim($otp) !== $record['otp_code']) {
        return ['success' => false, 'message' => 'Incorrect verification code.'];
    }

    $stmt = $pdo->prepare('UPDATE otp_verifications SET status = ? WHERE id = ?');
    $stmt->execute(['used', $record['id']]);

    if ($purpose === 'activation') {
        $_SESSION['student_activation_verified'] = true;
    } else {
        $_SESSION['student_password_reset_verified'] = true;
    }

    return ['success' => true, 'message' => 'Verification code accepted.'];
}

function student_verify_otp(string $otp): array
{
    return student_verify_otp_for_purpose($otp, 'activation');
}

function student_verify_password_reset_otp(string $otp): array
{
    return student_verify_otp_for_purpose($otp, 'password_reset');
}

function student_reset_password(string $password): array
{
    if (empty($_SESSION['student_password_reset_verified']) || empty($_SESSION['student_password_reset'])) {
        return ['success' => false, 'message' => 'Password reset verification missing.'];
    }

    $studentId = intval($_SESSION['student_password_reset']);
    if ($studentId <= 0) {
        return ['success' => false, 'message' => 'Password reset information is invalid.'];
    }

    $errors = student_validate_password($password);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    global $pdo;
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE students SET password = ?, is_activated = ?, activated_at = COALESCE(activated_at, NOW()) WHERE id = ?');
    $stmt->execute([$hashed, '1', $studentId]);

    $student = find_student_by_id($studentId);
    if (!$student) {
        return ['success' => false, 'message' => 'Unable to reset password.'];
    }

    unset($_SESSION['student_password_reset'], $_SESSION['student_password_reset_verified']);
    student_create_session($student);

    return ['success' => true, 'message' => 'Password has been reset successfully.'];
}

function student_resend_password_reset_otp(): array
{
    $student = student_password_reset_student();
    if (!$student) {
        return ['success' => false, 'message' => 'No pending password reset request found.'];
    }

    return send_student_password_reset_otp($student);
}

function student_login(string $identifier, string $password = ''): array
{
    $student = find_student_by_identifier(trim($identifier));

    if (!$student || $student['status'] !== 'Active') {
        return ['success' => false, 'message' => 'Invalid student ID, email, or account not active.'];
    }

    if ($student['is_activated'] !== '1' || empty($student['password'])) {
        return send_student_activation_otp($student);
    }

    if (empty($password)) {
        return ['success' => false, 'message' => 'Password is required for returning students.'];
    }

    if (!password_verify($password, $student['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    student_create_session($student);
    return ['success' => true, 'message' => 'Logged in successfully.'];
}

function student_pending_activation_student(): ?array
{
    if (empty($_SESSION['student_pending_activation'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, student_id, first_name, last_name, email, status, is_activated FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['student_pending_activation']]);
    return $stmt->fetch() ?: null;
}

function find_student_by_id(int $studentId): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, student_id, first_name, last_name, email, status, is_activated, password FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    return $stmt->fetch() ?: null;
}

function student_activate_account(string $password): array
{
    if (empty($_SESSION['student_activation_verified']) || empty($_SESSION['student_pending_activation'])) {
        return ['success' => false, 'message' => 'Activation verification missing.'];
    }

    $studentId = intval($_SESSION['student_pending_activation']);
    if ($studentId <= 0) {
        return ['success' => false, 'message' => 'Activation information is invalid.'];
    }

    $errors = student_validate_password($password);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    global $pdo;
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE students SET password = ?, is_activated = ?, activated_at = NOW() WHERE id = ?');
    $stmt->execute([$hashed, '1', $studentId]);

    $student = find_student_by_id($studentId);
    if (!$student) {
        return ['success' => false, 'message' => 'Unable to activate account.'];
    }

    $_SESSION['student_activation_verified'] = false;
    student_create_session($student);

    return ['success' => true, 'message' => 'Password created successfully.'];
}

function student_validate_password(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (!preg_match('/[!@#$%^&*()_+\-=[\]{};:"\\|,.<>\/]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }

    return $errors;
}

function student_resend_activation_otp(): array
{
    $student = student_pending_activation_student();
    if (!$student) {
        return ['success' => false, 'message' => 'No pending activation found.'];
    }

    return send_student_activation_otp($student);
}
