<?php
/**
 * Upload Page — Data Import Center
 *
 * Upload Excel / CSV files, OR fetch directly from a Google Sheet.
 * Maps columns → DB table, then commits via UploadService.
 *
 * @package App\Views\pages
 */

declare(strict_types=1);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-1">Data Upload</h2>
        <p class="text-muted">Import data from Excel, CSV, or Google Sheets</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Upload Controls -->
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Source</h5>
            </div>
            <div class="card-body">
                <!-- Upload Method Tabs -->
                <div class="mb-4">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="sourceMethod" id="srcFile" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="srcFile"><i class="fas fa-file-excel me-1"></i> File</label>

                        <input type="radio" class="btn-check" name="sourceMethod" id="srcSheet" autocomplete="off">
                        <label class="btn btn-outline-success" for="srcSheet"><i class="fas fa-table me-1"></i> Google Sheet</label>
                    </div>
                </div>

                <!-- Method A: File Upload -->
                <div id="method-file">
                    <form id="uploadFileForm" method="POST" enctype="multipart/form-data">
                        <?= \App\Helpers\Csrf::fieldHtml() ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Excel / CSV File</label>
                            <input type="file" name="data_file" accept=".xlsx,.xls,.csv" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Parse File
                        </button>
                    </form>
                </div>

                <!-- Method B: Google Sheet -->
                <div id="method-sheet" style="display:none;">
                    <form id="googleSheetForm" method="POST">
                        <?= \App\Helpers\Csrf::fieldHtml() ?>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Spreadsheet ID</label>
                            <input type="text" name="spreadsheet_id" class="form-control" placeholder="1BxiMVs0XRA5nFMdKvBdBZjGMUUqptbfs74NYvQEw">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Range</label>
                            <input type="text" name="range" class="form-control" value="Sheet1!A:Z">
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-download me-2"></i>Fetch Sheet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Mapping + Preview -->
    <div class="col-lg-8">
        <!-- Table Selection -->
        <div class="card-modern mb-4" id="uploadMapCard" style="display:none;">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-map me-2"></i>Column Mapping</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Target Table</label>
                        <select id="mapTable" class="form-control">
                            <option value="">-- Select table --</option>
                            <?php foreach ($availableTables as $table): ?>
                                <option value="<?= htmlspecialchars($table) ?>">
                                    <?= htmlspecialchars($table) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mode</label>
                        <select id="mapMethod" class="form-control">
                            <option value="new">Add New (Insert)</option>
                            <option value="update">Update Existing</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button id="btnLoadSchema" class="btn btn-btnoutline-primary w-100">
                            <i class="fas fa-sync me-2"></i>Load Schema
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column Mapping Area -->
        <div class="card-modern mb-4" id="mappingCard" style="display:none;">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list-check me-2"></i >Map Columns</h5>
            </div>
            <div class="card-body">
                <div id="mappingUI">
                    <p class="text-muted">Select a table to load schema.</p>
                </div>
                <button id="btnPerformUpload" class="btn btn-success mt-3" style="display:none;">
                    <i class="fas fa-check-circle me-2"></i>Start Upload
                </button>
            </div>
        </div>

        <!-- Data Preview -->
        <div class="card-modern" id="previewCard" style="display:none;">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Data Preview</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-modern mb-0" id="previewTable">
                        <thead id="previewHead"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Initial empty state -->
        <div class="card-modern" id="emptyState">
            <div class="card-body text-center py-5">
                <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No data uploaded yet</h5>
                <p class="text-muted small">Upload a file or fetch a Google Sheet to begin.</p>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = window.location.origin + '/certificate/';
let uploadHeaders   = [];
let uploadRows      = [];
let availableTables = <?= json_encode($availableTables) ?>;
let allowedTables   = <?= json_encode(array_keys($availableTables)) ?>;

/* ── Tab toggle ── */
document.querySelectorAll('input[name="sourceMethod"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('method-file').style.display   = r.id === 'srcFile'   ? '' : 'none';
        document.getElementById('method-sheet').style.display  = r.id === 'srcSheet'  ? '' : 'none';
    });
});

