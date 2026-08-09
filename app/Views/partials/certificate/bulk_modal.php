<!-- ============================================================ -->
<!-- BULK ISSUE OFFCANVAS FORM                                     -->
<!-- ============================================================ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="bulkIssueOffcanvas" aria-labelledby="bulkIssueOffcanvasLabel"
    style="width:520px;">
    <div class="offcanvas-header py-2 px-3 text-white" style="background:linear-gradient(135deg,#1a56db,#0e9f6e);">
        <h6 class="offcanvas-title fw-bold mb-0" id="bulkIssueOffcanvasLabel">
            <i class="fas fa-paper-plane me-1"></i>Issue Selected Certificate(s)
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

            <!-- SECTION 1: ITGK Details -->
            <div class="fw-semibold small text-primary mb-1">
                <i class="fas fa-university me-1"></i>Section 1: ITGK Details
            </div>
            <div class="card border-0 bg-primary bg-opacity-10 mb-2">
                <div class="card-body py-2 px-3">
                    <div class="row g-2 small">
                        <div class="col-6">
                            <span class="text-muted d-block">ITGK Code:</span>
                            <div class="fw-bold text-dark" id="bi_itgk_codes">--</div>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">District (Certificate Sheet):</span>
                            <input type="text" name="district" id="bi_district_input" class="form-control form-control-sm bg-white fw-bold" placeholder="e.g. Jodhpur">
                        </div>
                        <div class="col-12">
                            <span class="text-muted d-block">ITGK Name (Master Sheet):</span>
                            <div class="fw-bold text-dark" id="bi_itgk_name">--</div>
                        </div>
                        <div class="col-12">
                            <span class="text-muted d-block">ITGK Address (Master Sheet):</span>
                            <div class="text-secondary small fw-medium" id="bi_itgk_address">--</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Selected Certificates (Child Table with Editable Packet & Ranges) -->
            <div class="mb-2">
                <div class="fw-semibold small text-primary mb-1 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-1"></i>Section 2: Selected Certificates (<span id="bi_sel_count">0</span>)</span>
                    <span class="text-muted style-italic" style="font-size:10px;">Editable: Packet No, Cert From & To</span>
                </div>
                <div style="max-height:220px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
                    <table class="table table-sm table-hover mb-0" style="font-size:11px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width:30px;">#</th>
                                <th>Course / Exam</th>
                                <th style="width:90px;">Packet No.</th>
                                <th style="width:90px;">Cert From</th>
                                <th style="width:90px;">Cert To</th>
                                <th style="width:45px;" class="text-center">Pass</th>
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

            <!-- SECTION 3: Receiver Details (ITGK Representative) -->
            <div class="fw-semibold small text-success mb-1">
                <i class="fas fa-user-check me-1"></i>Section 3: Receiver Details (ITGK Representative)
            </div>
            <div class="row g-1.5 mb-2">
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Receiver Name <span class="text-danger">*</span></label>
                    <input type="text" name="receiver_name" id="bi_receiver_name" class="form-control form-control-sm"
                        placeholder="e.g. Ramesh Kumar" required>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Designation <span class="text-danger">*</span></label>
                    <select name="receiver_designation" id="bi_receiver_desig" class="form-select form-select-sm" required>
                        <option value="">-- Select Designation --</option>
                        <option value="PROPRIETOR">PROPRIETOR</option>
                        <option value="FACULTY">FACULTY</option>
                        <option value="COORDINATOR">COORDINATOR</option>
                        <option value="COUNSLLER">COUNSLLER</option>
                        <option value="BROTHER">BROTHER</option>
                        <option value="FRIEND">FRIEND</option>
                        <option value="OTHER">OTHER</option>
                    </select>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Mobile</label>
                    <input type="text" name="receiver_mobile" id="bi_receiver_mob" class="form-control form-control-sm"
                        placeholder="98XXXXXXXX">
                </div>
                <div class="col-12 mb-1">
                    <label class="form-label fw-semibold small mb-0">Email <span class="text-muted small">(Optional)</span></label>
                    <input type="email" name="receiver_email" id="bi_receiver_email"
                        class="form-control form-control-sm" placeholder="itgk@example.com">
                </div>
            </div>

            <!-- SECTION 4: Issuer Details (Office Representative) -->
            <?php 
                $currentUser  = \App\Services\AuthService::user();
                $isSuperAdmin = \App\Services\AuthService::isSuperAdmin();
            ?>
            <div class="fw-semibold small text-info mb-1 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-tie me-1"></i>Section 4: Issuer Details (Office Representative)</span>
                <?php if ($isSuperAdmin): ?>
                    <span class="badge bg-warning text-dark" style="font-size:9px;">SUPERADMIN Mode</span>
                <?php endif; ?>
            </div>
            <div class="row g-1.5 mb-2">
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Issuer Name</label>
                    <input type="text" name="issuer_name" id="bi_issuer_name" class="form-control form-control-sm <?= !$isSuperAdmin ? 'bg-light' : '' ?>"
                        value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>"
                        <?= !$isSuperAdmin ? 'readonly' : '' ?>
                        placeholder="Your Name">
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Issuer Designation</label>
                    <?php if ($isSuperAdmin): ?>
                        <input type="text" name="issuer_designation" id="bi_issuer_desig" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($currentUser['role'] ?? $currentUser['designation'] ?? 'OFFICER') ?>"
                            placeholder="Your Designation">
                    <?php else: ?>
                        <input type="text" name="issuer_designation" id="bi_issuer_desig" class="form-control form-control-sm bg-light"
                            value="<?= htmlspecialchars($currentUser['role'] ?? $currentUser['designation'] ?? 'OFFICER') ?>"
                            readonly>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-1">
                    <label class="form-label fw-semibold small mb-0">Issuer Mobile</label>
                    <input type="text" name="issuer_mobile" id="bi_issuer_mobile" class="form-control form-control-sm <?= !$isSuperAdmin ? 'bg-light' : '' ?>"
                        value="<?= htmlspecialchars((string) ($currentUser['mobile'] ?? '')) ?>"
                        <?= !$isSuperAdmin ? 'readonly' : '' ?>
                        placeholder="Mobile Number">
                </div>
            </div>

            <!-- Handover Remark -->
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

