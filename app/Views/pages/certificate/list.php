<?php
// JS handles pagination -- only need total count and role check
$total        = (int)($total ?? count($certificates ?? []));
$isSuperAdmin = \App\Services\AuthService::isSuperAdmin();
$canIssue     = \App\Services\AuthService::isAdmin(); // ADMIN+ can do bulk issue
?>

<div class="row mb-1">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-0"><i class="fas fa-certificate text-primary me-1"></i>ITGK Certificates</h3>
            <p class="text-muted small mb-0">
                Showing <strong><?= number_format(count($certificates ?? [])) ?></strong> of
                <strong><?= number_format($total) ?></strong> total Certificate Packets &mdash;
                Google Sheet (Tab: <?= htmlspecialchars($sheetTab ?? 'Certificate') ?>)
            </p>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <?php if ($isSuperAdmin): ?>
                <button type="button" id="btnConsolidate" class="btn btn-warning btn-sm fw-bold">
                    <i class="fas fa-cogs me-1"></i>Consolidate Student Results
                </button>
                <button type="button" class="btn btn-success btn-sm fw-bold"
                    data-bs-toggle="offcanvas" data-bs-target="#addItgkOffcanvas">
                    <i class="fas fa-plus-circle me-1"></i>Add Packet
                </button>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>itgk/list" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sync-alt me-1"></i>Refresh Sheet
            </a>
        </div>
    </div>
</div>

<?php if (!empty($sheetError)): ?>
    <div class="alert alert-danger py-1 small"><i class="fas fa-exclamation-triangle me-1"></i><?= htmlspecialchars($sheetError) ?></div>
<?php endif; ?>

<!-- Analytics Cards -->
<?php if (!empty($analytics)): ?>
    <div class="row g-1 mb-1 text-center">
        <div class="col-4 col-md-2">
            <div class="card shadow-sm border-0 bg-primary bg-opacity-10">
                <div class="card-body py-2">
                    <i class="fas fa-certificate fa-lg text-primary mb-1"></i>
                    <h5 class="fw-bold mb-0 text-primary"><?= number_format($analytics['total'] ?? $total) ?></h5>
                    <small class="text-muted">Total Certificates</small>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="card shadow-sm border-0 bg-success bg-opacity-10">
                <div class="card-body py-2">
                    <i class="fas fa-check-circle fa-lg text-success mb-1"></i>
                    <h5 class="fw-bold mb-0 text-success"><?= number_format($analytics['available'] ?? 0) ?></h5>
                    <small class="text-muted">Available</small>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="card shadow-sm border-0 bg-info bg-opacity-10">
                <div class="card-body py-2">
                    <i class="fas fa-hand-holding fa-lg text-info mb-1"></i>
                    <h5 class="fw-bold mb-0 text-info"><?= number_format($analytics['issued'] ?? 0) ?></h5>
                    <small class="text-muted">Issued</small>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="card shadow-sm border-0 bg-warning bg-opacity-10">
                <div class="card-body py-2">
                    <i class="fas fa-truck fa-lg text-warning mb-1"></i>
                    <h5 class="fw-bold mb-0 text-warning"><?= number_format($analytics['intransit'] ?? 0) ?></h5>
                    <small class="text-muted">In Transit</small>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Table -->
