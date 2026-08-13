<?php

/**
 * EmailService - SMTP Email Service for SoftSam Portal
 * 
 * Ported & enhanced from RSCITWALA project.
 * Supports Gmail & custom SMTP servers with PHPMailer.
 * All settings read from .env only (no MySQL dependency).
 * 
 * @package App\Services
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private ?array $settings = null;

    public function __construct()
    {
    }

    public function getSettings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $this->settings = [
            'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
            'smtp_user' => getenv('SMTP_USER') ?: 'softtech.lovejeet@gmail.com',
            'smtp_pass' => getenv('SMTP_PASS') ?: '',
            'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            'smtp_from_email' => getenv('SMTP_FROM_EMAIL') ?: 'softtech.lovejeet@gmail.com',
            'smtp_from_name' => getenv('SMTP_FROM_NAME') ?: 'SoftSam ITGK Portal'
        ];

        return $this->settings;
    }

    public function saveSettings(array $newSettings): bool
    {
        // SMTP settings are .env only - not persisted to database
        Logger::warn('EmailService::saveSettings() - settings are now .env only, not persisted to DB');
        return false;
    }

    /**
     * Configure and return PHPMailer instance
     * 
     * @return PHPMailer
     * @throws Exception
     */
    private function getMailer(): PHPMailer
    {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            $srcDir = dirname(__DIR__, 2) . '/vendor/phpmailer/phpmailer/src';
            if (file_exists($srcDir . '/PHPMailer.php')) {
                require_once $srcDir . '/Exception.php';
                require_once $srcDir . '/PHPMailer.php';
                require_once $srcDir . '/SMTP.php';
            }
        }

        $config = $this->getSettings();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_pass'];
        
        $encryption = strtolower($config['smtp_encryption']);
        $mail->SMTPSecure = ($encryption === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)$config['smtp_port'];

        // Disable SSL verification for local development environments
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
        $mail->addReplyTo($config['smtp_from_email'], $config['smtp_from_name']);

        return $mail;
    }

    /**
     * Get a pre-configured PHPMailer instance
     * 
     * @return PHPMailer
     */
    public function getMailerInstance(): PHPMailer
    {
        return $this->getMailer();
    }

    /**
     * Send test email to verify SMTP credentials
     * 
     * @param string $toEmail Recipient email address
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendTestEmail(string $toEmail): array
    {
        try {
            $config = $this->getSettings();
            $mail = $this->getMailer();
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'SMTP Configuration Test — SoftSam Portal 🚀';

            $timeStr = date('Y-m-d H:i:s');
            $body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; color: #1e293b; background: #f8fafc; }
                    .container { max-width: 550px; margin: 20px auto; padding: 15px; }
                    .card { background: #ffffff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
                    .success-title { color: #16a34a; font-size: 18px; font-weight: 700; margin-bottom: 12px; }
                    .info-box { background: #f1f5f9; padding: 12px; border-radius: 6px; font-size: 13px; margin: 12px 0; }
                    .footer { font-size: 11px; color: #64748b; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='card'>
                        <div class='success-title'>✅ Gmail SMTP Configuration Working!</div>
                        <p>If you are receiving this message, your Gmail SMTP email settings in <strong>SoftSam Certificate Portal</strong> are configured correctly.</p>
                        <div class='info-box'>
                            <p style='margin: 0 0 4px 0;'><strong>SMTP Host:</strong> {$config['smtp_host']}</p>
                            <p style='margin: 0 0 4px 0;'><strong>Port:</strong> {$config['smtp_port']}</p>
                            <p style='margin: 0 0 4px 0;'><strong>User:</strong> {$config['smtp_user']}</p>
                            <p style='margin: 0;'><strong>Timestamp:</strong> {$timeStr}</p>
                        </div>
                        <div class='footer'>Automated test notification from SoftSam Admin Panel.</div>
                    </div>
                </div>
            </body>
            </html>";

            $mail->Body = $body;
            $mail->AltBody = "✅ SMTP Configuration is Working! Host: {$config['smtp_host']}, Port: {$config['smtp_port']}, Time: {$timeStr}";

            $mail->send();
            Logger::info("SMTP test email sent successfully to {$toEmail}");

            return [
                'success' => true,
                'message' => "Test email sent successfully to {$toEmail}!"
            ];
        } catch (\Exception $e) {
            Logger::error("SMTP test email failed to {$toEmail}", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => "SMTP Error: " . $e->getMessage()
            ];
        }
    }

    /**
     * Send password recovery email (ported from RSCITWALA)
     * 
     * @param string $toEmail Recipient email address
     * @param string $name User display name
     * @param string $password Credentials / reset instructions
     * @return bool
     */
    public function sendPasswordRecoveryEmail(string $toEmail, string $name, string $password): bool
    {
        try {
            $mail = $this->getMailer();
            $mail->addAddress($toEmail, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Password Recovery - SoftSam Portal 🔐';

            $body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: 'Segoe UI', Roboto, sans-serif; line-height: 1.5; color: #333; }
                    .container { max-width: 550px; margin: 0 auto; padding: 15px; }
                    .card { background: #ffffff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; }
                    .password-box { background: #fef2f2; border: 1px solid #fecaca; padding: 12px; border-radius: 6px; text-align: center; margin: 15px 0; }
                    .password { color: #b91c1c; font-size: 22px; font-weight: bold; font-family: monospace; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='card'>
                        <h3 style='color: #2563eb; margin-top: 0;'>Hello " . htmlspecialchars($name) . ",</h3>
                        <p>You requested password recovery for <strong>SoftSam Portal</strong>.</p>
                        
                        <div class='password-box'>
                            <p style='margin:0; color: #991b1b; font-size: 12px;'>Your Password:</p>
                            <div class='password'>" . htmlspecialchars($password) . "</div>
                        </div>
                        
                        <p style='font-size: 13px;'>Please login and update your password immediately.</p>
                        <p style='color: #64748b; font-size: 11px; margin-top: 20px;'>Automated message. Do not reply directly.</p>
                    </div>
                </div>
            </body>
            </html>";

            $mail->Body = $body;
            $mail->AltBody = "Hello {$name},\n\nYour Password: {$password}\n\nPlease login and change your password.\n\n- SoftSam Portal";

            $mail->send();
            return true;
        } catch (\Exception $e) {
            Logger::error("Failed to send password recovery email to {$toEmail}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send Packet Issue Acknowledgement Receipt Email to ITGK
     */
    public function sendPacketIssueEmail(string $toEmail, array $certData, array $receiverData): bool
    {
        try {
            $mail = $this->getMailer();
            $mail->addAddress($toEmail, $receiverData['name'] ?? 'ITGK Representative');
            $mail->isHTML(true);
            $mail->Subject = 'Certificate Packet Issue Acknowledgement Receipt - SoftSam Portal 📜';

            $body = "
            <!DOCTYPE html>
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 8px;'>Certificate Packet Issue Receipt</h2>
                    <p>Dear <strong>" . htmlspecialchars($receiverData['name'] ?? 'ITGK Partner') . "</strong>,</p>
                    <p>This is an official acknowledgement that the following ITGK Certificate Packet has been issued:</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>ITGK Code:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($certData['itgk_code'] ?? '')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Course Name:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($certData['course_name'] ?? '')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Exam Name:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($certData['exam_name'] ?? '')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Pass Count:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($certData['pass'] ?? '0')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Cert Nos:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($certData['cert_no_from'] ?? '')) . " - " . htmlspecialchars((string)($certData['cert_no_to'] ?? '')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Receiver Mobile:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($receiverData['mobile'] ?? '')) . "</td></tr>
                    </table>
                    <p style='color: #64748b; font-size: 12px;'>SoftSam Certificate Management Portal</p>
                </div>
            </body>
            </html>";

            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            Logger::error("Failed to send packet issue email to {$toEmail}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send Learner Certificate Issue Acknowledgement Receipt Email
     */
    public function sendLearnerCertificateIssueEmail(string $toEmail, array $learnerData): bool
    {
        try {
            $mail = $this->getMailer();
            $mail->addAddress($toEmail, $learnerData['learner_name'] ?? 'Learner');
            $mail->isHTML(true);
            $mail->Subject = 'Learner Certificate Issue Acknowledgement Receipt - SoftSam Portal 🎓';

            $body = "
            <!DOCTYPE html>
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 8px;'>Learner Certificate Issue Receipt</h2>
                    <p>Dear <strong>" . htmlspecialchars($learnerData['learner_name'] ?? 'Learner') . "</strong>,</p>
                    <p>Your official examination certificate has been issued:</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Learner Code:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($learnerData['learner_code'] ?? '')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Course Name:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($learnerData['course_name'] ?? '')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Result:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($learnerData['result'] ?? 'PASS')) . "</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Certificate Number:</td><td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)($learnerData['certificate_no'] ?? 'N/A')) . "</td></tr>
                    </table>
                    <p style='color: #64748b; font-size: 12px;'>SoftSam Certificate Management Portal</p>
                </div>
            </body>
            </html>";

            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            Logger::error("Failed to send learner certificate email to {$toEmail}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Enqueue an email into the database queue for async processing
     *
     * @param string $toEmail Recipient email address
     * @param string $subject Email subject
     * @param string $body    HTML or plain text body
     * @param bool   $isHtml  Whether body is HTML
     * @return bool           Success state
     */
    public function enqueue(string $toEmail, string $subject, string $body, bool $isHtml = true): bool
    {
        try {
            $db = \App\Core\Database::getInstance();
            $db->insert('email_queue', [
                'recipient_email' => $toEmail,
                'subject' => $subject,
                'body' => $body,
                'is_html' => $isHtml ? 1 : 0,
                'status' => 'pending'
            ]);
            Logger::info('Email enqueued successfully', ['to' => $toEmail, 'subject' => $subject]);
            return true;
        } catch (\Exception $e) {
            Logger::warn('Database email queue unavailable, sending directly via SMTP', ['error' => $e->getMessage(), 'to' => $toEmail]);
            return $this->sendDirect($toEmail, $subject, $body, $isHtml);
        }
    }

    /**
     * Send email directly via PHPMailer (synchronous fallback)
     */
    public function sendDirect(string $toEmail, string $subject, string $body, bool $isHtml = true): bool
    {
        try {
            $mail = $this->getMailer();
            $mail->addAddress($toEmail);
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            Logger::info('Direct SMTP email sent successfully', ['to' => $toEmail, 'subject' => $subject]);
            return true;
        } catch (\Exception $ex) {
            Logger::error('Direct SMTP email failed', ['to' => $toEmail, 'error' => $ex->getMessage()]);
            return false;
        }
    }

    /**
     * Process pending queued emails
     *
     * @param int $limit Number of emails to process per run
     * @return int       Number of emails successfully processed
     */
    public function processQueue(int $limit = 10): int
    {
        $db = \App\Core\Database::getInstance();
        $pending = $db->fetchAll(
            "SELECT * FROM email_queue WHERE status = 'pending' ORDER BY id ASC LIMIT ?",
            [$limit]
        );

        if (empty($pending)) {
            return 0;
        }

        $sentCount = 0;
        foreach ($pending as $item) {
            try {
                $mail = $this->getMailer();
                $mail->addAddress($item['recipient_email']);
                $mail->isHTML((bool)$item['is_html']);
                $mail->Subject = $item['subject'];
                $mail->Body = $item['body'];

                $mail->send();

                $db->update(
                    'email_queue',
                    ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$item['id']]
                );
                $sentCount++;
            } catch (\Exception $e) {
                Logger::error("Queue email send failed ID {$item['id']}", ['error' => $e->getMessage()]);
                $db->update(
                    'email_queue',
                    ['status' => 'failed', 'error_message' => $e->getMessage()],
                    'id = ?',
                    [$item['id']]
                );
            }
        }

        return $sentCount;
    }
}
