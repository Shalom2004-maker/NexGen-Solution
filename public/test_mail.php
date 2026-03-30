<?php
require_once __DIR__ . '/../includes/mailer.php';

$result = send_password_reset_otp_email('test@example.com', 'Test User', '123456');
echo "Sent: " . ($result['sent'] ? 'Yes' : 'No') . "\n";
echo "Error: " . $result['error'] . "\n";
