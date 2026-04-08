<?php

if (!function_exists('inquiry_column_exists')) {
    function inquiry_column_exists(mysqli $conn, string $columnName): bool
    {
        $columnName = trim($columnName);
        if ($columnName === '') {
            return false;
        }

        $stmt = $conn->prepare("
            SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'inquiries'
              AND COLUMN_NAME = ?
            LIMIT 1
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $columnName);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows === 1;
        $stmt->close();

        return $exists;
    }
}

if (!function_exists('ensure_inquiry_reply_support')) {
    function ensure_inquiry_reply_support(mysqli $conn): void
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $alterStatements = [];

        if (!inquiry_column_exists($conn, 'reply_message')) {
            $alterStatements[] = "ALTER TABLE inquiries ADD COLUMN reply_message TEXT DEFAULT NULL AFTER message";
        }

        if (!inquiry_column_exists($conn, 'replied_at')) {
            $alterStatements[] = "ALTER TABLE inquiries ADD COLUMN replied_at DATETIME DEFAULT NULL AFTER reply_message";
        }

        if (!inquiry_column_exists($conn, 'replied_by')) {
            $alterStatements[] = "ALTER TABLE inquiries ADD COLUMN replied_by INT(11) DEFAULT NULL AFTER replied_at";
        }

        foreach ($alterStatements as $sql) {
            $conn->query($sql);
        }

        $checked = true;
    }
}

if (!function_exists('inquiry_preview_text')) {
    function inquiry_preview_text(?string $text, int $limit = 120): string
    {
        $text = trim((string)$text);
        if ($text === '' || $limit < 4) {
            return $text;
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(substr($text, 0, $limit - 3)) . '...';
    }
}

if (!function_exists('build_inquiry_reply_email_body')) {
    function build_inquiry_reply_email_body(string $recipientName, string $replyMessage, string $agentName): string
    {
        $safeRecipient = htmlspecialchars(trim($recipientName) !== '' ? $recipientName : 'there');
        $safeAgent = htmlspecialchars(trim($agentName) !== '' ? $agentName : 'NexGen Solution Team');
        $safeReply = nl2br(htmlspecialchars(trim($replyMessage)));

        return "
        <html>
        <head>
            <style>
                body { margin: 0; padding: 24px 0; background: #f6f8fb; font-family: 'Segoe UI', Arial, sans-serif; color: #25324b; }
                .email-shell { max-width: 640px; margin: 0 auto; padding: 0 16px; }
                .email-card { background: #ffffff; border: 1px solid #dbe4f0; border-radius: 20px; overflow: hidden; box-shadow: 0 18px 40px rgba(37, 50, 75, 0.12); }
                .hero { background: linear-gradient(135deg, #0f766e 0%, #155e75 100%); color: #ffffff; padding: 32px; }
                .hero h1 { margin: 0 0 10px; font-size: 28px; line-height: 1.2; }
                .hero p { margin: 0; font-size: 15px; line-height: 1.7; color: rgba(255, 255, 255, 0.9); }
                .content { padding: 30px 32px; }
                .content p { margin: 0 0 16px; line-height: 1.7; color: #475569; }
                .reply-panel { margin: 24px 0; padding: 22px; border-radius: 16px; background: #f8fafc; border-left: 4px solid #0f766e; }
                .reply-panel strong { display: block; margin-bottom: 10px; color: #0f172a; }
                .signature { margin-top: 28px; color: #334155; }
                .footer { padding: 0 32px 28px; font-size: 13px; color: #64748b; line-height: 1.7; }
            </style>
        </head>
        <body>
            <div class='email-shell'>
                <div class='email-card'>
                    <div class='hero'>
                        <h1>Reply To Your Inquiry</h1>
                        <p>Our team has reviewed your message and shared the response below.</p>
                    </div>
                    <div class='content'>
                        <p>Hello <strong>{$safeRecipient}</strong>,</p>
                        <p>Thank you for contacting NexGen Solution. Here is our reply to your inquiry:</p>
                        <div class='reply-panel'>
                            <strong>Response</strong>
                            {$safeReply}
                        </div>
                        <p class='signature'>Regards,<br>{$safeAgent}</p>
                    </div>
                    <div class='footer'>This message was sent from NexGen Solution in response to your inquiry.</div>
                </div>
            </div>
        </body>
        </html>";
    }
}

if (!function_exists('send_inquiry_reply_email')) {
    function send_inquiry_reply_email(string $to, string $recipientName, string $replyMessage, string $agentName)
    {
        require_once __DIR__ . '/mailer.php';

        $subject = 'Reply to your NexGen Solution inquiry';
        $body = build_inquiry_reply_email_body($recipientName, $replyMessage, $agentName);

        return sendEmail($to, $subject, $body);
    }
}
