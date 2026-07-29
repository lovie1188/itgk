<?php

/**
 * SetupController - Application Setup Controller
 * 
 * Handles application configuration, settings management,
 * and connection testing.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Helpers\Logger;

/**
 * SetupController Class
 * 
 * Controller for application setup and configuration.
 */
class SetupController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // SUPERADMIN only access
        $this->requireRole('SUPERADMIN');
    }

    /**
     * Show setup page
     * 
     * @return void
     */
    public function index(): void
    {
        // Get current settings
        $settings = $this->getSettings();

        // Get available tables for sync
        $tables = $this->getTables();

        $this->view('pages/setup', [
            'title' => 'Application Setup - SoftSam Portal',
            'settings' => $settings,
            'tables' => $tables,
            'ssoUrl' => getenv('SSO_URL')
        ]);
    }

    /**
     * Save settings
     * 
     * @return void
     */
    public function save(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                $this->json(['success' => false, 'message' => 'Invalid input'], 400);
                return;
            }

            // Save each setting in DB & .env
            $envData = [];
            $envMapping = [
                'google_sheet_id' => 'GSHEET_CERTIFICATE_ID',
                'google_sheet_tab' => 'GSHEET_CERTIFICATE_TAB',
                'google_sheet_range' => 'GSHEET_CERTIFICATE_RANGE',
                'student_result_sheet_id' => 'GSHEET_STUDENT_RESULT_ID',
                'student_result_tab' => 'GSHEET_STUDENT_RESULT_TAB',
                'student_result_range' => 'GSHEET_STUDENT_RESULT_RANGE',
                'itgk_master_sheet_id' => 'GSHEET_ITGK_MASTER_ID',
                'itgk_master_tab' => 'GSHEET_ITGK_MASTER_TAB',
                'itgk_master_range' => 'GSHEET_ITGK_MASTER_RANGE',
                'sync_mode' => 'SYNC_MODE'
            ];

            foreach ($input as $key => $value) {
                $valStr = is_array($value) ? json_encode($value) : (string)$value;
                $this->setSetting($key, $valStr);

                if (isset($envMapping[$key])) {
                    $envData[$envMapping[$key]] = $valStr;
                }
            }

            if (!empty($envData)) {
                \App\Helpers\Env::updateEnvFile($envData);
            }

            Logger::info('Settings saved and synchronized to .env', [
                'keys' => array_keys($input),
                'user_id' => $this->getCurrentUser()['id'] ?? null
            ]);

            $this->json(['success' => true, 'message' => 'Settings saved & synchronized to .env successfully!']);
        } catch (\Exception $e) {
            Logger::error('Failed to save settings', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test Google Sheet connection via SSO API or Direct Service
     * 
     * @return void
     */
    public function testConnection(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $spreadsheetId = trim($input['spreadsheet_id'] ?? getenv('GSHEET_CERTIFICATE_ID') ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4');
            $sheetTab = trim($input['sheet_tab'] ?? getenv('GSHEET_CERTIFICATE_TAB') ?: 'Certificate');
            $range = trim($input['range'] ?? '');

            if (empty($range)) {
                $range = !empty($sheetTab) ? "{$sheetTab}!A1:V" : "Certificate!A1:V";
            }

            if (empty($spreadsheetId)) {
                throw new \Exception('Spreadsheet ID is required');
            }

            // Use native standalone GoogleSheetService
            $sheetService = new \App\Services\GoogleSheetService();
            $parsed = $sheetService->fetchParsedSheet($spreadsheetId, $range);

            $rowCount = count($parsed['rows']);

            Logger::info('Google Sheet connection test successful', [
                'spreadsheet_id' => $spreadsheetId,
                'sheet_tab' => $sheetTab,
                'rows_found' => $rowCount
            ]);

            $this->json([
                'success' => true,
                'message' => "Connection successful! Fetched $rowCount data rows directly from Google Sheet.",
                'headers' => $parsed['headers'],
                'sample_data' => array_slice($parsed['rows'], 0, 3)
            ]);
        } catch (\Exception $e) {
            Logger::error('Google Sheet connection test failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    }

    /**
     * Get all settings
     * 
     * @return array
     */
    private function getSettings(): array
    {
        $db = Database::getInstance();

        try {
            // Check if table exists
            $db->fetch("SELECT 1 FROM app_settings LIMIT 1");
        } catch (\Exception $e) {
            // Create table if not exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS `app_settings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
                    `setting_value` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM app_settings");

        $settings = [];
        foreach ($rows as $row) {
            $value = $row['setting_value'];
            $decoded = json_decode($value, true);
            $settings[$row['setting_key']] = $decoded !== null ? $decoded : $value;
        }

        // Smart defaults merged with .env values
        $defaults = [
            'sync_mode'              => getenv('SYNC_MODE') ?: 'google_sheet',
            // Certificate sheet
            'google_sheet_id'        => getenv('GSHEET_CERTIFICATE_ID')        ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4',
            'google_sheet_tab'       => getenv('GSHEET_CERTIFICATE_TAB')       ?: 'Certificate',
            'google_sheet_range'     => getenv('GSHEET_CERTIFICATE_RANGE')     ?: 'Certificate!A1:V',
            // Student result sheet
            'student_result_sheet_id'=> getenv('GSHEET_STUDENT_RESULT_ID')    ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4',
            'student_result_tab'     => getenv('GSHEET_STUDENT_RESULT_TAB')   ?: 'Student_Result',
            'student_result_range'   => getenv('GSHEET_STUDENT_RESULT_RANGE') ?: 'Student_Result!A1:Z',
            // ITGK master sheet
            'itgk_master_sheet_id'   => getenv('GSHEET_ITGK_MASTER_ID')       ?: '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg',
            'itgk_master_tab'        => getenv('GSHEET_ITGK_MASTER_TAB')      ?: 'ITGK',
            'itgk_master_range'      => getenv('GSHEET_ITGK_MASTER_RANGE')    ?: 'ITGK!A1:R131',
            'auto_sync_enabled'      => false,
            'auto_sync_interval'     => 60
        ];

        return array_merge($defaults, $settings);
    }

    /**
     * Get database tables
     * 
     * @return array
     */
    private function getTables(): array
    {
        try {
            $db = Database::getInstance();
            $result = $db->fetchAll("SHOW TABLES");
            return array_column($result, array_key_first($result[0] ?? ['']));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set a single setting
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return void
     */
    private function setSetting(string $key, $value): void
    {
        $db = Database::getInstance();

        $db->query(
            "INSERT INTO app_settings (setting_key, setting_value) 
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value]
        );
    }
}
