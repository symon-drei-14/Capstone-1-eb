<?php
require_once __DIR__ . '/credentials.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/SMTP.php';


function getMailer() {
    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // --- SECURE CREDENTIALS ---
      
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- SENDER ---
        $mail->setFrom(SMTP_USERNAME, 'Mansar Logistics Admin');

        return $mail;
    } catch (Exception $e) {
        error_log("PHPMailer configuration error: {$mail->ErrorInfo}");
        return null;
    }
}
