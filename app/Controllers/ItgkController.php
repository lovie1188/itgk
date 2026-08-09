<?php

/**
 * ItgkController - ITGK Center Details & Master Directory Controller
 *
 * Provides comprehensive ITGK Details module operations.
 *
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GoogleSheetService;
use App\Helpers\Logger;

class ItgkController extends BaseController
{
    /**
     * Display ITGK Details Master Directory
     */
    public function index(): void
    {
        $this->requireAuth();

        $itgkList = [];
        $error = null;

        try {
            $sheetService = new GoogleSheetService();
            
            // 1. Fetch ITGK Master Sheet (Master records)
            $itgkMasterId    = $sheetService->getItgkMasterSheetId();
            $itgkMasterRange = $sheetService->getItgkMasterRange();
            $itgkMasterData  = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
            $itgkMasterRows  = $itgkMasterData['rows'] ?? [];

            // 2. Fetch ITGK 2026 Sheet (Current active status sheet)
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
                Logger::warn('Failed to fetch ITGK 2026 current status sheet', ['error' => $ex26->getMessage()]);
            }

            // 3. Process Master rows & mark status (Active vs Expired)
            foreach ($itgkMasterRows as $idx => $ir) {
                $code = trim((string)($ir['ITGK-CODE'] ?? $ir['ITGK CODE'] ?? $ir['ITGK_CODE'] ?? ''));

                // Skip merged message/note rows where ITGK CODE is empty
                if ($code === '' || !preg_match('/^\d+$/', $code)) {
                    continue;
                }

                $name   = trim((string)($ir['ITGK Name']      ?? $ir['ITGK NAME']      ?? ''));
                $dist   = trim((string)($ir['ITGK District']  ?? $ir['DISTRICT']       ?? $ir['District']  ?? ''));
                $email  = trim((string)($ir['ITGK Email']     ?? $ir['Email']          ?? $ir['EMAIL']     ?? $ir['E-Mail'] ?? ''));
                $mobile = trim((string)($ir['ITGK Mobile']    ?? $ir['Mobile']         ?? $ir['MOBILE']    ?? $ir['Phone'] ?? $ir['Contact'] ?? ''));
                $address= trim((string)($ir['ITGK Address']   ?? $ir['Address']        ?? $ir['ADDRESS']   ?? ''));

                $tehsil = trim((string)($ir['ITGK Tehsil']    ?? $ir['Tehsil']         ?? $ir['TEHSIL']    ?? ''));

                if ($code !== '') {
                    // Business Rule: If record (ITGK CODE) is NOT in itgk_2026 current status sheet, treat as Expired
                    $isCurrentActive = isset($active2026Codes[strtolower($code)]);
                    $status = $isCurrentActive ? 'Active' : 'Expired';

                    $itgkList[] = [
                        'id'       => $idx + 1,
                        'code'     => $code,
                        'name'     => $name ?: 'ITGK Center ' . $code,
                        'district' => $dist ?: '-',
                        'tehsil'   => $tehsil ?: '-',
                        'email'    => $email ?: '-',
                        'mobile'   => $mobile ?: '-',
                        'address'  => $address ?: '-',
                        'status'   => $status,
                    ];
                }
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch ITGK Master details', ['error' => $e->getMessage()]);
            $error = $e->getMessage();
        }

        // Extract unique options for filter dropdowns
        $districtOptions = array_values(array_filter(array_unique(array_column($itgkList, 'district'))));
        sort($districtOptions);

        // Read GET search and filter query params
        $search   = strtolower(trim((string)($_GET['search']   ?? '')));
        $code     = strtolower(trim((string)($_GET['code']     ?? '')));
        $name     = strtolower(trim((string)($_GET['name']     ?? '')));
        $district = strtolower(trim((string)($_GET['district'] ?? '')));
        $mobile   = strtolower(trim((string)($_GET['mobile']   ?? '')));
        $email    = strtolower(trim((string)($_GET['email']    ?? '')));
        $status   = strtolower(trim((string)($_GET['status']   ?? '')));

        $filteredList = array_filter($itgkList, function ($item) use ($search, $code, $name, $district, $mobile, $email, $status) {
            if ($code !== '' && !str_contains(strtolower((string)$item['code']), $code)) {
                return false;
            }
            if ($name !== '' && !str_contains(strtolower((string)$item['name']), $name)) {
                return false;
            }
            if ($district !== '' && strcasecmp((string)$item['district'], $district) !== 0) {
                return false;
            }
            if ($mobile !== '' && !str_contains(strtolower((string)$item['mobile']), $mobile)) {
                return false;
            }
            if ($email !== '' && !str_contains(strtolower((string)$item['email']), $email)) {
                return false;
            }
            if ($status !== '' && strcasecmp((string)$item['status'], $status) !== 0) {
                return false;
            }

            if ($search !== '') {
                $codeMatch   = str_contains(strtolower((string)$item['code']), $search);
                $nameMatch   = str_contains(strtolower((string)$item['name']), $search);
                $distMatch   = str_contains(strtolower((string)$item['district']), $search);
                $mobMatch    = str_contains(strtolower((string)$item['mobile']), $search);
                $emailMatch  = str_contains(strtolower((string)$item['email']), $search);

                if (!$codeMatch && !$nameMatch && !$distMatch && !$mobMatch && !$emailMatch) {
                    return false;
                }
            }

            return true;
        });

        $filteredList = array_values($filteredList);

        $analytics = [
            'total'     => count($itgkList),
            'active'    => count(array_filter($itgkList, fn($i) => $i['status'] === 'Active')),
            'expired'   => count(array_filter($itgkList, fn($i) => $i['status'] === 'Expired')),
            'districts' => count(array_unique(array_filter(array_column($itgkList, 'district')))),
            'filtered'  => count($filteredList),
        ];

        $this->view('pages/itgk/details', [
            'title'           => 'ITGK Center Details | ITGK Management System',
            'itgkList'        => $itgkList,
            'analytics'       => $analytics,
            'districtOptions' => $districtOptions,
            'filters'         => [
                'search'   => $_GET['search']   ?? '',
                'code'     => $_GET['code']     ?? '',
                'name'     => $_GET['name']     ?? '',
                'district' => $_GET['district'] ?? '',
                'mobile'   => $_GET['mobile']   ?? '',
                'email'    => $_GET['email']    ?? '',
                'status'   => $_GET['status']   ?? '',
            ],
            'error'           => $error,
        ]);
    }

    /**
     * Display ITGK Wise Admissions for Each Month
     */
    public function admissions(): void
    {
        $this->requireAuth();

        $admissionsList = [];
        $error = null;

        try {
            $sheetService = new GoogleSheetService();

            // 1. Fetch ADMISSIONS sheet
            $admissionsId    = $sheetService->getAdmissionsSheetId();
            $admissionsRange = $sheetService->getAdmissionsRange();
            $admissionsData  = $sheetService->fetchParsedSheet($admissionsId, $admissionsRange);
            $admissionsRows  = $admissionsData['rows'] ?? [];

            foreach ($admissionsRows as $idx => $row) {
                $year     = trim((string)($row['Year'] ?? ''));
                $key      = trim((string)($row['Key'] ?? ''));
                $sno      = trim((string)($row['Sno.'] ?? ''));
                $code     = trim((string)($row['Center Code'] ?? $row['ITGK'] ?? $row['ITGK CODE'] ?? $row['Code'] ?? $row['code'] ?? ''));
                $name     = trim((string)($row['ITGK NAME'] ?? $row['ITGK Name'] ?? $row['ITGK_Name'] ?? $row['Name'] ?? $row['name'] ?? ''));
                $batch    = trim((string)($row['BATCH'] ?? $row['Batch'] ?? $row['batch'] ?? $row['Month'] ?? $row['month'] ?? ''));
                $course   = trim((string)($row['COURSE'] ?? $row['Course'] ?? $row['course'] ?? ''));
                $syllabus = trim((string)($row['Syllabus'] ?? $row['Syllabus'] ?? ''));
                $totalUploaded = trim((string)($row['Total Uploaded'] ?? $row['total_uploaded'] ?? ''));
                // Column L is index 11 in raw sheet array (A=0, B=1, C=2, D=3, E=4, F=5, G=6, H=7, I=8, J=9, K=10, L=11)
                $totalConfirm  = trim((string)($row[11] ?? $row['Total Confirm'] ?? $row['total_confirm'] ?? $row['Total Confirm Student'] ?? '0'));
                $bookIssueStatus = trim((string)($row['Book Issue Status'] ?? $row['book_issue_status'] ?? ''));

                if ($code === '' && $batch === '') {
                    continue;
                }

                $admissionsList[] = [
                    'id'              => $idx + 1,
                    'year'            => $year ?: '-',
                    'key'             => $key ?: '-',
                    'sno'             => $sno ?: '-',
                    'code'            => $code ?: '-',
                    'name'            => $name ?: 'ITGK Center ' . $code,
                    'batch'           => $batch ?: '-',
                    'course'          => $course ?: '-',
                    'syllabus'        => $syllabus ?: '-',
                    'total_uploaded'  => $totalUploaded ?: '0',
                    'total_confirm'   => $totalConfirm ?: '0',
                    'book_issue_status' => $bookIssueStatus ?: '-',
                    'raw'             => $row,
                ];
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch ITGK Admissions data', ['error' => $e->getMessage()]);
            $error = $e->getMessage();
        }

        // Extract unique options for filter dropdowns
        $batchOptions = array_values(array_filter(array_unique(array_column($admissionsList, 'batch'))));
        sort($batchOptions);

        $yearOptions = array_values(array_filter(array_unique(array_column($admissionsList, 'year'))));
        rsort($yearOptions);

        $codeOptions = array_values(array_filter(array_unique(array_column($admissionsList, 'code'))));
        sort($codeOptions);

        $courseOptions = array_values(array_filter(array_unique(array_column($admissionsList, 'course'))));
        sort($courseOptions);

        // Sort records newest first (last records on top)
        $admissionsList = array_values(array_reverse($admissionsList));

        // Read GET search and filter query params
        $search   = strtolower(trim((string)($_GET['search']   ?? '')));
        $batch    = strtolower(trim((string)($_GET['batch']    ?? '')));
        $year     = strtolower(trim((string)($_GET['year']     ?? '')));
        $code     = strtolower(trim((string)($_GET['code']     ?? '')));
        $course   = strtolower(trim((string)($_GET['course']   ?? '')));

        $totalConfirmCount = 0;
        foreach ($admissionsList as $item) {
            $totalConfirmCount += (int)($item['total_confirm'] ?? 0);
        }

        $analytics = [
            'total'         => count($admissionsList),
            'total_confirm' => $totalConfirmCount,
            'batches'       => count(array_unique(array_filter(array_column($admissionsList, 'batch')))),
            'centers'       => count(array_unique(array_filter(array_column($admissionsList, 'code')))),
            'filtered'      => count($admissionsList),
        ];

        $this->view('pages/itgk/admissions', [
            'title'           => 'ITGK Admissions | ITGK Management System',
            'admissionsList'  => $admissionsList,
            'analytics'       => $analytics,
            'batchOptions'    => $batchOptions,
            'yearOptions'     => $yearOptions,
            'codeOptions'     => $codeOptions,
            'courseOptions'   => $courseOptions,
            'filters'         => [
                'search' => $_GET['search'] ?? '',
                'batch'  => $_GET['batch']  ?? '',
                'year'   => $_GET['year']   ?? '',
                'code'   => $_GET['code']   ?? '',
                'course' => $_GET['course'] ?? '',
            ],
            'error'           => $error,
        ]);
    }

    /**
     * Display ITGK Document Formats & Templates
     */
    public function formats(): void
    {
        $this->requireAuth();

        $formatsList = [];
        $error = null;

        try {
            $sheetService = new GoogleSheetService();

            // Fetch ITGK Master data for format context
            $itgkMasterId    = $sheetService->getItgkMasterSheetId();
            $itgkMasterRange = $sheetService->getItgkMasterRange();
            $itgkMasterData  = $sheetService->fetchParsedSheet($itgkMasterId, $itgkMasterRange);
            $itgkMasterRows  = $itgkMasterData['rows'] ?? [];

            foreach ($itgkMasterRows as $idx => $ir) {
                $code = trim((string)($ir['ITGK-CODE'] ?? $ir['ITGK CODE'] ?? $ir['ITGK_CODE'] ?? ''));
                $name = trim((string)($ir['ITGK Name'] ?? $ir['ITGK NAME'] ?? ''));
                $district = trim((string)($ir['ITGK District'] ?? $ir['DISTRICT'] ?? $ir['District'] ?? ''));

                if ($code === '' || !preg_match('/^\d+$/', $code)) {
                    continue;
                }

                $formatsList[] = [
                    'id'        => $idx + 1,
                    'code'      => $code,
                    'name'      => $name ?: 'ITGK Center ' . $code,
                    'district'  => $district ?: '-',
                ];
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch ITGK Formats data', ['error' => $e->getMessage()]);
            $error = $e->getMessage();
        }

        $analytics = [
            'total'     => count($formatsList),
            'districts' => count(array_unique(array_filter(array_column($formatsList, 'district')))),
        ];

        $this->view('pages/itgk/formats', [
            'title'          => 'ITGK Formats | ITGK Management System',
            'formatsList'    => $formatsList,
            'analytics'      => $analytics,
            'error'          => $error,
        ]);
    }
}