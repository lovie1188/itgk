<?php
/**
 * Books Management & ITGK Book Issue List View (ITGK Card-Row Pattern)
 *
 * Includes: Card-based Table Rows (5px spacing), Automatic live instant filtering,
 * Multi-filter controls (Search, Txn Type, Year, ITGK Code), Client-side pagination,
 * Edit Offcanvas, Soft Delete Confirmation Modal, Toast Notifications, Native Mobile Card Grid.
 */
?>
<style>
/* Card Table Row Styling (5px padding/gap between rows matching ITGK pattern) */
#booksCardTable {
    border-collapse: separate !important;
    border-spacing: 0 5px !important;
}

#booksCardTable tbody tr {
    background-color: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border-radius: 6px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

#booksCardTable tbody tr:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

#booksCardTable tbody td {
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    padding: 8px 10px;
}

#booksCardTable tbody td:first-child {
    border-left: 1px solid #f1f5f9;
    border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
}

#booksCardTable tbody td:last-child {
    border-right: 1px solid #f1f5f9;
    border-top-right-radius: 6px;
    border-bottom-right-radius: 6px;
}
</style>

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

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
    <div id="analyticsStripSection" class="mb-2 p-1" style="overflow-x:auto;">
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
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search Code, Name, Receiver..." oninput="triggerFilter()">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="text" id="filterYear" class="form-control form-control-sm" placeholder="Year (e.g. 2026)..." oninput="triggerFilter()">
                </div>
                <div class="col-md-2">
                    <input type="text" id="filterCode" class="form-control form-control-sm" placeholder="ITGK Code..." oninput="triggerFilter()">
                </div>
                <div class="col-md-2">
                    <select id="txnTypeFilter" class="form-select form-select-sm" onchange="triggerFilter()">
                        <option value="">-- All Txn Types --</option>
                        <option value="Issued">Issued</option>
                        <option value="Received">Received</option>
                        <option value="Transfered">Transfered</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters()"><i class="fas fa-undo me-1"></i> Reset</button>
                    <select id="pageSize" class="form-select form-select-sm w-auto" onchange="changePageSize()">
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

    <!-- Desktop Card-Row Table View -->
    <div class="d-none d-md-block mb-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="booksCardTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 40px;" class="rounded-start">#</th>
                        <th style="width: 60px;">Year</th>
                        <th style="width: 90px;">Date</th>
                        <th style="width: 250px;">ITGK Code & Center Name</th>
                        <th style="width: 140px;">Course & Medium</th>
                        <th style="width: 90px;">Txn Type</th>
                        <th style="width: 60px;" class="text-center">Qty</th>
                        <th style="width: 60px;" class="text-center">Balance</th>
                        <th style="width: 180px;">Receiver Details</th>
                        <th style="width: 130px;">Issuer</th>
                        <th style="width: 110px;" class="text-end rounded-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Rendered dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Native Mobile Card Grid (Visible on Mobile Only) -->
    <div class="d-block d-md-none mb-3">
        <div class="row g-2" id="mobileCardContainer">
            <!-- Rendered dynamically via JS -->
        </div>
    </div>

    <!-- Advanced Pagination & Record Counter Controls -->
    <div class="card border-0 shadow-sm py-2 px-3 d-flex flex-row justify-content-between align-items-center mb-4">
        <small class="text-muted" id="pageSummary">Showing 0 of 0 records</small>
        <nav>
            <ul class="pagination pagination-sm m-0" id="pagination"></ul>
        </nav>
    </div>
</div>

