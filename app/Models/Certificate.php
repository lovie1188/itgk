<?php

/**
 * Certificate Model - ITGK Certificate Management
 * 
 * All data operations read/write from Google Sheets only.
 * No MySQL/PDO dependency for certificate data.
 * 
 * @package App\Models
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Logger;
use App\Services\GoogleSheetService;

class Certificate
{
    private GoogleSheetService $sheetService;
    private string $sheetId;
    private string $sheetTab;
    private string $sheetRange;

    public function __construct()
    {
        $this->sheetService = new GoogleSheetService();
        $this->sheetId = $this->sheetService->getCertificateSheetId();
        $this->sheetTab = $this->sheetService->getCertificateTab();
        $this->sheetRange = $this->sheetService->getCertificateRange();
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

    public function createWithLearners(array $data): int
    {
        $headers = $this->sheetService->fetchSheet($this->sheetId, $this->sheetRange)['headers'] ?? [];
        $newRow = [];
        foreach ($headers as $h) {
            $newRow[] = $data[$h] ?? '';
        }
        $this->sheetService->appendRow($this->sheetId, $this->sheetTab, [$newRow]);
        return count($this->getAllRawRows());
    }

    public function create(array $data): int
    {
        return $this->createWithLearners($data);
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

    public function updateTracking(array $data): bool
    {
        $certId = trim((string)($data['certificate_id'] ?? ''));
        if (empty($certId)) {
            return false;
        }
        $rows = $this->getAllRawRows();
        $headers = $this->sheetService->fetchSheet($this->sheetId, $this->sheetRange)['headers'] ?? [];
        foreach ($rows as $idx => $row) {
            $sheetRowNum = $idx + 1;
            if (isset($row['S. No.']) && trim((string)$row['S. No.']) === $certId) {
                $updateData = [];
                foreach ($headers as $ci => $h) {
                    $updateData[$ci] = $row[$h] ?? '';
                }
                $status = $data['status'] ?? 'Available';
                $location = $data['current_location'] ?? '';
                $remark = $data['remark'] ?? '';
                if (isset($headers['STATUS'])) $updateData[array_search('STATUS', $headers)] = $status;
                if (isset($headers['Current Location'])) $updateData[array_search('Current Location', $headers)] = $location;
                if (isset($headers['Remark'])) $updateData[array_search('Remark', $headers)] = $remark;
                $startCol = $this->sheetService->colIndexToLetter(0);
                $endCol = $this->sheetService->colIndexToLetter(count($headers) - 1);
                $range = "{$this->sheetTab}!{$startCol}{$sheetRowNum}:{$endCol}{$sheetRowNum}";
                $this->sheetService->updateSheetRow($this->sheetId, $range, array_values($updateData));
                return true;
            }
        }
        return false;
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
        $statusIdx = array_search('STATUS', $headers);
        if ($statusIdx !== false) {
            $updateRow[$statusIdx] = 'Deleted';
        }
        $startCol = $this->sheetService->colIndexToLetter(0);
        $endCol = $this->sheetService->colIndexToLetter(count($headers) - 1);
        $range = "{$this->sheetTab}!{$startCol}{$id}:{$endCol}{$id}";
        $this->sheetService->updateSheetRow($this->sheetId, $range, array_values($updateRow));
        Logger::info('Certificate soft-deleted (marked as Deleted)', ['id' => $id]);
        return true;
    }

    public function getAll(int $limit = 100, int $offset = 0, ?string $status = null): array
    {
        $rows = $this->getAllRawRows();
        if ($status) {
            $filtered = [];
            foreach ($rows as $r) {
                if (isset($r['STATUS']) && strcasecmp((string)$r['STATUS'], $status) === 0) {
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
            $rows = array_filter($rows, fn($r) => isset($r['STATUS']) && strcasecmp((string)$r['STATUS'], $status) === 0);
        }
        return count($rows);
    }

    public function getAnalytics(): array
    {
        $rows = $this->getAllRawRows();
        return [
            'total' => count($rows),
            'available' => count(array_filter($rows, fn($r) => isset($r['STATUS']) && strcasecmp((string)($r['STATUS']), 'Available') === 0)),
            'issued' => count(array_filter($rows, fn($r) => isset($r['STATUS']) && (strcasecmp((string)($r['STATUS']), 'Issued') === 0 || strcasecmp((string)($r['STATUS']), 'ISSUED') === 0))),
            'intransit' => count(array_filter($rows, fn($r) => isset($r['STATUS']) && str_contains(strtolower((string)($r['STATUS'])), 'transit'))),
            'not_received' => count(array_filter($rows, fn($r) => isset($r['STATUS']) && str_contains(strtolower((string)($r['STATUS'])), 'not'))),
        ];
    }

    public function getMonthlyStats(int $months = 6): array
    {
        return [];
    }

    public function consolidateFromLearners(): array
    {
        return ['success' => true, 'stats' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0]];
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

    public function issueBatch(int $certificateId, array $receiverData): bool
    {
        $rows = $this->getAllRawRows();
        $headers = $this->sheetService->fetchSheet($this->sheetId, $this->sheetRange)['headers'] ?? [];
        foreach ($rows as $idx => $row) {
            $sheetRowNum = $idx + 1;
            if (isset($row['S. No.']) && trim((string)$row['S. No.']) === (string)$certificateId) {
                $updateData = [];
                foreach ($headers as $ci => $h) {
                    $updateData[$ci] = $row[$h] ?? '';
                }
                $idxQ = array_search('STATUS', $headers);
                $idxR = array_search('Remark', $headers);
                $idxS = array_search('Receiver Name', $headers);
                $idxT = array_search('Receiver Designation', $headers);
                $idxU = array_search('Receiver Mobile Number', $headers);
                $idxV = array_search('Image', $headers);
                if ($idxQ !== false) $updateData[$idxQ] = 'Issued';
                if ($idxR !== false) $updateData[$idxR] = $receiverData['remark'] ?? '';
                if ($idxS !== false) $updateData[$idxS] = $receiverData['name'] ?? '';
                if ($idxT !== false) $updateData[$idxT] = $receiverData['designation'] ?? '';
                if ($idxU !== false) $updateData[$idxU] = $receiverData['mobile'] ?? '';
                if ($idxV !== false) $updateData[$idxV] = "Issued by: {$receiverData['name']} on " . date('d/m/Y H:i');
                $startCol = $this->sheetService->colIndexToLetter(0);
                $endCol = $this->sheetService->colIndexToLetter(count($headers) - 1);
                $range = "{$this->sheetTab}!{$startCol}{$sheetRowNum}:{$endCol}{$sheetRowNum}";
                $this->sheetService->updateSheetRow($this->sheetId, $range, array_values($updateData));
                return true;
            }
        }
        return false;
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
}
