<?php

// SMTP configuration for PHPMailer. Update with your Gmail SMTP credentials.

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 465);
define('MAIL_USERNAME', 'ebakunado.linaohealthcenter@gmail.com');
define('MAIL_PASSWORD', 'yhfd becn tywa ncyy');
define('MAIL_FROM_EMAIL', 'ebakunado.linaohealthcenter@gmail.com');
define('MAIL_FROM_NAME', 'RFID Attendance System');
define('MAIL_ENCRYPTION', 'ssl');

define('OTP_EXPIRY_MINUTES', 5);
define('OTP_RESEND_COOLDOWN_SECONDS', 60);
