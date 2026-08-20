<?php
/**
 * Certificate Issue Acknowledgement Receipt
 * Dual Mode: Stunning Enterprise Layout on Screen, Clean & Traditional Slip on Print.
 *
 * @package App\Views\pages\certificate
 */

$cert = $cert ?? [];
$certs = $certs ?? [$cert];
$itgkMaster = $itgkMaster ?? ['name' => '', 'email' => '', 'mobile' => '', 'district' => '', 'address' => ''];
$issuerName = $issuerName ?? '';
$issuerEmail = $issuerEmail ?? '';
$issuerRole = $issuerRole ?? '';
$issuerFrom = $issuerFrom ?? '';
$issuerOffice = $issuerOffice ?? '';
$issueDate = $issueDate ?? date('d-m-Y');
$txnId = $txnId ?? ('ISSUE-' . date('Ymd') . '-' . rand(1000, 9999));
$issueTime = date('h:i A'); // Formatted transaction time

$itgkName = $itgkMaster['name'] ?: ($cert['itgk_code'] ?? 'N/A');
$itgkCode = $cert['itgk_code'] ?? 'N/A';
$itgkEmail = $itgkMaster['email'] ?? '';
$receiverName = $cert['receiver_name'] ?? 'N/A';
$receiverMob = $cert['receiver_mobile'] ?? '';
$receiverDesig = $cert['receiver_designation'] ?? '';

// Totals
$totalPass = array_sum(array_column($certs, 'pass'));
$totalFail = array_sum(array_column($certs, 'fail'));
$totalAbs = array_sum(array_column($certs, 'absent'));
$totalGrand = array_sum(array_column($certs, 'grand_total'));

