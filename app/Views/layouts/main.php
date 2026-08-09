<?php
// Shared layout wrapper - define BASE_URL guard in case served outside index.php front-controller
if (!defined('BASE_URL'))
    define('BASE_URL', '/');

// Prepare variables for partials
$navbarRole = $role ?? $_SESSION['role'] ?? 'GUEST';
$navbarName = $name ?? ($_SESSION['user']['first_name'] ?? 'User');
$navbarCsrf = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- PWA Capabilities -->
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/img/icon-192.png">

    <!-- Local vendor assets (downloaded from CDN) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/select2/select2.min.css">
    <link rel="stylesheet"
        href="<?= BASE_URL ?>assets/vendor/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <?= $extraCss ?? '' ?>
</head>

<body>
    <?php
    // Include navbar partial
    $navbarPath = __DIR__ . '/../partials/navbar.php';
    if (file_exists($navbarPath)):
        require $navbarPath;
    endif;
    ?>

    <div class="container-fluid p-0 mb-5">
        <?= $content ?? '' ?>
    </div>

    <?php
    // Include footer partial
    $footerPath = __DIR__ . '/../partials/footer.php';
    if (file_exists($footerPath)):
        require $footerPath;
    endif;

    // Include mobile bottom nav partial
    $bottomNavPath = __DIR__ . '/../partials/bottom_nav.php';
    if (file_exists($bottomNavPath)):
        require $bottomNavPath;
    endif;

    // Include loader partial
    $loaderPath = __DIR__ . '/../partials/loader.php';
    if (file_exists($loaderPath)):
        require $loaderPath;
    endif;
    ?>

    <?= $extraJs ?? '' ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('<?= BASE_URL ?>sw.js').then(function (reg) {
                    console.log('PWA ServiceWorker registered with scope:', reg.scope);
                }).catch(function (err) {
                    console.log('PWA ServiceWorker registration failed:', err);
                });
            });
        }
    </script>
</body>

</html>