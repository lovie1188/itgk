<?php
/**
 * SMTP Email Setup View — SUPERADMIN ONLY
 *
 * @package App\Views\pages
 */

declare(strict_types=1);
?>

<div class="row mb-2">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-envelope-open-text me-2 text-primary"></i>SMTP Email Setup</h2>
            <p class="text-muted small mb-0">Configure Gmail SMTP credentials for automated system emails and alerts.</p>
        </div>
        <span class="badge bg-danger px-2 py-1"><i class="fas fa-shield-alt me-1"></i>SUPERADMIN ONLY</span>
    </div>
</div>

<div class="row g-2">
    <!-- Left Column: SMTP Configuration Form -->
    <div class="col-lg-7">
        <form id="smtpForm">
            <?= \App\Helpers\Csrf::fieldHtml() ?>

            <div class="card-modern mb-2">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fab fa-google text-danger me-2"></i>Gmail & SMTP Server Credentials</h6>
                    <span class="badge bg-outline-primary">Gmail SSL/TLS</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">SMTP Host</label>
                            <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="<?= htmlspecialchars((string)($settings['smtp_host'] ?? 'smtp.gmail.com')) ?>" required placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Port</label>
                            <input type="number" name="smtp_port" id="smtp_port" class="form-control" value="<?= htmlspecialchars((string)($settings['smtp_port'] ?? 587)) ?>" required placeholder="587">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Encryption</label>
                            <select name="smtp_encryption" id="smtp_encryption" class="form-select">
                                <option value="tls" <?= ($settings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (Port 587 - Recommended)</option>
                                <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gmail Address / Username</label>
                            <input type="email" name="smtp_user" id="smtp_user" class="form-control" value="<?= htmlspecialchars((string)($settings['smtp_user'] ?? '')) ?>" required placeholder="your.email@gmail.com">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Gmail App Password</label>
                        <div class="input-group">
                            <input type="password" name="smtp_pass" id="smtp_pass" class="form-control" value="<?= htmlspecialchars((string)($settings['smtp_pass'] ?? '')) ?>" placeholder="16-character Gmail App Password">
                            <button class="btn btn-outline-secondary" type="button" id="btnTogglePass"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="form-text small">Use a 16-character Gmail App Password (not your regular account password).</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">From Email Address</label>
                            <input type="email" name="smtp_from_email" id="smtp_from_email" class="form-control" value="<?= htmlspecialchars((string)($settings['smtp_from_email'] ?? '')) ?>" required placeholder="no-reply@yourdomain.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sender Name</label>
                            <input type="text" name="smtp_from_name" id="smtp_from_name" class="form-control" value="<?= htmlspecialchars((string)($settings['smtp_from_name'] ?? 'SoftSam ITGK Portal')) ?>" required placeholder="SoftSam ITGK Portal">
                        </div>
                    </div>

                    <button type="submit" id="btnSaveSmtp" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-save me-1"></i>Save SMTP Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Right Column: Test Email Sandbox & Setup Instructions -->
    <div class="col-lg-5">
        <!-- Test Email Sandbox -->
        <div class="card-modern mb-2">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-paper-plane me-2 text-success"></i>SMTP Test Sandbox</h6>
            </div>
            <div class="card-body p-3">
                <p class="small text-muted mb-2">Send a real test email to verify your Gmail SMTP setup.</p>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Recipient Email Address</label>
                    <input type="email" id="test_email" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? 'softtech.lovejeet@gmail.com') ?>" placeholder="recipient@example.com">
                </div>
                <button type="button" id="btnSendTestMail" class="btn btn-outline-success btn-sm w-100 mb-2">
                    <i class="fas fa-paper-plane me-1"></i>Send Test Email
                </button>
                <div id="testMailResult"></div>
            </div>
        </div>

        <!-- Gmail Instructions -->
        <div class="card-modern">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-info"></i>Gmail App Password Guide</h6>
            </div>
            <div class="card-body p-3 small text-muted">
                <ol class="ps-3 mb-0">
                    <li class="mb-1">Enable <b>2-Step Verification</b> on your Google Account.</li>
                    <li class="mb-1">Go to <a href="https://myaccount.google.com/apppasswords" target="_blank" class="fw-bold">myaccount.google.com/apppasswords</a>.</li>
                    <li class="mb-1">Generate a new 16-character App Password under <b>Select App</b> → <i>Mail</i>.</li>
                    <li class="mb-0">Paste the 16-character code directly into <b>Gmail App Password</b> field.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
document.getElementById('btnTogglePass').addEventListener('click', function() {
    const input = document.getElementById('smtp_pass');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
});

// Save Settings Form
document.getElementById('smtpForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveSmtp');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    try {
        const res = await fetch('<?= BASE_URL ?>smtp-setup/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            alert('SMTP settings saved successfully!');
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
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Save SMTP Settings';
    }
});

// Send Test Email
document.getElementById('btnSendTestMail').addEventListener('click', async function() {
    const testEmail = document.getElementById('test_email').value.trim();
    const resDiv = document.getElementById('testMailResult');

    if (!testEmail) {
        alert('Please enter a recipient email address.');
        return;
    }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending Email...';
    if (typeof window.showLoader === 'function') {
        window.showLoader('Connecting & sending test email via Gmail SMTP...');
    }
    resDiv.innerHTML = '<div class="alert alert-info py-1 small mb-0"><i class="fas fa-spinner fa-spin me-1"></i>Connecting to Gmail SMTP server...</div>';

    // Collect current form inputs
    const form = document.getElementById('smtpForm');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData);
    payload.test_email = testEmail;

    try {
        const res = await fetch('<?= BASE_URL ?>smtp-setup/test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.success) {
            resDiv.innerHTML = `<div class="alert alert-success py-1 small mb-0"><i class="fas fa-check-circle me-1"></i>${json.message}</div>`;
        } else {
            resDiv.innerHTML = `<div class="alert alert-danger py-1 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>${json.message}</div>`;
        }
    } catch (err) {
        resDiv.innerHTML = `<div class="alert alert-danger py-1 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Connection failed: ${err.message}</div>`;
    } finally {
        if (typeof window.hideLoader === 'function') {
            window.hideLoader();
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Send Test Email';
    }
});
</script>