$verifyUrl = (getenv('APP_URL') ?: 'http://localhost/certificate') . '/verify/transaction?id=' . $txnId;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Certificate Issue Acknowledgement') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= \App\Helpers\Csrf::getToken() ?>">
    <style>
        /* ── Screen Layout (No Print) ── */
        @media screen {
            body {
                background: #f1f5f9;
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
                height: 80px;
                object-fit: contain;
            }

            .ent-header-logo-right img {
                height: 68px;
                object-fit: contain;
            }

            .ent-header-center {
                text-align: center;
                flex-grow: 1;
                padding: 0 10px;
            }

            .ent-header-center h1 {
                font-size: 20px;
                font-weight: 850;
                color: #0f172a;
                margin: 0 0 1px 0;
                letter-spacing: 0.5px;
            }

            .ent-header-center .tagline {
                font-size: 11px;
                font-weight: 600;
                color: #64748b;
                margin: 0 0 4px 0;
            }

            .ent-header-center h2 {
                font-size: 14px;
                font-weight: 700;
                color: #1e3a8a;
                margin: 0 0 4px 0;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }

            .badge-issued {
                background-color: #10b981;
                color: white;
                font-size: 11px;
                font-weight: 700;
                padding: 4px 14px;
                border-radius: 4px;
                text-transform: uppercase;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }

            /* Status Bar / Grid */
            .ent-status-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 6px;
                padding: 6px 15px;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                border-bottom: 1px solid #e2e8f0;
            }

            @media (min-width: 480px) {
                .ent-status-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (min-width: 768px) {
                .ent-status-grid {
                    grid-template-columns: repeat(5, 1fr);
                }
            }

            .status-card {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 5px 8px;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .status-card-icon {
                color: #3b82f6;
                font-size: 13px;
                width: 26px;
                height: 26px;
                background: #eff6ff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .status-card-info {
                display: flex;
                flex-direction: column;
            }

            .status-card-label {
                font-size: 9px;
                font-weight: 600;
                color: #64748b;
                text-transform: uppercase;
            }

            .status-card-val {
                font-size: 11.5px;
                font-weight: 700;
                color: #1e293b;
            }

            /* Two Column Content Area */
            .ent-content-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 10px 15px;
            }

            @media (min-width: 768px) {
                .ent-content-row {
                    grid-template-columns: 7fr 3fr;
                }
            }

            /* recipient card */
            .ent-card {
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                overflow: hidden;
            }

            .ent-card-header {
                background: #1e3a8a;
                color: white;
                font-weight: 700;
                font-size: 12px;
                padding: 4px 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .ent-card-body {
                padding: 8px;
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
                background: white;
            }

            @media (min-width: 480px) {
                .ent-card-body {
                    grid-template-columns: 1fr 1fr;
                }
            }

            .ent-info-item {
                display: flex;
                align-items: flex-start;
                gap: 6px;
            }

            .ent-info-icon {
                color: #3b82f6;
                margin-top: 2px;
                width: 14px;
            }

            .ent-info-lbl {
                font-size: 9px;
                font-weight: 600;
                color: #64748b;
                text-transform: uppercase;
            }

            .ent-info-val {
                font-size: 11.5px;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.3;
            }

            /* Verification Side panel */
            .verify-card {
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                border-radius: 6px;
                padding: 8px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .verify-title {
                color: #166534;
                font-weight: 800;
                font-size: 11.5px;
                display: flex;
                align-items: center;
                gap: 5px;
                text-transform: uppercase;
                margin-bottom: 4px;
            }

            .verify-desc {
                font-size: 10px;
                color: #475569;
                margin-bottom: 6px;
            }

            .verify-qr img {
                border: 1px solid #cbd5e1;
                padding: 2px;
                background: white;
                border-radius: 4px;
                margin-bottom: 6px;
            }

            .verify-link {
                color: #2563eb;
                font-weight: 700;
                font-size: 10px;
                text-decoration: none;
                margin-bottom: 2px;
            }

            .verify-ref {
                font-size: 9px;
                color: #64748b;
            }

            /* Table Styling */
            .ent-table-container {
                padding: 0 15px 10px;
            }

            .ent-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11.5px;
            }

            .ent-table th {
                background: #1e3a8a;
                color: white;
                font-weight: 700;
                text-transform: uppercase;
                padding: 4px 8px;
                font-size: 10.5px;
                border: 1px solid #1e3a8a;
            }

            .ent-table td {
                padding: 4px 8px;
                border: 1px solid #e2e8f0;
                color: #1e293b;
            }

            .ent-table tr:nth-child(even) {
                background: #f8fafc;
            }

            .ent-table td.ctr {
                text-align: center;
            }

            .ent-table-total {
                background: #f1f5f9;
                font-weight: 800;
                color: #0f172a;
            }

            /* System Metadata Row */
            .ent-sys-row {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 6px;
                padding: 6px 15px;
                border-top: 1px solid #e2e8f0;
                font-size: 10px;
                color: #64748b;
                background: #f8fafc;
            }

            .ent-sys-item {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .ent-sys-item i {
                color: #94a3b8;
                font-size: 11px;
            }

            /* Banner Row */
            .ent-banner-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
                align-items: center;
                background: #0f172a;
                color: white;
                padding: 12px 15px;
            }

            @media (min-width: 768px) {
                .ent-banner-row {
                    grid-template-columns: 5fr 4fr 3fr;
                    padding: 8px 15px;
                }
            }

            .ent-banner-note {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                font-size: 10.5px;
                line-height: 1.4;
            }

            @media (min-width: 768px) {
                .ent-banner-note {
                    border-right: 1px solid #334155;
                    padding-right: 10px;
                }
            }

            .ent-banner-note i {
                color: #f59e0b;
                font-size: 16px;
                margin-top: 2px;
            }

            .ent-banner-contact {
                font-size: 10px;
                line-height: 1.5;
            }

            @media (min-width: 768px) {
                .ent-banner-contact {
                    border-right: 1px solid #334155;
                    padding-right: 10px;
                    padding-left: 15px;
                }
            }

            .ent-banner-contact a {
                color: #60a5fa;
                text-decoration: none;
            }

            .ent-banner-slogan {
                font-weight: 700;
                font-style: italic;
                font-size: 10px;
                color: #94a3b8;
                line-height: 1.3;
            }

            @media (min-width: 768px) {
                .ent-banner-slogan {
                    text-align: right;
                    padding-left: 15px;
                }
            }

            /* Developer footer bar */
            /* Developer footer bar */
            .dev-footer-bar {
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                padding: 10px 15px;
                font-size: 11.5px;
                color: #475569;
                text-align: center;
                display: flex;
                flex-direction: column;
                gap: 4px;
                align-items: center;
                justify-content: center;
            }

            .dev-footer-bar div {
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }

            .dev-footer-bar a {
                color: #3b82f6;
                text-decoration: none;
            }

            .print-view {
                display: none !important;
            }
        }

        /* ── Print Layout overrides ── */
        @media print {
            body {
                background: #fff;
                margin: 0;
                padding: 0;
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 13px;
                color: #111;
            }

            .action-bar {
                display: none !important;
            }

            .enterprise-wrapper {
                display: none !important;
            }

            .print-view {
                display: block !important;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            /* Traditional Slip Card styling for printer */
            .slip {
                background: #fff;
                border: none;
                padding: 0;
            }

            .co-header {
                text-align: center;
                border-bottom: 2px solid #111;
                padding-bottom: 8px;
                margin-bottom: 12px;
            }

            .co-header h2 {
                font-size: 21px;
                font-weight: 900;
                letter-spacing: 1px;
                color: #1d4ed8;
                margin: 0 0 2px;
            }

            .co-header .tagline {
                font-size: 11.5px;
                color: #444;
                margin: 0 0 2px;
            }

            .co-header .contact {
                font-size: 10.5px;
                color: #444;
                margin: 0;
            }

            .co-header .contact a {
                color: #1d4ed8;
                text-decoration: none;
            }

            .top-info {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                font-size: 12.5px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 8px;
            }

            .top-info .left div,
            .top-info .right div {
                margin-bottom: 2px;
            }

            .top-info strong {
                font-weight: 700;
            }

            .sec-title {
                text-align: center;
                font-size: 14.5px;
                font-weight: 800;
                letter-spacing: .5px;
                text-transform: uppercase;
                margin: 6px 0 3px;
            }

            .txn-id {
                text-align: center;
                color: #1d4ed8;
                font-size: 12.5px;
                font-weight: 700;
                margin-bottom: 12px;
            }

            .cert-tbl {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 12px;
                font-size: 11.5px;
            }

            .cert-tbl th {
                background: #fff;
                border: 1px solid #555;
                padding: 4px 6px;
                font-weight: 700;
                text-align: center;
                text-transform: uppercase;
                font-size: 10.5px;
            }

            .cert-tbl td {
                border: 1px solid #777;
                padding: 4px 6px;
            }

            .cert-tbl td.ctr {
                text-align: center;
            }

            .cert-tbl tfoot td {
                border: 1px solid #555;
                font-weight: 700;
                padding: 4px 6px;
                background: #f9f9f9;
            }

            .issuer-block {
                font-size: 12.5px;
                margin-top: 8px;
                line-height: 1.8;
            }

            .issuer-block strong {
                font-weight: 700;
            }

            .footer-note {
                font-size: 10px;
                color: #444;
                border-top: 1px solid #ddd;
                margin-top: 12px;
                padding-top: 5px;
            }

            @page {
                margin: 15mm;
            }
        }
    </style>
</head>

<body>

    <!-- Action Bar (no-print) -->
    <div class="action-bar no-print">
        <a href="<?= BASE_URL ?>itgk/list" class="btn btn-outline-light btn-sm px-3">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-print me-1"></i> Print Receipt
        </button>
        <button id="btnEmail" onclick="sendEmail()" class="btn btn-success btn-sm px-3">
            <i class="fas fa-envelope me-1"></i> Email to 4 Stakeholders
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
                <h2>Certificate Issue Acknowledgement</h2>
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
                    <span class="status-card-label">Dispatch Txn ID</span>
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
                    <span class="status-card-val"><?= htmlspecialchars($issuerFrom ?: $issuerRole) ?></span>
                </div>
            </div>
        </div>

        <!-- Monetization / Advertisement Slot (Top Banner Ad) -->
        <div class="ad-slot-container no-print text-center my-1" style="display:none;" id="ad-slot-top-wrapper">
            <ins class="adsbygoogle"
                 style="display:block; text-align:center;"
                 data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
                 data-ad-slot="1234567890"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>
                (adsbygoogle = window.adsbygoogle || []).push({});
                // Show container only if AdSense fills the ad slot
                document.addEventListener('DOMContentLoaded', function() {
                    var ins = document.querySelector('#ad-slot-top-wrapper ins');
                    if (ins && (ins.getAttribute('data-ad-status') === 'filled' || ins.children.length > 0)) {
                        document.getElementById('ad-slot-top-wrapper').style.display = 'block';
                    }
                });
            </script>
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
                            <div class="ent-info-lbl">IT GK Name</div>
                            <div class="ent-info-val"><?= htmlspecialchars($itgkName) ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-barcode"></i></div>
                        <div>
                            <div class="ent-info-lbl">IT GK Code</div>
                            <div class="ent-info-val"><?= htmlspecialchars($itgkCode) ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item" style="grid-column: span 2;">
                        <div class="ent-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="ent-info-lbl">Address</div>
                            <div class="ent-info-val"><?= htmlspecialchars((string) ($itgkMaster['address'] ?? 'N/A')) ?>
                            </div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-city"></i></div>
                        <div>
                            <div class="ent-info-lbl">District</div>
                            <div class="ent-info-val">
                                <?= htmlspecialchars((string) ($itgkMaster['district'] ?? 'N/A')) ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <div class="ent-info-lbl">Receiver Name</div>
                            <div class="ent-info-val"><?= htmlspecialchars($receiverName) ?></div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="ent-info-lbl">Email</div>
                            <div class="ent-info-val"><?= htmlspecialchars((string) ($itgkMaster['email'] ?? 'N/A')) ?>
                            </div>
                        </div>
                    </div>
                    <div class="ent-info-item">
                        <div class="ent-info-icon"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <div class="ent-info-lbl">Receiver Mobile</div>
                            <div class="ent-info-val">
                                <?= htmlspecialchars($receiverMob ?: ($itgkMaster['mobile'] ?? 'N/A')) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification side panel -->
            <div class="verify-card">
                <div class="verify-title"><i class="fas fa-shield-alt text-success"></i> Verification</div>
                <div class="verify-desc">Scan QR Code to verify this transaction online.</div>
                <div class="verify-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($verifyUrl) ?>"
                        width="100" height="100" alt="Verification QR Code">
                </div>
                <a href="<?= $verifyUrl ?>" target="_blank" class="verify-link">Verify Online</a>
                <div class="verify-link" style="color:#1e3a8a; font-size:10.5px"><?= htmlspecialchars($verifyUrl) ?>
                </div>
                <div class="verify-ref">Ref No: RKCL-<?= htmlspecialchars($txnId) ?></div>
            </div>
        </div>

        <!-- Certificate details Table (Child Rows of Issue Transaction) -->
        <div class="ent-table-container">
            <div
                style="font-weight: 700; font-size: 12px; text-transform: uppercase; color: #1e3a8a; margin-bottom: 8px;">
                Certificate Issue Packet Details (Total <?= count($certs) ?> Packet Row<?= count($certs) > 1 ? 's' : '' ?>)</div>
            <table class="ent-table">
                <thead>
                    <tr>
                        <th>Course / Exam Name</th>
                        <th style="text-align: center;">Pass Count</th>
                        <th style="text-align: center;">Packet No</th>
                        <th>Cert No. From</th>
                        <th>Cert No. To</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $combinedRemarks = [];
                    foreach ($certs as $c): 
                        if (!empty($c['remark']) && !in_array($c['remark'], $combinedRemarks, true)) {
                            $combinedRemarks[] = $c['remark'];
                        }
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string) ($c['course_name'] ?? '-')) ?></strong>
                                <br><span
                                    style="font-size: 10.5px; color: #64748b;"><?= htmlspecialchars((string) ($c['exam_name'] ?? '-')) ?></span>
                            </td>
                            <td class="ctr"><?= (int) ($c['pass'] ?? 0) ?></td>
                            <td class="ctr"><?= htmlspecialchars((string) ($c['packet_no'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($c['cert_no_from'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($c['cert_no_to'] ?? '-')) ?></td>
                            <td class="ctr" style="color: #10b981; font-weight: 700;">Issued</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="ent-table-total">
                        <td style="text-align: right; font-weight: 800;">TOTAL CERTIFICATES ISSUED</td>
                        <td class="ctr"><?= $totalPass ?></td>
                        <td colspan="4"></td>
                    </tr>
                </tbody>
            </table>

            <!-- Unified Bottom Remark Box -->
            <div class="mt-2 p-2 bg-light border rounded-2" style="background:#f8fafc; border:1px solid #e2e8f0;">
                <div class="fw-bold text-dark" style="font-size:11px;"><i class="fas fa-comment-alt text-primary me-1.5"></i>Transaction / Packet Remark:</div>
                <div class="text-secondary" style="font-size:11px; margin-top:2px;">
                    <?= !empty($combinedRemarks) ? htmlspecialchars(implode(' | ', $combinedRemarks)) : 'No specific remark recorded for this issuance.' ?>
                </div>
            </div>
            
        <!-- Bottom Monetization / Advertisement Slot -->
        <div class="ad-slot-container no-print text-center my-1" style="display:none;" id="ad-slot-bottom-wrapper">
            <ins class="adsbygoogle"
                 style="display:block; text-align:center;"
                 data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
                 data-ad-slot="0987654321"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>
                (adsbygoogle = window.adsbygoogle || []).push({});
                document.addEventListener('DOMContentLoaded', function() {
                    var ins = document.querySelector('#ad-slot-bottom-wrapper ins');
                    if (ins && (ins.getAttribute('data-ad-status') === 'filled' || ins.children.length > 0)) {
                        document.getElementById('ad-slot-bottom-wrapper').style.display = 'block';
                    }
                });
            </script>
        </div>
        </div>

        <!-- Generated Info Metadata row -->
        <div class="ent-sys-row">
            <div class="ent-sys-item"><i class="fas fa-clock"></i> Generated On: <?= date('d-m-Y H:i:s') ?></div>
            <div class="ent-sys-item"><i class="fas fa-desktop"></i> Generated By: System</div>
            <div class="ent-sys-item"><i class="fas fa-code-branch"></i> System Version: v2.1.0</div>
            <div class="ent-sys-item"><i class="fas fa-fingerprint"></i> System Generated Acknowledgement</div>
        </div>

        <!-- Dark banner note -->
        <div class="ent-banner-row">
            <div class="ent-banner-note">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Important Note</strong><br>
                    This acknowledgement is digitally generated and does not require physical signature.
                </div>
            </div>
            <div class="ent-banner-contact">
                <i class="fas fa-globe text-primary me-1"></i> <a href="https://www.softtechseva.com"
                    target="_blank">www.softtechseva.com</a><br>
                <i class="fas fa-envelope text-primary me-1"></i> <a
                    href="mailto:softtechseva@gmail.com">softtechseva@gmail.com</a><br>
                <i class="fas fa-phone-alt text-primary me-1"></i> +91 9983750284
            </div>
            <div class="ent-banner-slogan">
                Technology | Trust | Service<br>
                <span style="font-size: 9.5px; color: #94a3b8; font-weight: normal;">Empowering Digitally, Enriching
                    Lives.</span>
            </div>
        </div>

        <!-- Developer Footer footer bar -->
        <div class="dev-footer-bar">
            <div>Developed By: <a href="http://rakshaeservices.co.in/" target="_blank"><strong>Raksha E
                        Services</strong></a></div>
            <div>Developer: <strong>LOVEJEET SINGH BHATI (+91 94615838757)</strong></div>
            <div>e-Mail: <strong>rakshaeservices@gmail.com, lovie1187@gmail.com</strong></div>
        </div>
    </div>

    <!-- =================================================== -->
    <!-- 2. PRINT SLIP VIEW (HIDDEN ON SCREEN, SHOWS IN PRINT)-->
    <!-- =================================================== -->
    <div class="print-view">
        <div class="slip" id="printSlip">
            <!-- Company Header -->
            <div class="co-header">
                <h2>SOFTTECH MULTI SERVICE PVT. LTD.</h2>
                <p class="tagline">RKCL SP, Emitra LSP</p>
                <p class="contact">
                    <a href="https://www.softtechseva.com" target="_blank">www.softtechseva.com</a>
                    &nbsp;|&nbsp; Email: <a href="mailto:softtechseva@gmail.com">softtechseva@gmail.com</a>
                </p>
            </div>

            <!-- ITGK & Receiver Info -->
            <div class="top-info">
                <div class="left">
                    <div><strong>IT GK Name :</strong> <?= htmlspecialchars($itgkName) ?></div>
                    <div><strong>Address :</strong> <?= htmlspecialchars((string) ($itgkMaster['address'] ?? 'N/A')) ?>
                    </div>
                    <div><strong>District :</strong> <?= htmlspecialchars((string) ($itgkMaster['district'] ?? 'N/A')) ?>
                    </div>
                    <div><strong>Email :</strong> <?= htmlspecialchars((string) ($itgkMaster['email'] ?? 'N/A')) ?></div>
                    <div><strong>Mobile No. :</strong> <?= htmlspecialchars((string) ($itgkMaster['mobile'] ?? 'N/A')) ?>
                    </div>
                </div>
                <div class="right" style="text-align:right">
                    <div><strong>IT GK Code :</strong> <?= htmlspecialchars($itgkCode) ?></div>
                    <div><strong>Receiver Name :</strong> <?= htmlspecialchars($receiverName) ?></div>
                    <?php if ($receiverMob): ?>
                        <div><strong>Receiver Mobile :</strong> <?= htmlspecialchars($receiverMob) ?></div>
                    <?php endif; ?>
                    <?php if ($receiverDesig): ?>
                        <div><strong>Designation :</strong> <?= htmlspecialchars($receiverDesig) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Title -->
            <div class="sec-title">Certificate Issue Acknowledgement</div>
            <div class="txn-id">Transaction ID: <?= htmlspecialchars($txnId) ?></div>

            <!-- Certificate Table -->
            <table class="cert-tbl">
                <thead>
                    <tr>
                        <th>Course / Exam Name</th>
                        <th style="text-align: center;">Pass</th>
                        <th style="text-align: center;">Packet No</th>
                        <th>Cert No. From</th>
                        <th>Cert No. To</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certs as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string) ($c['course_name'] ?? '-')) ?></strong>
                                <br><span
                                    style="font-size: 10.5px; color: #555;"><?= htmlspecialchars((string) ($c['exam_name'] ?? '-')) ?></span>
                            </td>
                            <td class="ctr"><?= (int) ($c['pass'] ?? 0) ?></td>
                            <td class="ctr"><?= htmlspecialchars((string) ($c['packet_no'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($c['cert_no_from'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($c['cert_no_to'] ?? '-')) ?></td>
                            <td class="ctr">Issued</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td style="text-align:right">TOTAL</td>
                        <td class="ctr"><?= $totalPass ?></td>
                        <td class="ctr" colspan="4"></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Unified Bottom Remark for Print Slip -->
            <div style="margin-top:8px; padding:6px; border:1px solid #ccc; font-size:11px;">
                <strong>Remark:</strong> <?= !empty($combinedRemarks) ? htmlspecialchars(implode(' | ', $combinedRemarks)) : 'N/A' ?>
            </div>

            <!-- Issuer Block -->
            <div class="issuer-block">
                <div><strong>Issuer :</strong> <?= htmlspecialchars($issuerName ?: 'N/A') ?></div>
                <?php if ($issuerFrom): ?>
                    <div><strong>Issued From :</strong> <?= htmlspecialchars($issuerFrom) ?></div>
                <?php elseif ($issuerRole): ?>
                    <div><strong>Issued From :</strong> <?= htmlspecialchars($issuerRole) ?></div>
                <?php endif; ?>
                <div><strong>Date :</strong> <?= htmlspecialchars($issueDate) ?></div>
            </div>

            <!-- Footer Note -->
            <div class="footer-note">
                Note: This is an automated acknowledgement. Please contact support for any discrepancies.
            </div>
        </div>
    </div>

    <script>
        // Email 4 stakeholders
        function sendEmail() {
            var btn = document.getElementById('btnEmail');
            var status = document.getElementById('emailStatus');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...'; }
            if (status) status.textContent = 'Sending email to stakeholders...';

            var certIds = [<?= implode(',', array_map(fn($c) => (int) preg_replace('/^certificate\s*/i', '', (string) $c['id']), $certs)) ?>];
            var txnId = <?= json_encode($txnId) ?>;

            fetch('<?= BASE_URL ?>itgk/send_ack_email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ cert_ids: certIds, txn_id: txnId })
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