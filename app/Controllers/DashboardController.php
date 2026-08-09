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

            // Calculate ITGK Master vs ITGK 2026 status
            $totalItgkMaster = 0;
            $activeItgk2026  = 0;
            $expiredItgk2026 = 0;

            try {
                $sheetService = new \App\Services\GoogleSheetService();

                // 1. Fetch ITGK Master Sheet
                $itgkMasterId    = $sheetService->getItgkMasterSheetId();
                $itgkMasterRange = $sheetService->getItgkMasterRange();
                $itgkMasterData  = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
                $itgkMasterRows  = $itgkMasterData['rows'] ?? [];

                // 2. Fetch ITGK 2026 Current Status Sheet
                $active2026Codes = [];
                try {
                    $itgk2026Id    = $sheetService->getItgk2026SheetId();
                    $itgk2026Range = $sheetService->getItgk2026Range();
                    $itgk2026Data  = $sheetService->fetchParsedSheet($itgk2026Id, $itgk2026Range);
                    $itgk2026Rows  = $itgk2026Data['rows'] ?? [];

                    foreach ($itgk2026Rows as $r26) {
                        $code26 = trim((string)($r26['ITGK-CODE'] ?? $r26['ITGK CODE'] ?? $r26['ITGK_CODE'] ?? $r26['Code'] ?? ''));
                        if ($code26 !== '') {
                            $active2026Codes[strtolower($code26)] = true;
                        }
                    }
                } catch (\Exception $ex26) {
                    \App\Helpers\Logger::warn('Failed to fetch ITGK 2026 for dashboard', ['error' => $ex26->getMessage()]);
                }

                // 3. Process ITGK Master Rows
                foreach ($itgkMasterRows as $ir) {
                    $code = trim((string)($ir['ITGK-CODE'] ?? $ir['ITGK CODE'] ?? $ir['ITGK_CODE'] ?? ''));

                    // Filter out merged notice/message rows
                    if ($code === '' || !preg_match('/^\d+$/', $code)) {
                        continue;
                    }

                    $totalItgkMaster++;
                    if (isset($active2026Codes[strtolower($code)])) {
                        $activeItgk2026++;
                    } else {
                        $expiredItgk2026++;
                    }
                }
            } catch (\Exception $exItgk) {
                \App\Helpers\Logger::error('Failed to calculate ITGK dashboard stats', ['error' => $exItgk->getMessage()]);
            }

            $data = [
                'totalCertificates' => $certCount,
                'totalLearners'     => $learnerCount,
                'totalItgkMaster'   => $totalItgkMaster,
                'activeItgk2026'    => $activeItgk2026,
                'expiredItgk2026'   => $expiredItgk2026,
                'role'              => $this->getCurrentRole(),
                'view'              => 'pages/dashboard',
                'title'             => 'Dashboard | SoftSam Portal'
            ];
        } else {
            // Show welcome page for guests
            $data = [
                'totalCertificates' => 0,
                'totalLearners'     => 0,
                'totalItgkMaster'   => 0,
                'activeItgk2026'    => 0,
                'expiredItgk2026'   => 0,
                'role'              => 'GUEST',
                'view'              => 'pages/dashboard',
                'title'             => 'Welcome | SoftSam Portal'
            ];
        }

        // Render dashboard view
        $this->view('pages/dashboard', $data);
    }
}
