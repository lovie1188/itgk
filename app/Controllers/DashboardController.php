<?php

/**
 * DashboardController - Main Dashboard Controller
 * 
 * Handles the main dashboard display with statistics
 * and overview information.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Certificate;
use App\Models\LearnerResult;

/**
 * DashboardController Class
 * 
 * Main dashboard controller for displaying application overview.
 */
class DashboardController extends BaseController
{
    /**
     * Display the main dashboard
     * 
     * @return void
     */
    public function index(): void
    {
        // Check if user is authenticated (but don't require it)
        $isAuthenticated = $this->isAuthenticated();

        if ($isAuthenticated) {
            // Gather statistics from Google Sheets via models
            $certModel  = new Certificate();
            $learnerModel = new LearnerResult();

            $certCount   = $certModel->count();
            $learnerCount = $learnerModel->count();

            $data = [
                'totalCertificates' => $certCount,
                'totalLearners' => $learnerCount,
                'role' => $this->getCurrentRole(),
                'view' => 'pages/dashboard',
                'title' => 'Dashboard | SoftSam Portal'
            ];
        } else {
            // Show welcome page for guests
            $data = [
                'totalCertificates' => 0,
                'totalLearners' => 0,
                'role' => 'GUEST',
                'view' => 'pages/dashboard',
                'title' => 'Welcome | SoftSam Portal'
            ];
        }

        // Render dashboard view
        $this->view('pages/dashboard', $data);
    }
}
