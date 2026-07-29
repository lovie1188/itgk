</div> <!-- end container -->

<?php
// Footer partial for MVC
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../Helpers/Env.php';
    App\Helpers\Env::load(__DIR__ . '/../../.env');
    $_bf = getenv('BASE_URL');
    if (!$_bf || !is_string($_bf)) $_bf = '/';
    define('BASE_URL', $_bf);
}
?>

<!-- Footer partial for MVC -->
<footer class="text-center fixed-footer py-2 mt-auto small text-muted border-top border-success">
    <div class="d-flex justify-content-center align-items-center">
        <span>© 2025 Softech Multi Service Pvt. Ltd. All rights reserved.</span>
        <button class="btn btn-sm btn-outline-primary ms-2" id="toggleNavBtn" title="Toggle Navigation">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>
    <div class="mt-1">
        Powered by <a href="https://rakashaeservices.co.in" target="_blank">Rakasha E Services</a>
    </div>
</footer>

<!-- Modern Mobile Bottom Nav -->
<div class="mobile-nav-modern">
    <a href="<?php echo BASE_URL; ?>index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-home fa-lg"></i><br><small>Home</small>
    </a>
    <a href="<?php echo BASE_URL; ?>itgk_certificate.php" class="<?= basename($_SERVER['PHP_SELF']) == 'itgk_certificate.php' ? 'active' : '' ?>">
        <i class="fas fa-certificate fa-lg"></i><br><small>ITGK</small>
    </a>
    <a href="<?php echo BASE_URL; ?>learner_result.php" class="<?= basename($_SERVER['PHP_SELF']) == 'learner_result.php' ? 'active' : '' ?>">
        <i class="fas fa-graduation-cap fa-lg"></i><br><small>Learner</small>
    </a>
    <?php if (($_SESSION['role'] ?? 'EMPLOYEE') === 'SUPERADMIN'): ?>
        <a href="<?php echo BASE_URL; ?>upload/uploaddata.php" class="<?= strpos($_SERVER['PHP_SELF'], 'upload/') !== false ? 'active' : '' ?>">
            <i class="fas fa-cloud-upload-alt fa-lg"></i><br><small>Upload</small>
        </a>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
        <i class="fas fa-user fa-lg"></i><br><small>Profile</small>
    </a>
</div>


<!-- Scripts -->
</div>
<!-- Local vendor assets (downloaded from CDN) -->
<script src="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/vendor/axios-0.21.1.min.js"></script>
<script src="<?= BASE_URL ?>assets/vendor/jquery.dataTables-1.11.5.min.js"></script>
<script src="<?= BASE_URL ?>assets/vendor/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>assets/vendor/fontawesome-5.15.4/all.min.js"></script>
<!-- MDB UI Kit removed - ripple effect was disruptive -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.2.0/mdb.min.js"></script> -->
<!-- <script src="/softtechseva/public/assets/js/scripts.js"></script -->

<script>
    // Initialize tooltips
    $(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Initialize popovers
    $(function() {
        $('[data-toggle="popover"]').popover();
    });

    // Toggle mobile navigation
    const toggleNavBtn = document.getElementById('toggleNavBtn');
    if (toggleNavBtn) {
        toggleNavBtn.addEventListener('click', function() {
            const mobileNav = document.querySelector('.mobile-nav-modern');
            const icon = this.querySelector('i');

            if (mobileNav) {
                if (mobileNav.style.display === 'none' || mobileNav.style.display === '') {
                    mobileNav.style.display = 'flex';
                    if (icon) {
                        icon.className = 'fas fa-chevron-down';
                        this.title = 'Hide Navigation';
                    }
                } else {
                    mobileNav.style.display = 'none';
                    if (icon) {
                        icon.className = 'fas fa-chevron-up';
                        this.title = 'Show Navigation';
                    }
                }
            }
        });
    }
</script>