<script>
(function () {
    // ITGK master lookup map (code -> {name, district, email, mobile})
    var itgkMap = {};
    <?php foreach (($itgkList ?? []) as $itgk): ?>
        itgkMap[<?= json_encode((string) $itgk['code']) ?>] = {
            name: <?= json_encode((string) $itgk['name']) ?>,
            district: <?= json_encode((string) $itgk['district']) ?>,
            address: <?= json_encode((string) ($itgk['address'] ?? '')) ?>,
            email: <?= json_encode((string) ($itgk['email'] ?? '')) ?>,
            mobile: <?= json_encode((string) ($itgk['mobile'] ?? '')) ?>
        };
    <?php endforeach; ?>

    var selectedCerts = {}; // key = sheetRow (int), value = cert data object

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
            var codes = [...new Set(keys.map(function (k) {
                return selectedCerts[k].itgk;
            }))];
            itgkEl.textContent = codes.join(', ') || '--';
        }
    }

    function bindCheckboxes() {
        document.querySelectorAll('.cert-select-chk').forEach(function (chk) {
            var sr = parseInt(chk.dataset.sheetRow, 10);
            if (sr && selectedCerts[sr]) {
                chk.checked = true;
            }

            var fresh = chk.cloneNode(true);
            fresh.checked = chk.checked;
            if (chk.parentNode) {
                chk.parentNode.replaceChild(fresh, chk);
            }

            fresh.addEventListener('change', function () {
                var sheetRow = parseInt(this.dataset.sheetRow, 10);
                if (!sheetRow) return;
                if (this.checked) {
                    selectedCerts[sheetRow] = {
                        sheetRow: sheetRow,
                        id: this.dataset.id || '',
                        itgk: this.dataset.itgk || '',
                        district: this.dataset.district || '',
                        course: this.dataset.course || '',
                        exam: this.dataset.exam || '',
                        packet: this.dataset.packet || '',
                        certFrom: this.dataset.certfrom || '',
                        certTo: this.dataset.certto || '',
                        total: this.dataset.total || ''
                    };
                } else {
                    delete selectedCerts[sheetRow];
                }
                updateActionBar();
                syncSelectAllState();
            });
        });

        syncSelectAllState();
    }

    function syncSelectAllState() {
        var selAll = document.getElementById('chkSelectAll');
        if (!selAll) return;
        var visChks = Array.from(document.querySelectorAll('.cert-select-chk')).filter(function (c) {
            var r = c.closest('.cert-main-row, .cert-mobile-card');
            return r && getComputedStyle(r).display !== 'none' && !c.disabled;
        });
        if (visChks.length === 0) {
            selAll.indeterminate = false;
            selAll.checked = false;
            return;
        }
        var allChecked = visChks.every(function (c) { return c.checked; });
        var someChecked = visChks.some(function (c) { return c.checked; });
        selAll.checked = allChecked;
        selAll.indeterminate = someChecked && !allChecked;
    }

    var selAllChk = document.getElementById('chkSelectAll');
    if (selAllChk) {
        selAllChk.addEventListener('change', function () {
            var visChks = Array.from(document.querySelectorAll('.cert-select-chk')).filter(function (c) {
                var r = c.closest('.cert-main-row, .cert-mobile-card');
                return r && getComputedStyle(r).display !== 'none' && !c.disabled;
            });
            visChks.forEach(function (chk) {
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
                        certFrom: chk.dataset.certfrom || '',
                        certTo: chk.dataset.certto || '',
                        total: chk.dataset.total || ''
                    };
                } else {
                    delete selectedCerts[sheetRow];
                }
            });
            updateActionBar();
        });
    }

    document.getElementById('btnClearSel')?.addEventListener('click', function () {
        selectedCerts = {};
        document.querySelectorAll('.cert-select-chk').forEach(function (c) {
            c.checked = false;
        });
        if (selAllChk) {
            selAllChk.checked = false;
            selAllChk.indeterminate = false;
        }
        updateActionBar();
    });

    document.getElementById('bulkIssueOffcanvas')?.addEventListener('show.bs.offcanvas', function () {
        var _flds = ['bi_receiver_name', 'bi_receiver_desig', 'bi_receiver_mob', 'bi_receiver_email'];
        _flds.forEach(function (id) { var el = document.getElementById(id); if (el) el.value = ''; });
        var keys = Object.keys(selectedCerts);
        var certs = keys.map(function (k) { return selectedCerts[k]; });

        var countEl = document.getElementById('bi_sel_count');
        if (countEl) countEl.textContent = certs.length;

        var codes = [...new Set(certs.map(function (c) { return c.itgk; }))];
        var dists = [...new Set(certs.map(function (c) { return c.district; }))];
        var names = codes.map(function (cd) { return itgkMap[cd] ? itgkMap[cd].name : cd; });

        var codesEl = document.getElementById('bi_itgk_codes');
        var distInput = document.getElementById('bi_district_input');
        var nameEl = document.getElementById('bi_itgk_name');
        var addrEl = document.getElementById('bi_itgk_address');

        if (codesEl) codesEl.textContent = codes.join(', ') || '--';
        if (distInput) distInput.value = dists.join(', ') || '';
        if (nameEl) nameEl.textContent = names.join(', ') || '--';

        if (codes.length === 1 && itgkMap[codes[0]]) {
            var itgkData = itgkMap[codes[0]];
            if (addrEl) addrEl.textContent = itgkData.address || 'N/A';

            var rNameEl = document.getElementById('bi_receiver_name');
            var desigEl = document.getElementById('bi_receiver_desig');
            var mobEl   = document.getElementById('bi_receiver_mob');
            var emailEl = document.getElementById('bi_receiver_email');
            if (rNameEl && !rNameEl.value) rNameEl.value = itgkData.name || '';
            if (desigEl && !desigEl.value) desigEl.value = 'COORDINATOR';
            if (mobEl && !mobEl.value)     mobEl.value   = itgkData.mobile || '';
            if (emailEl && !emailEl.value) emailEl.value = itgkData.email || '';
        } else {
            if (addrEl) addrEl.textContent = '--';
        }

        var itgkWarn = document.getElementById('bi_itgk_warn');
        var warnText = document.getElementById('bi_itgk_warn_text');
        var submitBtn = document.getElementById('btnBulkIssueSubmit');
        if (codes.length > 1) {
            if (itgkWarn) itgkWarn.style.display = '';
            if (warnText) warnText.textContent = '[!] Mixed ITGK codes selected -- issue allows only ONE ITGK code per transaction.';
            if (submitBtn) submitBtn.disabled = true;
        } else {
            if (itgkWarn) itgkWarn.style.display = 'none';
            if (submitBtn) submitBtn.disabled = false;
        }

        var tbody = document.getElementById('bi_certs_table_body');
        if (tbody) {
            if (certs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">No certificates selected</td></tr>';
            } else {
                tbody.innerHTML = certs.map(function (c, i) {
                    return '<tr>' +
                        '<td class="fw-bold text-muted">' + (i + 1) + '</td>' +
                        '<td><div class="fw-bold text-dark" style="font-size:10px;">' + (c.course || '--') + '</div><div class="text-muted" style="font-size:9.5px;">' + (c.exam || '--') + '</div></td>' +
                        '<td><input type="text" class="form-control form-control-sm py-0 px-1 bi-edit-packet" data-sr="' + c.sheetRow + '" value="' + (c.packet || '') + '" style="font-size:10.5px;"></td>' +
                        '<td><input type="text" class="form-control form-control-sm py-0 px-1 bi-edit-certfrom" data-sr="' + c.sheetRow + '" value="' + (c.certFrom || '') + '" style="font-size:10.5px;"></td>' +
                        '<td><input type="text" class="form-control form-control-sm py-0 px-1 bi-edit-certto" data-sr="' + c.sheetRow + '" value="' + (c.certTo || '') + '" style="font-size:10.5px;"></td>' +
                        '<td class="text-center fw-bold text-success">' + (c.total || '--') + '</td>' +
                        '</tr>';
                }).join('');

                // Attach event listeners for real-time section 2 input editing
                tbody.querySelectorAll('.bi-edit-packet').forEach(function(inp) {
                    inp.addEventListener('input', function() {
                        var sr = this.dataset.sr;
                        if (selectedCerts[sr]) selectedCerts[sr].packet = this.value;
                    });
                });
                tbody.querySelectorAll('.bi-edit-certfrom').forEach(function(inp) {
                    inp.addEventListener('input', function() {
                        var sr = this.dataset.sr;
                        if (selectedCerts[sr]) selectedCerts[sr].certFrom = this.value;
                    });
                });
                tbody.querySelectorAll('.bi-edit-certto').forEach(function(inp) {
                    inp.addEventListener('input', function() {
                        var sr = this.dataset.sr;
                        if (selectedCerts[sr]) selectedCerts[sr].certTo = this.value;
                    });
                });
            }
        }
    });

    document.getElementById('bulkIssueForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        var keys = Object.keys(selectedCerts);
        if (keys.length === 0) {
            alert('कोई Certificate select नहीं किया है। पहले Available rows में से select करें।');
            return;
        }
        var btn = document.getElementById('btnBulkIssueSubmit');
        var origLabel = '<i class="fas fa-paper-plane me-1"></i>Issue All Selected Certificates';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Issuing ' + keys.length + ' certificates...';
        }
        if (typeof window.showLoader === 'function') window.showLoader('Issuing ' + keys.length + ' certificate(s) & updating Learner records...');

        var mainDistrict = document.getElementById('bi_district_input')?.value || '';

        var jsonEl = document.getElementById('bi_selections_json');
        if (jsonEl) jsonEl.value = JSON.stringify(keys.map(function (k) {
            var c = selectedCerts[k];
            return {
                sheet_row: c.sheetRow,
                itgk_code: c.itgk,
                course_name: c.course,
                exam_name: c.exam,
                packet_no: c.packet || '',
                cert_no_from: c.certFrom || '',
                cert_no_to: c.certTo || '',
                grand_total: c.total || 0,
                district: mainDistrict || c.district || ''
            };
        }));

        try {
            var baseUrl = window.BASE_URL || '/certificate/';
            var fd = new FormData(this);
            var res = await fetch(baseUrl + 'itgk/issue_batch', {
                method: 'POST',
                body: fd
            });
            var json = await res.json();
            if (json.success) {
                var oc = bootstrap.Offcanvas.getInstance(document.getElementById('bulkIssueOffcanvas'));
                if (oc) oc.hide();

                var issuedIds = json.issued_ids || [];
                if (issuedIds.length > 0) {
                    var ackUrl = baseUrl + 'itgk/acknowledgement?ids=' + issuedIds.join(',');
                    window.open(ackUrl, '_blank');
                }

                alert('Issue Complete!\nCertificates Updated: ' + (json.certs_updated || 0) +
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
            if (typeof showToast === 'function') showToast('Network error: ' + err.message, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origLabel;
            }
        } finally {
            if (typeof window.hideLoader === 'function') window.hideLoader();
        }
    });

    window._bindCertCheckboxes = bindCheckboxes;
    bindCheckboxes();
})();
</script>
