<?php

/**
 * ProfileController - User Profile Controller
 * 
 * Handles user profile display and settings.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Helpers\Logger;
use App\Services\AuthService;

class ProfileController extends BaseController
{
    /**
     * Display user profile with office & designation options
     */
    public function index(): void
    {
        $this->requireAuth();

        $currentUser = $this->getCurrentUser();
        $db = Database::getInstance();

        // Fetch user record directly from DB to ensure freshest data
        $userRecord = null;
        if (!empty($currentUser['id'])) {
            $userRecord = $db->fetch(
                "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.mobile, u.designation,
                        eom.office_id, o.name as office_name
                 FROM users u
                 LEFT JOIN employee_office_map eom ON u.id = eom.user_id
                 LEFT JOIN offices o ON eom.office_id = o.id
                 WHERE u.id = :id
                 ORDER BY eom.id DESC
                 LIMIT 1",
                ['id' => $currentUser['id']]
            );
        }

        // Fetch all offices for dropdown
        $offices = [];
        try {
            $offices = $db->fetchAll("SELECT id, name, district, address FROM offices ORDER BY id ASC");
        } catch (\Exception $e) {
            Logger::error('Failed to fetch offices list', ['error' => $e->getMessage()]);
        }

        // Sync session user array with DB state
        if ($userRecord) {
            $_SESSION['user']['first_name']      = $userRecord['first_name'];
            $_SESSION['user']['last_name']       = $userRecord['last_name'];
            $_SESSION['user']['name']            = trim(($userRecord['first_name'] ?? '') . ' ' . ($userRecord['last_name'] ?? ''));
            $_SESSION['user']['email']           = $userRecord['email'];
            $_SESSION['user']['mobile']          = $userRecord['mobile'];
            $_SESSION['user']['designation']     = $userRecord['designation'];
            $_SESSION['user']['office_id']       = $userRecord['office_id'];
            $_SESSION['user']['office_name']     = $userRecord['office_name'];
        }

        $data = [
            'user'       => $_SESSION['user'] ?? $currentUser,
            'userRecord' => $userRecord ?: $currentUser,
            'offices'    => $offices,
            'role'       => $this->getCurrentRole(),
            'title'      => 'My Profile | SoftSam Portal',
            'view'       => 'pages/profile'
        ];

        $this->view('pages/profile', $data);
    }

    /**
     * Update user profile details (Name, Email, Mobile, Designation, Office)
     */
    public function update(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $currentUser = $this->getCurrentUser();
        $userId      = (int)($currentUser['id'] ?? 0);

        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'Unauthorized user session'], 401);
            return;
        }

        $firstName   = trim($_POST['first_name'] ?? '');
        $lastName    = trim($_POST['last_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $mobile      = trim($_POST['mobile'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $officeId    = (int)($_POST['office_id'] ?? 0);

        if (empty($firstName) || empty($email)) {
            $this->json(['success' => false, 'message' => 'First Name and Email are required.'], 400);
            return;
        }

        try {
            $db = Database::getInstance();

            // 1. Update users table
            $db->execute(
                "UPDATE users 
                 SET first_name = :fn, last_name = :ln, email = :email, mobile = :mobile, designation = :desig, updated_at = NOW() 
                 WHERE id = :id",
                [
                    'fn'    => $firstName,
                    'ln'    => $lastName,
                    'email' => $email,
                    'mobile'=> $mobile,
                    'desig' => $designation,
                    'id'    => $userId,
                ]
            );

            // 2. Update employee_office_map table
            if ($officeId > 0) {
                $db->execute(
                    "INSERT INTO employee_office_map (user_id, office_id) 
                     VALUES (:user_id, :office_id) 
                     ON DUPLICATE KEY UPDATE office_id = VALUES(office_id)",
                    ['user_id' => $userId, 'office_id' => $officeId]
                );
            } else {
                $db->execute("DELETE FROM employee_office_map WHERE user_id = :user_id", ['user_id' => $userId]);
            }

            // 3. Fetch updated office name
            $officeName = '';
            if ($officeId > 0) {
                $offRow = $db->fetch("SELECT name FROM offices WHERE id = :id", ['id' => $officeId]);
                $officeName = $offRow['name'] ?? '';
            }

            // 4. Save updated info into session
            $_SESSION['user']['first_name']  = $firstName;
            $_SESSION['user']['last_name']   = $lastName;
            $_SESSION['user']['name']        = trim("{$firstName} {$lastName}");
            $_SESSION['user']['email']       = $email;
            $_SESSION['user']['mobile']      = $mobile;
            $_SESSION['user']['designation'] = $designation;
            $_SESSION['user']['office_id']   = $officeId ?: null;
            $_SESSION['user']['office_name'] = $officeName ?: null;
            $_SESSION['name']                = $_SESSION['user']['name'];
            $_SESSION['email']               = $email;

            Logger::info('Profile updated successfully', ['user_id' => $userId, 'office' => $officeName]);

            $this->json([
                'success' => true,
                'message' => 'Profile updated successfully and saved to session!',
                'user'    => $_SESSION['user'],
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to update user profile', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Database update error: ' . $e->getMessage()], 500);
        }
    }
}
