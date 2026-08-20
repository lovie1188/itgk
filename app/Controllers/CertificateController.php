<?php

/**
 * CertificateController - ITGK Certificate Management Controller
 *
 * Data source: Google Sheets only. No DB fallback for display.
 * DB is used for write operations (issue, create) only.
 *
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Certificate;
use App\Helpers\Logger;
use App\Helpers\Csrf;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\GoogleSheetService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class CertificateController extends BaseController
{
    private Certificate $certificateModel;

    public function __construct()
    {
        parent::__construct();
        $this->certificateModel = new Certificate();
    }

    // =========================================================
    // INDEX â€” List certificates from Google Sheet
    // =========================================================
    public function index(): void
    {
        $this->requireAuth();

        $page  = max(1, (int)($_GET['page']  ?? 1));
        $limit = max(10, min(500, (int)($_GET['limit'] ?? 100)));

        $certificates = [];
        $sheetError   = null;

        try {
            $sheetService = new GoogleSheetService();
            $sheetId      = $sheetService->getCertificateSheetId();
            $sheetRange   = $sheetService->getCertificateRange();
            $sheetData    = $sheetService->fetchParsedSheet($sheetId, $sheetRange);
            $rawRows      = $sheetData['rows'] ?? [];
            $sheetStartRow = (int)($sheetData['startRow'] ?? 1);

            foreach ($rawRows as $idx => $r) {
                // Actual row number in the Google Sheet (for direct editing link)
                // sheet row 1 = header, data starts at sheetStartRow
                $actualSheetRow = $sheetStartRow + $idx;
                $certificates[] = [
                    'id'                   => $r['S. No.'] ?? ($idx + 1),
                    'course_name'          => $r['Course Name']  ?? '',
                    'receiving_date'       => $r['DATE']         ?? $r['Receiving Date'] ?? '',
                    'exam_name'            => $r['EXAM']         ?? $r['Exam Name']       ?? '',
                    'exam_date'            => $r['EXAM_DATE_ITGK'] ?? $r['Exam Date']    ?? '',
                    'itgk_code'            => $r['ITGK CODE']   ?? '',
                    'district'             => $r['DISTRICT']    ?? $r['District']         ?? '',
                    'absent'               => (int)($r['ABSENT']      ?? $r['Absent']     ?? 0),
                    'fail'                 => (int)($r['FAIL']        ?? $r['Fail']       ?? 0),
                    'pass'                 => (int)($r['PASS']        ?? $r['Pass']       ?? 0),
                    'ufm'                  => (int)($r['UFM']         ?? $r['Ufm']        ?? 0),
                    'grand_total'          => (int)($r['Grand Total'] ?? $r['grand_total'] ?? 0),
                    'packet_no'            => $r['Packet No.']         ?? $r['Packet No'] ?? '',
                    'cert_no_from'         => $r['Certificate No. From'] ?? $r['Cert No From'] ?? '',
                    'cert_no_to'           => $r['Certificate No. To']   ?? $r['Cert No To']   ?? '',
                    'current_location'     => $r['Current Location'] ?? '',
                    'status'               => $r['STATUS']   ?? $r['Status']   ?? 'Available',
                    'remark'               => $r['Remark']              ?? '',
                    'receiver_name'        => $r['Receiver Name']       ?? '',
                    'receiver_designation' => $r['Receiver Designation'] ?? '',
                    'receiver_mobile'      => $r['Receiver Mobile Number'] ?? '',
                    'issued_by'            => $r['Issued By']           ?? '',
                    'image'                => $r['Image']               ?? '',
                    'sheet_row'            => $actualSheetRow, // exact Google Sheet row
                ];
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch ITGK certificates from Google Sheet', ['error' => $e->getMessage()]);
            $sheetError = $e->getMessage();
        }

        // Build analytics from sheet data (no DB fallback)
        $analytics = [];
        if (!empty($certificates)) {
            $analytics = [
                'total'        => count($certificates),
                'available'    => count(array_filter($certificates, fn($c) => strcasecmp((string)($c['status'] ?? ''), 'Available')  === 0)),
                'issued'       => count(array_filter($certificates, fn($c) => strcasecmp((string)($c['status'] ?? ''), 'Issued')     === 0
                    || strcasecmp((string)($c['status'] ?? ''), 'ISSUED')    === 0)),
                'intransit'    => count(array_filter($certificates, fn($c) => str_contains(strtolower((string)($c['status'] ?? '')), 'transit'))),
                'not_received' => count(array_filter($certificates, fn($c) => str_contains(strtolower((string)($c['status'] ?? '')), 'not'))),
            ];
        }

        // API request â†’ return JSON
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            $this->json([
                'success'   => true,
                'data'      => $certificates,
                'analytics' => $analytics,
                'total'     => count($certificates),
                'page'      => $page,
                'error'     => $sheetError,
            ]);
            return;
        }

        // Fetch ITGK master data for dropdown
        $itgkList = [];
        try {
            $itgkMasterId    = $sheetService->getItgkMasterSheetId();
            $itgkMasterRange = $sheetService->getItgkMasterRange();
            $itgkMasterData  = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
            $itgkMasterRows  = $itgkMasterData['rows'] ?? [];
            foreach ($itgkMasterRows as $ir) {
                $code   = trim((string)($ir['ITGK-CODE']      ?? $ir['ITGK CODE']      ?? $ir['ITGK_CODE'] ?? ''));
                $name   = trim((string)($ir['ITGK Name']      ?? $ir['ITGK NAME']      ?? ''));
                $dist   = trim((string)($ir['ITGK District']  ?? $ir['DISTRICT']       ?? $ir['District']  ?? ''));
                // Actual column names from sheet: "ITGK Email" and "ITGK Mobile"
                $email  = trim((string)($ir['ITGK Email']     ?? $ir['Email']          ?? $ir['EMAIL']     ?? $ir['E-Mail'] ?? ''));
                $mobile = trim((string)($ir['ITGK Mobile']    ?? $ir['Mobile']         ?? $ir['MOBILE']    ?? $ir['Phone'] ?? $ir['Contact'] ?? ''));
                if ($code !== '' && is_numeric($code)) {
                    $itgkList[] = [
                        'code'     => $code,
                        'name'     => $name,   // ITGK center name
                        'district' => $dist,
                        'email'    => $email,
                        'mobile'   => $mobile,
                    ];
                }
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch ITGK master list', ['error' => $e->getMessage()]);
        }

        // Fetch dropdown options dynamically from 'misc' sheet
        $locationOptions = $sheetService->getLocationOptions();
        $statusOptions   = $sheetService->getStatusOptions();
        $districtOptions = $sheetService->getDistrictOptions();
        $remarkOptions   = $sheetService->getRemarkOptions();

        // Pass ALL records — JS handles pagination on client side (no server-side slicing)
        $this->view('pages/certificate/list', [
            'certificates'    => $certificates,
            'analytics'       => $analytics,
            'title'           => 'ITGK Certificates | SoftSam Portal',
            'view'            => 'pages/certificate/list',
            'total'           => count($certificates),
            'sheetError'      => $sheetError,
            'sheetTab'        => $sheetService->getCertificateTab() ?? 'Certificate',
            'itgkList'        => $itgkList,
            'locationOptions' => $locationOptions,
            'statusOptions'   => $statusOptions,
            'districtOptions' => $districtOptions,
            'remarkOptions'   => $remarkOptions,
        ]);
    }

    // =========================================================
    // UPDATE â€” Write edited record back to Google Sheet (API v4)
    // =========================================================
    public function update(): void
    {
        if (!AuthService::isSuperAdmin() && !AuthService::hasRoleLevel('COORDINATOR')) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        // Fields sent from Edit form
        $sheetRow     = (int)trim((string)($_POST['sheet_row']        ?? 0));

        if ($sheetRow <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid sheet row â€” cannot update']);
            return;
        }

        try {
            $sheetService = new GoogleSheetService();
            $sheetId      = $sheetService->getCertificateSheetId();
            $tab          = $sheetService->getCertificateTab();

            // Certificate sheet columns A–W (23 cols):
            // A=S.No, B=Course Name, C=DATE, D=EXAM, E=EXAM_DATE_ITGK,
            // F=ITGK CODE, G=DISTRICT, H=ABSENT, I=FAIL, J=PASS, K=UFM,
            // L=Grand Total, M=Packet No., N=Cert No. From, O=Cert No. To,
            // P=Current Location, Q=STATUS, R=Remark,
            // S=Receiver Name, T=Receiver Designation, U=Receiver Mobile Number, V=Issued By, W=Image
            $range = "{$tab}!A{$sheetRow}:W{$sheetRow}";

            // Fetch existing row first — only modified columns will be overwritten;
            // all other columns remain exactly as they were received from the sheet.
            $existing = $sheetService->fetchRawRow($sheetId, $range);

            // Build updated row array preserving existing values (0-indexed 0=A to 22=W)
            $rowValues = [];
            for ($i = 0; $i < 23; $i++) {
                $rowValues[$i] = trim((string)($existing[$i] ?? ''));
            }

            // Column map: POST key → 0-indexed column position
            $columnMap = [
                'course_name'          => 1,   // B - Course Name
                'receiving_date'       => 2,   // C - DATE
                'exam_name'            => 3,   // D - EXAM
                'exam_date'            => 4,   // E - EXAM_DATE_ITGK
                'itgk_code'            => 5,   // F - ITGK CODE
                'district'             => 6,   // G - DISTRICT
                'absent'               => 7,   // H - ABSENT
                'fail'                 => 8,   // I - FAIL
                'pass'                 => 9,   // J - PASS
                'ufm'                  => 10,  // K - UFM
                'grand_total'          => 11,  // L - Grand Total
                'packet_no'            => 12,  // M - Packet No.
                'cert_no_from'         => 13,  // N - Cert No. From
                'cert_no_to'           => 14,  // O - Cert No. To
                'current_location'     => 15,  // P - Current Location
                'status'               => 16,  // Q - STATUS
                'remark'               => 17,  // R - Remark
                'receiver_name'        => 18,  // S - Receiver Name
                'receiver_designation' => 19,  // T - Receiver Designation
                'receiver_mobile'      => 20,  // U - Receiver Mobile Number
                'issued_by'            => 21,  // V - Issued By
                'image'                => 22,  // W - Image
            ];

            $modified = false;
            foreach ($columnMap as $postKey => $colIdx) {
                if (isset($_POST[$postKey])) {
                    $newVal = trim((string)$_POST[$postKey]);
                    $oldVal = $rowValues[$colIdx];
                    if ($newVal !== $oldVal) {
                        $rowValues[$colIdx] = $newVal;
                        $modified = true;
                    }
                }
            }

            if (!$modified) {
                $this->json(['success' => true, 'message' => 'No changes detected — record unchanged.']);
                return;
            }

            $sheetService->updateSheetRow($sheetId, $range, $rowValues);

            Logger::info('Certificate row updated in Google Sheet', [
                'sheet_row' => $sheetRow
            ]);
            $this->json(['success' => true, 'message' => 'Record updated in Google Sheet successfully!']);
        } catch (\Exception $e) {
            Logger::error('Certificate sheet update failed', ['error' => $e->getMessage(), 'row' => $sheetRow]);
            $this->json(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    // BULK ISSUE â€” Issue multiple Certificate packets at once
    //              Updates Certificate sheet + Student_Result sheet
    // =========================================================
    public function bulkIssue(): void
    {
        if (!AuthService::isSuperAdmin() && !AuthService::hasRoleLevel('COORDINATOR')) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        // Validate CSRF
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            return;
        }

        // Parse selections JSON
        $selectionsJson = $_POST['selections'] ?? '[]';
        $selections     = json_decode($selectionsJson, true);
        if (empty($selections) || !is_array($selections)) {
            $this->json(['success' => false, 'message' => 'No certificates selected'], 400);
            return;
        }

        // Receiver & Issuer details
        $receiverName  = trim($_POST['receiver_name']        ?? '');
        $receiverDesig = trim($_POST['receiver_designation'] ?? '');
        $receiverMob   = trim($_POST['receiver_mobile']      ?? '');
        $receiverEmail = trim($_POST['receiver_email']       ?? '');
        $issuerName    = trim($_POST['issuer_name']          ?? '');
        $issuerDesig   = trim($_POST['issuer_designation']   ?? '');
        $remark        = trim($_POST['remark']               ?? '');
        $issueDate     = date('d/m/Y H:i');

        if (empty($receiverName)) {
            $this->json(['success' => false, 'message' => 'Receiver Name is required'], 400);
            return;
        }

        // receiver_email optional - only used for email notification
        if (!empty($receiverEmail) && !filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Invalid Receiver Email format'], 400);
            return;
        }

        if (empty($receiverDesig)) {
            $this->json(['success' => false, 'message' => 'Receiver Designation is required'], 400);
            return;
        }

        // Validate issuer details - Non-SUPERADMIN users cannot override their own issuer info
        $currentUser = AuthService::user();
        $isSuperAdmin = AuthService::isSuperAdmin();

        if (!$isSuperAdmin) {
            // For non-SUPERADMIN: issuer details MUST match the logged-in user
            $expectedIssuerName = (string)($currentUser['name'] ?? '');
            $expectedIssuerDesig = (string)($currentUser['role'] ?? '');

            if (strcasecmp($issuerName, $expectedIssuerName) !== 0) {
                $this->json([
                    'success' => false,
                    'message' => 'Issuer Name must be your account name. Unauthorized modification attempt.'
                ], 403);
                return;
            }

            if (strcasecmp($issuerDesig, $expectedIssuerDesig) !== 0) {
                $this->json([
                    'success' => false,
                    'message' => 'Issuer Designation must be your role. Unauthorized modification attempt.'
                ], 403);
                return;
            }
        }

        @set_time_limit(120);

        try {
            $sheetService = new GoogleSheetService();
            $certSheetId  = $sheetService->getCertificateSheetId();
            $certTab      = $sheetService->getCertificateTab();
            $srSheetId    = $sheetService->getStudentResultSheetId();
            $srTab        = $sheetService->getStudentResultTab();

            // â”€â”€ Validate all selections are not already issued â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // Fetch all rows to verify status before processing
            $invalidRows = [];
            $validSelections = [];

            foreach ($selections as $sel) {
                $sheetRow = (int)($sel['sheet_row'] ?? 0);
                if ($sheetRow <= 0) {
                    $invalidRows[] = 'Invalid sheet row: ' . $sheetRow;
                    continue;
                }

                $range    = "{$certTab}!A{$sheetRow}:V{$sheetRow}";
                $existing = $sheetService->fetchRawRow($certSheetId, $range);

                // Check current status (column Q = index 16)
                $currentStatus = trim((string)($existing[16] ?? 'Available'));
                if (strcasecmp($currentStatus, 'Issued') === 0 || strcasecmp($currentStatus, 'ISSUED') === 0) {
                    $invalidRows[] = 'Row ' . $sheetRow . ' is already ISSUED (status: ' . $currentStatus . ')';
                    continue;
                }

                // Add to valid selections with existing row data
                $validSelections[] = [
                    'sheet_row' => $sheetRow,
                    'existing'  => $existing,
                    'sel_data'  => $sel
                ];
            }

            if (!empty($invalidRows)) {
                $this->json([
                    'success' => false,
                    'message' => 'Cannot issue selected certificates: ' . implode('; ', $invalidRows)
                ], 422);
                return;
            }

            if (empty($validSelections)) {
                $this->json(['success' => false, 'message' => 'No valid certificates to issue'], 400);
                return;
            }

            // ── 1. Build Certificate sheet batchUpdate ──────────────────
            $certUpdates = [];
            foreach ($validSelections as $valid) {
                $existing = $valid['existing'];

                // Pad to 23 columns (A–W)
                while (count($existing) < 23) $existing[] = '';

                // Update only status + receiver + issuer columns
                $existing[16] = 'ISSUED';                              // Q — STATUS
                $existing[17] = $remark;                               // R — Remark
                $existing[18] = $receiverName;                         // S — Receiver Name
                $existing[19] = $receiverDesig;                        // T — Receiver Designation
                $existing[20] = $receiverMob;                          // U — Receiver Mobile
                $existing[21] = "Issued by: {$issuerName} ({$issuerDesig}) on {$issueDate}"; // V
                // Index 22 (W) — Image link preserved as is

                $certUpdates[] = [
                    'range'  => "{$certTab}!A" . $valid['sheet_row'] . ":W" . $valid['sheet_row'],
                    'values' => [array_values($existing)],
                ];
            }

            // Single batchUpdate for Certificate sheet
            if (!empty($certUpdates)) {
                $sheetService->batchUpdateRows($certSheetId, $certUpdates);
            }

            // ── 2. Update Student_Result (Learner) rows ──────────────
            // Fetch full Student_Result sheet, find matching rows by
            // ITGK Code + Course Name + Exam Name, batch-update Status col.
            $srData    = $sheetService->fetchParsedSheet($srSheetId, $sheetService->getStudentResultRange());
            $srHeaders = $srData['headers'] ?? [];
            $srRows    = $srData['rows']    ?? [];
            $srStartRow = (int)($srData['startRow'] ?? 2);

            // Detect Status column index in Student_Result sheet
            $statusColIdx = null;
            foreach ($srHeaders as $ci => $hdr) {
                if (strcasecmp(trim($hdr), 'Status') === 0 || strcasecmp(trim($hdr), 'STATUS') === 0) {
                    $statusColIdx = $ci;
                    break;
                }
            }

            // Build lookup keys from VALID selections
            $selectionKeys = [];
            foreach ($validSelections as $valid) {
                $sel = $valid['sel_data'];
                $itgk = strtolower(trim((string)($sel['itgk_code'] ?? '')));
                $course = strtolower(trim((string)($sel['course_name'] ?? '')));
                $exam = strtolower(trim((string)($sel['exam_name'] ?? '')));
                
                if ($itgk !== '') {
                    $selectionKeys["$itgk|||$course|||$exam"] = true;
                    if ($course !== '') {
                        $selectionKeys["$itgk|||$course"] = true;
                    }
                }
                
                if (preg_match('/\((\d{2}-\d{2}-\d{4})\)/', $exam, $m)) {
                    $dateSlash = str_replace('-', '/', $m[1]);
                    $selectionKeys["$itgk|||$course|||$dateSlash"] = true;
                }
            }

            $learnerUpdates = [];
            foreach ($srRows as $rowOffset => $r) {
                $itgk   = strtolower(trim((string)($r['ITGK Code']   ?? '')));
                $course = strtolower(trim((string)($r['Course Name']  ?? '')));
                $exam   = strtolower(trim((string)($r['Exam Name']    ?? '')));
                $heldDate = str_replace('-', '/', strtolower(trim((string)($r['exam_held_date'] ?? ''))));
                
                $matchFound = false;
                if (isset($selectionKeys["$itgk|||$course|||$exam"])) {
                    $matchFound = true;
                } elseif ($heldDate !== '' && isset($selectionKeys["$itgk|||$course|||$heldDate"])) {
                    $matchFound = true;
                } elseif (isset($selectionKeys["$itgk|||$course"])) {
                    $matchFound = true;
                }

                if (!$matchFound) continue;

                $actualRow = $srStartRow + $rowOffset;
                if ($statusColIdx !== null) {
                    $colLetter = $this->colIndexToLetter($statusColIdx);
                    $learnerUpdates[] = [
                        'range'  => "{$srTab}!{$colLetter}{$actualRow}",
                        'values' => [['ISSUED']],
                    ];
                    $learnersUpdated++;
                }
            }

            // Single batchUpdate for Student_Result sheet (same spreadsheet ID)
            if (!empty($learnerUpdates)) {
                $sheetService->batchUpdateRows($srSheetId, $learnerUpdates);
            }

            // â”€â”€ 3. Send Email Notifications to 4 Recipients â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $itgkEmail = null;
            $issuerEmail = $currentUser['email'] ?? null;

            // Fetch ITGK email from first valid selection's ITGK code
            if (!empty($validSelections)) {
                $firstSel = $validSelections[0] ?? [];
                $firstItgkCode = (string)($firstSel['sel_data']['itgk_code'] ?? '');

                if ($firstItgkCode) {
                    // Fetch ITGK MASTER sheet to get email for this ITGK
                    try {
                        $itgkMasterId = $sheetService->getItgkMasterSheetId();
                        $itgkMasterRange = $sheetService->getItgkMasterRange();
                        $itgkMasterData = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
                        $itgkRows = $itgkMasterData['rows'] ?? [];

                        foreach ($itgkRows as $itgkRow) {
                            $code = strtolower(trim((string)($itgkRow['ITGK CODE'] ?? $itgkRow['ITGK Code'] ?? '')));
                            if (strcasecmp($code, $firstItgkCode) === 0) {
                                $itgkEmail = trim((string)($itgkRow['Email'] ?? $itgkRow['EMAIL'] ?? ''));
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        Logger::warn('Could not fetch ITGK email', ['error' => $e->getMessage()]);
                    }
                }
            }

            // Send email to 4 recipients
            $emailRecipients = array_filter([
                'softtechseva@gmail.com',
                $receiverEmail,
                $itgkEmail,
                $issuerEmail
            ], function ($email) {
                return $email && filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            if (!empty($emailRecipients)) {
                try {
                    $emailService = new EmailService();
                    $certCount = count($certUpdates);
                    $itgkCodes = [];
                    foreach ($validSelections as $v) {
                        $code = (string)($v['sel_data']['itgk_code'] ?? '');
                        if ($code && !in_array($code, $itgkCodes)) {
                            $itgkCodes[] = $code;
                        }
                    }

                    foreach ($emailRecipients as $toEmail) {
                        try {
                            $emailSubject = "Certificate Bulk Issue Notification - " . count($itgkCodesList) . " ITGK Code(s)";
                            $emailBody = "
                            <!DOCTYPE html>
                            <html>
                            <body style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
                                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;'>
                                    <h2 style='color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 8px;'>Certificate Bulk Issue Notification</h2>
                                    <p>Dear Recipient,</p>
                                    <p>A bulk certificate issuance has been completed with the following details:</p>
                                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Total Certificates Issued:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars((string)$certCount) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>ITGK Codes:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars(implode(', ', $itgkCodes)) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Receiver:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($receiverName) . " (" . htmlspecialchars($receiverDesig) . ")</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Issued By:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($issuerName) . " (" . htmlspecialchars($issuerDesig) . ")</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Issue Date:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($issueDate) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Receiver Email:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($receiverEmail) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Remark:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($remark ?? 'N/A') . "</td></tr>
                                    </table>
                                    <p style='color: #64748b; font-size: 12px; margin-top: 20px;'>SoftSam Certificate Management Portal</p>
                                </div>
                            </body>
                            </html>";

                            // Use PHPMailer directly for sending to multiple recipients
                            $config = $emailService->getSettings();
                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = $config['smtp_host'];
                            $mail->SMTPAuth = true;
                            $mail->Username = $config['smtp_user'];
                            $mail->Password = $config['smtp_pass'];
                            $encryption = strtolower($config['smtp_encryption']);
                            $mail->SMTPSecure = ($encryption === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = (int)$config['smtp_port'];
                            $mail->SMTPOptions = [
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    'allow_self_signed' => true
                                ]
                            ];

                            $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
                            $mail->addReplyTo($config['smtp_from_email'], $config['smtp_from_name']);
                            $mail->addAddress($toEmail);
                            $mail->isHTML(true);
                            $mail->Subject = $emailSubject;
                            $mail->Body = $emailBody;
                            $mail->send();

                            Logger::info('Bulk issue email sent', ['to' => $toEmail, 'certs' => $certCount]);
                        } catch (\Exception $emailErr) {
                            Logger::warn('Failed to send bulk issue email', ['to' => $toEmail, 'error' => $emailErr->getMessage()]);
                        }
                    }
                } catch (\Exception $e) {
                    Logger::warn('Email notification setup failed', ['error' => $e->getMessage()]);
                }
            }

            Logger::info('Bulk certificate issue complete', [
                'certs_updated'   => count($certUpdates),
                'learners_updated' => count($learnerUpdates),
                'receiver'        => $receiverName,
            ]);

            $this->json([
                'success'          => true,
                'message'          => 'Bulk Issue Complete! ' . count($certUpdates) . ' certificates issued. Notifications sent.',
                'certs_updated'    => count($certUpdates),
                'learners_updated' => count($learnerUpdates),
            ]);
        } catch (\Exception $e) {
            Logger::error('Bulk issue failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Bulk issue failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Convert 0-based column index to Excel-style letter (0=A, 25=Z, 26=AA â€¦)
     */
    private function colIndexToLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = intdiv($index, 26);
        }
        return $letter;
    }


    // =========================================================
    // STORE â€” Add new certificate packet to Google Sheet
    // =========================================================
    public function store(?array $data = null): ?array
    {
        if (!AuthService::isSuperAdmin()) {
            $result = ['success' => false, 'message' => 'Unauthorized. SUPERADMIN role required.'];
            $this->json($result, 403);
            return null;
        }

        if (!$this->isApiRequest()) {
            if (!Csrf::verify()) {
                $result = ['success' => false, 'message' => 'Invalid or expired CSRF token. Please refresh and try again.'];
                $this->json($result, 400);
                return null;
            }
        }

        $input     = $data ?? $_POST;
        $sanitized = $this->sanitizeCertificateData($input);

        try {
            $sheetService = new GoogleSheetService();
            $sheetId      = $sheetService->getCertificateSheetId();
            $tab          = $sheetService->getCertificateTab();

            // Fetch existing Certificate sheet data to compute auto-incremental Serial Number (Column A)
            $certData     = $sheetService->fetchParsedSheet($sheetId, $sheetService->getCertificateRange());
            $existingRows = $certData['rows'] ?? [];
            $lastSerialNum = 0;
            foreach ($existingRows as $exR) {
                $rawSNo = trim((string)($exR['S. No.'] ?? $exR['S No.'] ?? $exR['S.No.'] ?? ''));
                if (preg_match('/(\d+)/', $rawSNo, $m)) {
                    $num = (int)$m[1];
                    if ($num > $lastSerialNum) {
                        $lastSerialNum = $num;
                    }
                }
            }
            $nextSerialNum = $lastSerialNum + 1;
            $colASerial = 'Certificate ' . $nextSerialNum;

            // Format Exam Date string for Column D: e.g. (17-05-2026) or (YYYY-MM-DD)
            $rawExamDate = trim((string)($sanitized['exam_date'] ?? ''));
            $formattedExamDate = '';
            if (!empty($rawExamDate)) {
                $ts = strtotime($rawExamDate);
                $formattedExamDate = $ts ? date('d-m-Y', $ts) : $rawExamDate;
            }

            // Column D: EXAM = {{Exam Name (Exam Date)}} e.g. "MAY 2026 (17-05-2026)" or "RS-CIT Exam (17-05-2026)"
            $examNameRaw = trim((string)($sanitized['exam_name'] ?? ''));
            if ($formattedExamDate !== '') {
                // If exam_name already contains parentheses date, avoid duplicating, otherwise format cleanly
                if (!str_contains($examNameRaw, '('.$formattedExamDate.')')) {
                    $colDExam = $examNameRaw . ' (' . $formattedExamDate . ')';
                } else {
                    $colDExam = $examNameRaw;
                }
            } else {
                $colDExam = $examNameRaw;
            }

            // Column E: EXAM_DATE_ITGK = {{Column D Value + ITGK CODE}} e.g. "MAY 2026 (17-05-2026)45290330"
            $colEExamItgk = $colDExam . $sanitized['itgk_code'];

            // Build row matching Certificate sheet column order (A–V, 22 cols)
            $row = [
                $colASerial,                         // A: S. No. (auto-incremental: Certificate XXXX)
                $sanitized['course_name'],           // B: Course Name
                $sanitized['receiving_date'],        // C: DATE
                $colDExam,                           // D: EXAM (Exam Name + (Exam Date))
                $colEExamItgk,                       // E: EXAM_DATE_ITGK (Column D + ITGK CODE)
                $sanitized['itgk_code'],             // F: ITGK CODE
                $sanitized['district'],              // G: DISTRICT
                $sanitized['absent'],                // H: ABSENT
                $sanitized['fail'],                  // I: FAIL
                $sanitized['pass'],                  // J: PASS
                $sanitized['ufm'],                   // K: UFM
                $sanitized['grand_total'],           // L: Grand Total
                $sanitized['packet_no'],             // M: Packet No.
                $sanitized['cert_no_from'],          // N: Certificate No. From
                $sanitized['cert_no_to'],            // O: Certificate No. To
                $sanitized['current_location'],      // P: Current Location
                $sanitized['status'] ?: 'Available', // Q: STATUS
                $sanitized['remark'],                // R: Remark
                '',                                  // S: Receiver Name
                '',                                  // T: Receiver Designation
                '',                                  // U: Receiver Mobile Number
                '',                                  // V: Image
            ];

            // 1. Append parent certificate record
            $sheetService->appendRow($sheetId, $tab, [$row]);

            Logger::info('Certificate packet appended to Google Sheet', [
                'serial_no' => $colASerial,
                'itgk_code' => $sanitized['itgk_code'],
                'user_id'   => AuthService::id(),
            ]);

            // 2. Append blank child learner records to Student_Result sheet (one per Pass count)
            $passCount        = (int)($sanitized['pass'] ?? 0);
            $learnersAppended = 0;

            if ($passCount > 0) {
                try {
                    $srSheetId = $sheetService->getStudentResultSheetId();
                    $srTab     = $sheetService->getStudentResultTab();

                    // Fetch headers and rows to compute auto-increment S. No. for Student_Result (Column A)
                    $srData    = $sheetService->fetchParsedSheet($srSheetId, $sheetService->getStudentResultRange());
                    $srHeaders = $srData['headers'] ?? [];
                    $srRowsEx  = $srData['rows'] ?? [];

                    $lastSrSerial = 0;
                    foreach ($srRowsEx as $srR) {
                        $rawSrSNo = trim((string)($srR['S No.'] ?? $srR['S. No.'] ?? $srR['S.No.'] ?? ''));
                        if (preg_match('/(\d+)/', $rawSrSNo, $m)) {
                            $n = (int)$m[1];
                            if ($n > $lastSrSerial) {
                                $lastSrSerial = $n;
                            }
                        }
                    }

                    // Lookup ITGK Name from Master sheet using ITGK Code
                    $itgkNameMaster = '';
                    try {
                        $itgkMasterId    = $sheetService->getItgkMasterSheetId();
                        $itgkMasterRange = $sheetService->getItgkMasterRange();
                        $itgkMasterData  = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
                        $itgkMasterRows  = $itgkMasterData['rows'] ?? [];
                        foreach ($itgkMasterRows as $ir) {
                            $code = trim((string)($ir['ITGK-CODE'] ?? $ir['ITGK CODE'] ?? $ir['ITGK_CODE'] ?? ''));
                            if (strcasecmp($code, trim($sanitized['itgk_code'])) === 0) {
                                $itgkNameMaster = trim((string)($ir['ITGK Name'] ?? $ir['ITGK NAME'] ?? ''));
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        Logger::warn('Failed to fetch ITGK Name for Student_Result append', ['error' => $e->getMessage()]);
                    }

                    // Build blank learner rows with common parent fields pre-filled:
                    // Column A (S. No.), Column B (Receiving Date), Column C (ITGK Code), Course Name, Exam Name, ITGK NAME, etc.
                    $learnerRows = [];
                    for ($i = 0; $i < $passCount; $i++) {
                        $nextSrSNo = $lastSrSerial + $i + 1;
                        $learnerRow = [];
                        foreach ($srHeaders as $col) {
                            $colLower = strtolower(trim($col));
                            if (in_array($colLower, ['s. no.', 's no.', 's.no.', 's_no', 's. no', 'id'])) {
                                $learnerRow[] = $nextSrSNo;
                            } elseif (in_array($colLower, ['receiving date', 'receiving_date', 'date'])) {
                                $learnerRow[] = $sanitized['receiving_date'];
                            } elseif (in_array($colLower, ['itgk code', 'itgk_code', 'itgk code'])) {
                                $learnerRow[] = $sanitized['itgk_code'];
                            } elseif (in_array($colLower, ['itgk name', 'itgk_name'])) {
                                $learnerRow[] = $itgkNameMaster;
                            } elseif ($colLower === 'course name') {
                                $learnerRow[] = $sanitized['course_name'];
                            } elseif (in_array($colLower, ['exam name', 'exam_name on certificate', 'exam'])) {
                                $learnerRow[] = $sanitized['exam_name'];
                            } elseif ($colLower === 'batch') {
                                $learnerRow[] = ''; // leave Batch column blank
                            } elseif (in_array($colLower, ['exam date', 'exam_date', 'exam held date', 'exam_held_date'])) {
                                $learnerRow[] = $sanitized['exam_date'];
                            } elseif (in_array($colLower, ['district'])) {
                                $learnerRow[] = $sanitized['district'];
                            } elseif (in_array($colLower, ['packet no.', 'packet no', 'packet_no'])) {
                                $learnerRow[] = $sanitized['packet_no'];
                            } elseif (in_array($colLower, ['result'])) {
                                $learnerRow[] = 'PASS';
                            } elseif (in_array($colLower, ['status'])) {
                                $learnerRow[] = 'Available';
                            } else {
                                $learnerRow[] = ''; // blank for remaining learner columns
                            }
                        }
                        $learnerRows[] = $learnerRow;
                    }

                    if (!empty($learnerRows)) {
                        $sheetService->appendRow($srSheetId, $srTab, $learnerRows);
                        $learnersAppended = count($learnerRows);
                    }

                    Logger::info('Blank learner records appended to Student_Result', [
                        'itgk_code'        => $sanitized['itgk_code'],
                        'learners_created' => $learnersAppended,
                    ]);
                } catch (\Exception $srEx) {
                    // Non-fatal: certificate was already saved, log and continue
                    Logger::warn('Failed to append learner records to Student_Result sheet', [
                        'error' => $srEx->getMessage(),
                    ]);
                }
            }

            $msg = 'Certificate Packet added to Google Sheet successfully!';
            if ($learnersAppended > 0) {
                $msg .= " {$learnersAppended} blank learner record(s) created in Student_Result sheet.";
            }

            $this->json(['success' => true, 'message' => $msg], 201);
            return null;
        } catch (\Exception $e) {
            Logger::error('Failed to create certificate packet in Google Sheet', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            return null;
        }
    }

    // =========================================================
    // CONSOLIDATE â€” Groups learner records from Google Sheet
    //               by ITGK Code + Course + Exam and appends
    //               certificate packet rows to the Certificate
    //               Google Sheet. Pure Google Sheet â†’ Sheet write.
    // =========================================================
    public function consolidate(): void
    {
        $this->requireRole('SUPERADMIN');
        $this->validateCsrf();

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        try {
            $sheetService = new GoogleSheetService();
            $srId         = $sheetService->getStudentResultSheetId();
            $srRange      = $sheetService->getStudentResultRange();
            $sheetData    = $sheetService->fetchParsedSheet($srId, $srRange);
            $rawRows      = $sheetData['rows'] ?? [];

            if (empty($rawRows)) {
                $this->json(['success' => false, 'message' => 'No student results found in Google Sheet to consolidate.'], 422);
                return;
            }

            // Group by ITGK Code + Course Name + Exam Name
            $groups = [];
            foreach ($rawRows as $r) {
                $itgk   = trim((string)($r['ITGK Code'] ?? $r['ITGK CODE'] ?? $r['ITGK_CODE'] ?? ''));
                $course = trim((string)($r['Course Name'] ?? ''));
                $exam   = trim((string)($r['Exam Name']  ?? $r['exam_name on certificate'] ?? $r['BATCH'] ?? ''));

                if (empty($itgk) || empty($course) || empty($exam)) continue;

                $key = $itgk . '|||' . $course . '|||' . $exam;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'itgk_code'    => $itgk,
                        'course_name'  => $course,
                        'exam_name'    => $exam,
                        'pass'         => 0,
                        'fail'         => 0,
                        'absent'       => 0,
                        'ufm'          => 0,
                        'total'        => 0,
                        'exam_date'    => $r['Exam Date'] ?? date('Y-m-d'),
                        'receiving_date' => $r['Receiving Date'] ?? $r['DATE'] ?? date('Y-m-d'),
                    ];
                }

                $result = strtoupper(trim((string)($r['Result'] ?? 'PASS')));
                if (str_contains($result, 'PASS'))        $groups[$key]['pass']++;
                elseif (str_contains($result, 'FAIL'))    $groups[$key]['fail']++;
                elseif (str_contains($result, 'ABSENT'))  $groups[$key]['absent']++;
                else                                       $groups[$key]['ufm']++;
                $groups[$key]['total']++;
            }

            if (empty($groups)) {
                $this->json(['success' => false, 'message' => 'No valid grouped records found.'], 422);
                return;
            }

            // Append consolidated rows to Certificate Google Sheet
            $certSheetId  = $sheetService->getCertificateSheetId();
            $certTab      = $sheetService->getCertificateTab();
            $certHeaders  = ['S. No.', 'Course Name', 'DATE', 'EXAM', 'EXAM_DATE_ITGK', 'ITGK CODE', 'DISTRICT', 'ABSENT', 'FAIL', 'PASS', 'UFM', 'Grand Total', 'Packet No.', 'Cert No. From', 'Cert No. To', 'Current Location', 'STATUS', 'Remark', 'Receiver Name', 'Receiver Designation', 'Receiver Mobile Number', 'Image'];

            $appendRows = [];
            foreach ($groups as $group) {
                $row = [
                    count($appendRows) + 1, // S. No.
                    $group['course_name'],
                    $group['receiving_date'],
                    $group['exam_name'],
                    $group['exam_date'],
                    $group['itgk_code'],
                    '', // DISTRICT
                    $group['absent'],
                    $group['fail'],
                    $group['pass'],
                    $group['ufm'],
                    $group['total'],
                    '', // Packet No.
                    '', // Cert No. From
                    '', // Cert No. To
                    '', // Current Location
                    'Available',
                    '', // Remark
                    '', // Receiver Name
                    '', // Receiver Designation
                    '', // Receiver Mobile Number
                    '', // Image
                ];
                $appendRows[] = $row;
            }

            if (!empty($appendRows)) {
                $sheetService->appendRow($certSheetId, $certTab, $appendRows);
            }

            Logger::info('Certificates consolidated to Google Sheet', ['groups' => count($groups)]);

            $this->json([
                'success'  => true,
                'message'  => 'Consolidation complete to Google Sheet.',
                'groups'   => count($groups),
            ]);
        } catch (\Exception $e) {
            Logger::error('Consolidation failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Consolidation error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ISSUE BATCH â€” Issue certificate packets to ITGK
    //              Supports both single (legacy) and bulk format
    // Updates Certificate sheet + Student_Result sheet
    // =========================================================
    public function issueBatch(): void
    {
        // Auth check â€” return JSON (not exception) so JS can handle it
        if (!AuthService::isAdmin()) {
            $this->json(['success' => false, 'message' => 'Unauthorized â€” Admin access required'], 403);
            return;
        }
        if (!$this->verifyCsrf()) {
            $this->json(['success' => false, 'message' => 'CSRF token mismatch â€” please refresh and try again'], 403);
            return;
        }

        $input         = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
        $selectionsJson = $input['selections'] ?? '[]';
        $selections     = json_decode($selectionsJson, true);

        // Legacy single-certificate fallback
        if (empty($selections) || !is_array($selections)) {
            $certificateId = trim((string)($input['certificate_id'] ?? $input['id'] ?? ''));
            if (empty($certificateId)) {
                $this->json(['success' => false, 'message' => 'No certificate ID or selections provided'], 400);
                return;
            }
            $selections = [[
                'sheet_row'   => 0,
                'itgk_code'   => '',
                'course_name' => '',
                'exam_name'   => '',
            ]];
        }

        $receiverName  = trim((string)($input['receiver_name']        ?? ''));
        $receiverDesig = trim((string)($input['receiver_designation'] ?? ''));
        $receiverMob   = trim((string)($input['receiver_mobile']      ?? ''));
        $receiverEmail = trim((string)($input['receiver_email']       ?? ''));
        $_SESSION['last_receiver_email'] = $receiverEmail;
        $issuerName    = trim((string)($input['issuer_name']          ?? ''));
        $issuerDesig   = trim((string)($input['issuer_designation']   ?? ''));
        $issuerMobile  = trim((string)($input['issuer_mobile']        ?? ''));
        $remark        = trim((string)($input['remark']               ?? ''));
        $issueDate     = date('d/m/Y H:i');

        if (empty($receiverName)) {
            $this->json(['success' => false, 'message' => 'Receiver Name is required'], 400);
            return;
        }

        if (empty($receiverDesig)) {
            $this->json(['success' => false, 'message' => 'Receiver Designation is required'], 400);
            return;
        }

        if (empty($receiverEmail) || !filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Valid Receiver Email is required'], 400);
            return;
        }

        // Auto-fill/enforce issuer details from logged in user if non-SUPERADMIN
        $currentUser = AuthService::user();
        $isSuperAdmin = AuthService::isSuperAdmin();

        if (!$isSuperAdmin) {
            $issuerName  = (string)($currentUser['name'] ?? $issuerName);
            $issuerDesig = (string)($currentUser['role'] ?? $currentUser['designation'] ?? $issuerDesig);
            $issuerMobile = (string)($currentUser['mobile'] ?? $issuerMobile);
        }

        @set_time_limit(120);

        try {
            $sheetService = new GoogleSheetService();
            $certSheetId  = $sheetService->getCertificateSheetId();
            $certTab      = $sheetService->getCertificateTab();
            $certRange    = $sheetService->getCertificateRange();
            $certData     = $sheetService->fetchParsedSheet($certSheetId, $certRange);
            $certRows     = $certData['rows'] ?? [];
            $certHeaders  = $certData['headers'] ?? [];

            // Build lookup: sheet_row → certificate data
            // Must match the same formula used in list(): $sheetStartRow + $idx
            $certByRow   = [];
            $certStartRow = (int)($certData['startRow'] ?? 1);
            foreach ($certRows as $idx => $row) {
                $sheetRowNum = $certStartRow + $idx;
                $certByRow[$sheetRowNum] = $row;
            }

            // Validate: all selected certificates must share the same ITGK code
            $itgkCodes = [];
            $invalidRows = [];

            foreach ($selections as $sel) {
                $sheetRow = (int)($sel['sheet_row'] ?? 0);
                if ($sheetRow <= 0) {
                    $invalidRows[] = 'Invalid sheet row: ' . $sheetRow;
                    continue;
                }

                $row = $certByRow[$sheetRow] ?? null;
                if (!$row) {
                    $invalidRows[] = 'Certificate row ' . $sheetRow . ' not found';
                    continue;
                }

                // Check current status (look for STATUS column case-insensitively)
                $statusColIdx = false;
                foreach ($certHeaders as $chi => $chName) {
                    if (strcasecmp(trim((string)$chName), 'status') === 0) {
                        $statusColIdx = $chi;
                        break;
                    }
                }

                if ($statusColIdx !== false) {
                    $currentStatus = trim((string)($row[$certHeaders[$statusColIdx]] ?? 'Available'));
                    if (strcasecmp($currentStatus, 'Issued') === 0) {
                        $invalidRows[] = 'Row ' . $sheetRow . ' is already ISSUED (status: ' . $currentStatus . ')';
                        continue;
                    }
                }

                $itgk = strtolower(trim((string)($row['ITGK CODE'] ?? $row['ITGK Code'] ?? '')));
                if ($itgk !== '') {
                    $itgkCodes[$itgk] = true;
                }
            }

            if (!empty($invalidRows)) {
                $this->json([
                    'success' => false,
                    'message' => 'Cannot issue selected certificates: ' . implode('; ', $invalidRows)
                ], 422);
                return;
            }

            if (count($itgkCodes) > 1) {
                $this->json([
                    'success' => false,
                    'message' => 'All selected certificates must belong to the same ITGK code for a single bulk issue. Please select certificates from only one ITGK code.',
                ], 400);
                return;
            }

            $certUpdates     = [];
            $certsUpdated    = 0;
            $learnersUpdated = 0;
            $issuedIds       = [];  // S.No IDs of successfully issued certificates

            foreach ($selections as $sel) {
                $sheetRow = (int)($sel['sheet_row'] ?? 0);
                if ($sheetRow <= 0) continue;

                $row = $certByRow[$sheetRow] ?? null;
                if (!$row) continue;

                // Build full row update preserving all existing values;
                // only STATUS, Remark, Receiver columns are overwritten
                $updateRow = [];
                foreach ($certHeaders as $ci => $h) {
                    $updateRow[$ci] = $row[$h] ?? '';
                }

                $idxQ = $idxR = $idxS = $idxT = $idxU = $idxV = $idxG = $idxM = $idxN = $idxO = false;
                foreach ($certHeaders as $ci => $h) {
                    $hNorm = strtolower(trim((string)$h));
                    if ($hNorm === 'status') $idxQ = $ci;
                    elseif ($hNorm === 'remark') $idxR = $ci;
                    elseif ($hNorm === 'receiver name') $idxS = $ci;
                    elseif ($hNorm === 'receiver designation') $idxT = $ci;
                    elseif ($hNorm === 'receiver mobile number' || $hNorm === 'receiver mobile') $idxU = $ci;
                    elseif ($hNorm === 'image') $idxV = $ci;
                    elseif ($hNorm === 'district') $idxG = $ci;
                    elseif ($hNorm === 'packet no.' || $hNorm === 'packet no' || $hNorm === 'packet_no') $idxM = $ci;
                    elseif (str_contains($hNorm, 'certificate no. from') || str_contains($hNorm, 'cert_no_from')) $idxN = $ci;
                    elseif (str_contains($hNorm, 'certificate no. to') || str_contains($hNorm, 'cert_no_to')) $idxO = $ci;
                }
                $issuerOfficeName = trim((string)($currentUser['office_name'] ?? $currentUser['office'] ?? 'HEAD OFFICE'));
                if ($idxQ !== false) $updateRow[$idxQ] = 'ISSUED';
                if ($idxR !== false) $updateRow[$idxR]  = $remark;
                if ($idxS !== false) $updateRow[$idxS]  = $receiverName;
                if ($idxT !== false) $updateRow[$idxT]  = $receiverDesig;
                if ($idxU !== false) $updateRow[$idxU]  = $receiverMob;
                if ($idxV !== false) $updateRow[$idxV]  = "Issued by: {$issuerName} ({$issuerDesig}) | Office: {$issuerOfficeName} | Mob: {$issuerMobile} | on {$issueDate}";

                // Update District (Section 1), Packet No, Cert From, Cert To (Section 2) if passed from offcanvas
                $selDistrict = trim((string)($sel['district'] ?? ''));
                $selPacket   = trim((string)($sel['packet_no'] ?? ''));
                $selCertFrom = trim((string)($sel['cert_no_from'] ?? ''));
                $selCertTo   = trim((string)($sel['cert_no_to'] ?? ''));

                if ($idxG !== false && $selDistrict !== '') $updateRow[$idxG] = $selDistrict;
                if ($idxM !== false && $selPacket !== '')   $updateRow[$idxM] = $selPacket;
                if ($idxN !== false && $selCertFrom !== '') $updateRow[$idxN] = $selCertFrom;
                if ($idxO !== false && $selCertTo !== '')   $updateRow[$idxO] = $selCertTo;

                $startCol = $this->colIndexToLetter(0);
                $endCol   = $this->colIndexToLetter(count($certHeaders) - 1);
                $range    = "{$certTab}!{$startCol}{$sheetRow}:{$endCol}{$sheetRow}";
                $certUpdates[] = [
                    'range'  => $range,
                    'values' => [array_values($updateRow)],
                ];
                $certsUpdated++;
                // Track S.No for acknowledgement page
                $sNo = trim((string)($row['S. No.'] ?? ''));
                if ($sNo !== '') $issuedIds[] = $sNo;
            }

            // Single batchUpdate for Certificate sheet
            if (!empty($certUpdates)) {
                $sheetService->batchUpdateRows($certSheetId, $certUpdates);
            }

            // â”€â”€ Also update Student_Result sheet â”€â”€
            $srId       = $sheetService->getStudentResultSheetId();
            $srTab      = $sheetService->getStudentResultTab();
            $srRange    = $sheetService->getStudentResultRange();
            $srData     = $sheetService->fetchParsedSheet($srId, $srRange);
            $srHeaders  = $srData['headers'] ?? [];
            $srRows     = $srData['rows'] ?? [];
            $srStartRow = (int)($srData['startRow'] ?? 2);

            // Build lookup key set from selections (both exact name and extracted date)
            $selKeys = [];
            foreach ($selections as $sel) {
                $itgk = strtolower(trim((string)($sel['itgk_code'] ?? '')));
                $course = strtolower(trim((string)($sel['course_name'] ?? '')));
                $exam = strtolower(trim((string)($sel['exam_name'] ?? '')));
                
                $selKeys["$itgk|||$course|||$exam"] = true;
                
                if (preg_match('/\((\d{2}-\d{2}-\d{4})\)/', $exam, $m)) {
                    $dateSlash = str_replace('-', '/', $m[1]);
                    $selKeys["$itgk|||$course|||$dateSlash"] = true;
                }
            }

            // Also match by sheet_row certificate data for each selected row
            foreach ($selections as $sel) {
                $sheetRow = (int)($sel['sheet_row'] ?? 0);
                if ($sheetRow <= 0 || !isset($certByRow[$sheetRow])) continue;
                $row = $certByRow[$sheetRow];
                $itgk   = strtolower(trim((string)($row['ITGK CODE'] ?? '')));
                $course = strtolower(trim((string)($row['Course Name'] ?? '')));
                $exam   = strtolower(trim((string)($row['EXAM'] ?? '')));
                
                if ($itgk !== '') {
                    $selKeys["$itgk|||$course|||$exam"] = true;
                    if ($course !== '') {
                        $selKeys["$itgk|||$course"] = true;
                    }
                }
                
                if (preg_match('/\((\d{2}-\d{2}-\d{4})\)/', $exam, $m)) {
                    $dateSlash = str_replace('-', '/', $m[1]);
                    $selKeys["$itgk|||$course|||$dateSlash"] = true;
                }
            }

            $statusColIdx = null;
            foreach ($srHeaders as $ci => $hdr) {
                if (strcasecmp(trim($hdr), 'STATUS') === 0 || strcasecmp(trim($hdr), 'Status') === 0) {
                    $statusColIdx = $ci;
                    break;
                }
            }

            $learnerUpdates = [];
            foreach ($srRows as $rowOffset => $r) {
                $itgk   = strtolower(trim((string)($r['ITGK Code']   ?? '')));
                $course = strtolower(trim((string)($r['Course Name']  ?? '')));
                $exam   = strtolower(trim((string)($r['Exam Name']    ?? '')));
                $heldDate = str_replace('-', '/', strtolower(trim((string)($r['exam_held_date'] ?? ''))));
                
                $matchFound = false;
                if (isset($selKeys["$itgk|||$course|||$exam"])) {
                    $matchFound = true;
                } elseif ($heldDate !== '' && isset($selKeys["$itgk|||$course|||$heldDate"])) {
                    $matchFound = true;
                } elseif (isset($selKeys["$itgk|||$course"])) {
                    $matchFound = true;
                }

                if (!$matchFound) continue;

                $actualRow = $srStartRow + $rowOffset;
                if ($statusColIdx !== null) {
                    $colLetter = $this->colIndexToLetter($statusColIdx);
                    $learnerUpdates[] = [
                        'range'  => "{$srTab}!{$colLetter}{$actualRow}",
                        'values' => [['ISSUED']],
                    ];
                    $learnersUpdated++;
                }
            }

            if (!empty($learnerUpdates)) {
                $sheetService->batchUpdateRows($srId, $learnerUpdates);
            }

            // ── 1. Append to Dispatch Register (Certificate Tracker) ──────────
            try {
                $this->appendToDispatchRegister(
                    $sheetService,
                    $selections,
                    $certsUpdated,
                    $receiverName,
                    $receiverMob,
                    $issuerName,
                    $issuerMobile,
                    $remark,
                    $issueDate
                );
                Logger::info('Dispatch register entry appended', ['certs' => $certsUpdated]);
            } catch (\Exception $drEx) {
                // Non-fatal: log warning but don't fail the response
                Logger::warn('Dispatch register append failed', ['error' => $drEx->getMessage()]);
            }

            // ── 2. Email Notifications ─────────────────────────────────────
            $currentUser = AuthService::user();
            $itgkEmail = null;
            $issuerEmail = $currentUser['email'] ?? null;

            // Fetch ITGK email from first selection's ITGK code
            $firstItgk = null;
            foreach ($selections as $sel) {
                $code = (string)($sel['itgk_code'] ?? '');
                if ($code) {
                    $firstItgk = $code;
                    break;
                }
            }

            if ($firstItgk) {
                try {
                    $itgkMasterId = $sheetService->getItgkMasterSheetId();
                    $itgkMasterRange = $sheetService->getItgkMasterRange();
                    $itgkMasterData = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
                    $itgkRows = $itgkMasterData['rows'] ?? [];

                    foreach ($itgkRows as $itgkRow) {
                        $code = strtolower(trim((string)($itgkRow['ITGK CODE'] ?? $itgkRow['ITGK Code'] ?? '')));
                        if (strcasecmp($code, $firstItgk) === 0) {
                            $itgkEmail = trim((string)($itgkRow['Email'] ?? $itgkRow['EMAIL'] ?? ''));
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Logger::warn('Could not fetch ITGK email', ['error' => $e->getMessage()]);
                }
            }

            // Send email to 4 recipients
            $emailRecipients = array_filter([
                'softtechseva@gmail.com',
                $receiverEmail,
                $itgkEmail,
                $issuerEmail
            ], function ($email) {
                return $email && filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            if (!empty($emailRecipients)) {
                try {
                    $emailService = new EmailService();
                    // Build itgkCodes list from selections
                    $itgkCodesList = [];
                    foreach ($selections as $sel) {
                        $code = (string)($sel['itgk_code'] ?? '');
                        if ($code && !in_array($code, $itgkCodesList)) {
                            $itgkCodesList[] = $code;
                        }
                    }

                    foreach ($emailRecipients as $toEmail) {
                        try {
                            $emailSubject = "Certificate Bulk Issue Notification - " . count($itgkCodesList) . " ITGK Code(s)";
                            $emailBody = "
                            <!DOCTYPE html>
                            <html>
                            <body style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
                                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;'>
                                    <h2 style='color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 8px;'>Certificate Bulk Issue Notification</h2>
                                    <p>Dear Recipient,</p>
                                    <p>A bulk certificate issuance has been completed with the following details:</p>
                                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Total Certificates Issued:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars((string)$certsUpdated) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>ITGK Codes:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars(implode(', ', array_keys($itgkCodes))) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Receiver:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($receiverName) . " (" . htmlspecialchars($receiverDesig) . ")</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Issued By:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($issuerName) . " (" . htmlspecialchars($issuerDesig) . ")</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Issue Date:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($issueDate) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Receiver Email:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($receiverEmail) . "</td></tr>
                                        <tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Remark:</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($remark ?? 'N/A') . "</td></tr>
                                    </table>
                                    <p style='color: #64748b; font-size: 12px; margin-top: 20px;'>SoftSam Certificate Management Portal</p>
                                </div>
                            </body>
                            </html>";

                            // Use PHPMailer directly for sending to multiple recipients
                            $config = $emailService->getSettings();
                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = $config['smtp_host'];
                            $mail->SMTPAuth = true;
                            $mail->Username = $config['smtp_user'];
                            $mail->Password = $config['smtp_pass'];
                            $encryption = strtolower($config['smtp_encryption']);
                            $mail->SMTPSecure = ($encryption === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = (int)$config['smtp_port'];
                            $mail->SMTPOptions = [
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    'allow_self_signed' => true
                                ]
                            ];

                            $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
                            $mail->addReplyTo($config['smtp_from_email'], $config['smtp_from_name']);
                            $mail->addAddress($toEmail);
                            $mail->isHTML(true);
                            $mail->Subject = $emailSubject;
                            $mail->Body = $emailBody;
                            $mail->send();

                            Logger::info('Bulk issue email sent (issueBatch)', ['to' => $toEmail, 'certs' => $certsUpdated]);
                        } catch (\Exception $emailErr) {
                            Logger::warn('Failed to send bulk issue email (issueBatch)', ['to' => $toEmail, 'error' => $emailErr->getMessage()]);
                        }
                    }
                } catch (\Exception $e) {
                    Logger::warn('Email notification setup failed (issueBatch)', ['error' => $e->getMessage()]);
                }
            }

            Logger::info('Bulk certificate issue complete (Google Sheets)', [
                'certs_updated'   => $certsUpdated,
                'learners_updated' => $learnersUpdated,
                'receiver'        => $receiverName,
            ]);

            $this->json([
                'success'           => true,
                'message'           => 'Bulk Issue Complete! Certificates issued in Google Sheets. Notifications sent.',
                'certs_updated'     => $certsUpdated,
                'learners_updated'  => $learnersUpdated,
                'issued_ids'        => $issuedIds,
            ]);
        } catch (\Exception $e) {
            Logger::error('Bulk issue failed (Google Sheets)', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Bulk issue failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // DISPATCH REGISTER — Append tracking entry
    // =========================================================
    private function appendToDispatchRegister(
        \App\Services\GoogleSheetService $sheetService,
        array  $selections,
        int    $certsUpdated,
        string $receiverName,
        string $receiverMobile,
        string $issuerName,
        string $issuerMobile,
        string $remark,
        string $issueDate
    ): void {
        // Collect unique ITGK codes and packet IDs from selections
        $itgkCodes  = [];
        $packetIds  = [];
        $courses    = [];
        $exams      = [];
        $grandTotal = 0;

        foreach ($selections as $sel) {
            $code   = trim((string)($sel['itgk_code']   ?? ''));
            $packet = trim((string)($sel['packet_no']   ?? ''));
            $course = trim((string)($sel['course_name'] ?? ''));
            $exam   = trim((string)($sel['exam_name']   ?? ''));
            $total  = (int)($sel['grand_total']         ?? 0);

            if ($code   && !in_array($code,   $itgkCodes, true)) $itgkCodes[]  = $code;
            if ($packet && !in_array($packet, $packetIds, true)) $packetIds[]  = $packet;
            if ($course && !in_array($course, $courses,   true)) $courses[]    = $course;
            if ($exam   && !in_array($exam,   $exams,     true)) $exams[]      = $exam;
            $grandTotal += $total;
        }

        // Auto-generate Movement ID: MOV-YYYYMMDD-HHMMSS
        $movementId = 'MOV-' . date('Ymd') . '-' . date('His');

        // Current user for Logged By
        $currentUser = AuthService::user();
        $loggedBy    = trim(($currentUser['name'] ?? '') . ' (' . ($currentUser['role'] ?? '') . ')');

        // Build the 16-column row
        $row = [
            $movementId,                                                   // 1. Movement ID
            implode(', ', $itgkCodes),                                     // 2. ITGK Code
            implode(', ', $packetIds)  ?: implode(', ', $courses),         // 3. Certificate Packet IDs
            implode(', ', $courses),                                       // 4. Course Name
            implode(', ', $exams),                                         // 5. Exam Name
            (string)($grandTotal ?: $certsUpdated),                        // 6. Grand Total (Total Students across all packets)
            'ISSUED',                                                      // 7. Action Type
            $issueDate,                                                    // 8. Date & Time
            $issuerName,                                                   // 9. Sender Name
            $issuerMobile,                                                 // 10. Sender Mobile
            $receiverName,                                                 // 11. Receiver Name
            $receiverMobile,                                               // 12. Receiver Mobile
            'ITGK Center (' . implode(', ', $itgkCodes) . ')',             // 13. Current Location
            $remark ?: 'Certificate packet issued via bulk issue.',         // 14. Remark
            '',                                                            // 15. Signature / eSign
            $loggedBy,                                                     // 16. Logged By
        ];

        $sheetService->appendRow(
            $sheetService->getCertTrackerSheetId(),
            $sheetService->getCertTrackerTab(),
            [$row]
        );
    }

    // =========================================================
    // DELETE â€” Clear certificate row in Google Sheet
    // =========================================================
    public function delete(): void
    {
        $this->requireRole('SUPERADMIN');
        $this->validateCsrf();

        $ids = json_decode($_POST['ids'] ?? '[]', true);

        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'No IDs provided'], 400);
            return;
        }

        try {
            $sheetService = new GoogleSheetService();
            $sheetId      = $sheetService->getCertificateSheetId();
            $tab          = $sheetService->getCertificateTab();
            $headers      = $sheetService->fetchSheet($sheetId, $sheetService->getCertificateRange())['headers'] ?? [];
            $blankRow     = array_fill(0, count($headers), '');
            $startCol     = $this->colIndexToLetter(0);
            $endCol       = $this->colIndexToLetter(count($headers) - 1);

            foreach ($ids as $id) {
                $range = "{$tab}!{$startCol}{$id}:{$endCol}{$id}";
                $sheetService->updateSheetRow($sheetId, $range, $blankRow);
            }

            Logger::info('Certificate rows cleared in Google Sheet', ['ids' => $ids, 'count' => count($ids)]);
            $this->json(['success' => true, 'deleted_count' => count($ids)]);
        } catch (\Exception $e) {
            Logger::error('Failed to clear certificate rows in Google Sheet', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ACKNOWLEDGEMENT â€” Printable receipt, fetches data from Google Sheets
    // =========================================================
    public function acknowledgement(): void
    {
        $this->requireAuth();

        // Accept: ?id=2350  OR  ?id=Certificate+2350  OR  ?ids=2350,2351
        $idsParam = trim((string)($_GET['ids'] ?? $_GET['id'] ?? ''));
        if ($idsParam === '') {
            http_response_code(400);
            echo 'Invalid certificate ID.';
            return;
        }

        // Parse and clean each ID (strip "Certificate " prefix, get numeric)
        $rawIds = array_map('trim', explode(',', $idsParam));
        $numericIds = [];
        foreach ($rawIds as $raw) {
            $clean = (int)preg_replace('/^certificate\s*/i', '', $raw);
            if ($clean > 0) $numericIds[] = $clean;
        }

        if (empty($numericIds)) {
            http_response_code(400);
            echo 'Invalid certificate ID(s).';
            return;
        }

        try {
            $sheetService  = new GoogleSheetService();
            $sheetId       = $sheetService->getCertificateSheetId();
            $sheetRange    = $sheetService->getCertificateRange();
            $sheetData     = $sheetService->fetchParsedSheet($sheetId, $sheetRange);
            $rawRows       = $sheetData['rows'] ?? [];
            $sheetStartRow = (int)($sheetData['startRow'] ?? 1);
            $certs         = [];

            foreach ($rawRows as $idx => $r) {
                $actualSheetRow = $sheetStartRow + $idx;
                $rowId = (string)($r['S. No.'] ?? ($idx + 1));
                // Match by S.No or sheet row number
                $rowNumericId = (int)preg_replace('/^certificate\s*/i', '', $rowId);
                if (!in_array($rowNumericId, $numericIds, true)) {
                    continue;
                }
                $certs[] = [
                    'id'                   => $rowId,
                    'course_name'          => $r['Course Name']  ?? '',
                    'receiving_date'       => $r['DATE']         ?? $r['Receiving Date'] ?? '',
                    'exam_name'            => $r['EXAM']         ?? $r['Exam Name']       ?? '',
                    'exam_date'            => $r['EXAM_DATE_ITGK'] ?? $r['Exam Date']    ?? '',
                    'itgk_code'            => $r['ITGK CODE']   ?? $r['ITGK Code']       ?? '',
                    'district'             => $r['DISTRICT']    ?? $r['District']         ?? '',
                    'absent'               => (int)($r['ABSENT']      ?? $r['Absent']     ?? 0),
                    'fail'                 => (int)($r['FAIL']        ?? $r['Fail']       ?? 0),
                    'pass'                 => (int)($r['PASS']        ?? $r['Pass']       ?? 0),
                    'ufm'                  => (int)($r['UFM']         ?? $r['Ufm']        ?? 0),
                    'grand_total'          => (int)($r['Grand Total'] ?? $r['grand_total'] ?? 0),
                    'packet_no'            => $r['Packet No.']         ?? $r['Packet No'] ?? '',
                    'cert_no_from'         => $r['Certificate No. From'] ?? $r['Cert No From'] ?? '',
                    'cert_no_to'           => $r['Certificate No. To']   ?? $r['Cert No To']   ?? '',
                    'current_location'     => $r['Current Location'] ?? '',
                    'status'               => $r['STATUS']   ?? $r['Status']   ?? 'Available',
                    'remark'               => $r['Remark']              ?? '',
                    'receiver_name'        => $r['Receiver Name']       ?? '',
                    'receiver_designation' => $r['Receiver Designation'] ?? '',
                    'receiver_mobile'      => $r['Receiver Mobile Number'] ?? '',
                    'sheet_row'            => $actualSheetRow,
                    // Issuer info (stored in Image column on issue)
                    'issuer_info'          => $r['Image'] ?? '',
                ];
                if (count($certs) >= count($numericIds)) break; // all found
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch certificate for acknowledgement', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Failed to load certificate data: ' . $e->getMessage();
            return;
        }

        if (empty($certs)) {
            http_response_code(404);
            echo 'Certificate(s) not found in Google Sheet.';
            return;
        }

        // --- Fetch ITGK Master for name/email ---
        $itgkCode   = trim((string)($certs[0]['itgk_code'] ?? ''));
        $itgkMaster = ['name' => '', 'email' => '', 'mobile' => '', 'district' => '', 'address' => ''];
        try {
            $imId    = $sheetService->getItgkMasterSheetId();
            $imRange = $sheetService->getItgkMasterRange();
            $imData  = $sheetService->fetchParsedSheet($imId, $imRange);
            foreach ($imData['rows'] ?? [] as $ir) {
                $c = trim((string)($ir['ITGK-CODE'] ?? $ir['ITGK CODE'] ?? $ir['ITGK_CODE'] ?? ''));
                if ($c !== '' && strcasecmp($c, $itgkCode) === 0) {
                    $itgkMaster = [
                        'name'     => trim((string)($ir['ITGK Name']     ?? $ir['ITGK NAME']     ?? '')),
                        'email'    => trim((string)($ir['ITGK Email']    ?? $ir['Email']          ?? $ir['EMAIL'] ?? '')),
                        'mobile'   => trim((string)($ir['ITGK Mobile']   ?? $ir['Mobile']         ?? $ir['MOBILE'] ?? '')),
                        'district' => trim((string)($ir['ITGK District'] ?? $ir['DISTRICT']     ?? $ir['District'] ?? '')),
                        'address'  => trim((string)($ir['ITGK Address']  ?? $ir['Address']       ?? $ir['ITGK ADDRESS'] ?? '')),
                    ];
                    break;
                }
            }
        } catch (\Exception $e) { /* non-fatal */ }

        // --- Issuer info from session & Google Sheet ---
        $currentUser  = AuthService::user();
        $sessionName  = trim((string)($currentUser['name'] ?? $currentUser['username'] ?? ''));
        $sessionDesig = trim((string)($currentUser['designation'] ?? $currentUser['role'] ?? ''));
        $sessionOffice= trim((string)($currentUser['office_name'] ?? $currentUser['office'] ?? ''));

        // Parse issuer info stored in Image column:
        // Format: "Issued by: Name (Desig) | Office: OfficeName | Mob: 9xx | on DATE"
        $imageStr    = trim((string)($certs[0]['issuer_info'] ?? ''));
        $issuerName  = '';
        $issuerDesig = '';
        $issuerFrom  = '';
        $issueDate   = date('d-m-Y');

        if ($imageStr && preg_match('/Issued by:\s*(.+?)(?:\s*\((.+?)\))?\s*\|(?:\s*Office:\s*(.+?)\s*\|)?\s*Mob:.*?\|\s*on\s*(.+)/i', $imageStr, $m)) {
            $issuerName  = trim($m[1]);
            $issuerDesig = !empty($m[2]) ? trim($m[2]) : '';
            if (!empty($m[3])) {
                $issuerFrom = trim($m[3]);
            }
            if (!empty($m[4])) {
                $issueDate = trim($m[4]);
            }
        }

        // Use session details if not present in Image column
        if (!$issuerName)  $issuerName  = $sessionName;
        if (!$issuerDesig) $issuerDesig = $sessionDesig;
        if (!$issuerFrom)  $issuerFrom  = $sessionOffice;

        // Combine Name & Designation if designation exists
        $issuedByFull = $issuerName . ($issuerDesig ? " ({$issuerDesig})" : '');

        // Transaction ID
        $txnId = 'ISSUE-' . date('Ymd') . '-' . strtoupper(substr(md5($itgkCode . implode(',', $numericIds)), 0, 6));

        // For backward compat: also expose single $cert
        $cert = $certs[0];

        $this->view('pages/certificate/acknowledgement', [
            'cert'         => $cert,
            'certs'        => $certs,
            'itgkMaster'   => $itgkMaster,
            'issuerName'   => $issuedByFull ?: ($certs[0]['issued_by'] ?? 'N/A'),
            'issuerEmail'  => $currentUser['email'] ?? '',
            'issuerRole'   => $sessionDesig,
            'issuerFrom'   => $issuerFrom ?: ($certs[0]['current_location'] ?? 'N/A'),
            'issuerOffice' => $issuerFrom ?: ($certs[0]['current_location'] ?? 'N/A'),
            'issueDate'    => $issueDate,
            'txnId'        => $txnId,
            'title'        => 'Certificate Issue Acknowledgement - SoftSam Portal',
        ], false);
    }

    
    
    public function sendAckEmail(): void
    {
        header('Content-Type: application/json');

        // Verify CSRF
        if (!$this->verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF validation failed. Please reload the page.']);
            return;
        }

        // Parse JSON input
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $certIdsRaw = $input['cert_ids'] ?? [];
        $txnId = trim((string)($input['txn_id'] ?? ''));

        $certIds = [];
        foreach ((array)$certIdsRaw as $id) {
            $clean = (int)preg_replace('/^certificate\s*/i', '', (string)$id);
            if ($clean > 0) $certIds[] = $clean;
        }

        if (empty($certIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No certificate IDs provided.']);
            return;
        }

        try {
            $sheetService  = new \App\Services\GoogleSheetService();
            $sheetId       = $sheetService->getCertificateSheetId();
            $sheetRange    = $sheetService->getCertificateRange();
            $sheetData     = $sheetService->fetchParsedSheet($sheetId, $sheetRange);
            $rawRows       = $sheetData['rows'] ?? [];

            $certs = [];
            foreach ($rawRows as $idx => $r) {
                $rowId = (string)($r['S. No.'] ?? ($idx + 1));
                $rowNumericId = (int)preg_replace('/^certificate\s*/i', '', $rowId);
                
                if (in_array($rowNumericId, $certIds, true)) {
                    $certs[] = [
                        'id'           => $rowId,
                        'course_name'  => $r['Course Name']  ?? '',
                        'receiving_date' => $r['DATE']         ?? $r['Receiving Date'] ?? '',
                        'exam_name'    => $r['EXAM']         ?? $r['Exam Name']       ?? '',
                        'itgk_code'    => $r['ITGK CODE']   ?? $r['ITGK Code']       ?? '',
                        'district'     => $r['DISTRICT']    ?? $r['District']         ?? '',
                        'absent'       => (int)($r['ABSENT']      ?? $r['Absent']     ?? 0),
                        'fail'         => (int)($r['FAIL']        ?? $r['Fail']       ?? 0),
                        'pass'         => (int)($r['PASS']        ?? $r['Pass']       ?? 0),
                        'grand_total'  => (int)($r['Grand Total'] ?? $r['grand_total'] ?? 0),
                        'packet_no'    => $r['Packet No.']         ?? $r['Packet No'] ?? '',
                        'cert_no_from' => $r['Certificate No. From'] ?? $r['Cert No From'] ?? '',
                        'cert_no_to'   => $r['Certificate No. To']   ?? $r['Cert No To']   ?? '',
                        'receiver_name' => $r['Receiver Name']       ?? '',
                        'receiver_designation' => $r['Receiver Designation'] ?? '',
                        'receiver_mobile'      => $r['Receiver Mobile Number'] ?? '',
                        'issuer_info' => $r['Image'] ?? '',
                    ];
                }
            }

            if (empty($certs)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Certificates not found in Google Sheet.']);
                return;
            }

            // Fetch ITGK details from ITGK Master
            $firstItgk = trim((string)($certs[0]['itgk_code'] ?? ''));
            $itgkEmail = null;
            $itgkName = 'N/A';
            $itgkAddress = 'N/A';
            $itgkDistrict = 'N/A';
            $itgkMobile = 'N/A';

            if ($firstItgk !== '') {
                $itgkMasterId = $sheetService->getItgkMasterSheetId();
                $itgkMasterRange = $sheetService->getItgkMasterRange();
                $itgkMasterData = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
                $itgkRows = $itgkMasterData['rows'] ?? [];

                foreach ($itgkRows as $itgkRow) {
                    $code = trim((string)($itgkRow['ITGK-CODE'] ?? $itgkRow['ITGK CODE'] ?? $itgkRow['ITGK_CODE'] ?? ''));
                    if ($code !== '' && strcasecmp($code, $firstItgk) === 0) {
                        $itgkEmail    = trim((string)($itgkRow['ITGK Email']    ?? $itgkRow['Email']          ?? $itgkRow['EMAIL'] ?? ''));
                        $itgkName     = trim((string)($itgkRow['ITGK Name']     ?? $itgkRow['ITGK NAME']     ?? $itgkRow['Name']  ?? 'N/A'));
                        $itgkAddress  = trim((string)($itgkRow['ITGK Address']  ?? $itgkRow['Address']       ?? $itgkRow['ITGK ADDRESS'] ?? 'N/A'));
                        $itgkDistrict = trim((string)($itgkRow['ITGK District'] ?? $itgkRow['DISTRICT']     ?? $itgkRow['District']    ?? 'N/A'));
                        $itgkMobile   = trim((string)($itgkRow['ITGK Mobile']   ?? $itgkRow['Mobile']         ?? $itgkRow['MOBILE']      ?? 'N/A'));
                        break;
                    }
                }
            }

            $receiverName = htmlspecialchars((string)($certs[0]['receiver_name'] ?? 'N/A'));
            $receiverDesig = htmlspecialchars((string)($certs[0]['receiver_designation'] ?? ''));
            $receiverMob = htmlspecialchars((string)($certs[0]['receiver_mobile'] ?? ''));

            $totalPass = array_sum(array_column($certs, 'pass'));
            $totalGrand = array_sum(array_column($certs, 'grand_total'));

            $currentUser  = AuthService::user();
            $sessionName  = trim((string)($currentUser['name'] ?? $currentUser['username'] ?? ''));
            $sessionDesig = trim((string)($currentUser['designation'] ?? $currentUser['role'] ?? ''));
            $sessionOffice= trim((string)($currentUser['office_name'] ?? $currentUser['office'] ?? ''));

            // Parse issuer info stored in Image column
            $imageStr    = trim((string)($certs[0]['issuer_info'] ?? ''));
            $issuerName  = '';
            $issuerDesig = '';
            $issuerFrom  = '';

            if ($imageStr && preg_match('/Issued by:\s*(.+?)(?:\s*\((.+?)\))?\s*\|(?:\s*Office:\s*(.+?)\s*\|)?\s*Mob:.*?\|\s*on\s*(.+)/i', $imageStr, $m)) {
                $issuerName  = trim($m[1]);
                $issuerDesig = !empty($m[2]) ? trim($m[2]) : '';
                if (!empty($m[3])) {
                    $issuerFrom = trim($m[3]);
                }
            }

            if (!$issuerName)  $issuerName  = $sessionName;
            if (!$issuerDesig) $issuerDesig = $sessionDesig;
            if (!$issuerFrom)  $issuerFrom  = $sessionOffice;

            // Full Issued By string for email
            $issuedByVal  = $issuerName . ($issuerDesig ? " ({$issuerDesig})" : '');
            $issuedByFull = htmlspecialchars($issuedByVal ?: ($certs[0]['issued_by'] ?? 'N/A'));
            $issuerFromEsc = htmlspecialchars($issuerFrom ?: ($certs[0]['current_location'] ?? 'N/A'));

            $issueDate = date('d-m-Y');
            $issueTime = date('h:i A');

            // Collect emails for 4 stakeholders
            $receiverEmail = $_SESSION['last_receiver_email'] ?? $itgkEmail;
            $recipients = array_unique(array_filter([
                'softtechseva@gmail.com',
                $receiverEmail,
                $itgkEmail,
                $currentUser['email'] ?? null
            ], function ($email) {
                return $email && filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No valid stakeholder emails found.']);
                return;
            }

            // Enterprise Level Subject Line
            $subject = "Certificate Issued ITGK - {$firstItgk} {$itgkName} {$txnId} {$issueDate}";

            $baseUrl = (getenv('APP_URL') ?: 'http://localhost/certificate') . '/';
            $logoPath = 'D:/xampp/htdocs/certificate/assets/img/logo-black.jpg';
            $logoRkclUrl  = 'https://banner2.cleanpng.com/20180815/vph/2d8115343fb6430696c56a87cc2a6523.webp';
            $verifyUrl = $baseUrl . 'verify/transaction?id=' . $txnId;
            $logoSrc = file_exists($logoPath) ? 'cid:softtech_logo' : 'https://softtechsso.com/public/assets/img/logo.png';

            // Build table rows with full inline CSS (for email clients)
            $tableRows = '';
            $rowBg = true;
            foreach ($certs as $c) {
                $bg = $rowBg ? '#ffffff' : '#f8fafc';
                $rowBg = !$rowBg;
                $tableRows .= "<tr style='background:{$bg};'>"
                    . "<td style='padding:6px 10px;border:1px solid #e2e8f0;font-size:12px;'>"
                    . "<strong style='color:#0f172a;'>" . htmlspecialchars((string)($c['course_name'] ?? '-')) . "</strong>"
                    . "<br><span style='font-size:10px;color:#64748b;'>" . htmlspecialchars((string)($c['exam_name'] ?? '-')) . "</span></td>"
                    . "<td style='padding:6px 10px;border:1px solid #e2e8f0;text-align:center;font-weight:700;color:#0f172a;font-size:12px;'>" . (int)($c['pass'] ?? 0) . "</td>"
                    . "<td style='padding:6px 10px;border:1px solid #e2e8f0;text-align:center;font-size:12px;'>" . htmlspecialchars((string)($c['packet_no'] ?? '-')) . "</td>"
                    . "<td style='padding:6px 10px;border:1px solid #e2e8f0;font-size:12px;'>" . htmlspecialchars((string)($c['cert_no_from'] ?? '-')) . "</td>"
                    . "<td style='padding:6px 10px;border:1px solid #e2e8f0;font-size:12px;'>" . htmlspecialchars((string)($c['cert_no_to'] ?? '-')) . "</td>"
                    . "<td style='padding:6px 10px;border:1px solid #e2e8f0;text-align:center;font-weight:700;color:#10b981;font-size:12px;'>Issued</td>"
                    . "<td style='padding:6px 10px;border:1px solid #e2e8f0;font-size:11px;color:#64748b;'>" . htmlspecialchars((string)($c['remark'] ?? '-')) . "</td>"
                    . "</tr>";
            }

            // Build email body using 100% inline CSS (works in Gmail, Outlook, Yahoo)
            $emailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Certificate Issue Acknowledgement</title>
</head>
<body style="margin:0;padding:20px 10px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#334155;">

<!-- Outer Wrapper -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:800px;margin:0 auto;">
<tr><td>

<!-- HEADER -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;border-radius:6px 6px 0 0;border:1px solid #e2e8f0;border-bottom:none;">
<tr>
  <td style="padding:10px 15px;width:90px;vertical-align:middle;">
    <img src="{$logoSrc}" alt="Softtech Logo" height="60" style="display:block;height:60px;object-fit:contain;">
  </td>
  <td style="padding:10px;text-align:center;vertical-align:middle;">
    <div style="font-size:18px;font-weight:900;color:#0f172a;letter-spacing:0.5px;margin-bottom:2px;">SOFTTECH MULTI SERVICE PVT. LTD.</div>
    <div style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">RKCL SP, Emitra LSP</div>
    <div style="font-size:13px;font-weight:700;color:#1e3a8a;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Certificate Issue Acknowledgement</div>
    <span style="display:inline-block;background:#10b981;color:#fff;font-size:11px;font-weight:700;padding:3px 14px;border-radius:4px;text-transform:uppercase;">&#10003; Issued</span>
  </td>
  <td style="padding:10px 15px;width:80px;vertical-align:middle;text-align:right;">
    <img src="{$logoRkclUrl}" alt="RKCL Logo" height="55" style="display:block;height:55px;object-fit:contain;">
  </td>
</tr>
</table>

<!-- STATUS BAR -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-bottom:none;">
<tr>
  <td style="padding:8px 15px;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="width:20%;padding:4px 6px;vertical-align:top;">
        <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:2px;">Transaction ID</div>
        <div style="font-size:11px;font-weight:700;color:#1e293b;">{$txnId}</div>
      </td>
      <td style="width:20%;padding:4px 6px;vertical-align:top;">
        <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:2px;">Issue Date</div>
        <div style="font-size:11px;font-weight:700;color:#1e293b;">{$issueDate}</div>
      </td>
      <td style="width:20%;padding:4px 6px;vertical-align:top;">
        <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:2px;">Issue Time</div>
        <div style="font-size:11px;font-weight:700;color:#1e293b;">{$issueTime}</div>
      </td>
      <td style="width:20%;padding:4px 6px;vertical-align:top;">
        <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:2px;">Issued By</div>
        <div style="font-size:11px;font-weight:700;color:#1e293b;">{$issuedByFull}</div>
      </td>
      <td style="width:20%;padding:4px 6px;vertical-align:top;">
        <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:2px;">Issued From</div>
        <div style="font-size:11px;font-weight:700;color:#1e293b;">{$issuerFromEsc}</div>
      </td>
    </tr>
    </table>
  </td>
</tr>
</table>

<!-- CONTENT ROW: Recipient + Verification -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-top:none;border-bottom:none;">
<tr>
  <!-- Recipient / ITGK Card -->
  <td style="width:65%;vertical-align:top;border-right:1px solid #e2e8f0;">
    <div style="background:#1e3a8a;color:#fff;font-weight:700;font-size:11px;padding:5px 10px;text-transform:uppercase;letter-spacing:0.5px;">Recipient / ITGK Details</div>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fff;">
      <tr>
        <td style="padding:5px 8px;width:50%;vertical-align:top;">
          <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;">IT GK Name</div>
          <div style="font-size:12px;font-weight:700;color:#1e293b;">{$itgkName}</div>
        </td>
        <td style="padding:5px 8px;width:50%;vertical-align:top;">
          <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;">IT GK Code</div>
          <div style="font-size:12px;font-weight:700;color:#1e293b;">{$firstItgk}</div>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="padding:5px 8px;vertical-align:top;">
          <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;">Address</div>
          <div style="font-size:12px;font-weight:700;color:#1e293b;">{$itgkAddress}</div>
        </td>
      </tr>
      <tr>
        <td style="padding:5px 8px;vertical-align:top;">
          <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;">District</div>
          <div style="font-size:12px;font-weight:700;color:#1e293b;">{$itgkDistrict}</div>
        </td>
        <td style="padding:5px 8px;vertical-align:top;">
          <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;">Receiver Name</div>
          <div style="font-size:12px;font-weight:700;color:#1e293b;">{$receiverName}</div>
        </td>
      </tr>
      <tr>
        <td style="padding:5px 8px;vertical-align:top;">
          <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;">Email</div>
          <div style="font-size:12px;font-weight:700;color:#1e293b;">{$itgkEmail}</div>
        </td>
        <td style="padding:5px 8px;vertical-align:top;">
          <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;">Receiver Mobile</div>
          <div style="font-size:12px;font-weight:700;color:#1e293b;">{$receiverMob}</div>
        </td>
      </tr>
    </table>
  </td>
  <!-- Verification Panel -->
  <td style="width:35%;vertical-align:top;background:#f0fdf4;text-align:center;padding:12px 10px;">
    <div style="color:#166534;font-weight:800;font-size:11px;text-transform:uppercase;margin-bottom:6px;">&#10003; Verification</div>
    <div style="font-size:10px;color:#475569;margin-bottom:8px;">Scan QR Code to verify this transaction online.</div>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={$verifyUrl}" width="100" height="100" alt="QR Code" style="border:1px solid #cbd5e1;padding:2px;background:#fff;border-radius:4px;display:block;margin:0 auto 8px;">
    <a href="{$verifyUrl}" style="color:#2563eb;font-weight:700;font-size:10px;text-decoration:none;display:block;margin-bottom:4px;">Verify Online</a>
    <div style="color:#1e3a8a;font-size:9px;word-break:break-all;margin-bottom:4px;">{$verifyUrl}</div>
    <div style="color:#64748b;font-size:9px;">Ref No: RKCL-{$txnId}</div>
  </td>
</tr>
</table>

<!-- CERTIFICATE TABLE -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-top:none;border-bottom:none;">
<tr>
  <td style="padding:10px 15px;background:#fff;">
    <div style="font-weight:700;font-size:11px;text-transform:uppercase;color:#1e3a8a;margin-bottom:8px;">Certificate Details</div>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-size:12px;">
    <thead>
      <tr style="background:#1e3a8a;">
        <th style="padding:5px 10px;color:#fff;font-weight:700;text-align:left;border:1px solid #1e3a8a;font-size:10px;text-transform:uppercase;">Course / Exam Name</th>
        <th style="padding:5px 10px;color:#fff;font-weight:700;text-align:center;border:1px solid #1e3a8a;font-size:10px;text-transform:uppercase;">Pass</th>
        <th style="padding:5px 10px;color:#fff;font-weight:700;text-align:center;border:1px solid #1e3a8a;font-size:10px;text-transform:uppercase;">Packet No</th>
        <th style="padding:5px 10px;color:#fff;font-weight:700;text-align:left;border:1px solid #1e3a8a;font-size:10px;text-transform:uppercase;">Cert No. From</th>
        <th style="padding:5px 10px;color:#fff;font-weight:700;text-align:left;border:1px solid #1e3a8a;font-size:10px;text-transform:uppercase;">Cert No. To</th>
        <th style="padding:5px 10px;color:#fff;font-weight:700;text-align:center;border:1px solid #1e3a8a;font-size:10px;text-transform:uppercase;">Status</th>
        <th style="padding:5px 10px;color:#fff;font-weight:700;text-align:left;border:1px solid #1e3a8a;font-size:10px;text-transform:uppercase;">Remark</th>
      </tr>
    </thead>
    <tbody>
      {$tableRows}
      <tr style="background:#f1f5f9;">
        <td style="padding:5px 10px;border:1px solid #e2e8f0;text-align:right;font-weight:800;color:#0f172a;font-size:12px;">TOTAL</td>
        <td style="padding:5px 10px;border:1px solid #e2e8f0;text-align:center;font-weight:800;color:#0f172a;font-size:13px;">{$totalPass}</td>
        <td colspan="5" style="padding:5px 10px;border:1px solid #e2e8f0;"></td>
      </tr>
    </tbody>
    </table>
  </td>
</tr>
</table>

<!-- METADATA ROW -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-bottom:none;">
<tr>
  <td style="padding:6px 15px;font-size:10px;color:#64748b;">
    Generated On: {$issueDate} {$issueTime} &nbsp;|&nbsp; Generated By: System &nbsp;|&nbsp; Version: v2.1.0 &nbsp;|&nbsp; System Generated Acknowledgement
  </td>
</tr>
</table>

<!-- DARK BANNER -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0f172a;border:1px solid #0f172a;border-top:none;border-bottom:none;">
<tr>
  <td style="padding:10px 15px;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="width:45%;vertical-align:top;padding-right:10px;border-right:1px solid #334155;">
        <div style="font-size:10px;color:#e2e8f0;line-height:1.4;"><strong style="color:#f59e0b;">&#9432; Important Note</strong><br>This acknowledgement is digitally generated and does not require physical signature.</div>
      </td>
      <td style="width:35%;vertical-align:top;padding:0 10px;border-right:1px solid #334155;">
        <div style="font-size:10px;color:#cbd5e1;line-height:1.8;">
          <a href="https://www.softtechseva.com" style="color:#60a5fa;text-decoration:none;">www.softtechseva.com</a><br>
          <a href="mailto:softtechseva@gmail.com" style="color:#60a5fa;text-decoration:none;">softtechseva@gmail.com</a><br>
          +91 9983750284
        </div>
      </td>
      <td style="width:20%;vertical-align:middle;padding-left:10px;text-align:right;">
        <div style="font-weight:700;font-style:italic;font-size:10px;color:#94a3b8;line-height:1.3;">Technology | Trust | Service<br><span style="font-size:9px;font-weight:normal;">Empowering Digitally, Enriching Lives.</span></div>
      </td>
    </tr>
    </table>
  </td>
</tr>
</table>

<!-- SECURITY DISCLAIMER -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fff0f0;border:1px solid #fecaca;border-top:none;border-bottom:none;">
<tr>
  <td style="padding:10px 15px;">
    <div style="font-size:10px;color:#7f1d1d;line-height:1.5;">
      <strong>&#128737; CONFIDENTIALITY &amp; SECURITY NOTICE:</strong><br>
      This is an automated enterprise email. If you received this in error, delete it immediately. It may contain sensitive information. Do not click links or scan QR codes if you do not trust the source. This system operates under strict anti-spam and security protocols.
    </div>
  </td>
</tr>
</table>

<!-- FOOTER -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 6px 6px;">
<tr>
  <td style="padding:8px 15px;text-align:center;font-size:11px;color:#475569;">
    Developed By: <a href="http://rakshaeservices.co.in/" style="color:#3b82f6;text-decoration:none;"><strong>Raksha E Services</strong></a> &nbsp;|&nbsp;
    Developer: <strong>LOVEJEET SINGH BHATI (+91 94615838757)</strong> &nbsp;|&nbsp;
    e-Mail: <strong>rakshaeservices@gmail.com</strong>
  </td>
</tr>
</table>

</td></tr>
</table>

</body>
</html>
HTML;

            $emailService = new \App\Services\EmailService();
            $queuedCount = 0;

            foreach ($recipients as $email) {
                if ($emailService->enqueue($email, $subject, $emailBody, true)) {
                    $queuedCount++;
                }
            }

            // Trigger non-blocking background queue runner HTTP request
            $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/certificate', '/');
            $cronUrl = $appUrl . '/cron/process-email-queue';
            
            $ch = curl_init($cronUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT_MS => 500, // Non-blocking: wait only 500ms max
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            @curl_exec($ch);
            @curl_close($ch);

            echo json_encode([
                'success' => true, 
                'message' => "Acknowledgement email queued for {$queuedCount} stakeholder(s) and sending in background!"
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to queue email: ' . $e->getMessage()]);
        }
    }
    
    

    public function verifyTransaction(): void
    {
        $txnId = trim((string)($_GET['id'] ?? ''));
        if ($txnId === '') {
            http_response_code(400);
            echo 'Transaction ID is required.';
            return;
        }

        // Fetch all certificates
        $sheetService  = new \App\Services\GoogleSheetService();
        $sheetId       = $sheetService->getCertificateSheetId();
        $sheetRange    = $sheetService->getCertificateRange();
        $sheetData     = $sheetService->fetchParsedSheet($sheetId, $sheetRange);
        $rawRows       = $sheetData['rows'] ?? [];

        // STRATEGY: Extract date from txnId itself (format: ISSUE-YYYYMMDD-HASH)
        // Group ALL ISSUED rows by ITGK code (Image column may be empty).
        // For each group, compute hash using same formula as acknowledgement():
        //   md5($itgkCode . implode(',', sorted_numericIds))
        // Try matching with both: Image date AND date from txnId itself.

        // Step 1: Extract the date portion from txnId
        $txnDateYmd = '';
        if (preg_match('/^ISSUE-(\d{8})-[A-Z0-9]{6}$/i', $txnId, $txnMatch)) {
            $txnDateYmd = $txnMatch[1]; // e.g. "20260729"
        }

        // Step 2: Group ALL ISSUED rows by ITGK code
        $groups = [];
        foreach ($rawRows as $idx => $r) {
            $status = strtoupper(trim((string)($r['STATUS'] ?? $r['Status'] ?? '')));
            if ($status !== 'ISSUED') continue;
            $itgkCode = trim((string)($r['ITGK CODE'] ?? $r['ITGK Code'] ?? ''));
            if ($itgkCode === '') continue;

            $image = trim((string)($r['Image'] ?? $r['W'] ?? ''));
            $imageDateYmd = '';
            if ($image && preg_match('/on\s+(\d{2})\/(\d{2})\/(\d{4})/i', $image, $dm)) {
                $imageDateYmd = $dm[3] . $dm[2] . $dm[1];
            }

            $rowId        = (string)($r['S. No.'] ?? ($idx + 1));
            $rowNumericId = (int)preg_replace('/^certificate\s*/i', '', $rowId);
            if ($rowNumericId <= 0) continue;

            if (!isset($groups[$itgkCode])) {
                $groups[$itgkCode] = [
                    'itgkCode'   => $itgkCode,
                    'dateYmd'    => $imageDateYmd ?: $txnDateYmd,
                    'certs'      => [],
                    'firstImage' => $image
                ];
            }
            $groups[$itgkCode]['certs'][] = [
                'numericId' => $rowNumericId,
                'row'       => $r
            ];
        }

        // Step 3: Find the matching group by computing hash
        // Try both the Image date AND the txnId date (handles empty Image column)
        $matchedGroup = null;
        $matchedCerts = [];
        foreach ($groups as $itgkCode => $g) {
            $ids = array_column($g['certs'], 'numericId');
            sort($ids);
            $hash = strtoupper(substr(md5($g['itgkCode'] . implode(',', $ids)), 0, 6));

            // Try with Image date
            if ($g['dateYmd'] !== '') {
                if ('ISSUE-' . $g['dateYmd'] . '-' . $hash === $txnId) {
                    $matchedGroup = $g;
                    $matchedCerts = $g['certs'];
                    break;
                }
            }

            // Fallback: try with date from txnId (for certs with empty Image column)
            if ($txnDateYmd !== '' && $txnDateYmd !== $g['dateYmd']) {
                if ('ISSUE-' . $txnDateYmd . '-' . $hash === $txnId) {
                    $matchedGroup = $g;
                    $matchedGroup['dateYmd'] = $txnDateYmd;
                    $matchedCerts = $g['certs'];
                    break;
                }
            }
        }
        
        if (!$matchedGroup) {
            $this->view('pages/certificate/verify_error', ['txnId' => $txnId], false);
            return;
        }

        // We found a match! Fetch ITGK Master details
        $itgkEmail = '';
        $itgkName = 'N/A';
        $itgkAddress = 'N/A';
        $itgkDistrict = 'N/A';
        $itgkMobile = 'N/A';
        
        $itgkMasterId = $sheetService->getItgkMasterSheetId();
        $itgkMasterRange = $sheetService->getItgkMasterRange();
        $itgkMasterData = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
        $itgkRows = $itgkMasterData['rows'] ?? [];

        foreach ($itgkRows as $itgkRow) {
            $code = strtolower(trim((string)($itgkRow['ITGK CODE'] ?? $itgkRow['ITGK Code'] ?? '')));
            if (strcasecmp($code, $matchedGroup['itgkCode']) === 0) {
                $itgkEmail    = trim((string)($itgkRow['Email'] ?? $itgkRow['EMAIL'] ?? ''));
                $itgkName     = trim((string)($itgkRow['ITGK Name'] ?? $itgkRow['ITGK NAME'] ?? $itgkRow['Name'] ?? 'N/A'));
                $itgkAddress  = trim((string)($itgkRow['Address'] ?? $itgkRow['ADDRESS'] ?? 'N/A'));
                $itgkDistrict = trim((string)($itgkRow['District'] ?? $itgkRow['DISTRICT'] ?? 'N/A'));
                $itgkMobile   = trim((string)($itgkRow['Mobile'] ?? $itgkRow['MOBILE'] ?? $itgkRow['Mobile No.'] ?? 'N/A'));
                break;
            }
        }
        
        $certsFormatted = [];
        foreach ($matchedCerts as $cData) {
            $r = $cData['row'];
            $certsFormatted[] = [
                'course_name'  => $r['Course Name'] ?? '',
                'exam_name'    => $r['EXAM'] ?? $r['Exam Name'] ?? '',
                'packet_no'    => $r['Packet No.'] ?? $r['Packet No'] ?? '',
                'cert_no_from' => $r['Certificate No. From'] ?? $r['Cert No From'] ?? '',
                'cert_no_to'   => $r['Certificate No. To'] ?? $r['Cert No To'] ?? '',
                'pass'         => (int)($r['PASS'] ?? $r['Pass'] ?? 0)
            ];
        }
        
        // Extract issuer details
        $issuerName = 'N/A';
        $issuerFrom = 'N/A';
        $issueDateStr = '';
        if (preg_match('/Issued by:\s*(.+?)\s*\((.+?)\)\s*\|\s*Mob:.*?\|\s*on\s*(.+)/i', $matchedGroup['firstImage'], $m)) {
            $issuerName = trim($m[1]);
            $issuerFrom = trim($m[2]);
            $issueDateStr = trim($m[3]);
        }
        
        $this->view('pages/certificate/verify', [
            'txnId' => $txnId,
            'itgkCode' => $matchedGroup['itgkCode'],
            'itgkName' => $itgkName,
            'itgkAddress' => $itgkAddress,
            'itgkDistrict' => $itgkDistrict,
            'itgkMobile' => $itgkMobile,
            'issuerName' => $issuerName,
            'issuerFrom' => $issuerFrom,
            'issueDateStr' => $issueDateStr,
            'certs' => $certsFormatted,
            'totalPass' => array_sum(array_column($certsFormatted, 'pass'))
        ], false);
    }

    public function logVerification(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $txnId = trim((string)($input['txn_id'] ?? ''));
        $visitorName = trim((string)($input['visitor_name'] ?? 'Anonymous'));
        $visitorMob = trim((string)($input['visitor_mobile'] ?? 'N/A'));
        
        if (!$txnId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Txn ID missing']);
            return;
        }

        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $timestamp = date('d-m-Y H:i:s');

        try {
            $emailService = new \App\Services\EmailService();
            $mail = $emailService->getMailerInstance();
            $mail->addAddress('softtechseva@gmail.com');
            $mail->isHTML(true);
            $mail->Subject = "Verification Accessed - Txn: {$txnId}";
            
            $emailBody = "
            <!DOCTYPE html>
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #2563eb; margin-top: 0;'>Verification Attempt Logged</h2>
                    <p>A user has accessed the verification page for Transaction <strong>{$txnId}</strong>.</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px;'>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Visitor Name:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$visitorName}</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Visitor Mobile:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$visitorMob}</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Device / User Agent:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$userAgent}</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>IP Address:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$ipAddress}</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Timestamp:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$timestamp}</td></tr>
                    </table>
                    <p style='color: #64748b; font-size: 11px; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 10px;'>SoftSam Certificate Security System</p>
                </div>
            </body>
            </html>";

            $mail->Body = $emailBody;
            $mail->send();

            echo json_encode(['success' => true, 'message' => 'Logged successfully.']);
        } catch (\Exception $e) {
            \App\Helpers\Logger::error('Failed to send verification log email', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Failed to send log email: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function isApiRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
            || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
    }

    private function sanitizeCertificateData(array $input): array
    {
        return [
            'course_name'     => htmlspecialchars(trim($input['course_name']     ?? '')),
            'receiving_date'  => $input['receiving_date']  ?? date('Y-m-d'),
            'exam_name'       => htmlspecialchars(trim($input['exam_name']       ?? '')),
            'exam_date'       => !empty($input['exam_date']) ? $input['exam_date'] : ($input['receiving_date'] ?? date('Y-m-d')),
            'itgk_code'       => htmlspecialchars(trim($input['itgk_code']       ?? '')),
            'district'        => htmlspecialchars(trim($input['district']        ?? '')),
            'absent'          => (int)($input['absent']      ?? 0),
            'fail'            => (int)($input['fail']        ?? 0),
            'pass'            => (int)($input['pass']        ?? 0),
            'ufm'             => (int)($input['ufm']         ?? 0),
            'grand_total'     => (int)($input['grand_total'] ?? 0),
            'packet_no'       => htmlspecialchars(trim($input['packet_no']       ?? '')),
            'cert_no_from'    => htmlspecialchars(trim($input['cert_no_from']    ?? '')),
            'cert_no_to'      => htmlspecialchars(trim($input['cert_no_to']      ?? '')),
            'current_location' => htmlspecialchars(trim($input['current_location'] ?? '')),
            'status'          => htmlspecialchars(trim($input['status']          ?? 'Not Received')),
            'remark'          => htmlspecialchars(trim($input['remark']          ?? '')),
            'created_by'      => $this->getCurrentUser()['id'] ?? null,
        ];
    }

    protected function validateCsrf(): void
    {
        if (!Csrf::verify()) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
            exit;
        }
    }

    /** Returns true if CSRF token is valid, false otherwise (used in methods that need JSON 403) */
    protected function verifyCsrf(): bool
    {
        return Csrf::verify();
    }
}
