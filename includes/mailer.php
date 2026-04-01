<?php
// ============================================================
// mailer.php — PHP_Project_2025-26
// Sends HTML emails using PHPMailer + Gmail SMTP
// Usage: include 'mailer.php'; sendEmail($to, $subject, $body);
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require('../public/PHPMailer/PHPMailer.php');
require('../public/PHPMailer/SMTP.php');
require('../public/PHPMailer/Exception.php');

/**
 * Send an HTML email via Gmail SMTP.
 *
 * @param string      $to      Recipient email address
 * @param string      $subject Email subject line
 * @param string      $body    HTML body content
 * @param string|null $file    Optional file attachment path (or null)
 * @return true|string         Returns true on success, error string on failure
 */
function sendEmail($to, $subject, $body, $file = null)
{
    // Try PHPMailer first
    $result = sendEmail_PHPMailer($to, $subject, $body, $file);
    if ($result === true) {
        return true;
    }

    // Fallback to PHP mail() function
    return sendEmail_Fallback($to, $subject, $body);
}

/**
 * Send email using PHPMailer (Gmail SMTP)
 */
function sendEmail_PHPMailer($to, $subject, $body, $file = null)
{
    $mail = new PHPMailer(true); // Enable exceptions

    try {
        // ---- SMTP Configuration ----
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'tls';
        $mail->Host       = 'smtp.gmail.com';
        $mail->Port       = 587;
        $mail->Username   = 'benevolenteager@gmail.com';
        $mail->Password   = 'qjgk tpac eotv qvsx';

        // Disable SSL peer verification (for local/dev environments)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        // ---- Sender & Recipient ----
        $mail->setFrom('benevolenteager@gmail.com', 'Nexgen Solution Team');
        $mail->addReplyTo('benevolenteager@gmail.com', 'Nexgen Support Team');
        $mail->addAddress($to);

        // ---- Content ----
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        // ---- Optional Attachment ----
        if ($file && file_exists($file)) {
            $mail->addAttachment($file);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return 'PHPMailer failed: ' . $mail->ErrorInfo;
    }
}

/**
 * Fallback email using PHP's built-in mail() function
 */
function sendEmail_Fallback($to, $subject, $body)
{
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Nexgen Solution Team <noreply@nexgensolution.local>" . "\r\n";

    if (mail($to, $subject, $body, $headers)) {
        return true;
    } else {
        return 'PHP mail() function failed - check your PHP mail configuration';
    }
}