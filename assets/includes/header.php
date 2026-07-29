<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SoftSam Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Local vendor assets (downloaded from CDN) -->
    <link href="<?= BASE_URL ?? '/certificate/' ?>assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?? '/certificate/' ?>assets/vendor/fontawesome-6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?? '/certificate/' ?>assets/css/style.css">
    <script src="<?= BASE_URL ?? '/certificate/' ?>assets/vendor/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?? '/certificate/' ?>assets/vendor/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?? '/certificate/' ?>assets/js/script.js"></script>

    <style>
        /* Modern navbar styling */
        .navbar-modern {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            transition: all 0.15s ease-in-out;
            position: relative;
        }

        .nav-link:hover {
            color: #3b82f6 !important;
        }

        .nav-link.active {
            background: #eff6ff;
            border-radius: 6px;
            color: #3b82f6 !important;
        }


        @media (max-width: 768px) {
            .mobile-nav-modern {
                display: flex;
                justify-content: space-around;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 12px 0;
                z-index: 1000;
            }

            .navbar-toggler {
                border: none;
                background: rgba(255, 255, 255, 0.1);
            }

            .navbar-toggler:focus {
                box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
            }
        }

        /* Content wrapper with modern background */
        .content-wrapper {
            min-height: calc(100vh - 200px);
            padding: 20px 0;
        }
    </style>
</head>

