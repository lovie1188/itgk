<?php

/**
 * GoogleSheetService - Direct Standalone Google Sheets Integration Service
 *
 * Fetches full sheet via gviz CSV export (no range restriction in URL),
 * then slices data based on configured startRow for reliability.
 *
 * @package App\Services
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Exceptions\ValidationException;

class GoogleSheetService
{
    private array $presetSheets = [
        'itgk_master' => [
            'id' => '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg',
            'sheet' => 'ITGK'
        ],
        'itgk_2026' => [
            'id' => '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg',
            'sheet' => 'ITGK_2026'
        ],
        'itgk_admissions' => [
            'id' => '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg',
            'sheet' => 'ADMISSIONS'
        ],
        'itgk_certificate' => [
            'id' => '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4',
            'sheet' => 'Certificate'
        ],
        'student_result' => [
            'id' => '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4',
            'sheet' => 'Student_Result'
        ],
        'certificate_tracker' => [
            'id' => '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4',
            'sheet' => 'Dispach_register'
        ],
        'books_management' => [
            'id' => '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4',
            'sheet' => 'Book_Issue'
        ]
    ];

    // ----------------------------------------------------------------
    // Dataset Getters (read from .env)
    // ----------------------------------------------------------------

    public function getCertificateSheetId(): string
    {
        return getenv('GSHEET_CERTIFICATE_ID') ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4';
    }

    public function getCertificateTab(): string
    {
        return getenv('GSHEET_CERTIFICATE_TAB') ?: 'Certificate';
    }

    public function getCertificateRange(): string
    {
        return getenv('GSHEET_CERTIFICATE_RANGE') ?: 'Certificate!A1:Z';
    }

    public function getStudentResultSheetId(): string
    {
        return getenv('GSHEET_STUDENT_RESULT_ID') ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4';
    }

    public function getStudentResultTab(): string
    {
        return getenv('GSHEET_STUDENT_RESULT_TAB') ?: 'Student_Result';
    }

    public function getStudentResultRange(): string
    {
        return getenv('GSHEET_STUDENT_RESULT_RANGE') ?: 'Student_Result!A1:Z';
    }

    public function getItgkMasterSheetId(): string
    {
        return getenv('GSHEET_ITGK_MASTER_ID') ?: '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg';
    }

    public function getItgkMasterTab(): string
    {
        return getenv('GSHEET_ITGK_MASTER_TAB') ?: 'ITGK';
    }

    public function getItgkMasterRange(): string
    {
        return getenv('GSHEET_ITGK_MASTER_RANGE') ?: 'ITGK!A1:R131';
    }

    public function getItgk2026SheetId(): string
    {
        return getenv('GSHEET_ITGK_2026_ID') ?: '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg';
    }

    public function getItgk2026Tab(): string
    {
        return getenv('GSHEET_ITGK_2026_TAB') ?: 'ITGK_2026';
    }

    public function getItgk2026Range(): string
    {
        return getenv('GSHEET_ITGK_2026_RANGE') ?: 'ITGK_2026!A1:Z';
    }

    // ----------------------------------------------------------------
    // ITGK Admissions helpers
    // ----------------------------------------------------------------

    public function getAdmissionsSheetId(): string
    {
        return getenv('GSHEET_ITGK_ADMISSIONS_ID') ?: '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg';
    }

    public function getAdmissionsTab(): string
    {
        return getenv('GSHEET_ITGK_ADMISSIONS_TAB') ?: 'ADMISSIONS';
    }

    public function getAdmissionsRange(): string
    {
        return getenv('GSHEET_ITGK_ADMISSIONS_RANGE') ?: 'ADMISSIONS!A1:S';
    }

    // ----------------------------------------------------------------
    // Certificate Tracker (Dispatch Register) helpers
    // ----------------------------------------------------------------

    public function getCertTrackerSheetId(): string
    {
        return getenv('GSHEET_CERT_TRACKER_ID') ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4';
    }

    public function getCertTrackerTab(): string
    {
        return getenv('GSHEET_CERT_TRACKER_TAB') ?: 'Dispach_register';
    }

    public function getCertTrackerRange(): string
    {
        return getenv('GSHEET_CERT_TRACKER_RANGE') ?: 'Dispach_register!A1:Z500';
    }

    // ----------------------------------------------------------------
    // Books Management helpers
    // ----------------------------------------------------------------

    public function getBooksSheetId(): string
    {
        return getenv('GSHEET_BOOKS_ID') ?: '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg';
    }

    public function getBooksTab(): string
    {
        return getenv('GSHEET_BOOKS_TAB') ?: 'new_book_issue';
    }

    public function getBooksRange(): string
    {
        return getenv('GSHEET_BOOKS_RANGE') ?: 'new_book_issue!A1:X';
    }

    // ----------------------------------------------------------------
    // Core Fetch — always fetches the FULL sheet tab (no cell range
    // restriction in the URL for reliability). Slicing by startRow is
    // done in fetchParsedSheet().
    // ----------------------------------------------------------------

    /**
     * Fetch all raw rows of a sheet tab as a 2D array.
     *
     * @param string $spreadsheetId Spreadsheet ID or preset key
     * @param string $range  Used only to extract the sheet/tab name. Cell range is ignored in the URL.
     * @return array 2D array — row 0 is always the header row of the sheet.
     * @throws ValidationException
     */
    public function fetchSheet(string $spreadsheetId, string $range = ''): array
    {
        $sheetName = 'Certificate';

        // Resolve preset keys
        $key = strtolower(trim($spreadsheetId));
        if ($key === 'itgk' || $key === 'itgk_certificate') {
            $spreadsheetId = $this->getCertificateSheetId();
            if (empty($range) || $range === 'Sheet1!A1:Z') {
                $range = $this->getCertificateRange();
            }
        } elseif ($key === 'student_result' || $key === 'learners') {
            $spreadsheetId = $this->getStudentResultSheetId();
            if (empty($range) || $range === 'Sheet1!A1:Z') {
                $range = $this->getStudentResultRange();
            }
        } elseif ($key === 'itgk_master') {
            $spreadsheetId = $this->getItgkMasterSheetId();
            if (empty($range) || $range === 'Sheet1!A1:Z') {
                $range = $this->getItgkMasterRange();
            }
        } elseif ($key === 'books_management' || $key === 'books') {
            $spreadsheetId = $this->getBooksSheetId();
            if (empty($range) || $range === 'Sheet1!A1:Z') {
                $range = $this->getBooksRange();
            }
        } elseif (isset($this->presetSheets[$key])) {
            $spreadsheetId = $this->presetSheets[$key]['id'];
            $sheetName = $this->presetSheets[$key]['sheet'];
        }

        // Extract sheet/tab name from range string (e.g. "Certificate!A2290:V" → "Certificate")
        if (strpos($range, '!') !== false) {
            [$sheetName] = explode('!', $range, 2);
        } elseif (!empty($range) && !preg_match('/^[A-Z]+\d+/i', $range)) {
            // Range is just a tab name (no column notation)
            $sheetName = $range;
        }

        // gviz CSV export — NO cell range in URL to ensure full sheet is returned
        // Cell-range restriction is applied in PHP after fetching (more reliable)
        $csvUrl = sprintf(
            'https://docs.google.com/spreadsheets/d/%s/gviz/tq?tqx=out:csv&sheet=%s',
            urlencode($spreadsheetId),
            urlencode($sheetName)
        );

        // ── Data Caching Layer (120 seconds TTL for fast responses) ────────
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $cacheKey = 'gsheet_' . md5($spreadsheetId . '_' . $sheetName) . '.cache';
        $cacheFile = $cacheDir . '/' . $cacheKey;
        $cacheTTL = (int)(getenv('GSHEET_CACHE_TTL') ?: 120);

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            $cachedCsv = @file_get_contents($cacheFile);
            if (!empty($cachedCsv)) {
                Logger::info('Serving Google Sheet from local cache', [
                    'spreadsheet_id' => $spreadsheetId,
                    'sheet_name' => $sheetName,
                    'age_sec' => (time() - filemtime($cacheFile)),
                ]);
                return $this->parseCsvData($cachedCsv);
            }
        }

        Logger::info('Fetching Google Sheet from live API', [
            'spreadsheet_id' => $spreadsheetId,
            'sheet_name' => $sheetName,
            'url' => $csvUrl,
        ]);

        $ch = curl_init($csvUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SoftSam/1.0',
        ]);

        $csvData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('Google Sheet cURL failed', ['error' => $error, 'url' => $csvUrl]);
            throw new ValidationException('Network error fetching Google Sheet: ' . $error);
        }

        if ($httpCode !== 200 || empty($csvData)) {
            Logger::error('Google Sheet fetch returned non-200', ['http_code' => $httpCode, 'url' => $csvUrl]);
            throw new ValidationException("Failed to fetch Google Sheet (HTTP {$httpCode}). Ensure sheet is shared publicly with 'Anyone with the link'.");
        }

        // Save fresh data to local cache
        @file_put_contents($cacheFile, $csvData);

        return $this->parseCsvData($csvData);
    }

    /**
     * Clear all cached Google Sheet files (called upon write/edit/issue operations)
     */
    public function clearCache(): void
    {
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/gsheet_*.cache');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
            Logger::info('Google Sheet cache cleared completely');
        }
    }

    // ----------------------------------------------------------------
    // Parsed Fetch — returns associative rows with correct headers.
    //
    // Range config (e.g. "Certificate!A2290:V") tells us:
    //   - Which tab to fetch (Certificate)
    //   - From which row to start returning DATA (row 2290 of the sheet)
    //
    // Sheet structure assumed:
    //   Row 1 = column headers  →  rawRows[0]
    //   Row 2 = first data row  →  rawRows[1]
    //   ...
    //   Row N = rawRows[N-1]
    //
    // So for startRow = 2290:
    //   dataStartIndex = 2290 - 1 = 2289  (rawRows[2289] = sheet row 2290)
    // ----------------------------------------------------------------

    /**
     * Fetch sheet and return associative rows starting from the configured startRow.
     *
     * @param string $spreadsheetId Spreadsheet ID or preset key
     * @param string $range  e.g. 'Certificate!A2290:V' or 'Student_Result!A1:Z'
     * @return array ['headers' => [...], 'rows' => [...], 'startRow' => int, 'totalRaw' => int]
     */
    public function fetchParsedSheet(string $spreadsheetId = 'itgk', string $range = ''): array
    {
        // Resolve default range for certificate preset key
        $key = strtolower(trim($spreadsheetId));
        if (($key === 'itgk' || $key === 'itgk_certificate' || empty($spreadsheetId)) && (empty($range) || $range === 'Sheet1!A1:Z')) {
            $range = $this->getCertificateRange();
        }

        // Parse startRow from cell range (e.g. "A2290:V" → 2290, "A1:Z" → 1)
        $startRow = 1;
        $cellRange = $range;
        if (strpos($range, '!') !== false) {
            [, $cellRange] = explode('!', $range, 2);
        }
        if (preg_match('/[A-Z]+(\d+)/i', $cellRange, $m)) {
            $startRow = (int) $m[1];
        }

        // Fetch full sheet tab (row 0 = header, row 1..N-1 = data from row 2 of sheet)
        $rawRows = $this->fetchSheet($spreadsheetId, $range);

        if (empty($rawRows)) {
            return ['headers' => [], 'rows' => [], 'startRow' => $startRow, 'totalRaw' => 0];
        }

        // Row 0 is ALWAYS the header row (sheet row 1)
        $headers = array_map('trim', $rawRows[0]);

        // dataStartIndex: which rawRows index corresponds to the configured startRow
        // sheet row 1 = rawRows[0] (header, always skipped)
        // sheet row 2 = rawRows[1] (first possible data row)
        // sheet row startRow = rawRows[startRow - 1]
        // So: dataStartIndex = max(1, startRow - 1)
        $dataStartIndex = max(1, $startRow - 1);

        $rows = [];
        for ($i = $dataStartIndex; $i < count($rawRows); $i++) {
            $rawRow = $rawRows[$i];
            if (empty(array_filter($rawRow, fn($v) => trim($v) !== ''))) {
                continue; // skip blank rows
            }
            $row = [];
            foreach ($headers as $col => $header) {
                if ($header === '')
                    continue;
                $row[$header] = isset($rawRow[$col]) ? trim($rawRow[$col]) : '';
            }
            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'startRow' => $startRow,
            'totalRaw' => count($rawRows),
        ];
    }

    /**
     * Parse CSV string into 2D array, handling quoted fields and embedded newlines.
     */
    private function parseCsvData(string $csvData): array
    {
        $lines = str_getcsv($csvData, "\n");
        $parsed = [];
        foreach ($lines as $line) {
            if (trim($line) === '')
                continue;
            $parsed[] = str_getcsv($line);
        }
        return $parsed;
    }

    public function getPresets(): array
    {
        return array_keys($this->presetSheets);
    }

    // ================================================================
    // WRITE — Update a row in Google Sheet via Sheets API v4
    // Uses Service Account JWT — no OAuth user flow needed.
    // ================================================================

    /**
     * Get the path to service account JSON (from .env or default)
     */
    private function getServiceAccountPath(): string
    {
        $rel = getenv('GSHEET_SERVICE_ACCOUNT_JSON') ?: '';
        $base = dirname(__DIR__, 2);
        return $rel ? $base . '/' . ltrim($rel, '/\\') : '';
    }

    /**
     * Build a signed JWT and exchange it for a Google OAuth2 access token.
     * Uses only PHP built-ins — no external library needed.
     */
    public function getAccessToken(): string
    {
        $path = $this->getServiceAccountPath();
        if (!$path || !file_exists($path)) {
            throw new \RuntimeException('Service account JSON not found: ' . $path);
        }

        $sa = json_decode(file_get_contents($path), true);
        if (empty($sa['private_key']) || empty($sa['client_email'])) {
            throw new \RuntimeException('Invalid service account JSON');
        }

        // ── Clock-skew fix ──────────────────────────────────────────────
        // Local PHP time() may be behind real UTC (Windows clock drift).
        // Fetch current Unix timestamp from Google's own token endpoint
        // (HEAD request returns "Date:" header) so iat/exp are always valid.
        $now = $this->getGoogleServerTime();
        // ────────────────────────────────────────────────────────────────

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim  = base64_encode(json_encode([
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now - 10,      // 10-second leeway for minor drift
            'exp'   => $now + 3590,    // just under 1 hour
        ]));

        // URL-safe base64
        $header = rtrim(strtr($header, '+/', '-_'), '=');
        $claim  = rtrim(strtr($claim,  '+/', '-_'), '=');

        $toSign = $header . '.' . $claim;
        openssl_sign($toSign, $signature, $sa['private_key'], 'SHA256');
        $sig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $jwt = $toSign . '.' . $sig;

        // Exchange JWT for access token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT    => 20,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error getting access token: ' . $err);
        }

        $data = json_decode($resp, true);
        if (empty($data['access_token'])) {
            throw new \RuntimeException('Failed to get access token: ' . ($data['error_description'] ?? $resp));
        }

        return $data['access_token'];
    }

    /**
     * Get current UTC Unix timestamp from Google's token endpoint Date header.
     * This compensates for Windows / XAMPP clock drift without requiring NTP.
     * Falls back to local time() if the request fails.
     */
    private function getGoogleServerTime(): int
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,   // HEAD-like: only headers
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if (!$err && $resp) {
            // Parse "Date: Thu, 31 Jul 2026 03:30:00 GMT" header
            if (preg_match('/^Date:\s*(.+)$/mi', $resp, $m)) {
                $ts = strtotime(trim($m[1]));
                if ($ts && $ts > 0) {
                    Logger::info('JWT clock-sync: using Google server time', [
                        'google_utc' => gmdate('Y-m-d H:i:s', $ts),
                        'local_utc'  => gmdate('Y-m-d H:i:s'),
                        'drift_sec'  => ($ts - time()),
                    ]);
                    return $ts;
                }
            }
        }

        // Fallback to local time (will work if clock is in sync)
        Logger::warn('JWT clock-sync: could not fetch Google server time, using local time()', []);
        return time();
    }

    /**
     * Fetch a single raw row from Google Sheet via Sheets API v4.
     * Returns flat array of cell values.
     *
     * @param string $sheetId  Spreadsheet ID
     * @param string $range    e.g. "Certificate!A2290:V2290"
     * @return array           Flat array of cell values
     */
    public function fetchRawRow(string $sheetId, string $range): array
    {
        $token = $this->getAccessToken();

        // Encode only the tab name; keep "!A1:Z1" cell-range notation literal for Sheets API.
        $rangeForUrl = $range;
        if (strpos($range, '!') !== false) {
            [$tabPart, $cellPart] = explode('!', $range, 2);
            $rangeForUrl = urlencode($tabPart) . '!' . $cellPart;
        } else {
            $rangeForUrl = urlencode($range);
        }
        $url = 'https://sheets.googleapis.com/v4/spreadsheets/'
            . urlencode($sheetId)
            . '/values/'
            . $rangeForUrl;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error fetching row: ' . $err);
        }
        if ($status < 200 || $status >= 300) {
            $msg = json_decode($resp, true)['error']['message'] ?? $resp;
            throw new \RuntimeException("Sheets API error ({$status}): {$msg}");
        }

        $data = json_decode($resp, true);
        return $data['values'][0] ?? [];
    }

    /**
     * Update specific cells in a Google Sheet row using Sheets API v4.
     *
     * @param string $sheetId    Spreadsheet ID
     * @param string $range      A1 notation e.g. "Certificate!A2290:V2290"
     * @param array  $rowValues  Flat array of cell values in column order
     */
    public function updateSheetRow(string $sheetId, string $range, array $rowValues): void
    {
        $token = $this->getAccessToken();

        // Encode only the tab name portion; keep the "!A1:Z1" cell range literal.
        $rangeForUrl = $range;
        if (strpos($range, '!') !== false) {
            [$tabPart, $cellPart] = explode('!', $range, 2);
            $rangeForUrl = urlencode($tabPart) . '!' . $cellPart;
        } else {
            $rangeForUrl = urlencode($range);
        }

        $url = 'https://sheets.googleapis.com/v4/spreadsheets/'
            . urlencode($sheetId)
            . '/values/'
            . $rangeForUrl
            . '?valueInputOption=USER_ENTERED';

        $body = json_encode([
            'range' => $range,
            'majorDimension' => 'ROWS',
            'values' => [$rowValues],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error updating sheet: ' . $err);
        }
        if ($status < 200 || $status >= 300) {
            $msg = json_decode($resp, true)['error']['message'] ?? $resp;
            throw new \RuntimeException("Sheets API error ({$status}): {$msg}");
        }

        $this->clearCache();
    }

    /**
     * Batch-update multiple ranges in a single Sheets API call.
     * Much faster than calling updateSheetRow() N times.
     *
     * @param string $sheetId  Spreadsheet ID
     * @param array  $updates  Array of ['range' => string, 'values' => array] pairs
     *                         e.g. [['range' => 'Certificate!A2:V2', 'values' => [[...]]]]
     */
    public function batchUpdateRows(string $sheetId, array $updates): void
    {
        if (empty($updates))
            return;

        $token = $this->getAccessToken();

        $url = 'https://sheets.googleapis.com/v4/spreadsheets/'
            . urlencode($sheetId)
            . '/values:batchUpdate';

        $data = array_map(fn($u) => [
            'range' => $u['range'],
            'majorDimension' => 'ROWS',
            'values' => $u['values'],
        ], $updates);

        $body = json_encode([
            'valueInputOption' => 'USER_ENTERED',
            'data' => $data,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error in batchUpdate: ' . $err);
        }
        if ($status < 200 || $status >= 300) {
            $msg = json_decode($resp, true)['error']['message'] ?? $resp;
            throw new \RuntimeException("Sheets API batchUpdate error ({$status}): {$msg}");
        }

        $this->clearCache();
    }

    /**
     * Append one or more rows to the end of a sheet tab.
     *
     * @param string $sheetId    Spreadsheet ID
     * @param string $tab        Sheet/tab name
     * @param array  $rows       Array of row arrays (each row is array of cell values)
     * @param string $valueOption Value input option (default RAW)
     */
    public function appendRow(string $sheetId, string $tab, array $rows, string $valueOption = 'RAW'): void
    {
        $token = $this->getAccessToken();

        // Extract clean tab name if a range notation like "Book_Issue!A1:Z" was passed
        $tabName = $tab;
        if (strpos($tab, '!') !== false) {
            [$tabName] = explode('!', $tab, 2);
        }

        // Encode only the tab name; keep "!A:Z" literal so Sheets API can parse the range.
        $url = 'https://sheets.googleapis.com/v4/spreadsheets/'
            . urlencode($sheetId)
            . '/values/'
            . urlencode($tabName) . '!A:Z'
            . ':append?valueInputOption=' . urlencode($valueOption);

        $body = json_encode([
            'majorDimension' => 'ROWS',
            'values' => $rows,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error appending row: ' . $err);
        }
        if ($status < 200 || $status >= 300) {
            $msg = json_decode($resp, true)['error']['message'] ?? $resp;
            throw new \RuntimeException("Sheets API append error ({$status}): {$msg}");
        }

        $this->clearCache();
    }

    /**
     * Clear a single row (set all cells to empty).
     *
     * @param string $sheetId  Spreadsheet ID
     * @param string $tab      Sheet/tab name
     * @param int    $rowNum   1-based row number
     */
    public function clearRow(string $sheetId, string $tab, int $rowNum): void
    {
        $token = $this->getAccessToken();

        $url = 'https://sheets.googleapis.com/v4/spreadsheets/'
            . urlencode($sheetId)
            . '/values/'
            . urlencode($tab) . "!A{$rowNum}:Z{$rowNum}"
            . ':clear';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error clearing row: ' . $err);
        }
        if ($status < 200 || $status >= 300) {
            $msg = json_decode($resp, true)['error']['message'] ?? $resp;
            throw new \RuntimeException("Sheets API clear error ({$status}): {$msg}");
        }
    }

    /**
     * Fetch status options from misc tab Column F
     */
    public function getStatusOptions(): array
    {
        try {
            $sheetId = $this->getCertificateSheetId();
            $rawRows = $this->fetchSheet($sheetId, 'misc');
            if (empty($rawRows)) {
                return ['Available', 'Issued', 'Not Received', 'InTransit'];
            }
            
            $options = [];
            foreach ($rawRows as $row) {
                $val = trim((string)($row[5] ?? ''));
                if ($val !== '') {
                    $parts = preg_split('/\s+/', $val);
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            $options[] = $p;
                        }
                    }
                }
            }
            return array_values(array_unique($options));
        } catch (\Exception $e) {
            return ['Available', 'Issued', 'Not Received', 'InTransit'];
        }
    }

    /**
     * Fetch district options from misc tab Column H
     */
    public function getDistrictOptions(): array
    {
        try {
            $sheetId = $this->getCertificateSheetId();
            $rawRows = $this->fetchSheet($sheetId, 'misc');
            if (empty($rawRows)) {
                return [];
            }
            
            $options = [];
            foreach ($rawRows as $row) {
                $val = trim((string)($row[7] ?? ''));
                if ($val !== '') {
                    $parts = preg_split('/\s+/', $val);
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            $options[] = $p;
                        }
                    }
                }
            }
            return array_values(array_unique($options));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Fetch location options from tab "misc" Column J
     */
    public function getLocationOptions(): array
    {
        try {
            $sheetId = $this->getCertificateSheetId();
            $rawRows = $this->fetchSheet($sheetId, 'misc');
            if (empty($rawRows)) {
                return [];
            }
            
            $options = [];
            foreach ($rawRows as $row) {
                $val = trim((string)($row[9] ?? ''));
                if ($val !== '') {
                    if (strcasecmp($val, 'SP OFFICE OSIAN SP OFFICE JODHPUR Delivered to ITGK') === 0) {
                        $options[] = 'SP OFFICE OSIAN';
                        $options[] = 'SP OFFICE JODHPUR';
                        $options[] = 'Delivered to ITGK';
                    } else {
                        $options[] = $val;
                    }
                }
            }
            return array_values(array_unique($options));
        } catch (\Exception $e) {
            Logger::error('Failed to fetch location options from misc sheet', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch remark options from misc tab Column L
     */
    public function getRemarkOptions(): array
    {
        try {
            $sheetId = $this->getCertificateSheetId();
            $rawRows = $this->fetchSheet($sheetId, 'misc');
            if (empty($rawRows)) {
                return [];
            }
            
            $options = [];
            foreach ($rawRows as $row) {
                $val = trim((string)($row[11] ?? ''));
                if ($val !== '') {
                    $options[] = $val;
                }
            }
            return array_values(array_unique($options));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Convert 0-based column index to Excel-style letter (0=A, 25=Z, 26=AA …)
     */
    public function colIndexToLetter(int $index): string

    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }
        return $letter;
    }
}
