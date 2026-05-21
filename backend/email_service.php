<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail.php';

/**
 * Send Generic System Email
 */
function send_system_email(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody
): bool {

    $mail = new PHPMailer(true);

    try {

        $mail->SMTPDebug = 0;

        $mail->isSMTP();

        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->AltBody = strip_tags($htmlBody);

        return $mail->send();

    } catch (Exception $e) {

        error_log('Email failed: ' . $mail->ErrorInfo);

        return false;
    }
}

/**
 * Reusable Email Template
 */
function build_email_template(
    string $heading,
    string $message,
    string $footer = ''
): string {

    return <<<HTML
<div style="font-family: Arial, sans-serif; color: #1f2937; padding: 24px; background: #f8fafc;">

    <div style="
        max-width: 680px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    ">

        <div style="
            background: linear-gradient(135deg, #fb8500 0%, #ffb703 100%);
            padding: 28px 24px;
        ">

            <h1 style="
                margin:0;
                color:#ffffff;
                font-family:'Plus Jakarta Sans', sans-serif;
                font-size:24px;
                letter-spacing:-0.02em;
            ">
                RFID Attendance System
            </h1>

        </div>

        <div style="padding:24px;">

            <h2 style="
                margin-top:0;
                font-size:20px;
                color:#111827;
            ">
                {$heading}
            </h2>

            <div style="
                margin-top:16px;
                font-size:15px;
                line-height:1.7;
                color:#475569;
            ">
                {$message}
            </div>

HTML

    . ($footer !== '' ? <<<HTML

            <div style="
                margin-top:24px;
                padding:18px;
                background:#f8fafc;
                border-radius:14px;
                color:#334155;
                font-size:14px;
            ">
                {$footer}
            </div>

HTML : '')

    . <<<HTML

        </div>

        <div style="
            background:#f1f5f9;
            padding:18px 24px;
            font-size:13px;
            color:#64748b;
        ">
            This is an automated message from the RFID Attendance System.
        </div>

    </div>

</div>
HTML;
}

/**
 * Send Student OTP Email
 */
function send_student_otp_email(
    array $student,
    string $otp,
    int $expiresMinutes
): bool {

    $subject = 'Your RFID Student Account OTP';

    $name = trim(
        $student['first_name'] . ' ' . $student['last_name']
    );

    $safeName = htmlspecialchars(
        $name,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeOTP = htmlspecialchars(
        $otp,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeStudentId = htmlspecialchars(
        $student['student_id'],
        ENT_QUOTES,
        'UTF-8'
    );

    $message = <<<HTML

<p>
    Hello <strong>{$safeName}</strong>,
</p>

<p>
    Your one-time verification code is:
</p>

<div style="
    margin:18px 0;
    padding:18px 22px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:14px;
    display:inline-block;
    font-size:28px;
    letter-spacing:4px;
    font-weight:700;
    color:#111827;
">
    {$safeOTP}
</div>

<p style="margin-top:16px;">
    Enter this code on the verification page within
    <strong>{$expiresMinutes} minutes</strong>.
</p>

<table style="
    border-collapse: collapse;
    width:100%;
    margin-top:20px;
">

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
            width:35%;
        ">
            <strong>Student ID</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            {$safeStudentId}
        </td>

    </tr>

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            <strong>Name</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            {$safeName}
        </td>

    </tr>

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            <strong>Expiration</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            {$expiresMinutes} minutes
        </td>

    </tr>

</table>

HTML;

    $footer = '
        If you did not request this code,
        please contact your administrator immediately.
    ';

    $htmlBody = build_email_template(
        'OTP Verification Code',
        $message,
        $footer
    );

    return send_system_email(
        $student['email'],
        $name,
        $subject,
        $htmlBody
    );
}

/**
 * Send Attendance Email
 */
function send_attendance_email(
    array $student,
    string $attendanceType,
    array $session
): bool {

    $subject = 'Attendance Recorded Successfully';

    $name = trim(
        $student['first_name'] . ' ' . $student['last_name']
    );

    $safeName = htmlspecialchars(
        $name,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeStudentId = htmlspecialchars(
        $student['student_id'],
        ENT_QUOTES,
        'UTF-8'
    );

    $safeAttendanceType = htmlspecialchars(
        $attendanceType,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeSessionName = htmlspecialchars(
        $session['session_name'] ?? 'N/A',
        ENT_QUOTES,
        'UTF-8'
    );

    $currentDate = date('F d, Y h:i A');

    $message = <<<HTML

<p>
    Hello <strong>{$safeName}</strong>,
</p>

<p>
    Your attendance has been successfully recorded by the RFID Attendance System.
</p>

<table style="
    border-collapse: collapse;
    width:100%;
    margin-top:15px;
">

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            <strong>Student ID</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            {$safeStudentId}
        </td>

    </tr>

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            <strong>Name</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            {$safeName}
        </td>

    </tr>

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            <strong>Attendance Type</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
            color:#16a34a;
            font-weight:700;
        ">
            {$safeAttendanceType}
        </td>

    </tr>

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            <strong>Session</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            {$safeSessionName}
        </td>

    </tr>

    <tr>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            <strong>Date & Time</strong>
        </td>

        <td style="
            padding:10px;
            border:1px solid #e2e8f0;
        ">
            {$currentDate}
        </td>

    </tr>

</table>

HTML;

    $footer = '
        This attendance notification was automatically generated
        by the RFID Attendance System.
    ';

    $htmlBody = build_email_template(
        'Attendance Confirmed',
        $message,
        $footer
    );

    return send_system_email(
        $student['email'],
        $name,
        $subject,
        $htmlBody
    );
}