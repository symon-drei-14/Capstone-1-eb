<?php
// This file centralizes your PHPMailer configuration.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// We are now loading the PHPMailer files manually from the libs directory.
// The path is relative to this file's location (include/handlers/).
require_once __DIR__ . '/../../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/SMTP.php';


function getMailer() {
    $mail = new PHPMailer(true);

    try {
       
       
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';       
        $mail->SMTPAuth   = true;
        $mail->Username   = 'manstartrucking@gmail.com'; 
        $mail->Password   = 'huhf gvih zlgx icea ';     
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;                      

        // --- SENDER ---
        $mail->setFrom('manstartrucking@gmail.com', 'Mansar Logistics Admin');

        return $mail;
    } catch (Exception $e) {
        // This is a configuration error. We can't send an email about it,
        // so we log it and return null for the calling script to handle.
        error_log("PHPMailer configuration error: {$mail->ErrorInfo}");
        return null;
    }
}