<?php

/**
 * LearnerResult Model - ITGK Learner Result Management
 * 
 * All data operations read/write from Google Sheets only.
 * No MySQL/PDO dependency for learner data.
 * 
 * @package App\Models
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Logger;
use App\Services\GoogleSheetService;

class LearnerResult
{
    private GoogleSheetService $sheetService;
    private string $sheetId;
    private string $sheetTab;
    private string $sheetRange;

    public function __construct()
    {
        $this->sheetService = new GoogleSheetService();
        $this->sheetId = $this->sheetService->getStudentResultSheetId();
        $this->sheetTab = $this->sheetService->getStudentResultTab();
        $this->sheetRange = $this->sheetService->getStudentResultRange();
    }

    private function getAllRawRows(): array
    {
        $data = $this->sheetService->fetchParsedSheet($this->sheetId, $this->sheetRange);
        return $data['rows'] ?? [];
    }

    public function find(int $id): ?array
    {
        $rows = $this->getAllRawRows();
        return $rows[$id - 1] ?? null;
    }

    public function create(array $data): int
    {
        $headers = $this->sheetService->fetchSheet($this->sheetId, $this->sheetRange)['headers'] ?? [];
        $newRow = [];
        foreach ($headers as $h) {
            $newRow[] = $data[$h] ?? '';
        }
        $this->sheetService->appendRow($this->sheetId, $this->sheetTab, [$newRow]);
        return count($this->getAllRawRows());
    }

    public function update(int $id, array $data): bool
    {
        $rows = $this->getAllRawRows();
        if (!isset($rows[$id - 1])) {
            return false;
        }
        $headers = $this->sheetService->fetchSheet($this->sheetId, $this->sheetRange)['headers'] ?? [];
        $updatedRow = [];
        foreach ($headers as $colIndex => $header) {
            $updatedRow[$colIndex] = $data[$header] ?? $rows[$id - 1][$colIndex] ?? '';
        }
        $startCol = $this->sheetService->colIndexToLetter(0);
        $endCol = $this->sheetService->colIndexToLetter(count($headers) - 1);
        $range = "{$this->sheetTab}!{$startCol}{$id}:{$endCol}{$id}";
        $this->sheetService->updateSheetRow($this->sheetId, $range, array_values($updatedRow));
        return true;
    }

    public function delete(int $id): bool
    {
        $rows = $this->getAllRawRows();
        if (!isset($rows[$id - 1])) {
            return false;
        }
        $headers = $this->sheetService->fetchSheet($this->sheetId, $this->sheetRange)['headers'] ?? [];
        $updateRow = [];
        foreach ($headers as $colIndex => $header) {
            $updateRow[$colIndex] = $rows[$id - 1][$header] ?? '';
        }
        $statusIdx = array_search('Status', $headers);
        if ($statusIdx !== false) {
            $updateRow[$statusIdx] = 'Deleted';
        }
        $startCol = $this->sheetService->colIndexToLetter(0);
        $endCol = $this->sheetService->colIndexToLetter(count($headers) - 1);
        $range = "{$this->sheetTab}!{$startCol}{$id}:{$endCol}{$id}";
        $this->sheetService->updateSheetRow($this->sheetId, $range, array_values($updateRow));
        Logger::info('Learner soft-deleted (marked as Deleted)', ['id' => $id]);
        return true;
    }

    public function getAll(int $limit = 100, int $offset = 0, ?string $status = null): array
    {
        $rows = $this->getAllRawRows();
        if ($status) {
            $filtered = [];
            foreach ($rows as $r) {
                $rowStatus = $r['Status'] ?? $r['status'] ?? '';
                if (strcasecmp((string)$rowStatus, $status) === 0) {
                    $filtered[] = $r;
                }
            }
            $rows = $filtered;
        }
        return array_slice($rows, $offset, $limit);
    }

    public function count(?string $status = null): int
    {
        $rows = $this->getAllRawRows();
        if ($status) {
            $rows = array_filter($rows, fn($r) => strcasecmp((string)($r['Status'] ?? $r['status'] ?? ''), $status) === 0);
        }
        return count($rows);
    }

    public function countByResult(string $result): int
    {
        $rows = $this->getAllRawRows();
        return count(array_filter($rows, fn($r) => strcasecmp((string)($r['Result'] ?? $r['result'] ?? ''), $result) === 0));
    }

    public function getAnalytics(): array
    {
        $rows = $this->getAllRawRows();
        return [
            'total' => count($rows),
            'pass' => count(array_filter($rows, fn($r) => strcasecmp((string)($r['Result'] ?? $r['result'] ?? ''), 'PASS') === 0)),
            'fail' => count(array_filter($rows, fn($r) => strcasecmp((string)($r['Result'] ?? $r['result'] ?? ''), 'FAIL') === 0)),
            'absent' => count(array_filter($rows, fn($r) => strcasecmp((string)($r['Result'] ?? $r['result'] ?? ''), 'ABSENT') === 0)),
        ];
    }

    public function getMonthlyStats(int $months = 6): array
    {
        return [];
    }

    public function issueIndividual(int $id, array $receiverData): bool
    {
        $rows = $this->getAllRawRows();
        if (!isset($rows[$id - 1])) {
            return false;
        }
        $headers = $this->sheetService->fetchSheet($this->sheetId, $this->sheetRange)['headers'] ?? [];
        $updatedRow = [];
        foreach ($headers as $colIndex => $header) {
            $updatedRow[$colIndex] = $rows[$id - 1][$header] ?? '';
        }
        $statusIdx = array_search('Status', $headers);
        $remarkIdx = array_search('Remark', $headers);
        if ($statusIdx !== false) $updatedRow[$statusIdx] = 'Issued';
        if ($remarkIdx !== false) $updatedRow[$remarkIdx] = $receiverData['remark'] ?? '';
        $startCol = $this->sheetService->colIndexToLetter(0);
        $endCol = $this->sheetService->colIndexToLetter(count($headers) - 1);
        $range = "{$this->sheetTab}!{$startCol}{$id}:{$endCol}{$id}";
        $this->sheetService->updateSheetRow($this->sheetId, $range, array_values($updatedRow));
        Logger::info('Individual learner certificate issued', ['learner_id' => $id]);
        return true;
    }

    public function deleteMany(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->delete((int)$id)) {
                $count++;
            }
        }
        return $count;
    }

    public function search(string $query, int $limit = 50): array
    {
        $rows = $this->getAllRawRows();
        $results = [];
        foreach ($rows as $r) {
            $haystack = json_encode($r);
            if (stripos($haystack, $query) !== false) {
                $results[] = $r;
                if (count($results) >= $limit) {
                    break;
                }
            }
        }
        return $results;
    }

    public function getByItgkCode(string $itgkCode, int $limit = 100): array
    {
        $rows = $this->getAllRawRows();
        $filtered = array_filter($rows, fn($r) => strcasecmp((string)($r['ITGK Code'] ?? $r['ITGK CODE'] ?? ''), $itgkCode) === 0);
        return array_slice(array_values($filtered), 0, $limit);
    }

    public function getByExam(string $examName, ?string $courseName = null): array
    {
        $rows = $this->getAllRawRows();
        $filtered = array_filter($rows, fn($r) => strcasecmp((string)($r['Exam Name'] ?? $r['exam_name on certificate'] ?? $r['BATCH'] ?? ''), $examName) === 0);
        if ($courseName) {
            $filtered = array_filter($filtered, fn($r) => strcasecmp((string)($r['Course Name'] ?? ''), $courseName) === 0);
        }
        return array_values($filtered);
    }

    public function getDistinctItgkCodes(): array
    {
        $rows = $this->getAllRawRows();
        $codes = [];
        foreach ($rows as $r) {
            $code = trim((string)($r['ITGK Code'] ?? $r['ITGK CODE'] ?? ''));
            if ($code !== '' && !in_array($code, $codes)) {
                $codes[] = $code;
            }
        }
        return $codes;
    }

    public function getDistinctExams(): array
    {
        $rows = $this->getAllRawRows();
        $exams = [];
        foreach ($rows as $r) {
            $exam = trim((string)($r['Exam Name'] ?? $r['exam_name on certificate'] ?? $r['BATCH'] ?? ''));
            if ($exam !== '' && !in_array($exam, $exams)) {
                $exams[] = $exam;
            }
        }
        return $exams;
    }
}
