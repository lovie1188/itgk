<?php

/**
 * API AnalyticsController - Returns analytics data
 * 
 * @package App\Controllers\Api
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;

use App\Models\Certificate;
use App\Models\LearnerResult;

class AnalyticsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    /**
     * Get analytics data
     * 
     * @return void
     */
    public function index(): void
    {
        $certModel = new Certificate();
        $learnerModel = new LearnerResult();

        $certAnalytics = $certModel->getAnalytics();
        $learnerAnalytics = $learnerModel->getAnalytics();
        $certMonthly = $certModel->getMonthlyStats(6);
        $learnerMonthly = $learnerModel->getMonthlyStats(6);

        $this->json([
            'success' => true,
            'data' => [
                'certificates' => [
                    'total' => $certModel->count(),
                    'summary' => $certAnalytics,
                    'monthly' => $certMonthly
                ],
                'learners' => [
                    'total' => $learnerModel->count(),
                    'summary' => $learnerAnalytics,
                    'monthly' => $learnerMonthly
                ]
            ]
        ]);
    }
}