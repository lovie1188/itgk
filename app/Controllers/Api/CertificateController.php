<?php

/**
 * API CertificateController - Handles certificate data via API
 * 
 * RBAC Rules (GUEST < PARTNER < COORDINATOR < EMPLOYEE < ADMIN < SUPERADMIN):
 * - List: Authenticated users (any role)
 * - Create/Update: COORDINATOR+
 * - Consolidate: ADMIN+ (sensitive operation affecting multiple records)
 * - Delete/Issue: ADMIN+
 * 
 * @package App\Controllers\Api
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Certificate;

class CertificateController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    /**
     * List certificates - Authenticated users can view
     * 
     * @return void
     */
    public function index(): void
    {
        $certModel = new Certificate();
        $certificates = $certModel->getAll(100, 0);
        
        $this->json(['success' => true, 'data' => $certificates]);
    }

    /**
     * Store certificate - COORDINATOR+ can create
     * 
     * @return void
     */
    public function store(): void
    {
        $this->requireRoleLevel('COORDINATOR');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $certModel = new Certificate();
        
        $id = $certModel->create($input);
        
        $this->json(['success' => true, 'id' => $id, 'message' => 'Certificate created']);
    }

    /**
     * Consolidate certificates from learners - ADMIN+ only
     * 
     * @return void
     */
    public function consolidate(): void
    {
        $this->requireRoleLevel('ADMIN');
        
        $certModel = new Certificate();
        $result = $certModel->consolidateFromLearners();
        
        $this->json(['success' => true, 'stats' => $result['stats']]);
    }

    /**
     * Issue batch certificate packet - SUPERADMIN/ADMIN only
     * 
     * @param int|string|null $id Certificate ID
     * @return void
     */
    public function issueBatch($id = null): void
    {
        $this->requireRoleLevel('ADMIN');

        $certId = (int)($id ?? $_GET['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if ($certId <= 0) {
            $this->json(['success' => false, 'error' => ['message' => 'Invalid certificate ID']], 400);
            return;
        }

        $certModel = new Certificate();
        $success = $certModel->issueBatch($certId, [
            'name' => $input['receiver_name'] ?? $input['name'] ?? '',
            'designation' => $input['receiver_designation'] ?? $input['designation'] ?? '',
            'mobile' => $input['receiver_mobile'] ?? $input['mobile'] ?? '',
            'remark' => $input['remark'] ?? ''
        ]);

        $this->json(['success' => $success, 'message' => $success ? 'Certificate batch issued successfully' : 'Failed to issue batch']);
    }

    /**
     * Delete certificate - SUPERADMIN only
     * 
     * @return void
     */
    public function delete(): void
    {
        $this->requireRoleLevel('SUPERADMIN');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'error' => ['message' => 'Invalid ID']], 400);
            return;
        }

        $certModel = new Certificate();
        $deleted = $certModel->delete($id);

        $this->json(['success' => $deleted, 'message' => $deleted ? 'Certificate deleted' : 'Delete failed']);
    }
}