/* ── File upload ── */
document.getElementById('uploadFileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const r = await fetch(API_BASE + 'data-upload/file', { method:'POST', body: fd });
        const d = await r.json();
        if (d.success) { uploadHeaders = d.headers || []; uploadRows = d.rows || []; showPreview(); }
        else alert(d.message);
    } catch(err) { alert('Upload failed: ' + err.message); }
});

/* ── Google Sheet fetch ── */
document.getElementById('googleSheetForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const r = await fetch(API_BASE + 'data-upload/fetch-sheet', {
            method: 'POST', body: formData
        });
        const d = await r.json();
        if (d.success) { uploadHeaders = d.headers || []; uploadRows = d.rows || []; showPreview(); }
        else alert(d.message);
    } catch(err) { alert('Sheet fetch failed: ' + err.message); }
});

/* ── Preview ── */
function showPreview() {
    document.getElementById('emptyState').style.display   = 'none';
    document.getElementById('previewCard').style.display  = '';
    document.getElementById('uploadMapCard').style.display = '';

    // Table head
    const thead = document.getElementById('previewHead');
    thead.innerHTML = '<tr><th>#</th>' + uploadHeaders.map(h => '<th>' + h + '</th>').join('') + '</tr>';
    // Table body
    const tbody = document.getElementById('previewBody');
    tbody.innerHTML = uploadRows.map((row, i) =>
        '<tr><td>' + (i+1) + '</td>' +
        uploadHeaders.map(h => '<td>' + (row[h] ?? '') + '</td>').join('') + '</tr>'
    ).join('');
}

/* ── Load schema ── */
document.getElementById('btnLoadSchema').addEventListener('click', async () => {
    const table  = document.getElementById('mapTable').value;
    const method = document.getElementById('mapMethod').value;
    if (!table) { alert('Select a table first.'); return; }

    try {
        // Re-fetch schema
        const r = await fetch(API_BASE + 'data-upload/table-schema?table=' + encodeURIComponent(table));
        const d = await r.json();
        if (!d.success) { alert(d.message); return; }

        buildMappingUI(d.columns, table, method);
    } catch(err) { alert('Failed to load schema: ' + err.message); }
});

/* ── Build mapping UI ── */
function buildMappingUI(columns, table, method) {
    const ui = document.getElementById('mappingUI');
    document.getElementById('mappingCard').style.display = '';
    document.getElementById('btnPerformUpload').style.display = '';

    const colOpts = '<option value="">-- Ignore --</option>' +
        columns.map(c => '<option value="' + c + '">' + c + '</option>').join('');

    ui.innerHTML = uploadHeaders.map((h, i) =>
        '<div class="row g-2 align-items-center mb-2">' +
        '<div class="col-5"><label class="small fw-bold">' + h + '</label></div>' +
        '<div class="col-6"><select class="form-select form-select-sm col-map" data-file-col="' + i + '">' +
        colOpts + '</select></div>' +
        '<div class="col-1"><span class="badge bg-secondary">←</span></div>' +
        '</div>'
    ).join('');
}

/* ── Perform upload ── */
document.getElementById('btnPerformUpload').addEventListener('click', async () => {
    const table  = document.getElementById('mapTable').value;
    const method = document.getElementById('mapMethod').value;
    if (!table) { alert('Select a table.'); return; }

    // Build mapping: file-header-index → db-column-name
    const mapping = {};
    document.querySelectorAll('.col-map').forEach(sel => {
        const fileIdx = sel.getAttribute('data-file-col');
        const dbCol   = sel.value;
        if (dbCol) mapping[uploadHeaders[fileIdx]] = dbCol;
    });

    if (Object.keys(mapping).length === 0) { alert('Map at least one column.'); return; }

    try {
        const r = await fetch(API_BASE + 'data-upload/perform', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ table, method, mapping, rows: uploadRows, headers: uploadHeaders })
        });
        const d = await r.json();
        if (d.success)
            alert('Done! Inserted: ' + d.inserted + ', Updated: ' + d.updated + ', Skipped: ' + d.skipped);
        else
            alert('Error: ' + d.message);
    } catch(err) { alert('Upload failed: ' + err.message); }
});
</script>