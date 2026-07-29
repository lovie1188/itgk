<?php

/**
 * SmtpController - SMTP Email Configuration Controller
 * 
 * Handles SMTP Gmail configuration and email testing.
 * Strictly restricted to SUPERADMIN role.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EmailService;
use App\Helpers\Logger;

class SmtpController extends BaseController
{
    /**
     * Email service instance
     * @var EmailService|null
     */
    private ?EmailService $emailService = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Enforce SUPERADMIN role strictly
        $this->requireRole('SUPERADMIN');
    }

    /**
     * Get email service instance
     * 
     * @return EmailService
     */
    private function getEmailService(): EmailService
    {
        if ($this->emailService === null) {
            $this->emailService = new EmailService();
        }
        return $this->emailService;
    }

    /**
     * Show SMTP setup page
     * 
     * @return void
     */
    public function index(): void
    {
        $settings = $this->getEmailService()->getSettings();

        $this->view('pages/smtp_setup', [
            'title' => 'SMTP Email Setup - SoftSam Portal',
            'settings' => $settings
        ]);
    }

    /**
     * AJAX/POST: Save SMTP Configuration Settings
     * 
     * @return void
     */
    public function save(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            if (empty($input)) {
                $this->json(['success' => false, 'message' => 'Invalid or empty input data'], 400);
                return;
            }

            $success = $this->getEmailService()->saveSettings($input);

            Logger::info('SMTP settings updated by SUPERADMIN', [
                'user_id' => $this->getCurrentUser()['id'] ?? null,
                'smtp_user' => $input['smtp_user'] ?? ''
            ]);

            $this->json([
                'success' => true,
                'message' => 'SMTP settings saved successfully!'
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to save SMTP settings', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    }

    /**
     * AJAX/POST: Send Test Email
     * 
     * @return void
     */
    public function test(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $toEmail = trim($input['test_email'] ?? $input['email'] ?? '');

            if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $this->json(['success' => false, 'message' => 'Valid recipient email address required'], 400);
                return;
            }

            // Save transient settings if passed along in test request
            if (!empty($input['smtp_user'])) {
                $this->getEmailService()->saveSettings($input);
            }

            $result = $this->getEmailService()->sendTestEmail($toEmail);

            $this->json($result, 200);
        } catch (\Exception $e) {
            Logger::error('SMTP test execution failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    }
}
