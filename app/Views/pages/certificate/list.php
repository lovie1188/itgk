<?php
$total = (int) ($total ?? count($certificates ?? []));
$isSuperAdmin = \App\Services\AuthService::isSuperAdmin();
$canIssue = \App\Services\AuthService::isAdmin();
$data = get_defined_vars();
?>

<!-- ─── PAGE HEADER ─────────────────────────────────────────── -->
<div id="pageHeaderSection" class="d-flex align-items-center justify-content-between mb-1 gap-1 flex-wrap">
    <div>
        <h5 class="fw-bold mb-0 text-dark" style="font-size:16px;">
            <i class="fas fa-certificate text-primary me-1"></i>ITGK Certificates
        </h5>
        <p class="text-muted mb-0" style="font-size:10.5px;">
            Showing <strong><?= number_format(count($certificates ?? [])) ?></strong> of
            <strong><?= number_format($total) ?></strong> packets
            &mdash; <span class="text-primary"><?= htmlspecialchars($sheetTab ?? 'Certificate') ?></span>
        </p>
    </div>
    <div id="headerActionsBlock" class="d-flex gap-1 flex-wrap align-items-center">
        <?php if ($isSuperAdmin): ?>
            <button type="button" id="btnConsolidate" class="btn btn-warning btn-sm fw-bold px-2 py-1"
                style="font-size:11px;">
                <i class="fas fa-cogs me-1"></i><span class="d-none d-sm-inline">Consolidate</span>
            </button>
            <button type="button" id="btnAddPacketModal" class="btn btn-success btn-sm fw-bold px-2 py-1"
                style="font-size:11px;" data-bs-toggle="offcanvas" data-bs-target="#addItgkOffcanvas">
                <i class="fas fa-plus me-1"></i><span class="d-none d-sm-inline">Add Packet</span>
            </button>
        <?php endif; ?>
        <a id="btnRefreshList" href="<?= BASE_URL ?>itgk/list" class="btn btn-outline-primary btn-sm px-2 py-1"
            style="font-size:11px;">
            <i class="fas fa-sync-alt"></i>
        </a>
    </div>
</div>

<?php if (!empty($sheetError)): ?>
    <div id="sheetErrorAlertBlock" class="alert alert-danger py-1 px-2 small mb-1">
        <i class="fas fa-exclamation-triangle me-1"></i><?= htmlspecialchars($sheetError) ?>
    </div>
<?php endif; ?>

