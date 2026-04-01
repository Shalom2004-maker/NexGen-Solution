<?php
require_once __DIR__ . '/../includes/mailer.php';

echo "Testing email functionality...<br>\n";

$result = sendEmail('test@example.com', 'Test Subject', '<h1>Test Body</h1>');

if ($result === true) {
    echo "Email sent successfully!<br>\n";
} else {
    echo "Email failed: " . htmlspecialchars($result) . "<br>\n";
}