<!-- Offcanvas Form: New Book Transaction (Tightened Premium & Dense UI) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="issueBookOffcanvas" aria-labelledby="issueBookOffcanvasLabel" style="width: 480px;">
    <div class="offcanvas-header bg-primary text-white py-2 px-3">
        <h6 class="offcanvas-title fw-bold mb-0" id="issueBookOffcanvasLabel">
            <i class="fas fa-book me-1"></i> New Book Transaction
        </h6>
        <button type="button" class="btn-close btn-close-white btn-close-sm" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
        <form id="issueForm" onsubmit="handleFormSubmit(event)">
            <div id="items-container"></div>
            
            <div class="mb-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="addCourseItem()" style="font-size: 12px;">
                    <i class="fas fa-plus me-1"></i> Add Another Course
                </button>
            </div>

            <!-- ITGK Details Section -->
            <div class="text-xs fw-bold text-uppercase text-secondary mb-2 pb-1 border-bottom">
                <i class="fas fa-building me-1"></i> ITGK Details
            </div>

            <div class="mb-2">
                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">ITGK Code <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light py-1 px-2"><i class="fas fa-search" style="font-size: 11px;"></i></span>
                    <input type="text" class="form-control form-control-sm py-1 px-2" name="ITGK CODE" list="itgkCodeList" placeholder="Enter or search ITGK Code..." required autocomplete="off">
                </div>
                <datalist id="itgkCodeList">
                    <?php foreach ($itgkList ?? [] as $itgk): ?>
                        <option value="<?= htmlspecialchars($itgk['code']) ?>"><?= htmlspecialchars($itgk['name']) ?></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="row g-2 mb-2">
                <div class="col-7">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">ITGK Name</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2 bg-light" name="NAME" placeholder="Auto-filled" readonly>
                </div>
                <div class="col-5">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">ITGK Mobile</label>
                    <input type="tel" class="form-control form-control-sm py-1 px-2 bg-light" name="ITGK Mobile" placeholder="Auto-filled" readonly>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">ITGK Email</label>
                <input type="email" class="form-control form-control-sm py-1 px-2 bg-light" name="ITGK Email" placeholder="Auto-filled from Code" readonly>
            </div>

            <!-- Receiver Section -->
            <div class="text-xs fw-bold text-uppercase text-secondary mt-3 mb-2 pb-1 border-bottom">
                <i class="fas fa-user-check me-1"></i> Collector / Receiver Details
            </div>
            
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Receiver Name</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" name="Receiver Name" placeholder="Person collecting books">
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Mobile No.</label>
                    <input type="tel" class="form-control form-control-sm py-1 px-2" name="Receiver Mobile No." placeholder="Mobile Number">
                </div>
            </div>
            
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Email ID</label>
                    <input type="email" class="form-control form-control-sm py-1 px-2" name="Email ID" placeholder="itgk@example.com">
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Remark</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" name="Remark" placeholder="Remark / Note">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Document Link</label>
                <input type="text" class="form-control form-control-sm py-1 px-2" name="Merged Document link" placeholder="Merged document URL">
            </div>

            <!-- Issuer Section -->
            <div class="text-xs fw-bold text-uppercase text-secondary mt-3 mb-2 pb-1 border-bottom">
                <i class="fas fa-user me-1"></i> Issuer Details (Logged-in User)
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Issued By (Name)</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2 bg-light" name="issuer_name" id="issuerName" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Issuer Mobile</label>
                    <input type="tel" class="form-control form-control-sm py-1 px-2 bg-light" name="issuer_mobile" id="issuerMobile" readonly>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Issuer Office</label>
                <input type="text" class="form-control form-control-sm py-1 px-2 bg-light" name="issuer_office" id="issuerOffice" readonly>
            </div>

            <div class="form-check form-switch mt-2 mb-2">
                <input class="form-check-input" type="checkbox" id="sendEmailNotify" name="sendEmailNotify" checked>
                <label class="form-check-label text-muted" style="font-size: 11px;" for="sendEmailNotify">Send Email Receipt Automatically to ITGK</label>
            </div>

            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary btn-sm fw-bold py-2" id="submitBtn">
                    <i class="fas fa-save me-1"></i> Save Book Transaction
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Offcanvas Form: Edit Book Transaction (Tightened Premium & Dense UI) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="editBookOffcanvas" aria-labelledby="editBookOffcanvasLabel" style="width: 480px;">
    <div class="offcanvas-header bg-warning py-2 px-3">
        <h6 class="offcanvas-title fw-bold text-dark mb-0" id="editBookOffcanvasLabel">
            <i class="fas fa-edit me-1"></i> Edit Book Transaction
        </h6>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
        <form id="editBookForm" onsubmit="handleEditSubmit(event)">
            <input type="hidden" id="editSheetRow" name="sheet_row">

            <!-- Transaction Details Section -->
            <div class="text-xs fw-bold text-uppercase text-secondary mb-2 pb-1 border-bottom">
                <i class="fas fa-file-invoice me-1"></i> Transaction Info
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Year</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" id="editYear" name="year" required>
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Issue Date</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" id="editIssueDate" name="issue_date" placeholder="YYYY-MM-DD" required>
                </div>
            </div>

            <!-- ITGK & Course Section -->
            <div class="row g-2 mb-2">
                <div class="col-5">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">ITGK Code</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2 font-monospace fw-bold" id="editItgkCode" name="itgk_code" required>
                </div>
                <div class="col-7">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">ITGK Center Name</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" id="editItgkName" name="itgk_name" required>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Course</label>
                    <select class="form-select form-select-sm py-1 px-2" id="editCourseName" name="course_name">
                        <option value="RS-CIT">RS-CIT</option>
                        <option value="RS-CFA">RS-CFA</option>
                        <option value="RS-CSEP">RS-CSEP</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Medium</label>
                    <select class="form-select form-select-sm py-1 px-2" id="editMedium" name="medium">
                        <option value="Hindi">Hindi</option>
                        <option value="English">English</option>
                    </select>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Transaction Type</label>
                    <select class="form-select form-select-sm py-1 px-2" id="editTxnType" name="txn_type">
                        <option value="Issued">Issued</option>
                        <option value="Received">Received</option>
                        <option value="Transfered">Transfered</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Quantity (Issued Book)</label>
                    <input type="number" class="form-control form-control-sm py-1 px-2 fw-bold text-success" id="editQuantity" name="quantity" min="1" required>
                </div>
            </div>

            <!-- Receiver & Issuer Section -->
            <div class="text-xs fw-bold text-uppercase text-secondary mt-3 mb-2 pb-1 border-bottom">
                <i class="fas fa-user-check me-1"></i> Receiver & Document Details
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Receiver Name</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" id="editReceiverName" name="receiver_name">
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Receiver Mobile</label>
                    <input type="tel" class="form-control form-control-sm py-1 px-2" id="editReceiverMobile" name="receiver_mobile">
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Email ID</label>
                    <input type="email" class="form-control form-control-sm py-1 px-2" id="editEmail" name="email">
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Issued From</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" id="editIssuedFrom" name="issued_from">
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Issuer Name</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" id="editIssuerName" name="issuer_name">
                </div>
                <div class="col-6">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Remark</label>
                    <input type="text" class="form-control form-control-sm py-1 px-2" id="editRemark" name="remark">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Document Link</label>
                <input type="text" class="form-control form-control-sm py-1 px-2" id="editDocLink" name="doc_link">
            </div>

            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-warning btn-sm fw-bold py-2" id="editSubmitBtn">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Soft Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Soft Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-trash-alt text-danger mb-3" style="font-size: 3rem;"></i>
                <h5 class="fw-bold">Soft Delete this Book Issue record?</h5>
                <p class="text-muted mb-0" id="deleteTargetSummary">Row will be marked as deleted (is_deleted = YES) in Google Sheet without removing history.</p>
            </div>
            <div class="modal-footer border-top-0 justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4 fw-bold" id="btnConfirmDelete" onclick="executeDelete()">
                    <i class="fas fa-trash me-1"></i> Delete Record
                </button>
            </div>
        </div>
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
                <button type="button" class="btn btn-primary btn-sm" onclick="printReceipt()">
                    <i class="fas fa-print me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const allBooks = <?= json_encode($books ?? []) ?>;
