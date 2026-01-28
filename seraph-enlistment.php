<?php
session_start();

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

$password = $_ENV['SERAPH_DASHBOARD_PASSWORD'] ?? 'seraph@2026';
$error = '';
$isLoggedIn = isset($_SESSION['seraph_dashboard_auth']) && $_SESSION['seraph_dashboard_auth'] === true;

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['seraph_dashboard_auth']);
    header('Location: seraph-enlistment.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['seraph_dashboard_auth'] = true;
        header('Location: seraph-enlistment.php');
        exit;
    } else {
        $error = 'Invalid password';
    }
}

// Handle status update
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $newStatus = $_POST['status'];

    $dataFile = __DIR__ . '/data/seraph_distributors.json';
    if (file_exists($dataFile)) {
        $applications = json_decode(file_get_contents($dataFile), true) ?: [];
        foreach ($applications as &$app) {
            if ($app['id'] === $id) {
                $app['status'] = $newStatus;
                break;
            }
        }
        file_put_contents($dataFile, json_encode($applications, JSON_PRETTY_PRINT));
    }
    header('Location: seraph-enlistment.php');
    exit;
}

// Handle delete
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = (int)$_POST['id'];

    $dataFile = __DIR__ . '/data/seraph_distributors.json';
    if (file_exists($dataFile)) {
        $applications = json_decode(file_get_contents($dataFile), true) ?: [];
        $applications = array_filter($applications, function($app) use ($id) {
            return $app['id'] !== $id;
        });
        file_put_contents($dataFile, json_encode(array_values($applications), JSON_PRETTY_PRINT));
    }
    header('Location: seraph-enlistment.php');
    exit;
}

// Load applications if logged in
$applications = [];
if ($isLoggedIn) {
    $dataFile = __DIR__ . '/data/seraph_distributors.json';
    if (file_exists($dataFile)) {
        $applications = json_decode(file_get_contents($dataFile), true) ?: [];
        // Sort by newest first
        usort($applications, function($a, $b) {
            return $b['id'] - $a['id'];
        });
    }
}

