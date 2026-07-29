<?php
/**
 * Learner Certificate Issue Acknowledgement Receipt View — Printable Document
 *
 * @package App\Views\pages\learner
 */

$learner = $learner ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Learner Certificate Issue Acknowledgement') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; }
        .receipt-card { max-width: 800px; margin: 30px auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #e2e8f0; }
        .header-logo { border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 25px; }
        .signature-box { margin-top: 60px; border-top: 1px dashed #cbd5e1; padding-top: 15px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .receipt-card { box-shadow: none; border: none; padding: 0; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="receipt-card">
        <!-- Action Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="<?= BASE_URL ?>learners/list" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Learner Results</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i>Print Learner Receipt</button>
        </div>

        <!-- Header -->
        <div class="header-logo d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-primary mb-0"><i class="fas fa-graduation-cap me-2"></i>SoftSam Certificate Portal</h3>
                <p class="text-muted small mb-0">Learner Certificate Issue Acknowledgement Receipt</p>
            </div>
            <div class="text-end">
                <span class="badge bg-success px-3 py-2 fs-6">STATUS: ISSUED</span>
                <div class="small text-muted mt-1">Receipt Date: <?= date('d M Y, h:i A') ?></div>
            </div>
        </div>

        <!-- Student & ITGK Info -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                    <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-user-graduate me-2"></i>Learner Details</h6>
                    <div><strong>Learner Name:</strong> <span class="fw-bold text-primary fs-6"><?= htmlspecialchars((string)($learner['learner_name'] ?? 'N/A')) ?></span></div>
                    <div><strong>Father's Name:</strong> <?= htmlspecialchars((string)($learner['father_name'] ?? 'N/A')) ?></div>
                    <div><strong>Learner Code:</strong> <code><?= htmlspecialchars((string)($learner['learner_code'] ?? 'N/A')) ?></code></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                    <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-certificate me-2"></i>Examination Details</h6>
                    <div><strong>Course:</strong> <?= htmlspecialchars((string)($learner['course_name'] ?? 'N/A')) ?></div>
                    <div><strong>Exam Name:</strong> <?= htmlspecialchars((string)($learner['exam_name'] ?? 'N/A')) ?></div>
                    <div><strong>ITGK Code:</strong> <code><?= htmlspecialchars((string)($learner['itgk_code'] ?? 'N/A')) ?></code></div>
                </div>
            </div>
        </div>

        <!-- Result & Certificate Number Table -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-dark">
                <tr>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                    <th>Result</th>
                    <th>Issued Certificate Number</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold fs-5"><?= $learner['marks_obtained'] ?? 0 ?></td>
                    <td><?= $learner['total_marks'] ?? 100 ?></td>
                    <td class="fw-bold"><?= number_format((float)($learner['percentage'] ?? 0), 1) ?>%</td>
                    <td>
                        <span class="badge bg-success fs-6"><?= htmlspecialchars((string)($learner['result'] ?? 'PASS')) ?></span>
                    </td>
                    <td class="fw-bold text-primary fs-5">
                        <?= htmlspecialchars((string)($learner['certificate_no'] ?? 'N/A')) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($learner['remark'])): ?>
        <div class="mb-4">
            <label class="small text-muted fw-bold">Remarks / Dispatch Notes:</label>
            <div class="p-2 bg-light rounded border small"><?= htmlspecialchars($learner['remark']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Signatures & Stamp Block -->
        <div class="signature-box row">
            <div class="col-6 text-center">
                <div style="height: 50px;"></div>
                <p class="fw-bold mb-0 border-top pt-2 d-inline-block px-4">Learner Signature</p>
                <div class="small text-muted">Received Certificate Original</div>
            </div>
            <div class="col-6 text-center">
                <div style="height: 50px;"></div>
                <p class="fw-bold mb-0 border-top pt-2 d-inline-block px-4">Authorized ITGK / Staff Signature</p>
                <div class="small text-muted">SoftSam Certificate Department</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
