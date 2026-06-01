<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail.php';

function send_system_email(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
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

function build_email_template(string $heading, string $message, string $footer = ''): string
{
    return "<div style='font-family: Arial, sans-serif; color: #1f2937; padding: 24px; background: #f8fafc;'>
        <div style='max-width: 680px; margin: 0 auto; background: #ffffff; border-radius: 18px; overflow: hidden; border: 1px solid #e2e8f0;'>
            <div style='background: linear-gradient(135deg, #fb8500 0%, #ffb703 100%); padding: 28px 24px;'>
                <h1 style='margin:0; color:#ffffff; font-family: \"Plus Jakarta Sans\", sans-serif; font-size: 24px; letter-spacing: -0.02em;'>RFID Attendance System</h1>
            </div>
            <div style='padding: 24px;'>
                <h2 style='margin-top:0; font-size: 20px; color: #111827;'>" . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . "</h2>
                <div style='margin-top: 16px; font-size: 15px; line-height: 1.7; color: #475569;'>" . $message . "</div>
                " . ($footer !== '' ? "<div style='margin-top: 24px; padding: 18px; background: #f8fafc; border-radius: 14px; color: #334155; font-size: 14px;'>" . $footer . "</div>" : '') . "
            </div>
            <div style='background: #f1f5f9; padding: 18px 24px; font-size: 13px; color: #64748b;'>
                This is an automated message from the RFID Attendance System.
            </div>
        </div>
    </div>";
}

function send_student_otp_email(array $student, string $otp, int $expiresMinutes, string $purpose = 'activation'): bool
{
    $name = trim($student['first_name'] . ' ' . $student['last_name']);
    $subject = $purpose === 'password_reset'
        ? 'Your RFID Password Reset Code'
        : 'Your RFID Student Account OTP';

    $heading = $purpose === 'password_reset'
        ? 'Password Reset Verification Code'
        : 'OTP Verification Code';

    $intro = $purpose === 'password_reset'
        ? '<p>Hello <strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>,</p>' .
          '<p>You requested a password reset for your RFID student account. Use the code below to reset your password.</p>'
        : '<p>Hello <strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>,</p>' .
          '<p>Your one-time verification code is:</p>';

    $message = $intro .
        "<div style='margin: 18px 0; padding: 18px 22px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; display: inline-block; font-size: 28px; letter-spacing: 4px; font-weight: 700; color: #111827;'>" . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "</div>" .
        "<p style='margin-top: 16px;'>Enter this code on the verification page within <strong>" . intval($expiresMinutes) . " minutes</strong>.</p>" .
        "<table style='border-collapse: collapse; width:100%; margin-top: 20px;'>" .
        "<tr><td style='padding: 10px; border:1px solid #e2e8f0; width: 35%;'><strong>Student ID</strong></td><td style='padding: 10px; border:1px solid #e2e8f0;'>" . htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') . "</td></tr>" .
        "<tr><td style='padding: 10px; border:1px solid #e2e8f0;'><strong>Name</strong></td><td style='padding: 10px; border:1px solid #e2e8f0;'>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</td></tr>" .
        "<tr><td style='padding: 10px; border:1px solid #e2e8f0;'><strong>Expiration</strong></td><td style='padding: 10px; border:1px solid #e2e8f0;'>" . intval($expiresMinutes) . " minutes</td></tr>" .
        "</table>";

    return send_system_email($student['email'], $name, $subject, build_email_template($heading, $message, 'If you did not request this code, please contact your administrator.'));
}
