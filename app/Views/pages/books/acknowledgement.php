<?php
/**
 * Book Issue Acknowledgement Receipt View
 * Dual Mode: Stunning Enterprise Layout on Screen, Clean & Traditional Slip on Print.
 * Email to Stakeholders (Head Office, Receiver, ITGK, Issuer)
 *
 * @package App\Views\pages\books
 */

$book = $book ?? [];
$books = $books ?? [$book];
$itgkMaster = $itgkMaster ?? ['name' => '', 'email' => '', 'mobile' => '', 'district' => '', 'address' => ''];
$issuerName = $issuerName ?? '';
$issuerEmail = $issuerEmail ?? '';
$issuerRole = $issuerRole ?? '';
$issuerFrom = $issuerFrom ?? '';
$issuerOffice = $issuerOffice ?? '';
$issueDate = $issueDate ?? date('d-m-Y');
$txnId = $txnId ?? ('BKISSUE-' . date('Ymd') . '-' . rand(1000, 9999));
$issueTime = date('h:i A');

$itgkName = $itgkMaster['name'] ?: ($book['itgk_name'] ?: ($book['itgk_code'] ?? 'N/A'));
$itgkCode = $book['itgk_code'] ?? 'N/A';
$itgkEmail = $itgkMaster['email'] ?? '';
$receiverName = $book['receiver_name'] ?? 'N/A';
$receiverMob = $book['receiver_mobile'] ?? '';
$receiverEmail = $book['email'] ?? '';

// Totals
$totalQty = array_sum(array_column($books, 'quantity'));

