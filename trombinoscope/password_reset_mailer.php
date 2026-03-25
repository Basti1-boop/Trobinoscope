<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function ensure_password_resets_table(PDO $pdo): void
{
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_password_resets_token_hash (token_hash),
        KEY idx_password_resets_user_id (user_id),
        CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
}

function send_password_reset_email(string $toEmail, string $toName, string $resetUrl): void
{
    $smtpHost = defined('MAILTRAP_SMTP_HOST') ? trim((string) MAILTRAP_SMTP_HOST) : '';
    $smtpPort = defined('MAILTRAP_SMTP_PORT') ? (int) MAILTRAP_SMTP_PORT : 0;
    $smtpUsername = defined('MAILTRAP_SMTP_USERNAME') ? trim((string) MAILTRAP_SMTP_USERNAME) : '';
    $smtpPassword = defined('MAILTRAP_SMTP_PASSWORD') ? trim((string) MAILTRAP_SMTP_PASSWORD) : '';

    if ($smtpHost === '' || $smtpPort <= 0 || $smtpUsername === '' || $smtpPassword === '') {
        throw new RuntimeException('Configuration SMTP Mailtrap incomplete dans config.php.');
    }

    try {
        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $smtpHost;
        $mailer->Port = $smtpPort;
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtpUsername;
        $mailer->Password = $smtpPassword;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->CharSet = 'UTF-8';

        $mailer->setFrom('hello@demomailtrap.co', 'Trombinoscope');
        $mailer->addAddress($toEmail, $toName);
        $mailer->Subject = 'Demande de changement de mot de passe';

        $html = '<p>Bonjour ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Clique sur ce lien pour changer ton mot de passe :</p>'
            . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Changer mon mot de passe</a></p>'
            . '<p>Ce lien expire dans 30 minutes.</p>'
            . '<p>Si tu n es pas a l origine de cette demande, ignore simplement cet email.</p>';

        $text = "Bonjour {$toName},\n\n"
            . "Clique sur ce lien pour changer ton mot de passe : {$resetUrl}\n\n"
            . "Ce lien expire dans 30 minutes.\n"
            . "Si tu n es pas a l origine de cette demande, ignore simplement cet email.";

        $mailer->isHTML(true);
        $mailer->Body = $html;
        $mailer->AltBody = $text;
        $mailer->send();
    } catch (Exception $e) {
        throw new RuntimeException('Erreur SMTP Mailtrap: ' . $e->getMessage());
    }
}
