<!-- ============================================================ -->
<!-- EDIT CERTIFICATE RECORD -- MULTI-STEP OFFCANVAS FORM          -->
<!-- ============================================================ -->
<div class="offcanvas offcanvas-end shadow-lg border-0" tabindex="-1" id="editCertOffcanvas" aria-labelledby="editCertOffcanvasLabel" style="width: 100%; max-width: 620px;">
    <div class="offcanvas-header bg-gradient bg-warning text-dark py-2 px-3 align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-dark bg-opacity-10 p-1.5 d-flex align-items-center justify-content-center" style="width:34px; height:34px;">
                <i class="fas fa-edit text-dark fs-6"></i>
            </div>
            <div>
                <h6 class="offcanvas-title fw-bold mb-0" id="editCertOffcanvasLabel" style="font-size: 14px;">Edit Certificate Record</h6>
                <small class="text-dark-50 d-block" style="font-size: 10px; line-height: 1.1;">Update certificate packet data in Google Sheet</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Stepper Navigation Bar -->
    <div class="bg-white border-bottom px-2 py-1.5">
        <div class="d-flex justify-content-between align-items-center position-relative">
            <!-- Progress Line Background -->
            <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-light" style="height: 3px; z-index: 1;"></div>
            <div id="ecStepProgressLine" class="position-absolute top-50 start-0 translate-middle-y bg-warning" style="height: 3px; width: 0%; z-index: 2; transition: width 0.3s ease;"></div>

            <!-- Step 1 Indicator -->
            <button type="button" class="btn p-0 border-0 text-center position-relative ec-step-btn active" data-step="1" style="z-index: 3;">
                <span class="step-num rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-dark bg-warning" style="width:24px; height:24px; font-size:11px; border:2px solid #fff;">1</span>
                <span class="step-label d-block fw-semibold text-dark" style="font-size:9.5px; margin-top:2px;">Course & Center</span>
            </button>

            <!-- Step 2 Indicator -->
            <button type="button" class="btn p-0 border-0 text-center position-relative ec-step-btn" data-step="2" style="z-index: 3;">
                <span class="step-num rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-secondary bg-white border" style="width:24px; height:24px; font-size:11px;">2</span>
                <span class="step-label d-block text-muted" style="font-size:9.5px; margin-top:2px;">Counts & Packet</span>
            </button>

            <!-- Step 3 Indicator -->
            <button type="button" class="btn p-0 border-0 text-center position-relative ec-step-btn" data-step="3" style="z-index: 3;">
                <span class="step-num rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-secondary bg-white border" style="width:24px; height:24px; font-size:11px;">3</span>
                <span class="step-label d-block text-muted" style="font-size:9.5px; margin-top:2px;">Range & Location</span>
            </button>

            <!-- Step 4 Indicator -->
            <button type="button" class="btn p-0 border-0 text-center position-relative ec-step-btn" data-step="4" style="z-index: 3;">
                <span class="step-num rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-secondary bg-white border" style="width:24px; height:24px; font-size:11px;">4</span>
                <span class="step-label d-block text-muted" style="font-size:9.5px; margin-top:2px;">Receiver & Issuance</span>
            </button>
        </div>
    </div>

    <div class="offcanvas-body p-2 bg-light d-flex flex-column justify-content-between">
        <form id="editCertForm" class="flex-grow-1 d-flex flex-column justify-content-between">
            <?= \App\Helpers\Csrf::fieldHtml() ?>
            <input type="hidden" name="sheet_row" id="ec_sheet_row">

            <div class="tab-content flex-grow-1">
                <!-- STEP 1: Course & Center Information -->
                <div class="ec-step-content active" id="ecStep1">
                    <div class="card border-0 shadow-sm rounded-2">
                        <div class="card-header bg-white py-1.5 px-2.5 border-bottom">
                            <span class="fw-bold text-dark" style="font-size: 12px;"><i class="fas fa-graduation-cap text-warning me-1.5"></i>Step 1: Course &amp; Center Details</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-7 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Course Name <span class="text-danger">*</span></label>
                                    <input type="text" name="course_name" id="ec_course" class="form-control form-control-sm" placeholder="Course Name" required>
                                </div>
                                <div class="col-md-5 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Exam Name <span class="text-danger">*</span></label>
                                    <input type="text" name="exam_name" id="ec_exam" class="form-control form-control-sm" placeholder="Exam Name" required>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">ITGK Code <span class="text-danger">*</span></label>
                                    <input type="text" name="itgk_code" id="ec_itgk" class="form-control form-control-sm" placeholder="ITGK Code" required>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">District</label>
                                    <input type="text" name="district" id="ec_district" class="form-control form-control-sm" placeholder="District">
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Receiving Date</label>
                                    <input type="date" name="receiving_date" id="ec_date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Exam Date</label>
                                    <input type="date" name="exam_date" id="ec_examdate" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Counts & Packet Numbers -->
                <div class="ec-step-content d-none" id="ecStep2">
                    <div class="card border-0 shadow-sm rounded-2">
                        <div class="card-header bg-white py-1.5 px-2.5 border-bottom">
                            <span class="fw-bold text-dark" style="font-size: 12px;"><i class="fas fa-calculator text-warning me-1.5"></i>Step 2: Packet &amp; Result Counts</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Packet No.</label>
                                    <input type="text" name="packet_no" id="ec_packet" class="form-control form-control-sm" placeholder="Packet No.">
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-primary mb-0" style="font-size: 10.5px;">Grand Total</label>
                                    <input type="number" name="grand_total" id="ec_total" class="form-control form-control-sm text-center fw-bold text-primary" min="0" placeholder="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fw-semibold text-success mb-0" style="font-size: 10.5px;"><i class="fas fa-check-circle me-1"></i>Pass</label>
                                    <input type="number" name="pass" id="ec_pass" class="form-control form-control-sm text-center fw-bold text-success" min="0" placeholder="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fw-semibold text-danger mb-0" style="font-size: 10.5px;"><i class="fas fa-times-circle me-1"></i>Fail</label>
                                    <input type="number" name="fail" id="ec_fail" class="form-control form-control-sm text-center fw-bold text-danger" min="0" placeholder="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fw-semibold text-warning mb-0" style="font-size: 10.5px;"><i class="fas fa-user-slash me-1"></i>Absent</label>
                                    <input type="number" name="absent" id="ec_absent" class="form-control form-control-sm text-center fw-bold text-warning" min="0" placeholder="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;"><i class="fas fa-exclamation-triangle me-1"></i>UFM</label>
                                    <input type="number" name="ufm" id="ec_ufm" class="form-control form-control-sm text-center fw-bold text-secondary" min="0" placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Certificate Range & Location & Status -->
                <div class="ec-step-content d-none" id="ecStep3">
                    <div class="card border-0 shadow-sm rounded-2">
                        <div class="card-header bg-white py-1.5 px-2.5 border-bottom">
                            <span class="fw-bold text-dark" style="font-size: 12px;"><i class="fas fa-map-marker-alt text-warning me-1.5"></i>Step 3: Certificate Range, Location &amp; Status</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Cert No. From</label>
                                    <input type="text" name="cert_no_from" id="ec_certfrom" class="form-control form-control-sm" placeholder="From">
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Cert No. To</label>
                                    <input type="text" name="cert_no_to" id="ec_certto" class="form-control form-control-sm" placeholder="To">
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="ec_status" class="form-select form-select-sm" required>
                                        <option value="">-- Select Status --</option>
                                        <?php foreach ($statusOptions ?? ['Available', 'Issued', 'Not Received', 'InTransit'] as $status): ?>
                                            <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Storage Location</label>
                                    <select name="current_location" id="ec_location" class="form-select form-select-sm">
                                        <option value="">-- Select Storage Location --</option>
                                        <?php foreach ($locationOptions ?? [] as $loc): ?>
                                            <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: Receiver & Issuance Details -->
                <div class="ec-step-content d-none" id="ecStep4">
                    <div class="card border-0 shadow-sm rounded-2">
                        <div class="card-header bg-white py-1.5 px-2.5 border-bottom">
                            <span class="fw-bold text-dark" style="font-size: 12px;"><i class="fas fa-user-check text-warning me-1.5"></i>Step 4: Receiver &amp; Issuance Details</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-4 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Receiver Name</label>
                                    <input type="text" name="receiver_name" id="ec_receiver" class="form-control form-control-sm" placeholder="Receiver Name">
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Designation</label>
                                    <input type="text" name="receiver_designation" id="ec_desig" class="form-control form-control-sm" placeholder="Designation">
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Mobile</label>
                                    <input type="text" name="receiver_mobile" id="ec_mobile" class="form-control form-control-sm" placeholder="Mobile">
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Issued By</label>
                                    <input type="text" name="issued_by" id="ec_issuedby" class="form-control form-control-sm" placeholder="Issued By">
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Image / Photo URL</label>
                                    <input type="text" name="image" id="ec_image" class="form-control form-control-sm" placeholder="Image URL">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary mb-0" style="font-size: 10.5px;">Remark</label>
                                    <textarea name="remark" id="ec_remark" class="form-control form-control-sm" rows="2" placeholder="Enter Remark"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div class="border-top pt-2 mt-2 bg-white px-2 rounded-2">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-3" id="btnEcPrev" disabled>
                        <i class="fas fa-arrow-left me-1"></i>Previous
                    </button>
                    <div class="d-flex gap-1.5">
                        <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" data-bs-dismiss="offcanvas">Cancel</button>
                        <button type="button" class="btn btn-warning btn-sm py-1 px-3 fw-bold text-dark" id="btnEcNext">
                            Next<i class="fas fa-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-success btn-sm py-1 px-3 fw-bold text-white d-none" id="btnEditCertSubmit">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Multi-step Edit Certificate Offcanvas Wizard