const itgkDataMap = <?= json_encode($itgkList ?? []) ?>;

// Stamp a stable numeric index on every book for receipt lookup
allBooks.forEach((b, i) => { b._idx = i; });

let currentPage = 1;
let pageSize = 25;
let filteredBooks = [...allBooks];
let rowToDelete = null;

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

    const toastHtml = `
        <div class="toast align-items-center text-white ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${icon} me-2"></i>${escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = toastHtml;
    const toastEl = wrapper.firstElementChild;
    container.appendChild(toastEl);

    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();

    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function triggerFilter() {
    const search = document.getElementById('searchInput').value.trim().toLowerCase();
    const typeFilter = document.getElementById('txnTypeFilter').value.trim().toLowerCase();
    const yearFilter = document.getElementById('filterYear').value.trim().toLowerCase();
    const codeFilter = document.getElementById('filterCode').value.trim().toLowerCase();

    // Dynamically update URL query string without page reload
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (typeFilter) params.set('type', typeFilter);
    if (yearFilter) params.set('year', yearFilter);
    if (codeFilter) params.set('code', codeFilter);

    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({ path: newUrl }, '', newUrl);

    filteredBooks = allBooks.filter(b => {
        if (typeFilter && !(b.txn_type || '').toLowerCase().includes(typeFilter)) {
            return false;
        }
        if (yearFilter && !(b.year || '').toLowerCase().includes(yearFilter)) {
            return false;
        }
        if (codeFilter && !(b.itgk_code || '').toLowerCase().includes(codeFilter)) {
            return false;
        }
        if (search) {
            const searchStr = `${b.itgk_code} ${b.itgk_name} ${b.receiver_name} ${b.receiver_mobile} ${b.course_name} ${b.issuer_name}`.toLowerCase();
            if (!searchStr.includes(search)) {
                return false;
            }
        }
        return true;
    });

    currentPage = 1;
    renderViews();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('txnTypeFilter').value = '';
    document.getElementById('filterYear').value = '';
    document.getElementById('filterCode').value = '';

    window.history.replaceState({ path: window.location.pathname }, '', window.location.pathname);

    filteredBooks = [...allBooks];
    currentPage = 1;
    renderViews();
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pageSize').value) || 25;
    currentPage = 1;
    renderViews();
}

function renderViews() {
    renderDesktopTable();
    renderMobileCards();
    renderPagination();
}

function renderDesktopTable() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (filteredBooks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-muted bg-white rounded">No matching Book Issue records found.</td></tr>';
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
            <td><span class="badge bg-light text-dark border">${escapeHtml(b.year || '-')}</span></td>
            <td>${escapeHtml(b.issue_date || '-')}</td>
            <td>
                <span class="badge bg-secondary font-monospace me-1">${escapeHtml(b.itgk_code || '-')}</span>
                <span class="fw-bold text-dark">${escapeHtml(b.itgk_name || ('ITGK ' + b.itgk_code))}</span>
            </td>
            <td>
                <span class="badge bg-info text-dark me-1">${escapeHtml(b.course_name || 'RS-CIT')}</span>
                <span class="badge bg-light text-dark border">${escapeHtml(b.medium || 'Hindi')}</span>
            </td>
            <td><span class="badge ${badgeClass}">${escapeHtml(b.txn_type || 'Issued')}</span></td>
            <td class="text-center fw-bold text-success fs-6">${b.quantity || 0}</td>
            <td class="text-center fw-bold text-secondary">${b.balance || 0}</td>
            <td>
                <div class="fw-semibold text-dark">${escapeHtml(b.receiver_name || '-')}</div>
                ${b.receiver_mobile ? `<div class="small text-muted"><i class="fas fa-phone me-1"></i>${escapeHtml(b.receiver_mobile)}</div>` : ''}
                ${b.email ? `<div class="small text-muted"><i class="fas fa-envelope me-1"></i>${escapeHtml(b.email)}</div>` : ''}
            </td>
            <td>
                <span class="small fw-semibold text-secondary">${escapeHtml(b.issuer_name || '-')}</span>
                ${b.issued_from ? `<div class="small text-muted">${escapeHtml(b.issued_from)}</div>` : ''}
            </td>
            <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= BASE_URL ?>books/acknowledgement?sheet_row=${b.sheet_row}" target="_blank" class="btn btn-outline-primary py-0 px-2" title="View / Print Acknowledgement Receipt">
                        <i class="fas fa-print"></i>
                    </a>
                    <button type="button" class="btn btn-outline-warning py-0 px-2" title="Edit Record" onclick='openEditOffcanvas(${JSON.stringify(b)})'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger py-0 px-2" title="Delete Record" onclick='confirmDelete(${b.sheet_row})'>
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderMobileCards() {
    const container = document.getElementById('mobileCardContainer');
    container.innerHTML = '';

    if (filteredBooks.length === 0) {
        container.innerHTML = '<div class="col-12"><div class="card border-0 shadow-sm p-4 text-center text-muted">No matching Book Issue records found.</div></div>';
        return;
    }

    const start = (currentPage - 1) * pageSize;
    const end = pageSize === 1000 ? filteredBooks.length : Math.min(start + pageSize, filteredBooks.length);
    const pageItems = filteredBooks.slice(start, end);

    pageItems.forEach(b => {
        const t = (b.txn_type || '').toLowerCase();
        let badgeClass = 'bg-primary';
        if (t.includes('issue')) badgeClass = 'bg-success';
        else if (t.includes('transfer')) badgeClass = 'bg-warning text-dark';
        else if (t.includes('receive')) badgeClass = 'bg-info text-dark';

        const cardDiv = document.createElement('div');
        cardDiv.className = 'col-12';
        cardDiv.innerHTML = `
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-secondary font-monospace me-1">${escapeHtml(b.itgk_code)}</span>
                            <span class="fw-bold text-dark fs-6">${escapeHtml(b.itgk_name)}</span>
                        </div>
                        <div><span class="badge ${badgeClass}">${escapeHtml(b.txn_type || 'Issued')}</span></div>
                    </div>
                    <div class="small text-muted mb-2">
                        <span class="badge bg-info text-dark me-1">${escapeHtml(b.course_name)}</span>
                        <span class="badge bg-light text-dark border me-1">${escapeHtml(b.medium)}</span>
                        <span class="badge bg-light text-dark border">Qty: ${b.quantity}</span>
                    </div>
                    <hr class="my-2 text-muted opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small">
                            <div class="text-dark fw-semibold"><i class="fas fa-user me-1 text-secondary"></i>${escapeHtml(b.receiver_name || '-')}</div>
                            ${b.receiver_mobile ? `<div class="text-muted font-monospace"><i class="fas fa-phone me-1 text-secondary"></i>${escapeHtml(b.receiver_mobile)}</div>` : ''}
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="<?= BASE_URL ?>books/acknowledgement?sheet_row=${b.sheet_row}" target="_blank" class="btn btn-outline-primary" title="View / Print Acknowledgement Receipt">
                                <i class="fas fa-print"></i>
                            </a>
                            <button type="button" class="btn btn-outline-warning" title="Edit Record" onclick='openEditOffcanvas(${JSON.stringify(b)})'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" title="Delete Record" onclick='confirmDelete(${b.sheet_row})'>
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(cardDiv);
    });
}

