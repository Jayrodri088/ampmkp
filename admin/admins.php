<?php
// Admin and Role Management Page
require_once __DIR__ . '/includes/admin_functions.php';
require_once __DIR__ . '/includes/rbac.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isRequestHttps() ? 1 : 0);
session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . getAdminAbsoluteUrl('auth.php'));
    exit;
}

// Check session timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 86400)) {
    session_destroy();
    header('Location: ' . getAdminAbsoluteUrl('auth.php?timeout=1'));
    exit;
}

// Only super admins can manage admins
$isSuper = ($_SESSION['admin_role_slug'] ?? '') === 'super_admin';
if (!$isSuper) {
    header('Location: ' . getAdminUrl('index.php?error=unauthorized'));
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!isset($_SESSION['admin_csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $csrfToken)) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        // Create new admin
        if ($action === 'create_admin') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $roleId = intval($_POST['role_id'] ?? 0);

            if (empty($name) || empty($email) || empty($password) || empty($roleId)) {
                $message = 'All fields are required.';
                $messageType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Invalid email address.';
                $messageType = 'error';
            } elseif (strlen($password) < 8) {
                $message = 'Password must be at least 8 characters.';
                $messageType = 'error';
            } elseif (getAdminByEmail($email)) {
                $message = 'An admin with this email already exists.';
                $messageType = 'error';
            } else {
                $adminId = createAdmin([
                    'role_id' => $roleId,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'status' => 'active'
                ]);

                if ($adminId) {
                    $message = 'Admin created successfully.';
                    $messageType = 'success';
                    logAdminActivity('create_admin', 'admin', $adminId, ['name' => $name]);
                } else {
                    $message = 'Failed to create admin.';
                    $messageType = 'error';
                }
            }
        }
        // Update admin
        elseif ($action === 'update_admin') {
            $adminId = intval($_POST['admin_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $roleId = intval($_POST['role_id'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            $password = $_POST['password'] ?? '';

            if (empty($name) || empty($email) || empty($roleId)) {
                $message = 'Name, email, and role are required.';
                $messageType = 'error';
            } else {
                $data = [
                    'role_id' => $roleId,
                    'name' => $name,
                    'email' => $email,
                    'status' => $status
                ];

                if (!empty($password)) {
                    $data['password'] = $password;
                }

                if (updateAdmin($adminId, $data)) {
                    $message = 'Admin updated successfully.';
                    $messageType = 'success';
                    logAdminActivity('update_admin', 'admin', $adminId, $data);
                } else {
                    $message = 'Failed to update admin.';
                    $messageType = 'error';
                }
            }
        }
        // Delete admin
        elseif ($action === 'delete_admin') {
            $adminId = intval($_POST['admin_id'] ?? 0);

            // Prevent deleting yourself
            if ($adminId === ($_SESSION['admin_id'] ?? 0)) {
                $message = 'You cannot delete your own account.';
                $messageType = 'error';
            } else {
                if (deleteAdmin($adminId)) {
                    $message = 'Admin deleted successfully.';
                    $messageType = 'success';
                    logAdminActivity('delete_admin', 'admin', $adminId);
                } else {
                    $message = 'Failed to delete admin.';
                    $messageType = 'error';
                }
            }
        }
    }

    // Regenerate CSRF token
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Get data
$admins = getAdmins();
$roles = getRoles();

// Generate CSRF token if not exists
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Angel Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        folly: {
                            DEFAULT: '#FF0055',
                            50: '#ffccdd',
                            100: '#ff99bb',
                            200: '#ff6699',
                            300: '#ff3377',
                            400: '#ff0055',
                            500: '#cc0044',
                            600: '#990033',
                            700: '#660022',
                            800: '#330011',
                            900: '#1a0008'
                        },
                        charcoal: {
                            DEFAULT: '#3B4255',
                            50: '#f7f8fa',
                            100: '#ebeef3',
                            200: '#d4d7e1',
                            300: '#a8afc3',
                            400: '#7d88a5',
                            500: '#596380',
                            600: '#3b4255',
                            700: '#2f3443',
                            800: '#232733',
                            900: '#171a22'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">
        <!-- Desktop Sidebar -->
        <div class="hidden lg:block w-64 bg-charcoal text-white flex-shrink-0">
            <div class="p-6 border-b border-charcoal-500">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="bi bi-shield-check mr-3 text-folly"></i>
                    Admin Panel
                </h2>
                <p class="text-charcoal-200 text-sm mt-2">
                    Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                </p>
            </div>
            <nav class="p-4 space-y-1">
                <?php $activePage = 'admins'; include __DIR__ . '/partials/nav_links_desktop.php'; ?>
            </nav>
        </div>

        <!-- Mobile Sidebar -->
        <div id="mobileSidebar" class="lg:hidden fixed left-0 top-0 h-full w-64 bg-charcoal text-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out">
            <div class="p-4 border-b border-charcoal-500">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-bold flex items-center">
                        <i class="bi bi-shield-check mr-2 text-folly"></i>
                        Admin Panel
                    </h2>
                    <button onclick="closeMobileMenu()" class="text-white hover:text-gray-300 p-1">
                        <i class="bi bi-x text-xl"></i>
                    </button>
                </div>
                <p class="text-charcoal-200 text-xs mt-2">
                    Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                </p>
            </div>
            <nav class="p-2 space-y-1 overflow-y-auto max-h-[calc(100vh-80px)]">
                <?php $activePage = 'admins'; include __DIR__ . '/partials/nav_links_mobile.php'; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header -->
            <div class="bg-white border-b border-gray-200 px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <button onclick="openMobileMenu()" class="lg:hidden mr-3 p-2 text-charcoal-600 hover:text-folly">
                            <i class="bi bi-list text-xl"></i>
                        </button>
                        <h1 class="text-xl font-bold text-charcoal">Admin & Role Management</h1>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="flex-1 p-4 overflow-auto">
                <?php if ($message): ?>
                <div class="mb-6 <?= $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> border px-4 py-3 rounded-lg">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Roles Overview -->
                <div class="bg-white border border-gray-200 rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-charcoal">Roles Overview</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php foreach ($roles as $role): ?>
                            <div class="border rounded-lg p-4 <?= $role['slug'] === 'super_admin' ? 'bg-folly-50 border-folly-200' : 'bg-gray-50' ?>">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-semibold text-charcoal"><?= htmlspecialchars($role['name']) ?></h3>
                                    <span class="text-xs bg-<?= $role['slug'] === 'super_admin' ? 'folly' : 'charcoal' ?>-100 text-<?= $role['slug'] === 'super_admin' ? 'folly' : 'charcoal' ?>-800 px-2 py-1 rounded">
                                        <?= count(array_filter($admins, fn($a) => ($a['role_slug'] ?? '') === $role['slug'])) ?> admins
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($role['description'] ?? '') ?></p>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center">
                                        <i class="bi bi-globe mr-2 text-gray-400"></i>
                                        <span>Web: </span>
                                        <?php $perms = is_string($role['permissions']) ? json_decode($role['permissions'], true) : $role['permissions']; ?>
                                        <?php if ($role['slug'] === 'super_admin'): ?>
                                            <span class="ml-2 text-green-600"><i class="bi bi-check-circle-fill"></i> Full Access</span>
                                        <?php else: ?>
                                            <span class="ml-2 <?= ($perms['web']['products'] ?? false) ? 'text-green-600' : 'text-red-500' ?>">
                                                <i class="bi bi-<?= ($perms['web']['products'] ?? false) ? 'check-circle-fill' : 'x-circle' ?>"></i>
                                                <?= ($perms['web']['products'] ?? false) ? 'Granted' : 'Restricted' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="bi bi-phone mr-2 text-gray-400"></i>
                                        <span>Mobile: </span>
                                        <?php if ($role['slug'] === 'super_admin'): ?>
                                            <span class="ml-2 text-green-600"><i class="bi bi-check-circle-fill"></i> Full Access</span>
                                        <?php else: ?>
                                            <span class="ml-2 <?= ($perms['mobile']['api_access'] ?? false) ? 'text-green-600' : 'text-red-500' ?>">
                                                <i class="bi bi-<?= ($perms['mobile']['api_access'] ?? false) ? 'check-circle-fill' : 'x-circle' ?>"></i>
                                                <?= ($perms['mobile']['api_access'] ?? false) ? 'Granted' : 'Restricted' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Admins List -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-charcoal">Admin Users</h2>
                        <button onclick="openAddModal()" class="px-4 py-2 bg-folly text-white hover:bg-folly-600 rounded-lg text-sm font-medium">
                            <i class="bi bi-plus-circle mr-2"></i>Add Admin
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-6 font-medium text-charcoal-600 text-sm uppercase">Name</th>
                                    <th class="text-left py-3 px-6 font-medium text-charcoal-600 text-sm uppercase">Email</th>
                                    <th class="text-left py-3 px-6 font-medium text-charcoal-600 text-sm uppercase">Role</th>
                                    <th class="text-left py-3 px-6 font-medium text-charcoal-600 text-sm uppercase">Status</th>
                                    <th class="text-left py-3 px-6 font-medium text-charcoal-600 text-sm uppercase">Last Login</th>
                                    <th class="text-right py-3 px-6 font-medium text-charcoal-600 text-sm uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-6 text-sm font-medium text-charcoal"><?= htmlspecialchars($admin['name']) ?></td>
                                    <td class="py-3 px-6 text-sm text-charcoal-600"><?= htmlspecialchars($admin['email']) ?></td>
                                    <td class="py-3 px-6">
                                        <span class="px-2 py-1 text-xs font-medium rounded
                                            <?= ($admin['role_slug'] ?? '') === 'super_admin' ? 'bg-folly-100 text-folly-800' :
                                               (($admin['role_slug'] ?? '') === 'website_admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                            <?= htmlspecialchars($admin['role_name'] ?? 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6">
                                        <span class="px-2 py-1 text-xs font-medium rounded <?= ($admin['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= ucfirst($admin['status'] ?? 'active') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-sm text-charcoal-600"><?= $admin['last_login'] ? date('M j, Y', strtotime($admin['last_login'])) : 'Never' ?></td>
                                    <td class="py-3 px-6 text-right">
                                        <button onclick='editAdmin(<?= json_encode($admin) ?>)' class="text-folly hover:text-folly-600 mr-3">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if (($admin['id'] ?? 0) !== ($_SESSION['admin_id'] ?? 0)): ?>
                                        <button onclick="deleteAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name']) ?>')" class="text-red-500 hover:text-red-600">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Admin Modal -->
    <div id="adminModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold">Add New Admin</h3>
            </div>
            <form id="adminForm" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create_admin">
                <input type="hidden" name="admin_id" id="formAdminId" value="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">

                <div>
                    <label class="block text-sm font-medium text-charcoal-700 mb-1">Name</label>
                    <input type="text" name="name" id="formName" required class="w-full px-3 py-2 border rounded-lg focus:border-folly focus:ring-2 focus:ring-folly/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-charcoal-700 mb-1">Email</label>
                    <input type="email" name="email" id="formEmail" required class="w-full px-3 py-2 border rounded-lg focus:border-folly focus:ring-2 focus:ring-folly/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-charcoal-700 mb-1">Password <span id="passwordOptional" class="text-gray-400">(leave blank to keep current)</span></label>
                    <input type="password" name="password" id="formPassword" class="w-full px-3 py-2 border rounded-lg focus:border-folly focus:ring-2 focus:ring-folly/20" minlength="8">
                </div>
                <div>
                    <label class="block text-sm font-medium text-charcoal-700 mb-1">Role</label>
                    <select name="role_id" id="formRoleId" required class="w-full px-3 py-2 border rounded-lg focus:border-folly focus:ring-2 focus:ring-folly/20">
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?> - <?= htmlspecialchars($role['description'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="formStatusField" class="hidden">
                    <label class="block text-sm font-medium text-charcoal-700 mb-1">Status</label>
                    <select name="status" id="formStatus" class="w-full px-3 py-2 border rounded-lg focus:border-folly focus:ring-2 focus:ring-folly/20">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-folly text-white rounded-lg hover:bg-folly-600">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Delete Admin</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete <span id="deleteName" class="font-medium"></span>? This action cannot be undone.</p>
            <form id="deleteForm" method="POST" class="flex justify-end gap-3">
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" name="admin_id" id="deleteAdminId">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Delete</button>
            </form>
        </div>
    </div>

    <div id="mobileMenuOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="closeMobileMenu()"></div>

    <script>
        function openMobileMenu() {
            document.getElementById('mobileSidebar').classList.remove('-translate-x-full');
            document.getElementById('mobileMenuOverlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            document.getElementById('mobileSidebar').classList.add('-translate-x-full');
            document.getElementById('mobileMenuOverlay').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Admin';
            document.getElementById('formAction').value = 'create_admin';
            document.getElementById('formAdminId').value = '';
            document.getElementById('formName').value = '';
            document.getElementById('formEmail').value = '';
            document.getElementById('formPassword').value = '';
            document.getElementById('formPassword').required = true;
            document.getElementById('passwordOptional').classList.add('hidden');
            document.getElementById('formStatusField').classList.add('hidden');
            document.getElementById('adminModal').classList.remove('hidden');
        }

        function editAdmin(admin) {
            document.getElementById('modalTitle').textContent = 'Edit Admin';
            document.getElementById('formAction').value = 'update_admin';
            document.getElementById('formAdminId').value = admin.id;
            document.getElementById('formName').value = admin.name;
            document.getElementById('formEmail').value = admin.email;
            document.getElementById('formPassword').value = '';
            document.getElementById('formPassword').required = false;
            document.getElementById('passwordOptional').classList.remove('hidden');
            document.getElementById('formStatusField').classList.remove('hidden');
            document.getElementById('formRoleId').value = admin.role_id;
            document.getElementById('formStatus').value = admin.status || 'active';
            document.getElementById('adminModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('adminModal').classList.add('hidden');
        }

        function deleteAdmin(id, name) {
            document.getElementById('deleteAdminId').value = id;
            document.getElementById('deleteName').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>
