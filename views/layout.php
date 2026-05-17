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
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    </a>
                </div>

                <?php if ($isSuperAdmin): ?>
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
}

function render_footer(): void
{
    ?>
            </div>
        </main>
    </div>

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
    </script>
</body>
</html>
    <?php
}
