<!-- ============================================================ -->
<!-- ADD ITGK CERTIFICATE -- ENHANCED OFFCANVAS FORM              -->
<!-- ============================================================ -->
<div class="offcanvas offcanvas-end shadow-lg border-0" tabindex="-1" id="addItgkOffcanvas" aria-labelledby="addItgkOffcanvasLabel" style="width: 100%; max-width: 620px;">
    <div class="offcanvas-header bg-gradient bg-primary text-white py-1.5 px-2.5">
        <div class="d-flex align-items-center gap-1.5">
            <div class="rounded-circle bg-white bg-opacity-20 p-1 d-flex align-items-center justify-content-center" style="width:30px; height:30px;">
                <i class="fas fa-certificate text-white small"></i>
            </div>
            <div>
                <h6 class="offcanvas-title fw-bold mb-0" id="addItgkOffcanvasLabel" style="font-size: 13.5px;">Add ITGK Certificate Packet</h6>
                <small class="text-white-50 d-block" style="font-size: 9.5px; line-height: 1.1;">Enter details to record a new certificate packet</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2 bg-light">
        <form method="POST" id="addItgkForm" action="<?= BASE_URL ?>itgk/create">
            <?= \App\Helpers\Csrf::fieldHtml() ?>

            <!-- Section 1: ITGK & Center Information -->
            <div class="card border-0 shadow-sm mb-1.5 rounded-2">
                <div class="card-header bg-white py-1 px-2 border-bottom-0">
                    <span class="fw-bold text-primary" style="font-size: 11px;"><i class="fas fa-building me-1"></i>Center &amp; Location Details</span>
                </div>
                <div class="card-body p-1.5 pt-0">
                    <div class="row g-1">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">ITGK Code &amp; Name <span class="text-danger">*</span></label>
                            <select name="itgk_code" id="add_itgk_code_select" class="form-select form-select-sm" required>
                                <option value="">-- Select ITGK --</option>
                                <?php if (!empty($itgkList)): ?>
                                    <?php foreach ($itgkList as $itgk): ?>
                                        <option value="<?= htmlspecialchars((string) $itgk['code']) ?>"
                                            data-district="<?= htmlspecialchars((string) $itgk['district']) ?>">
                                            <?= htmlspecialchars((string) $itgk['code']) ?> - <?= htmlspecialchars((string) $itgk['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">District</label>
                            <input name="district" id="add_district_input" class="form-control form-control-sm" placeholder="District">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Course & Exam Info -->
            <div class="card border-0 shadow-sm mb-1.5 rounded-2">
                <div class="card-header bg-white py-1 px-2 border-bottom-0">
                    <span class="fw-bold text-primary" style="font-size: 11px;"><i class="fas fa-graduation-cap me-1"></i>Course &amp; Exam Information</span>
                </div>
                <div class="card-body p-1.5 pt-0">
                    <div class="row g-1">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Course Name <span class="text-danger">*</span></label>
                            <input name="course_name" class="form-control form-control-sm" placeholder="e.g. RS-CIT" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Receiving Date <span class="text-danger">*</span></label>
                            <input type="date" name="receiving_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Exam Name <span class="text-danger">*</span></label>
                            <input name="exam_name" class="form-control form-control-sm" placeholder="e.g. General Exam" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Exam Date</label>
                            <input type="date" name="exam_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Packet Numbers & Counts -->
            <div class="card border-0 shadow-sm mb-1.5 rounded-2">
                <div class="card-header bg-white py-1 px-2 border-bottom-0">
                    <span class="fw-bold text-primary" style="font-size: 11px;"><i class="fas fa-layer-group me-1"></i>Packet &amp; Result Counts</span>
                </div>
                <div class="card-body p-1.5 pt-0">
                    <div class="row g-1">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Packet No.</label>
                            <input name="packet_no" class="form-control form-control-sm" placeholder="Packet number">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-success mb-0" style="font-size: 10.5px;"><i class="fas fa-check-circle me-0.5"></i>Pass</label>
                            <input type="number" name="pass" id="add_pass_cnt" class="form-control form-control-sm text-center fw-bold text-success" placeholder="0" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-danger mb-0" style="font-size: 10.5px;"><i class="fas fa-times-circle me-0.5"></i>Fail</label>
                            <input type="number" name="fail" id="add_fail_cnt" class="form-control form-control-sm text-center fw-bold text-danger" placeholder="0" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-warning mb-0" style="font-size: 10.5px;"><i class="fas fa-user-slash me-0.5"></i>Absent</label>
                            <input type="number" name="absent" id="add_absent_cnt" class="form-control form-control-sm text-center fw-bold text-warning" placeholder="0" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;"><i class="fas fa-exclamation-triangle me-0.5"></i>UFM</label>
                            <input type="number" name="ufm" id="add_ufm_cnt" class="form-control form-control-sm text-center fw-bold" placeholder="0" min="0" value="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Certificate Number Range & Location -->
            <div class="card border-0 shadow-sm mb-2 rounded-2">
                <div class="card-header bg-white py-1 px-2 border-bottom-0">
                    <span class="fw-bold text-primary" style="font-size: 11px;"><i class="fas fa-map-marker-alt me-1"></i>Certificate Range, Total &amp; Status</span>
                </div>
                <div class="card-body p-1.5 pt-0">
                    <div class="row g-1">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Cert No. From</label>
                            <input name="cert_no_from" class="form-control form-control-sm" placeholder="Starting cert no.">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Cert No. To</label>
                            <input name="cert_no_to" class="form-control form-control-sm" placeholder="Ending cert no.">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-primary mb-0" style="font-size: 10.5px;">Grand Total</label>
                            <input type="number" name="grand_total" id="add_grand_total" class="form-control form-control-sm text-center fw-bold text-primary" placeholder="0" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Storage Location</label>
                            <select name="current_location" class="form-select form-select-sm">
                                <option value="">-- Select Location --</option>
                                <?php if (!empty($locationOptions)): ?>
                                    <?php foreach ($locationOptions as $loc): ?>
                                        <option value="<?= htmlspecialchars((string) $loc) ?>"><?= htmlspecialchars((string) $loc) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="Available" selected>Available</option>
                                <option value="Not Received">Not Received</option>
                                <option value="Issued">Issued</option>
                                <option value="InTransit">In Transit</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 pt-0.5">
                <button type="button" class="btn btn-outline-secondary btn-sm py-1 flex-grow-1" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm py-1 flex-grow-1 fw-bold" id="btnAddItgkSubmit">
                    <i class="fas fa-save me-1"></i>Save Certificate Packet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto fill district on ITGK selection & Auto compute grand total
(function initAddItgkForm() {
    var sel = document.getElementById('add_itgk_code_select');
    var distInput = document.getElementById('add_district_input');
    if (sel && distInput) {
        sel.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.district) {
                distInput.value = opt.dataset.district;
            }
        });
    }

    // Auto sum total
    ['add_pass_cnt', 'add_fail_cnt', 'add_absent_cnt', 'add_ufm_cnt'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                var p = parseInt(document.getElementById('add_pass_cnt')?.value || 0, 10);
                var f = parseInt(document.getElementById('add_fail_cnt')?.value || 0, 10);
                var a = parseInt(document.getElementById('add_absent_cnt')?.value || 0, 10);
                var u = parseInt(document.getElementById('add_ufm_cnt')?.value || 0, 10);
                var gt = document.getElementById('add_grand_total');
                if (gt) gt.value = (p + f + a + u);
            });
        }
    });

    // AJAX Submit — intercept form POST so JSON response is handled gracefully
    var form = document.getElementById('addItgkForm');
    var btn  = document.getElementById('btnAddItgkSubmit');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            // Show loader
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving...';

            var formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Save Certificate Packet';
                if (data.success) {
                    // Show success toast or alert
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Certificate Packet saved!', 'success');
                    } else {
                        alert('✅ ' + (data.message || 'Certificate Packet saved!'));
                    }
                    form.reset();
                    // Close offcanvas
                    var offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('addItgkOffcanvas'));
                    if (offcanvas) offcanvas.hide();
                    // Reload page after short delay to refresh list
                    setTimeout(function() { window.location.reload(); }, 800);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + (data.message || 'Failed to save.'), 'danger');
                    } else {
                        alert('❌ ' + (data.message || 'Failed to save.'));
                    }
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Save Certificate Packet';
                console.error('Add ITGK submit error:', err);
                alert('❌ Network error. Please try again.');
            });
        });
    }
})();
</script>
