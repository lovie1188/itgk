<?php
/**
 * Public Verification Page
 * Asks for visitor details first, logs securely to server, and renders the verification receipt.
 *
 * @package App\Views\pages\certificate
 */

$certs       = $certs       ?? [];
$itgkMaster  = $itgkMaster  ?? ['name' => '', 'email' => '', 'mobile' => '', 'district' => '', 'address' => ''];
$issuerName  = $issuerName  ?? 'N/A';
$issuerFrom  = $issuerFrom  ?? 'N/A';
$issueDate   = $issueDate   ?? date('d-m-Y');
$txnId       = $txnId       ?? '';
$issueTime   = date('h:i A');

$itgkName    = $itgkMaster['name']   ?: ($certs[0]['itgk_code'] ?? 'N/A');
$itgkCode    = $certs[0]['itgk_code']    ?? 'N/A';
$itgkEmail   = $itgkMaster['email']  ?? '';

$receiverName= $certs[0]['receiver_name'] ?? 'N/A';
$receiverMob = $certs[0]['receiver_mobile'] ?? '';
$receiverDesig = $certs[0]['receiver_designation'] ?? '';

$totalPass   = array_sum(array_column($certs, 'pass'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Verify Certificate - SoftSam Portal') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
    <style>
        body {
            background: #f1f5f9;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-size: 13px;
            color: #334155;
            margin: 0;
            padding: 20px 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* ── Verification Gate Form Card ── */
        .gate-card {
            max-width: 420px;
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            animation: fadeIn 0.4s ease-out;
        }
        .gate-header {
            background: #1e3a8a;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .gate-header i {
            font-size: 36px;
            margin-bottom: 10px;
            color: #3b82f6;
        }
        .gate-header h3 {
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 4px;
        }
        .gate-header p {
            font-size: 11px;
            color: #93c5fd;
            margin: 0;
        }
        .gate-body {
            padding: 24px;
        }
        .form-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
        }
        .form-control-custom {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 13px;
            transition: all 0.2s;
            width: 100%;
        }
        .form-control-custom:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .btn-verify {
            background: #10b981;
            color: white;
            font-weight: 700;
            border: none;
            padding: 12px;
            border-radius: 6px;
            width: 100%;
            margin-top: 15px;
            font-size: 13px;
            transition: background 0.2s;
        }
        .btn-verify:hover {
            background: #059669;
        }

        /* ── Verified Document Display (Hidden initially) ── */
        .document-view {
            display: none;
            max-width: 950px;
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            overflow: hidden;
            position: relative;
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Verified Watermark background */
        .document-view::before {
            content: "VERIFIED OFFICIAL COPY";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 55px;
            font-weight: 900;
            color: rgba(16, 185, 129, 0.06);
            white-space: nowrap;
            pointer-events: none;
            letter-spacing: 2px;
            z-index: 1;
        }

        /* Header block */
        .ent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
        }
        .ent-header-logo img { height: 70px; object-fit: contain; }
        .ent-header-center {
            text-align: center;
            flex-grow: 1;
            padding: 0 15px;
        }
        .ent-header-center h1 {
            font-size: 18px;
            font-weight: 850;
            color: #0f172a;
            margin: 0 0 1px 0;
        }
        .ent-header-center .tagline {
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            margin: 0 0 3px 0;
        }
        .ent-header-center h2 {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .badge-issued {
            background-color: #10b981;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Status grid */
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
            padding: 4px 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-card-icon {
            color: #10b981;
            font-size: 12px;
            width: 24px;
            height: 24px;
            background: #d1fae5;
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
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
        }

        /* content layout */
        .ent-content-row {
            display: grid;
            grid-template-columns: 1fr;
            padding: 10px 15px;
        }
        .ent-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .ent-card-header {
            background: #1e3a8a;
            color: white;
            font-weight: 700;
            font-size: 11.5px;
            padding: 4px 8px;
            text-transform: uppercase;
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
            margin-top: 1px;
            width: 14px;
        }
        .ent-info-lbl {
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }
        .ent-info-val {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
        }

        /* table styling */
        .ent-table-container {
            padding: 5px 15px 10px;
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
            font-size: 10px;
            border: 1px solid #1e3a8a;
        }
        .ent-table td {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .ent-table tr:nth-child(even) { background: #f8fafc; }
        .ent-table td.ctr { text-align: center; }
        .ent-table-total {
            background: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
        }

        /* footer sys info */
        .ent-sys-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            padding: 6px 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 9.5px;
            color: #64748b;
            background: #f8fafc;
        }
        @media (min-width: 768px) {
            .ent-sys-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .ent-sys-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ent-sys-item i { color: #94a3b8; }

        /* Banner styling */
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
            font-size: 10px;
            line-height: 1.3;
        }
        @media (min-width: 768px) {
            .ent-banner-note {
                border-right: 1px solid #334155;
                padding-right: 10px;
            }
        }
        .ent-banner-note i { color: #10b981; font-size: 14px; margin-top: 1px; }
        .ent-banner-contact {
            font-size: 9.5px;
            line-height: 1.4;
        }
        @media (min-width: 768px) {
            .ent-banner-contact {
                border-right: 1px solid #334155;
                padding-right: 10px;
                padding-left: 15px;
            }
        }
        .ent-banner-contact a { color: #60a5fa; text-decoration: none; }
        .ent-banner-slogan {
            font-weight: 700;
            font-style: italic;
            font-size: 9.5px;
            color: #94a3b8;
        }
        @media (min-width: 768px) {
            .ent-banner-slogan {
                text-align: right;
                padding-left: 15px;
            }
        }

        /* Developer info */
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
        .dev-footer-bar div { display: inline-flex; align-items: center; gap: 4px; }
        .dev-footer-bar a { color: #3b82f6; text-decoration: none; }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- 1. Verification Gate Card -->
<div class="gate-card" id="gateCard">
    <div class="gate-header">
        <i class="fas fa-user-shield"></i>
        <h3>Secure Verification Gate</h3>
        <p>Enter details to securely verify this certificate</p>
    </div>
    <div class="gate-body">
        <form id="verifyForm">
            <div class="mb-3">
                <label class="form-label">Visitor Name</label>
                <input type="text" id="vName" class="form-control-custom" placeholder="Enter Your Name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mobile Number</label>
                <input type="tel" id="vMobile" class="form-control-custom" placeholder="Enter 10-digit Mobile" pattern="[0-9]{10}" maxlength="10" required>
            </div>
            <button type="submit" class="btn-verify" id="btnVerifySubmit">
                <i class="fas fa-shield-alt me-1"></i> Verify Certificate
            </button>
        </form>
    </div>
</div>

<!-- 2. Verified Document Display (Fades in on submit) -->
<div class="document-view" id="documentView">
    <!-- Header -->
    <div class="ent-header">
        <div class="ent-header-logo">
            <img src="<?= BASE_URL ?>assets/img/logo-black.jpg" alt="Softtech Logo">
        </div>
        <div class="ent-header-center">
            <h1>SOFTTECH MULTI SERVICE PVT. LTD.</h1>
            <p class="tagline">RKCL SP, Emitra LSP</p>
            <h2>Certificate Issue Verification</h2>
            <div>
                <span class="badge-issued">
                    <i class="fas fa-check-circle"></i> VERIFIED OFFICIAL COPY
                </span>
            </div>
        </div>
        <div class="ent-header-logo">
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
                <span class="status-card-val"><?= htmlspecialchars($issuerName) ?></span>
            </div>
        </div>
        <div class="status-card">
            <div class="status-card-icon"><i class="fas fa-building-user"></i></div>
            <div class="status-card-info">
                <span class="status-card-label">Issued From</span>
                <span class="status-card-val"><?= htmlspecialchars($issuerFrom) ?></span>
            </div>
        </div>
    </div>

    <!-- Content Block -->
    <div class="ent-content-row">
        <!-- Recipient Card -->
        <div class="ent-card">
            <div class="ent-card-header">Verified Recipient & ITGK Details</div>
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
                        <div class="ent-info-val"><?= htmlspecialchars((string)($itgkMaster['address'] ?? 'N/A')) ?></div>
                    </div>
                </div>
                <div class="ent-info-item">
                    <div class="ent-info-icon"><i class="fas fa-city"></i></div>
                    <div>
                        <div class="ent-info-lbl">District</div>
                        <div class="ent-info-val"><?= htmlspecialchars((string)($itgkMaster['district'] ?? 'N/A')) ?></div>
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
                        <div class="ent-info-val"><?= htmlspecialchars((string)($itgkMaster['email'] ?? 'N/A')) ?></div>
                    </div>
                </div>
                <div class="ent-info-item">
                    <div class="ent-info-icon"><i class="fas fa-phone-alt"></i></div>
                    <div>
                        <div class="ent-info-lbl">Receiver Mobile</div>
                        <div class="ent-info-val"><?= htmlspecialchars($receiverMob ?: ($itgkMaster['mobile'] ?? 'N/A')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate details Table -->
    <div class="ent-table-container">
        <div style="font-weight: 700; font-size: 11.5px; text-transform: uppercase; color: #1e3a8a; margin-bottom: 6px;">Verified Certificate Details</div>
        <table class="ent-table">
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
                        <strong><?= htmlspecialchars((string)($c['course_name'] ?? '-')) ?></strong>
                        <br><span style="font-size: 10px; color: #64748b;"><?= htmlspecialchars((string)($c['exam_name'] ?? '-')) ?></span>
                    </td>
                    <td class="ctr"><?= (int)($c['pass'] ?? 0) ?></td>
                    <td class="ctr"><?= htmlspecialchars((string)($c['packet_no'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string)($c['cert_no_from'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string)($c['cert_no_to'] ?? '-')) ?></td>
                    <td class="ctr" style="color: #10b981; font-weight: 700;">Issued</td>
                </tr>
                <?php endforeach; ?>
                <tr class="ent-table-total">
                    <td style="text-align: right; font-weight: 800;">TOTAL</td>
                    <td class="ctr"><?= $totalPass ?></td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Generated Info Metadata row -->
    <div class="ent-sys-row">
        <div class="ent-sys-item"><i class="fas fa-clock"></i> Verified On: <?= date('d-m-Y H:i:s') ?></div>
        <div class="ent-sys-item"><i class="fas fa-desktop"></i> Verified Online Portal</div>
        <div class="ent-sys-item"><i class="fas fa-code-branch"></i> System Version: v2.1.0</div>
        <div class="ent-sys-item"><i class="fas fa-fingerprint"></i> Officially Verified Document</div>
    </div>

    <!-- Dark banner note -->
    <div class="ent-banner-row">
        <div class="ent-banner-note">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Secure Copy</strong><br>
                This document is officially verified online from SoftSam Live servers.
            </div>
        </div>
        <div class="ent-banner-contact">
            <i class="fas fa-globe text-primary me-1"></i> <a href="https://www.softtechseva.com" target="_blank">www.softtechseva.com</a><br>
            <i class="fas fa-envelope text-primary me-1"></i> <a href="mailto:softtechseva@gmail.com">softtechseva@gmail.com</a>
        </div>
        <div class="ent-banner-slogan">
            Technology | Trust | Service<br>
            <span style="font-size: 9px; color: #94a3b8; font-weight: normal;">Empowering Digitally, Enriching Lives.</span>
        </div>
    </div>

    <!-- Developer Footer footer bar -->
    <div class="dev-footer-bar">
        <div>Developed By: <a href="http://rakshaeservices.co.in/" target="_blank"><strong>Raksha E Services</strong></a></div>
        <div>Developer: <strong>LOVEJEET SINGH BHATI (+91 94615838757)</strong></div>
        <div>e-Mail: <strong>rakshaeservices@gmail.com, lovie1187@gmail.com</strong></div>
    </div>
</div>

<script>
document.getElementById('verifyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnVerifySubmit');
    const name = document.getElementById('vName').value.trim();
    const mobile = document.getElementById('vMobile').value.trim();
    const txnId = <?= json_encode($txnId) ?>;

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verifying...';
    }

    try {
        const res = await fetch('<?= BASE_URL ?>verify/log', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, mobile, txn_id: txnId })
        });
        
        // Hide gate, show verified card
        document.getElementById('gateCard').style.display = 'none';
        document.getElementById('documentView').style.display = 'block';
    } catch (err) {
        console.error('Logging failed:', err);
        // Display document view anyway for good user experience
        document.getElementById('gateCard').style.display = 'none';
        document.getElementById('documentView').style.display = 'block';
    }
});
</script>
</body>
</html>
