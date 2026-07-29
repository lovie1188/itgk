<?php

/**
 * UploadService - File Upload and Data Import Service
 * 
 * Handles file uploads (Excel/CSV) and Google Sheets imports.
 * All data operations use Google Sheets exclusively.
 * MySQL is NOT used in any data service.
 * 
 * @package App\Services
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Services\GoogleSheetService;
use App\Exceptions\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class UploadService
{
    private array $allowedTables = [
        'itgk_certificate' => 'ITGK Certificates',
        'itgk_learner_result' => 'ITGK Learner Results',
    ];

    private GoogleSheetService $sheetService;

    public function __construct()
    {
        $this->sheetService = new GoogleSheetService();
    }

    public function getAllowedTables(): array
    {
        return $this->allowedTables;
    }

    public function getTableSchema(string $table): array
    {
        if (!isset($this->allowedTables[$table])) {
            throw new ValidationException("Table not allowed for upload: {$table}");
        }

        if ($table === 'itgk_certificate') {
            return $this->getSheetSchema(
                $this->sheetService->getCertificateSheetId(),
                $this->sheetService->getCertificateRange()
            );
        }

        return $this->getSheetSchema(
            $this->sheetService->getStudentResultSheetId(),
            $this->sheetService->getStudentResultRange()
        );
    }

    private function getSheetSchema(string $sheetId, string $range): array
    {
        $data = $this->sheetService->fetchSheet($sheetId, $range);
        $headers = $data['headers'] ?? [];

        $columns = [];
        foreach ($headers as $idx => $header) {
            $columns[] = [
                'COLUMN_NAME' => $header,
                'DATA_TYPE' => 'string',
                'IS_NULLABLE' => 'YES',
                'ORDINAL_POSITION' => $idx + 1
            ];
        }
        return $columns;
    }

    public function parseFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            if (in_array($extension, ['xlsx', 'xls'])) {
                return $this->parseExcel($filePath);
            } elseif ($extension === 'csv') {
                return $this->parseCsv($filePath);
            } else {
                throw new ValidationException("Unsupported file type: {$extension}");
            }
        } catch (\Exception $e) {
            Logger::error('File parsing failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new ValidationException("Failed to parse file: " . $e->getMessage());
        }
    }

    private function parseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $data = [];

        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headers[] = $cell->getValue();
            }
        }

        $rowIterator = $worksheet->getRowIterator(2);
        foreach ($rowIterator as $row) {
            $rowData = [];
            $isEmpty = true;

            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                $rowData[] = $value;
                if ($value !== null && $value !== '') {
                    $isEmpty = false;
                }
            }

            if (!$isEmpty) {
                $data[] = $rowData;
            }
        }

        return [
            'headers' => $headers,
            'data' => $data,
            'total_rows' => count($data)
        ];
    }

    private function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new ValidationException("Cannot open CSV file");
        }

        $headers = fgetcsv($handle);
        $data = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (array_filter($row) !== []) {
                $data[] = $row;
            }
        }

        fclose($handle);

        return [
            'headers' => $headers ?: [],
            'data' => $data,
            'total_rows' => count($data)
        ];
    }

    public function performUpload(
        string $table,
        string $method,
        array $mapping,
        array $data
    ): array {
        if (!isset($this->allowedTables[$table])) {
            throw new ValidationException("Table not allowed for upload: {$table}");
        }

        $schema = $this->getTableSchema($table);
        $schemaColumns = array_column($schema, 'COLUMN_NAME');

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        if ($table === 'itgk_certificate') {
            $sheetId = $this->sheetService->getCertificateSheetId();
            $tab = $this->sheetService->getCertificateTab();
        } else {
            $sheetId = $this->sheetService->getStudentResultSheetId();
            $tab = $this->sheetService->getStudentResultTab();
        }

        foreach ($data as $row) {
            $mappedRow = [];
            foreach ($schemaColumns as $h) {
                $mappedRow[] = $row[$h] ?? '';
            }
            $this->sheetService->appendRow($sheetId, $tab, [$mappedRow]);
            $stats['inserted']++;
        }

        Logger::info('Upload completed to Google Sheets', [
            'table' => $table,
            'inserted' => $stats['inserted']
        ]);

        return $stats;
    }

    public function saveTemplate(string $name, string $table, array $mapping): int
    {
        Logger::info('Template save requested', ['name' => $name, 'table' => $table]);
        return time();
    }

    public function getTemplates(): array
    {
        return [];
    }

    public function deleteTemplate(int $id): bool
    {
        return true;
    }

    public function parseFileWithName(string $filePath, string $fileName): array
    {
        $result = $this->parseFile($filePath);

        $headers = $result['headers'];
        $rows = [];

        foreach ($result['data'] as $row) {
            $rowData = [];
            foreach ($headers as $idx => $header) {
                $rowData[$header] = $row[$idx] ?? '';
            }
            $rows[] = $rowData;
        }

        return [
            'headers' => $headers,
            'rows' => $rows
        ];
    }

    public function fetchGoogleSheet(string $spreadsheetId, string $range): array
    {
        return $this->sheetService->fetchParsedSheet($spreadsheetId, $range);
    }

    public function uploadToTable(string $table, array $data, array $mapping, string $method): array
    {
        return $this->performUpload($table, $method, $mapping, $data);
    }
}