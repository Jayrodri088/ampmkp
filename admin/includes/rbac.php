<?php
/**
 * RBAC (Role-Based Access Control) Helper Functions
 * Handles role and permission management for admin users
 */

require_once __DIR__ . '/../../includes/database.php';

/**
 * Get all roles from database
 */
function getRoles(): array {
    try {
        if (!Database::isMySQLEnabled()) {
            // Fallback to JSON if MySQL not enabled
            $rolesFile = __DIR__ . '/../../data/admin_roles.json';
            if (file_exists($rolesFile)) {
                return json_decode(file_get_contents($rolesFile), true) ?? [];
            }
            return getDefaultRoles();
        }

        return Database::fetchAll('SELECT * FROM admin_roles ORDER BY id ASC');
    } catch (Exception $e) {
        error_log('Error getting roles: ' . $e->getMessage());
        return getDefaultRoles();
    }
}

/**
 * Get a role by ID
 */
function getRoleById(int $id): ?array {
    try {
        if (!Database::isMySQLEnabled()) {
            $roles = getRoles();
            foreach ($roles as $role) {
                if ($role['id'] == $id) {
                    return $role;
                }
            }
            return null;
        }

        return Database::fetchOne('SELECT * FROM admin_roles WHERE id = ?', [$id]);
    } catch (Exception $e) {
        error_log('Error getting role: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get a role by slug
 */
function getRoleBySlug(string $slug): ?array {
    try {
        if (!Database::isMySQLEnabled()) {
            $roles = getRoles();
            foreach ($roles as $role) {
                if ($role['slug'] === $slug) {
                    return $role;
                }
            }
            return null;
        }

        return Database::fetchOne('SELECT * FROM admin_roles WHERE slug = ?', [$slug]);
    } catch (Exception $e) {
        error_log('Error getting role by slug: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get default roles (fallback)
 */
function getDefaultRoles(): array {
    return [
        [
            'id' => 1,
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Full access to all resources including web and mobile',
            'permissions' => json_encode([
                'web' => [
                    'products' => true,
                    'categories' => true,
                    'orders' => true,
                    'customers' => true,
                    'vendors' => true,
                    'ads' => true,
                    'settings' => true
                ],
                'mobile' => [
                    'products' => true,
                    'categories' => true,
                    'orders' => true,
                    'customers' => true,
                    'vendors' => true,
                    'ads' => true,
                    'settings' => true,
                    'api_access' => true
                ]
            ])
        ],
        [
            'id' => 2,
            'name' => 'Website Admin',
            'slug' => 'website_admin',
            'description' => 'Access to website management only',
            'permissions' => json_encode([
                'web' => [
                    'products' => true,
                    'categories' => true,
                    'orders' => true,
                    'customers' => true,
                    'vendors' => true,
                    'ads' => true,
                    'settings' => true
                ],
                'mobile' => [
                    'products' => false,
                    'categories' => false,
                    'orders' => false,
                    'customers' => false,
                    'vendors' => false,
                    'ads' => false,
                    'settings' => false,
                    'api_access' => false
                ]
            ])
        ],
        [
            'id' => 3,
            'name' => 'Mobile Admin',
            'slug' => 'mobile_admin',
            'description' => 'Access to mobile app management only',
            'permissions' => json_encode([
                'web' => [
                    'products' => false,
                    'categories' => false,
                    'orders' => false,
                    'customers' => false,
                    'vendors' => false,
                    'ads' => false,
                    'settings' => false
                ],
                'mobile' => [
                    'products' => true,
                    'categories' => true,
                    'orders' => true,
                    'customers' => true,
                    'vendors' => true,
                    'ads' => true,
                    'settings' => true,
                    'api_access' => true
                ]
            ])
        ]
    ];
}

/**
 * Get all admins
 */
function getAdmins(): array {
    try {
        if (!Database::isMySQLEnabled()) {
            $adminsFile = __DIR__ . '/../../data/admins.json';
            if (file_exists($adminsFile)) {
                return json_decode(file_get_contents($adminsFile), true) ?? [];
            }
            return [];
        }

        $admins = Database::fetchAll('
            SELECT a.*, r.name as role_name, r.slug as role_slug, r.permissions
            FROM admins a
            INNER JOIN admin_roles r ON a.role_id = r.id
            ORDER BY a.created_at DESC
        ');

        // Parse permissions for each admin
        foreach ($admins as &$admin) {
            if (is_string($admin['permissions'])) {
                $admin['permissions'] = json_decode($admin['permissions'], true);
            }
        }

        return $admins;
    } catch (Exception $e) {
        error_log('Error getting admins: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get admin by ID
 */
function getAdminById(int $id): ?array {
    try {
        if (!Database::isMySQLEnabled()) {
            $admins = getAdmins();
            foreach ($admins as $admin) {
                if ($admin['id'] == $id) {
                    return $admin;
                }
            }
            return null;
        }

        $admin = Database::fetchOne('
            SELECT a.*, r.name as role_name, r.slug as role_slug, r.permissions
            FROM admins a
            INNER JOIN admin_roles r ON a.role_id = r.id
            WHERE a.id = ?
        ', [$id]);

        if ($admin && is_string($admin['permissions'])) {
            $admin['permissions'] = json_decode($admin['permissions'], true);
        }

        return $admin;
    } catch (Exception $e) {
        error_log('Error getting admin: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get admin by email
 */
function getAdminByEmail(string $email): ?array {
    try {
        if (!Database::isMySQLEnabled()) {
            $admins = getAdmins();
            foreach ($admins as $admin) {
                if ($admin['email'] === $email) {
                    return $admin;
                }
            }
            return null;
        }

        $admin = Database::fetchOne('
            SELECT a.*, r.name as role_name, r.slug as role_slug, r.permissions
            FROM admins a
            INNER JOIN admin_roles r ON a.role_id = r.id
            WHERE a.email = ?
        ', [$email]);

        if ($admin && is_string($admin['permissions'])) {
            $admin['permissions'] = json_decode($admin['permissions'], true);
        }

        return $admin;
    } catch (Exception $e) {
        error_log('Error getting admin by email: ' . $e->getMessage());
        return null;
    }
}

/**
 * Check if current admin has permission
 * @param string $platform 'web' or 'mobile'
 * @param string $resource The resource to check (products, orders, etc.)
 * @param string $action The action (view, create, update, delete) - defaults to 'view'
 * @return bool
 */
function hasPermission(string $platform, string $resource, string $action = 'view'): bool {
    if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_permissions'])) {
        return false;
    }

    $permissions = $_SESSION['admin_permissions'];

    // Super admin has all permissions
    if (isset($permissions['is_super']) && $permissions['is_super'] === true) {
        return true;
    }

    // Check specific permission
    return $permissions[$platform][$resource] ?? false;
}

/**
 * Check if current admin has any mobile permissions
 */
function hasMobileAccess(): bool {
    if (!isset($_SESSION['admin_logged_in'])) {
        return false;
    }

    if (isset($_SESSION['admin_permissions']['is_super']) && $_SESSION['admin_permissions']['is_super'] === true) {
        return true;
    }

    $permissions = $_SESSION['admin_permissions']['mobile'] ?? [];
    return !empty($permissions) && (isset($permissions['api_access']) && $permissions['api_access'] === true);
}

/**
 * Check if current admin has any web permissions
 */
function hasWebAccess(): bool {
    if (!isset($_SESSION['admin_logged_in'])) {
        return false;
    }

    if (isset($_SESSION['admin_permissions']['is_super']) && $_SESSION['admin_permissions']['is_super'] === true) {
        return true;
    }

    $permissions = $_SESSION['admin_permissions']['web'] ?? [];
    return !empty(array_filter($permissions));
}

/**
 * Require permission - redirect if not authorized
 */
function requirePermission(string $platform, string $resource, string $action = 'view'): void {
    if (!hasPermission($platform, $resource, $action)) {
        header('Location: ' . getAdminUrl('index.php?error=unauthorized'));
        exit;
    }
}

/**
 * Log admin activity
 */
function logAdminActivity(string $action, ?string $entityType = null, ?string $entityId = null, ?array $details = null, string $platform = 'web'): void {
    if (!isset($_SESSION['admin_id'])) {
        return;
    }

    try {
        if (Database::isMySQLEnabled()) {
            Database::insert('admin_activity_log', [
                'admin_id' => $_SESSION['admin_id'],
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details ? json_encode($details) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'platform' => $platform
            ]);
        } else {
            // Fallback to JSON logging
            $logFile = __DIR__ . '/../../data/admin_activity_log.json';
            $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
            $logs[] = [
                'admin_id' => $_SESSION['admin_id'],
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'platform' => $platform,
                'created_at' => date('Y-m-d H:i:s')
            ];
            file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
        }
    } catch (Exception $e) {
        error_log('Error logging activity: ' . $e->getMessage());
    }
}

/**
 * Create a new admin
 */
function createAdmin(array $data): int|string|false {
    try {
        if (!Database::isMySQLEnabled()) {
            // JSON fallback
            $adminsFile = __DIR__ . '/../../data/admins.json';
            $admins = file_exists($adminsFile) ? json_decode(file_get_contents($adminsFile), true) : [];
            $data['id'] = count($admins) + 1;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $admins[] = $data;
            file_put_contents($adminsFile, json_encode($admins, JSON_PRETTY_PRINT));
            return $data['id'];
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return Database::insert('admins', $data);
    } catch (Exception $e) {
        error_log('Error creating admin: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update admin
 */
function updateAdmin(int $id, array $data): bool {
    try {
        if (!Database::isMySQLEnabled()) {
            $adminsFile = __DIR__ . '/../../data/admins.json';
            $admins = json_decode(file_get_contents($adminsFile), true);
            foreach ($admins as &$admin) {
                if ($admin['id'] == $id) {
                    $admin = array_merge($admin, $data);
                    break;
                }
            }
            file_put_contents($adminsFile, json_encode($admins, JSON_PRETTY_PRINT));
            return true;
        }

        // Don't update password if not provided
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        Database::update('admins', $data, 'id = ?', [$id]);
        return true;
    } catch (Exception $e) {
        error_log('Error updating admin: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete admin
 */
function deleteAdmin(int $id): bool {
    try {
        if (!Database::isMySQLEnabled()) {
            $adminsFile = __DIR__ . '/../../data/admins.json';
            $admins = json_decode(file_get_contents($adminsFile), true);
            $admins = array_filter($admins, fn($a) => $a['id'] != $id);
            file_put_contents($adminsFile, json_encode(array_values($admins), JSON_PRETTY_PRINT));
            return true;
        }

        Database::delete('admins', 'id = ?', [$id]);
        return true;
    } catch (Exception $e) {
        error_log('Error deleting admin: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update admin last login
 */
function updateAdminLastLogin(int $adminId): void {
    try {
        if (Database::isMySQLEnabled()) {
            Database::query('UPDATE admins SET last_login = NOW() WHERE id = ?', [$adminId]);
        }
    } catch (Exception $e) {
        error_log('Error updating last login: ' . $e->getMessage());
    }
}

/**
 * Verify admin credentials
 */
function verifyAdminCredentials(string $email, string $password): ?array {
    $admin = getAdminByEmail($email);

    if (!$admin) {
        return null;
    }

    if ($admin['status'] !== 'active') {
        return null;
    }

    if (password_verify($password, $admin['password'])) {
        // Remove password from returned data
        unset($admin['password']);
        return $admin;
    }

    return null;
}

/**
 * Get admin session data
 */
function getAdminSessionData(): ?array {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return null;
    }

    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'name' => $_SESSION['admin_name'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null,
        'role_slug' => $_SESSION['admin_role_slug'] ?? null,
        'permissions' => $_SESSION['admin_permissions'] ?? []
    ];
}
