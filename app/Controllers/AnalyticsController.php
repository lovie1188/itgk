<?php

/**
 * AnalyticsController - Analytics Dashboard Controller
 * 
 * Handles analytics and reporting functionality
 * with charts and statistics.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Certificate;
use App\Models\LearnerResult;

/**
 * AnalyticsController Class
 * 
 * Controller for displaying analytics and reports.
 */
class AnalyticsController extends BaseController
{
    /**
     * Display analytics dashboard
     * 
     * @return void
     */
    public function index(): void
    {
        // Require authentication
        $this->requireAuth();

        // Initialize models
        $certModel = new Certificate();
        $learnerModel = new LearnerResult();

        // Get counts
        $totalCertificates = $certModel->count();
        $totalLearners = $learnerModel->count();

        // Get analytics data
        $certAnalytics = $certModel->getAnalytics();
        $learnerAnalytics = $learnerModel->getAnalytics();

        // Real monthly aggregation from database
        $certMonthly = $certModel->getMonthlyStats(6);
        $learnerMonthly = $learnerModel->getMonthlyStats(6);

        // Build parallel month/label arrays (fill missing months with 0)
        $months = [];
        $certData = [];
        $learnerData = [];
        for ($i = 5; $i >= 0; $i--) {
            $label = date('M', strtotime("-$i months"));
            $months[] = $label;
            
            $foundCert = null;
            foreach ($certMonthly as $r) {
                if (($r['month'] ?? '') === $label) {
                    $foundCert = $r;
                    break;
                }
            }

            $foundLearner = null;
            foreach ($learnerMonthly as $r) {
                if (($r['month'] ?? '') === $label) {
                    $foundLearner = $r;
                    break;
                }
            }

            $certData[] = $foundCert ? (int)$foundCert['count'] : 0;
            $learnerData[] = $foundLearner ? (int)$foundLearner['count'] : 0;
        }

        $data = [
            'totalCertificates' => $totalCertificates,
            'totalLearners' => $totalLearners,
            'certAnalytics' => $certAnalytics,
            'learnerAnalytics' => $learnerAnalytics,
            'months' => $months,
            'certData' => $certData,
            'learnerData' => $learnerData,
            'title' => 'Analytics | SoftSam Portal',
            'view' => 'pages/analytics'
        ];

        $this->view('pages/analytics', $data);
    }
}