function renderPagination() {
    const totalItems = filteredBooks.length;
    const totalPages = Math.ceil(totalItems / pageSize);
    const summary = document.getElementById('pageSummary');
    const pagination = document.getElementById('pagination');

    pagination.innerHTML = '';

    if (totalItems === 0) {
        summary.innerText = 'Showing 0 of 0 records';
        return;
    }

    const start = (currentPage - 1) * pageSize + 1;
    const end = pageSize === 1000 ? totalItems : Math.min(currentPage * pageSize, totalItems);
    summary.innerText = `Showing ${start} to ${end} of ${totalItems} records`;

    if (totalPages <= 1) return;

    // Previous Button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="gotoPage(${currentPage - 1}); return false;">Prev</a>`;
    pagination.appendChild(prevLi);

    // Page Numbers (limited to 5 visible)
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
    renderViews();
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

let itemIndex = 0;
function addCourseItem() {
    itemIndex++;
    const container = document.getElementById('items-container');
    const html = `
        <div class="item-row border rounded p-2 mb-2 bg-light position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-1 remove-item-btn btn-close-sm" onclick="this.parentElement.remove()" style="font-size: 10px;"></button>
            
            <div class="mb-2 pe-3">
                <label class="form-label text-secondary fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">TRANSACTION TYPE</label>
                <div class="row g-1">
                    <div class="col-4">
                        <input type="radio" class="btn-check type-received" name="type_${itemIndex}" id="type-received-${itemIndex}" value="Received">
                        <label class="btn btn-outline-info btn-sm w-100 py-0 px-1" for="type-received-${itemIndex}" style="font-size: 11px;">Received</label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check type-issued" name="type_${itemIndex}" id="type-issued-${itemIndex}" value="Issued" checked>
                        <label class="btn btn-outline-success btn-sm w-100 py-0 px-1" for="type-issued-${itemIndex}" style="font-size: 11px;">Issued</label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check type-transfer" name="type_${itemIndex}" id="type-transfer-${itemIndex}" value="Transfered">
                        <label class="btn btn-outline-warning btn-sm w-100 py-0 px-1" for="type-transfer-${itemIndex}" style="font-size: 11px;">Transfered</label>
                    </div>
                </div>
            </div>

            <div class="row g-1">
                <div class="col-5">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Course</label>
                    <select class="form-select form-select-sm py-1 px-2 item-course">
                         <option value="RS-CIT">RS-CIT</option>
                         <option value="RS-CFA">RS-CFA</option>
                         <option value="RS-CSEP">RS-CSEP</option>
                    </select>
                </div>
                <div class="col-4">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Medium</label>
                    <select class="form-select form-select-sm py-1 px-2 item-medium">
                         <option value="Hindi">Hindi</option>
                         <option value="English">English</option>
                    </select>
                </div>
                <div class="col-3">
                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 11px;">Qty</label>
                    <input type="number" class="form-control form-control-sm py-1 px-2 item-qty fw-bold text-success" value="1" min="1">
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

function openEditOffcanvas(b) {
    document.getElementById('editSheetRow').value = b.sheet_row || '';
    document.getElementById('editYear').value = b.year || '';
    document.getElementById('editIssueDate').value = b.issue_date || '';
    document.getElementById('editItgkCode').value = b.itgk_code || '';
    document.getElementById('editItgkName').value = b.itgk_name || '';
    document.getElementById('editCourseName').value = b.course_name || 'RS-CIT';
    document.getElementById('editMedium').value = b.medium || 'Hindi';
    document.getElementById('editTxnType').value = b.txn_type || 'Issued';
    document.getElementById('editQuantity').value = b.quantity || 1;
    document.getElementById('editReceiverName').value = b.receiver_name || '';
    document.getElementById('editReceiverMobile').value = b.receiver_mobile || '';
    document.getElementById('editEmail').value = b.email || '';
    document.getElementById('editRemark').value = b.remark || '';
    document.getElementById('editDocLink').value = b.doc_link || '';
    document.getElementById('editIssuedFrom').value = b.issued_from || '';
    document.getElementById('editIssuerName').value = b.issuer_name || '';

    const offcanvas = new bootstrap.Offcanvas(document.getElementById('editBookOffcanvas'));
    offcanvas.show();
}

async function handleEditSubmit(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Updating...';

    const form = e.target;
    const formData = new FormData(form);
    const payload = {};
    formData.forEach((value, key) => payload[key] = value);

    try {
        const response = await fetch('<?= BASE_URL ?>books/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            const editOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('editBookOffcanvas'));
            if (editOffcanvas) editOffcanvas.hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Error: ' + result.message, 'danger');
        }
    } catch (err) {
        showToast('Failed to update record: ' + err.message, 'danger');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Update Record';
    }
}

function confirmDelete(sheetRow) {
    rowToDelete = sheetRow;
    document.getElementById('deleteTargetSummary').innerText = `Row #${sheetRow} will be marked as deleted (is_deleted = YES) in Google Sheet.`;
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

async function executeDelete() {
    if (!rowToDelete) return;

    const btn = document.getElementById('btnConfirmDelete');
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Deleting...';

    try {
        const response = await fetch('<?= BASE_URL ?>books/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sheet_row: rowToDelete })
        });
        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
            if (modal) modal.hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Error: ' + result.message, 'danger');
        }
    } catch (err) {
        showToast('Failed to delete record: ' + err.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash me-1"></i> Delete Record';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Read initial URL params if present, set inputs and filter instantly
    const params = new URLSearchParams(window.location.search);

    if (params.has('search')) document.getElementById('searchInput').value = params.get('search');
    if (params.has('type')) document.getElementById('txnTypeFilter').value = params.get('type');
    if (params.has('year')) document.getElementById('filterYear').value = params.get('year');
    if (params.has('code')) document.getElementById('filterCode').value = params.get('code');

    if (params.has('search') || params.has('type') || params.has('year') || params.has('code')) {
        triggerFilter();
    } else {
        renderViews();
    }

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
            showToast(result.message, 'success');
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('issueBookOffcanvas'));
            if (offcanvas) offcanvas.hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Error: ' + result.message, 'danger');
        }
    } catch (err) {
        showToast('Failed to save transaction: ' + err.message, 'danger');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save Book Transaction';
    }
}

