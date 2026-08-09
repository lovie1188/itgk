<?php
/**
 * ITGK Wise Admissions for Each Month View
 *
 * Displays admissions data from the ADMISSIONS Google Sheet tab,
 * grouped by batch/month with ITGK-wise breakdown.
 * Includes loading state overlay and client-side instant filtering (no page reload).
 */
?>
<style>
    /* Loading Overlay */
    #admissionsLoadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.92);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity 0.3s ease;
    }

    #admissionsLoadingOverlay .spinner {
        width: 48px;
        height: 48px;
        border: 4px solid #e2e8f0;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: admissionsSpin 0.8s linear infinite;
    }

    @keyframes admissionsSpin {
        to {
            transform: rotate(360deg);
        }
    }

    #admissionsLoadingOverlay .loading-text {
        margin-top: 16px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
    }

    #admissionsLoadingOverlay.hidden {
        opacity: 0;
        pointer-events: none;
    }

    /* Card Table Row Styling */
    #admissionsCardTable {
        border-collapse: separate !important;
        border-spacing: 0 5px !important;
    }

    #admissionsCardTable tbody tr {
        background-color: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border-radius: 6px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    #admissionsCardTable tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    #admissionsCardTable tbody td {
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
        padding: 10px 12px;
    }

    #admissionsCardTable tbody td:first-child {
        border-left: 1px solid #f1f5f9;
        border-top-left-radius: 6px;
        border-bottom-left-radius: 6px;
    }

    #admissionsCardTable tbody td:last-child {
        border-right: 1px solid #f1f5f9;
        border-top-right-radius: 6px;
        border-bottom-right-radius: 6px;
    }

    .batch-group-header {
        background-color: #f1f5f9 !important;
        font-weight: 700;
        color: #1e3a8a;
    }

    .batch-group-header td {
        border-top: 2px solid #cbd5e1;
        border-bottom: 2px solid #cbd5e1;
    }

    .status-badge-issued {
        background-color: #dcfce7;
        color: #166534;
    }

    .status-badge-pending {
        background-color: #fef9c3;
        color: #854d0e;
    }
</style>

<!-- Loading Overlay -->
<div id="admissionsLoadingOverlay">
    <div class="spinner"></div>
    <div class="loading-text">Loading Admissions Data...</div>
</div>

