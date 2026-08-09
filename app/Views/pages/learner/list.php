<?php
/**
 * Learner List Page View — Google Sheet Integration
 * Exact Original Visual Elements & Offcanvas Forms
 */

$currentPage = (int)($currentPage ?? 1);
$totalPages  = (int)($totalPages  ?? 1);
$total       = (int)($total       ?? count($learners ?? []));
$limit       = (int)($limit       ?? 100);
$isSuperAdmin = \App\Services\AuthService::isSuperAdmin();
// Build base URL for pagination links
$baseUrl = BASE_URL . 'learners/list';
?>

<div class="row mb-2">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-0"><i class="fas fa-graduation-cap text-primary me-2"></i>Learner Examination Results</h3>
            <p class="text-muted small mb-0">
                Showing <strong><?= number_format(count($learners ?? [])) ?></strong> of <strong><?= number_format($total) ?></strong> total Learner Records &mdash; Google Sheet (Tab: Student_Result)
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($isSuperAdmin): ?>
                <button type="button" class="btn btn-success btn-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#addLearnerOffcanvas" aria-controls="addLearnerOffcanvas">
                    <i class="fas fa-plus-circle me-1"></i>Add Learner Result
                </button>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>learners/list?source_gsheet=1" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sync-alt me-1"></i>Refresh Sheet
            </a>
        </div>
    </div>
</div>

<!-- Filter Control Bar -->
<div class="card border-0 shadow-sm mb-2 rounded-3" style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);">
    <div class="card-body py-2 px-2">
        <form method="GET" action="<?= BASE_URL ?>learners/list" id="learnerFilterForm" class="row g-1 align-items-center">
            <!-- Search Name / Code -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute text-white-50"
                        style="left:10px;top:50%;transform:translateY(-50%);font-size:11px;pointer-events:none;"></i>
                    <input type="search" name="search"
                        class="form-control form-control-sm border-0 ps-4"
                        placeholder="Search Name or Code..."
                        value="<?= htmlspecialchars((string)($filters['search'] ?? '')) ?>"
                        style="background:rgba(255,255,255,.18);color:#fff;font-size:11.5px;border-radius:20px;"
                        autocomplete="off">
                </div>
            </div>

            <!-- ITGK CODE Dropdown -->
            <div class="col-6 col-sm-3 col-md-2">
                <select name="itgk_code" class="form-select form-select-sm border-0 text-dark"
                    style="font-size:11.5px;border-radius:20px;background:#fff;"
                    onchange="this.form.submit()">
                    <option value="">-- ITGK CODE --</option>
                    <?php foreach ($itgkOptions as $opt): ?>
                        <option value="<?= htmlspecialchars((string)$opt) ?>" <?= ($filters['itgk_code'] ?? '') === (string)$opt ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Course Name Dropdown -->
            <div class="col-6 col-sm-3 col-md-3">
                <select name="course_name" class="form-select form-select-sm border-0 text-dark"
                    style="font-size:11.5px;border-radius:20px;background:#fff;"
                    onchange="this.form.submit()">
                    <option value="">-- ALL COURSES --</option>
                    <?php foreach ($courseOptions as $opt): ?>
                        <option value="<?= htmlspecialchars((string)$opt) ?>" <?= ($filters['course_name'] ?? '') === (string)$opt ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Exam Name Dropdown -->
            <div class="col-8 col-sm-4 col-md-3">
                <select name="exam_name" class="form-select form-select-sm border-0 text-dark"
                    style="font-size:11.5px;border-radius:20px;background:#fff;"
                    onchange="this.form.submit()">
                    <option value="">-- ALL EXAMS --</option>
                    <?php foreach ($examOptions as $opt): ?>
                        <option value="<?= htmlspecialchars((string)$opt) ?>" <?= ($filters['exam_name'] ?? '') === (string)$opt ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-4 col-sm-2 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-light btn-sm w-100 fw-bold px-1" style="border-radius:20px;font-size:11px;" title="Apply Filter">
                    <i class="fas fa-filter"></i>
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['itgk_code']) || !empty($filters['course_name']) || !empty($filters['exam_name'])): ?>
                    <a href="<?= BASE_URL ?>learners/list" class="btn btn-outline-light btn-sm py-0 px-2 flex-shrink-0" style="border-radius:20px;font-size:11px;" title="Reset Filters">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Main Table Card -->
