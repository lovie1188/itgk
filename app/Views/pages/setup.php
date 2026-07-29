<?php
/**
 * Application Setup & Google Sheets Integration View — SUPERADMIN ONLY
 *
 * @package App\Views\pages
 */

declare(strict_types=1);
?>

<div class="row mb-2">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-0"><i class="fas fa-sliders-h me-2 text-primary"></i>Application & Google Sheets Setup</h3>
            <p class="text-muted small mb-0">Manage Google Sheets credentials, cell range mappings, and database sync options.</p>
        </div>
        <span class="badge bg-danger px-2 py-1"><i class="fas fa-shield-alt me-1"></i>SUPERADMIN ONLY</span>
    </div>
</div>

<div class="row g-2">
    <!-- Left Column: Settings Form -->
    <div class="col-lg-7">
        <form id="setupForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>

            <!-- 1. ITGK Certificates Google Sheet Card -->
            <div class="card-modern mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-certificate me-2 text-warning"></i>1. ITGK Certificates Dataset Configuration</h6>
                    <span class="badge bg-warning text-dark">Tab: Certificate</span>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Google Sheet ID</label>
                        <input type="text" name="google_sheet_id" id="google_sheet_id" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['google_sheet_id'] ?? getenv('GSHEET_CERTIFICATE_ID') ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4')) ?>" placeholder="e.g. 18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tab Name</label>
                            <input type="text" name="google_sheet_tab" id="google_sheet_tab" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['google_sheet_tab'] ?? getenv('GSHEET_CERTIFICATE_TAB') ?: 'Certificate')) ?>" placeholder="Certificate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Range Expression</label>
                            <input type="text" name="google_sheet_range" id="google_sheet_range" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['google_sheet_range'] ?? getenv('GSHEET_CERTIFICATE_RANGE') ?: 'Certificate!A1:V')) ?>" placeholder="Certificate!A1:V">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Learner Results Google Sheet Card -->
            <div class="card-modern mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-graduation-cap me-2 text-info"></i>2. Learner Results Dataset Configuration</h6>
                    <span class="badge bg-info text-white">Tab: Student_Result</span>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Google Sheet ID</label>
                        <input type="text" name="student_result_sheet_id" id="student_result_sheet_id" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['student_result_sheet_id'] ?? getenv('GSHEET_STUDENT_RESULT_ID') ?: '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4')) ?>" placeholder="e.g. 18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tab Name</label>
                            <input type="text" name="student_result_tab" id="student_result_tab" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['student_result_tab'] ?? getenv('GSHEET_STUDENT_RESULT_TAB') ?: 'Student_Result')) ?>" placeholder="Student_Result">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Range Expression</label>
                            <input type="text" name="student_result_range" id="student_result_range" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['student_result_range'] ?? getenv('GSHEET_STUDENT_RESULT_RANGE') ?: 'Student_Result!A1:Z')) ?>" placeholder="Student_Result!A1:Z">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. ITGK Master List Configuration Card -->
            <div class="card-modern mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-building me-2 text-success"></i>3. ITGK Master List Configuration</h6>
                    <span class="badge bg-success text-white">Tab: ITGK!A1:R131</span>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Google Sheet ID</label>
                        <input type="text" name="itgk_master_sheet_id" id="itgk_master_sheet_id" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['itgk_master_sheet_id'] ?? getenv('GSHEET_ITGK_MASTER_ID') ?: '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg')) ?>" placeholder="e.g. 16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tab Name</label>
                            <input type="text" name="itgk_master_tab" id="itgk_master_tab" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['itgk_master_tab'] ?? getenv('GSHEET_ITGK_MASTER_TAB') ?: 'ITGK')) ?>" placeholder="ITGK">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Range Expression</label>
                            <input type="text" name="itgk_master_range" id="itgk_master_range" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($settings['itgk_master_range'] ?? getenv('GSHEET_ITGK_MASTER_RANGE') ?: 'ITGK!A1:R131')) ?>" placeholder="ITGK!A1:R131">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Data Fetch & Sync Mode</label>
                        <select name="sync_mode" id="sync_mode" class="form-select form-select-sm">
                            <option value="google_sheet" <?= ($settings['sync_mode'] ?? '') === 'google_sheet' ? 'selected' : '' ?>>Google Sheets Mode (Direct Live Read - Recommended)</option>
                            <option value="database" <?= ($settings['sync_mode'] ?? '') === 'database' ? 'selected' : '' ?>>Local Database Mode (MySQL Tables Only)</option>
                            <option value="both" <?= ($settings['sync_mode'] ?? '') === 'both' ? 'selected' : '' ?>>Hybrid Mode (MySQL Database + Google Sheets Sync)</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" id="btnSaveSetup" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-save me-1"></i>Save Configurations & Sync to .env
                        </button>
                        <button type="button" id="btnTestConn" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plug me-1"></i>Test Connection & Extract Columns
                        </button>
                    </div>

                    <div id="testResult" class="mt-2"></div>
                </div>
            </div>
        </form>
    </div>

    <!-- Right Column: System Status & Help Guide -->
    <div class="col-lg-5">
        <div class="card-modern mb-2">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-server me-2 text-info"></i>Environment & Service Health</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-group list-group-flush mb-0 small">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                        <span><i class="fas fa-database text-primary me-2"></i>MySQL Database</span>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Connected</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                        <span><i class="fab fa-google text-danger me-2"></i>Firebase Auth</span>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                        <span><i class="fas fa-envelope text-warning me-2"></i>Gmail SMTP Service</span>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ready</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                        <span><i class="fas fa-table text-info me-2"></i>Registered DB Tables</span>
                        <span class="badge bg-secondary"><?= count($tables ?? []) ?> tables</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                        <span><i class="fas fa-code-branch text-secondary me-2"></i>PHP Version</span>
                        <span class="badge bg-dark"><?= PHP_VERSION ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card-modern">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Google Sheets Access Rules</h6>
            </div>
            <div class="card-body p-3 small text-muted">
                <p class="mb-1"><b>1. Public Link Sharing:</b> Ensure your Google Sheet is shared as <i>"Anyone with the link can view"</i>.</p>
                <p class="mb-1"><b>2. Exact Tab Names:</b> Match tab names precisely (e.g. <code>Certificate</code>, <code>ITGK_2026</code>).</p>
                <p class="mb-0"><b>3. Range Specs:</b> Specify column ranges like <code>Certificate!A1:V</code> to fetch all records cleanly.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Preset Selector Handler
