<?php
/**
 * Loader Partial - Global Activity Overlay & Double-Click Protection
 * Compliant with User Rule 4: Always use loader for server activity.
 */
?>
<div id="app-global-loader" class="global-loader-overlay d-none" aria-hidden="true">
    <div class="global-loader-content">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div id="global-loader-text" class="global-loader-text mt-3 fw-semibold text-secondary">
            Processing, please wait...
        </div>
    </div>
</div>

<style>
.global-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.global-loader-content {
    background: #ffffff;
    padding: 1.5rem 2.5rem;
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    text-align: center;
    border: 1px solid rgba(0,0,0,0.05);
}
@media (prefers-color-scheme: dark) {
    .global-loader-overlay {
        background: rgba(15, 23, 42, 0.8);
    }
    .global-loader-content {
        background: #1e293b;
        color: #f8fafc;
        border-color: #334155;
    }
    #global-loader-text {
        color: #cbd5e1 !important;
    }
}
</style>

<script>
window.showLoader = function(msg) {
    var loader = document.getElementById('app-global-loader');
    var text = document.getElementById('global-loader-text');
    if (loader) {
        if (msg && text) text.textContent = msg;
        loader.classList.remove('d-none');
        loader.setAttribute('aria-hidden', 'false');
    }
};

window.hideLoader = function() {
    var loader = document.getElementById('app-global-loader');
    if (loader) {
        loader.classList.add('d-none');
        loader.setAttribute('aria-hidden', 'true');
    }
};

// Global form submit handler to prevent duplicate submissions and display loader
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (form && form.tagName === 'FORM' && !form.hasAttribute('data-no-loader')) {
            window.showLoader('Submitting data...');
            var submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitBtns.forEach(function(btn) {
                btn.disabled = true;
            });
        }
    });
});
</script>