<body>
    <?php
    // Include helper functions
    require_once dirname(__DIR__, 2) . '/config/auth.php';
    // Include CSRF Helper
    require_once dirname(__DIR__, 2) . '/app/Helpers/Csrf.php';
    require_once dirname(__DIR__, 2) . '/app/Helpers/functions.php';
    
    // Get Flash Message if any
    $flash = getFlashMessage();
    if ($flash) {
        if ($flash['type'] === 'success') {
            $successMessage = $flash['message'];
        } else {
            $errorMessage = $flash['message'];
        }
    }
    ?>

    <!-- Success/Error Messages -->
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-modern alert-success position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-check-circle me-2"></i><?= $successMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-modern alert-danger position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $errorMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Modern Navbar -->
    <!-- Modern Navbar -->
    <?php require_once __DIR__ . '/navbar.php'; ?>

    <!-- Slide-in Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="sidebar-title">
                <i class="fas fa-bars me-2"></i>Menu
            </h5>
            <button class="btn-close-sidebar" id="closeSidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sidebar-body">
            <nav class="sidebar-nav">
                <a href="<?php echo BASE_URL; ?>index.php" class="sidebar-link">
                    <i class="fas fa-home me-3"></i>Home
                </a>
                <a href="<?php echo BASE_URL; ?>itgk_certificate.php" class="sidebar-link">
                    <i class="fas fa-certificate me-3"></i>ITGK Certificates
                </a>
                <a href="<?php echo BASE_URL; ?>learner_result.php" class="sidebar-link">
                    <i class="fas fa-graduation-cap me-3"></i>Learner Results
                </a>
                <?php if (($_SESSION['role'] ?? 'EMPLOYEE') === 'SUPERADMIN'): ?>
                    <a href="<?php echo BASE_URL; ?>upload/uploaddata.php" class="sidebar-link">
                        <i class="fas fa-cloud-upload-alt me-3"></i>Upload Data
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>profile.php" class="sidebar-link">
                    <i class="fas fa-user me-3"></i>Profile
                </a>
                <hr class="sidebar-divider">
                <a href="<?php echo BASE_URL; ?>logout.php" class="sidebar-link text-danger">
                    <i class="fas fa-sign-out-alt me-3"></i>Logout
                </a>
            </nav>
        </div>
    </div>
    <!-- Offcanvas Components -->
    <!-- ITGK Certificate Add Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="addItgkOffcanvas" aria-labelledby="addItgkOffcanvasLabel">
        <div class="offcanvas-header bg-primary text-white">
            <h5 class="offcanvas-title" id="addItgkOffcanvasLabel">
                <i class="fas fa-plus-circle me-2"></i>Add ITGK Certificate
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" id="addItgkForm" action="actions/add_certificate.php">
                <?php Csrf::field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Course Name</label>
                        <input name="course_name" class="form-control-modern" placeholder="Enter course name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Receiving Date</label>
                        <input type="date" name="receiving_date" class="form-control-modern" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Exam Name</label>
                        <input name="exam_name" class="form-control-modern" placeholder="Enter exam name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Exam Date</label>
                        <input type="date" name="exam_date" class="form-control-modern" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">ITGK Code</label>
                        <input name="itgk_code" class="form-control-modern" placeholder="ITGK code" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">District</label>
                        <input name="district" class="form-control-modern" placeholder="District">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Packet No</label>
                        <input name="packet_no" class="form-control-modern" placeholder="Packet number">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Pass</label>
                        <input type="number" name="pass" class="form-control-modern" placeholder="0" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fail</label>
                        <input type="number" name="fail" class="form-control-modern" placeholder="0" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Absent</label>
                        <input type="number" name="absent" class="form-control-modern" placeholder="0" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">UFM</label>
                        <input type="number" name="ufm" class="form-control-modern" placeholder="0" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Certificate No From</label>
                        <input name="cert_no_from" class="form-control-modern" placeholder="Starting cert number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Certificate No To</label>
                        <input name="cert_no_to" class="form-control-modern" placeholder="Ending cert number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-control-modern">
                            <option value="Not Received">Not Received</option>
                            <option value="Available">Available</option>
                            <option value="Issued">Issued</option>
                            <option value="InTransit">In Transit</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Current Location</label>
                        <input name="current_location" class="form-control-modern" placeholder="Current location">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Grand Total</label>
                        <input type="number" name="grand_total" class="form-control-modern" placeholder="Total certificates" min="0">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_itgk" class="btn btn-modern w-100">
                            <i class="fas fa-save me-2"></i>Save Certificate
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Learner Certificate Add Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="addLearnerOffcanvas" aria-labelledby="addLearnerOffcanvasLabel">
        <div class="offcanvas-header bg-success text-white">
            <h5 class="offcanvas-title" id="addLearnerOffcanvasLabel">
                <i class="fas fa-plus-circle me-2"></i>Add Learner Result
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" id="addLearnerForm" action="actions/add_learner_result.php">
                <?php Csrf::field(); ?>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">S No.</label>
                        <input type="number" name="s_no" class="form-control-modern" placeholder="S No.">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Receiving Date</label>
                        <input type="date" name="receiving_date" class="form-control-modern">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">ITGK Code</label>
                        <input name="itgk_code" class="form-control-modern" placeholder="ITGK Code" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Learner Code</label>
                        <input name="learner_code" class="form-control-modern" placeholder="Learner Code">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Learner Name</label>
                        <input name="learner_name" class="form-control-modern" placeholder="Learner Name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Father Name</label>
                        <input name="father_name" class="form-control-modern" placeholder="Father Name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Total Marks</label>
                        <input type="number" step="0.01" name="total_marks" class="form-control-modern" placeholder="Total Marks">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Marks Obtained</label>
                        <input type="number" step="0.01" name="marks_obtained" class="form-control-modern" placeholder="Marks Obtained">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Percentage</label>
                        <input type="number" step="0.01" name="percentage" class="form-control-modern" placeholder="%">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Result</label>
                        <select name="result" class="form-control-modern" required>
                            <option value="PASS">PASS</option>
                            <option value="FAIL">FAIL</option>
                            <option value="ABSENT">ABSENT</option>
                            <option value="UFM">UFM</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Certificate No</label>
                        <input name="certificate_no" class="form-control-modern" placeholder="Certificate No">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Course Name</label>
                        <input name="course_name" class="form-control-modern" placeholder="Course Name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Exam Name</label>
                        <input name="exam_name" class="form-control-modern" placeholder="Exam Name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Exam Date</label>
                        <input type="date" name="exam_date" class="form-control-modern">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-control-modern">
                            <option value="Not Received">Not Received</option>
                            <option value="Available">Available</option>
                            <option value="Issued">Issued</option>
                            <option value="InTransit">In Transit</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Remark</label>
                        <textarea name="remark" class="form-control-modern" rows="3" placeholder="Remark (use SP/ITGK keywords)"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_learner" class="btn btn-modern w-100">
                            <i class="fas fa-save me-2"></i>Save Learner Result
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-wrapper py-3">






















        <!-- Navbar --
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SOFTTECH SEVA</a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="itgk_certificate.php">ITGK</a></li>
                    <li class="nav-item"><a class="nav-link" href="learner_result.php">Learner</a></li>
                    <!-- Profile Dropdown --
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Profile
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    ---->