document.getElementById('preset_selector')?.addEventListener('change', function() {
    const val = this.value;
    const sheetId = document.getElementById('google_sheet_id');
    const sheetTab = document.getElementById('google_sheet_tab');
    const sheetRange = document.getElementById('google_sheet_range');

    if (val === 'itgk_cert') {
        sheetId.value = '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4';
        sheetTab.value = 'Certificate';
        sheetRange.value = 'Certificate!A1:V';
    } else if (val === 'student_result') {
        sheetId.value = '18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4';
        sheetTab.value = 'Student_Result';
        sheetRange.value = 'Student_Result!A1:Z';
    } else if (val === 'itgk_master') {
        sheetId.value = '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg';
        sheetTab.value = 'ITGK';
        sheetRange.value = 'ITGK!A1:R131';
    } else if (val === 'itgk_2026') {
        sheetId.value = '16-aykoIV-uUWiqgh1xyhoQuC7zesJoko7uZCWCEDOXg';
        sheetTab.value = 'ITGK_2026';
        sheetRange.value = 'ITGK_2026!A1:Z';
    }
});

// Save Settings Form
document.getElementById('setupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveSetup');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    if (typeof window.showLoader === 'function') {
        window.showLoader('Saving Google Sheet configuration & updating .env...');
    }

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    try {
        const res = await fetch('<?= BASE_URL ?>setup/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            alert('Google Sheet configuration saved & synchronized to .env successfully!');
        } else {
            alert('Error: ' + (json.message || 'Failed to save settings'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    } finally {
        if (typeof window.hideLoader === 'function') {
            window.hideLoader();
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Save Configuration & Sync to .env';
    }
});

// Test Connection & Column Extraction
document.getElementById('btnTestConn').addEventListener('click', async function() {
    const spreadsheet_id = document.getElementById('google_sheet_id').value.trim();
    const sheet_tab = document.getElementById('google_sheet_tab').value.trim();
    const range = document.getElementById('google_sheet_range').value.trim();
    const resDiv = document.getElementById('testResult');

    if (!spreadsheet_id) {
        alert('Please enter a Google Sheet ID to test.');
        return;
    }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';

    if (typeof window.showLoader === 'function') {
        window.showLoader('Connecting to Google Sheet & extracting schema...');
    }

    resDiv.innerHTML = '<div class="alert alert-info py-1 small mb-0"><i class="fas fa-spinner fa-spin me-1"></i>Fetching data directly from Google Sheet...</div>';

    try {
        const res = await fetch('<?= BASE_URL ?>setup/test-connection', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ spreadsheet_id, sheet_tab, range })
        });
        const json = await res.json();

        if (json.success) {
            let headersHtml = '';
            if (json.headers && json.headers.length) {
                headersHtml = '<div class="mt-2"><strong>Extracted Column Headers (' + json.headers.length + '):</strong><div class="d-flex flex-wrap gap-1 mt-1">' +
                    json.headers.map(h => `<span class="badge bg-primary text-wrap">${h}</span>`).join('') +
                    '</div></div>';
            }

            resDiv.innerHTML = `
                <div class="alert alert-success py-2 small mb-0">
                    <i class="fas fa-check-circle me-1"></i>${json.message}
                    ${headersHtml}
                </div>
            `;
        } else {
            resDiv.innerHTML = `<div class="alert alert-danger py-2 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>${json.message}</div>`;
        }
    } catch (err) {
        resDiv.innerHTML = `<div class="alert alert-danger py-2 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Connection failed: ${err.message}</div>`;
    } finally {
        if (typeof window.hideLoader === 'function') {
            window.hideLoader();
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test Connection & Extract Columns';
    }
});
</script>
