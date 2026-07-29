<?php

/**
 * UploadController - Data Upload Controller
 * 
 * Handles data upload from Excel, CSV files, and Google Sheets.
 * Provides table schema inspection and data mapping.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Logger;
use App\Services\UploadService;

/**
 * UploadController Class
 * 
 * Controller for data upload operations.
 */
class UploadController extends BaseController
{
/**
     * System sheets that should not be available for upload
     * @var array
     */
    private array $systemSheets = ['Sheet1', 'Sheet2', 'Sheet3'];

    /**
     * Upload service instance
     * @var UploadService|null
     */
    private ?UploadService $uploadService = null;

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
     * Get upload service instance
     *
     * @return UploadService
     */
    private function getUploadService(): UploadService
    {
        if ($this->uploadService === null) {
            $this->uploadService = new UploadService();
        }
        return $this->uploadService;
    }

    /**
     * Show upload page
     * 
     * @return void
     */
    public function index(): void
    {
        // Get available tables
        $availableTables = $this->getAvailableTables();

        $this->view('pages/upload', [
            'title' => 'Data Upload - SoftSam Portal',
            'availableTables' => $availableTables
        ]);
    }

    /**
     * AJAX: Get sheet column headers
     *
     * @return void
     */
    public function getTableSchema(): void
    {
        try {
            $sheetId = $_POST['sheet_id'] ?? $_GET['sheet_id'] ?? '';
            $tabName = $_POST['tab'] ?? $_GET['tab'] ?? 'Sheet1';

            if (empty($sheetId)) {
                throw new \Exception('Spreadsheet ID required');
            }

            $sheetService = new \App\Services\GoogleSheetService();
            $data = $sheetService->fetchSheet($sheetId, $tabName . '!1:1');
            $headers = $data['rows'][0] ?? [];

            $columns = [];
            foreach ($headers as $idx => $header) {
                $columns[] = [
                    'Field' => $header,
                    'Type' => 'string',
                    'Null' => 'YES',
                    'Col' => $idx + 1
                ];
            }

            $this->json(['success' => true, 'columns' => $columns]);
        } catch (\Exception $e) {
            Logger::error('Failed to get sheet schema', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * AJAX: Fetch Google Sheet data via SSO API
     * 
     * @return void
     */
    public function fetchGoogleSheet(): void
    {
        try {
            $spreadsheetId = $_POST['spreadsheet_id'] ?? '';
            $range = $_POST['range'] ?? 'Sheet1!A:Z';

            if (empty($spreadsheetId)) {
                throw new \Exception('Spreadsheet ID required');
            }

            // Use upload service to fetch data
            $result = $this->getUploadService()->fetchGoogleSheet($spreadsheetId, $range);

            // Store in session
            $_SESSION['upload_data'] = [
                'data' => $result['rows'],
                'headers' => $result['headers'],
                'source' => 'google_sheet',
                'spreadsheet_id' => $spreadsheetId,
                'range' => $range,
                'upload_time' => time()
            ];

            Logger::info('Google Sheet data fetched', [
                'spreadsheet_id' => $spreadsheetId,
                'row_count' => count($result['rows']),
                'user_id' => $this->getCurrentUser()['id'] ?? null
            ]);

            $this->json([
                'success' => true,
                'message' => 'Sheet data fetched successfully',
                'row_count' => count($result['rows']),
                'headers' => $result['headers']
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to fetch Google Sheet', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Upload file (Excel/CSV)
     * 
     * @return void
     */
    public function uploadFile(): void
    {
        try {
            if (!isset($_FILES['data_file'])) {
                throw new \Exception('No file uploaded');
            }

            $file = $_FILES['data_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('File upload error: ' . $this->getUploadErrorMessage($file['error']));
            }

            // Check file size (10MB limit)
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new \Exception('File size exceeds 10MB limit');
            }

            // Use upload service to parse file
            $result = $this->getUploadService()->parseFileWithName($file['tmp_name'], $file['name']);

            // Store in session
            $_SESSION['upload_data'] = [
                'data' => $result['rows'],
                'headers' => $result['headers'],
                'source' => 'file',
                'filename' => $file['name'],
                'upload_time' => time()
            ];

            Logger::info('File uploaded and parsed', [
                'filename' => $file['name'],
                'row_count' => count($result['rows']),
                'user_id' => $this->getCurrentUser()['id'] ?? null
            ]);

            $this->json([
                'success' => true,
                'message' => 'File parsed successfully',
                'row_count' => count($result['rows']),
                'headers' => $result['headers'],
                'filename' => $file['name']
            ]);
        } catch (\Exception $e) {
            Logger::error('File upload failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * AJAX: Perform data upload to Google Sheets
     *
     * @return void
     */
    public function performUpload(): void
    {
        try {
            $sheetId  = $_POST['sheet_id'] ?? '';
            $tabName  = $_POST['tab'] ?? 'Sheet1';
            $method   = $_POST['method'] ?? 'new';
            $mapping  = json_decode($_POST['mapping'] ?? '[]', true);

            if (empty($sheetId) || empty($mapping)) {
                throw new \Exception('Spreadsheet ID and mapping required');
            }

            // 1. Prefer session data (set by uploadFile / fetchGoogleSheet)
            $data = ($_SESSION['upload_data']['data'] ?? null);

            // 2. Fall back to JSON body
            if ($data === null) {
                $jsonBody = json_decode(file_get_contents('php://input'), true);
                if (isset($jsonBody['rows']) && is_array($jsonBody['rows'])) {
                    $data = $jsonBody['rows'];
                }
            }

            if ($data === null || empty($data)) {
                throw new \Exception('No upload data found. Upload a file or fetch a sheet first.');
            }

            $sheetService = new \App\Services\GoogleSheetService();
            $inserted = 0;

            foreach ($data as $row) {
                $mappedRow = [];
                foreach ($mapping as $colIndex => $headerName) {
                    $mappedRow[$colIndex] = $row[$headerName] ?? $row[$colIndex] ?? '';
                }
                // Sort by column index
                ksort($mappedRow);
                $sheetService->appendRow($sheetId, $tabName, [array_values($mappedRow)]);
                $inserted++;
            }

            // Clear session data if present
            if (isset($_SESSION['upload_data'])) {
                unset($_SESSION['upload_data']);
            }

            Logger::info('Data upload to Google Sheets completed', [
                'sheet_id' => $sheetId,
                'tab' => $tabName,
                'inserted' => $inserted,
                'user_id' => $this->getCurrentUser()['id'] ?? null
            ]);

            $this->json([
                'success' => true,
                'message' => 'Data uploaded to Google Sheets successfully',
                'inserted' => $inserted
            ]);
        } catch (\Exception $e) {
            Logger::error('Data upload to Google Sheets failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get available Google Sheets for upload
     *
     * @return array
     */
    private function getAvailableTables(): array
    {
        try {
            $sheetService = new \App\Services\GoogleSheetService();
            $presets = $sheetService->getPresets();

            return array_diff($presets, $this->systemSheets);
        } catch (\Exception $e) {
            Logger::error('Failed to get available sheets', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
    }

    /**
     * AJAX: Get all upload templates
     * 
     * @return void
     */
    public function getTemplates(): void
    {
        try {
            $templates = $this->getUploadService()->getTemplates();
            $this->json(['success' => true, 'data' => $templates]);
        } catch (\Exception $e) {
            Logger::error('Failed to get templates', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Delete a template
     * 
     * @return void
     */
    public function deleteTemplate(): void
    {
        try {
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                $this->json(['success' => false, 'message' => 'Template ID required'], 400);
                return;
            }
            $result = $this->getUploadService()->deleteTemplate($id);
            if ($result) {
                $this->json(['success' => true, 'message' => 'Template deleted']);
            } else {
                $this->json(['success' => false, 'message' => 'Template not found'], 404);
            }
        } catch (\Exception $e) {
            Logger::error('Failed to delete template', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