<div class="card-modern">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-primary text-white">
        <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Learner Examination Results (<?= number_format($total) ?> records)</h6>
        <span class="badge bg-light text-dark">Google Sheet Live</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($learners)): ?>
        <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th style="width: 50px;">S.No</th>
                        <th>Learner Info</th>
                        <th>Father Name</th>
                        <th>ITGK Code</th>
                        <th>Course & Exam</th>
                        <th>Marks & Result</th>
                        <th>Certificate No</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($learners as $idx => $learner): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $learner['s_no'] ?? ($idx + 1) ?></td>
                        <td>
                            <div class="fw-bold text-primary"><?= htmlspecialchars((string)($learner['learner_name'] ?? '')) ?></div>
                            <div class="small text-muted"><code><?= htmlspecialchars((string)($learner['learner_code'] ?? '')) ?></code></div>
                        </td>
                        <td><?= htmlspecialchars((string)($learner['father_name'] ?? '-')) ?></td>
                        <td><code><?= htmlspecialchars((string)($learner['itgk_code'] ?? '-')) ?></code></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars((string)($learner['course_name'] ?? '')) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string)($learner['exam_name'] ?? '-')) ?></div>
                        </td>
                        <td>
                            <?php 
                            $result = strtoupper(trim((string)($learner['result'] ?? 'PASS')));
                            $badgeClass = match(true) {
                                str_contains($result, 'PASS') => 'bg-success',
                                str_contains($result, 'FAIL') => 'bg-danger',
                                str_contains($result, 'ABSENT') => 'bg-secondary',
                                default => 'bg-info'
                            };
                            ?>
                            <div><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($result) ?></span></div>
                            <div class="small text-muted"><?= $learner['marks_obtained'] ?? 0 ?> / <?= $learner['total_marks'] ?? 100 ?> (<?= number_format((float)($learner['percentage'] ?? 0), 1) ?>%)</div>
                        </td>
                        <td>
                            <code><?= htmlspecialchars((string)($learner['certificate_no'] ?? '-')) ?></code>
                        </td>
                        <td>
                            <span class="badge bg-<?= (strcasecmp((string)($learner['status'] ?? ''), 'ISSUED') === 0) ? 'success' : 'primary' ?>">
                                <?= htmlspecialchars((string)($learner['status'] ?? 'Available')) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <!-- View button (expand row) -->
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 btn-view-learner"
                                data-idx="<?= $idx ?>"
                                title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($isSuperAdmin): ?>
                                <!-- Edit button -->
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 btn-edit-learner"
                                    data-id="<?= $learner['id'] ?? ($idx + 1) ?>"
                                    data-name="<?= htmlspecialchars((string)($learner['learner_name'] ?? '')) ?>"
                                    data-itgk="<?= htmlspecialchars((string)($learner['itgk_code'] ?? '')) ?>"
                                    data-course="<?= htmlspecialchars((string)($learner['course_name'] ?? '')) ?>"
                                    data-result="<?= htmlspecialchars((string)($learner['result'] ?? '')) ?>"
                                    data-cert="<?= htmlspecialchars((string)($learner['certificate_no'] ?? '')) ?>"
                                    data-status="<?= htmlspecialchars((string)($learner['status'] ?? '')) ?>"
                                    data-bs-toggle="offcanvas" data-bs-target="#editLearnerOffcanvas"
                                    title="Edit Record">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Issue button -->
                                <button type="button" class="btn btn-outline-success btn-sm py-0 px-2 btn-issue-learner" 
                                    data-id="<?= $learner['id'] ?? ($idx + 1) ?>"
                                    data-name="<?= htmlspecialchars((string)($learner['learner_name'] ?? '')) ?>"
                                    data-cert="<?= htmlspecialchars((string)($learner['certificate_no'] ?? '')) ?>"
                                    data-bs-toggle="offcanvas" data-bs-target="#issueLearnerOffcanvas"
                                    title="Issue Certificate">
                                    <i class="fas fa-certificate"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (strcasecmp((string)($learner['status'] ?? ''), 'ISSUED') === 0 && !empty($learner['id'])): ?>
                                <a href="<?= BASE_URL ?>learners/acknowledgement?id=<?= $learner['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm py-0 px-2" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No learner results found in Google Sheet</h5>
            <p class="small text-muted">Click "Refresh Sheet" above to load data from your connected Google Sheet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>



