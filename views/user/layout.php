<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../backend/student_auth.php';

ensure_student_session();

function render_student_header(string $page_title = 'Student Portal'): void
{
    $student = get_logged_student();

    $studentName = trim(
        ($student['first_name'] ?? '') . ' ' .
        ($student['last_name'] ?? '')
    );

    $currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?> | RFID Student Portal</title>

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

        /* ── SIDEBAR ── */

        .sidebar {
            width: 280px;
            background: #0f172a;
            color: #fff;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.06);
            position: relative;
            z-index: 1000;
            overflow: hidden;
            transition: width 0.25s ease;
            flex-shrink: 0;
        }

        /* COLLAPSED */

        .sidebar.collapsed {
            width: 72px;
        }

        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-section-title,
        .sidebar.collapsed .nav-item span,
        .sidebar.collapsed .logout-btn span,
        .sidebar.collapsed .student-card {
            opacity: 0;
            width: 0;
            overflow: hidden;
            pointer-events: none;
            white-space: nowrap;
        }

        .sidebar.collapsed .student-card {
            height: 0;
            padding: 0;
            margin: 0;
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
            flex-direction: column;
            gap: 0.6rem;
            padding: 1rem 0.5rem;
        }

        .sidebar.collapsed .sidebar-logo {
            justify-content: center;
        }

        /* SIDEBAR HEADER */

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 80px;
        }

        .sidebar-logo {
            display: flex;
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
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            flex-shrink: 0;
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
            color: rgba(255,255,255,0.6);
            margin-top: 0.15rem;
            white-space: nowrap;
        }

        .toggle-btn {
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.3rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: color 0.15s, background 0.15s;
        }

        .toggle-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.08);
        }

        /* SIDEBAR NAV */

        .sidebar-nav {
            padding: 1.25rem 1rem;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sidebar-nav::-webkit-scrollbar {
            display: none;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.45);
            padding: 0 0.8rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            transition: opacity 0.2s;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            color: rgba(255,255,255,0.72);
            margin-bottom: 0.35rem;
            transition: background 0.15s, color 0.15s;
            font-size: 0.9rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-item span {
            transition: opacity 0.2s;
        }

        .nav-item i {
            font-size: 1.1rem;
            min-width: 1.1rem;
            flex-shrink: 0;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }

        .nav-item.active {
            background: #fff;
            color: #0f172a;
            font-weight: 700;
        }

        /* SIDEBAR FOOTER */

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            overflow: hidden;
        }

        .student-card {
            background: rgba(255,255,255,0.06);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.2s, height 0.25s, padding 0.25s, margin 0.25s;
        }

        .student-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
        }

        .student-role {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            margin-top: 0.2rem;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            height: 2.8rem;
            border-radius: 0.875rem;
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
            font-weight: 600;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
            overflow: hidden;
        }

        .logout-btn i {
            flex-shrink: 0;
        }

        .logout-btn:hover {
            background: #ef4444;
            color: #fff;
        }

        /* ── MAIN ── */

        .main {
            flex: 1;
            overflow-y: auto;
            background: #f8fafc;
            min-width: 0;
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
            margin: 0;
        }

        .topbar-left p {
            font-size: 0.82rem;
            color: #94a3b8;
            margin-top: 0.2rem;
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
            font-size: 1.1rem;
        }

        .content {
            padding: 1.5rem;
        }

        /* ── OVERLAY ── */

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.45);
            opacity: 0;
            visibility: hidden;
            transition: 0.2s ease;
            z-index: 999;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* ── MOBILE ── */

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

            .sidebar.collapsed .student-card {
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
                flex-direction: row;
                padding: 1.5rem;
                min-height: 80px;
            }

            .sidebar-overlay {
                display: block;
            }

            .toggle-btn {
                display: none;
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
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="logo-text">
                    <h2>Student Portal</h2>
                    <p>RFID Attendance</p>
                </div>
            </div>

            <button class="toggle-btn" id="toggleSidebar" title="Toggle sidebar">
                <i class="bi bi-layout-sidebar-reverse"></i>
            </button>

        </div>

        <nav class="sidebar-nav">

            <div class="nav-section">

                <div class="nav-section-title">Main Menu</div>

                <a href="/views/user/dashboard.php"
                   class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>

                <a href="/views/user/attendance_history.php"
                   class="nav-item <?= $currentPage === 'attendance_history.php' ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>Attendance History</span>
                </a>

                <a href="/views/user/profile.php"
                   class="nav-item <?= $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-circle"></i>
                    <span>Profile</span>
                </a>

            </div>

        </nav>

        <div class="sidebar-footer">

            <div class="student-card">
                <div class="student-name"><?= htmlspecialchars($studentName); ?></div>
                <div class="student-role">Student Account</div>
            </div>

            <a href="/student_logout.php" class="logout-btn">
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
            <div>
                <button class="mobile-menu-btn" id="openSidebar">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>

        <div class="content">

<?php
}

function render_student_footer(): void
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
    const toggleSidebar  = document.getElementById('toggleSidebar');

    // Restore collapsed state on page load
    if (localStorage.getItem('studentSidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    // Desktop toggle collapse
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem(
                'studentSidebarCollapsed',
                sidebar.classList.contains('collapsed')
            );
        });
    }

    // Mobile open
    if (openSidebar) {
        openSidebar.addEventListener('click', () => {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
        });
    }

    // Mobile close via overlay
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