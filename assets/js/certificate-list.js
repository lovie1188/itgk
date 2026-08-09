// Premium Toast Notification
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : 'bg-warning text-dark');
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle');
    const html = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fas ${icon} fs-5"></i>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(toastId);
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }
}

// Edit Certificate Form Submit -- saves to Google Sheet via API
document.getElementById('editCertForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btnEditCertSubmit');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving to Google Sheet...';
    }
    if (typeof window.showLoader === 'function') window.showLoader('Saving to Google Sheet...');
    try {
        const baseUrl = window.BASE_URL || '/certificate/';
        const res = await fetch(baseUrl + 'itgk/update', {
            method: 'POST',
            body: new FormData(this)
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message || 'Saved to Google Sheet!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(json.message || 'Save failed', 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Save to Google Sheet';
            }
        }
    } catch (err) {
        showToast('Network error: ' + err.message, 'danger');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Save to Google Sheet';
        }
    } finally {
        if (typeof window.hideLoader === 'function') window.hideLoader();
    }
});

// Quick Issue Button Handler
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-quick-issue');
    if (!btn) return;
    var sheetRow = parseInt(btn.dataset.sheetRow, 10);
    if (!sheetRow) { alert('Sheet row not found.'); return; }
    document.dispatchEvent(new CustomEvent('quickIssue', {
        detail: {
            sheetRow: sheetRow,
            id: btn.dataset.id || '',
            itgk: btn.dataset.itgk || '',
            district: btn.dataset.district || '',
            course: btn.dataset.course || '',
            exam: btn.dataset.exam || '',
            packet: btn.dataset.packet || '',
            total: btn.dataset.total || ''
        }
    }));
});

// Edit Button Handler -- Populate Edit Offcanvas
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-edit-cert');
    if (!btn) return;
    
    function set(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val || '';
    }
    function sel(id, val) {
        var el = document.getElementById(id);
        if (!el) return;
        el.value = val || '';
        if (el.value !== (val || '')) {
            for (var i = 0; i < el.options.length; i++) {
                if (el.options[i].value.toLowerCase() === (val || '').toLowerCase()) {
                    el.selectedIndex = i;
                    break;
                }
            }
        }
    }

    function formatDateVal(val) {
        if (!val) return '';
        var v = val.trim();
        // If DD/MM/YYYY or DD-MM-YYYY
        var match = v.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (match) {
            var day = match[1].padStart(2, '0');
            var month = match[2].padStart(2, '0');
            var year = match[3];
            return year + '-' + month + '-' + day;
        }
        return v;
    }

    set('ec_sheet_row', btn.getAttribute('data-sheetrow'));
    set('ec_course', btn.getAttribute('data-course'));
    set('ec_exam', btn.getAttribute('data-exam'));
    set('ec_itgk', btn.getAttribute('data-itgk'));
    set('ec_district', btn.getAttribute('data-district'));
    set('ec_date', formatDateVal(btn.getAttribute('data-date')));
    set('ec_examdate', formatDateVal(btn.getAttribute('data-examdate')));
    set('ec_pass', btn.getAttribute('data-pass'));
    set('ec_fail', btn.getAttribute('data-fail'));
    set('ec_absent', btn.getAttribute('data-absent'));
    set('ec_ufm', btn.getAttribute('data-ufm'));
    set('ec_total', btn.getAttribute('data-total'));
    set('ec_packet', btn.getAttribute('data-packet'));
    set('ec_certfrom', btn.getAttribute('data-certfrom'));
    set('ec_certto', btn.getAttribute('data-certto'));
    sel('ec_location', btn.getAttribute('data-location'));
    set('ec_remark', btn.getAttribute('data-remark'));
    set('ec_receiver', btn.getAttribute('data-receiver'));
    set('ec_desig', btn.getAttribute('data-desig'));
    set('ec_mobile', btn.getAttribute('data-mobile'));
    set('ec_issuedby', btn.getAttribute('data-issuedby'));
    set('ec_image', btn.getAttribute('data-image'));
    sel('ec_status', btn.getAttribute('data-status'));
});