(function initEditCertWizard() {
    let currentStep = 1;
    const totalSteps = 4;

    function showStep(step) {
        currentStep = step;
        
        // Hide all step content panels
        document.querySelectorAll('.ec-step-content').forEach(el => {
            el.classList.add('d-none');
            el.classList.remove('active');
        });
        
        // Show target step panel
        const targetPanel = document.getElementById('ecStep' + step);
        if (targetPanel) {
            targetPanel.classList.remove('d-none');
            targetPanel.classList.add('active');
        }

        // Update Stepper Progress Line
        const progressPct = ((step - 1) / (totalSteps - 1)) * 100;
        const progressLine = document.getElementById('ecStepProgressLine');
        if (progressLine) progressLine.style.width = progressPct + '%';

        // Update Step Buttons styling
        document.querySelectorAll('.ec-step-btn').forEach((btn, idx) => {
            const btnStep = idx + 1;
            const numSpan = btn.querySelector('.step-num');
            const lblSpan = btn.querySelector('.step-label');

            if (btnStep === step) {
                btn.classList.add('active');
                if (numSpan) {
                    numSpan.className = 'step-num rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-dark bg-warning';
                    numSpan.style.border = '2px solid #fff';
                }
                if (lblSpan) lblSpan.className = 'step-label d-block fw-bold text-dark';
            } else if (btnStep < step) {
                btn.classList.remove('active');
                if (numSpan) {
                    numSpan.className = 'step-num rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white bg-success';
                    numSpan.style.border = 'none';
                }
                if (lblSpan) lblSpan.className = 'step-label d-block text-success fw-semibold';
            } else {
                btn.classList.remove('active');
                if (numSpan) {
                    numSpan.className = 'step-num rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-secondary bg-white border';
                    numSpan.style.border = '1px solid #cbd5e1';
                }
                if (lblSpan) lblSpan.className = 'step-label d-block text-muted';
            }
        });

        // Navigation button states
        const btnPrev = document.getElementById('btnEcPrev');
        const btnNext = document.getElementById('btnEcNext');
        const btnSubmit = document.getElementById('btnEditCertSubmit');

        if (btnPrev) btnPrev.disabled = (step === 1);

        if (step === totalSteps) {
            if (btnNext) btnNext.classList.add('d-none');
            if (btnSubmit) btnSubmit.classList.remove('d-none');
        } else {
            if (btnNext) btnNext.classList.remove('d-none');
            if (btnSubmit) btnSubmit.classList.add('d-none');
        }
    }

    // Auto compute total in Edit wizard step 2
    ['ec_pass', 'ec_fail', 'ec_absent', 'ec_ufm'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                const p = parseInt(document.getElementById('ec_pass')?.value || 0, 10);
                const f = parseInt(document.getElementById('ec_fail')?.value || 0, 10);
                const a = parseInt(document.getElementById('ec_absent')?.value || 0, 10);
                const u = parseInt(document.getElementById('ec_ufm')?.value || 0, 10);
                const totalEl = document.getElementById('ec_total');
                if (totalEl) totalEl.value = (p + f + a + u);
            });
        }
    });

    // Next / Prev Button Listeners
    document.getElementById('btnEcNext')?.addEventListener('click', function() {
        if (currentStep < totalSteps) showStep(currentStep + 1);
    });

    document.getElementById('btnEcPrev')?.addEventListener('click', function() {
        if (currentStep > 1) showStep(currentStep - 1);
    });

    // Step Direct Click Navigation
    document.querySelectorAll('.ec-step-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'), 10);
            if (step >= 1 && step <= totalSteps) showStep(step);
        });
    });

    // Reset Wizard to Step 1 on Offcanvas Open
    const offcanvasEl = document.getElementById('editCertOffcanvas');
    if (offcanvasEl) {
        offcanvasEl.addEventListener('show.bs.offcanvas', function() {
            showStep(1);
        });
    }
})();
</script>
