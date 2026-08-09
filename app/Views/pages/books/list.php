<?php
/**
 * Books Management & ITGK Book Issue List View (Advanced Table & Full Data Rendering)
 *
 * Includes: All 750+ Book Issue records rendered (Newest at top), Advanced Filter Controls,
 * Page size selector, Dynamic Search, Client-side pagination, Multi-item Dynamic Form,
 * Professional Printable Receipt with complete center & receiver details.
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-primary">
                <i class="fas fa-book me-2"></i>ITGK Books Management
            </h4>
            <p class="text-muted small mb-0">Track and manage complete ITGK Book transactions & receipts (Total: <?= $analytics['total_transactions'] ?? 0 ?> Records)</p>
        </div>
        <div>
            <button type="button" class="btn btn-success btn-sm shadow-sm px-3" onclick="prepareNewIssue()">
                <i class="fas fa-plus-circle me-1"></i> New Book Transaction
            </button>
        </div>
    </div>

    <!-- ─── ANALYTICS STRIP ───────────────────────────────── -->
    <?php if (!empty($analytics)): ?>
    <div id="analyticsStripSection" class="mb-2" style="overflow-x:auto;">
        <div id="analyticsCardsRow" class="d-flex gap-1" style="min-width:100%;">
            <?php
            $cards = [
                ['label'=>'Total Txns',  'value'=>$analytics['total_transactions'] ?? 0, 'icon'=>'fa-exchange-alt',  'color'=>'#4f46e5', 'bg'=>'#eef2ff'],
                ['label'=>'Issued',      'value'=>$analytics['total_issued'] ?? 0,        'icon'=>'fa-book-reader',    'color'=>'#16a34a', 'bg'=>'#f0fdf4'],
                ['label'=>'Transferred', 'value'=>$analytics['total_transfered'] ?? 0,    'icon'=>'fa-exchange-alt',   'color'=>'#d97706', 'bg'=>'#fffbeb'],
                ['label'=>'Received',    'value'=>$analytics['total_received'] ?? 0,      'icon'=>'fa-inbox',          'color'=>'#0891b2', 'bg'=>'#ecfeff'],
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

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Advanced Controls & Filter Bar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-2 d-flex align-items-center">
                    <label class="me-2 small text-muted">Show:</label>
                    <select id="pageSize" class="form-select form-select-sm" onchange="changePageSize()">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="1000">All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search Code, Name, Receiver..." onkeyup="filterTable()">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="txnTypeFilter" class="form-select form-select-sm" onchange="filterTable()">
                        <option value="">-- All Txn Types --</option>
                        <option value="Issued">Issued</option>
                        <option value="Received">Received</option>
                        <option value="Transfered">Transfered</option>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark border p-2" id="recordCounter">
                        Showing <span id="showingCount">0</span> of <?= count($books) ?> Records
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Books Issue Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="booksTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>ITGK Code</th>
                            <th>Center Name</th>
                            <th>Course / Medium</th>
                            <th>Txn Type</th>
                            <th>Qty</th>
                            <th>Receiver Name</th>
                            <th>Receiver Mobile</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Loaded dynamically via JS for fast pagination -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-2 border-top-0 d-flex justify-content-between align-items-center">
            <small class="text-muted" id="pageInfo">Page 1 of 1</small>
            <nav>
                <ul class="pagination pagination-sm m-0" id="pagination"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Offcanvas Form: Multi-item Book Issue/Transaction -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="issueBookOffcanvas" aria-labelledby="issueBookOffcanvasLabel" style="width: 500px;">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title" id="issueBookOffcanvasLabel"><i class="fas fa-book me-2"></i>Book Transaction</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="issueForm" onsubmit="handleFormSubmit(event)">
            <div id="items-container"></div>
            
            <div class="mb-3 text-end">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCourseItem()">
                    <i class="fas fa-plus me-1"></i> Add Another Course
                </button>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">ITGK Code <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="ITGK CODE" list="itgkCodeList" placeholder="Enter or search ITGK Code..." required autocomplete="off">
                </div>
                <datalist id="itgkCodeList">
                    <?php foreach ($itgkList ?? [] as $itgk): ?>
                        <option value="<?= htmlspecialchars($itgk['code']) ?>"><?= htmlspecialchars($itgk['name']) ?></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="mb-3">
                <label class="form-label">ITGK Name</label>
                <input type="text" class="form-control bg-light" name="NAME" placeholder="Auto-filled from Code" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">ITGK Email</label>
                <input type="email" class="form-control bg-light" name="ITGK Email" placeholder="Auto-filled from Code" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">ITGK Mobile</label>
                <input type="tel" class="form-control bg-light" name="ITGK Mobile" placeholder="Auto-filled from Code" readonly>
            </div>

            <hr class="my-3">
            <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-user-check me-2"></i>Collector / Receiver Details</h6>
            
            <div class="mb-3">
                <label class="form-label">Receiver Name</label>
                <input type="text" class="form-control" name="Receiver Name" placeholder="Person collecting/returning books">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Mobile No.</label>
                <input type="tel" class="form-control" name="Receiver Mobile No." placeholder="Mobile Number">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email ID (for Receipt Notification)</label>
                <input type="email" class="form-control" name="Email ID" placeholder="itgk@example.com">
            </div>

            <div class="mb-3">
                <label class="form-label">Document Link / Remarks</label>
                <input type="text" class="form-control" name="Merged Document link" placeholder="Note or Document link">
            </div>

            <hr class="my-3">
            <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-user me-2"></i>Issuer Details (Logged-in User)</h6>

            <div class="mb-3">
                <label class="form-label">Issued By (Name)</label>
                <input type="text" class="form-control" name="issuer_name" id="issuerName" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Issuer Mobile</label>
                <input type="tel" class="form-control" name="issuer_mobile" id="issuerMobile" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Issuer Office</label>
                <input type="text" class="form-control" name="issuer_office" id="issuerOffice" readonly>
            </div>

            <div class="form-check form-switch mt-3 mb-3">
                <input class="form-check-input" type="checkbox" id="sendEmailNotify" name="sendEmailNotify" checked>
                <label class="form-check-label text-muted small" for="sendEmailNotify">Send Email Receipt Automatically to ITGK</label>
            </div>

            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="fas fa-save me-1"></i> Save Book Transaction
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Receipt Modal: Professional PDF Printable & Shareable Receipt -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold text-primary"><i class="fas fa-receipt me-2"></i>Transaction Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="receiptContent">
                <div class="receipt-container p-4 bg-white" style="font-family: sans-serif; color: #000;">
                    <div style="text-align: right; font-size: 10px; font-weight: bold;">TIN NO 08172610923</div>
                    <div style="text-align: center;">
                        <h2 style="color: #1e3a8a; font-family: serif; font-size: 22px; margin: 5px 0; font-weight: bold; text-transform: uppercase;">SOFTTECH MULTI SERVICE PVT. LTD.</h2>
                        <div style="font-size: 12px; font-weight: bold;">RKCL SP, Emitra LSP</div>
                        <div style="font-size: 12px; margin-top: 4px;">Head Off : Near Teshil Bhawan, Osian Dist. Jodhpur (RAJ) 342303</div>
                        <div style="font-size: 12px;">Cop. Off: 180, Behind 'Sanjivani Ananda', Manji Ka hattha, Jodhpur</div>
                        <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">Phone 9413571175, 9314001171, 9983750 284</div>
                        <div style="font-size: 12px; font-weight: bold;">www.softtechseva.com, Email:- softtechseva@gmail.com</div>
                    </div>
                    <hr style="border-top: 1px solid #000; margin: 12px 0;" />
                    <table style="width: 100%; font-size: 13px; font-weight: bold; margin-bottom: 15px;">
                        <tr>
                            <td style="width: 120px; padding: 3px 0;">ITGK Name :</td>
                            <td style="border-bottom: 1px solid #ccc;" id="rct-itgk-name"></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;">ITGK Code :</td>
                            <td style="border-bottom: 1px solid #ccc;" id="rct-itgk-code"></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;">Receiver Name:</td>
                            <td style="border-bottom: 1px solid #ccc;" id="rct-receiver"></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;">Mobile No. :</td>
                            <td style="border-bottom: 1px solid #ccc;"><span id="rct-mobile"></span></td>
                        </tr>
                    </table>
                    
                    <div style="text-align: center; font-size: 15px; font-weight: bold; margin: 15px 0; text-transform: uppercase;">BOOK ISSUE ACKNOWLEDGEMENT</div>
                    
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: center; margin-bottom: 15px; border: 1px solid #000;">
                        <thead>
                            <tr style="background: #f1f5f9;">
                                <th style="border: 1px solid #000; padding: 8px;">COURSE NAME</th>
                                <th style="border: 1px solid #000; padding: 8px;">MEDIUM</th>
                                <th style="border: 1px solid #000; padding: 8px;">TRANSACTION TYPE</th>
                                <th style="border: 1px solid #000; padding: 8px;">QUANTITY</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px;" id="rct-course"></td>
                                <td style="border: 1px solid #000; padding: 8px;" id="rct-medium"></td>
                                <td style="border: 1px solid #000; padding: 8px;" id="rct-type"></td>
                                <td style="border: 1px solid #000; padding: 8px; font-weight: bold;" id="rct-qty"></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8fafc;">
                                <td colspan="3" style="border: 1px solid #000; text-align: left; padding: 8px; font-weight: bold;">TOTAL</td>
                                <td style="border: 1px solid #000; padding: 8px; font-weight: bold;" id="rct-total-qty"></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <table style="width: 100%; font-size: 13px; margin-top: 25px;">
                        <tr>
                            <td style="width: 50%;"><strong>Issued From :</strong> <span id="rct-issued-from" style="border-bottom: 1px dashed #000; padding: 0 20px; display: inline-block;"></span></td>
                            <td style="width: 50%; text-align: right;"><strong>DATE :</strong> <span id="rct-date" style="border-bottom: 1px dashed #000; padding: 0 20px; display: inline-block;"></span></td>
                        </tr>
                    </table>
                    
                    <div style="text-align: right; margin-top: 50px; font-size: 13px;">
                        Receiver Sign : .............................. (STAMP)
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const allBooks = <?= json_encode($books ?? []) ?>;
const itgkDataMap = <?= json_encode($itgkList ?? []) ?>;

let currentPage = 1;
let pageSize = 25;
let filteredBooks = [...allBooks];

function renderTable() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (filteredBooks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No matching Book Issue records found.</td></tr>';
        document.getElementById('pageInfo').innerText = 'Showing 0 of 0';
        document.getElementById('pagination').innerHTML = '';
        document.getElementById('showingCount').innerText = '0';
        return;
    }

    const start = (currentPage - 1) * pageSize;
    const end = pageSize === 1000 ? filteredBooks.length : Math.min(start + pageSize, filteredBooks.length);
    const pageItems = filteredBooks.slice(start, end);

    pageItems.forEach((b, index) => {
        const t = (b.txn_type || '').toLowerCase();
        let badgeClass = 'bg-primary';
        if (t.includes('issue')) badgeClass = 'bg-success';
        else if (t.includes('transfer')) badgeClass = 'bg-warning text-dark';
        else if (t.includes('receive')) badgeClass = 'bg-info text-dark';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${start + index + 1}</td>
            <td>${escapeHtml(b.issue_date || '-')}</td>
            <td><span class="badge bg-secondary font-monospace">${escapeHtml(b.itgk_code || '-')}</span></td>
            <td class="fw-bold text-dark">${escapeHtml(b.itgk_name || ('ITGK ' + b.itgk_code))}</td>
            <td>
                <span class="badge bg-info text-dark">${escapeHtml(b.course_name || 'RS-CIT')}</span>
                <span class="badge bg-light text-dark border">${escapeHtml(b.medium || 'Hindi')}</span>
            </td>
            <td><span class="badge ${badgeClass}">${escapeHtml(b.txn_type || 'Issued')}</span></td>
            <td class="fw-bold text-success fs-6">${b.quantity || 0}</td>
            <td>${escapeHtml(b.receiver_name || '-')}</td>
            <td>${escapeHtml(b.receiver_mobile || '-')}</td>
            <td class="text-end">
                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick='openReceipt(${JSON.stringify(b)})'>
                    <i class="fas fa-print me-1"></i> Receipt
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('showingCount').innerText = pageItems.length;
    document.getElementById('pageInfo').innerText = `Showing ${start + 1} to ${end} of ${filteredBooks.length} records`;

    renderPagination();
}

function renderPagination() {
    const totalPages = Math.ceil(filteredBooks.length / pageSize);
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    if (totalPages <= 1) return;

    // Previous Button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${currentPage - 1}); return false;">Prev</a>`;
    pagination.appendChild(prevLi);

    // Page Number Buttons (Limit visible pages)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    for (let p = startPage; p <= endPage; p++) {
        const li = document.createElement('li');
        li.className = `page-item ${p === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${p}); return false;">${p}</a>`;
        pagination.appendChild(li);
    }

    // Next Button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${currentPage + 1}); return false;">Next</a>`;
    pagination.appendChild(nextLi);
}

function gotoPage(p) {
    const totalPages = Math.ceil(filteredBooks.length / pageSize);
    if (p < 1 || p > totalPages) return;
    currentPage = p;
    renderTable();
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pageSize').value) || 25;
    currentPage = 1;
    renderTable();
}

function filterTable() {
    const query = document.getElementById('searchInput').value.trim().toLowerCase();
    const typeFilter = document.getElementById('txnTypeFilter').value.trim().toLowerCase();

    filteredBooks = allBooks.filter(b => {
        const matchesType = !typeFilter || (b.txn_type || '').toLowerCase().includes(typeFilter);
        if (!matchesType) return false;

        if (!query) return true;
        const searchStr = `${b.itgk_code} ${b.itgk_name} ${b.receiver_name} ${b.receiver_mobile} ${b.course_name}`.toLowerCase();
        return searchStr.includes(query);
    });

    currentPage = 1;
    renderTable();
}

function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

let itemIndex = 0;
function addCourseItem() {
    itemIndex++;
    const container = document.getElementById('items-container');
    const html = `
        <div class="item-row border rounded p-3 mb-3 bg-light position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2 remove-item-btn" onclick="this.parentElement.remove()"></button>
            
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">TRANSACTION TYPE</label>
                <div class="row g-2">
                    <div class="col-4">
                        <input type="radio" class="btn-check type-received" name="type_${itemIndex}" id="type-received-${itemIndex}" value="Received">
                        <label class="btn btn-outline-info btn-sm w-100" for="type-received-${itemIndex}">Received</label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check type-issued" name="type_${itemIndex}" id="type-issued-${itemIndex}" value="Issued" checked>
                        <label class="btn btn-outline-success btn-sm w-100" for="type-issued-${itemIndex}">Issued</label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check type-transfer" name="type_${itemIndex}" id="type-transfer-${itemIndex}" value="Transfered">
                        <label class="btn btn-outline-warning btn-sm w-100" for="type-transfer-${itemIndex}">Transfered</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-5 mb-2">
                    <label class="form-label">Course</label>
                    <select class="form-select item-course">
                         <option value="RS-CIT">RS-CIT</option>
                         <option value="RS-CFA">RS-CFA</option>
                         <option value="RS-CSEP">RS-CSEP</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Medium</label>
                    <select class="form-select item-medium">
                         <option value="Hindi">Hindi</option>
                         <option value="English">English</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Qty</label>
                    <input type="number" class="form-control item-qty fw-bold" value="1" min="1">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function prepareNewIssue() {
    const form = document.getElementById('issueForm');
    form.reset();
    document.getElementById('items-container').innerHTML = '';
    itemIndex = 0;
    addCourseItem();

    const offcanvas = new bootstrap.Offcanvas(document.getElementById('issueBookOffcanvas'));
    offcanvas.show();
}

document.addEventListener('DOMContentLoaded', () => {
    renderTable();

    const codeInput = document.querySelector('input[name="ITGK CODE"]');
    const nameInput = document.querySelector('input[name="NAME"]');
    const itgkEmailInput = document.querySelector('input[name="ITGK Email"]');
    const itgkMobileInput = document.querySelector('input[name="ITGK Mobile"]');
    const receiverEmailInput = document.querySelector('input[name="Email ID"]');
    const receiverMobileInput = document.querySelector('input[name="Receiver Mobile No."]');

    if (codeInput) {
        codeInput.addEventListener('input', function() {
            const val = this.value.trim();
            const data = itgkDataMap.find(s => s.code === val || val.startsWith(s.code));
            if (data) {
                if (nameInput) nameInput.value = data.name || '';
                if (itgkEmailInput) itgkEmailInput.value = data.email || '';
                if (itgkMobileInput) itgkMobileInput.value = data.mobile || '';
                if (receiverEmailInput) receiverEmailInput.value = data.email || '';
                if (receiverMobileInput) receiverMobileInput.value = data.mobile || '';
            }
        });
    }

    // Populate issuer details from session
    const issuerNameEl = document.getElementById('issuerName');
    const issuerMobileEl = document.getElementById('issuerMobile');
    const issuerOfficeEl = document.getElementById('issuerOffice');

    const issuerName  = <?= json_encode($_SESSION['user']['name'] ?? '') ?>;
    const issuerMobile = <?= json_encode($_SESSION['user']['mobile'] ?? '') ?>;
    const issuerOffice = <?= json_encode($_SESSION['user']['office_name'] ?? '') ?>;

    if (issuerNameEl) issuerNameEl.value = issuerName;
    if (issuerMobileEl) issuerMobileEl.value = issuerMobile;
    if (issuerOfficeEl) issuerOfficeEl.value = issuerOffice;
});

async function handleFormSubmit(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Saving...';

    const form = e.target;
    const formData = new FormData(form);
    const payload = {};
    formData.forEach((value, key) => payload[key] = value);

    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const txnType = row.querySelector('input[type="radio"]:checked')?.value || 'Issued';
        const course = row.querySelector('.item-course').value;
        const medium = row.querySelector('.item-medium').value;
        const qty = parseInt(row.querySelector('.item-qty').value) || 1;
        items.push({ txn_type: txnType, course, medium, qty });
    });

    payload.items = items;
    payload.sendEmailNotify = document.getElementById('sendEmailNotify').checked;

    try {
        const response = await fetch('<?= BASE_URL ?>books/issue', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) {
        alert('Failed to save transaction: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save Book Transaction';
    }
}

function openReceipt(b) {
    document.getElementById('rct-itgk-name').innerText = b.itgk_name || ('ITGK ' + b.itgk_code);
    document.getElementById('rct-itgk-code').innerText = b.itgk_code || '';
    document.getElementById('rct-receiver').innerText = b.receiver_name || '-';
    document.getElementById('rct-mobile').innerText = b.receiver_mobile || '-';
    document.getElementById('rct-course').innerText = b.course_name || 'RS-CIT';
    document.getElementById('rct-medium').innerText = b.medium || 'Hindi';
    document.getElementById('rct-type').innerText = b.txn_type || 'Issued';
    document.getElementById('rct-qty').innerText = b.quantity || 1;
    document.getElementById('rct-total-qty').innerText = b.quantity || 1;
    document.getElementById('rct-issued-from').innerText = b.issued_from || 'Head Office';
    document.getElementById('rct-date').innerText = b.issue_date || '<?= date("Y-m-d") ?>';

    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    modal.show();
}
</script>
