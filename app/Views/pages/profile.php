<?php
/**
 * User Profile View
 * Enhanced profile management screen showing Issuer details, mapped office location, and user fields.
 *
 * @package App\Views\pages
 */

$user    = $user    ?? $_SESSION['user'] ?? [];
$offices = $offices ?? [];
$role    = $role    ?? $_SESSION['role'] ?? 'User';
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-user-circle me-2"></i>My Profile</h4>
                <p class="text-muted small mb-0">Manage issuer details, designation, and mapped office location</p>
            </div>
            <span class="badge bg-primary px-3 py-2 fs-6">
                <i class="fas fa-shield-alt me-1"></i>Role: <?= htmlspecialchars(strtoupper($role)) ?>
            </span>
        </div>
    </div>

    <!-- Alert Box -->
    <div id="profileAlert" class="alert alert-dismissible fade show d-none mb-3" role="alert">
        <span id="profileAlertMsg"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <div class="row g-3">
        <!-- Main Form Card -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="fas fa-user-edit me-2 text-primary"></i>Issuer Profile Information
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form id="profileUpdateForm">
                        <?= \App\Helpers\Csrf::fieldHtml() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Username</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars((string)($user['username'] ?? '')) ?>" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Role / Access</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(strtoupper((string)($user['role_name'] ?? $role))) ?>" readonly disabled>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small required">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars((string)($user['first_name'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars((string)($user['last_name'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small required">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string)($user['email'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Mobile Number</label>
                                <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars((string)($user['mobile'] ?? '')) ?>" placeholder="e.g. 98290XXXXX">
                            </div>
                        </div>

                        <hr class="my-4 text-muted">

                        <h6 class="fw-bold mb-3 text-secondary">
                            <i class="fas fa-building me-2"></i>Issuer &amp; Office Mapping
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Designation / Role Title</label>
                                <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars((string)($user['designation'] ?? '')) ?>" placeholder="e.g. Center Manager / Admin">
                                <div class="form-text small">Used as 'Issued From / Designation' on certificate issue acknowledgement.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Mapped Issuer Office</label>
                                <select name="office_id" class="form-select">
                                    <option value="">-- Select Mapped Office --</option>
                                    <?php foreach ($offices as $off): ?>
                                        <option value="<?= (int)$off['id'] ?>" <?= ((int)($user['office_id'] ?? 0) === (int)$off['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($off['name']) ?> <?= !empty($off['district']) ? ' (' . htmlspecialchars($off['district']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text small">Mapped office for certificate dispatch and receipts.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnSaveProfile">
                                <i class="fas fa-save me-2"></i>Save Profile Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Card: Session Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3 mb-3">
                <div class="card-header bg-light py-3 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="fas fa-id-badge me-2 text-info"></i>Active Session Data
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Issuer</small>
                        <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars((string)($user['name'] ?? 'N/A')) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Issued From</small>
                        <span class="fw-semibold text-secondary"><?= htmlspecialchars((string)($user['designation'] ?: 'Not set')) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Issuer Office</small>
                        <span class="badge bg-success px-2 py-1 fs-6"><?= htmlspecialchars((string)($user['office_name'] ?: 'Not mapped')) ?></span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Email</small>
                        <span class="text-dark small"><?= htmlspecialchars((string)($user['email'] ?? 'N/A')) ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Mobile</small>
                        <span class="text-dark small"><?= htmlspecialchars((string)($user['mobile'] ?: 'N/A')) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profileUpdateForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveProfile');
    const alertBox = document.getElementById('profileAlert');
    const alertMsg = document.getElementById('profileAlertMsg');

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    }

    try {
        const res = await fetch('<?= BASE_URL ?>profile/update', {
            method: 'POST',
            body: new FormData(this)
        });
        const json = await res.json();

        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');

        if (json.success) {
            alertBox.classList.add('alert-success');
            alertMsg.textContent = json.message || 'Profile updated successfully!';
            setTimeout(() => location.reload(), 1200);
        } else {
            alertBox.classList.add('alert-danger');
            alertMsg.textContent = json.message || 'Failed to update profile.';
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Save Profile Details';
            }
        }
    } catch (err) {
        alertBox.classList.remove('d-none');
        alertBox.classList.add('alert-danger');
        alertMsg.textContent = 'Network error: ' + err.message;
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Save Profile Details';
        }
    }
});
</script>