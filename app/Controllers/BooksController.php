<?php

/**
 * BooksController - Books Management & ITGK Book Issue Controller
 *
 * Data source: Google Sheets (Book_Issue tab).
 * Provides full features for ITGK Book Issue & Books Inventory Management.
 *
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GoogleSheetService;
use App\Helpers\Logger;

class BooksController extends BaseController
{
    /**
     * Display Books Management / ITGK Book Issue List
     */
    public function index(): void
    {
        $this->requireAuth();

        $books = [];
        $error = null;

        try {
            $sheetService = new GoogleSheetService();
            $sheetId      = $sheetService->getBooksSheetId();
            $tab          = $sheetService->getBooksTab();
            $rawSheetRows = $sheetService->fetchSheet($sheetId, $tab);
            $totalRows = count($rawSheetRows);

            // Row 0 is header row; data rows start at index 1 (Sheet Row 2)
            for ($i = 1; $i < $totalRows; $i++) {
                $r = $rawSheetRows[$i];
                if (empty(array_filter($r, fn($v) => trim((string)$v) !== ''))) {
                    continue; // Skip blank rows
                }

                // Soft delete check if column Y (idx 24) is_deleted exists
                $isDeleted = strtoupper(trim((string)($r[24] ?? '')));
                if ($isDeleted === 'YES' || $isDeleted === 'TRUE' || $isDeleted === '1') {
                    continue;
                }

                $code = trim((string)($r[6] ?? ''));
                $name = trim((string)($r[7] ?? ''));
                $actualSheetRow = $i + 1; // 1-based sheet row number

                $books[] = [
                    'id'               => $i,
                    'sheet_row'        => $actualSheetRow,
                    'key'              => trim((string)($r[0] ?? '')),
                    'year'             => trim((string)($r[1] ?? '')),
                    'issue_date'       => trim((string)($r[2] ?? '')),
                    'course_name'      => trim((string)($r[5] ?? 'RS-CIT')),
                    'itgk_code'        => $code,
                    'itgk_name'        => $name ?: ($code ? 'ITGK ' . $code : '-'),
                    'txn_type'         => trim((string)($r[12] ?? 'Issued')),
                    'issued_from'      => trim((string)($r[13] ?? 'Main Office')),
                    'quantity'         => (int)($r[14] ?? 0),
                    'receiver_name'    => trim((string)($r[16] ?? '')),
                    'receiver_mobile'  => trim((string)($r[17] ?? '')),
                    'email'            => trim((string)($r[18] ?? '')),
                    'remark'           => trim((string)($r[19] ?? '')),
                    'doc_link'         => trim((string)($r[21] ?? '')),
                    'medium'           => trim((string)($r[22] ?? 'Hindi')),
                    'issuer_name'      => trim((string)($r[23] ?? '')),
                    'status'           => 'ISSUED',
                ];
            }
            $books = array_reverse($books); // show newest first
        } catch (\Exception $e) {
            Logger::error('Failed to fetch Book Issue list from Google Sheet', ['error' => $e->getMessage()]);
            $error = $e->getMessage();
        }

        // Calculate full stats matching standalone app
        $issuedCount   = 0;
        $transferCount = 0;
        $receivedCount = 0;

        foreach ($books as $b) {
            $type = strtolower(trim((string)$b['txn_type']));
            $qty  = (int)$b['quantity'];
            if (str_contains($type, 'issue') || $type === 'issued') {
                $issuedCount += $qty;
            } elseif (str_contains($type, 'transfer')) {
                $transferCount += $qty;
            } elseif (str_contains($type, 'receive') || $type === 'received') {
                $receivedCount += $qty;
            } else {
                $issuedCount += $qty;
            }
        }

        // Fetch ITGK Master data for Auto-fill in Form & Filter
        $itgkList = [];
        try {
            $sheetService = new GoogleSheetService();
            $itgkMasterId    = $sheetService->getItgkMasterSheetId();
            $itgkMasterRange = $sheetService->getItgkMasterRange();
            $itgkMasterData  = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
            $itgkMasterRows  = $itgkMasterData['rows'] ?? [];

            foreach ($itgkMasterRows as $ir) {
                $code   = trim((string)($ir['ITGK-CODE']      ?? $ir['ITGK CODE']      ?? $ir['ITGK_CODE'] ?? ''));
                $name   = trim((string)($ir['ITGK Name']      ?? $ir['ITGK NAME']      ?? ''));
                $email  = trim((string)($ir['ITGK Email']     ?? $ir['Email']          ?? $ir['EMAIL']     ?? ''));
                $mobile = trim((string)($ir['ITGK Mobile']    ?? $ir['Mobile']         ?? $ir['MOBILE']    ?? ''));
                if ($code !== '') {
                    $itgkList[] = [
                        'code'   => $code,
                        'name'   => $name,
                        'email'  => $email,
                        'mobile' => $mobile,
                    ];
                }
            }
        } catch (\Exception $exItgk) {
            Logger::warn('Failed to fetch ITGK master for books auto-fill', ['error' => $exItgk->getMessage()]);
        }

        $analytics = [
            'total_transactions' => count($books),
            'total_issued'       => $issuedCount,
            'total_transfered'   => $transferCount,
            'total_received'     => $receivedCount,
        ];

        $this->view('pages/books/list', [
            'title'     => 'ITGK Books Management | ITGK Management System',
            'books'     => $books,
            'analytics' => $analytics,
            'itgkList'  => $itgkList,
            'error'     => $error,
        ]);
    }

    /**
     * Issue New Book Batch to ITGK (Supports Multi-item dynamic courses & auto-fill)
     */
    public function store(): void
    {
        $this->requireRole('SUPERADMIN');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $itgkCode     = trim((string)($input['ITGK CODE'] ?? $input['itgk_code'] ?? ''));
            $itgkName     = trim((string)($input['NAME'] ?? $input['itgk_name'] ?? ''));
            $itgkEmail    = trim((string)($input['ITGK Email'] ?? $input['itgk_email'] ?? ''));
            $itgkMobile   = trim((string)($input['ITGK Mobile'] ?? $input['itgk_mobile'] ?? ''));
            $issuedFrom   = trim((string)($input['Issued From'] ?? $input['issued_from'] ?? 'Main Center'));
            $receiverName = trim((string)($input['Receiver Name'] ?? $input['receiver_name'] ?? ''));
            $receiverMob  = trim((string)($input['Receiver Mobile No.'] ?? $input['receiver_mobile'] ?? ''));
            $emailId      = trim((string)($input['Email ID'] ?? $input['email'] ?? ''));
            $remark       = trim((string)($input['Remark'] ?? $input['remark'] ?? ''));
            $docLink      = trim((string)($input['Merged Document link'] ?? $input['doc_link'] ?? ''));
            $sendEmail    = !empty($input['sendEmailNotify']);
            $issuerName   = trim((string)($input['issuer_name'] ?? ''));
            $issuerMobile = trim((string)($input['issuer_mobile'] ?? ''));
            $issuerOffice = trim((string)($input['issuer_office'] ?? ''));

            $items        = $input['items'] ?? [];

            if (empty($itgkCode)) {
                $this->json(['success' => false, 'message' => 'ITGK Code is required'], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            $issuerName  = $issuerName ?: ($currentUser['name'] ?? 'Admin User');
            $issuerMobile = $issuerMobile ?: ($currentUser['mobile'] ?? '');
            $issuerOffice = $issuerOffice ?: ($currentUser['office_name'] ?? '');
            $issueDate   = date('Y-m-d');
            $currentYear = date('Y');

            // Fallback for single item form submit
            if (empty($items)) {
                $items = [[
                    'txn_type' => trim((string)($input['txn_type'] ?? 'Issued')),
                    'course'   => trim((string)($input['course_name'] ?? 'RSCIT')),
                    'medium'   => trim((string)($input['medium'] ?? 'Hindi')),
                    'qty'      => (int)($input['quantity'] ?? 1),
                ]];
            }

            $sheetService = new GoogleSheetService();
            $sheetId = $sheetService->getBooksSheetId();
            $tab     = $sheetService->getBooksTab();

            $rowsToAppend = [];
            foreach ($items as $item) {
                $txnType = $item['txn_type'] ?? 'Issued';
                $course  = $item['course']   ?? 'RSCIT';
                $medium  = $item['medium']   ?? 'Hindi';
                $qty     = (int)($item['qty'] ?? 1);

                // 24 Columns layout (A to X):
                // A: KEY, B: YEAR, C: Date, D: Page No., E: Pg S. No., F: Course Name,
                // G: ITGK CODE, H: NAME, I: Stock Available, J: Stock JDP, K: Stock OSN, L: Stock JPR,
                // M: Transaction Type, N: Issued From, O: Issued Book, P: Balance,
                // Q: Receiver Name, R: Receiver Mobile No., S: Email ID, T: Remark,
                // U: Signature, V: Merged Document link, W: Medium, X: issuer Name
                $row = array_fill(0, 24, '');
                $row[0]  = 'KEY_' . time() . '_' . rand(100, 999);
                $row[1]  = $currentYear;
                $row[2]  = $issueDate;
                $row[3]  = ''; // Page No.
                $row[4]  = ''; // Pg S. No.
                $row[5]  = $course;
                $row[6]  = $itgkCode;
                $row[7]  = $itgkName;
                $row[8]  = ''; // Stock Available
                $row[9]  = ''; // Stock JDP
                $row[10] = ''; // Stock OSN
                $row[11] = ''; // Stock JPR
                $row[12] = $txnType;
                $row[13] = $issuedFrom;
                $row[14] = $qty;
                $row[15] = ''; // Balance
                $row[16] = $receiverName;
                $row[17] = $receiverMob;
                $row[18] = $emailId;
                $row[19] = $remark;
                $row[20] = ''; // Signature
                $row[21] = $docLink;
                $row[22] = $medium;
                $row[23] = $issuerName;

                $rowsToAppend[] = $row;
            }

            $sheetService->appendRow($sheetId, $tab, $rowsToAppend, 'USER_ENTERED');

            // Send Async Email Receipt to ITGK if checkbox is checked
            if ($sendEmail && !empty($emailId) && filter_var($emailId, FILTER_VALIDATE_EMAIL)) {
                $emailService = new \App\Services\EmailService();
                $subject = "Book Issue Receipt - ITGK {$itgkCode}";
                $body = "
                <h2>Book Issue Transaction Receipt</h2>
                <p>Dear <strong>{$itgkName}</strong> ({$itgkCode}),</p>
                <p>Your book transaction has been recorded successfully on <strong>{$issueDate}</strong>.</p>
                <hr>
                <table style='border-collapse:collapse; width:100%; font-size:13px;'>
                    <tr><td style='padding:4px 0;'><strong>ITGK Center:</strong></td><td>{$itgkName} ({$itgkCode})</td></tr>
                    <tr><td style='padding:4px 0;'><strong>Issued By:</strong></td><td>{$issuerName}</td></tr>
                    <tr><td style='padding:4px 0;'><strong>Issuer Mobile:</strong></td><td>{$issuerMobile}</td></tr>
                    <tr><td style='padding:4px 0;'><strong>Issuer Office:</strong></td><td>{$issuerOffice}</td></tr>
                    <tr><td style='padding:4px 0;'><strong>Receiver:</strong></td><td>{$receiverName} ({$receiverMob})</td></tr>
                    <tr><td style='padding:4px 0;'><strong>Email:</strong></td><td>{$emailId}</td></tr>
                </table>
                <hr>
                <p><strong>Items:</strong></p>
                <ul>";
                foreach ($items as $item) {
                    $body .= "<li>{$item['course']} ({$item['medium']}) - {$item['txn_type']}: {$item['qty']}</li>";
                }
                $body .= "</ul>
                <p>Thank you,<br>SoftSam ITGK Management System</p>";
                $emailService->enqueue($emailId, $subject, $body, true);
            }

            $this->json([
                'success' => true,
                'message' => "Book Transaction saved successfully for ITGK {$itgkCode}!"
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to append book issue entry', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing Book Issue row in Google Sheets
     */
    public function update(): void
    {
        $this->requireRole('SUPERADMIN');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $sheetRow = (int)($input['sheet_row'] ?? 0);

            if ($sheetRow <= 1) {
                $this->json(['success' => false, 'message' => 'Invalid sheet row number for update'], 400);
                return;
            }

            $sheetService = new GoogleSheetService();
            $sheetId = $sheetService->getBooksSheetId();
            $tab     = $sheetService->getBooksTab();

            // Build range: e.g. "new_book_issue!A{row}:X{row}"
            $range = "{$tab}!A{$sheetRow}:X{$sheetRow}";

            // Fetch existing raw row to preserve unchanged cells
            $existingRow = $sheetService->fetchRawRow($sheetId, $range);
            $rowValues = array_pad($existingRow, 24, '');

            // Update editable fields according to Schema (Cols A to X):
            // A: KEY, B: YEAR, C: Date, D: Page No., E: Pg S. No., F: Course Name,
            // G: ITGK CODE, H: NAME, I: Stock Available, J: Stock JDP, K: Stock OSN, L: Stock JPR,
            // M: Transaction Type, N: Issued From, O: Issued Book, P: Balance,
            // Q: Receiver Name, R: Receiver Mobile No., S: Email ID, T: Remark,
            // U: Signature, V: Merged Document link, W: Medium, X: issuer Name
            if (isset($input['year']))            $rowValues[1]  = trim((string)$input['year']);
            if (isset($input['issue_date']))      $rowValues[2]  = trim((string)$input['issue_date']);
            if (isset($input['course_name']))     $rowValues[5]  = trim((string)$input['course_name']);
            if (isset($input['itgk_code']))       $rowValues[6]  = trim((string)$input['itgk_code']);
            if (isset($input['itgk_name']))       $rowValues[7]  = trim((string)$input['itgk_name']);
            if (isset($input['txn_type']))        $rowValues[12] = trim((string)$input['txn_type']);
            if (isset($input['issued_from']))     $rowValues[13] = trim((string)$input['issued_from']);
            if (isset($input['quantity']))        $rowValues[14] = (int)$input['quantity'];
            if (isset($input['receiver_name']))   $rowValues[16] = trim((string)$input['receiver_name']);
            if (isset($input['receiver_mobile'])) $rowValues[17] = trim((string)$input['receiver_mobile']);
            if (isset($input['email']))           $rowValues[18] = trim((string)$input['email']);
            if (isset($input['remark']))          $rowValues[19] = trim((string)$input['remark']);
            if (isset($input['doc_link']))        $rowValues[21] = trim((string)$input['doc_link']);
            if (isset($input['medium']))          $rowValues[22] = trim((string)$input['medium']);
            if (isset($input['issuer_name']))     $rowValues[23] = trim((string)$input['issuer_name']);

            $sheetService->updateSheetRow($sheetId, $range, $rowValues);

            $this->json([
                'success' => true,
                'message' => "Book Transaction row #{$sheetRow} updated successfully!"
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to update book issue entry', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft-delete a Book Issue row by setting column Y (is_deleted) to 'YES'
     */
    public function destroy(): void
    {
        $this->requireRole('SUPERADMIN');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $sheetRow = (int)($input['sheet_row'] ?? 0);

            if ($sheetRow <= 1) {
                $this->json(['success' => false, 'message' => 'Invalid sheet row number for delete'], 400);
                return;
            }

            $sheetService = new GoogleSheetService();
            $sheetId = $sheetService->getBooksSheetId();
            $tab     = $sheetService->getBooksTab();

            // Column Y range for soft delete (Y is column index 24)
            $range = "{$tab}!Y{$sheetRow}";
            $sheetService->updateSheetRow($sheetId, $range, ['YES']);

            $this->json([
                'success' => true,
                'message' => "Book Transaction record soft-deleted successfully!"
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to soft-delete book issue entry', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display Book Issue Acknowledgement Receipt (Enterprise Screen + Print Dual Mode)
     */
    public function acknowledgement(): void
    {
        $this->requireAuth();

        $rowsParam = trim((string)($_GET['sheet_row'] ?? $_GET['id'] ?? ''));
        if (!$rowsParam) {
            http_response_code(400);
            echo 'Missing sheet_row or id parameter.';
            return;
        }

        $rowNums = array_filter(array_map('intval', explode(',', $rowsParam)));
        if (empty($rowNums)) {
            http_response_code(400);
            echo 'Invalid sheet_row parameter.';
            return;
        }

        $sheetService = new GoogleSheetService();
        $sheetId      = $sheetService->getBooksSheetId();
        $tab          = $sheetService->getBooksTab();
        $rawSheetRows = $sheetService->fetchSheet($sheetId, $tab);

        $books = [];
        foreach ($rowNums as $rowNum) {
            $rawIdx = $rowNum - 1; // 0-based array index
            if (isset($rawSheetRows[$rawIdx])) {
                $r = $rawSheetRows[$rawIdx];
                $books[] = [
                    'sheet_row'       => $rowNum,
                    'key'             => trim((string)($r[0] ?? '')),
                    'year'            => trim((string)($r[1] ?? '')),
                    'issue_date'      => trim((string)($r[2] ?? '')),
                    'course_name'     => trim((string)($r[5] ?? 'RS-CIT')),
                    'itgk_code'       => trim((string)($r[6] ?? '')),
                    'itgk_name'       => trim((string)($r[7] ?? '')),
                    'txn_type'        => trim((string)($r[12] ?? 'Issued')),
                    'issued_from'     => trim((string)($r[13] ?? 'Head Office')),
                    'quantity'        => (int)($r[14] ?? 0),
                    'receiver_name'   => trim((string)($r[16] ?? '')),
                    'receiver_mobile' => trim((string)($r[17] ?? '')),
                    'email'           => trim((string)($r[18] ?? '')),
                    'remark'          => trim((string)($r[19] ?? '')),
                    'doc_link'        => trim((string)($r[21] ?? '')),
                    'medium'          => trim((string)($r[22] ?? 'Hindi')),
                    'issuer_name'     => trim((string)($r[23] ?? '')),
                ];
            }
        }

        if (empty($books)) {
            http_response_code(404);
            echo 'Book Issue record(s) not found in Google Sheet.';
            return;
        }

        // Fetch ITGK Master data for ITGK Center Name & Email
        $itgkCode   = trim((string)($books[0]['itgk_code'] ?? ''));
        $itgkMaster = ['name' => '', 'email' => '', 'mobile' => '', 'district' => '', 'address' => ''];
        if ($itgkCode !== '') {
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
        }

        $currentUser  = \App\Services\AuthService::user();
        $sessionName  = trim((string)($currentUser['name'] ?? $currentUser['username'] ?? 'Admin User'));
        $sessionDesig = trim((string)($currentUser['designation'] ?? $currentUser['role'] ?? 'Administrator'));
        $sessionOffice= trim((string)($currentUser['office_name'] ?? $currentUser['office'] ?? 'Head Office'));

        $issuerName = $books[0]['issuer_name'] ?: $sessionName;
        $txnId      = 'BKISSUE-' . date('Ymd') . '-' . strtoupper(substr(md5($itgkCode . implode(',', $rowNums)), 0, 6));

        $this->view('pages/books/acknowledgement', [
            'book'         => $books[0],
            'books'        => $books,
            'itgkMaster'   => $itgkMaster,
            'issuerName'   => $issuerName,
            'issuerEmail'  => $currentUser['email'] ?? '',
            'issuerRole'   => $sessionDesig,
            'issuerFrom'   => $books[0]['issued_from'] ?: $sessionOffice,
            'issuerOffice' => $sessionOffice,
            'issueDate'    => $books[0]['issue_date'] ?: date('d-m-Y'),
            'txnId'        => $txnId,
            'title'        => 'Book Issue Acknowledgement - SoftSam Portal',
        ], false);
    }

    /**
     * Send Email Acknowledgement to Stakeholders (Head Office, Receiver, ITGK, Issuer)
     */
    public function sendAckEmail(): void
    {
        header('Content-Type: application/json');

        if (!$this->verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF validation failed. Please reload page.']);
            return;
        }

        $input     = json_decode(file_get_contents('php://input'), true) ?: [];
        $sheetRows = (array)($input['sheet_rows'] ?? []);
        $txnId     = trim((string)($input['txn_id'] ?? ''));

        if (empty($sheetRows)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No sheet_rows provided.']);
            return;
        }

        try {
            $sheetService = new GoogleSheetService();
            $sheetId      = $sheetService->getBooksSheetId();
            $tab          = $sheetService->getBooksTab();
            $rawSheetRows = $sheetService->fetchSheet($sheetId, $tab);

            $books = [];
            foreach ($sheetRows as $rowNum) {
                $rawIdx = (int)$rowNum - 1;
                if (isset($rawSheetRows[$rawIdx])) {
                    $r = $rawSheetRows[$rawIdx];
                    $books[] = [
                        'sheet_row'       => $rowNum,
                        'key'             => trim((string)($r[0] ?? '')),
                        'year'            => trim((string)($r[1] ?? '')),
                        'issue_date'      => trim((string)($r[2] ?? '')),
                        'course_name'     => trim((string)($r[5] ?? 'RS-CIT')),
                        'itgk_code'       => trim((string)($r[6] ?? '')),
                        'itgk_name'       => trim((string)($r[7] ?? '')),
                        'txn_type'        => trim((string)($r[12] ?? 'Issued')),
                        'issued_from'     => trim((string)($r[13] ?? 'Head Office')),
                        'quantity'        => (int)($r[14] ?? 0),
                        'receiver_name'   => trim((string)($r[16] ?? '')),
                        'receiver_mobile' => trim((string)($r[17] ?? '')),
                        'email'           => trim((string)($r[18] ?? '')),
                        'remark'          => trim((string)($r[19] ?? '')),
                        'medium'          => trim((string)($r[22] ?? 'Hindi')),
                        'issuer_name'     => trim((string)($r[23] ?? '')),
                    ];
                }
            }

            if (empty($books)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Book records not found in Google Sheet.']);
                return;
            }

            $itgkCode      = $books[0]['itgk_code'];
            $receiverEmail = $books[0]['email'];
            $receiverName  = $books[0]['receiver_name'];
            $currentUser   = \App\Services\AuthService::user();
            $issuerEmail   = $currentUser['email'] ?? '';

            // ITGK email from Master
            $itgkEmail = null;
            if ($itgkCode !== '') {
                try {
                    $imId    = $sheetService->getItgkMasterSheetId();
                    $imRange = $sheetService->getItgkMasterRange();
                    $imData  = $sheetService->fetchParsedSheet($imId, $imRange);
                    foreach ($imData['rows'] ?? [] as $ir) {
                        $c = trim((string)($ir['ITGK-CODE'] ?? $ir['ITGK CODE'] ?? $ir['ITGK_CODE'] ?? ''));
                        if ($c !== '' && strcasecmp($c, $itgkCode) === 0) {
                            $itgkEmail = trim((string)($ir['ITGK Email'] ?? $ir['Email'] ?? $ir['EMAIL'] ?? ''));
                            break;
                        }
                    }
                } catch (\Exception $e) { /* non-fatal */ }
            }

            // Stakeholder Email Recipients (Head Office, Receiver, ITGK, Issuer)
            $recipients = array_filter(array_unique([
                'softtechseva@gmail.com',
                $receiverEmail,
                $itgkEmail,
                $issuerEmail
            ]), fn($e) => $e && filter_var($e, FILTER_VALIDATE_EMAIL));

            if (empty($recipients)) {
                echo json_encode(['success' => false, 'message' => 'No valid stakeholder email addresses found.']);
                return;
            }

            $emailService = new \App\Services\EmailService();
            $totalQty = array_sum(array_column($books, 'quantity'));
            $subject  = "Book Issue Acknowledgement - ITGK {$itgkCode} [{$txnId}]";

            $itemsHtml = '';
            foreach ($books as $b) {
                $itemsHtml .= "<tr>
                    <td style='padding:6px; border:1px solid #ddd;'>{$b['year']}</td>
                    <td style='padding:6px; border:1px solid #ddd;'><strong>{$b['course_name']}</strong> ({$b['medium']})</td>
                    <td style='padding:6px; border:1px solid #ddd;'>{$b['txn_type']}</td>
                    <td style='padding:6px; border:1px solid #ddd; text-align:center;'><strong>{$b['quantity']}</strong></td>
                    <td style='padding:6px; border:1px solid #ddd;'>{$b['issued_from']}</td>
                </tr>";
            }

            $emailBody = "
            <!DOCTYPE html>
            <html>
            <body style='font-family: Arial, sans-serif; font-size: 13px; color: #333;'>
                <div style='max-width: 650px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; background: #ffffff;'>
                    <h2 style='color: #1e3a8a; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-top: 0;'>ITGK Book Issue Acknowledgement</h2>
                    <p>Dear Stakeholder,</p>
                    <p>Book transaction <strong>{$txnId}</strong> has been issued and processed successfully.</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold; width: 35%;'>ITGK Center:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$books[0]['itgk_name']} ({$itgkCode})</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Receiver Name:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$receiverName} ({$books[0]['receiver_mobile']})</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Issued By:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$books[0]['issuer_name']}</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Issued Date:</td><td style='padding: 6px; border: 1px solid #ddd;'>{$books[0]['issue_date']}</td></tr>
                        <tr><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold;'>Total Quantity:</td><td style='padding: 6px; border: 1px solid #ddd; font-weight: bold; color: #1e3a8a;'>{$totalQty}</td></tr>
                    </table>
                    <h3>Issued Items</h3>
                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                        <thead>
                            <tr style='background: #f1f5f9;'>
                                <th style='padding:6px; border:1px solid #ddd;'>Year</th>
                                <th style='padding:6px; border:1px solid #ddd;'>Course</th>
                                <th style='padding:6px; border:1px solid #ddd;'>Type</th>
                                <th style='padding:6px; border:1px solid #ddd;'>Qty</th>
                                <th style='padding:6px; border:1px solid #ddd;'>Issued From</th>
                            </tr>
                        </thead>
                        <tbody>{$itemsHtml}</tbody>
                    </table>
                    <p style='color: #64748b; font-size: 11px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;'>SoftSam ITGK Management System — Automated Receipt</p>
                </div>
            </body>
            </html>";

            $sentCount = 0;
            foreach ($recipients as $toEmail) {
                try {
                    $emailService->enqueue($toEmail, $subject, $emailBody, true);
                    $sentCount++;
                } catch (\Exception $exMail) {
                    Logger::warn('Failed sending book ack email to ' . $toEmail, ['error' => $exMail->getMessage()]);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Acknowledgement email sent to {$sentCount} stakeholder(s)!"
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to send book acknowledgement email', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
        }
    }
}