$verifyUrl = (getenv('APP_URL') ?: 'http://localhost/certificate') . '/verify/transaction?id=' . $txnId;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Book Issue Acknowledgement') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= \App\Helpers\Csrf::getToken() ?>">
    <style>
        /* ── Screen Layout (No Print) ── */
        @media screen {
            body {
                background: #dfeff9;
                font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
                font-size: 13px;
                color: #334155;
                padding-bottom: 40px;
            }

            .action-bar {
                background: #0f172a;
                color: #fff;
                padding: 10px 20px;
                display: flex;
                gap: 8px;
                align-items: center;
                position: sticky;
                top: 0;
                z-index: 100;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .action-bar .info-txt {
                font-size: 11px;
                color: #94a3b8;
                margin-left: 6px;
            }

            .enterprise-wrapper {
                max-width: 1020px;
                margin: 10px auto;
                background: #fff;
                border-radius: 6px;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
                border: 1px solid #e2e8f0;
                overflow: hidden;
            }

            /* Corporate Header */
            .ent-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 20px 8px;
                background: #fff;
                border-bottom: 1px solid #f1f5f9;
            }

            .ent-header-logo-left img {
                height: 70px;
                object-fit: contain;
            }

            .ent-header-center {
                text-align: center;
            }

            .ent-header-center h1 {
                font-size: 18px;
                font-weight: 800;
                color: #1e3a8a;
                margin: 0;
                letter-spacing: -0.2px;
            }

            .ent-header-center .tagline {
                font-size: 11px;
                color: #64748b;
                margin: 1px 0 3px;
                font-weight: 600;
            }

            .ent-header-center h2 {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
                margin: 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .badge-issued {
                background: #dcfce7;
                color: #166534;
                font-size: 11px;
                font-weight: 700;
                padding: 2px 10px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                margin-top: 4px;
            }

            .ent-header-logo-right img {
                height: 70px;
                object-fit: contain;
            }

            /* Status Grid */
            .ent-status-grid {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 8px;
                padding: 8px 20px;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
            }

            .status-card {
                background: #fff;
                padding: 6px 10px;
                border-radius: 6px;
                border: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .status-card-icon {
                width: 28px;
                height: 28px;
                border-radius: 6px;
                background: #eff6ff;
                color: #2563eb;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                flex-shrink: 0;
            }

            .status-card-info {
                display: flex;
                flex-direction: column;
                min-width: 0;
            }

            .status-card-label {
                font-size: 10px;
                color: #64748b;
                font-weight: 600;
                text-transform: uppercase;
            }

            .status-card-val {
                font-size: 11px;
                font-weight: 700;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Main Content Layout */
            .ent-content-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                padding: 12px 20px;
            }

            .ent-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                overflow: hidden;
            }

            .ent-card-header {
                background: #f1f5f9;
                padding: 6px 12px;
                font-size: 11px;
                font-weight: 700;
                color: #334155;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .ent-card-body {
                padding: 8px 12px;
            }

            .ent-info-item {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 6px;
            }

            .ent-info-item:last-child {
                margin-bottom: 0;
            }

            .ent-info-icon {
                color: #64748b;
                font-size: 11px;
                margin-top: 2px;
                width: 14px;
                text-align: center;
            }

            .ent-info-lbl {
                font-size: 10px;
                color: #64748b;
                font-weight: 600;
            }

            .ent-info-val {
                font-size: 12px;
                font-weight: 700;
                color: #0f172a;
            }

            .ent-info-sub {
                font-size: 10.5px;
                color: #64748b;
            }

            /* Items Table */
            .ent-table-wrapper {
                padding: 0 20px 12px;
            }

            .ent-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11.5px;
            }

            .ent-table th {
                background: #0f172a;
                color: #fff;
                padding: 6px 10px;
                font-weight: 600;
                text-align: left;
                font-size: 10.5px;
                text-transform: uppercase;
            }

            .ent-table td {
                padding: 6px 10px;
                border-bottom: 1px solid #e2e8f0;
            }

            .ent-table tr:nth-child(even) {
                background: #f8fafc;
            }

            .ent-table tfoot td {
                background: #f1f5f9;
                font-weight: 700;
                border-top: 2px solid #cbd5e1;
                font-size: 11.5px;
            }

            .traditional-slip {
                display: none;
            }
        }

        /* ── Print Layout overrides ── */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
                font-family: Arial, sans-serif !important;
                font-size: 12px !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .traditional-slip {
                display: block !important;
                width: 100%;
                margin: 0 auto;
                padding: 10px;
            }

            .slip-header {
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 8px;
                margin-bottom: 12px;
            }

            .slip-header h2 {
                font-size: 18px;
                font-weight: bold;
                margin: 0;
                text-transform: uppercase;
            }

            .slip-header p {
                font-size: 11px;
                margin: 2px 0 0;
            }

            .slip-title {
                text-align: center;
                font-size: 14px;
                font-weight: bold;
                margin: 10px 0;
                text-transform: uppercase;
                text-decoration: underline;
            }

            .slip-meta-table {
                width: 100%;
                margin-bottom: 12px;
                font-size: 11.5px;
            }

            .slip-meta-table td {
                padding: 3px 0;
                vertical-align: top;
            }

            .slip-items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
                font-size: 11.5px;
            }

            .slip-items-table th,
            .slip-items-table td {
                border: 1px solid #000;
                padding: 5px 8px;
            }

            .slip-items-table th {
                background: #f0f0f0;
                text-align: center;
            }

            .slip-items-table td.ctr {
                text-align: center;
            }

            .issuer-block {
                margin-top: 25px;
                display: flex;
                justify-content: space-between;
                font-size: 11.5px;
            }

            .footer-note {
                margin-top: 15px;
                font-size: 10px;
                text-align: center;
                border-top: 1px solid #ccc;
                padding-top: 5px;
            }

            @page {
                margin: 10mm;
            }
        }
    </style>
</head>

