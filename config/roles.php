<?php
/**
 * Role Configuration - RBAC System
 * 
 * Hierarchical role-based access control with the following privilege levels:
 * GUEST < PARTNER < COORDINATOR < EMPLOYEE < ADMIN < SUPERADMIN
 * 
 * Role permissions are cumulative - higher roles inherit all lower role permissions.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Role Hierarchy
    |--------------------------------------------------------------------------
    | Numerical values determine privilege level (higher = more privileges)
    */
    'hierarchy' => [
        'GUEST' => 0,
        'PARTNER' => 10,
        'COORDINATOR' => 20,
        'EMPLOYEE' => 30,
        'ADMIN' => 40,
        'SUPERADMIN' => 100
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Display Names
    |--------------------------------------------------------------------------
    */
    'display_names' => [
        'GUEST' => 'Guest',
        'PARTNER' => 'Partner',
        'COORDINATOR' => 'Coordinator',
        'EMPLOYEE' => 'Employee',
        'ADMIN' => 'Administrator',
        'SUPERADMIN' => 'Super Administrator'
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Descriptions
    |--------------------------------------------------------------------------
    */
    'descriptions' => [
        'GUEST' => 'Unauthenticated/visitor with read-only access',
        'PARTNER' => 'Partner organization - limited certificate viewing',
        'COORDINATOR' => 'ITGK Center Coordinator - manage learners and certificates',
        'EMPLOYEE' => 'Regular employee - standard access',
        'ADMIN' => 'Administrator - manage users and sensitive operations',
        'SUPERADMIN' => 'Super Administrator - full system access'
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Permissions by Role
    |--------------------------------------------------------------------------
    | Permissions are inherited from lower roles
    */
    'permissions' => [
        'GUEST' => [],
        'PARTNER' => [
            'view_certificates',
            'view_learners'
        ],
        'COORDINATOR' => [
            'view_certificates',
            'view_learners',
            'create_certificates',
            'create_learners',
            'update_learners',
            'issue_certificates'
        ],
        'EMPLOYEE' => [
            'view_certificates',
            'view_learners',
            'receive_certificates'
        ],
        'ADMIN' => [
            'view_certificates',
            'view_learners',
            'create_certificates',
            'create_learners',
            'update_learners',
            'issue_certificates',
            'consolidate_certificates',
            'delete_learners',
            'manage_users'
        ],
        'SUPERADMIN' => [
            'view_certificates',
            'view_learners',
            'create_certificates',
            'create_learners',
            'update_learners',
            'issue_certificates',
            'consolidate_certificates',
            'delete_learners',
            'delete_certificates',
            'manage_users',
            'manage_roles',
            'manage_certificates',
            'manage_learners',
            'view_reports',
            'system_settings',
            'delete_records',
            'upload_data'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Access by Role
    |--------------------------------------------------------------------------
    */
    'menu_access' => [
        'view_dashboard' => ['PARTNER', 'COORDINATOR', 'EMPLOYEE', 'ADMIN', 'SUPERADMIN'],
        'view_certificates' => ['PARTNER', 'COORDINATOR', 'EMPLOYEE', 'ADMIN', 'SUPERADMIN'],
        'manage_certificates' => ['COORDINATOR', 'ADMIN', 'SUPERADMIN'],
        'consolidate_certificates' => ['ADMIN', 'SUPERADMIN'],
        'view_learners' => ['PARTNER', 'COORDINATOR', 'EMPLOYEE', 'ADMIN', 'SUPERADMIN'],
        'manage_learners' => ['COORDINATOR', 'ADMIN', 'SUPERADMIN'],
        'data_upload' => ['ADMIN', 'SUPERADMIN'],
        'app_setup' => ['SUPERADMIN']
    ]
];