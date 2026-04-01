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

function send_ip_unblock_email(string $toEmail, string $toName, string $unblockUrl): void
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
        $mailer->Subject = 'Deblocage de connexion';

        $html = '<p>Bonjour ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Tu as atteint la limite de tentatives. Clique sur ce lien pour debloquer la connexion depuis ton appareil :</p>'
            . '<p><a href="' . htmlspecialchars($unblockUrl, ENT_QUOTES, 'UTF-8') . '">Debloquer mon acces</a></p>'
            . '<p>Ce lien expire dans 30 minutes.</p>'
            . '<p>Si tu n es pas a l origine de cette demande, ignore simplement cet email.</p>';

        $text = "Bonjour {$toName},\n\n"
            . "Tu as atteint la limite de tentatives. Clique sur ce lien pour debloquer la connexion depuis ton appareil : {$unblockUrl}\n\n"
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

function send_ip_unblock_request_to_admin(string $toEmail, string $toName, string $ip, string $adminUrl): void
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
        $mailer->Subject = 'Demande de deban IP';

        $html = '<p>Bonjour ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Une demande de deban a ete faite pour l IP suivante : <strong>' . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p>Ouvrez le panneau admin pour traiter la demande :</p>'
            . '<p><a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '">Voir les demandes</a></p>';

        $text = "Bonjour {$toName},\n\n"
            . "Une demande de deban a ete faite pour l IP suivante : {$ip}\n"
            . "Ouvrez le panneau admin pour traiter la demande : {$adminUrl}\n";

        $mailer->isHTML(true);
        $mailer->Body = $html;
        $mailer->AltBody = $text;
        $mailer->send();
    } catch (Exception $e) {
        throw new RuntimeException('Erreur SMTP Mailtrap: ' . $e->getMessage());
    }
}

function send_user_unban_request_to_admin(
    string $toEmail,
    string $toName,
    string $userFullName,
    string $userEmail,
    string $adminUrl,
    string $reason
): void {
    $smtpHost = defined('MAILTRAP_SMTP_HOST') ? trim((string) MAILTRAP_SMTP_HOST) : '';
    $smtpPort = defined('MAILTRAP_SMTP_PORT') ? (int) MAILTRAP_SMTP_PORT : 0;
    $smtpUsername = defined('MAILTRAP_SMTP_USERNAME') ? trim((string) MAILTRAP_SMTP_USERNAME) : '';
    $smtpPassword = defined('MAILTRAP_SMTP_PASSWORD') ? trim((string) MAILTRAP_SMTP_PASSWORD) : '';

    if ($smtpHost === '' || $smtpPort <= 0 || $smtpUsername === '' || $smtpPassword === '') {
        throw new RuntimeException('Configuration SMTP Mailtrap incomplete dans config.php.');
    }

    $safeReason = trim($reason);
    if ($safeReason === '') {
        $safeReason = 'Aucune raison fournie.';
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
        $mailer->Subject = 'Demande de deban utilisateur';

        $html = '<p>Bonjour ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Une demande de deban utilisateur a ete soumise :</p>'
            . '<ul>'
            . '<li>Utilisateur : <strong>' . htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8') . '</strong></li>'
            . '<li>Email : <strong>' . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . '</strong></li>'
            . '<li>Raison : ' . htmlspecialchars($safeReason, ENT_QUOTES, 'UTF-8') . '</li>'
            . '</ul>'
            . '<p>Ouvrez le panneau admin pour traiter la demande :</p>'
            . '<p><a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '">Voir les demandes</a></p>';

        $text = "Bonjour {$toName},\n\n"
            . "Une demande de deban utilisateur a ete soumise :\n"
            . "Utilisateur : {$userFullName}\n"
            . "Email : {$userEmail}\n"
            . "Raison : {$safeReason}\n\n"
            . "Ouvrez le panneau admin pour traiter la demande : {$adminUrl}\n";

        $mailer->isHTML(true);
        $mailer->Body = $html;
        $mailer->AltBody = $text;
        $mailer->send();
    } catch (Exception $e) {
        throw new RuntimeException('Erreur SMTP Mailtrap: ' . $e->getMessage());
    }
}

