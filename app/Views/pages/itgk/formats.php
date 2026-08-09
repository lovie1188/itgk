<?php
/**
 * ITGK Document Formats & Templates View
 *
 * Displays ITGK-related document formats and templates.
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-primary">
                <i class="fas fa-file-alt me-2"></i>ITGK Formats
            </h4>
            <p class="text-muted small mb-0">Document formats and templates for ITGK centers</p>
        </div>
    </div>

    <!-- ─── ANALYTICS STRIP ───────────────────────────────── -->
    <?php if (!empty($analytics)): ?>
    <div id="analyticsStripSection" class="mb-2" style="overflow-x:auto;">
        <div id="analyticsCardsRow" class="d-flex gap-1" style="min-width:100%;">
            <?php
            $cards = [
                ['label'=>'Total Centers', 'value'=>$analytics['total'] ?? 0,     'icon'=>'fa-building',     'color'=>'#4f46e5', 'bg'=>'#eef2ff'],
                ['label'=>'Districts',     'value'=>$analytics['districts'] ?? 0,  'icon'=>'fa-map-marker-alt','color'=>'#0891b2', 'bg'=>'#ecfeff'],
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

    <!-- Formats Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="formatsTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;" class="rounded-start">#</th>
                            <th style="width: 100px;">ITGK Code</th>
                            <th>ITGK Center Name</th>
                            <th style="width: 160px;">District</th>
                            <th style="width: 120px;">Available Formats</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($formatsList)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No ITGK format records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($formatsList as $item): ?>
                                <tr>
                                    <td><?= $item['id'] ?></td>
                                    <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($item['code']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['district']) ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="#" class="btn btn-outline-primary btn-sm" title="Download Admission Form"><i class="fas fa-download me-1"></i>Admission</a>
                                            <a href="#" class="btn btn-outline-success btn-sm" title="Download Certificate Format"><i class="fas fa-download me-1"></i>Certificate</a>
                                            <a href="#" class="btn btn-outline-info btn-sm" title="Download Report Format"><i class="fas fa-download me-1"></i>Report</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>