<div class="container-fluid py-3" id="admissionsContent" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-primary">
                <i class="fas fa-user-plus me-2"></i>ITGK Admissions
            </h4>
            <p class="text-muted small mb-0">ITGK Wise Admissions for Each Month</p>
        </div>
    </div>

    <!-- ─── ANALYTICS STRIP ───────────────────────────────── -->
    <?php if (!empty($analytics)): ?>
        <div id="analyticsStripSection" class="mb-2" style="overflow-x:auto;">
            <div id="analyticsCardsRow" class="d-flex gap-1" style="min-width:100%;">
                <?php
                $cards = [
                    ['id' => 'statTotalVal', 'label' => 'Total Records', 'value' => $analytics['total'] ?? 0, 'icon' => 'fa-file-alt', 'color' => '#4f46e5', 'bg' => '#eef2ff'],
                    ['id' => 'statTotalConfirmVal', 'label' => 'Total Confirm', 'value' => $analytics['total_confirm'] ?? 0, 'icon' => 'fa-check-circle', 'color' => '#059669', 'bg' => '#ecfdf5'],
                    ['id' => 'statBatchesVal', 'label' => 'Batches', 'value' => $analytics['batches'] ?? 0, 'icon' => 'fa-calendar', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
                    ['id' => 'statCentersVal', 'label' => 'ITGK Centers', 'value' => $analytics['centers'] ?? 0, 'icon' => 'fa-building', 'color' => '#0891b2', 'bg' => '#ecfeff'],
                    ['id' => 'statFilteredVal', 'label' => 'Filtered', 'value' => $analytics['filtered'] ?? 0, 'icon' => 'fa-filter', 'color' => '#d97706', 'bg' => '#fffbeb'],
                ];
                foreach ($cards as $c): ?>
                    <div class="flex-shrink-0 rounded-3 text-center p-2"
                        style="background:<?= $c['bg'] ?>;min-width:80px;border:1px solid <?= $c['color'] ?>22;">
                        <i class="fas <?= $c['icon'] ?> mb-1" style="color:<?= $c['color'] ?>;font-size:16px;"></i>
                        <div class="fw-bold lh-1 mb-0" id="<?= $c['id'] ?>" style="font-size:18px;color:<?= $c['color'] ?>;">
                            <?= number_format((int) $c['value']) ?>
                        </div>
                        <div style="font-size:9px;color:#666;margin-top:2px;"><?= $c['label'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Global Search..."
                            value="<?= htmlspecialchars($filters['search'] ?? '') ?>" oninput="triggerInstantFilter()">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="filterYear" class="form-select form-select-sm" onchange="triggerInstantFilter()">
                        <option value="">-- All Years --</option>
                        <?php foreach ($yearOptions as $y): ?>
                            <option value="<?= htmlspecialchars($y) ?>" <?= (strcasecmp($filters['year'] ?? '', $y) === 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($y) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterBatch" class="form-select form-select-sm" onchange="triggerInstantFilter()">
                        <option value="">-- All Batches/Months --</option>
                        <?php foreach ($batchOptions as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>" <?= (strcasecmp($filters['batch'] ?? '', $b) === 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterCourse" class="form-select form-select-sm" onchange="triggerInstantFilter()">
                        <option value="">-- All Courses --</option>
                        <?php foreach ($courseOptions ?? [] as $crs): ?>
                            <option value="<?= htmlspecialchars($crs) ?>" <?= (strcasecmp($filters['course'] ?? '', $crs) === 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($crs) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterCode" class="form-select form-select-sm" onchange="triggerInstantFilter()">
                        <option value="">-- All ITGK Centers --</option>
                        <?php foreach ($codeOptions as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= (strcasecmp($filters['code'] ?? '', $c) === 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-center justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters()"><i
                            class="fas fa-undo me-1"></i> Reset</button>
                    <select id="pageSizeSelect" class="form-select form-select-sm w-auto" onchange="changePageSize()">
                        <option value="15">15 / pg</option>
                        <option value="25" selected>25 / pg</option>
                        <option value="50">50 / pg</option>
                        <option value="100">100 / pg</option>
                        <option value="1000">All</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Desktop Card-Row Table View -->
    <div class="d-none d-md-block mb-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="admissionsCardTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;" class="rounded-start">#</th>
                        <th style="width: 100px;">Year</th>
                        <th style="width: 110px;">Center Code</th>
                        <th>ITGK Center Name</th>
                        <th style="width: 160px;">Batch / Month</th>
                        <th style="width: 100px;">Course</th>
                        <th style="width: 80px;">Uploaded</th>
                        <th style="width: 80px;">Confirmed</th>
                        <th style="width: 120px;">Book Issue Status</th>
                    </tr>
                </thead>
                <tbody id="desktopTbody">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card Grid -->
    <div class="d-block d-md-none mb-3">
        <div class="row g-2" id="mobileCardContainer">
        </div>
    </div>

    <!-- Pagination -->
    <div class="card border-0 shadow-sm py-2 px-3 d-flex flex-row justify-content-between align-items-center">
        <small class="text-muted" id="pageSummary">Showing 0 of 0 Records</small>
        <nav>
            <ul class="pagination pagination-sm m-0" id="paginationControls"></ul>
        </nav>
    </div>
</div>

<script>
    // All raw admissions records passed from controller
    var rawAdmissionsData = <?= json_encode($admissionsList ?? []) ?>;

    var currentPage = 1;
    var pageSize = 25;
    var filteredData = rawAdmissionsData.slice();

    function triggerInstantFilter() {
        var search = document.getElementById('filterSearch').value.trim().toLowerCase();
        var batch = document.getElementById('filterBatch').value.trim().toLowerCase();
        var year = document.getElementById('filterYear').value.trim().toLowerCase();
        var code = document.getElementById('filterCode').value.trim().toLowerCase();
        var courseFilter = document.getElementById('filterCourse') ? document.getElementById('filterCourse').value.trim().toLowerCase() : '';

        // Update URL query string without page reload
        var params = new URLSearchParams();
        if (search) params.set('search', search);
        if (batch) params.set('batch', batch);
        if (year) params.set('year', year);
        if (code) params.set('code', code);
        if (courseFilter) params.set('course', courseFilter);

        var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({ path: newUrl }, '', newUrl);

        // Client-side filtering
        filteredData = rawAdmissionsData.filter(function (item) {
            if (batch && strcasecmp(item.batch, batch) !== 0) {
                return false;
            }
            if (year && strcasecmp(item.year, year) !== 0) {
                return false;
            }
            if (code && !(item.code || '').toLowerCase().includes(code)) {
                return false;
            }
            if (courseFilter && strcasecmp(item.course, courseFilter) !== 0) {
                return false;
            }
            if (search) {
                var combined = (item.batch + ' ' + item.code + ' ' + item.name + ' ' + item.course + ' ' + item.key).toLowerCase();
                if (!combined.includes(search)) {
                    return false;
                }
            }
            return true;
        });

        currentPage = 1;
        renderViews();
    }

    function strcasecmp(a, b) {
        return String(a || '').toLowerCase().localeCompare(String(b || '').toLowerCase());
    }

    function resetFilters() {
        document.getElementById('filterSearch').value = '';
        document.getElementById('filterBatch').value = '';
        document.getElementById('filterYear').value = '';
        document.getElementById('filterCode').value = '';
        if (document.getElementById('filterCourse')) document.getElementById('filterCourse').value = '';

        window.history.replaceState({ path: window.location.pathname }, '', window.location.pathname);

        filteredData = rawAdmissionsData.slice();
        currentPage = 1;
        renderViews();
    }

    function changePageSize() {
        pageSize = parseInt(document.getElementById('pageSizeSelect').value) || 25;
        currentPage = 1;
        renderViews();
    }

    function updateAnalyticsCards() {
        var totalRecs = filteredData.length;

        var uniqueBatches = {};
        var uniqueCenters = {};
        var confirmSum = 0;

        filteredData.forEach(function (item) {
            if (item.batch) uniqueBatches[item.batch] = true;
            if (item.code) uniqueCenters[item.code] = true;
            confirmSum += parseInt(item.total_confirm || 0) || 0;
        });

        var batchCount = Object.keys(uniqueBatches).length;
        var centerCount = Object.keys(uniqueCenters).length;

        var statTotal = document.getElementById('statTotalVal');
        var statTotalConfirm = document.getElementById('statTotalConfirmVal');
        var statBatches = document.getElementById('statBatchesVal');
        var statCenters = document.getElementById('statCentersVal');
        var statFiltered = document.getElementById('statFilteredVal');

        if (statTotal) statTotal.innerText = totalRecs.toLocaleString();
        if (statTotalConfirm) statTotalConfirm.innerText = confirmSum.toLocaleString();
        if (statBatches) statBatches.innerText = batchCount.toLocaleString();
        if (statCenters) statCenters.innerText = centerCount.toLocaleString();
        if (statFiltered) statFiltered.innerText = totalRecs.toLocaleString();
    }

    function renderViews() {
        updateAnalyticsCards();
        renderDesktopTable();
        renderMobileCards();
        renderPagination();
    }

    function renderDesktopTable() {
        var tbody = document.getElementById('desktopTbody');
        tbody.innerHTML = '';

        if (filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted bg-white rounded">No matching admissions records found.</td></tr>';
            return;
        }

        var start = (currentPage - 1) * pageSize;
        var end = pageSize === 1000 ? filteredData.length : Math.min(start + pageSize, filteredData.length);
        var pageItems = filteredData.slice(start, end);

        // Group by batch
        var groups = {};
        pageItems.forEach(function (item) {
            var b = item.batch || 'Unknown';
            if (!groups[b]) groups[b] = [];
            groups[b].push(item);
        });

        var globalIdx = start;
        Object.keys(groups).sort().forEach(function (batchKey) {
            var items = groups[batchKey];
            // Batch group header row
            var headerTr = document.createElement('tr');
            headerTr.className = 'batch-group-header';
            headerTr.innerHTML = '<td colspan="9"><i class="fas fa-calendar-alt me-2"></i>' + escapeHtml(batchKey) + ' <span class="badge bg-secondary ms-2">' + items.length + ' record(s)</span></td>';
            tbody.appendChild(headerTr);

            items.forEach(function (item) {
                globalIdx++;
                var statusBadge = '';
                var statusLower = (item.book_issue_status || '').toLowerCase();
                if (statusLower === 'issued') {
                    statusBadge = '<span class="badge status-badge-issued">ISSUED</span>';
                } else if (statusLower === 'pending') {
                    statusBadge = '<span class="badge status-badge-pending">PENDING</span>';
                } else {
                    statusBadge = '<span class="badge bg-light text-dark border">' + escapeHtml(item.book_issue_status) + '</span>';
                }

                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + globalIdx + '</td>' +
                    '<td>' + escapeHtml(item.year) + '</td>' +
                    '<td><span class="font-monospace fw-bold text-primary">' + escapeHtml(item.code) + '</span></td>' +
                    '<td>' + escapeHtml(item.name) + '</td>' +
                    '<td><span class="badge bg-light text-dark border">' + escapeHtml(item.batch) + '</span></td>' +
                    '<td>' + escapeHtml(item.course) + '</td>' +
                    '<td class="text-center fw-bold">' + escapeHtml(item.total_uploaded) + '</td>' +
                    '<td class="text-center fw-bold">' + escapeHtml(item.total_confirm) + '</td>' +
                    '<td>' + statusBadge + '</td>';
                tbody.appendChild(tr);
            });
        });
    }

    function renderMobileCards() {
        var container = document.getElementById('mobileCardContainer');
        container.innerHTML = '';

        if (filteredData.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="card border-0 shadow-sm p-4 text-center text-muted">No matching admissions records found.</div></div>';
            return;
        }

        var start = (currentPage - 1) * pageSize;
        var end = pageSize === 1000 ? filteredData.length : Math.min(start + pageSize, filteredData.length);
        var pageItems = filteredData.slice(start, end);

        // Group by batch
        var groups = {};
        pageItems.forEach(function (item) {
            var b = item.batch || 'Unknown';
            if (!groups[b]) groups[b] = [];
            groups[b].push(item);
        });

        Object.keys(groups).sort().forEach(function (batchKey) {
            var items = groups[batchKey];
            // Batch group header
            var headerDiv = document.createElement('div');
            headerDiv.className = 'col-12';
            headerDiv.innerHTML = '<div class="card border-0 shadow-sm mb-2"><div class="card-body p-2 bg-light"><h6 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>' + escapeHtml(batchKey) + ' <span class="badge bg-secondary ms-2">' + items.length + '</span></h6></div></div>';
            container.appendChild(headerDiv);

            items.forEach(function (item) {
                var cardDiv = document.createElement('div');
                cardDiv.className = 'col-12';
                cardDiv.innerHTML =
                    '<div class="card border-0 shadow-sm mb-2">' +
                    '<div class="card-body p-3">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2">' +
                    '<div>' +
                    '<span class="badge bg-secondary font-monospace me-1">' + escapeHtml(item.code) + '</span>' +
                    '<span class="fw-bold text-dark">' + escapeHtml(item.name) + '</span>' +
                    '</div>' +
                    '</div>' +
                    '<div class="small text-muted mb-2">' +
                    '<span class="badge bg-light text-dark border me-1">' + escapeHtml(item.batch) + '</span>' +
                    '<span class="badge bg-light text-dark border me-1">' + escapeHtml(item.course) + '</span>' +
                    '<span class="badge bg-light text-dark border">Up: ' + escapeHtml(item.total_uploaded) + '</span>' +
                    '<span class="badge bg-light text-dark border">Conf: ' + escapeHtml(item.total_confirm) + '</span>' +
                    '</div>' +
                    '<div class="text-muted small">' +
                    '<span class="badge bg-light text-dark border">Year: ' + escapeHtml(item.year) + '</span>' +
                    '<span class="badge bg-light text-dark border ms-1">' + escapeHtml(item.key) + '</span>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                container.appendChild(cardDiv);
            });
        });
    }

    function renderPagination() {
        var totalItems = filteredData.length;
        var totalPages = Math.ceil(totalItems / pageSize);
        var summary = document.getElementById('pageSummary');
        var controls = document.getElementById('paginationControls');

        controls.innerHTML = '';

        if (totalItems === 0) {
            summary.innerText = 'Showing 0 of 0 Records';
            return;
        }

        var start = (currentPage - 1) * pageSize + 1;
        var end = pageSize === 1000 ? totalItems : Math.min(currentPage * pageSize, totalItems);
        summary.innerText = 'Showing ' + start + ' to ' + end + ' of ' + totalItems + ' Records';

        if (totalPages <= 1) return;

        var prevLi = document.createElement('li');
        prevLi.className = 'page-item ' + (currentPage === 1 ? 'disabled' : '');
        prevLi.innerHTML = '<a class="page-link" href="#" onclick="gotoPage(' + (currentPage - 1) + '); return false;">Prev</a>';
        controls.appendChild(prevLi);

        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

        for (var p = startPage; p <= endPage; p++) {
            var li = document.createElement('li');
            li.className = 'page-item ' + (p === currentPage ? 'active' : '');
            li.innerHTML = '<a class="page-link" href="#" onclick="gotoPage(' + p + '); return false;">' + p + '</a>';
            controls.appendChild(li);
        }

        var nextLi = document.createElement('li');
        nextLi.className = 'page-item ' + (currentPage === totalPages ? 'disabled' : '');
        nextLi.innerHTML = '<a class="page-link" href="#" onclick="gotoPage(' + (currentPage + 1) + '); return false;">Next</a>';
        controls.appendChild(nextLi);
    }

    function gotoPage(page) {
        var totalPages = Math.ceil(filteredData.length / pageSize);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderViews();
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Hide loading overlay and show content
        var overlay = document.getElementById('admissionsLoadingOverlay');
        var content = document.getElementById('admissionsContent');

        requestAnimationFrame(function () {
            if (overlay) {
                overlay.classList.add('hidden');
                setTimeout(function () {
                    if (overlay) overlay.style.display = 'none';
                }, 300);
            }
            if (content) {
                content.style.display = '';
            }
        });

        // Read initial URL params and filter instantly
        var params = new URLSearchParams(window.location.search);
        if (params.has('search') || params.has('batch') || params.has('year') || params.has('code')) {
            triggerInstantFilter();
        } else {
            renderViews();
        }
    });
</script>