function send_user_unban_decision_email(string $toEmail, string $toName, bool $approved): void
{
    $smtpHost = defined('MAILTRAP_SMTP_HOST') ? trim((string) MAILTRAP_SMTP_HOST) : '';
    $smtpPort = defined('MAILTRAP_SMTP_PORT') ? (int) MAILTRAP_SMTP_PORT : 0;
    $smtpUsername = defined('MAILTRAP_SMTP_USERNAME') ? trim((string) MAILTRAP_SMTP_USERNAME) : '';
    $smtpPassword = defined('MAILTRAP_SMTP_PASSWORD') ? trim((string) MAILTRAP_SMTP_PASSWORD) : '';

    if ($smtpHost === '' || $smtpPort <= 0 || $smtpUsername === '' || $smtpPassword === '') {
        throw new RuntimeException('Configuration SMTP Mailtrap incomplete dans config.php.');
    }

    $subject = $approved ? 'Demande de deban approuvee' : 'Demande de deban refusee';
    $statusText = $approved ? 'approuvee' : 'refusee';

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
        $mailer->Subject = $subject;

        $html = '<p>Bonjour ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Votre demande de deban a ete ' . htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') . '.</p>'
            . '<p>Vous pouvez maintenant essayer de vous reconnecter.</p>';

        $text = "Bonjour {$toName},\n\n"
            . "Votre demande de deban a ete {$statusText}.\n"
            . "Vous pouvez maintenant essayer de vous reconnecter.\n";

        $mailer->isHTML(true);
        $mailer->Body = $html;
        $mailer->AltBody = $text;
        $mailer->send();
    } catch (Exception $e) {
        throw new RuntimeException('Erreur SMTP Mailtrap: ' . $e->getMessage());
    }
}

function send_report_auto_delete_to_admin(string $toEmail, string $toName, string $targetType, string $adminUrl): void
{
    $smtpHost = defined('MAILTRAP_SMTP_HOST') ? trim((string) MAILTRAP_SMTP_HOST) : '';
    $smtpPort = defined('MAILTRAP_SMTP_PORT') ? (int) MAILTRAP_SMTP_PORT : 0;
    $smtpUsername = defined('MAILTRAP_SMTP_USERNAME') ? trim((string) MAILTRAP_SMTP_USERNAME) : '';
    $smtpPassword = defined('MAILTRAP_SMTP_PASSWORD') ? trim((string) MAILTRAP_SMTP_PASSWORD) : '';

    if ($smtpHost === '' || $smtpPort <= 0 || $smtpUsername === '' || $smtpPassword === '') {
        throw new RuntimeException('Configuration SMTP Mailtrap incomplete dans config.php.');
    }

    $safeType = $targetType === 'commentaire' ? 'un commentaire' : 'une publication';

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
        $mailer->Subject = 'Suppression automatique apres signalements';

        $html = '<p>Bonjour ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Un contenu (' . htmlspecialchars($safeType, ENT_QUOTES, 'UTF-8') . ') a ete supprime automatiquement apres 5 signalements.</p>'
            . '<p>Ouvrez le panneau de moderation pour valider ou restaurer :</p>'
            . '<p><a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '">Acceder a la moderation</a></p>';

        $text = "Bonjour {$toName},\n\n"
            . "Un contenu ({$safeType}) a ete supprime automatiquement apres 5 signalements.\n"
            . "Ouvrez le panneau de moderation pour valider ou restaurer : {$adminUrl}\n";

        $mailer->isHTML(true);
        $mailer->Body = $html;
        $mailer->AltBody = $text;
        $mailer->send();
    } catch (Exception $e) {
        throw new RuntimeException('Erreur SMTP Mailtrap: ' . $e->getMessage());
    }
}
