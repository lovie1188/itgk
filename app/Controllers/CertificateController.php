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
                    'image'                => $r['Image'] ?? '',
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

        // Fetch Current Location dropdown options from sheet 'misc', range J2:J18
        $locationOptions = $sheetService->getLocationOptions();

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

            // Certificate sheet columns Aâ€“V (22 cols):
            // A=S.No, B=Course Name, C=DATE, D=EXAM, E=EXAM_DATE_ITGK,
            // F=ITGK CODE, G=DISTRICT, H=ABSENT, I=FAIL, J=PASS, K=UFM,
            // L=Grand Total, M=Packet No., N=Cert No. From, O=Cert No. To,
            // P=Current Location, Q=STATUS, R=Remark,
            // S=Receiver Name, T=Receiver Designation, U=Receiver Mobile Number, V=Image
            $range = "{$tab}!A{$sheetRow}:V{$sheetRow}";

            // Fetch existing row first â€” only modified columns will be overwritten;
            // all other columns remain exactly as they were received from the sheet.
            $existing = $sheetService->fetchRawRow($sheetId, $range);

            // Pad to 22 columns
            while (count($existing) < 22) {
                $existing[] = '';
            }

            // Column map: POST key â†’ 0-indexed column position
            $columnMap = [
                'course_name'     => 1,   // B - Course Name
                'receiving_date'  => 2,   // C - DATE
                'exam_name'       => 3,   // D - EXAM
                'exam_date'       => 4,   // E - EXAM_DATE_ITGK
                'itgk_code'       => 5,   // F - ITGK CODE
                'district'        => 6,   // G - DISTRICT
                'absent'          => 7,   // H - ABSENT
                'fail'            => 8,   // I - FAIL
                'pass'            => 9,   // J - PASS
                'grand_total'     => 11,  // L - Grand Total
                'packet_no'       => 12,  // M - Packet No.
                'cert_no_from'    => 13,  // N - Cert No. From
                'cert_no_to'      => 14,  // O - Cert No. To
                'current_location' => 15,  // P - Current Location
                'status'          => 16,  // Q - STATUS
                'remark'          => 17,  // R - Remark
                'receiver_name'   => 18,  // S - Receiver Name
                'receiver_designation' => 19, // T - Receiver Designation
                'receiver_mobile' => 20,  // U - Receiver Mobile Number
            ];

            $modified = false;
            foreach ($columnMap as $postKey => $colIdx) {
                $newVal  = trim((string)($_POST[$postKey] ?? ''));
                $oldVal  = trim((string)($existing[$colIdx] ?? ''));
                if ($newVal !== $oldVal) {
                    $existing[$colIdx] = $newVal;
                    $modified = true;
                }
            }

            if (!$modified) {
                $this->json(['success' => true, 'message' => 'No changes detected â€” record unchanged.']);
                return;
            }

            $sheetService->updateSheetRow($sheetId, $range, $existing);

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

            // â”€â”€ 1. Build Certificate sheet batchUpdate â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $certUpdates = [];
            foreach ($validSelections as $valid) {
                $existing = $valid['existing'];

                // Pad to 22 columns (Aâ€“V)
                while (count($existing) < 22) $existing[] = '';

                // Update only status + receiver + issuer columns
                $existing[16] = 'ISSUED';                              // Q â€” STATUS
                $existing[17] = $remark;                               // R â€” Remark
                $existing[18] = $receiverName;                         // S â€” Receiver Name
                $existing[19] = $receiverDesig;                        // T â€” Receiver Designation
                $existing[20] = $receiverMob;                          // U â€” Receiver Mobile
                $existing[21] = "Issued by: {$issuerName} ({$issuerDesig}) on {$issueDate}"; // V

                $certUpdates[] = [
                    'range'  => "{$certTab}!A" . $valid['sheet_row'] . ":V" . $valid['sheet_row'],
                    'values' => [$existing],
                ];
            }

            // Single batchUpdate for Certificate sheet
            if (!empty($certUpdates)) {
                $sheetService->batchUpdateRows($certSheetId, $certUpdates);
            }

            // â”€â”€ 2. Update Student_Result (Learner) rows â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // Fetch full Student_Result sheet, find matching rows by
            // ITGK Code + Course Name + Exam Name, batch-update Status col.
            $srData    = $sheetService->fetchParsedSheet($srSheetId, $sheetService->getStudentResultRange());
            $srHeaders = $srData['headers'] ?? [];
            $srRows    = $srData['rows']    ?? [];
            $srStartRow = (int)($srData['startRow'] ?? 2);

            // Build lookup keys from VALID selections (both exact name and extracted date)
            $selectionKeys = [];
            foreach ($validSelections as $valid) {
                $sel = $valid['sel_data'];
                $itgk = strtolower(trim((string)($sel['itgk_code'] ?? '')));
                $course = strtolower(trim((string)($sel['course_name'] ?? '')));
                $exam = strtolower(trim((string)($sel['exam_name'] ?? '')));
                
                $selectionKeys["$itgk|||$course|||$exam"] = true;
                
                if (preg_match('/\((\d{2}-\d{2}-\d{4})\)/', $exam, $m)) {
                    $dateSlash = str_replace('-', '/', $m[1]);
                    $selectionKeys["$itgk|||$course|||$dateSlash"] = true;
                }
            }

            $learnerUpdates = [];
            foreach ($srRows as $rowOffset => $r) {
                $itgk   = strtolower(trim((string)($r['ITGK Code']   ?? $r['ITGK CODE'] ?? '')));
                $course = strtolower(trim((string)($r['Course Name']  ?? '')));
                $exam   = strtolower(trim((string)($r['Exam Name']    ?? $r['exam_name on certificate'] ?? $r['BATCH'] ?? '')));
                $heldDate = str_replace('-', '/', strtolower(trim((string)($r['exam_held_date'] ?? ''))));
                
                $matchFound = false;
                if (isset($selKeys["$itgk|||$course|||$exam"])) {
                    $matchFound = true;
                } elseif ($heldDate !== '' && isset($selKeys["$itgk|||$course|||$heldDate"])) {
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
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                $result = ['success' => false, 'message' => 'Invalid CSRF token'];
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

            // Build row data matching sheet column order
            $headers = ['S. No.', 'Course Name', 'DATE', 'EXAM', 'EXAM_DATE_ITGK', 'ITGK CODE', 'DISTRICT', 'ABSENT', 'FAIL', 'PASS', 'UFM', 'Grand Total', 'Packet No.', 'Cert No. From', 'Cert No. To', 'Current Location', 'STATUS', 'Remark', 'Receiver Name', 'Receiver Designation', 'Receiver Mobile Number', 'Image'];
            $row = [];
            foreach ($headers as $h) {
                $row[] = $sanitized[$h] ?? '';
            }

            $sheetService->appendRow($sheetId, $tab, [$row]);

            $certCount = count(array_filter(
                $sheetService->fetchParsedSheet($sheetId, $sheetService->getCertificateRange())['rows'] ?? [],
                fn($r) => !empty(array_filter($r, fn($v) => trim($v) !== ''))
            ));

            Logger::info('Certificate packet appended to Google Sheet', [
                'itgk_code' => $sanitized['itgk_code'] ?? '',
                'user_id'   => AuthService::id()
            ]);

            $result = ['success' => true, 'message' => 'Certificate Packet Created Successfully in Google Sheet'];
            $this->json($result, 201);
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
                $itgk   = trim((string)($r['ITGK Code'] ?? $r['ITGK CODE'] ?? $r['Statu'] ?? ''));
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

                // Check current status (look for STATUS column)
                $statusColIdx = array_search('STATUS', $certHeaders);
                if ($statusColIdx === false) {
                    $statusColIdx = array_search('Status', $certHeaders);
                }

                if ($statusColIdx !== false) {
                    $currentStatus = trim((string)($row[$certHeaders[$statusColIdx]] ?? 'Available'));
                    if (strcasecmp($currentStatus, 'Issued') === 0 || strcasecmp($currentStatus, 'ISSUED') === 0) {
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

                $idxQ = array_search('STATUS', $certHeaders);
                $idxR = array_search('Remark', $certHeaders);
                $idxS = array_search('Receiver Name', $certHeaders);
                $idxT = array_search('Receiver Designation', $certHeaders);
                $idxU = array_search('Receiver Mobile Number', $certHeaders);
                $idxV = array_search('Image', $certHeaders);
                if ($idxQ !== false) $updateRow[$idxQ] = 'ISSUED';
                if ($idxR !== false) $updateRow[$idxR]  = $remark;
                if ($idxS !== false) $updateRow[$idxS]  = $receiverName;
                if ($idxT !== false) $updateRow[$idxT]  = $receiverDesig;
                if ($idxU !== false) $updateRow[$idxU]  = $receiverMob;
                if ($idxV !== false) $updateRow[$idxV]  = "Issued by: {$issuerName} ({$issuerDesig}) | Mob: {$issuerMobile} | on {$issueDate}";

                $startCol = $this->colIndexToLetter(0);
                $endCol   = $this->colIndexToLetter(count($certHeaders) - 1);
                $range    = "{$certTab}!{$startCol}{$sheetRow}:{$endCol}{$sheetRow}";
                $certUpdates[] = [
                    'range'  => $range,
                    'values' => [$updateRow],
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
                $itgk   = strtolower(trim((string)($row['ITGK CODE'] ?? $row['ITGK Code'] ?? '')));
                $course = strtolower(trim((string)($row['Course Name'] ?? '')));
                $exam   = strtolower(trim((string)($row['EXAM'] ?? $row['Exam Name'] ?? '')));
                
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
                $itgk   = strtolower(trim((string)($row['ITGK CODE'] ?? $row['ITGK Code'] ?? '')));
                $course = strtolower(trim((string)($row['Course Name'] ?? '')));
                $exam   = strtolower(trim((string)($row['EXAM'] ?? $row['Exam Name'] ?? '')));
                $key = "{$itgk}|||{$course}|||{$exam}";
                if ($itgk !== '' && $course !== '' && $exam !== '') {
                    $selKeys[$key] = true;
                }
            }

            $statusColIdx = null;
            foreach ($srHeaders as $ci => $hdr) {
                if (strcasecmp(trim($hdr), 'Status') === 0) {
                    $statusColIdx = $ci;
                    break;
                }
            }

            $learnerUpdates = [];
            foreach ($srRows as $rowOffset => $r) {
                $itgk   = strtolower(trim((string)($r['ITGK Code']   ?? $r['ITGK CODE'] ?? '')));
                $course = strtolower(trim((string)($r['Course Name']  ?? '')));
                $exam   = strtolower(trim((string)($r['Exam Name']    ?? $r['exam_name on certificate'] ?? $r['BATCH'] ?? '')));
                $key    = "{$itgk}|||{$course}|||{$exam}";
                if (!isset($selKeys[$key])) continue;

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

            // â”€â”€ Send Email Notifications to 4 Recipients â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

            // ── Append to Dispatch Register (Certificate Tracker) ──────────
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
            (string)$certsUpdated,                                         // 6. Grand Total
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
                if ($c === $itgkCode) {
                    $itgkMaster = [
                        'name'     => trim((string)($ir['ITGK Name']   ?? $ir['ITGK NAME']   ?? '')),
                        'email'    => trim((string)($ir['ITGK Email']  ?? $ir['Email']        ?? '')),
                        'mobile'   => trim((string)($ir['ITGK Mobile'] ?? $ir['Mobile']       ?? '')),
                        'district' => trim((string)($ir['ITGK District'] ?? $ir['DISTRICT']   ?? '')),
                        'address'  => trim((string)($ir['ITGK Address']  ?? $ir['Address'] ?? $ir['ITGK ADDRESS'] ?? '')),
                    ];

                    break;
                }
            }
        } catch (\Exception $e) { /* non-fatal */ }

        // --- Issuer info from session ---
        $currentUser  = AuthService::user();
        $issuerName   = trim((string)($currentUser['name']  ?? ''));
        $issuerEmail  = trim((string)($currentUser['email'] ?? ''));
        $issuerRole   = trim((string)($currentUser['role']  ?? ''));

        // Parse issuer info stored in Image column: "Issued by: Name (Desig) | Mob: 9xx | on DATE"
        $imageStr    = trim((string)($certs[0]['issuer_info'] ?? ''));
        $issuerFrom  = '';
        $issueDate   = date('d-m-Y');
        if ($imageStr && preg_match('/Issued by:\s*(.+?)\s*\((.+?)\)\s*\|\s*Mob:.*?\|\s*on\s*(.+)/i', $imageStr, $m)) {
            if (!$issuerName) $issuerName = trim($m[1]);
            $issuerFrom = trim($m[2]); // designation as "issued from"
            $issueDate  = trim($m[3]);
        }

        // Issuer office location from session or DB
        $issuerOffice = trim((string)($currentUser['office_name'] ?? ''));

        // Transaction ID
        $txnId = 'ISSUE-' . date('Ymd') . '-' . strtoupper(substr(md5($itgkCode . implode(',', $numericIds)), 0, 6));

        // For backward compat: also expose single $cert
        $cert = $certs[0];

        $this->view('pages/certificate/acknowledgement', [
            'cert'         => $cert,
            'certs'        => $certs,
            'itgkMaster'   => $itgkMaster,
            'issuerName'   => $issuerName,
            'issuerEmail'  => $issuerEmail,
            'issuerRole'   => $issuerRole,
            'issuerFrom'   => $issuerFrom,
            'issuerOffice' => $issuerOffice,
            'issueDate'    => $issueDate,
            'txnId'        => $txnId,
            'title'        => 'Certificate Issue Acknowledgement - SoftSam Portal',
        ]);
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
            $sheetStartRow = (int)($sheetData['startRow'] ?? 1);

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
                        'pass'         => (int)($r['PASS']        ?? $r['Pass']       ?? 0),
                        'grand_total'  => (int)($r['Grand Total'] ?? $r['grand_total'] ?? 0),
                        'packet_no'    => $r['Packet No.']         ?? $r['Packet No'] ?? '',
                        'cert_no_from' => $r['Certificate No. From'] ?? $r['Cert No From'] ?? '',
                        'cert_no_to'   => $r['Certificate No. To']   ?? $r['Cert No To']   ?? '',
                        'receiver_name' => $r['Receiver Name']       ?? '',
                    ];
                }
            }

            if (empty($certs)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Certificates not found in Google Sheet.']);
                return;
            }

            // Fetch ITGK email from ITGK Master
            $firstItgk = trim((string)($certs[0]['itgk_code'] ?? ''));
            $itgkEmail = null;
            if ($firstItgk !== '') {
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
            }

            // Collect emails for 4 stakeholders
            $currentUser = AuthService::user();
            $issuerEmail = $currentUser['email'] ?? null;
            $receiverEmail = $_SESSION['last_receiver_email'] ?? $itgkEmail;

            $recipients = array_unique(array_filter([
                'softtechseva@gmail.com',
                $receiverEmail,
                $itgkEmail,
                $issuerEmail
            ], function ($email) {
                return $email && filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No valid stakeholder emails found.']);
                return;
            }

            // Send emails using EmailService
            $emailService = new \App\Services\EmailService();
            $subject = "Acknowledgement Slip Receipt - SoftSam Portal - Txn: {$txnId}";
            
            // Build table rows
            $tableRows = '';
            foreach ($certs as $index => $c) {
                $tableRows .= "
                <tr>
                    <td style='padding: 6px; border: 1px solid #ddd;'>" . ($index + 1) . "</td>
                    <td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)$c['course_name']) . "</td>
                    <td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)$c['exam_name']) . "</td>
                    <td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)$c['packet_no']) . "</td>
                    <td style='padding: 6px; border: 1px solid #ddd;'>" . htmlspecialchars((string)$c['cert_no_from']) . " - " . htmlspecialchars((string)$c['cert_no_to']) . "</td>
                    <td style='padding: 6px; border: 1px solid #ddd; text-align: center;'>" . htmlspecialchars((string)$c['grand_total']) . "</td>
                </tr>";
            }

            $emailBody = "
            <!DOCTYPE html>
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
                <div style='max-width: 650px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;'>
                    <div style='text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 15px;'>
                        <h2 style='color: #2563eb; margin: 0;'>SoftSam ITGK Certificate Portal</h2>
                        <p style='margin: 4px 0 0 0; color: #64748b; font-size: 13px;'>Acknowledgement Receipt</p>
                    </div>
                    <p>Dear Representative / Stakeholder,</p>
                    <p>An official receipt slip has been generated for your certificate issuance transaction. Details are provided below:</p>
                    
                    <div style='background: #f1f5f9; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px;'>
                        <p style='margin: 0 0 4px 0;'><strong>Transaction ID:</strong> " . htmlspecialchars($txnId) . "</p>
                        <p style='margin: 0 0 4px 0;'><strong>ITGK Code:</strong> " . htmlspecialchars($firstItgk) . "</p>
                        <p style='margin: 0;'><strong>Date of Transaction:</strong> " . date('d-m-Y H:i:s') . "</p>
                    </div>

                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 12px;'>
                        <thead>
                            <tr style='background: #e2e8f0;'>
                                <th style='padding: 6px; border: 1px solid #ddd; text-align: left;'>#</th>
                                <th style='padding: 6px; border: 1px solid #ddd; text-align: left;'>Course</th>
                                <th style='padding: 6px; border: 1px solid #ddd; text-align: left;'>Exam</th>
                                <th style='padding: 6px; border: 1px solid #ddd; text-align: left;'>Packet No</th>
                                <th style='padding: 6px; border: 1px solid #ddd; text-align: left;'>Certificate Nos</th>
                                <th style='padding: 6px; border: 1px solid #ddd; text-align: center;'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$tableRows}
                        </tbody>
                    </table>

                    <p style='font-size: 13px; color: #475569;'>This email serves as an automated receipt. Please verify with the portal or physically count the certificates upon receipt.</p>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                    <p style='color: #64748b; font-size: 11px; text-align: center; margin: 0;'>Sent securely via SoftSam Certificate Management System</p>
                </div>
            </body>
            </html>";

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $settings = $emailService->getSettings();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'];
            $mail->Password = $settings['smtp_pass'];
            
            $encryption = strtolower($settings['smtp_encryption']);
            $mail->SMTPSecure = ($encryption === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)$settings['smtp_port'];
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_name']);
            
            foreach ($recipients as $email) {
                $mail->addAddress($email);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $emailBody;
            $mail->send();

            echo json_encode(['success' => true, 'message' => 'Acknowledgement email sent to 4 stakeholders successfully!']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()]);
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

    private function validateCsrf(): void
    {
        if (!Csrf::verify()) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
            exit;
        }
    }

    /** Returns true if CSRF token is valid, false otherwise (used in methods that need JSON 403) */
    private function verifyCsrf(): bool
    {
        return Csrf::verify();
    }
}
