<?php
// Analytics Page View
// Renders real model-driven charts and KPI cards
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-1">Analytics</h2>
        <p class="text-muted">Detailed statistics and trends</p>
    </div>
</div>

<!-- KPI summary row -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-3">
            <i class="fas fa-certificate fa-2x text-primary mb-2"></i>
            <h4 class="fw-bold mb-0"><?= (int)($totalCertificates ?? 0) ?></h4>
            <small class="text-muted">Total Certificates</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-3">
            <i class="fas fa-graduation-cap fa-2x text-success mb-2"></i>
            <h4 class="fw-bold mb-0"><?= (int)($totalLearners ?? 0) ?></h4>
            <small class="text-muted">Total Learners</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-3">
            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
            <h4 class="fw-bold mb-0"><?= (int)($certAnalytics['issued'] ?? 0) ?></h4>
            <small class="text-muted">Certificates Issued</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-modern text-center p-3">
            <i class="fas fa-truck fa-2x text-warning mb-2"></i>
            <h4 class="fw-bold mb-0"><?= (int)($certAnalytics['intransit'] ?? 0) ?></h4>
            <small class="text-muted">In Transit</small>
        </div>
    </div>
</div>

<!-- Certificate status breakdown -->
<?php if (!empty($certAnalytics)): ?>
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Certificate Status</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4 mb-3">
                        <div class="p-3 rounded bg-success bg-opacity-10">
                            <i class="fas fa-check fa-2x text-success mb-2"></i>
                            <h5 class="fw-bold mb-0"><?= (int)($certAnalytics['issued'] ?? 0) ?></h5>
                            <small class="text-muted">Issued</small>
                        </div>
                    </div>
                    <div class="col-4 mb-3">
                        <div class="p-3 rounded bg-primary bg-opacity-10">
                            <i class="fas fa-box fa-2x text-primary mb-2"></i>
                            <h5 class="fw-bold mb-0"><?= (int)($certAnalytics['available'] ?? 0) ?></h5>
                            <small class="text-muted">Available</small>
                        </div>
                    </div>
                    <div class="col-4 mb-3">
                        <div class="p-3 rounded bg-warning bg-opacity-10">
                            <i class="fas fa-truck fa-2x text-warning mb-2"></i>
                            <h5 class="fw-bold mb-0"><?= (int)($certAnalytics['intransit'] ?? 0) ?></h5>
                            <small class="text-muted">In Transit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Monthly trend chart (Canvas-based, no external libs) -->
<div class="row g-4 mb-4" id="chart-section">
    <div class="col-12">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>6-Month Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="120" style="max-height:400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Learner result breakdown (table) -->
<?php if (!empty($learnerAnalytics)): ?>
<div class="row g-4">
    <div class="col-12 col-md-6">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Learner Results</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-check text-success me-2"></i>Passed</span>
                        <strong><?= (int)($learnerAnalytics['passed'] ?? 0) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-times text-danger me-2"></i>Failed</span>
                        <strong><?= (int)($learnerAnalytics['failed'] ?? 0) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-book text-info me-2"></i>ITGK Codes</span>
                        <strong><?= (int)($learnerAnalytics['itgk_count'] ?? 0) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-check-double text-success me-2"></i>Issued</span>
                        <strong><?= (int)($learnerAnalytics['issued'] ?? 0) ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Available Learners</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-book-open text-primary me-2"></i>Scheduled SP</span>
                        <strong><?= (int)($learnerAnalytics['available_sp'] ?? 0) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-university text-warning me-2"></i>Pending ITGK</span>
                        <strong><?= (int)($learnerAnalytics['available_itgk'] ?? 0) ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Mini Chart: singleton Canvas -->
<script>
(function() {
    const months  = <?= json_encode($months ?? []) ?>;
    const certData  = <?= json_encode($certData  ?? []) ?>;
    const lrnData   = <?= json_encode($learnerData  ?? []) ?>;

    const canvas = document.getElementById('trendChart');
    if (!canvas || !months.length) return;

    // ── Scale helper ──────────────────────────────────────────────────────
    function scaleY(data) {
        const W = canvas.parentElement.clientWidth - 40;
        const H = 280;
        canvas.width  = W;
        canvas.height = H;
        const max = Math.max(...data, 1);
        const pad = 30;
        const xS = (i) => pad + (i / (data.length - 1)) * (W - 2 * pad);
        const yS = (v) => H - pad - (v / max) * (H - 2 * pad);
        return { x: xS, y: yS, W, H, pad };
    }

    const ctx = canvas.getContext('2d');

    // ── Grid ──────────────────────────────────────────────────────────────
    function drawGrid(max) {
        const steps = 5;
        ctx.font = '11px system-ui';
        ctx.textAlign = 'right';
        ctx.strokeStyle = 'rgba(148,163,184,0.25)';
        ctx.fillStyle   = '#94a3b8';
        for (let i = 0; i <= steps; i++) {
            const v = Math.round(max / steps * i);
            const y = canvas.height - 30 - (v / max) * (canvas.height - 60);
            ctx.beginPath();
            ctx.moveTo(40, y);
            ctx.lineTo(canvas.width - 10, y);
            ctx.stroke();
            ctx.fillText(v, 34, y + 4);
        }
        // x-axis labels
        ctx.textAlign = 'center';
        months.forEach((m, i) => {
            const x = 40 + (i / (months.length - 1)) * (canvas.width - 2 * 30);
            ctx.fillText(m, x, canvas.height - 10);
        });
    }

    // ── Draw one line series ───────────────────────────────────────────────
    function drawLine(data, color) {
        const max = Math.max(...data, ...certData, ...lrnData, 1);
        const xS  = (i) => 40 + (i / (data.length - 1)) * (canvas.width - 2 * 30);
        const yS  = (v) => canvas.height - 30 - (v / max) * (canvas.height - 60);

        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth   = 2;
        ctx.lineJoin    = 'round';
        data.forEach((v, i) => {
            i === 0 ? ctx.moveTo(xS(i), yS(v)) : ctx.lineTo(xS(i), yS(v));
        });
        ctx.stroke();

        // Dots
        ctx.fillStyle = color;
        data.forEach((v, i) => {
            ctx.beginPath();
            ctx.arc(xS(i), yS(v), 4, 0, Math.PI * 2);
            ctx.fill();
        });
    }

    const maxVal = Math.max(...certData, ...lrnData, 1);
    drawGrid(maxVal);
    drawLine(certData,  '#3b82f6');  // blue  — certificates
    drawLine(lrnData,   '#22c55e');  // green — learners

    // ── Legend ────────────────────────────────────────────────────────────
    const legend = [
        { label: 'Certificates', color: '#3b82f6' },
        { label: 'Learners',     color: '#22c55e' },
    ];
    let legendX = 40;
    ctx.font = '12px system-ui';
    legend.forEach(item => {
        ctx.fillStyle = item.color;
        ctx.fillRect(legendX, 4, 12, 12);
        ctx.fillStyle = '#334155';
        ctx.textAlign = 'left';
        ctx.fillText(item.label, legendX + 16, 14);
        legendX += ctx.measureText(item.label).width + 36;
    });
})();
</script>
