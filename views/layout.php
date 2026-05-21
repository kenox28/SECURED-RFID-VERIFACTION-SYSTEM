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
<<<<<<< HEAD
    ?>
=======
    $currentPage = basename($_SERVER['PHP_SELF']);
?>
>>>>>>> 042cc59 (with design)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title><?php echo htmlspecialchars($page_title); ?> | RFID System</title>
    <link rel="stylesheet" href="/assets/css/design-system.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        /* Prevent flash of unstyled content on dark mode */
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
</head>
<body x-data="appState()" x-init="checkTheme()" @theme:toggle.window="toggleTheme()">
    <div class="flex h-screen">
        <!-- SIDEBAR -->
        <aside :class="sidebarOpen ? 'sidebar sidebar-open' : 'sidebar sidebar-closed'" @click.outside="if(isMobile) sidebarOpen = false">
            <div class="sidebar-brand">
                <span class="sidebar-brand-text">RFID System</span>
                <button @click="sidebarOpen = !sidebarOpen" class="text-white opacity-70 hover:opacity-100 md:hidden text-xl p-1" title="Toggle Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav flex-1">
                <a href="/views/dashboard.php" class="active">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    <span class="sidebar-nav-label">Dashboard</span>
                </a>
                <a href="/views/students.php">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 11a6 6 0 11-12 0 6 6 0 0112 0zM13 16a1 1 0 11-2 0 1 1 0 012 0zM17 15a1 1 0 10-2 0 1 1 0 002 0zM19 14a1 1 0 11-2 0 1 1 0 012 0z"></path>
                    </svg>
                    <span class="sidebar-nav-label">Students</span>
                </a>
                <a href="/views/register_student.php">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 16A8 8 0 100 8a8 8 0 008 8zm1-11a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path>
                    </svg>
                    <span class="sidebar-nav-label">Register</span>
                </a>
                <a href="/views/profile.php">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sidebar-nav-label">Profile</span>
                </a>

                <div class="mt-4 pt-4 border-t border-white border-opacity-10">
                    <a href="/views/attendance/attendance_logs.php">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.5 1.5H5.75A2.25 2.25 0 003.5 3.75v12.5A2.25 2.25 0 005.75 18.5h8.5a2.25 2.25 0 002.25-2.25V6.5m-12-4v3.25m8-3.25v3.25M3.5 9.5h13"></path>
                        </svg>
                        <span class="sidebar-nav-label">Attendance</span>
                    </a>
                    <a href="/views/attendance/attendance_sessions.php">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.5A1.5 1.5 0 0117 5.5v9A1.5 1.5 0 0115.5 16h-11A1.5 1.5 0 013 14.5v-9A1.5 1.5 0 014.5 4h.5V2.75A.75.75 0 015.75 2zm0 6.5a.75.75 0 01.75-.75h8.5a.75.75 0 010 1.5h-8.5a.75.75 0 01-.75-.75z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="sidebar-nav-label">Sessions</span>
                    </a>
                    <a href="/views/attendance/attendance_reports.php">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2 4a1 1 0 011-1h6a1 1 0 011 1v12a1 1 0 11-2 0V5H3a1 1 0 01-1-1zm8-1a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm4-1a1 1 0 011 1v12a1 1 0 11-2 0V3a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="sidebar-nav-label">Reports</span>
=======
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
>>>>>>> 042cc59 (with design)
                    </a>
                </div>

                <?php if ($isSuperAdmin): ?>
<<<<<<< HEAD
                    <div class="mt-4 pt-4 border-t border-white border-opacity-10">
                        <a href="/views/manage_admins.php">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                            </svg>
                            <span class="sidebar-nav-label">Manage Admins</span>
                        </a>
                        <a href="/views/manage_departments.php">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.5 1.5H5.75A2.25 2.25 0 003.5 3.75v12.5A2.25 2.25 0 005.75 18.5h8.5a2.25 2.25 0 002.25-2.25V6.5"></path>
                            </svg>
                            <span class="sidebar-nav-label">Departments</span>
                        </a>
                        <a href="/views/activity_logs.php">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.895 1.949H7a.75.75 0 000 1.5h4.956"></path>
                            </svg>
                            <span class="sidebar-nav-label">Activity Logs</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="border-t border-white border-opacity-10 p-4">
                <button @click="toggleTheme()" class="flex items-center gap-2 w-full text-white opacity-70 hover:opacity-100 text-sm mb-3">
                    <svg x-show="!isDark" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.293 2.293a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zm2.828 2.828a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zM10 7a3 3 0 100 6 3 3 0 000-6zm3.879-1.707a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zM16 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm2.121 2.879a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zM10 17a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-5.379-1.707a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zM4 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm0-5a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1zm4.293 11.707a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <svg x-show="isDark" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <span class="sidebar-nav-label"><span x-show="!isDark">Dark</span><span x-show="isDark">Light</span></span>
                </button>

                <a href="/logout.php" class="flex items-center gap-2 text-white opacity-70 hover:opacity-100 text-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sidebar-nav-label">Logout</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="text-sm" style="color: var(--color-text-secondary); margin-top: 0.25rem;">
                        <?php echo htmlspecialchars($userFullname); ?> • <?php echo htmlspecialchars($userDept); ?>
                    </p>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" class="md:hidden bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <div class="container-page">
    <?php
=======
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
>>>>>>> 042cc59 (with design)
}

function render_footer(): void
{
?>
            </div>
        </main>
    </div>

<<<<<<< HEAD
    <script>
        function appState() {
            return {
                sidebarOpen: window.innerWidth > 768,
                isDark: false,
                isMobile: window.innerWidth < 768,

                checkTheme() {
                    this.isDark = document.documentElement.classList.contains('dark');
                },

                toggleTheme() {
                    this.isDark = !this.isDark;
                    const theme = this.isDark ? 'dark' : 'light';
                    
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    
                    localStorage.setItem('theme', theme);
                    window.dispatchEvent(new CustomEvent('theme:toggle'));
                }
            };
        }

        // Handle responsive sidebar
        window.addEventListener('resize', () => {
            const isMobile = window.innerWidth < 768;
            if (!isMobile) {
                document.querySelector('[x-data="appState()"]').sidebarOpen = true;
            }
        });
=======
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

>>>>>>> 042cc59 (with design)
    </script>

</body>
</html>
<?php
}
?>