<!-- ─── ANALYTICS STRIP ───────────────────────────────────────── -->
<?php if (!empty($analytics)): ?>
    <div id="analyticsStripSection" class="mb-2" style="overflow-x:auto;">
        <div id="analyticsCardsRow" class="d-flex gap-1" style="min-width:100%;">
            <?php
            $cards = [
                ['label' => 'Total', 'value' => $analytics['total'] ?? $total, 'icon' => 'fa-certificate', 'color' => '#4f46e5', 'bg' => '#eef2ff'],
                ['label' => 'Available', 'value' => $analytics['available'] ?? 0, 'icon' => 'fa-check-circle', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
                ['label' => 'Issued', 'value' => $analytics['issued'] ?? 0, 'icon' => 'fa-hand-holding', 'color' => '#0891b2', 'bg' => '#ecfeff'],
                ['label' => 'In Transit', 'value' => $analytics['intransit'] ?? 0, 'icon' => 'fa-truck', 'color' => '#d97706', 'bg' => '#fffbeb'],
                ['label' => 'Not Rcvd', 'value' => $analytics['not_received'] ?? 0, 'icon' => 'fa-times-circle', 'color' => '#64748b', 'bg' => '#f8fafc'],
            ];
            foreach ($cards as $c): ?>
                <div class="flex-shrink-0 rounded-3 text-center p-2"
                    style="background:<?= $c['bg'] ?>;min-width:80px;border:1px solid <?= $c['color'] ?>22;">
                    <i class="fas <?= $c['icon'] ?> mb-1" style="color:<?= $c['color'] ?>;font-size:16px;"></i>
                    <div class="fw-bold lh-1 mb-0" style="font-size:18px;color:<?= $c['color'] ?>;">
                        <?= number_format((int) $c['value']) ?>
                    </div>
                    <div style="font-size:9px;color:#666;margin-top:2px;"><?= $c['label'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ─── QUERY FORM (ITGK & STATUS FILTER) ────────────────────────── -->
<div id="queryFilterCardSection" class="card border-0 shadow-sm mb-2 rounded-3"
    style="background:#f8fafc;border:1px solid #e2e8f0;">
    <div class="card-body p-2">
        <form id="itgkQueryForm" onsubmit="return false;">
            <div class="row g-2 align-items-center">
                <!-- ITGK Code Select -->
                <div id="itgkSelectBlock" class="col-md-5 col-sm-6">
                    <label class="form-label mb-1 fw-bold text-secondary" style="font-size:11px;">
                        <i class="fas fa-building me-1 text-primary"></i>Select ITGK Center <span
                            class="text-danger">*</span>
                    </label>
                    <select id="filterItgkCode" class="form-select form-select-sm shadow-none select2-itgk-dropdown"
                        data-placeholder="-- Select ITGK Center to View Records --"
                        style="font-size:12px;border-color:#cbd5e1;">
                        <option value=""></option>
                        <option value="ALL">-- ALL ITGK CENTERS (Show All Records) --</option>
                        <?php foreach ($itgkList ?? [] as $itgk): ?>
                            <option value="<?= htmlspecialchars((string) $itgk['code']) ?>"
                                data-name="<?= htmlspecialchars((string) ($itgk['name'] ?? '')) ?>"
                                data-district="<?= htmlspecialchars((string) ($itgk['district'] ?? '')) ?>"
                                data-email="<?= htmlspecialchars((string) ($itgk['email'] ?? '')) ?>"
                                data-mobile="<?= htmlspecialchars((string) ($itgk['mobile'] ?? '')) ?>">
                                ITGK <?= htmlspecialchars((string) $itgk['code']) ?> -
                                <?= htmlspecialchars((string) ($itgk['name'] ?? '')) ?>
                                <?= !empty($itgk['district']) ? '(' . htmlspecialchars((string) $itgk['district']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter (Multi-select pill buttons) -->
                <div id="statusFilterBlock" class="col-md-5 col-sm-6">
                    <label class="form-label mb-1 fw-bold text-secondary" style="font-size:11px;">
                        <i class="fas fa-tasks me-1 text-primary"></i>Filter Status (Multi-Select)
                    </label>
                    <div class="d-flex gap-1 flex-wrap align-items-center" id="statusFilterGroup">
                        <button type="button" class="btn btn-sm btn-dark py-0 px-2 status-pill active" data-status="ALL"
                            style="font-size:11px;border-radius:12px;">
                            ALL
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 status-pill"
                            data-status="Available" style="font-size:11px;border-radius:12px;">
                            <i class="fas fa-check-circle me-1"></i>Available
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2 status-pill"
                            data-status="Pending" style="font-size:11px;border-radius:12px;">
                            <i class="fas fa-clock me-1"></i>Pending
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info py-0 px-2 status-pill"
                            data-status="Issued" style="font-size:11px;border-radius:12px;">
                            <i class="fas fa-hand-holding me-1"></i>Issued
                        </button>
                    </div>
                </div>

                <!-- Reset / Clear Button -->
                <div id="queryResetBlock" class="col-md-2 col-12 text-end">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm w-100 py-1"
                        style="font-size:11px;">
                        <i class="fas fa-undo me-1"></i>Reset Query
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ─── SELECTED ITGK DETAILS CONTAINER ────────────────────────── -->
<div id="itgkDetailsContainer" class="card border-0 shadow-sm mb-2 rounded-3 d-none"
    style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-left:4px solid #2563eb !important;">
    <div class="card-body p-2.5">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="badge bg-primary px-2 py-1" style="font-size:11px;" id="itgkDetailCodeBadge">
                ITGK CODE: -
            </span>
            <span class="text-primary fw-semibold" style="font-size:11px;">
                <i class="fas fa-info-circle me-1"></i>ITGK Master Details
            </span>
        </div>
        <div class="row g-2" style="font-size:12px;">
            <div class="col-md-4 col-sm-6">
                <small class="text-muted d-block" style="font-size:10px;">CENTER NAME</small>
                <strong id="itgkDetailName" class="text-dark">-</strong>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block" style="font-size:10px;">DISTRICT</small>
                <span id="itgkDetailDistrict" class="fw-semibold text-secondary">-</span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted d-block" style="font-size:10px;">EMAIL</small>
                <span id="itgkDetailEmail" class="text-dark">-</span>
            </div>
            <div class="col-md-2 col-sm-6">
                <small class="text-muted d-block" style="font-size:10px;">MOBILE</small>
                <span id="itgkDetailMobile" class="text-dark">-</span>
            </div>
        </div>
    </div>
</div>

<!-- ─── SEARCH + CONTROLS BAR ─────────────────────────────────── -->
<div id="searchControlBarSection" class="card border-0 shadow-sm mb-2 rounded-3"
    style="background:linear-gradient(135deg,#1a56db,#1e429f);">
    <div class="card-body py-2 px-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="flex-grow-1 position-relative">
                <i class="fas fa-search position-absolute text-white-50"
                    style="left:10px;top:50%;transform:translateY(-50%);font-size:11px;pointer-events:none;"></i>
                <input type="search" id="certSearch" class="form-control form-control-sm border-0 ps-4"
                    placeholder="Search ITGK, Course, Exam..."
                    style="background:rgba(255, 255, 255, 0.43);color:#fff;font-size:12px;border-radius:20px;"
                    autocomplete="off">
            </div>
            <select id="certPerPage" class="form-select form-select-sm border-0 flex-shrink-0"
                style="width:60px;background:rgba(255,255,255,.15);color:#fff;font-size:12px;border-radius:20px;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="badge bg-white text-primary fw-semibold flex-shrink-0" style="font-size:10px;">
                <span id="certVisibleCount"><?= number_format($total) ?></span> records
            </span>
        </div>
    </div>
</div>

<!-- ─── CONTENT ───────────────────────────────────────────────── -->
<?php if (!empty($certificates)): ?>

    <!-- DESKTOP TABLE (md+) -->
    <div id="desktopCertTableSection" class="d-none d-md-block">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table id="desktopCertTable" class="table table-sm table-hover mb-0 align-middle" style="font-size:12px;">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th class="text-center" style="width:36px;">
                                <input type="checkbox" id="chkSelectAll" class="form-check-input" title="Select all">
                            </th>
                            <th class="text-center" style="width:36px;">#</th>
                            <th style="width:80px;">ITGK Code</th>
                            <th>Course &amp; Exam</th>
                            <th style="width:85px;">District</th>
                            <th class="text-center" style="width:55px;">Pass</th>
                            <th class="text-center" style="width:75px;">Packet</th>
                            <th style="width:120px;">Cert Range</th>
                            <th class="text-center" style="width:115px;">Status &amp; Location</th>
                            <th style="width:105px;">Receiver</th>
                            <th class="text-center" style="width:115px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="certTableBody">
                        <?php foreach ($certificates as $index => $cert):
                            $rowNum = $index + 1;
                            $status = trim((string) ($cert['status'] ?? ''));
                            $statusBadge = match (strtolower($status)) {
                                'available' => 'bg-success',
                                'issued' => 'bg-info text-dark',
                                'not received' => 'bg-secondary',
                                'intransit', 'in transit' => 'bg-warning text-dark',
                                default => 'bg-light text-dark border',
                            };
                            $sheetRow = (int) ($cert['sheet_row'] ?? 0);
                            $itgkCode = (string) ($cert['itgk_code'] ?? '');
                            $district = (string) ($cert['district'] ?? '');
                            $courseName = (string) ($cert['course_name'] ?? '');
                            $examName = (string) ($cert['exam_name'] ?? '');
                            $packetNo = (string) ($cert['packet_no'] ?? '');
                            $totalCount = (int) ($cert['grand_total'] ?? $cert['pass'] ?? 0);
                            $isIssued = strtolower($status) === 'issued';
                            ?>
                            <tr class="cert-main-row" data-row="<?= $rowNum ?>" data-sheet-row="<?= $sheetRow ?>"
                                data-itgk="<?= htmlspecialchars($itgkCode) ?>" data-status="<?= htmlspecialchars($status) ?>">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input cert-select-chk"
                                        data-sheet-row="<?= $sheetRow ?>" data-id="<?= htmlspecialchars((string) $cert['id']) ?>"
                                        data-itgk="<?= htmlspecialchars($itgkCode) ?>"
                                        data-district="<?= htmlspecialchars($district) ?>"
                                        data-course="<?= htmlspecialchars($courseName) ?>"
                                        data-exam="<?= htmlspecialchars($examName) ?>"
                                        data-packet="<?= htmlspecialchars($packetNo) ?>"
                                        data-certfrom="<?= htmlspecialchars((string) ($cert['cert_no_from'] ?? '')) ?>"
                                        data-certto="<?= htmlspecialchars((string) ($cert['cert_no_to'] ?? '')) ?>"
                                        data-total="<?= $totalCount ?>" data-status="<?= htmlspecialchars($status) ?>"
                                        <?= $isIssued ? 'disabled title="Already Issued"' : '' ?>>
                                </td>
                                <td class="text-center fw-bold text-muted"><?= $rowNum ?></td>
                                <td><code class="text-primary fw-bold"><?= htmlspecialchars($itgkCode) ?></code></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($courseName) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($examName) ?></small>
                                </td>
                                <td><span
                                        class="badge bg-light text-dark border"><?= htmlspecialchars($district ?: '-') ?></span>
                                </td>
                                <td class="text-center fw-bold text-success"><?= (int) ($cert['pass'] ?? 0) ?></td>
                                <td class="text-center"><code><?= htmlspecialchars($packetNo ?: '-') ?></code></td>
                                <td>
                                    <small class="text-truncate d-block" style="max-width:120px;"
                                        title="<?= htmlspecialchars(($cert['cert_no_from'] ?? '') . ' - ' . ($cert['cert_no_to'] ?? '')) ?>">
                                        <?= htmlspecialchars(($cert['cert_no_from'] ?? '-') . ' to ' . ($cert['cert_no_to'] ?? '-')) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge <?= $statusBadge ?> mb-1"><?= htmlspecialchars($status ?: 'Unknown') ?></span>
                                    <?php if (!empty($cert['current_location'])): ?>
                                        <div class="text-muted" style="font-size:10px;">
                                            <i
                                                class="fas fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($cert['current_location']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cert['receiver_name'])): ?>
                                        <div class="fw-semibold small text-truncate" style="max-width:105px;"
                                            title="<?= htmlspecialchars($cert['receiver_name']) ?>">
                                            <i
                                                class="fas fa-user-check text-success me-1"></i><?= htmlspecialchars($cert['receiver_name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($isIssued): ?>
                                            <a href="<?= BASE_URL ?>itgk/acknowledgement?id=<?= urlencode((string) $cert['id']) ?>"
                                                target="_blank" class="btn btn-outline-success btn-sm py-0 px-1"
                                                title="View / Print Receipt">
                                                <i class="fas fa-print me-1"></i>Receipt
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($isSuperAdmin): ?>
                                            <button type="button" class="btn btn-outline-warning btn-edit-cert btn-sm py-0 px-1"
                                                title="Edit Record" data-bs-toggle="offcanvas" data-bs-target="#editCertOffcanvas"
                                                data-sheetrow="<?= $sheetRow ?>" data-course="<?= htmlspecialchars($courseName) ?>"
                                                data-exam="<?= htmlspecialchars($examName) ?>"
                                                data-itgk="<?= htmlspecialchars($itgkCode) ?>"
                                                data-district="<?= htmlspecialchars($district) ?>"
                                                data-date="<?= htmlspecialchars((string) ($cert['receiving_date'] ?? '')) ?>"
                                                data-examdate="<?= htmlspecialchars((string) ($cert['exam_date'] ?? '')) ?>"
                                                data-pass="<?= (int) ($cert['pass'] ?? 0) ?>"
                                                data-fail="<?= (int) ($cert['fail'] ?? 0) ?>"
                                                data-absent="<?= (int) ($cert['absent'] ?? 0) ?>"
                                                data-ufm="<?= (int) ($cert['ufm'] ?? 0) ?>" data-total="<?= $totalCount ?>"
                                                data-packet="<?= htmlspecialchars($packetNo) ?>"
                                                data-certfrom="<?= htmlspecialchars((string) ($cert['cert_no_from'] ?? '')) ?>"
                                                data-certto="<?= htmlspecialchars((string) ($cert['cert_no_to'] ?? '')) ?>"
                                                data-location="<?= htmlspecialchars((string) ($cert['current_location'] ?? '')) ?>"
                                                data-remark="<?= htmlspecialchars((string) ($cert['remark'] ?? '')) ?>"
                                                data-receiver="<?= htmlspecialchars((string) ($cert['receiver_name'] ?? '')) ?>"
                                                data-desig="<?= htmlspecialchars((string) ($cert['receiver_designation'] ?? '')) ?>"
                                                data-mobile="<?= htmlspecialchars((string) ($cert['receiver_mobile'] ?? '')) ?>"
                                                data-issuedby="<?= htmlspecialchars((string) ($cert['issued_by'] ?? '')) ?>"
                                                data-image="<?= htmlspecialchars((string) ($cert['image'] ?? '')) ?>"
                                                data-status="<?= htmlspecialchars($status) ?>">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ─── MOBILE CARD VIEW (< md) ─────────────────────────────── -->
    <div id="mobileCertCardsSection" class="d-block d-md-none">
        <?php foreach ($certificates as $index => $cert):
            $rowNum = $index + 1;
            $status = trim((string) ($cert['status'] ?? ''));
            $sheetRow = (int) ($cert['sheet_row'] ?? 0);
            $itgkCode = (string) ($cert['itgk_code'] ?? '');
            $district = (string) ($cert['district'] ?? '');
            $courseName = (string) ($cert['course_name'] ?? '');
            $examName = (string) ($cert['exam_name'] ?? '');
            $packetNo = (string) ($cert['packet_no'] ?? '');
            $totalCount = (int) ($cert['grand_total'] ?? $cert['pass'] ?? 0);
            $passCount = (int) ($cert['pass'] ?? 0);
            $failCount = (int) ($cert['fail'] ?? 0);
            $absentCount = (int) ($cert['absent'] ?? 0);
            $isIssued = strtolower($status) === 'issued';
            $statusColor = match (strtolower($status)) {
                'available' => ['bg' => '#16a34a', 'text' => '#fff', 'light' => '#f0fdf4'],
                'issued' => ['bg' => '#0891b2', 'text' => '#fff', 'light' => '#ecfeff'],
                'not received' => ['bg' => '#64748b', 'text' => '#fff', 'light' => '#f8fafc'],
                'intransit', 'in transit' => ['bg' => '#d97706', 'text' => '#fff', 'light' => '#fffbeb'],
                default => ['bg' => '#94a3b8', 'text' => '#fff', 'light' => '#f1f5f9'],
            };
            ?>
            <!-- MOBILE CERT CARD -->
            <div class="cert-mobile-card mb-2" data-row="<?= $rowNum ?>" data-sheet-row="<?= $sheetRow ?>" data-itgk="<?= htmlspecialchars($itgkCode) ?>" data-status="<?= htmlspecialchars($status) ?>">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">

                    <!-- Card Header Strip -->
                    <div class="d-flex align-items-stretch">
                        <!-- Color Accent Left Bar -->
                        <div style="width:5px;background:<?= $statusColor['bg'] ?>;flex-shrink:0;"></div>

                        <!-- Main Card Content -->
                        <div class="flex-grow-1 p-2" style="min-width:0;">

                            <!-- Row 1: Checkbox + ITGK Code + Status badge -->
                            <div class="d-flex align-items-center justify-content-between mb-1 gap-1">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <input type="checkbox" class="form-check-input cert-select-chk flex-shrink-0"
                                        style="width:16px;height:16px;" data-sheet-row="<?= $sheetRow ?>"
                                        data-id="<?= htmlspecialchars((string) $cert['id']) ?>"
                                        data-itgk="<?= htmlspecialchars($itgkCode) ?>"
                                        data-district="<?= htmlspecialchars($district) ?>"
                                        data-course="<?= htmlspecialchars($courseName) ?>"
                                        data-exam="<?= htmlspecialchars($examName) ?>"
                                        data-packet="<?= htmlspecialchars($packetNo) ?>"
                                        data-certfrom="<?= htmlspecialchars((string) ($cert['cert_no_from'] ?? '')) ?>"
                                        data-certto="<?= htmlspecialchars((string) ($cert['cert_no_to'] ?? '')) ?>"
                                        data-total="<?= $totalCount ?>" data-status="<?= htmlspecialchars($status) ?>"
                                        <?= $isIssued ? 'disabled title="Already Issued"' : '' ?>>
                                    <div class="min-w-0">
                                        <span class="fw-bold text-primary" style="font-size:12px;">
                                            <?= htmlspecialchars($itgkCode ?: 'N/A') ?>
                                        </span>
                                        <?php if ($district): ?>
                                            <span class="badge bg-light text-dark border ms-1"
                                                style="font-size:9px;"><?= htmlspecialchars($district) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge flex-shrink-0"
                                    style="background:<?= $statusColor['bg'] ?>;color:<?= $statusColor['text'] ?>;font-size:10px;padding:3px 8px;border-radius:20px;">
                                    <?= htmlspecialchars($status ?: 'Unknown') ?>
                                </span>
                            </div>

                            <!-- Row 2: Course Name + Exam -->
                            <div class="mb-1" style="min-width:0;">
                                <div class="fw-bold text-dark text-truncate" style="font-size:13px;line-height:1.2;">
                                    <?= htmlspecialchars($courseName ?: '—') ?>
                                </div>
                                <div class="text-muted text-truncate" style="font-size:10.5px;">
                                    <i
                                        class="fas fa-graduation-cap me-1 text-secondary"></i><?= htmlspecialchars($examName ?: '—') ?>
                                </div>
                            </div>

                            <!-- Row 3: Stats strip -->
                            <div class="d-flex gap-1 mb-2 rounded-2 p-1"
                                style="background:<?= $statusColor['light'] ?>;font-size:10px;">
                                <div class="text-center flex-fill">
                                    <div class="text-muted" style="font-size:8.5px;letter-spacing:.3px;">PASS</div>
                                    <div class="fw-bold text-success" style="font-size:14px;line-height:1;"><?= $passCount ?>
                                    </div>
                                </div>
                                <div style="border-left:1px solid rgba(0,0,0,.08);"></div>
                                <div class="text-center flex-fill">
                                    <div class="text-muted" style="font-size:8.5px;letter-spacing:.3px;">FAIL</div>
                                    <div class="fw-bold text-danger" style="font-size:14px;line-height:1;"><?= $failCount ?>
                                    </div>
                                </div>
                                <div style="border-left:1px solid rgba(0,0,0,.08);"></div>
                                <div class="text-center flex-fill">
                                    <div class="text-muted" style="font-size:8.5px;letter-spacing:.3px;">PACKET</div>
                                    <div class="fw-bold text-dark" style="font-size:11px;line-height:1.2;">
                                        <?= htmlspecialchars($packetNo ?: '—') ?></div>
                                </div>
                                <div style="border-left:1px solid rgba(0,0,0,.08);"></div>
                                <div class="text-center flex-fill">
                                    <div class="text-muted" style="font-size:8.5px;letter-spacing:.3px;">LOCATION</div>
                                    <div class="fw-bold text-dark text-truncate"
                                        style="font-size:10px;line-height:1.2;max-width:70px;">
                                        <?= htmlspecialchars(($cert['current_location'] ?? '') ?: '—') ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 4: Accordion Details + Actions -->
                            <div class="d-flex align-items-center justify-content-between gap-1">
                                <!-- Expand Details -->
                                <button class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                                    style="font-size:10.5px;" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#mCard_<?= $rowNum ?>">
                                    <i class="fas fa-chevron-down me-1" style="font-size:9px;"></i>Details
                                </button>

                                <!-- Action Buttons -->
                                <div class="d-flex gap-1">
                                    <?php if ($isIssued): ?>
                                        <a href="<?= BASE_URL ?>itgk/acknowledgement?id=<?= urlencode((string) $cert['id']) ?>"
                                            target="_blank" class="btn btn-sm fw-semibold"
                                            style="font-size:10px;padding:3px 9px;background:#dcfce7;color:#16a34a;border:1px solid #16a34a33;border-radius:20px;">
                                            <i class="fas fa-print me-1"></i>Receipt
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($isSuperAdmin): ?>
                                        <button type="button" class="btn btn-sm btn-edit-cert fw-semibold"
                                            style="font-size:10px;padding:3px 9px;background:#fffbeb;color:#d97706;border:1px solid #d9770633;border-radius:20px;"
                                            data-bs-toggle="offcanvas" data-bs-target="#editCertOffcanvas"
                                            data-sheetrow="<?= $sheetRow ?>" data-course="<?= htmlspecialchars($courseName) ?>"
                                            data-exam="<?= htmlspecialchars($examName) ?>"
                                            data-itgk="<?= htmlspecialchars($itgkCode) ?>"
                                            data-district="<?= htmlspecialchars($district) ?>"
                                            data-date="<?= htmlspecialchars((string) ($cert['receiving_date'] ?? '')) ?>"
                                            data-examdate="<?= htmlspecialchars((string) ($cert['exam_date'] ?? '')) ?>"
                                            data-pass="<?= (int) ($cert['pass'] ?? 0) ?>"
                                            data-fail="<?= (int) ($cert['fail'] ?? 0) ?>"
                                            data-absent="<?= (int) ($cert['absent'] ?? 0) ?>"
                                            data-ufm="<?= (int) ($cert['ufm'] ?? 0) ?>" data-total="<?= $totalCount ?>"
                                            data-packet="<?= htmlspecialchars($packetNo) ?>"
                                            data-certfrom="<?= htmlspecialchars((string) ($cert['cert_no_from'] ?? '')) ?>"
                                            data-certto="<?= htmlspecialchars((string) ($cert['cert_no_to'] ?? '')) ?>"
                                            data-location="<?= htmlspecialchars((string) ($cert['current_location'] ?? '')) ?>"
                                            data-remark="<?= htmlspecialchars((string) ($cert['remark'] ?? '')) ?>"
                                            data-receiver="<?= htmlspecialchars((string) ($cert['receiver_name'] ?? '')) ?>"
                                            data-desig="<?= htmlspecialchars((string) ($cert['receiver_designation'] ?? '')) ?>"
                                            data-mobile="<?= htmlspecialchars((string) ($cert['receiver_mobile'] ?? '')) ?>"
                                            data-issuedby="<?= htmlspecialchars((string) ($cert['issued_by'] ?? '')) ?>"
                                            data-image="<?= htmlspecialchars((string) ($cert['image'] ?? '')) ?>"
                                            data-status="<?= htmlspecialchars($status) ?>">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Collapsed Detail Rows -->
                            <div class="collapse" id="mCard_<?= $rowNum ?>">
                                <div class="mt-1 pt-1 border-top">
                                    <div class="row g-0" style="font-size:10.5px;">
                                        <div class="col-5 text-muted">Cert Range:</div>
                                        <div class="col-7 fw-semibold text-end">
                                            <?= htmlspecialchars(($cert['cert_no_from'] ?? '—') . ' → ' . ($cert['cert_no_to'] ?? '—')) ?>
                                        </div>
                                        <div class="col-5 text-muted">Receiver:</div>
                                        <div class="col-7 fw-semibold text-end">
                                            <?= htmlspecialchars($cert['receiver_name'] ?: '—') ?>
                                        </div>
                                        <div class="col-5 text-muted">Mobile:</div>
                                        <div class="col-7 fw-semibold text-end">
                                            <?= htmlspecialchars($cert['receiver_mobile'] ?: '—') ?>
                                        </div>
                                        <div class="col-5 text-muted">Rcv. Date:</div>
                                        <div class="col-7 fw-semibold text-end">
                                            <?= htmlspecialchars((string) ($cert['receiving_date'] ?? '—')) ?>
                                        </div>
                                        <div class="col-5 text-muted">Remark:</div>
                                        <div class="col-7 fw-semibold text-end text-truncate">
                                            <?= htmlspecialchars((string) ($cert['remark'] ?? '—')) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /main card content -->
                    </div><!-- /d-flex accent row -->

                </div><!-- /card -->
            </div><!-- /cert-mobile-card -->
        <?php endforeach; ?>
    </div><!-- /certCardContainer -->

<?php else: ?>
    <div id="emptyCertStateBlock" class="text-center py-5">
        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No certificates found</h5>
        <p class="small text-muted">Click <strong>Refresh Sheet</strong> to reload data from Google Sheet.</p>
    </div>
<?php endif; ?>

<!-- ─── PAGINATION ─────────────────────────────────────────────── -->
<div id="paginationFooterSection"
    class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 mb-4 px-1">
    <small class="text-muted fw-semibold" id="certShowingText">Loading...</small>
    <nav aria-label="Certificate pagination" id="certPagination" style="min-height:32px;"></nav>
</div>

<!-- ─── FLOATING BULK ACTION BAR ─────────────────────────────── -->
<div id="bulkActionBar" style="display:none;
           position:fixed;
           bottom:72px;
           left:50%;
           transform:translateX(-50%);
           z-index:1045;
           width:calc(100% - 24px);
           max-width:680px;
           background:linear-gradient(135deg,#1a56db,#0e9f6e);
           color:#fff;
           border-radius:14px;
           padding:10px 14px;
           box-shadow:0 8px 32px rgba(0,0,0,.35);">
    <div class="d-flex align-items-center justify-content-between gap-2">
        <div>
            <div class="fw-bold" style="font-size:13px;">
                <i class="fas fa-check-square me-1"></i>
                <span id="bulkSelCount">0</span> Selected
            </div>
            <div style="font-size:10px;opacity:.8;">
                ITGK: <span id="bulkSelItgk" class="fw-semibold">--</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm fw-bold"
                style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);border-radius:20px;padding:5px 14px;font-size:11px;"
                id="btnClearSel">
                <i class="fas fa-times me-1"></i>Clear
            </button>
            <button type="button" class="btn btn-warning btn-sm fw-bold"
                style="border-radius:20px;padding:5px 14px;font-size:11px;" id="btnOpenBulkIssue"
                data-bs-toggle="offcanvas" data-bs-target="#bulkIssueOffcanvas">
                <i class="fas fa-paper-plane me-1"></i>Issue Selected
            </button>
        </div>
    </div>
</div>

<!-- ─── MODAL PARTIALS ────────────────────────────────────────── -->
<?php \App\Helpers\View::partial('partials.certificate.bulk_modal', $data); ?>
<?php \App\Helpers\View::partial('partials.certificate.add_modal', $data); ?>
<?php \App\Helpers\View::partial('partials.certificate.edit_modal', $data); ?>

<!-- ─── SCRIPTS ──────────────────────────────────────────────── -->
<script src="<?= BASE_URL ?>assets/js/certificate-list.js"></script>