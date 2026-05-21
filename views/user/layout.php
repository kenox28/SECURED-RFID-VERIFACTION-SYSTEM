<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/auth.php';

ensure_user_session();

function render_header(string $page_title = 'RFID Verification System'): void
{
    $isSuperAdmin = is_super_admin();
    $userFullname = $_SESSION['fullname'] ?? 'Admin';
    $userDept = $_SESSION['department_id'] ? 'Department Admin' : 'Super Admin';
    $currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?> | RFID System</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            overflow: hidden;
        }

        a {
            text-decoration: none;
        }

        .layout {
            display: flex;
            height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 280px;
            background: #0f172a;
            color: #fff;
            display: flex;
            flex-direction: column;
            transition: width 0.25s ease;
            border-right: 1px solid rgba(255,255,255,0.06);
            position: relative;
            z-index: 1000;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 80px;
            white-space: nowrap;
        }

        .sidebar-logo {
            
            align-items: center;
            gap: 0.75rem;
            overflow: hidden;
        }

        .logo-icon {
            width: 2.5rem;
            height: 2.5rem;
            min-width: 2.5rem;
            border-radius: 0.875rem;
            background: linear-gradient(135deg, #fb8500, #ffb703);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            font-weight: 700;
        }

        .logo-text h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
        }

        .logo-text p {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.55);
            margin-top: 0.15rem;
            white-space: nowrap;
        }

        .sidebar-header-actions {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .toggle-btn,
        .mobile-close {
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .toggle-btn:hover,
        .mobile-close:hover {
            color: #fff;
            background: rgba(255,255,255,0.08);
        }

        .mobile-close {
            display: none;
        }

        .sidebar-nav {
            padding: 1.25rem 1rem;
            overflow-y: auto;
            overflow-x: hidden;
            flex: 1;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.4);
            font-weight: 600;
            padding: 0 0.85rem;
            margin-bottom: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.2s ease;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            color: rgba(255,255,255,0.72);
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.15s ease, color 0.15s ease;
            margin-bottom: 0.35rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-item i {
            font-size: 1.1rem;
            min-width: 1.1rem;
            flex-shrink: 0;
        }

        .nav-item span {
            overflow: hidden;
            transition: opacity 0.2s ease, width 0.2s ease;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }

        .nav-item.active {
            background: #ffffff;
            color: #0f172a;
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            overflow: hidden;
        }

        .admin-card {
            background: rgba(255,255,255,0.06);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.2s ease, height 0.25s ease, padding 0.25s ease, margin 0.25s ease;
        }

        .admin-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
        }

        .admin-role {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.55);
            margin-top: 0.2rem;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            height: 2.85rem;
            border-radius: 0.875rem;
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.75);
            font-size: 0.8125rem;
            font-weight: 600;
            transition: background 0.15s ease, color 0.15s ease;
            white-space: nowrap;
            overflow: hidden;
        }

        .logout-btn:hover {
            background: #ef4444;
            color: #fff;
        }

        .logout-btn i {
            flex-shrink: 0;
        }

        /* COLLAPSED STATE */

        .sidebar.collapsed {
            width: 72px;
        }

        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-section-title,
        .sidebar.collapsed .nav-item span,
        .sidebar.collapsed .logout-btn span {
            opacity: 0;
            width: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .sidebar.collapsed .admin-card {
            opacity: 0;
            height: 0;
            padding: 0;
            margin: 0;
            pointer-events: none;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 0.85rem;
            gap: 0;
        }

        .sidebar.collapsed .logout-btn {
            gap: 0;
        }

        .sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding: 1.5rem 0.85rem;
        }

        .sidebar.collapsed .sidebar-header-actions .toggle-btn {
            /* still visible so user can re-expand */
        }

        /* MAIN */

        .main {
            flex: 1;
            overflow-y: auto;
            background: #f8fafc;
            transition: none;
        }

        .topbar {
            height: 80px;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid #f1f5f9;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .topbar-left p {
            font-size: 0.8125rem;
            color: #94a3b8;
            margin-top: 0.2rem;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .live-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 0.55rem 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.35; }
        }

        .live-badge span {
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
        }

        .mobile-menu-btn {
            display: none;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.875rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            cursor: pointer;
            color: #475569;
            align-items: center;
            justify-content: center;
        }

        .content {
            padding: 1.5rem;
        }

        /* OVERLAY */

        .sidebar-overlay {
            display: none;
        }

        /* MOBILE */

        @media (max-width: 768px) {

            body {
                overflow: auto;
            }

            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                height: 100%;
                width: 280px;
                transition: left 0.2s ease;
            }

            .sidebar.active {
                left: 0;
            }

            /* disable collapse on mobile */
            .sidebar.collapsed {
                width: 280px;
            }

            .sidebar.collapsed .logo-text,
            .sidebar.collapsed .nav-section-title,
            .sidebar.collapsed .nav-item span,
            .sidebar.collapsed .logout-btn span {
                opacity: 1;
                width: auto;
            }

            .sidebar.collapsed .admin-card {
                opacity: 1;
                height: auto;
                padding: 1rem;
                margin-bottom: 0.85rem;
            }

            .sidebar.collapsed .nav-item {
                justify-content: flex-start;
                padding: 0.85rem 1rem;
                gap: 0.85rem;
            }

            .sidebar.collapsed .sidebar-header {
                justify-content: space-between;
                padding: 1.5rem;
            }

            .sidebar-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.45);
                opacity: 0;
                visibility: hidden;
                transition: 0.2s ease;
                z-index: 999;
            }

            .sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .toggle-btn {
                display: none;
            }

            .mobile-close {
                display: flex;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .topbar {
                padding: 0 1rem;
            }

            .content {
                padding: 1rem;
            }

            .live-badge {
                display: none;
            }
        }
        .sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding: 1.5rem 0.5rem;
            flex-direction: column;
            gap: 0.75rem;
            min-height: auto;
        }

        .sidebar.collapsed .sidebar-logo {
            justify-content: center;
        }

        .sidebar.collapsed .sidebar-header-actions {
            justify-content: center;
        }
        .sidebar-nav::-webkit-scrollbar {
            display: none;               /* Chrome/Safari */
        }
    </style>

