<?php
// 404 Error Page - Page Not Found
if (!defined('BASE_URL')) define('BASE_URL', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | SoftSam Portal</title>
    <!-- Local vendor assets (downloaded from CDN) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="error-card p-5">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
                    <h2 class="fw-bold">404 - Page Not Found</h2>
                    <p class="text-muted mb-4">The page you are looking for does not exist.</p>
                    <a href="<?= BASE_URL ?>" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>