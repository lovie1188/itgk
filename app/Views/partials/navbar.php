<?php
// View partial for navbar - MVC compliant
// Include this in layouts/main.php
?>
<nav class="navbar navbar-expand-lg navbar-modern fixed-top">
    <div class="container-fluid">
        <button class="btn btn-sidebar-toggle d-lg-none me-1" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand" href="<?= BASE_URL ?>dashboard">
            <i class="fas fa-certificate me-2"></i>SoftSam Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false || $_SERVER['REQUEST_URI'] == BASE_URL ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <?php if (in_array($role ?? 'GUEST', ['SUPERADMIN', 'ADMIN', 'COORDINATOR', 'PARTNER'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'itgk') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>itgk/list">
                            <i class="fas fa-certificate me-1"></i>ITGK
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'learner') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>learners/list">
                        <i class="fas fa-graduation-cap me-1"></i>Learner
                    </a>
                </li>
                <?php if (($role ?? 'GUEST') === 'SUPERADMIN'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], 'upload') !== false || strpos($_SERVER['REQUEST_URI'], 'setup') !== false || strpos($_SERVER['REQUEST_URI'], 'smtp') !== false ? 'active' : '' ?>" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminMenu">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>data-upload">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>Data Upload
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>smtp-setup">
                                    <i class="fas fa-envelope me-2"></i>SMTP Email Setup
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>setup">
                                    <i class="fas fa-cog me-2"></i>App Setup
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i><?= $name ?? 'User' ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>profile">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
                        <?php if (!empty($_SESSION['sso_login'] ?? null)): ?>
                            <li>
                                <a class="dropdown-item" href="<?= getenv('SSO_URL') ?: 'http://localhost/softtechsso' ?>/dashboard">
                                    <i class="fas fa-external-link-alt me-2"></i>Back to SSO
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>logout">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>