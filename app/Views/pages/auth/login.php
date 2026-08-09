<?php
// BASE_URL guard in case served outside index.php front-controller
if (!defined('BASE_URL')) define('BASE_URL', getenv('BASE_URL') ?: '/certificate/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SoftSam Portal</title>
    <!-- Local vendor assets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 0.75rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="container-fluid px-3">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-8 col-md-5 col-lg-4">
                <div class="card login-card border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-certificate fa-2x text-primary mb-2"></i>
                            <h4 class="fw-bold mb-1">SoftSam Portal</h4>
                            <p class="text-muted small mb-0">RS-CIT Certificate & Learner Management</p>
                        </div>

                        <?php if (!empty($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <?= htmlspecialchars($_GET['error']) ?>
                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Standard Local Login Form -->
                        <form id="loginForm" method="POST" action="<?= BASE_URL ?>api/login.php">
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Username or Email</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" name="username" class="form-control" 
                                           placeholder="Enter username or email" required 
                                           value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Password</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Enter password" required>
                                </div>
                            </div>

                            <button type="submit" id="btnLogin" class="btn btn-primary w-100 btn-sm fw-bold mb-2">
                                <i class="fas fa-sign-in-alt me-1"></i>Sign In
                            </button>
                        </form>

                        <?php if (!empty($firebaseEnabled)): ?>
                        <div class="text-center my-2 text-muted small">OR</div>
                        <button type="button" id="btnFirebaseGoogle" class="btn btn-google w-100 btn-sm mb-2">
                            <i class="fab fa-google me-2" style="color: #4285F4;"></i>Sign in with Google
                        </button>
                        <?php endif; ?>

                        <?php if (!empty($ssoEnabled) && !empty($ssoUrl)): ?>
                        <hr class="my-2">
                        <a href="<?= BASE_URL ?>auth/sso" class="btn btn-outline-primary w-100 btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>Sign in with SoftTech SSO
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Scripts -->
    <script src="<?= BASE_URL ?>assets/vendor/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>assets/vendor/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

    <?php if (!empty($firebaseEnabled) && !empty($firebaseConfig)): ?>
    <!-- Local Firebase Web SDK -->
    <script src="<?= BASE_URL ?>assets/vendor/firebase/firebase-app-compat.js"></script>
    <script src="<?= BASE_URL ?>assets/vendor/firebase/firebase-auth-compat.js"></script>
    <script>
    const firebaseConfig = <?= json_encode($firebaseConfig) ?>;
    if (typeof firebase !== 'undefined' && firebaseConfig && firebaseConfig.apiKey) {
        firebase.initializeApp(firebaseConfig);

        // ── Handle redirect result after Google sign-in returns ──────────
        let isVerifying = false;
        const processFirebaseUser = (user) => {
            if (!user || isVerifying) return;
            isVerifying = true;

            const btnGoogle = document.getElementById('btnFirebaseGoogle');
            if (btnGoogle) {
                btnGoogle.disabled = true;
                btnGoogle.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying Google account...';
            }
            user.getIdToken().then((idToken) => {
                return fetch('<?= BASE_URL ?>auth/firebase-verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_token: idToken })
                });
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.replace(data.redirect || '<?= BASE_URL ?>dashboard');
                } else {
                    isVerifying = false;
                    alert('Authentication Error: ' + (data.message || 'Verification failed'));
                    if (btnGoogle) {
                        btnGoogle.disabled = false;
                        btnGoogle.innerHTML = '<i class="fab fa-google me-2" style="color: #4285F4;"></i>Sign in with Google';
                    }
                }
            })
            .catch(err => {
                isVerifying = false;
                console.error('Firebase verify error:', err);
                if (btnGoogle) {
                    btnGoogle.disabled = false;
                    btnGoogle.innerHTML = '<i class="fab fa-google me-2" style="color: #4285F4;"></i>Sign in with Google';
                }
            });
        };

        firebase.auth().getRedirectResult().then((result) => {
            if (result && result.user) {
                processFirebaseUser(result.user);
            } else {
                firebase.auth().onAuthStateChanged((user) => {
                    if (user) {
                        processFirebaseUser(user);
                    }
                });
            }
        }).catch((err) => {
            if (err.code !== 'auth/no-current-user') {
                console.error('Firebase redirect result error:', err);
            }
        });

        // ── Google Sign-In button: use signInWithPopup ─────────────────────
        const btnGoogle = document.getElementById('btnFirebaseGoogle');
        if (btnGoogle) {
            btnGoogle.addEventListener('click', function() {
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Opening Google...';

                const provider = new firebase.auth.GoogleAuthProvider();
                provider.setCustomParameters({ prompt: 'select_account' });

                firebase.auth().signInWithPopup(provider)
                .then((result) => {
                    if (result && result.user) {
                        processFirebaseUser(result.user);
                    }
                })
                .catch((error) => {
                    console.error('Firebase Google Auth error:', error);
                    if (error.code !== 'auth/popup-closed-by-user') {
                        alert('Google Sign-In Error: ' + error.message);
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fab fa-google me-2" style="color: #4285F4;"></i>Sign in with Google';
                });
            });
        }
    }
    </script>
    <?php endif; ?>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnLogin');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Signing in...';

        const formData = new FormData(this);
        const jsonData = Object.fromEntries(formData);
        
        fetch('<?= BASE_URL ?>api/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(jsonData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Invalid credentials');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt me-1"></i>Sign In';
            }
        })
        .catch(err => {
            alert('Login failed: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt me-1"></i>Sign In';
        });
    });
    </script>
</body>
</html>