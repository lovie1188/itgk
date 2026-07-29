<?php

/**
 * API LearnerController - Handles learner data via API
 * 
 * RBAC Rules (GUEST < PARTNER < COORDINATOR < EMPLOYEE < ADMIN < SUPERADMIN):
 * - List: Authenticated users (any role)
 * - Create/Update: COORDINATOR+
 * - Delete: ADMIN+
 * - Issue certificate: COORDINATOR+
 * 
 * @package App\Controllers\Api
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\LearnerResult;

class LearnerController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    /**
     * List learners
     * 
     * @return void
     */
    public function index(): void
    {
        $learnerModel = new LearnerResult();
        $learners = $learnerModel->getAll(100, 0);
        
        $this->json(['success' => true, 'data' => $learners]);
    }

    /**
     * Store learner - COORDINATOR+ can create
     * 
     * @return void
     */
    public function store(): void
    {
        $this->requireRoleLevel('COORDINATOR');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $learnerModel = new LearnerResult();
        
        $id = $learnerModel->create($input);
        
        $this->json(['success' => true, 'id' => $id, 'message' => 'Learner created']);
    }

    /**
     * Issue individual learner certificate - COORDINATOR+
     * 
     * @param int|string|null $id Learner ID
     * @return void
     */
    public function issueIndividual($id = null): void
    {
        $this->requireRoleLevel('COORDINATOR');

        $learnerId = (int)($id ?? $_GET['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if ($learnerId <= 0) {
            $this->json(['success' => false, 'error' => ['message' => 'Invalid learner ID']], 400);
            return;
        }

        $learnerModel = new LearnerResult();
        $success = $learnerModel->issueIndividual($learnerId, [
            'name' => $input['receiver_name'] ?? $input['name'] ?? '',
            'designation' => $input['receiver_designation'] ?? $input['designation'] ?? '',
            'mobile' => $input['receiver_mobile'] ?? $input['mobile'] ?? '',
            'remark' => $input['remark'] ?? ''
        ]);

        $this->json(['success' => $success, 'message' => $success ? 'Individual certificate issued successfully' : 'Failed to issue certificate']);
    }

    /**
     * Delete learners - SUPERADMIN only
     * 
     * @return void
     */
    public function delete(): void
    {
        $this->requireRoleLevel('SUPERADMIN');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $ids = $input['ids'] ?? (isset($input['id']) ? [$input['id']] : []);

        if (empty($ids)) {
            $this->json(['success' => false, 'error' => ['message' => 'No learner IDs provided']], 400);
            return;
        }

        $learnerModel = new LearnerResult();
        $count = $learnerModel->deleteMany((array)$ids);

        $this->json(['success' => $count > 0, 'count' => $count, 'message' => "{$count} learner record(s) deleted"]);
    }
}