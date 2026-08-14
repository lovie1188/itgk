<?php

/**
 * LearnerController - Learner Result Management Controller
 * 
 * Handles learner result listing, creation, editing,
 * deletion, and individual certificate issuing.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LearnerResult;
use App\Helpers\Logger;
use App\Services\AuthService;

/**
 * LearnerController Class
 * 
 * Controller for learner result management operations.
 */
class LearnerController extends BaseController
{
    /**
     * LearnerResult model instance
     * @var LearnerResult
     */
    private LearnerResult $learnerModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->learnerModel = new LearnerResult();
    }

    /**
     * Display learner list
     * 
     * @return void
     */
    public function index(): void
    {
        // Require authentication
        $this->requireAuth();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(10, min(500, (int)($_GET['limit'] ?? 100)));

        $allLearners = [];
        try {
            $sheetService = new \App\Services\GoogleSheetService();
            $sheetId = $sheetService->getStudentResultSheetId();
            $sheetRange = $sheetService->getStudentResultRange();
            $sheetData = $sheetService->fetchParsedSheet($sheetId, $sheetRange);
            $rawRows = $sheetData['rows'] ?? [];

            foreach ($rawRows as $idx => $r) {
                $allLearners[] = [
                    'id'             => $idx + 1,
                    's_no'           => $r['S No.'] ?? ($idx + 1),
                    'receiving_date' => $r['Receiving Date'] ?? '',
                    'itgk_code'      => trim((string)($r['ITGK Code'] ?? '')),
                    'learner_code'   => $r['Learner Code'] ?? '',
                    'learner_name'   => $r['Learner Name'] ?? '',
                    'father_name'    => $r['FATHER NAME'] ?? '',
                    'total_marks'    => (int)($r['Total Marks'] ?? 100),
                    'marks_obtained' => (int)($r['Marks Obtained'] ?? 0),
                    'percentage'     => (float)($r['Percentage'] ?? 0),
                    'result'         => $r['Result'] ?? 'PASS',
                    'certificate_no' => $r['Certificate Number'] ?? '',
                    'course_name'    => $r['Course Name'] ?? '',
                    'exam_name'      => $r['Exam Name'] ?? '',
                    'status'         => $r['STATUS'] ?? 'Available',
                    'remark'         => $r['Remark'] ?? ''
                ];
            }
            // Extract unique values for filter dropdowns
            $itgkOptions   = array_values(array_filter(array_unique(array_column($allLearners, 'itgk_code'))));
            $courseOptions = array_values(array_filter(array_unique(array_column($allLearners, 'course_name'))));
            $examOptions   = array_values(array_filter(array_unique(array_column($allLearners, 'exam_name'))));
            sort($itgkOptions);
            sort($courseOptions);
            sort($examOptions);

            // Read filter params from GET
            $filterItgk   = trim((string)($_GET['itgk_code']   ?? ''));
            $filterSearch = trim((string)($_GET['search']      ?? ''));
            $filterCourse = trim((string)($_GET['course_name'] ?? ''));
            $filterExam   = trim((string)($_GET['exam_name']   ?? ''));

            // Filter learners list
            $filteredLearners = array_filter($allLearners, function ($l) use ($filterItgk, $filterSearch, $filterCourse, $filterExam) {
                if ($filterItgk !== '' && strcasecmp((string)$l['itgk_code'], $filterItgk) !== 0) {
                    return false;
                }
                if ($filterCourse !== '' && strcasecmp((string)$l['course_name'], $filterCourse) !== 0) {
                    return false;
                }
                if ($filterExam !== '' && strcasecmp((string)$l['exam_name'], $filterExam) !== 0) {
                    return false;
                }
                if ($filterSearch !== '') {
                    $s = strtolower($filterSearch);
                    $nameMatch   = str_contains(strtolower((string)$l['learner_name']), $s);
                    $codeMatch   = str_contains(strtolower((string)$l['learner_code']), $s);
                    $fatherMatch = str_contains(strtolower((string)$l['father_name']), $s);
                    if (!$nameMatch && !$codeMatch && !$fatherMatch) {
                        return false;
                    }
                }
                return true;
            });

            $learners = array_values($filteredLearners);
        } catch (\Exception $e) {
            Logger::error('Failed to fetch learner results from Google Sheet', ['error' => $e->getMessage()]);
        }

        // Build analytics purely from sheet data — no DB fallback
        $analytics = [];
        if (!empty($allLearners)) {
            $analytics = [
                'total'  => count($allLearners),
                'pass'   => count(array_filter($allLearners, fn($l) => strcasecmp((string)($l['result'] ?? ''), 'PASS')   === 0)),
                'fail'   => count(array_filter($allLearners, fn($l) => strcasecmp((string)($l['result'] ?? ''), 'FAIL')   === 0)),
                'absent' => count(array_filter($allLearners, fn($l) => strcasecmp((string)($l['result'] ?? ''), 'ABSENT') === 0)),
            ];
        }

        $totalCount = count($learners);
        $totalPages = max(1, (int)ceil($totalCount / $limit));
        $page = min($page, $totalPages); // clamp page
        $pagedLearners = array_slice($learners, ($page - 1) * $limit, $limit);

        $data = [
            'learners'      => $pagedLearners,
            'analytics'     => $analytics,
            'itgkOptions'   => $itgkOptions ?? [],
            'courseOptions' => $courseOptions ?? [],
            'examOptions'   => $examOptions ?? [],
            'filters'       => [
                'itgk_code'   => $_GET['itgk_code'] ?? '',
                'search'      => $_GET['search'] ?? '',
                'course_name' => $_GET['course_name'] ?? '',
                'exam_name'   => $_GET['exam_name'] ?? '',
            ],
            'title'         => 'Learner Results | SoftSam Portal',
            'view'          => 'pages/learner/list',
            'currentPage'   => $page,
            'total'         => $totalCount,
            'limit'         => $limit,
            'totalPages'    => $totalPages,
            'sheetTab'      => (new \App\Services\GoogleSheetService())->getStudentResultTab(),
            'baseUrl'       => BASE_URL . 'learners/list',
        ];

        $this->view('pages/learner/list', $data);
    }

    /**
     * Get learner for editing (AJAX)
     * 
     * @return void
     */
    public function edit(): void
    {
        // Require authentication
        $this->requireAuth();

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if (!$id) {
            $this->json(['success' => false, 'message' => 'Invalid ID'], 400);
            return;
        }

        try {
            $learner = $this->learnerModel->find($id);

            if ($learner) {
                $this->json(['success' => true, 'learner' => $learner]);
            } else {
                $this->json(['success' => false, 'message' => 'Learner not found'], 404);
            }
        } catch (\Exception $e) {
            Logger::error('Failed to fetch learner', ['error' => $e->getMessage(), 'id' => $id]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new learner (returns result for both web actions and API)
     * 
     * @param array|null $data Optional data override (uses $_POST if null)
     * @return array Result with success and message
     */
    public function store(?array $data = null): array
    {
        // Role check: COORDINATOR, ADMIN, SUPERADMIN can create learners
        if (!AuthService::hasRoleLevel('COORDINATOR')) {
            Logger::warning('Unauthorized learner creation attempt', [
                'user_id' => AuthService::id()
            ]);
            return ['success' => false, 'message' => 'Unauthorized. COORDINATOR role or higher required.'];
        }

        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        // Use provided data or fall back to POST
        $input = $data ?? $_POST;
        $sanitized = $this->sanitizeLearnerData($input);

        try {
            $id = $this->learnerModel->create($sanitized);

            Logger::info('Learner created', [
                'id' => $id,
                'learner_name' => $sanitized['learner_name'] ?? '',
                'user_id' => AuthService::id()
            ]);

            return ['success' => true, 'id' => $id, 'message' => 'Learner added successfully'];
        } catch (\Exception $e) {
            Logger::error('Failed to create learner', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update learner
     * 
     * @return void
     */
    public function update(): void
    {
        // Require COORDINATOR role or higher
        $this->requireRoleLevel('COORDINATOR');

        // Validate CSRF token
        $this->validateCsrf();

        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $this->json(['success' => false, 'message' => 'Invalid ID'], 400);
            return;
        }

        $data = $this->sanitizeLearnerData($_POST);

        try {
            $this->learnerModel->update($id, $data);

            Logger::info('Learner updated', [
                'id' => $id,
                'user_id' => $this->getCurrentUser()['id'] ?? null
            ]);

            $this->json(['success' => true, 'message' => 'Learner updated successfully']);
        } catch (\Exception $e) {
            Logger::error('Failed to update learner', ['error' => $e->getMessage(), 'id' => $id]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete learners
     * 
     * @return void
     */
    public function delete(): void
    {
        // Require ADMIN role or higher
        $this->requireRoleLevel('ADMIN');

        // Validate CSRF token
        $this->validateCsrf();

        $ids = json_decode($_POST['ids'] ?? '[]', true);

        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'No IDs provided'], 400);
            return;
        }

        try {
            $count = $this->learnerModel->deleteMany($ids);

            Logger::info('Learners deleted', [
                'ids' => $ids,
                'count' => $count,
                'user_id' => $this->getCurrentUser()['id'] ?? null
            ]);

            $this->json(['success' => true, 'deleted_count' => $count]);
        } catch (\Exception $e) {
            Logger::error('Failed to delete learners', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Issue individual certificate
     * 
     * @return void
     */
    public function issueIndividual(): void
    {
        // Require COORDINATOR role or higher
        $this->requireRoleLevel('COORDINATOR');

        // Validate CSRF token
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true);
        $learnerId = (int)($input['learner_id'] ?? 0);
        $receiverData = $input['receiver_data'] ?? [];

        if (!$learnerId || empty($receiverData)) {
            $this->json(['success' => false, 'message' => 'Missing required data'], 400);
            return;
        }

        try {
            $this->learnerModel->issueIndividual($learnerId, $receiverData);

            Logger::info('Individual certificate issued', [
                'learner_id' => $learnerId,
                'receiver' => $receiverData['name'] ?? '',
                'user_id' => $this->getCurrentUser()['id'] ?? null
            ]);

            $this->json(['success' => true, 'message' => 'Certificate Issued to Learner Successfully']);
        } catch (\Exception $e) {
            Logger::error('Failed to issue individual certificate', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Sanitize learner data
     * 
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    private function sanitizeLearnerData(array $input): array
    {
        return [
            's_no' => (int)($input['s_no'] ?? 0),
            'receiving_date' => $input['receiving_date'] ?? null,
            'itgk_code' => htmlspecialchars(trim($input['itgk_code'] ?? '')),
            'learner_code' => htmlspecialchars(trim($input['learner_code'] ?? '')),
            'learner_name' => htmlspecialchars(trim($input['learner_name'] ?? '')),
            'father_name' => htmlspecialchars(trim($input['father_name'] ?? '')),
            'total_marks' => (int)($input['total_marks'] ?? 0),
            'marks_obtained' => (int)($input['marks_obtained'] ?? 0),
            'percentage' => (float)($input['percentage'] ?? 0),
            'result' => htmlspecialchars(trim($input['result'] ?? '')),
            'certificate_no' => htmlspecialchars(trim($input['certificate_no'] ?? '')),
            'course_name' => htmlspecialchars(trim($input['course_name'] ?? '')),
            'exam_name' => htmlspecialchars(trim($input['exam_name'] ?? '')),
            'exam_date' => $input['exam_date'] ?? null,
            'status' => htmlspecialchars(trim($input['status'] ?? 'Not Received')),
            'remark' => htmlspecialchars(trim($input['remark'] ?? '')),
            'created_by' => $this->getCurrentUser()['id'] ?? null
        ];
    }

    /**
     * Validate CSRF token
     * 
     * @return void
     */
    protected function validateCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            exit;
        }
    }

    /**
     * Issue individual learner certificate
     */
    public function issue(): void
    {
        $this->requireRole('SUPERADMIN');
        $this->validateCsrf();

        $input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($input['learner_id'] ?? $input['id'] ?? 0);
        $certNo = trim((string)($input['certificate_no'] ?? ''));
        $email = trim((string)($input['learner_email'] ?? $input['email'] ?? ''));
        $remark = trim((string)($input['remark'] ?? ''));

        if (!$id) {
            $this->json(['success' => false, 'message' => 'Learner ID is required'], 400);
            return;
        }

        try {
            $learner = $this->learnerModel->find($id);
            if (!$learner) {
                $this->json(['success' => false, 'message' => 'Learner record not found'], 404);
                return;
            }

            $this->learnerModel->update($id, [
                'status' => 'Issued',
                'certificate_no' => !empty($certNo) ? $certNo : ($learner['certificate_no'] ?? ''),
                'remark' => $remark
            ]);

            $updatedLearner = $this->learnerModel->find($id);

            if (!empty($email)) {
                try {
                    $emailService = new \App\Services\EmailService();
                    $emailService->sendLearnerCertificateIssueEmail($email, $updatedLearner ?: []);
                } catch (\Throwable $e) {
                    Logger::error('Failed sending learner issue email', ['error' => $e->getMessage()]);
                }
            }

            $this->json([
                'success' => true,
                'message' => 'Learner Certificate Issued Successfully!',
                'acknowledgement_url' => BASE_URL . 'learners/acknowledgement?id=' . $id
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed issuing learner certificate', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display printable Learner Certificate Acknowledgement Receipt
     */
    public function acknowledgement(): void
    {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $learner = $this->learnerModel->find($id);

        if (!$learner) {
            $this->render404('Learner record not found');
            return;
        }

        $this->view('pages/learner/acknowledgement', [
            'learner' => $learner,
            'title' => 'Learner Certificate Issue Acknowledgement - SoftSam Portal'
        ]);
    }
}