<body>

    <!-- Action Bar (no-print) -->
    <div class="action-bar no-print">
        <a href="<?= BASE_URL ?>books/list" class="btn btn-outline-light btn-sm px-3">
            <i class="fas fa-arrow-left me-1"></i> Back to Books
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-print me-1"></i> Print Receipt
        </button>
        <button id="btnEmail" onclick="sendEmail()" class="btn btn-success btn-sm px-3">
            <i class="fas fa-envelope me-1"></i> Email to Stakeholders
        </button>
        <span class="info-txt" id="emailStatus"></span>
    </div>

    <!-- =================================================== -->
    <!-- 1. ENTERPRISE VIEW (ON SCREEN)                      -->
    <!-- =================================================== -->
    <div class="enterprise-wrapper no-print">
        <!-- Header -->
        <div class="ent-header">
            <div class="ent-header-logo-left">
                <img src="<?= BASE_URL ?>assets/img/logo-black.jpg" alt="Softtech Logo">
            </div>
            <div class="ent-header-center">
                <h1>SOFTTECH MULTI SERVICE PVT. LTD.</h1>
                <p class="tagline">RKCL SP, Emitra LSP</p>
                <h2>ITGK Book Issue Acknowledgement</h2>
                <div>
                    <span class="badge-issued">
                        <i class="fas fa-check-circle"></i> Issued
                    </span>
                </div>
            </div>
            <div class="ent-header-logo-right">
                <img src="<?= BASE_URL ?>assets/img/rkcl-logo.jpg" alt="RKCL Logo">
            </div>
        </div>

        <!-- Status Grid -->
        <div class="ent-status-grid">
            <div class="status-card">
                <div class="status-card-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="status-card-info">
                    <span class="status-card-label">Transaction ID</span>
                    <span class="status-card-val"><?= htmlspecialchars($txnId) ?></span>
                </div>
            </div>
            <div class="status-card">
                <div class="status-card-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="status-card-info">
                    <span class="status-card-label">Issue Date</span>
                    <span class="status-card-val"><?= htmlspecialchars($issueDate) ?></span>
                </div>
            </div>
            <div class="status-card">
                <div class="status-card-icon"><i class="fas fa-clock"></i></div>
                <div class="status-card-info">
                    <span class="status-card-label">Issue Time</span>
                    <span class="status-card-val"><?= htmlspecialchars($issueTime) ?></span>
                </div>
            </div>
            <div class="status-card">
                <div class="status-card-icon"><i class="fas fa-user-tie"></i></div>
                <div class="status-card-info">
                    <span class="status-card-label">Issued By</span>
                    <span class="status-card-val"><?= htmlspecialchars($issuerName ?: 'N/A') ?></span>
                </div>
            </div>
            <div class="status-card">
                <div class="status-card-icon"><i class="fas fa-building-user"></i></div>
                <div class="status-card-info">
                    <span class="status-card-label">Issued From</span>
                    <span class="status-card-val"><?= htmlspecialchars($issuerFrom ?: 'Head Office') ?></span>
                </div>
            </div>
        </div>

        <!-- Content Block -->
        <div class="ent-content-row">
            <!-- Recipient & ITGK Card -->
            <div class="ent-card">
                <div class="ent-card-header">Recipient / ITGK Details</div>
                <div class="ent-card-body">
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-building"></i></div>
                        <div>
                            <div class="ent-info-lbl">ITGK Center</div>
                            <div class="ent-info-val"><?= htmlspecialchars($itgkName) ?></div>
                            <div class="ent-info-sub">Code: <?= htmlspecialchars($itgkCode) ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-user"></i></div>
                        <div>
                            <div class="ent-info-lbl">Receiver Name</div>
                            <div class="ent-info-val"><?= htmlspecialchars($receiverName) ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <div class="ent-info-lbl">Mobile / Email</div>
                            <div class="ent-info-val"><?= htmlspecialchars($receiverMob ?: '-') ?></div>
                            <div class="ent-info-sub"><?= htmlspecialchars($receiverEmail ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Issuer Details Card -->
            <div class="ent-card">
                <div class="ent-card-header">Issuer / Office Details</div>
                <div class="ent-card-body">
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-user-shield"></i></div>
                        <div>
                            <div class="ent-info-lbl">Issuer Name</div>
                            <div class="ent-info-val"><?= htmlspecialchars($issuerName ?: 'N/A') ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-store"></i></div>
                        <div>
                            <div class="ent-info-lbl">Office Location</div>
                            <div class="ent-info-val"><?= htmlspecialchars($issuerFrom ?: 'Head Office') ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-barcode"></i></div>
                        <div>
                            <div class="ent-info-lbl">Document Link</div>
                            <div class="ent-info-val"><?= htmlspecialchars($book['doc_link'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="ent-table-wrapper">
            <table class="ent-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Course Name</th>
                        <th>Medium</th>
                        <th>Transaction Type</th>
                        <th style="text-align: center;">Quantity</th>
                        <th>Issued From</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($b['year'] ?? date('Y'))) ?></td>
                            <td><strong><?= htmlspecialchars((string)($b['course_name'] ?? 'RS-CIT')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($b['medium'] ?? 'Hindi')) ?></td>
                            <td><span class="badge bg-success"><?= htmlspecialchars((string)($b['txn_type'] ?? 'Issued')) ?></span></td>
                            <td style="text-align: center; font-weight: bold;"><?= (int)($b['quantity'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($b['issued_from'] ?? 'Head Office')) ?></td>
                            <td><?= htmlspecialchars((string)($b['remark'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right;">TOTAL QUANTITY</td>
                        <td style="text-align: center; font-weight: bold; font-size: 13px; color: #1e3a8a;"><?= $totalQty ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>


    <!-- =================================================== -->
    <!-- 2. TRADITIONAL PRINT SLIP (FOR WINDOW.PRINT ONLY)   -->
    <!-- =================================================== -->
    <div class="traditional-slip">
        <div class="slip-header">
            <h2>SOFTTECH MULTI SERVICE PVT. LTD.</h2>
            <p>RKCL SP, Emitra LSP | Near Tehsil Bhawan, Osian Dist. Jodhpur (RAJ) 342303</p>
            <p>Phone: 9413571175, 9314001171 | Email: softtechseva@gmail.com</p>
        </div>

        <div class="slip-title">ITGK BOOK ISSUE ACKNOWLEDGEMENT</div>

        <table class="slip-meta-table">
            <tr>
                <td style="width: 50%;"><strong>ITGK Name :</strong> <?= htmlspecialchars($itgkName) ?></td>
                <td style="width: 50%; text-align: right;"><strong>Txn ID :</strong> <?= htmlspecialchars($txnId) ?></td>
            </tr>
            <tr>
                <td><strong>ITGK Code :</strong> <?= htmlspecialchars($itgkCode) ?></td>
                <td style="text-align: right;"><strong>Date :</strong> <?= htmlspecialchars($issueDate) ?></td>
            </tr>
            <tr>
                <td><strong>Receiver :</strong> <?= htmlspecialchars($receiverName) ?> (<?= htmlspecialchars($receiverMob) ?>)</td>
                <td style="text-align: right;"><strong>Email :</strong> <?= htmlspecialchars($receiverEmail ?: '-') ?></td>
            </tr>
        </table>

        <table class="slip-items-table">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Course Name</th>
                    <th>Medium</th>
                    <th>Transaction Type</th>
                    <th class="ctr">Quantity</th>
                    <th>Issued From</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $b): ?>
                    <tr>
                        <td class="ctr"><?= htmlspecialchars((string)($b['year'] ?? date('Y'))) ?></td>
                        <td><strong><?= htmlspecialchars((string)($b['course_name'] ?? 'RS-CIT')) ?></strong></td>
                        <td class="ctr"><?= htmlspecialchars((string)($b['medium'] ?? 'Hindi')) ?></td>
                        <td class="ctr"><?= htmlspecialchars((string)($b['txn_type'] ?? 'Issued')) ?></td>
                        <td class="ctr" style="font-weight: bold;"><?= (int)($b['quantity'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string)($b['issued_from'] ?? 'Head Office')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL QUANTITY</td>
                    <td class="ctr" style="font-weight: bold; font-size: 13px;"><?= $totalQty ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="issuer-block">
            <div><strong>Issued By :</strong> <?= htmlspecialchars($issuerName ?: 'N/A') ?></div>
            <div><strong>Issued From :</strong> <?= htmlspecialchars($issuerFrom ?: 'Head Office') ?></div>
            <div><strong>Receiver Sign :</strong> .............................. (STAMP)</div>
        </div>

        <div class="footer-note">
            Note: This is an official Book Issue Acknowledgement. Please verify quantities upon receipt.
        </div>
    </div>

    <script>
        // Email stakeholders
        function sendEmail() {
            var btn = document.getElementById('btnEmail');
            var status = document.getElementById('emailStatus');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...'; }
            if (status) status.textContent = 'Sending email to stakeholders...';

            var bookRows = [<?= implode(',', array_map(fn($b) => (int)($b['sheet_row'] ?? 0), $books)) ?>];
            var txnId = <?= json_encode($txnId) ?>;

            fetch('<?= BASE_URL ?>books/send_ack_email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ sheet_rows: bookRows, txn_id: txnId })
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (j.success) {
                        if (status) status.innerHTML = '<span style="color:#22c55e">&#10003; ' + (j.message || 'Emails sent!') + '</span>';
                        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-1"></i> Sent!'; }
                    } else {
                        if (status) status.innerHTML = '<span style="color:#ef4444">&#10007; ' + (j.message || 'Failed') + '</span>';
                        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-envelope me-1"></i> Retry Email'; }
                    }
                })
                .catch(function (e) {
                    if (status) status.innerHTML = '<span style="color:#ef4444">Network error: ' + e.message + '</span>';
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-envelope me-1"></i> Retry Email'; }
                });
        }
    </script>
</body>

</html>