<?php
// Dashboard Page View
// Displays statistics and overview for authenticated users
// RBAC: PARTNER+ can view dashboard
?>

<div class="row mb-2">
    <div class="col-12">
        <h2 class="fw-bold mb-0">Dashboard</h2>
        <p class="text-muted small mb-0">Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></p>
    </div>
</div>

<?php if ($role !== 'GUEST'): ?>
<!-- Top KPI cards -->
<div class="row g-2 mb-2">
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-2">
            <i class="fas fa-certificate fa-lg text-primary mb-1"></i>
            <h4 class="fw-bold mb-0"><?= (int)($totalCertificates ?? 0) ?></h4>
            <small class="text-muted">Certificates</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-2">
            <i class="fas fa-users fa-lg text-success mb-1"></i>
            <h4 class="fw-bold mb-0"><?= (int)($totalLearners ?? 0) ?></h4>
            <small class="text-muted">Learners</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-2">
            <i class="fas fa-chart-bar fa-lg text-info mb-1"></i>
            <h4 class="fw-bold mb-0">Analytics</h4>
            <small class="text-muted"><a href="<?= BASE_URL ?>analytics" class="text-decoration-none">View Reports →</a></small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-2">
            <i class="fas fa-user-tag fa-lg text-warning mb-1"></i>
            <h6 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars(ucfirst($role)) ?></h6>
            <small class="text-muted">Your Role</small>
        </div>
    </div>
</div>

<!-- Monthly trend (certificate + learner) -->
<?php
$certMonthly  = (new App\Models\Certificate())->getMonthlyStats(6);
$learnerMonthly = (new App\Models\LearnerResult())->getMonthlyStats(6);
$months = [];
for ($i = 5; $i >= 0; $i--) $months[] = date('M', strtotime("-$i months"));
$chartCert = array_map(fn($label) => (int)($certMonthly[array_search($label, array_column($certMonthly, 'month'))]['count'] ?? 0), $months);
$chartLrn  = array_map(fn($label) => (int)($learnerMonthly[array_search($label, array_column($learnerMonthly, 'month'))]['count'] ?? 0), $months);
?>
<div class="row mb-2" id="dashboard-chart">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>6-Month Trend</h6>
            </div>
            <div class="card-body p-2">
                <canvas id="dashChart" height="90"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions + Recent Activity -->
<div class="row g-2">
    <div class="col-md-6 mb-2">
        <div class="card-modern">
            <div class="card-body p-2">
                <h6 class="card-title fw-bold mb-2">Quick Actions</h6>
                <div class="d-grid gap-1">
                    <a href="<?= BASE_URL ?>itgk/list" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-certificate me-1"></i>Manage Certificates
                    </a>
                    <a href="<?= BASE_URL ?>learners/list" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-graduation-cap me-1"></i>View Learner Results
                    </a>
                    <a href="<?= BASE_URL ?>analytics" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-chart-bar me-1"></i>View Analytics
                    </a>
                    <?php if ($role === 'SUPERADMIN'): ?>
                    <a href="<?= BASE_URL ?>data-upload" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Data Upload
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-2">
        <div class="card-modern">
            <div class="card-body p-2">
                <h6 class="card-title fw-bold mb-2">Recent Activity</h6>
                <p class="text-muted small mb-2">Latest system events will appear here.</p>
                <ul class="list-unstyled mb-0 small">
                    <?php if ((int)($totalCertificates ?? 0) > 0): ?>
                    <li class="mb-1"><i class="fas fa-dot-circle text-success me-1"></i>Certificates tracked</li>
                    <?php endif; ?>
                    <?php if ((int)($totalLearners ?? 0) > 0): ?>
                    <li class="mb-1"><i class="fas fa-dot-circle text-primary me-1"></i>Learner records in system</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card-modern text-center p-5">
            <i class="fas fa-lock fa-3x text-muted mb-3"></i>
            <h4 class="fw-bold">Authentication Required</h4>
            <p class="text-muted">Please <a href="<?= BASE_URL ?>login">login</a> to access the full dashboard.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Mini canvas chart (no external charting lib) -->
<script>
(function() {
    var months  = <?= json_encode($months ?? []) ?>;
    var certData  = <?= json_encode($chartCert ?? []) ?>;
    var lrnData   = <?= json_encode($chartLrn ?? []) ?>;

    var canvas  = document.getElementById('dashChart');
    if (!canvas || !months.length) return;

    var W = canvas.parentElement.clientWidth - 40,
        H = 260,
        pad = 38;
    canvas.width  = W;
    canvas.height = H;
    var CTX = canvas.getContext('2d');

    function xS(i) { return pad + (i / (months.length - 1)) * (W - pad * 2); }
    function yS(v) {
        var mx = Math.max.apply(null, certData.concat(lrnData).concat([1]));
        return H - pad - (v / mx) * (H - pad * 2);
    }

    // grid
    CTX.font = '11px system-ui';
    CTX.textAlign = 'right';
    CTX.strokeStyle = 'rgba(148,163,184,0.2)';
    CTX.fillStyle   = '#94a3b8';
    [0,1,2,3,4].forEach(function(i) {
        var mx = Math.max.apply(null, certData.concat(lrnData).concat([1]));
        var v  = Math.round(mx / 4 * i);
        var y  = H - pad - (v / mx) * (H - pad * 2);
        CTX.beginPath(); CTX.moveTo(pad, y); CTX.lineTo(W - pad, y); CTX.stroke();
        CTX.fillText(v, pad - 6, y + 4);
    });
    // x labels
    CTX.textAlign = 'center';
    months.forEach(function(m, i) { CTX.fillText(m, xS(i), H - 10); });

    function line(data, color) {
        CTX.beginPath();
        CTX.strokeStyle = color; CTX.lineWidth = 2; CTX.lineJoin = 'round';
        data.forEach(function(v,i) { i === 0 ? CTX.moveTo(xS(i), yS(v)) : CTX.lineTo(xS(i), yS(v)); });
        CTX.stroke();
        CTX.fillStyle = color;
        data.forEach(function(v,i) { CTX.beginPath(); CTX.arc(xS(i), yS(v), 4, 0, Math.PI*2); CTX.fill(); });
    }
    line(certData, '#3b82f6');
    line(lrnData,  '#22c55e');
})();
</script>