// Stats
$totalApplications = count($applications);
$newApplications = count(array_filter($applications, fn($a) => ($a['status'] ?? 'new') === 'new'));
$approvedApplications = count(array_filter($applications, fn($a) => ($a['status'] ?? '') === 'approved'));
$rejectedApplications = count(array_filter($applications, fn($a) => ($a['status'] ?? '') === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seraph Distributor Applications | AMP Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ['Poppins', 'sans-serif'],
                    display: ['Coves', 'Poppins', 'sans-serif'],
                },
                extend: {
                    colors: {
                        'folly': {
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
                        'charcoal': {
                            DEFAULT: '#3B4255',
                            50: '#d4d7e1',
                            100: '#a8afc3',
                            200: '#7d88a5',
                            300: '#596380',
                            400: '#3b4255',
                            500: '#2f3443',
                            600: '#232733',
                            700: '#171a22',
                            800: '#0c0d11',
                            900: '#060608'
                        },
                        'tangerine': {
                            DEFAULT: '#F5884B',
                            50: '#fde8db',
                            100: '#fbd0b8',
                            200: '#f9b994',
                            300: '#f7a270',
                            400: '#f5884b',
                            500: '#f16310',
                            600: '#b64a0b',
                            700: '#793107',
                            800: '#3d1904',
                            900: '#1e0c02'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @font-face {
            font-family: 'Coves';
            src: url('assets/fonts/Coves-Bold.otf') format('opentype');
            font-weight: bold;
            font-style: normal;
        }
        * { font-family: 'Poppins', sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-display { font-family: 'Coves', 'Poppins', sans-serif !important; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .status-new { background: #3b82f6; }
        .status-contacted { background: #f59e0b; }
        .status-approved { background: #10b981; }
        .status-rejected { background: #ef4444; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 25px -5px rgba(0,0,0,0.1); }
        .modal-overlay { background: rgba(59, 66, 85, 0.75); backdrop-filter: blur(8px); }
        input:focus, select:focus, button:focus { outline: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen antialiased">
    <?php if (!$isLoggedIn): ?>
    <!-- Login -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 p-8">
                <div class="mb-8">
                    <img src="assets/images/general/logo.png" alt="AMP Logo" class="h-12 mb-4">
                    <h1 class="text-xl font-semibold text-charcoal-400 font-display">Seraph Dashboard</h1>
                    <p class="text-gray-500 text-sm mt-1">Enter your password to continue</p>
                </div>

                <?php if ($error): ?>
                <div class="mb-6 p-3 bg-red-50 border border-red-100 rounded-lg text-red-600 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-5">
                        <input type="password" id="password" name="password" required autofocus
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-charcoal-400 placeholder-gray-400 focus:border-folly focus:ring-2 focus:ring-folly-50 transition-all"
                            placeholder="Password">
                    </div>
                    <button type="submit" class="w-full bg-folly hover:bg-folly-600 text-white font-medium py-3 rounded-xl transition-colors">
                        Sign In
                    </button>
                </form>
            </div>
            <p class="text-center text-gray-400 text-xs mt-6">Seraph Distributor Application Management</p>
        </div>
    </div>
    <?php else: ?>
    <!-- Dashboard -->
    <div class="min-h-screen">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-3">
                        <img src="assets/images/general/logo.png" alt="AMP Logo" class="h-8">
                        <div>
                            <h1 class="text-base font-semibold text-charcoal-400 font-display">Seraph Distributors</h1>
                            <p class="text-xs text-gray-500 hidden sm:block">Application Management</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="seraph.php" class="hidden sm:flex items-center gap-2 px-3 py-2 text-sm text-charcoal-300 hover:text-charcoal-400 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            View Form
                        </a>
                        <a href="?logout=1" class="flex items-center gap-2 px-3 py-2 text-sm text-charcoal-300 hover:text-folly hover:bg-folly-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span class="hidden sm:inline">Sign Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Stats Row -->
            <div class="grid grid-cols-4 gap-3 mb-6">
                <button onclick="filterApplications('all')" class="filter-btn bg-white rounded-xl p-4 text-left border border-gray-200 hover:border-folly-200 hover:shadow-md transition-all cursor-pointer" data-filter="all">
                    <p class="text-2xl font-bold text-charcoal-400"><?php echo $totalApplications; ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-1">All</p>
                </button>
                <button onclick="filterApplications('new')" class="filter-btn bg-white rounded-xl p-4 text-left border border-gray-200 hover:border-blue-200 hover:shadow-md transition-all cursor-pointer" data-filter="new">
                    <p class="text-2xl font-bold text-blue-600"><?php echo $newApplications; ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-1 flex items-center gap-1.5">
                        <span class="status-dot status-new"></span>New
                    </p>
                </button>
                <button onclick="filterApplications('approved')" class="filter-btn bg-white rounded-xl p-4 text-left border border-gray-200 hover:border-emerald-200 hover:shadow-md transition-all cursor-pointer" data-filter="approved">
                    <p class="text-2xl font-bold text-emerald-600"><?php echo $approvedApplications; ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-1 flex items-center gap-1.5">
                        <span class="status-dot status-approved"></span>Approved
                    </p>
                </button>
                <button onclick="filterApplications('rejected')" class="filter-btn bg-white rounded-xl p-4 text-left border border-gray-200 hover:border-red-200 hover:shadow-md transition-all cursor-pointer" data-filter="rejected">
                    <p class="text-2xl font-bold text-red-500"><?php echo $rejectedApplications; ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-1 flex items-center gap-1.5">
                        <span class="status-dot status-rejected"></span>Rejected
                    </p>
                </button>
            </div>

            <!-- Search & Filter Bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-3 mb-4 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="searchInput" placeholder="Search by name, email, or location..."
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-charcoal-400 placeholder-gray-400 focus:border-folly focus:bg-white transition-all"
                        oninput="searchApplications(this.value)">
                </div>
                <div class="flex gap-2">
                    <select id="statusFilter" onchange="filterApplications(this.value)" class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-charcoal-300 focus:border-folly cursor-pointer">
                        <option value="all">All Status</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <?php if (empty($applications)): ?>
            <!-- Empty State -->
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <div class="w-16 h-16 bg-folly-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-charcoal-400 mb-1 font-display">No applications yet</h3>
                <p class="text-gray-500 text-sm">Applications will appear here when people submit the form.</p>
            </div>
            <?php else: ?>

            <!-- Results Info -->
            <div class="flex items-center justify-between mb-3">
                <p id="resultsCount" class="text-sm text-gray-500"><span id="visibleCount"><?php echo $totalApplications; ?></span> applications</p>
                <p class="text-xs text-gray-400">Click on a row to view details</p>
            </div>

            <!-- Applications List -->
            <div id="applicationsList" class="space-y-2">
                <?php foreach ($applications as $index => $app):
                    $status = $app['status'] ?? 'new';
                ?>
                <div class="application-card bg-white rounded-xl border border-gray-200 hover:border-folly-200 card-hover transition-all cursor-pointer overflow-hidden"
                     data-id="<?php echo $app['id']; ?>"
                     data-status="<?php echo $status; ?>"
                     data-name="<?php echo strtolower(htmlspecialchars($app['name'])); ?>"
                     data-email="<?php echo strtolower(htmlspecialchars($app['email'])); ?>"
                     data-location="<?php echo strtolower(htmlspecialchars($app['location'])); ?>"
                     onclick="openModal(<?php echo $app['id']; ?>)">
                    <div class="p-4 sm:p-5">
                        <div class="flex items-start gap-4">
                            <!-- Avatar -->
                            <div class="w-11 h-11 bg-folly-50 rounded-full flex items-center justify-center flex-shrink-0 text-folly font-semibold text-sm">
                                <?php echo strtoupper(substr($app['name'], 0, 2)); ?>
                            </div>

                            <!-- Main Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-charcoal-400 truncate"><?php echo htmlspecialchars($app['name']); ?></h3>
                                        <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($app['email']); ?></p>
                                    </div>
                                    <!-- Status Badge -->
                                    <div class="flex-shrink-0">
                                        <?php
                                        $statusClasses = [
                                            'new' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'contacted' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'rejected' => 'bg-red-50 text-red-700 border-red-200'
                                        ];
                                        $statusClass = $statusClasses[$status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                                        ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border <?php echo $statusClass; ?>">
                                            <span class="status-dot status-<?php echo $status; ?>"></span>
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Meta Info -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        <?php echo htmlspecialchars($app['phone']); ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <?php echo htmlspecialchars($app['location']); ?>
                                    </span>
                                    <span class="flex items-center gap-1 text-gray-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <?php echo date('M j, Y', strtotime($app['submitted_at'])); ?>
                                    </span>
                                </div>

                                <?php if (!empty($app['reason'])): ?>
                                <p class="mt-3 text-sm text-charcoal-300 line-clamp-2 leading-relaxed"><?php echo htmlspecialchars($app['reason']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- No Results State -->
            <div id="noResults" class="hidden bg-white rounded-xl border border-gray-200 p-8 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <p class="text-charcoal-300 font-medium">No matching applications</p>
                <p class="text-gray-400 text-sm mt-1">Try adjusting your search or filter</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Applicant Detail Modal -->
    <div id="applicantModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute inset-0" onclick="closeModal()"></div>
        <div class="absolute inset-0 sm:inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-xl md:max-h-[85vh] bg-white sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-charcoal-400 font-display">Application Details</h3>
                <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-folly hover:bg-folly-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 overflow-y-auto flex-1 scrollbar-hide">
                <div id="modalContent"></div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between gap-3">
                <div id="modalStatusForm"></div>
                <div class="flex gap-2">
                    <button onclick="closeModal()" class="px-4 py-2 text-sm text-charcoal-300 hover:text-charcoal-400 font-medium rounded-lg hover:bg-gray-100 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const applicationsData = <?php echo json_encode($applications); ?>;
        let currentFilter = 'all';
        let currentSearch = '';

        function filterApplications(status) {
            currentFilter = status;
            document.getElementById('statusFilter').value = status;

            // Update filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-folly', 'ring-offset-2');
                if (btn.dataset.filter === status) {
                    btn.classList.add('ring-2', 'ring-folly', 'ring-offset-2');
                }
            });

            applyFilters();
        }

        function searchApplications(query) {
            currentSearch = query.toLowerCase();
            applyFilters();
        }

        function applyFilters() {
            const cards = document.querySelectorAll('.application-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const status = card.dataset.status;
                const name = card.dataset.name;
                const email = card.dataset.email;
                const location = card.dataset.location;

                const matchesStatus = currentFilter === 'all' || status === currentFilter;
                const matchesSearch = !currentSearch ||
                    name.includes(currentSearch) ||
                    email.includes(currentSearch) ||
                    location.includes(currentSearch);

                if (matchesStatus && matchesSearch) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
            document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
        }

        function openModal(id) {
            const app = applicationsData.find(a => a.id === id);
            if (!app) return;

            const status = app.status || 'new';
            const statusConfig = {
                'new': { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'status-new' },
                'contacted': { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'status-contacted' },
                'approved': { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'status-approved' },
                'rejected': { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', dot: 'status-rejected' }
            };
            const sc = statusConfig[status] || statusConfig['new'];

            const date = new Date(app.submitted_at);
            const formattedDate = date.toLocaleDateString('en-US', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                hour: 'numeric', minute: '2-digit'
            });

            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-5">
                    <!-- Header with Avatar -->
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-folly-50 rounded-full flex items-center justify-center text-folly font-semibold text-lg">
                            ${escapeHtml(app.name.substring(0, 2).toUpperCase())}
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-charcoal-400">${escapeHtml(app.name)}</h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border ${sc.bg} ${sc.text} ${sc.border}">
                                    <span class="status-dot ${sc.dot}"></span>
                                    ${status.charAt(0).toUpperCase() + status.slice(1)}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center border border-gray-200">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 font-medium">Email</p>
                                <a href="mailto:${escapeHtml(app.email)}" class="text-sm text-charcoal-400 hover:text-folly truncate block">${escapeHtml(app.email)}</a>
                            </div>
                            <a href="mailto:${escapeHtml(app.email)}" class="p-2 text-gray-400 hover:text-folly hover:bg-folly-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center border border-gray-200">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 font-medium">Phone</p>
                                <a href="tel:${escapeHtml(app.phone)}" class="text-sm text-charcoal-400 hover:text-folly">${escapeHtml(app.phone)}</a>
                            </div>
                            <a href="tel:${escapeHtml(app.phone)}" class="p-2 text-gray-400 hover:text-folly hover:bg-folly-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center border border-gray-200">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 font-medium">Location</p>
                                <p class="text-sm text-charcoal-400">${escapeHtml(app.location)}</p>
                            </div>
                        </div>
                    </div>

                    ${app.reason ? `
                    <!-- Reason -->
                    <div>
                        <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Why They Want to Distribute</h5>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <p class="text-sm text-charcoal-300 whitespace-pre-wrap leading-relaxed">${escapeHtml(app.reason)}</p>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Submission Info -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100 text-xs text-gray-400">
                        <span>ID: #${app.id}</span>
                        <span>${formattedDate}</span>
                    </div>
                </div>
            `;

            // Status update form in footer
            document.getElementById('modalStatusForm').innerHTML = `
                <form method="POST" class="flex items-center gap-2" onclick="event.stopPropagation()">
                    <input type="hidden" name="id" value="${app.id}">
                    <input type="hidden" name="update_status" value="1">
                    <label class="text-xs text-gray-500 font-medium">Status:</label>
                    <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-charcoal-300 focus:border-folly cursor-pointer">
                        <option value="new" ${status === 'new' ? 'selected' : ''}>New</option>
                        <option value="contacted" ${status === 'contacted' ? 'selected' : ''}>Contacted</option>
                        <option value="approved" ${status === 'approved' ? 'selected' : ''}>Approved</option>
                        <option value="rejected" ${status === 'rejected' ? 'selected' : ''}>Rejected</option>
                    </select>
                    <form method="POST" onsubmit="return confirm('Delete this application permanently?')" class="inline">
                        <input type="hidden" name="id" value="${app.id}">
                        <input type="hidden" name="delete" value="1">
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </form>
            `;

            document.getElementById('applicantModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('applicantModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
            if (e.key === '/' && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });

        // Initialize - select "All" filter
        filterApplications('all');
    </script>
    <?php endif; ?>
</body>
</html>
