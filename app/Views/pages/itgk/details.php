<?php
/**
 * ITGK Center Details Master Directory View
 * 
 * Includes: Card-based Table Rows (5px spacing), Automatic live instant filtering (No Page Reload),
 * Advanced Controls, Custom Page Size, Interactive Pagination, Native Mobile Card Grid.
 */
?>
<style>
/* Card Table Row Styling (5px padding/gap between rows) */
#itgkCardTable {
    border-collapse: separate !important;
    border-spacing: 0 5px !important;
}

#itgkCardTable tbody tr {
    background-color: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border-radius: 6px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

#itgkCardTable tbody tr:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

#itgkCardTable tbody td {
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    padding: 10px 12px;
}

#itgkCardTable tbody td:first-child {
    border-left: 1px solid #f1f5f9;
    border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
}

#itgkCardTable tbody td:last-child {
    border-right: 1px solid #f1f5f9;
    border-top-right-radius: 6px;
    border-bottom-right-radius: 6px;
}
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-primary">
                <i class="fas fa-building me-2"></i>ITGK Center Details
            </h4>
            <p class="text-muted small mb-0">Master directory of ITGK Centers and contact information</p>
        </div>
    </div>

    <!-- ─── ANALYTICS STRIP ───────────────────────────────── -->
    <?php if (!empty($analytics)): ?>
    <div id="analyticsStripSection" class="mb-2" style="overflow-x:auto;">
        <div id="analyticsCardsRow" class="d-flex gap-1" style="min-width:100%;">
            <?php
            $cards = [
                ['label'=>'Total Master', 'value'=>$analytics['total'] ?? 0,         'icon'=>'fa-building',       'color'=>'#4f46e5', 'bg'=>'#eef2ff'],
                ['label'=>'Active 2026',  'value'=>$analytics['active'] ?? 0,          'icon'=>'fa-check-circle',   'color'=>'#16a34a', 'bg'=>'#f0fdf4'],
                ['label'=>'Expired',      'value'=>$analytics['expired'] ?? 0,         'icon'=>'fa-exclamation-triangle', 'color'=>'#dc2626', 'bg'=>'#fef2f2'],
                ['label'=>'Districts',    'value'=>$analytics['districts'] ?? 0,       'icon'=>'fa-map-marker-alt', 'color'=>'#0891b2', 'bg'=>'#ecfeff'],
                ['label'=>'Filtered',     'value'=>$analytics['filtered'] ?? 0,        'icon'=>'fa-filter',         'color'=>'#d97706', 'bg'=>'#fffbeb'],
            ];
            foreach ($cards as $c): ?>
            <div class="flex-shrink-0 rounded-3 text-center p-2"
                style="background:<?= $c['bg'] ?>;min-width:80px;border:1px solid <?= $c['color'] ?>22;">
                <i class="fas <?= $c['icon'] ?> mb-1" style="color:<?= $c['color'] ?>;font-size:16px;"></i>
                <div class="fw-bold lh-1 mb-0" style="font-size:18px;color:<?= $c['color'] ?>;">
                    <?= number_format((int)$c['value']) ?>
                </div>
                <div style="font-size:9px;color:#666;margin-top:2px;"><?= $c['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Automatic Instant Filter Bar (No Page Reload) -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Global Search (Code/Name/Mobile)..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" oninput="triggerInstantFilter()">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="text" id="filterCode" class="form-control form-control-sm" placeholder="ITGK Code..." value="<?= htmlspecialchars($filters['code'] ?? '') ?>" oninput="triggerInstantFilter()">
                </div>
                <div class="col-md-2">
                    <select id="filterDistrict" class="form-select form-select-sm" onchange="triggerInstantFilter()">
                        <option value="">-- All Districts --</option>
                        <?php foreach ($districtOptions as $dist): ?>
                            <option value="<?= htmlspecialchars($dist) ?>" <?= (strcasecmp($filters['district'] ?? '', $dist) === 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dist) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterStatus" class="form-select form-select-sm" onchange="triggerInstantFilter()">
                        <option value="">-- All Status --</option>
                        <option value="Active" <?= (strcasecmp($filters['status'] ?? '', 'Active') === 0) ? 'selected' : '' ?>>Active</option>
                        <option value="Expired" <?= (strcasecmp($filters['status'] ?? '', 'Expired') === 0) ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters()"><i class="fas fa-undo me-1"></i> Reset</button>
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

    <!-- Desktop Card-Row Table View (Hidden on Mobile) -->
    <div class="d-none d-md-block mb-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="itgkCardTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;" class="rounded-start">#</th>
                        <th style="width: 320px;">ITGK Center Details</th>
                        <th>Location Details</th>
                        <th style="width: 220px;">Contact & Actions</th>
                        <th style="width: 100px;" class="rounded-end">Status</th>
                    </tr>
                </thead>
                <tbody id="desktopTbody">
                    <!-- Rendered dynamically via JavaScript for live non-reloading filter & pagination -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Native Mobile App Card Grid (Visible on Mobile Only) -->
    <div class="d-block d-md-none mb-3">
        <div class="row g-2" id="mobileCardContainer">
            <!-- Rendered dynamically via JavaScript -->
        </div>
    </div>

    <!-- Advanced Pagination & Record Counter Controls -->
    <div class="card border-0 shadow-sm py-2 px-3 d-flex flex-row justify-content-between align-items-center">
        <small class="text-muted" id="pageSummary">Showing 0 of 0 ITGK Centers</small>
        <nav>
            <ul class="pagination pagination-sm m-0" id="paginationControls"></ul>
        </nav>
    </div>
</div>

<script>
// All raw ITGK master records passed from controller
const rawItgkData = <?= json_encode($itgkList ?? []) ?>;

let currentPage = 1;
let pageSize = 25;
let filteredData = [...rawItgkData];

function triggerInstantFilter() {
    const search = document.getElementById('filterSearch').value.trim().toLowerCase();
    const code = document.getElementById('filterCode').value.trim().toLowerCase();
    const district = document.getElementById('filterDistrict').value.trim().toLowerCase();
    const status = document.getElementById('filterStatus').value.trim().toLowerCase();

    // Dynamically update URL address query string without reloading page
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (code) params.set('code', code);
    if (district) params.set('district', district);
    if (status) params.set('status', status);

    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({ path: newUrl }, '', newUrl);

    // Perform instant client-side filtering across array
    filteredData = rawItgkData.filter(item => {
        if (code && !(item.code || '').toLowerCase().includes(code)) {
            return false;
        }
        if (district && (item.district || '').toLowerCase() !== district) {
            return false;
        }
        if (status && (item.status || '').toLowerCase() !== status) {
            return false;
        }
        if (search) {
            const combined = `${item.code} ${item.name} ${item.mobile} ${item.email} ${item.district} ${item.address}`.toLowerCase();
            if (!combined.includes(search)) {
                return false;
            }
        }
        return true;
    });

    currentPage = 1;
    renderViews();
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterCode').value = '';
    document.getElementById('filterDistrict').value = '';
    document.getElementById('filterStatus').value = '';
    
    window.history.replaceState({ path: window.location.pathname }, '', window.location.pathname);
    
    filteredData = [...rawItgkData];
    currentPage = 1;
    renderViews();
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pageSizeSelect').value) || 25;
    currentPage = 1;
    renderViews();
}

function renderViews() {
    renderDesktopTable();
    renderMobileCards();
    renderPagination();
}

function renderDesktopTable() {
    const tbody = document.getElementById('desktopTbody');
    tbody.innerHTML = '';

    if (filteredData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted bg-white rounded">No matching ITGK Center records found.</td></tr>';
        return;
    }

    const start = (currentPage - 1) * pageSize;
    const end = pageSize === 1000 ? filteredData.length : Math.min(start + pageSize, filteredData.length);
    const pageItems = filteredData.slice(start, end);

    pageItems.forEach((item, idx) => {
        const cleanMobile = (item.mobile || '').replace(/[^0-9]/g, '');
        const cleanEmail = (item.email || '').trim();

        let actionButtonsHtml = '';
        if (cleanMobile) {
            actionButtonsHtml += `<a href="tel:${cleanMobile}" class="text-decoration-none fs-5 me-2" title="Call Center" style="color: #0d6efd;"><i class="fas fa-phone-alt"></i></a>`;
            actionButtonsHtml += `<a href="sms:${cleanMobile}" class="text-decoration-none fs-5 me-2" title="Send SMS" style="color: #0dcaf0;"><i class="fas fa-comment-dots"></i></a>`;
            actionButtonsHtml += `<a href="https://wa.me/91${cleanMobile}" target="_blank" class="text-decoration-none fs-5 me-2" title="WhatsApp Message" style="color: #25d366;"><i class="fab fa-whatsapp"></i></a>`;
        }
        if (cleanEmail && cleanEmail !== '-') {
            actionButtonsHtml += `<a href="mailto:${escapeHtml(cleanEmail)}" class="text-decoration-none fs-5" title="Send Email" style="color: #ea4335;"><i class="fas fa-envelope"></i></a>`;
        }

        const statusBadge = item.status === 'Active'
            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>'
            : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Expired</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${start + idx + 1}</td>
            <td>
                <div class="fw-bold text-primary mb-1">
                    <span class="badge bg-secondary font-monospace me-1">${escapeHtml(item.code)}</span>
                    ${escapeHtml(item.name)}
                </div>
            </td>
            <td style="max-width: 300px;">
                <div class="small">
                    <div class="text-dark fw-semibold mb-1 text-truncate" title="${escapeHtml(item.address)}">
                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                        ${escapeHtml(item.address)}
                    </div>
                    <div class="text-muted">
                        <span class="badge bg-light text-dark border me-1">Tehsil: ${escapeHtml(item.tehsil)}</span>
                        <span class="badge bg-light text-dark border">Dist: ${escapeHtml(item.district)}</span>
                    </div>
                </div>
            </td>
            <td>
                <div class="small mb-1">
                    ${cleanMobile ? `<div class="text-dark font-monospace"><i class="fas fa-phone me-1 text-secondary"></i>${escapeHtml(item.mobile)}</div>` : ''}
                    ${cleanEmail && cleanEmail !== '-' ? `<div class="text-truncate text-muted" style="max-width: 200px;" title="${escapeHtml(cleanEmail)}"><i class="fas fa-envelope me-1 text-secondary"></i>${escapeHtml(cleanEmail)}</div>` : ''}
                </div>
                <div class="d-flex align-items-center mt-1">
                    ${actionButtonsHtml}
                </div>
            </td>
            <td>${statusBadge}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderMobileCards() {
    const container = document.getElementById('mobileCardContainer');
    container.innerHTML = '';

    if (filteredData.length === 0) {
        container.innerHTML = '<div class="col-12"><div class="card border-0 shadow-sm p-4 text-center text-muted">No matching ITGK Center records found.</div></div>';
        return;
    }

    const start = (currentPage - 1) * pageSize;
    const end = pageSize === 1000 ? filteredData.length : Math.min(start + pageSize, filteredData.length);
    const pageItems = filteredData.slice(start, end);

    pageItems.forEach(item => {
        const cleanMobile = (item.mobile || '').replace(/[^0-9]/g, '');
        const cleanEmail = (item.email || '').trim();

        let actionButtonsHtml = '';
        if (cleanMobile) {
            actionButtonsHtml += `<a href="tel:${cleanMobile}" class="text-decoration-none" title="Call Center" style="color: #0d6efd;"><i class="fas fa-phone-alt"></i></a>`;
            actionButtonsHtml += `<a href="sms:${cleanMobile}" class="text-decoration-none" title="Send SMS" style="color: #0dcaf0;"><i class="fas fa-comment-dots"></i></a>`;
            actionButtonsHtml += `<a href="https://wa.me/91${cleanMobile}" target="_blank" class="text-decoration-none" title="WhatsApp Message" style="color: #25d366;"><i class="fab fa-whatsapp"></i></a>`;
        }
        if (cleanEmail && cleanEmail !== '-') {
            actionButtonsHtml += `<a href="mailto:${escapeHtml(cleanEmail)}" class="text-decoration-none" title="Send Email" style="color: #ea4335;"><i class="fas fa-envelope"></i></a>`;
        }

        const statusBadge = item.status === 'Active'
            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>'
            : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Expired</span>';

        const cardDiv = document.createElement('div');
        cardDiv.className = 'col-12';
        cardDiv.innerHTML = `
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-secondary font-monospace me-1">${escapeHtml(item.code)}</span>
                            <span class="fw-bold text-dark fs-6">${escapeHtml(item.name)}</span>
                        </div>
                        <div>${statusBadge}</div>
                    </div>
                    <div class="small text-muted mb-2">
                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                        ${escapeHtml(item.address)}
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border me-1">Tehsil: ${escapeHtml(item.tehsil)}</span>
                            <span class="badge bg-light text-dark border">Dist: ${escapeHtml(item.district)}</span>
                        </div>
                    </div>
                    <hr class="my-2 text-muted opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small">
                            ${cleanMobile ? `<div class="text-dark font-monospace fw-semibold"><i class="fas fa-phone me-1 text-secondary"></i>${escapeHtml(item.mobile)}</div>` : ''}
                        </div>
                        <div class="d-flex gap-3 align-items-center fs-4">
                            ${actionButtonsHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(cardDiv);
    });
}

function renderPagination() {
    const totalItems = filteredData.length;
    const totalPages = Math.ceil(totalItems / pageSize);
    const summary = document.getElementById('pageSummary');
    const controls = document.getElementById('paginationControls');

    controls.innerHTML = '';

    if (totalItems === 0) {
        summary.innerText = 'Showing 0 of 0 ITGK Centers';
        return;
    }

    const start = (currentPage - 1) * pageSize + 1;
    const end = pageSize === 1000 ? totalItems : Math.min(currentPage * pageSize, totalItems);
    summary.innerText = `Showing ${start} to ${end} of ${totalItems} ITGK Centers`;

    if (totalPages <= 1) return;

    // Previous Button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${currentPage - 1}); return false;">Prev</a>`;
    controls.appendChild(prevLi);

    // Page Numbers (limited to 5 visible)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    for (let p = startPage; p <= endPage; p++) {
        const li = document.createElement('li');
        li.className = `page-item ${p === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${p}); return false;">${p}</a>`;
        controls.appendChild(li);
    }

    // Next Button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${currentPage + 1}); return false;">Next</a>`;
    controls.appendChild(nextLi);
}

function gotoPage(page) {
    const totalPages = Math.ceil(filteredData.length / pageSize);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderViews();
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
    // Read initial URL params if present, set dropdown/input values, and filter instantly
    const params = new URLSearchParams(window.location.search);

    if (params.has('search')) document.getElementById('filterSearch').value = params.get('search');
    if (params.has('code')) document.getElementById('filterCode').value = params.get('code');
    if (params.has('district')) document.getElementById('filterDistrict').value = params.get('district');
    if (params.has('status')) {
        const rawStatus = params.get('status') || '';
        const select = document.getElementById('filterStatus');
        for (let opt of select.options) {
            if (opt.value.toLowerCase() === rawStatus.toLowerCase()) {
                select.value = opt.value;
                break;
            }
        }
    }

    if (params.has('search') || params.has('code') || params.has('district') || params.has('status')) {
        triggerInstantFilter();
    } else {
        renderViews();
    }
});
</script>
