<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Transaction - SoftSam Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .error-card { max-width: 500px; width: 100%; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 30px; text-align: center; border-top: 4px solid #ef4444; }
        .error-icon { font-size: 60px; color: #ef4444; margin-bottom: 20px; }
        .error-title { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .error-desc { color: #64748b; font-size: 15px; margin-bottom: 20px; }
        .txn-badge { background: #fef2f2; color: #991b1b; padding: 6px 12px; border-radius: 6px; font-family: monospace; font-size: 14px; font-weight: bold; display: inline-block; margin-bottom: 20px; border: 1px solid #fecaca; }
        .btn-home { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-block; transition: background 0.2s; }
        .btn-home:hover { background: #1d4ed8; color: #fff; }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <div class="error-title">Transaction Not Found</div>
        <div class="error-desc">
            We could not verify this transaction. The transaction ID might be invalid, expired, or the certificates have not been officially issued yet.
        </div>
        <?php if (!empty($txnId)): ?>
            <div class="txn-badge">ID: <?= htmlspecialchars($txnId) ?></div>
        <?php endif; ?>
        <div>
            <a href="<?= getenv('APP_URL') ?: 'http://localhost/certificate' ?>" class="btn-home">
                <i class="fas fa-home me-2"></i> Go to Homepage
            </a>
        </div>
    </div>
</body>
</html>
