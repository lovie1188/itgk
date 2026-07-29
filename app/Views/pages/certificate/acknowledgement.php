<?php
/**
 * Certificate Issue Acknowledgement Receipt
 * Single copy display + print. Email sent to 4 stakeholders.
 *
 * @package App\Views\pages\certificate
 */

$cert        = $cert        ?? [];
$certs       = $certs       ?? [$cert];
$itgkMaster  = $itgkMaster  ?? ['name' => '', 'email' => '', 'mobile' => '', 'district' => ''];
$issuerName  = $issuerName  ?? '';
$issuerEmail = $issuerEmail ?? '';
$issuerRole  = $issuerRole  ?? '';
$issuerFrom  = $issuerFrom  ?? '';
$issueDate   = $issueDate   ?? date('d-m-Y');
$txnId       = $txnId       ?? ('ISSUE-' . date('Ymd') . '-' . rand(1000, 9999));

$itgkName    = $itgkMaster['name']   ?: ($cert['itgk_code'] ?? 'N/A');
$itgkCode    = $cert['itgk_code']    ?? 'N/A';
$itgkEmail   = $itgkMaster['email']  ?? '';
$receiverName= $cert['receiver_name'] ?? 'N/A';
$receiverMob = $cert['receiver_mobile'] ?? '';

