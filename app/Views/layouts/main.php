<?php
// Shared layout wrapper - define BASE_URL guard in case served outside index.php front-controller
if (!defined('BASE_URL')) define('BASE_URL', '/');

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
    <title><?= $title ?? 'SoftSam Portal' ?></title>
    <!-- Local vendor assets (downloaded from CDN) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
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
    
    <div class="container-fluid px-2 py-2">
        <?= $content ?? '' ?>
    </div>

    <?php 
    // Include footer partial
    $footerPath = __DIR__ . '/../partials/footer.php';
    if (file_exists($footerPath)): 
        require $footerPath;
    endif; 

    // Include loader partial
    $loaderPath = __DIR__ . '/../partials/loader.php';
    if (file_exists($loaderPath)): 
        require $loaderPath;
    endif; 
    ?>
    
    <?= $extraJs ?? '' ?>
</body>
</html>