</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="layout">

        <aside class="sidebar" id="sidebar">

            <div class="sidebar-header">

                <div class="sidebar-logo">
                    <div class="logo-icon">
                        <i class="bi bi-broadcast-pin"></i>
                    </div>
                    <div class="logo-text">
                        <h2>RFID System</h2>
                        <p>Verification Platform</p>
                    </div>
                </div>

                <div class="sidebar-header-actions">
                    <button class="toggle-btn" id="toggleSidebar" title="Toggle sidebar">
                        <i class="bi bi-layout-sidebar-reverse"></i>
                    </button>
                    <button class="mobile-close" id="closeSidebar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

            </div>

            <nav class="sidebar-nav">

                <div class="nav-section">
                    <div class="nav-section-title">Main</div>

                    <a href="/views/dashboard.php" class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="bi bi-grid"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="/views/students.php" class="nav-item <?= $currentPage === 'students.php' ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i>
                        <span>Students</span>
                    </a>

                    <a href="/views/register_student.php" class="nav-item <?= $currentPage === 'register_student.php' ? 'active' : ''; ?>">
                        <i class="bi bi-person-plus"></i>
                        <span>Register Student</span>
                    </a>

                    <a href="/views/profile.php" class="nav-item <?= $currentPage === 'profile.php' ? 'active' : ''; ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Attendance</div>

                    <a href="/views/attendance/attendance_logs.php" class="nav-item <?= $currentPage === 'attendance_logs.php' ? 'active' : ''; ?>">
                        <i class="bi bi-clock-history"></i>
                        <span>Attendance Logs</span>
                    </a>

                    <a href="/views/attendance/attendance_sessions.php" class="nav-item <?= $currentPage === 'attendance_sessions.php' ? 'active' : ''; ?>">
                        <i class="bi bi-calendar2-week"></i>
                        <span>Sessions</span>
                    </a>

                    <a href="/views/attendance/attendance_reports.php" class="nav-item <?= $currentPage === 'attendance_reports.php' ? 'active' : ''; ?>">
                        <i class="bi bi-bar-chart"></i>
                        <span>Reports</span>
                    </a>
                </div>

                <?php if ($isSuperAdmin): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Administration</div>

                    <a href="/views/manage_admins.php" class="nav-item <?= $currentPage === 'manage_admins.php' ? 'active' : ''; ?>">
                        <i class="bi bi-person-gear"></i>
                        <span>Manage Admins</span>
                    </a>

                    <a href="/views/manage_departments.php" class="nav-item <?= $currentPage === 'manage_departments.php' ? 'active' : ''; ?>">
                        <i class="bi bi-building"></i>
                        <span>Departments</span>
                    </a>

                    <a href="/views/activity_logs.php" class="nav-item <?= $currentPage === 'activity_logs.php' ? 'active' : ''; ?>">
                        <i class="bi bi-activity"></i>
                        <span>Activity Logs</span>
                    </a>
                </div>
                <?php endif; ?>

            </nav>

            <div class="sidebar-footer">
                <div class="admin-card">
                    <div class="admin-name"><?= htmlspecialchars($userFullname); ?></div>
                    <div class="admin-role"><?= htmlspecialchars($userDept); ?></div>
                </div>

                <a href="/logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>

        </aside>

        <main class="main">

            <div class="topbar">
                <div class="topbar-left">
                    <h1><?= htmlspecialchars($page_title); ?></h1>
                    <p><?= date('l, F j, Y'); ?></p>
                </div>
                <div class="topbar-right">
                    <div class="live-badge">
                        <span class="live-dot"></span>
                        <span>Live System</span>
                    </div>
                    <button class="mobile-menu-btn" id="openSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </div>

            <div class="content">

<?php
}

function render_footer(): void
{
?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        const sidebar        = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const openSidebar    = document.getElementById('openSidebar');
        const closeSidebar   = document.getElementById('closeSidebar');
        const toggleSidebar  = document.getElementById('toggleSidebar');

        // Restore collapsed state on page load
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }

        // Toggle collapse
        if (toggleSidebar) {
            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // Mobile open
        if (openSidebar) {
            openSidebar.addEventListener('click', () => {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
            });
        }

        // Mobile close
        if (closeSidebar) {
            closeSidebar.addEventListener('click', closeSidebarMenu);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebarMenu);
        }

        function closeSidebarMenu() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        }

    </script>

</body>
</html>
<?php
}
?>