// Totals
$totalPass   = array_sum(array_column($certs, 'pass'));
$totalFail   = array_sum(array_column($certs, 'fail'));
$totalAbs    = array_sum(array_column($certs, 'absent'));
$totalGrand  = array_sum(array_column($certs, 'grand_total'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Certificate Issue Acknowledgement') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/all.min.css">
    <meta name="csrf-token" content="<?= \App\Helpers\Csrf::getToken() ?>">
    <style>
        /* ── Screen ── */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            font-size: 13px;
            color: #111;
        }
        .action-bar {
            background: #1e293b;
            color: #fff;
            padding: 8px 20px;
            display: flex;
            gap: 8px;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .action-bar .info-txt { font-size: 11px; color: #94a3b8; margin-left: 6px; }
        .slip-wrapper { max-width: 820px; margin: 24px auto 60px; padding: 0 12px; }

        /* ── Slip Card ── */
        .slip {
            background: #fff;
            border: 1px solid #ccc;
            padding: 28px 32px;
        }

        /* ── Company Header ── */
        .co-header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .co-header h2 {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #1d4ed8;
            margin: 0 0 2px;
        }
        .co-header .tagline { font-size: 12px; color: #444; margin: 0 0 3px; }
        .co-header .contact { font-size: 11px; color: #444; margin: 0; }
        .co-header .contact a { color: #1d4ed8; text-decoration: none; }

        /* ── Top info row ── */
        .top-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 13px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .top-info .left div, .top-info .right div { margin-bottom: 3px; }
        .top-info strong { font-weight: 700; }

        /* ── Section title ── */
        .sec-title {
            text-align: center;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin: 8px 0 4px;
        }
        .txn-id {
            text-align: center;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        /* ── Table ── */
        .cert-tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 12px;
        }
        .cert-tbl th {
            background: #fff;
            border: 1px solid #666;
            padding: 5px 8px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            font-size: 11px;
        }
        .cert-tbl td {
            border: 1px solid #888;
            padding: 5px 8px;
        }
        .cert-tbl td.ctr { text-align: center; }
        .cert-tbl tfoot td {
            border: 1px solid #666;
            font-weight: 700;
            padding: 5px 8px;
            background: #f9f9f9;
        }
        .cert-tbl tfoot td.ctr { text-align: center; }

        /* ── Issuer block ── */
        .issuer-block { font-size: 13px; margin-top: 10px; line-height: 1.9; }
        .issuer-block strong { font-weight: 700; }

        /* ── Footer note ── */
        .footer-note {
            font-size: 10.5px;
            color: #555;
            border-top: 1px solid #ddd;
            margin-top: 14px;
            padding-top: 6px;
        }

        /* ── Email status ── */
        #emailStatus { font-size: 12px; }

        /* ── Print ── */
        @media print {
            .action-bar { display: none !important; }
            body { background: #fff; }
            .slip-wrapper { margin: 0; padding: 0; max-width: 100%; }
            .slip { border: none; padding: 10px 14px; }
        }
    </style>
</head>
<body>

<!-- Action Bar (no-print) -->
<div class="action-bar">
    <a href="<?= BASE_URL ?>itgk/list" class="btn btn-outline-light btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fas fa-print me-1"></i> Print
    </button>
    <button id="btnEmail" onclick="sendEmail()" class="btn btn-success btn-sm">
        <i class="fas fa-envelope me-1"></i> Email to 4 Stakeholders
    </button>
    <span class="info-txt" id="emailStatus"></span>
</div>

<!-- Slip -->
<div class="slip-wrapper">
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
            <div><strong>Address :</strong> <?= htmlspecialchars((string)($itgkMaster['address'] ?? 'N/A')) ?></div>
            <div><strong>District :</strong> <?= htmlspecialchars((string)($itgkMaster['district'] ?? 'N/A')) ?></div>
            <div><strong>Email :</strong> <?= htmlspecialchars((string)($itgkMaster['email'] ?? 'N/A')) ?></div>
            <div><strong>Mobile No. :</strong> <?= htmlspecialchars((string)($itgkMaster['mobile'] ?? 'N/A')) ?></div>
        </div>
        <div class="right" style="text-align:right">
            <div><strong>IT GK Code :</strong> <?= htmlspecialchars($itgkCode) ?></div>
            <div><strong>Receiver Name :</strong> <?= htmlspecialchars($receiverName) ?></div>
            <?php if ($receiverMob): ?>
            <div><strong>Receiver Mobile :</strong> <?= htmlspecialchars($receiverMob) ?></div>
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
                <th>Exam Name</th>
                <th>Course Name</th>
                <th>Pass</th>
                <th>Packet No</th>
                <th>Cert No. From</th>
                <th>Cert No. To</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($certs as $c): ?>
            <tr>
                <td><?= htmlspecialchars((string)($c['exam_name'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string)($c['course_name'] ?? '-')) ?></td>
                <td class="ctr"><?= (int)($c['pass'] ?? 0) ?></td>
                <td class="ctr"><?= htmlspecialchars((string)($c['packet_no'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string)($c['cert_no_from'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string)($c['cert_no_to'] ?? '-')) ?></td>
                <td class="ctr">Issued</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">TOTAL</td>
                <td class="ctr"><?= $totalPass ?></td>
                <td class="ctr" colspan="4"></td>
            </tr>
        </tfoot>
    </table>


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

</div><!-- /.slip -->
</div><!-- /.slip-wrapper -->

<script>
// Email 4 stakeholders
function sendEmail() {
    var btn = document.getElementById('btnEmail');
    var status = document.getElementById('emailStatus');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...'; }
    if (status) status.textContent = 'Sending email to stakeholders...';

    var certIds = [<?= implode(',', array_map(fn($c) => (int)($c['id'] ?? 0), $certs)) ?>];
    var txnId   = <?= json_encode($txnId) ?>;

    fetch('<?= BASE_URL ?>itgk/send_ack_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ cert_ids: certIds, txn_id: txnId })
    })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.success) {
            if (status) status.innerHTML = '<span style="color:#22c55e">&#10003; ' + (j.message || 'Emails sent!') + '</span>';
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-1"></i> Sent!'; }
        } else {
            if (status) status.innerHTML = '<span style="color:#ef4444">&#10007; ' + (j.message || 'Failed') + '</span>';
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-envelope me-1"></i> Retry Email'; }
        }
    })
    .catch(function(e) {
        if (status) status.innerHTML = '<span style="color:#ef4444">Network error: ' + e.message + '</span>';
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-envelope me-1"></i> Retry Email'; }
    });
}
</script>
</body>
</html>
