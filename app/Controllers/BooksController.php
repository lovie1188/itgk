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
            // Fetch book issue rows from SSO API (matches standalone app dataset)
            $ssoApiUrl = 'http://localhost/softtechsso/backend/api/v1/google/sheets?type=book_issue';
            $ch = curl_init($ssoApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);

            $result = json_decode((string)$resp, true);

            if (($result['success'] ?? false) && !empty($result['data']) && count($result['data']) > 1) {
                $rows = array_slice($result['data'], 1); // skip header row
                $rows = array_reverse($rows); // show newest first

                // Column indices from standalone app COL_MAP:
                // 0: Key, 1: YEAR, 2: Date, 5: Course Name, 6: ITGK CODE, 7: NAME,
                // 12: Transaction Type, 13: Issued From, 14: Issued Book, 15: Balance,
                // 16: Receiver Name, 17: Receiver Mobile No., 18: Email ID, 21: Merged Document link, 22: Medium, 23: issuer Name
                foreach ($rows as $idx => $r) {
                    $code = trim((string)($r[6] ?? ''));
                    $name = trim((string)($r[7] ?? ''));
                    
                    $books[] = [
                        'id'              => $idx + 1,
                        'key'             => trim((string)($r[0] ?? '')),
                        'year'            => trim((string)($r[1] ?? '')),
                        'issue_date'      => trim((string)($r[2] ?? '')),
                        'course_name'     => trim((string)($r[5] ?? 'RS-CIT')),
                        'itgk_code'       => $code,
                        'itgk_name'       => $name ?: ($code ? 'ITGK ' . $code : '-'),
                        'txn_type'        => trim((string)($r[12] ?? 'Issued')),
                        'issued_from'     => trim((string)($r[13] ?? 'Main Office')),
                        'quantity'        => (int)($r[14] ?? 0),
                        'balance'         => (int)($r[15] ?? 0),
                        'receiver_name'   => trim((string)($r[16] ?? '')),
                        'receiver_mobile' => trim((string)($r[17] ?? '')),
                        'email'           => trim((string)($r[18] ?? '')),
                        'doc_link'        => trim((string)($r[21] ?? '')),
                        'medium'          => trim((string)($r[22] ?? 'Hindi')),
                        'issuer_name'     => trim((string)($r[23] ?? '')),
                        'status'          => 'ISSUED',
                    ];
                }
            } else {
                // Fallback to GoogleSheetService if API endpoint is unreachable
                $sheetService = new GoogleSheetService();
                $sheetId    = $sheetService->getBooksSheetId();
                $sheetRange = $sheetService->getBooksRange();
                $sheetData  = $sheetService->fetchParsedSheet($sheetId, $sheetRange);
                $rawRows    = $sheetData['rows'] ?? [];

                foreach ($rawRows as $idx => $r) {
                    $books[] = [
                        'id'               => $idx + 1,
                        'year'             => $r['YEAR']       ?? date('Y'),
                        'issue_date'       => $r['Date']       ?? '',
                        'course_name'      => $r['Course Name']?? 'RS-CIT',
                        'itgk_code'        => $r['ITGK CODE']  ?? '',
                        'itgk_name'        => $r['NAME']       ?? '',
                        'txn_type'         => $r['Transaction Type'] ?? 'Issued',
                        'issued_from'      => $r['Issued From'] ?? 'Main Office',
                        'quantity'         => (int)($r['Issued Book'] ?? 0),
                        'balance'          => (int)($r['Balance']  ?? 0),
                        'receiver_name'    => $r['Receiver Name'] ?? '',
                        'receiver_mobile'  => $r['Receiver Mobile No.'] ?? '',
                        'email'            => $r['Email ID']   ?? '',
                        'medium'           => $r['Medium']     ?? 'Hindi',
                        'issuer_name'      => $r['issuer Name']?? '',
                        'doc_link'         => $r['Merged Document link'] ?? '',
                        'status'           => 'ISSUED',
                    ];
                }
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch Book Issue list', ['error' => $e->getMessage()]);
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
                $issuedCount += $qty; // Default
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

                // 24 Columns layout matching COL_MAP:
                // 0: Key, 1: YEAR, 2: Date, 3: Col3, 4: Col4, 5: Course Name, 6: ITGK CODE, 7: NAME,
                // 8: Col8, 9: Col9, 10: Col10, 11: Col11, 12: Transaction Type, 13: Issued From,
                // 14: Issued Book, 15: Balance, 16: Receiver Name, 17: Receiver Mobile No.,
                // 18: Email ID, 19: Col19, 20: Col20, 21: Merged Document link, 22: Medium, 23: issuer Name
                $row = array_fill(0, 24, '');
                $row[0]  = 'KEY_' . time() . '_' . rand(100, 999);
                $row[1]  = $currentYear;
                $row[2]  = $issueDate;
                $row[5]  = $course;
                $row[6]  = $itgkCode;
                $row[7]  = $itgkName;
                $row[12] = $txnType;
                $row[13] = $issuedFrom;
                $row[14] = $qty;
                $row[16] = $receiverName;
                $row[17] = $receiverMob;
                $row[18] = $emailId;
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

            // Also send receipt to ITGK email if available and different from receiver email
            if ($sendEmail && !empty($itgkEmail) && filter_var($itgkEmail, FILTER_VALIDATE_EMAIL) && $itgkEmail !== $emailId) {
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
                </table>
                <hr>
                <p><strong>Items:</strong></p>
                <ul>";
                foreach ($items as $item) {
                    $body .= "<li>{$item['course']} ({$item['medium']}) - {$item['txn_type']}: {$item['qty']}</li>";
                }
                $body .= "</ul>
                <p>Thank you,<br>SoftSam ITGK Management System</p>";
                $emailService->enqueue($itgkEmail, $subject, $body, true);
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
}
