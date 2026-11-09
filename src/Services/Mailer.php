<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends transactional e-mails through SMTP (PHPMailer, installed via Composer).
 * When SMTP is not configured, messages are written to the PHP error log so
 * the application remains fully usable in local development.
 */
final class Mailer
{
    public function __construct(private readonly Config $config)
    {
    }

    public function sendActivationLink(string $email, string $activationUrl): bool
    {
        $appName = (string) $this->config->get('app.name');
        $link    = htmlspecialchars($activationUrl, ENT_QUOTES, 'UTF-8');

        return $this->send(
            $email,
            "Activa tu cuenta en {$appName}",
            "<p>¡Gracias por registrarte en {$appName}!</p>"
            . "<p>Pulsa el siguiente enlace para activar tu cuenta:</p>"
            . "<p><a href=\"{$link}\">Activar mi cuenta</a></p>",
            "Activa tu cuenta en {$appName}: {$activationUrl}",
        );
    }

    private function send(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        if (!$this->isConfigured()) {
            error_log("[mail] SMTP not configured. To: {$to} | Subject: {$subject} | {$textBody}");

            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = (string) $this->config->get('mail.host');
            $mail->Port       = (int) $this->config->get('mail.port');
            $mail->SMTPAuth   = true;
            $mail->Username   = (string) $this->config->get('mail.username');
            $mail->Password   = (string) $this->config->get('mail.password');
            $mail->SMTPSecure = (string) $this->config->get('mail.encryption');
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;

            $mail->setFrom((string) $this->config->get('mail.from.address'), (string) $this->config->get('mail.from.name'));
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            return $mail->send();
        } catch (MailerException $e) {
            error_log('[mail] Delivery failed: ' . $mail->ErrorInfo);

            return false;
        }
    }

    private function isConfigured(): bool
    {
        return $this->config->get('mail.host') !== '' && class_exists(PHPMailer::class);
    }
}
