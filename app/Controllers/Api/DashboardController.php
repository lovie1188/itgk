<?php

/**
 * API DashboardController - Returns dashboard statistics
 * 
 * @package App\Controllers\Api
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Certificate;
use App\Models\LearnerResult;

class DashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    /**
     * Get dashboard statistics
     * 
     * @return void
     */
    public function index(): void
    {
        $certModel = new Certificate();
        $learnerModel = new LearnerResult();

        $stats = [
            'certificates' => [
                'total' => $certModel->count(),
                'available' => $certModel->count('Available'),
                'issued' => $certModel->count('Issued')
            ],
            'learners' => [
                'total' => $learnerModel->count(),
                'pass' => $learnerModel->countByResult('PASS'),
                'fail' => $learnerModel->countByResult('FAIL')
            ]
        ];

        $this->json(['success' => true, 'data' => $stats]);
    }
}