<?php
// Build pagination query string helper
$filterParams = array_filter($filters ?? [], fn($v) => $v !== '');
function buildPageUrl($baseUrl, $page, $limit, $params) {
    $params['page'] = $page;
    $params['limit'] = $limit;
    return $baseUrl . '?' . http_build_query($params);
}
?>

<!-- Pagination + Per Page Controls -->
<?php if ($totalPages > 1 || $total > 50): ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 mb-3">
    <!-- Records per page -->
    <div class="d-flex align-items-center gap-2 small">
        <span class="text-muted">Records per page:</span>
        <?php foreach ([50, 100, 200, 500] as $opt): ?>
            <a href="<?= buildPageUrl($baseUrl, 1, $opt, $filterParams) ?>"
               class="btn btn-sm py-0 px-2 <?= ($limit === $opt) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $opt ?>
            </a>
        <?php endforeach; ?>
        <span class="text-muted ms-2">
            Showing <?= number_format(($currentPage - 1) * $limit + 1) ?>–<?= number_format(min($currentPage * $limit, $total)) ?> of <?= number_format($total) ?>
        </span>
    </div>

    <!-- Page navigation -->
    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildPageUrl($baseUrl, 1, $limit, $filterParams) ?>"><i class="fas fa-angle-double-left"></i></a>
            </li>
            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildPageUrl($baseUrl, $currentPage - 1, $limit, $filterParams) ?>"><i class="fas fa-angle-left"></i></a>
            </li>
            <?php
            $start = max(1, $currentPage - 2);
            $end   = min($totalPages, $currentPage + 2);
            if ($start > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
            for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= buildPageUrl($baseUrl, $p, $limit, $filterParams) ?>"><?= $p ?></a>
                </li>
            <?php endfor;
            if ($end < $totalPages): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildPageUrl($baseUrl, $currentPage + 1, $limit, $filterParams) ?>"><i class="fas fa-angle-right"></i></a>
            </li>
            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildPageUrl($baseUrl, $totalPages, $limit, $filterParams) ?>"><i class="fas fa-angle-double-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($isSuperAdmin): ?>

<!-- ============================================================ -->
<!-- EDIT LEARNER RECORD — OFFCANVAS FORM                          -->
<!-- ============================================================ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="editLearnerOffcanvas" aria-labelledby="editLearnerOffcanvasLabel">
    <div class="offcanvas-header bg-primary text-white py-2 px-3">
        <h6 class="offcanvas-title fw-bold mb-0" id="editLearnerOffcanvasLabel">
            <i class="fas fa-edit me-1"></i>Edit Learner Record
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2">
        <form id="editLearnerForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>
            <input type="hidden" name="id" id="edit_learner_id">
            <div class="row g-1.5">
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Learner Name</label>
                    <input type="text" name="learner_name" id="edit_learner_name" class="form-control form-control-sm bg-light" readonly>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">ITGK Code</label>
                    <input type="text" name="itgk_code" id="edit_learner_itgk" class="form-control form-control-sm bg-light" readonly>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Course</label>
                    <input type="text" name="course_name" id="edit_learner_course" class="form-control form-control-sm bg-light" readonly>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Result</label>
                    <input type="text" name="result" id="edit_learner_result" class="form-control form-control-sm bg-light" readonly>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Certificate No</label>
                    <input type="text" name="certificate_no" id="edit_learner_cert" class="form-control form-control-sm" placeholder="Certificate number">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Status</label>
                    <select name="status" id="edit_learner_status" class="form-select form-select-sm">
                        <option value="Available">Available</option>
                        <option value="Issued">Issued</option>
                        <option value="Not Received">Not Received</option>
                    </select>
                </div>
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Remark</label>
                    <textarea name="remark" class="form-control form-control-sm" rows="2" placeholder="Notes..."></textarea>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm py-1 w-100 fw-bold" id="btnEditLearnerSubmit">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ADD LEARNER RESULT OFFCANVAS FORM -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="addLearnerOffcanvas" aria-labelledby="addLearnerOffcanvasLabel">
    <div class="offcanvas-header bg-success text-white py-2 px-3">
        <h6 class="offcanvas-title fw-bold mb-0" id="addLearnerOffcanvasLabel">
            <i class="fas fa-plus-circle me-1"></i>Add Learner Result
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2">
        <form method="POST" id="addLearnerForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>
            <div class="row g-1.5">
                <div class="col-md-2 mb-1">
                    <label class="form-label fw-semibold small mb-0">S No.</label>
                    <input type="number" name="s_no" class="form-control form-control-sm" placeholder="S No.">
                </div>
                <div class="col-md-5 mb-1">
                    <label class="form-label fw-semibold small mb-0">Receiving Date</label>
                    <input type="date" name="receiving_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-5 mb-1">
                    <label class="form-label fw-semibold small mb-0">ITGK Code</label>
                    <input name="itgk_code" class="form-control form-control-sm" placeholder="ITGK Code" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Learner Code</label>
                    <input name="learner_code" class="form-control form-control-sm" placeholder="Learner Code">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Learner Name</label>
                    <input name="learner_name" class="form-control form-control-sm" placeholder="Learner Name" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Father Name</label>
                    <input name="father_name" class="form-control form-control-sm" placeholder="Father Name">
                </div>
                <div class="col-md-3 mb-1">
                    <label class="form-label fw-semibold small mb-0">Total Marks</label>
                    <input type="number" step="0.01" name="total_marks" class="form-control form-control-sm" value="100">
                </div>
                <div class="col-md-3 mb-1">
                    <label class="form-label fw-semibold small mb-0">Marks Obt.</label>
                    <input type="number" step="0.01" name="marks_obtained" class="form-control form-control-sm" value="0">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Percentage</label>
                    <input type="number" step="0.01" name="percentage" class="form-control form-control-sm" value="0">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Result</label>
                    <select name="result" class="form-select form-select-sm" required>
                        <option value="PASS" selected>PASS</option>
                        <option value="FAIL">FAIL</option>
                        <option value="ABSENT">ABSENT</option>
                        <option value="UFM">UFM</option>
                    </select>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Certificate No</label>
                    <input name="certificate_no" class="form-control form-control-sm" placeholder="Certificate No">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Course Name</label>
                    <input name="course_name" class="form-control form-control-sm" placeholder="Course Name" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Exam Name</label>
                    <input name="exam_name" class="form-control form-control-sm" placeholder="Exam Name" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Exam Date</label>
                    <input type="date" name="exam_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="Not Received">Not Received</option>
                        <option value="Available" selected>Available</option>
                        <option value="Issued">Issued</option>
                        <option value="InTransit">In Transit</option>
                    </select>
                </div>
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Remark</label>
                    <textarea name="remark" class="form-control form-control-sm" rows="2" placeholder="Remark"></textarea>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" name="add_learner" class="btn btn-success btn-sm py-1 w-100 fw-bold">
                        <i class="fas fa-save me-1"></i>Save Learner Result
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ISSUE LEARNER CERTIFICATE OFFCANVAS FORM -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="issueLearnerOffcanvas" aria-labelledby="issueLearnerOffcanvasLabel">
    <div class="offcanvas-header bg-primary text-white py-2 px-3">
        <h6 class="offcanvas-title fw-bold mb-0" id="issueLearnerOffcanvasLabel"><i class="fas fa-graduation-cap me-1"></i>Issue Learner Certificate</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2">
        <form id="issueLearnerForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>
            <input type="hidden" name="learner_id" id="issue_learner_id">
            <div class="mb-1">
                <label class="form-label fw-semibold small mb-0">Learner Name</label>
                <input type="text" id="issue_learner_name" class="form-control form-control-sm bg-light" readonly>
            </div>
            <div class="mb-1">
                <label class="form-label fw-semibold small mb-0">Certificate Number <span class="text-danger">*</span></label>
                <input type="text" name="certificate_no" id="issue_certificate_no" class="form-control form-control-sm" placeholder="e.g. CERT-10023" required>
            </div>
            <div class="mb-1">
                <label class="form-label fw-semibold small mb-0">Learner / Parent Email (for Email Receipt)</label>
                <input type="email" name="learner_email" class="form-control form-control-sm" placeholder="learner@example.com">
            </div>
            <div class="mb-2">
                <label class="form-label fw-semibold small mb-0">Dispatch / Handover Remarks</label>
                <textarea name="remark" class="form-control form-control-sm" rows="2" placeholder="Handed over directly or dispatched by post..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm py-1 w-100 fw-bold">
                <i class="fas fa-paper-plane me-1"></i>Issue Certificate &amp; Send Email Receipt
            </button>
        </form>
    </div>
</div>

<script>
// Add Learner Form Submit
document.getElementById('addLearnerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (typeof window.showLoader === 'function') window.showLoader('Saving Learner Result...');

    const formData = new FormData(this);
    try {
        const res = await fetch('<?= BASE_URL ?>learners/create', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            alert('Learner Result Created Successfully!');
            window.location.reload();
        } else {
            alert('Error creating learner result: ' + (json.message || 'Error'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    } finally {
        if (typeof window.hideLoader === 'function') window.hideLoader();
    }
});

// Issue Learner button — populate offcanvas
document.querySelectorAll('.btn-issue-learner').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('issue_learner_id').value  = this.getAttribute('data-id');
        document.getElementById('issue_learner_name').value = this.getAttribute('data-name');
        document.getElementById('issue_certificate_no').value = this.getAttribute('data-cert');
    });
});