// Store reference to book currently open in receipt modal
let _receiptBook = null;

// Called from table/card Print buttons via stable index stamped at page load
function openReceiptById(idx) {
    const b = allBooks[idx];
    if (!b) { console.warn('openReceiptById: no book at index', idx); return; }
    openReceipt(b);
}

function openReceipt(b) {
    _receiptBook = b;
    document.getElementById('rct-itgk-name').innerText = b.itgk_name || ('ITGK ' + b.itgk_code) || '-';
    document.getElementById('rct-itgk-code').innerText = b.itgk_code || '-';
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

function printReceipt() {
    const receiptEl = document.getElementById('receiptContent');
    if (!receiptEl) return;

    // Collect all linked stylesheets from the current page
    const styleLinks = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
        .map(l => `<link rel="stylesheet" href="${l.href}">`)
        .join('\n');

    const receiptHtml = receiptEl.innerHTML;

    const printWin = window.open('', '_blank', 'width=800,height=700,scrollbars=yes');
    printWin.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Issue Receipt</title>
    ${styleLinks}
    <style>
        body { background: #fff !important; margin: 0; padding: 16px; font-family: sans-serif; color: #000; }
        @media print {
            body { margin: 0; padding: 0; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>
    ${receiptHtml}
    <script>
        window.onload = function() {
            window.focus();
            window.print();
            setTimeout(function() { window.close(); }, 800);
        };
    <\/script>
</body>
</html>`);
    printWin.document.close();
}
</script>