<div class="card-modern glass-card">
    <div class="card-header py-1 bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center gap-1 flex-wrap">
            <h6 class="mb-0">
                <i class="fas fa-list me-1"></i>
                ITGK Certificates &mdash; <span id="certVisibleCount"><?= number_format($total) ?></span> / <?= number_format($total) ?> records
            </h6>
            <div class="d-flex align-items-center gap-2">
                <input type="search" id="certSearch"
                    class="form-control form-control-sm"
                    placeholder="&#x1F50D; Search ITGK, Course, Exam, Status..."
                    style="width:230px;background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3);color:#fff;"
                    autocomplete="off">
                <select id="certPerPage" class="form-select form-select-sm" style="width:75px;background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3);color:#fff;" title="Records per page">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                </select>
                <span class="badge bg-light text-dark text-nowrap">Live Sheet</span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($certificates)): ?>
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th style="width:32px;">
                                <input type="checkbox" id="chkSelectAll" class="form-check-input" title="Select all visible Available rows">
                            </th>

                            <th style="width:30px;"></th><!-- expand toggle -->
                            <th style="width:50px;">S.No</th>
                            <th>Course &amp; Date</th>
                            <th>Exam</th>
                            <th>ITGK &amp; District</th>
                            <th>P / F / A / Tot</th>
                            <th>Packet &amp; Cert Nos</th>
                            <th>Status</th>
                            <?php if ($isSuperAdmin): ?><th class="text-end">Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="certTbody">
                        <!-- Empty state row (shown by JS when search = no results) -->
                        <tr id="certEmptyRow" style="display:none;">
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-search fa-2x mb-2 d-block opacity-50"></i>
                                <span id="certEmptyMsg">No matching records found.</span>
                            </td>
                        </tr>
                        <?php foreach ($certificates as $idx => $row): ?>
                            <?php
                            $rowId  = htmlspecialchars((string)($row['id'] ?? ($idx + 1)));
                            $status = strtoupper(trim((string)($row['status'] ?? 'AVAILABLE')));
                            $bgBadge = match (true) {
                                str_contains($status, 'ISSUED')  => 'bg-info text-dark',
                                str_contains($status, 'AVAIL')   => 'bg-success',
                                str_contains($status, 'TRANSIT') => 'bg-warning text-dark',
                                default                          => 'bg-secondary',
                            };
                            ?>
                            <!-- Main row -->
                            <tr class="cert-main-row" data-row="<?= $idx ?>" style="cursor:pointer;" title="Click to expand details">
                                <!-- Checkbox for bulk selection (Available rows only) -->
                                <td class="text-center" onclick="event.stopPropagation()" style="width:32px;">
                                    <?php if (str_contains($status, 'AVAIL')): ?>
                                        <input type="checkbox"
                                            class="form-check-input cert-select-chk"
                                            data-sheet-row="<?= (int)($row['sheet_row'] ?? 0) ?>"
                                            data-id="<?= $rowId ?>"
                                            data-itgk="<?= htmlspecialchars((string)($row['itgk_code'] ?? '')) ?>"
                                            data-district="<?= htmlspecialchars((string)($row['district'] ?? '')) ?>"
                                            data-course="<?= htmlspecialchars((string)($row['course_name'] ?? '')) ?>"
                                            data-exam="<?= htmlspecialchars((string)($row['exam_name'] ?? '')) ?>"
                                            data-packet="<?= htmlspecialchars((string)($row['packet_no'] ?? '')) ?>"
                                            data-total="<?= (int)($row['grand_total'] ?? 0) ?>"
                                            title="Select for bulk issue">
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <i class="fas fa-chevron-right expand-icon text-muted" style="font-size:10px;transition:transform .2s"></i>
                                </td>
                                <td class="fw-bold text-muted"><?= $rowId ?></td>
                                <td>
                                    <div class="fw-bold text-primary"><?= htmlspecialchars((string)($row['course_name'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($row['receiving_date'] ?? '')) ?></div>
                                </td>
                                <td><?= htmlspecialchars((string)($row['exam_name'] ?? '')) ?></td>
                                <td>
                                    <code><?= htmlspecialchars((string)($row['itgk_code'] ?? '')) ?></code>
                                    <?php if (!empty($row['district'])): ?>
                                        <div class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars((string)$row['district']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success" title="Pass"><?= (int)($row['pass'] ?? 0) ?>P</span>
                                    <span class="badge bg-danger" title="Fail"><?= (int)($row['fail'] ?? 0) ?>F</span>
                                    <span class="badge bg-secondary" title="Absent"><?= (int)($row['absent'] ?? 0) ?>A</span>
                                    <span class="badge bg-dark" title="Total">Tot:<?= (int)($row['grand_total'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['packet_no'])): ?>
                                        <div><small>Pkt:</small> <code><?= htmlspecialchars((string)$row['packet_no']) ?></code></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['cert_no_from']) || !empty($row['cert_no_to'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars((string)($row['cert_no_from'] ?? '')) ?> â€“ <?= htmlspecialchars((string)($row['cert_no_to'] ?? '')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $bgBadge ?>"><?= htmlspecialchars((string)($row['status'] ?? 'Available')) ?></span></td>
                                <?php if ($isSuperAdmin): ?>
                                    <td class="text-end">
                                        <!-- View Details -->
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm py-0 px-2 btn-view-cert"
                                            data-row="<?= $idx ?>" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <!-- Edit -->
                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm py-0 px-2 btn-edit-cert"
                                            data-id="<?= $rowId ?>"
                                            data-course="<?= htmlspecialchars((string)($row['course_name'] ?? '')) ?>"
                                            data-exam="<?= htmlspecialchars((string)($row['exam_name'] ?? '')) ?>"
                                            data-itgk="<?= htmlspecialchars((string)($row['itgk_code'] ?? '')) ?>"
                                            data-district="<?= htmlspecialchars((string)($row['district'] ?? '')) ?>"
                                            data-date="<?= htmlspecialchars((string)($row['receiving_date'] ?? '')) ?>"
                                            data-examdate="<?= htmlspecialchars((string)($row['exam_date'] ?? '')) ?>"
                                            data-pass="<?= (int)($row['pass'] ?? 0) ?>"
                                            data-fail="<?= (int)($row['fail'] ?? 0) ?>"
                                            data-absent="<?= (int)($row['absent'] ?? 0) ?>"
                                            data-total="<?= (int)($row['grand_total'] ?? 0) ?>"
                                            data-packet="<?= htmlspecialchars((string)($row['packet_no'] ?? '')) ?>"
                                            data-certfrom="<?= htmlspecialchars((string)($row['cert_no_from'] ?? '')) ?>"
                                            data-certto="<?= htmlspecialchars((string)($row['cert_no_to'] ?? '')) ?>"
                                            data-status="<?= htmlspecialchars((string)($row['status'] ?? '')) ?>"
                                            data-location="<?= htmlspecialchars((string)($row['current_location'] ?? '')) ?>"
                                            data-remark="<?= htmlspecialchars((string)($row['remark'] ?? '')) ?>"
                                            data-receiver="<?= htmlspecialchars((string)($row['receiver_name'] ?? '')) ?>"
                                            data-desig="<?= htmlspecialchars((string)($row['receiver_designation'] ?? '')) ?>"
                                            data-mobile="<?= htmlspecialchars((string)($row['receiver_mobile'] ?? '')) ?>"
                                            data-sheetrow="<?= (int)($row['sheet_row'] ?? 0) ?>"
                                            data-bs-toggle="offcanvas" data-bs-target="#editCertOffcanvas"
                                            title="Edit Record">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Issue -> Bulk Issue offcanvas -->
                                        <button type="button" class="btn btn-outline-success btn-sm py-0 px-2 btn-quick-issue"
                                            data-sheet-row="<?= (int)($row['sheet_row'] ?? 0) ?>"
                                            data-id="<?= $rowId ?>"
                                            data-itgk="<?= htmlspecialchars((string)($row['itgk_code'] ?? '')) ?>"
                                            data-district="<?= htmlspecialchars((string)($row['district'] ?? '')) ?>"
                                            data-course="<?= htmlspecialchars((string)($row['course_name'] ?? '')) ?>"
                                            data-exam="<?= htmlspecialchars((string)($row['exam_name'] ?? '')) ?>"
                                            data-packet="<?= htmlspecialchars((string)($row['packet_no'] ?? '')) ?>"
                                            data-total="<?= (int)($row['grand_total'] ?? 0) ?>"
                                            title="Issue Packet">
                                            <i class="fas fa-hand-holding-heart"></i>
                                        </button>
                                        <?php if (str_contains($status, 'ISSUED')): ?>
                                            <a href="<?= BASE_URL ?>itgk/acknowledgement?id=<?= urlencode($rowId) ?>"
                                                target="_blank" class="btn btn-outline-info btn-sm py-0 px-2" title="Print Receipt">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <!-- Expanded detail row (hidden by default) -->
                            <tr class="cert-detail-row d-none" data-row="<?= $idx ?>">
                                <td colspan="<?= $isSuperAdmin ? 9 : 8 ?>" class="bg-light p-0">
                                    <div class="p-3 border-top">
                                        <div class="row g-2 small">
                                            <div class="col-md-3">
                                                <strong>Current Location:</strong><br>
                                                <?= htmlspecialchars((string)($row['current_location'] ?? 'N/A')) ?>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Exam Date:</strong><br>
                                                <?= htmlspecialchars((string)($row['exam_date'] ?? '-')) ?>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Receiver Info:</strong><br>
                                                <?php if (!empty($row['receiver_name'])): ?>
                                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars((string)$row['receiver_name']) ?>
                                                    <?php if (!empty($row['receiver_designation'])): ?>
                                                        <br><span class="text-muted"><?= htmlspecialchars((string)$row['receiver_designation']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['receiver_mobile'])): ?>
                                                        <br><i class="fas fa-phone me-1"></i><?= htmlspecialchars((string)$row['receiver_mobile']) ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not yet issued</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Remark:</strong><br>
                                                <?= htmlspecialchars((string)($row['remark'] ?? '-')) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No certificates found in Google Sheet</h5>
                <p class="small text-muted">Click "Refresh Sheet" above to reload data from your connected Google Sheet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- Floating Bulk-Selection Action Bar (appears when rows are checked) -->
<div id="bulkActionBar"
    style="display:none;position:sticky;bottom:16px;z-index:1040;
            background:linear-gradient(135deg,#1a56db,#0e9f6e);
            color:#fff;border-radius:10px;padding:8px 16px;
            box-shadow:0 4px 20px rgba(0,0,0,.35);
            max-width:700px;margin:8px auto;"
    class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
    <span class="fw-bold">
        <i class="fas fa-check-square me-2"></i>
        <span id="bulkSelCount">0</span> certificate(s) selected
        &mdash; ITGK: <span id="bulkSelItgk" class="badge bg-white text-dark">--</span>
    </span>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm fw-bold" id="btnClearSel">
            <i class="fas fa-times me-1"></i>Clear
        </button>
        <button type="button" class="btn btn-warning btn-sm fw-bold" id="btnOpenBulkIssue"
            data-bs-toggle="offcanvas" data-bs-target="#bulkIssueOffcanvas">
            <i class="fas fa-paper-plane me-1"></i>Bulk Issue Selected
        </button>
    </div>
</div>

<!-- JS Pagination Controls (outside card so always visible) -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 mb-3 px-1">
    <small class="text-muted fw-semibold" id="certShowingText">Loading...</small>
    <nav aria-label="Certificate pagination" id="certPagination" style="min-height:32px;"></nav>
</div>


<!-- ============================================================ -->
<!-- BULK ISSUE OFFCANVAS FORM                                     -->
<!-- ============================================================ -->

<div class="offcanvas offcanvas-end" tabindex="-1" id="bulkIssueOffcanvas"
    aria-labelledby="bulkIssueOffcanvasLabel" style="width:520px;">
    <div class="offcanvas-header py-2 px-3 text-white" style="background:linear-gradient(135deg,#1a56db,#0e9f6e);">
        <h6 class="offcanvas-title fw-bold mb-0" id="bulkIssueOffcanvasLabel">
            <i class="fas fa-paper-plane me-1"></i>Bulk Issue Certificates
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2">
        <form id="bulkIssueForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>
            <input type="hidden" name="selections" id="bi_selections_json">

            <!-- ITGK Same-Code Validation Warning -->
            <div id="bi_itgk_warn" class="alert alert-warning py-1 px-2 small mb-2" style="display:none;">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <span id="bi_itgk_warn_text"></span>
            </div>

            <!-- ITGK Info Panel -->
            <div class="card border-0 bg-primary bg-opacity-10 mb-2">
                <div class="card-body py-2 px-3">
                    <div class="row g-1 small">
                        <div class="col-6">
                            <span class="text-muted">ITGK Code(s):</span>
                            <div class="fw-bold" id="bi_itgk_codes">--</div>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">District:</span>
                            <div class="fw-bold" id="bi_district">--</div>
                        </div>
                        <div class="col-12">
                            <span class="text-muted">ITGK Name:</span>
                            <div class="fw-bold" id="bi_itgk_name">--</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Certificates Table -->
            <div class="mb-2">
                <div class="fw-semibold small text-primary mb-1">
                    <i class="fas fa-list me-1"></i>
                    Selected Certificates (<span id="bi_sel_count">0</span>)
                </div>
                <div style="max-height:180px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
                    <table class="table table-sm table-hover mb-0" style="font-size:11px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>S.No</th>
                                <th>ITGK</th>
                                <th>Course</th>
                                <th>Exam</th>
                                <th>Packet</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="bi_certs_table_body">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-2">No certificates selected</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr class="my-2">

            <!-- Receiver Details -->
            <div class="fw-semibold small text-success mb-1">
                <i class="fas fa-user-check me-1"></i>Receiver Details (ITGK Representative)
            </div>
            <div class="row g-1 mb-2">
                <div class="col-12">
                    <label class="form-label fw-semibold small mb-0">Receiver Name <span class="text-danger">*</span></label>
                    <input type="text" name="receiver_name" id="bi_receiver_name"
                        class="form-control form-control-sm" placeholder="e.g. Ramesh Kumar" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-0">Designation</label>
                    <input type="text" name="receiver_designation" id="bi_receiver_desig"
                        class="form-control form-control-sm" placeholder="ITGK Head / Coordinator">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-0">Mobile</label>
                    <input type="text" name="receiver_mobile" id="bi_receiver_mob"
                        class="form-control form-control-sm" placeholder="98XXXXXXXX">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small mb-0">Email <span class="text-muted small">(Optional)</span></label>
                    <input type="email" name="receiver_email" id="bi_receiver_email"
                        class="form-control form-control-sm" placeholder="itgk@example.com">
                </div>
            </div>

            <!-- Issuer Details (auto-filled from session) -->
            <div class="fw-semibold small text-info mb-1">
                <i class="fas fa-user-tie me-1"></i>Issuer Details (Office Representative)
            </div>
            <div class="row g-1 mb-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-0">Issuer Name</label>
                    <input type="text" name="issuer_name" id="bi_issuer_name"
                        class="form-control form-control-sm"
                        value="<?= htmlspecialchars(\App\Services\AuthService::user()['name'] ?? '') ?>"
                        placeholder="Your Name">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-0">Issuer Designation</label>
                    <input type="text" name="issuer_designation" id="bi_issuer_desig"
                        class="form-control form-control-sm"
                        value="<?= htmlspecialchars(\App\Services\AuthService::user()['role'] ?? '') ?>"
                        placeholder="Your Designation">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-0">Issuer Mobile</label>
                    <input type="text" name="issuer_mobile" id="bi_issuer_mobile"
                        class="form-control form-control-sm"
                        value="<?= htmlspecialchars((string)(\App\Services\AuthService::user()['mobile'] ?? '')) ?>"
                        placeholder="Mobile Number">
                </div>
            </div>

            <!-- Remark -->
            <div class="mb-2">
                <label class="form-label fw-semibold small mb-0">Handover Remark</label>
                <textarea name="remark" id="bi_remark" class="form-control form-control-sm" rows="2"
                    placeholder="Dispatch note, reference number, handover note..."></textarea>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-success btn-sm py-1 w-100 fw-bold" id="btnBulkIssueSubmit">
                <i class="fas fa-paper-plane me-1"></i>Issue All Selected Certificates
            </button>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD ITGK CERTIFICATE -- OFFCANVAS FORM                         -->
<!-- ============================================================ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="addItgkOffcanvas" aria-labelledby="addItgkOffcanvasLabel">
    <div class="offcanvas-header bg-primary text-white py-2 px-3">
        <h6 class="offcanvas-title fw-bold mb-0" id="addItgkOffcanvasLabel">
            <i class="fas fa-plus-circle me-1"></i>Add ITGK Certificate
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2">
        <form method="POST" id="addItgkForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>
            <div class="row g-1.5">
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Course Name <span class="text-danger">*</span></label>
                    <input name="course_name" class="form-control form-control-sm" placeholder="e.g. RSCIT" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Receiving Date <span class="text-danger">*</span></label>
                    <input type="date" name="receiving_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Exam Name <span class="text-danger">*</span></label>
                    <input name="exam_name" class="form-control form-control-sm" placeholder="e.g. General Exam 2026" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Exam Date</label>
                    <input type="date" name="exam_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">ITGK Code <span class="text-danger">*</span></label>
                    <select name="itgk_code" id="add_itgk_code_select" class="form-select form-select-sm" required>
                        <option value="">-- Select ITGK --</option>
                        <?php if (!empty($itgkList)): ?>
                            <?php foreach ($itgkList as $itgk): ?>
                                <option value="<?= htmlspecialchars((string)$itgk['code']) ?>" data-district="<?= htmlspecialchars((string)$itgk['district']) ?>">
                                    <?= htmlspecialchars((string)$itgk['code']) ?> - <?= htmlspecialchars((string)$itgk['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">District</label>
                    <input name="district" id="add_district_input" class="form-control form-control-sm" placeholder="District name">
                </div>
                <div class="col-md-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">Packet No</label>
                    <input name="packet_no" class="form-control form-control-sm" placeholder="Packet number">
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label fw-semibold small mb-0">Pass</label>
                    <input type="number" name="pass" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label fw-semibold small mb-0">Fail</label>
                    <input type="number" name="fail" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label fw-semibold small mb-0">Absent</label>
                    <input type="number" name="absent" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label fw-semibold small mb-0">UFM</label>
                    <input type="number" name="ufm" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                </div>
                <div class="col-md-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">Cert No From</label>
                    <input name="cert_no_from" class="form-control form-control-sm" placeholder="Starting cert no.">
                </div>
                <div class="col-md-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">Cert No To</label>
                    <input name="cert_no_to" class="form-control form-control-sm" placeholder="Ending cert no.">
                </div>
                <div class="col-md-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">Grand Total</label>
                    <input type="number" name="grand_total" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Current Location</label>
                    <input name="current_location" class="form-control form-control-sm" placeholder="Storage location">
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
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm py-1 w-100 fw-bold" id="btnAddItgkSubmit">
                        <i class="fas fa-save me-1"></i>Save Certificate Packet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

</div>

<!-- ============================================================ -->
<!-- EDIT CERTIFICATE RECORD -- OFFCANVAS FORM (Saves to GSheet)   -->
<!-- ============================================================ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="editCertOffcanvas" aria-labelledby="editCertOffcanvasLabel" style="width:440px">
    <div class="offcanvas-header bg-warning text-dark py-2 px-3">
        <h6 class="offcanvas-title fw-bold mb-0" id="editCertOffcanvasLabel">
            <i class="fas fa-edit me-1"></i>Edit Certificate Record
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2">
        <form id="editCertForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>
            <input type="hidden" name="sheet_row" id="ec_sheet_row">
            <div class="row g-1.5">
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Course Name</label>
                    <input type="text" name="course_name" id="ec_course" class="form-control form-control-sm" placeholder="Course Name">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Exam Name</label>
                    <input type="text" name="exam_name" id="ec_exam" class="form-control form-control-sm" placeholder="Exam Name">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">ITGK Code</label>
                    <input type="text" name="itgk_code" id="ec_itgk" class="form-control form-control-sm" placeholder="ITGK Code">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">District</label>
                    <input type="text" name="district" id="ec_district" class="form-control form-control-sm" placeholder="District">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Receiving Date</label>
                    <input type="text" name="receiving_date" id="ec_date" class="form-control form-control-sm" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Exam Date</label>
                    <input type="text" name="exam_date" id="ec_examdate" class="form-control form-control-sm" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">Pass</label>
                    <input type="number" name="pass" id="ec_pass" class="form-control form-control-sm" min="0">
                </div>
                <div class="col-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">Fail</label>
                    <input type="number" name="fail" id="ec_fail" class="form-control form-control-sm" min="0">
                </div>
                <div class="col-4 mb-1">
                    <label class="form-label fw-semibold small mb-0">Absent</label>
                    <input type="number" name="absent" id="ec_absent" class="form-control form-control-sm" min="0">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Grand Total</label>
                    <input type="number" name="grand_total" id="ec_total" class="form-control form-control-sm" min="0">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Packet No.</label>
                    <input type="text" name="packet_no" id="ec_packet" class="form-control form-control-sm" placeholder="Packet No.">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Cert No. From</label>
                    <input type="text" name="cert_no_from" id="ec_certfrom" class="form-control form-control-sm" placeholder="From">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Cert No. To</label>
                    <input type="text" name="cert_no_to" id="ec_certto" class="form-control form-control-sm" placeholder="To">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Status</label>
                    <select name="status" id="ec_status" class="form-select form-select-sm">
                        <option value="">-- Select --</option>
                        <option value="Not Received">Not Received</option>
                        <option value="Available">Available</option>
                        <option value="Issued">Issued</option>
                        <option value="InTransit">In Transit</option>
                    </select>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Current Location</label>
                    <select name="current_location" id="ec_location" class="form-select form-select-sm">
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locationOptions ?? [] as $loc): ?>
                        <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Remark</label>
                    <textarea name="remark" id="ec_remark" class="form-control form-control-sm" rows="2" placeholder="Remark"></textarea>
                </div>
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Receiver Name</label>
                    <input type="text" name="receiver_name" id="ec_receiver" class="form-control form-control-sm" placeholder="Receiver Name">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Designation</label>
                    <input type="text" name="receiver_designation" id="ec_desig" class="form-control form-control-sm" placeholder="Designation">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Mobile</label>
                    <input type="text" name="receiver_mobile" id="ec_mobile" class="form-control form-control-sm" placeholder="Mobile Number">
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-warning btn-sm py-1 w-100 fw-bold" id="btnEditCertSubmit">
                        <i class="fas fa-save me-1"></i>Save to Google Sheet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Premium Toast Notification
    function showToast(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        const toastId = 'toast_' + Date.now();
        const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : 'bg-warning text-dark');
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle');
        const html = `
            <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="fas ${icon} fs-5"></i>
                        <div>${message}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(toastId);
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }
    }

    // =================================================================
    // Client-side Pagination + Live Search Engine
    // NOTE: This view is included in a layout -- DOMContentLoaded has
    //       already fired. Run directly as IIFE instead.
    // =================================================================
    (function initCertPagination() {
        var allMainRows = Array.from(document.querySelectorAll('.cert-main-row'));
        var filtered = allMainRows.slice();
        var curPage = 1;
        var perPageEl = document.getElementById('certPerPage');
        var perPage = perPageEl ? parseInt(perPageEl.value, 10) : 10;

        // Paired detail row lookup
        function detailOf(mainRow) {
            return document.querySelector('.cert-detail-row[data-row="' + mainRow.dataset.row + '"]');
        }

        // ----- Search / Filter -------------------------------------------
        function applySearch(q) {
            q = q.toLowerCase().trim();
            filtered = q ?
                allMainRows.filter(r => r.textContent.toLowerCase().includes(q)) :
                allMainRows.slice();
            curPage = 1;
            render();
        }

        // ----- Render page -----------------------------------------------
        function render() {
            const total = filtered.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));
            curPage = Math.min(curPage, totalPages);
            const start = (curPage - 1) * perPage;
            const end = Math.min(start + perPage, total);

            // Build position map once -- O(n) instead of O(nÂ²) indexOf calls
            const posMap = new Map(filtered.map((r, i) => [r, i]));
            const filteredSet = new Set(filtered);

            // Show/hide rows
            allMainRows.forEach(function(row) {
                const detail = detailOf(row);
                if (!filteredSet.has(row)) {
                    row.style.display = 'none';
                    if (detail) detail.style.display = 'none';
                    return;
                }
                const pos = posMap.get(row);
                const show = pos >= start && pos < end;
                row.style.display = show ? '' : 'none';
                if (detail && !show) {
                    detail.style.display = 'none';
                    detail.classList.add('d-none');
                    const icon = row.querySelector('.expand-icon');
                    if (icon) icon.style.transform = '';
                }
            });

            // Empty state row
            const emptyRow = document.getElementById('certEmptyRow');
            if (emptyRow) {
                emptyRow.style.display = (total === 0) ? '' : 'none';
                const emptyMsg = document.getElementById('certEmptyMsg');
                if (emptyMsg) {
                    const q = document.getElementById('certSearch')?.value?.trim();
                    emptyMsg.textContent = q ?
                        'No records matching â€œ' + q + 'â€' :
                        'No records found.';
                }
            }

            // Showing info
            const showingEl = document.getElementById('certShowingText');
            if (showingEl) {
                showingEl.textContent = total === 0 ?
                    'No matching records' :
                    'Showing ' + (start + 1).toLocaleString() +
                    'â€“' + end.toLocaleString() +
                    ' of ' + total.toLocaleString();
            }

            // Header counter
            const cntEl = document.getElementById('certVisibleCount');
            if (cntEl) cntEl.textContent = total.toLocaleString();

            // Build pagination nav
            buildPagination(curPage, totalPages);

            // Re-bind checkboxes after each render (pagination/search changes visible rows)
            if (typeof window._bindCertCheckboxes === 'function') {
                window._bindCertCheckboxes();
            }
        }

        // ----- Build pagination buttons ----------------------------------
        function buildPagination(page, totalPages) {
            var nav = document.getElementById('certPagination');
            if (!nav) return;

            if (totalPages <= 1) {
                nav.innerHTML = '';
                return;
            }

            var html = '<ul class="pagination pagination-sm mb-0">';

            // First & Prev
            html += btn(page <= 1, '1', '&laquo;');
            html += btn(page <= 1, page - 1, '&lsaquo;');

            // Page number window
            var ws = Math.max(1, page - 2);
            var we = Math.min(totalPages, page + 2);
            if (ws > 1) html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            for (var p = ws; p <= we; p++) {
                html += '<li class="page-item' + (p === page ? ' active' : '') + '">' +
                    '<a class="page-link cert-pg-btn" data-p="' + p + '" href="#">' + p + '</a></li>';
            }
            if (we < totalPages) html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';

            // Next & Last
            html += btn(page >= totalPages, page + 1, '&rsaquo;');
            html += btn(page >= totalPages, totalPages, '&raquo;');

            html += '</ul>';
            nav.innerHTML = html;

            // Attach click events
            nav.querySelectorAll('.cert-pg-btn').forEach(function(a) {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    var p = parseInt(this.dataset.p, 10);
                    if (!isNaN(p) && p >= 1 && p <= totalPages) {
                        curPage = p;
                        render();
                        document.querySelector('.card-modern')?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }

        // Helper: make a page-item with disabled state
        function btn(disabled, p, label) {
            return '<li class="page-item' + (disabled ? ' disabled' : '') + '">' +
                '<a class="page-link cert-pg-btn" data-p="' + p + '" href="#">' + label + '</a></li>';
        }

        // ----- Wire up search input --------------------------------------
        var searchEl = document.getElementById('certSearch');
        if (searchEl) {
            var searchTimer;
            searchEl.addEventListener('input', function() {
                clearTimeout(searchTimer);
                var q = this.value;
                searchTimer = setTimeout(function() {
                    applySearch(q);
                }, 180);
            });
            searchEl.addEventListener('search', function() {
                applySearch(this.value);
            });
            // Placeholder color tweak
            searchEl.addEventListener('focus', function() {
                this.style.color = '#fff';
            });
        }

        // ----- Wire up per-page select -----------------------------------
        var perPageEl = document.getElementById('certPerPage');
        if (perPageEl) {
            perPageEl.addEventListener('change', function() {
                perPage = parseInt(this.value, 10);
                curPage = 1;
                render();
            });
        }

        // ----- Initial render --------------------------------------------
        render();

    })(); // end initCertPagination IIFE

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Expandable Rows -- click anywhere on main row (except buttons)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.querySelectorAll('.cert-main-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('a')) return;
            const idx = this.getAttribute('data-row');
            const detail = document.querySelector('.cert-detail-row[data-row="' + idx + '"]');
            const icon = this.querySelector('.expand-icon');
            if (detail) {
                detail.classList.toggle('d-none');
                if (icon) icon.style.transform = detail.classList.contains('d-none') ? '' : 'rotate(90deg)';
            }
        });
    });

    // View Details button (expand same row)
    document.querySelectorAll('.btn-view-cert').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const idx = this.getAttribute('data-row');
            const detail = document.querySelector('.cert-detail-row[data-row="' + idx + '"]');
            const icon = document.querySelector('.cert-main-row[data-row="' + idx + '"] .expand-icon');
            if (detail) {
                detail.classList.toggle('d-none');
                if (icon) icon.style.transform = detail.classList.contains('d-none') ? '' : 'rotate(90deg)';
            }
        });
    });

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Consolidate Button
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('btnConsolidate')?.addEventListener('click', async function() {
        if (!confirm('à¤¯à¤¹ Student Results à¤•à¥‹ Certificate Packets à¤®à¥‡à¤‚ group à¤•à¤°à¥‡à¤--à¤¾à¥¤ Continue?')) return;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Consolidating...';
        if (typeof window.showLoader === 'function') window.showLoader('Consolidating student results...');
        try {
            const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
            const fd = new FormData();
            fd.append('csrf_token', csrf);
            const res = await fetch('<?= BASE_URL ?>itgk/consolidate', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            if (json.success) {
                const s = json.stats || {};
                alert('âœ… Consolidation Done!\nInserted: ' + (s.inserted || 0) + '  Updated: ' + (s.updated || 0) + '  Skipped: ' + (s.skipped || 0) + '\nGroups: ' + (json.groups || 0));
                location.reload();
            } else {
                alert('âŒ ' + (json.message || 'Consolidation failed'));
            }
        } catch (err) {
            showToast('Network error: ' + err.message, 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cogs me-1"></i>Consolidate Student Results';
            if (typeof window.hideLoader === 'function') window.hideLoader();
        }
    });

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Add ITGK Form
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('addItgkForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnAddItgkSubmit');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        }
        if (typeof window.showLoader === 'function') window.showLoader('Saving Certificate Packet...');
        try {
            const res = await fetch('<?= BASE_URL ?>itgk/create', {
                method: 'POST',
                body: new FormData(this)
            });
            const json = await res.json();
            if (json.success) {
                showToast('Certificate Packet Created successfully!', 'success')
                location.reload();
            } else {
                alert('âŒ ' + (json.message || 'Error'));
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-2"></i>Save Certificate Packet';
                }
            }
        } catch (err) {
            showToast('Network error: ' + err.message, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Save Certificate Packet';
            }
        } finally {
            if (typeof window.hideLoader === 'function') window.hideLoader();
        }
    });

    // ITGK Code Dropdown Change Auto-fill District
    document.getElementById('add_itgk_code_select')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const district = selectedOption.getAttribute('data-district') || '';
        const districtInput = document.getElementById('add_district_input');
        if (districtInput) {
            districtInput.value = district;
        }
    });

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Edit Certificate -- populate form fields from data-* attributes
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.querySelectorAll('.btn-edit-cert').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val || '';
            };
            const sel = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val || '';
            };

            set('ec_sheet_row', this.getAttribute('data-sheetrow'));
            set('ec_course', this.getAttribute('data-course'));
            set('ec_exam', this.getAttribute('data-exam'));
            set('ec_itgk', this.getAttribute('data-itgk'));
            set('ec_district', this.getAttribute('data-district'));
            set('ec_date', this.getAttribute('data-date'));
            set('ec_examdate', this.getAttribute('data-examdate'));
            set('ec_pass', this.getAttribute('data-pass'));
            set('ec_fail', this.getAttribute('data-fail'));
            set('ec_absent', this.getAttribute('data-absent'));
            set('ec_total', this.getAttribute('data-total'));
            set('ec_packet', this.getAttribute('data-packet'));
            set('ec_certfrom', this.getAttribute('data-certfrom'));
            set('ec_certto', this.getAttribute('data-certto'));
            set('ec_location', this.getAttribute('data-location'));
            set('ec_remark', this.getAttribute('data-remark'));
            set('ec_receiver', this.getAttribute('data-receiver'));
            set('ec_desig', this.getAttribute('data-desig'));
            set('ec_mobile', this.getAttribute('data-mobile'));
            sel('ec_status', this.getAttribute('data-status'));
        });
    });

    // Edit Certificate Form Submit -- saves to Google Sheet via API
    document.getElementById('editCertForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnEditCertSubmit');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving to Google Sheet...';
        }
        if (typeof window.showLoader === 'function') window.showLoader('Saving to Google Sheet...');
        try {
            const res = await fetch('<?= BASE_URL ?>itgk/update', {
                method: 'POST',
                body: new FormData(this)
            });
            const json = await res.json();
            if (json.success) {
                alert('âœ… ' + (json.message || 'Saved to Google Sheet!'));
                location.reload();
            } else {
                alert('âŒ ' + (json.message || 'Save failed'));
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-2"></i>Save to Google Sheet';
                }
            }
        } catch (err) {
            showToast('Network error: ' + err.message, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Save to Google Sheet';
            }
        } finally {
            if (typeof window.hideLoader === 'function') window.hideLoader();
        }
    });


    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Issue Packet -- populate offcanvas
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€


    // Issue Packet Form Submit
        // Quick Issue Button: single row click -> clears selection, selects that row, opens bulk offcanvas
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-quick-issue');
        if (!btn) return;
        var sheetRow = parseInt(btn.dataset.sheetRow, 10);
        if (!sheetRow) { alert('Sheet row not found.'); return; }
        document.dispatchEvent(new CustomEvent('quickIssue', { detail: {
            sheetRow: sheetRow,
            id:       btn.dataset.id       || '',
            itgk:     btn.dataset.itgk     || '',
            district: btn.dataset.district || '',
            course:   btn.dataset.course   || '',
            exam:     btn.dataset.exam     || '',
            packet:   btn.dataset.packet   || '',
            total:    btn.dataset.total    || ''
        }}));
    });


    // =================================================================
    // BULK SELECTION SYSTEM
    // Direct checkbox binding -- called after each pagination render
    // =================================================================
    (function() {

        // â”€â”€ ITGK master lookup map (code â†’ {name, district}) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        var itgkMap = {};
        <?php foreach (($itgkList ?? []) as $itgk): ?>
            itgkMap[<?= json_encode((string)$itgk['code']) ?>] = {
                name: <?= json_encode((string)$itgk['name'])     ?>,
                district: <?= json_encode((string)$itgk['district']) ?>,
                email:    <?= json_encode((string)($itgk['email']  ?? '')) ?>,
                mobile:   <?= json_encode((string)($itgk['mobile'] ?? '')) ?>
            };
        <?php endforeach; ?>

        // â”€â”€ State â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        var selectedCerts = {}; // key = sheetRow (int), value = cert data object

        // â”€â”€ Update floating action bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function updateActionBar() {
            var keys = Object.keys(selectedCerts);
            var count = keys.length;
            var bar = document.getElementById('bulkActionBar');
            if (!bar) return;

            if (count === 0) {
                bar.style.display = 'none';
                return;
            }
            bar.style.display = '';

            var countEl = document.getElementById('bulkSelCount');
            var itgkEl = document.getElementById('bulkSelItgk');
            if (countEl) countEl.textContent = count;
            if (itgkEl) {
                var codes = [...new Set(keys.map(function(k) {
                    return selectedCerts[k].itgk;
                }))];
                itgkEl.textContent = codes.join(', ') || '--';
            }
        }

        // â”€â”€ Bind checkboxes -- call this after every render() â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function bindCheckboxes() {
            document.querySelectorAll('.cert-select-chk').forEach(function(chk) {
                // Restore checked state if this row was previously selected
                var sr = parseInt(chk.dataset.sheetRow, 10);
                if (sr && selectedCerts[sr]) {
                    chk.checked = true;
                }

                // Remove old listener to avoid duplicates (clone trick)
                var fresh = chk.cloneNode(true);
                fresh.checked = chk.checked;
                chk.parentNode.replaceChild(fresh, chk);

                fresh.addEventListener('change', function() {
                    var sheetRow = parseInt(this.dataset.sheetRow, 10);
                    if (!sheetRow) {
                        console.warn('cert-select-chk: data-sheet-row is 0 or missing', this);
                        return;
                    }
                    if (this.checked) {
                        selectedCerts[sheetRow] = {
                            sheetRow: sheetRow,
                            id: this.dataset.id || '',
                            itgk: this.dataset.itgk || '',
                            district: this.dataset.district || '',
                            course: this.dataset.course || '',
                            exam: this.dataset.exam || '',
                            packet: this.dataset.packet || '',
                            total: this.dataset.total || ''
                        };
                        console.log('Selected:', sheetRow, selectedCerts[sheetRow]);
                    } else {
                        delete selectedCerts[sheetRow];
                        console.log('Deselected:', sheetRow);
                    }
                    updateActionBar();
                });
            });

            // Update Select All checkbox state
            syncSelectAllState();
        }

        // â”€â”€ Sync Select-All header checkbox state â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function syncSelectAllState() {
            var selAll = document.getElementById('chkSelectAll');
            if (!selAll) return;
            var visChks = Array.from(document.querySelectorAll('.cert-select-chk')).filter(function(c) {
                var r = c.closest('.cert-main-row');
                return r && r.style.display !== 'none';
            });
            if (visChks.length === 0) {
                selAll.indeterminate = false;
                selAll.checked = false;
                return;
            }
            var allChecked = visChks.every(function(c) {
                return c.checked;
            });
            var someChecked = visChks.some(function(c) {
                return c.checked;
            });
            selAll.checked = allChecked;
            selAll.indeterminate = someChecked && !allChecked;
        }

        // â”€â”€ Select All â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        var selAllChk = document.getElementById('chkSelectAll');
        if (selAllChk) {
            selAllChk.addEventListener('change', function() {
                // Act on VISIBLE rows only
                var visChks = Array.from(document.querySelectorAll('.cert-select-chk')).filter(function(c) {
                    var r = c.closest('.cert-main-row');
                    return r && r.style.display !== 'none';
                });
                visChks.forEach(function(chk) {
                    chk.checked = selAllChk.checked;
                    var sheetRow = parseInt(chk.dataset.sheetRow, 10);
                    if (!sheetRow) return;
                    if (selAllChk.checked) {
                        selectedCerts[sheetRow] = {
                            sheetRow: sheetRow,
                            id: chk.dataset.id || '',
                            itgk: chk.dataset.itgk || '',
                            district: chk.dataset.district || '',
                            course: chk.dataset.course || '',
                            exam: chk.dataset.exam || '',
                            packet: chk.dataset.packet || '',
                            total: chk.dataset.total || ''
                        };
                    } else {
                        delete selectedCerts[sheetRow];
                    }
                });
                updateActionBar();
            });
        }

        // â”€â”€ Clear selection â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        document.getElementById('btnClearSel')?.addEventListener('click', function() {
            selectedCerts = {};
            document.querySelectorAll('.cert-select-chk').forEach(function(c) {
                c.checked = false;
            });
            if (selAllChk) {
                selAllChk.checked = false;
                selAllChk.indeterminate = false;
            }
            updateActionBar();
        });

        // â”€â”€ Populate offcanvas when it opens â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        document.getElementById('bulkIssueOffcanvas')?.addEventListener('show.bs.offcanvas', function() {
            // Reset receiver fields for fresh ITGK prefill on each open
            var _flds = ['bi_receiver_name','bi_receiver_desig','bi_receiver_mob','bi_receiver_email'];
            _flds.forEach(function(id) { var el = document.getElementById(id); if(el) el.value=''; });
            var keys = Object.keys(selectedCerts);
            var certs = keys.map(function(k) {
                return selectedCerts[k];
            });

            // Count
            var countEl = document.getElementById('bi_sel_count');
            if (countEl) countEl.textContent = certs.length;

            // Hidden JSON field
            var jsonEl = document.getElementById('bi_selections_json');
            if (jsonEl) jsonEl.value = JSON.stringify(certs.map(function(c) {
                return {
                    sheet_row: c.sheetRow,
                    itgk_code: c.itgk,
                    course_name: c.course,
                    exam_name: c.exam
                };
            }));

            // ITGK info panel
            var codes = [...new Set(certs.map(function(c) {
                return c.itgk;
            }))];
            var dists = [...new Set(certs.map(function(c) {
                return c.district;
            }))];
            var names = codes.map(function(cd) {
                return itgkMap[cd] ? itgkMap[cd].name : cd;
            });
            var codesEl = document.getElementById('bi_itgk_codes');
            var distEl = document.getElementById('bi_district');
            var nameEl = document.getElementById('bi_itgk_name');
            if (codesEl) codesEl.textContent = codes.join(', ') || '--';
            if (distEl) distEl.textContent = dists.join(', ') || '--';
            if (nameEl) nameEl.textContent = names.join(', ') || '--';
            // Pre-fill receiver details from ITGK master data
            if (codes.length === 1 && itgkMap[codes[0]]) {
                var itgkData = itgkMap[codes[0]];
                var nameEl   = document.getElementById('bi_receiver_name');
                var desigEl  = document.getElementById('bi_receiver_desig');
                var mobEl    = document.getElementById('bi_receiver_mob');
                var emailEl  = document.getElementById('bi_receiver_email');
                if (nameEl  && !nameEl.value)  nameEl.value  = itgkData.name   || '';
                if (desigEl && !desigEl.value) desigEl.value = 'ITGK Head / Coordinator';
                if (mobEl   && !mobEl.value)   mobEl.value   = itgkData.mobile || '';
                if (emailEl && !emailEl.value) emailEl.value = itgkData.email  || '';
            }
            // Validate: all selected certs must share the same ITGK code
            var itgkWarn = document.getElementById('bi_itgk_warn');
            var warnText = document.getElementById('bi_itgk_warn_text');
            var submitBtn = document.getElementById('btnBulkIssueSubmit');
            if (codes.length > 1) {
                if (itgkWarn) itgkWarn.style.display = '';
                if (warnText) warnText.textContent = '[!] Mixed ITGK codes selected -- bulk issue allows only ONE ITGK code per transaction.';
                if (submitBtn) submitBtn.disabled = true;
            } else {
                if (itgkWarn) itgkWarn.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }

            // Selected certs table
            var tbody = document.getElementById('bi_certs_table_body');
            if (tbody) {
                if (certs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">No certificates selected</td></tr>';
                } else {
                    tbody.innerHTML = certs.map(function(c, i) {
                        return '<tr>' +
                            '<td class="fw-bold text-muted">' + (i + 1) + '</td>' +
                            '<td><code class="text-primary">' + (c.itgk || '--') + '</code></td>' +
                            '<td class="small">' + (c.course || '--') + '</td>' +
                            '<td class="small">' + (c.exam || '--') + '</td>' +
                            '<td><code>' + (c.packet || '--') + '</code></td>' +
                            '<td class="text-center fw-bold">' + (c.total || '--') + '</td>' +
                            '</tr>';
                    }).join('');
                }
            }
        });

        // â”€â”€ Bulk Issue Form Submit â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        document.getElementById('bulkIssueForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            var keys = Object.keys(selectedCerts);
            if (keys.length === 0) {
                alert('à¤•à¥‹à¤ˆ Certificate select à¤¨à¤¹à¥€à¤‚ à¤•à¤¿à¤¯à¤¾ à¤¹à¥ˆà¥¤ à¤ªà¤¹à¤²à¥‡ Available rows à¤®à¥‡à¤‚ à¤¸à¥‡ select à¤•à¤°à¥‡à¤‚à¥¤');
                return;
            }
            var btn = document.getElementById('btnBulkIssueSubmit');
            var origLabel = '<i class="fas fa-paper-plane me-1"></i>Issue All Selected Certificates';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Issuing ' + keys.length + ' certificates...';
            }
            if (typeof window.showLoader === 'function') window.showLoader('Issuing ' + keys.length + ' certificate(s) & updating Learner records...');

            // Refresh JSON hidden field
            var jsonEl = document.getElementById('bi_selections_json');
            if (jsonEl) jsonEl.value = JSON.stringify(keys.map(function(k) {
                var c = selectedCerts[k];
                return {
                    sheet_row:   c.sheetRow,
                    itgk_code:   c.itgk,
                    course_name: c.course,
                    exam_name:   c.exam,
                    packet_no:   c.packet  || '',
                    grand_total: c.total   || 0,
                    district:    c.district || ''
                };
            }));

            try {
                var fd = new FormData(this);
                var res = await fetch('<?= BASE_URL ?>itgk/issue_batch', {
                    method: 'POST',
                    body: fd
                });
                var json = await res.json();
                if (json.success) {
                    var oc = bootstrap.Offcanvas.getInstance(document.getElementById('bulkIssueOffcanvas'));
                    if (oc) oc.hide();

                    // Open acknowledgement/print page in new tab
                    var issuedIds = json.issued_ids || [];
                    if (issuedIds.length > 0) {
                        var ackUrl = '<?= BASE_URL ?>itgk/acknowledgement?ids=' + issuedIds.join(',');
                        window.open(ackUrl, '_blank');
                    }

                    alert('Bulk Issue Complete!\nCertificates Updated: ' + (json.certs_updated || 0) +
                        '\nLearner Records Updated: ' + (json.learners_updated || 0) +
                        '\n\nAcknowledgement slip opened in new tab.');
                    location.reload();
                } else {
                    alert('Issue Failed: ' + (json.message || 'Bulk issue failed'));
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = origLabel;
                    }
                }
            } catch (err) {
                showToast('Network error: ' + err.message, 'danger');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origLabel;
                }
            } finally {
                if (typeof window.hideLoader === 'function') window.hideLoader();
            }
        });

        // â”€â”€ Expose bindCheckboxes so pagination render() can call it â”€â”€â”€â”€â”€â”€

        // quickIssue: single-row Issue button -> clear, select that row, open bulk offcanvas
        document.addEventListener('quickIssue', function(e) {
            var d = e.detail;
            // Clear all selections
            selectedCerts = {};
            // Reset receiver fields so ITGK prefill fires fresh
            var flds = ['bi_receiver_name','bi_receiver_desig','bi_receiver_mob','bi_receiver_email'];
            flds.forEach(function(id) { var el = document.getElementById(id); if(el) el.value=''; });
            document.querySelectorAll('.cert-select-chk').forEach(function(c) { c.checked = false; });
            var sa = document.getElementById('chkSelectAll');
            if (sa) { sa.checked = false; sa.indeterminate = false; }
            // Add this single row
            selectedCerts[d.sheetRow] = d;
            var chk = document.querySelector('.cert-select-chk[data-sheet-row="' + d.sheetRow + '"]');
            if (chk) chk.checked = true;
            updateActionBar();
            // Open bulk issue offcanvas
            var ocEl = document.getElementById('bulkIssueOffcanvas');
            if (ocEl) { var oc = new bootstrap.Offcanvas(ocEl); oc.show(); }
        });
        window._bindCertCheckboxes = bindCheckboxes;

        // Initial bind
        bindCheckboxes();

    })(); // end Bulk Selection IIFE
</script>