// Edit Learner button — populate edit offcanvas
document.querySelectorAll('.btn-edit-learner').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_learner_id').value     = this.getAttribute('data-id');
        document.getElementById('edit_learner_name').value   = this.getAttribute('data-name');
        document.getElementById('edit_learner_itgk').value   = this.getAttribute('data-itgk');
        document.getElementById('edit_learner_course').value = this.getAttribute('data-course');
        document.getElementById('edit_learner_result').value = this.getAttribute('data-result');
        document.getElementById('edit_learner_cert').value   = this.getAttribute('data-cert');
        const statusEl = document.getElementById('edit_learner_status');
        if (statusEl) statusEl.value = this.getAttribute('data-status') || 'Available';
    });
});

// Edit Learner Form Submit
document.getElementById('editLearnerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnEditLearnerSubmit');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...'; }
    if (typeof window.showLoader === 'function') window.showLoader('Saving Learner Record...');
    try {
        const res  = await fetch('<?= BASE_URL ?>learners/update', { method: 'POST', body: new FormData(this) });
        const json = await res.json();
        if (json.success) {
            alert('✅ Learner Record Updated!');
            location.reload();
        } else {
            alert('❌ ' + (json.message || 'Update failed'));
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes'; }
        }
    } catch (err) {
        alert('Network error: ' + err.message);
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes'; }
    } finally {
        if (typeof window.hideLoader === 'function') window.hideLoader();
    }
});

// Issue Learner Form Submit
document.getElementById('issueLearnerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (typeof window.showLoader === 'function') window.showLoader('Issuing Learner Certificate & Sending Email Receipt...');

    const formData = new FormData(this);
    try {
        const res = await fetch('<?= BASE_URL ?>learners/issue', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            alert('Learner Certificate Issued Successfully!');
            if (json.acknowledgement_url) {
                window.open(json.acknowledgement_url, '_blank');
            }
            window.location.reload();
        } else {
            alert('Error issuing certificate: ' + (json.message || 'Error'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    } finally {
        if (typeof window.hideLoader === 'function') window.hideLoader();
    }
});
</script>
<?php endif; ?>