// Client-side Pagination + Live Search Engine
(function initCertPagination() {
    function getRows() {
        var isMobile = window.innerWidth < 768;
        var selector = isMobile ? '.cert-mobile-card' : '.cert-main-row';
        return Array.from(document.querySelectorAll(selector));
    }

    var perPageEl = document.getElementById('certPerPage');
    var perPage = perPageEl ? parseInt(perPageEl.value, 10) : 10;
    var curPage = 1;

    // Status multi-select state management
    var selectedStatuses = new Set(['ALL']);

    function updateStatusPillStyles() {
        document.querySelectorAll('#statusFilterGroup .status-pill').forEach(function(pill) {
            var st = pill.dataset.status;
            if (selectedStatuses.has(st)) {
                pill.classList.add('active');
                if (st === 'ALL') {
                    pill.className = 'btn btn-sm btn-dark py-0 px-2 status-pill active';
                } else if (st === 'Available') {
                    pill.className = 'btn btn-sm btn-success py-0 px-2 status-pill active text-white';
                } else if (st === 'Pending') {
                    pill.className = 'btn btn-sm btn-warning py-0 px-2 status-pill active text-dark';
                } else if (st === 'Issued') {
                    pill.className = 'btn btn-sm btn-info py-0 px-2 status-pill active text-white';
                }
            } else {
                pill.classList.remove('active');
                if (st === 'ALL') {
                    pill.className = 'btn btn-sm btn-outline-dark py-0 px-2 status-pill';
                } else if (st === 'Available') {
                    pill.className = 'btn btn-sm btn-outline-success py-0 px-2 status-pill';
                } else if (st === 'Pending') {
                    pill.className = 'btn btn-sm btn-outline-warning py-0 px-2 status-pill';
                } else if (st === 'Issued') {
                    pill.className = 'btn btn-sm btn-outline-info py-0 px-2 status-pill';
                }
            }
        });
    }

    // Toggle status pill selection
    document.querySelectorAll('#statusFilterGroup .status-pill').forEach(function(pill) {
        pill.addEventListener('click', function() {
            var st = this.dataset.status;
            if (st === 'ALL') {
                selectedStatuses.clear();
                selectedStatuses.add('ALL');
            } else {
                selectedStatuses.delete('ALL');
                if (selectedStatuses.has(st)) {
                    selectedStatuses.delete(st);
                } else {
                    selectedStatuses.add(st);
                }
                if (selectedStatuses.size === 0) {
                    selectedStatuses.add('ALL');
                }
            }
            updateStatusPillStyles();
            curPage = 1;
            render();
        });
    });

    // Initialize Select2 Dropdown for ITGK Selection with Search & Filter
    function initItgkSelect2() {
        if (window.jQuery && jQuery.fn.select2) {
            var $select = jQuery('#filterItgkCode');
            if ($select.length && !$select.hasClass('select2-hidden-accessible')) {
                $select.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '-- Select ITGK Center to View Records --',
                    allowClear: true
                }).on('change', function() {
                    handleItgkSelectChange();
                    curPage = 1;
                    render();
                });
            }
        } else {
            document.getElementById('filterItgkCode')?.addEventListener('change', function() {
                handleItgkSelectChange();
                curPage = 1;
                render();
            });
        }
    }

    if (window.jQuery) {
        jQuery(document).ready(initItgkSelect2);
    } else {
        document.addEventListener('DOMContentLoaded', initItgkSelect2);
    }

    // Handle ITGK Select Dropdown Change & ITGK Details Container Display
    function handleItgkSelectChange() {
        var itgkSelect = document.getElementById('filterItgkCode');
        var detailsContainer = document.getElementById('itgkDetailsContainer');
        if (!itgkSelect || !detailsContainer) return;

        var selectedVal = (itgkSelect.value || '').trim();
        var selectedOpt = itgkSelect.options[itgkSelect.selectedIndex];

        if (selectedVal && selectedVal !== 'ALL' && selectedOpt) {
            document.getElementById('itgkDetailCodeBadge').textContent = 'ITGK CODE: ' + selectedVal;
            document.getElementById('itgkDetailName').textContent = selectedOpt.dataset.name || 'N/A';
            document.getElementById('itgkDetailDistrict').textContent = selectedOpt.dataset.district || 'N/A';
            document.getElementById('itgkDetailEmail').textContent = selectedOpt.dataset.email || 'N/A';
            document.getElementById('itgkDetailMobile').textContent = selectedOpt.dataset.mobile || 'N/A';
            detailsContainer.classList.remove('d-none');
        } else {
            detailsContainer.classList.add('d-none');
        }
    }

    // Reset Filters Handler
    document.getElementById('btnResetFilters')?.addEventListener('click', function() {
        var itgkSelect = document.getElementById('filterItgkCode');
        if (itgkSelect) {
            itgkSelect.value = '';
            if (window.jQuery && jQuery.fn.select2) {
                jQuery('#filterItgkCode').val('').trigger('change');
            }
        }
        var searchInput = document.getElementById('certSearch');
        if (searchInput) searchInput.value = '';
        selectedStatuses.clear();
        selectedStatuses.add('ALL');
        updateStatusPillStyles();
        handleItgkSelectChange();
        curPage = 1;
        render();
    });

    function render() {
        var allRows = getRows();
        var searchInput = document.getElementById('certSearch');
        var q = searchInput ? searchInput.value.toLowerCase().trim() : '';

        var itgkSelect = document.getElementById('filterItgkCode');
        var selectedItgk = itgkSelect ? itgkSelect.value.trim() : '';

        // If no ITGK center is selected yet, show 0 records initially
        if (!selectedItgk) {
            allRows.forEach(function (row) {
                row.style.setProperty('display', 'none', 'important');
                var detailRow = document.querySelector('.cert-detail-row[data-row="' + row.dataset.row + '"]');
                if (detailRow) detailRow.style.setProperty('display', 'none', 'important');
            });
            var visibleCountEl = document.getElementById('certVisibleCount');
            if (visibleCountEl) visibleCountEl.textContent = 0;

            var showingEl = document.getElementById('certShowingText');
            if (showingEl) showingEl.textContent = 'Please select an ITGK Center or "ALL ITGK CENTERS" to display records.';

            var paginationEl = document.getElementById('certPagination');
            if (paginationEl) paginationEl.innerHTML = '';

            if (window._bindCertCheckboxes) window._bindCertCheckboxes();
            return;
        }

        var filtered = allRows.filter(function (r) {
            // 1. Text Search Filter
            if (q && !r.textContent.toLowerCase().includes(q)) {
                return false;
            }

            // 2. ITGK Code Filter
            if (selectedItgk !== 'ALL') {
                var rowItgk = (r.dataset.itgk || '').trim();
                if (!rowItgk) {
                    // Fallback: extract ITGK code from table cell / card header text if data-itgk wasn't set
                    var m = r.textContent.match(/ITGK\s*([0-9]+)/i);
                    if (m) rowItgk = m[1].trim();
                }
                // Robust numeric comparison (e.g. 18580 vs "18580")
                if (rowItgk !== selectedItgk && parseInt(rowItgk, 10) !== parseInt(selectedItgk, 10)) {
                    return false;
                }
            }

            // 3. Status Filter (Multi-select)
            if (!selectedStatuses.has('ALL')) {
                var rowStatus = (r.dataset.status || '').trim().toLowerCase();
                var matches = false;
                selectedStatuses.forEach(function(s) {
                    var target = s.toLowerCase();
                    if (rowStatus === target || (target === 'issued' && rowStatus.includes('issued'))) {
                        matches = true;
                    }
                });
                if (!matches) return false;
            }

            return true;
        });

        var total = filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / perPage));
        curPage = Math.min(curPage, totalPages);
        var start = (curPage - 1) * perPage;
        var end = Math.min(start + perPage, total);

        var filteredSet = new Set(filtered);
        var posMap = new Map(filtered.map(function (r, i) { return [r, i]; }));

        allRows.forEach(function (row) {
            var detailRow = document.querySelector('.cert-detail-row[data-row="' + row.dataset.row + '"]');
            var isMobileCard = row.classList.contains('cert-mobile-card');
            
            if (!filteredSet.has(row)) {
                row.style.setProperty('display', 'none', 'important');
                if (detailRow) detailRow.style.setProperty('display', 'none', 'important');
                return;
            }
            var pos = posMap.get(row);
            var show = pos >= start && pos < end;
            if (show) {
                if (isMobileCard) {
                    row.style.removeProperty('display');
                } else {
                    row.style.removeProperty('display');
                }
            } else {
                row.style.setProperty('display', 'none', 'important');
            }
            if (detailRow && !show) {
                detailRow.style.setProperty('display', 'none', 'important');
            }
        });

        // Visible count badges
        var visibleCountEl = document.getElementById('certVisibleCount');
        if (visibleCountEl) visibleCountEl.textContent = total;

        var showingEl = document.getElementById('certShowingText');
        if (showingEl) {
            showingEl.textContent = total > 0 ?
                'Showing ' + (start + 1) + '-' + end + ' of ' + total + ' records' :
                'No records to display';
        }

        // Build Pagination HTML
        var paginationEl = document.getElementById('certPagination');
        if (paginationEl) {
            if (totalPages <= 1) {
                paginationEl.innerHTML = '';
            } else {
                var html = '<ul class="pagination pagination-sm mb-0">';
                html += '<li class="page-item ' + (curPage === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (curPage - 1) + '">&laquo;</a></li>';
                for (var p = 1; p <= totalPages; p++) {
                    if (p === 1 || p === totalPages || (p >= curPage - 2 && p <= curPage + 2)) {
                        html += '<li class="page-item ' + (p === curPage ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                    } else if (p === curPage - 3 || p === curPage + 3) {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                html += '<li class="page-item ' + (curPage === totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (curPage + 1) + '">&raquo;</a></li>';
                html += '</ul>';
                paginationEl.innerHTML = html;
            }
        }

        if (window._bindCertCheckboxes) {
            window._bindCertCheckboxes();
        }
    }

    // Search input listener
    document.getElementById('certSearch')?.addEventListener('input', function () {
        curPage = 1;
        render();
    });

    // Per page listener
    document.getElementById('certPerPage')?.addEventListener('change', function () {
        perPage = parseInt(this.value, 10) || 10;
        curPage = 1;
        render();
    });

    // Pagination click listener
    document.getElementById('certPagination')?.addEventListener('click', function (e) {
        var link = e.target.closest('a.page-link');
        if (!link) return;
        e.preventDefault();
        var p = parseInt(link.dataset.page, 10);
        if (p && p !== curPage) {
            curPage = p;
            render();
        }
    });

    // Initial render
    render();
    window._renderCertPagination = render;
})();
