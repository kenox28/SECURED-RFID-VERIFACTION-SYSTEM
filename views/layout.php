<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /login.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

function render_header(string $page_title = 'RFID System'): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar bg-dark text-white">
            <div class="sidebar-brand p-3 mb-3 border-bottom border-secondary">
                <div class="h5 mb-1">RFID SYSTEM</div>
                <div class="small text-muted">Admin Dashboard</div>
            </div>
            <nav class="nav flex-column px-2">
                <a class="nav-link text-white" href="/views/dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a>
                <a class="nav-link text-white" href="/views/students.php"><i class="bi bi-people me-2"></i>Students</a>
                <a class="nav-link text-white" href="/views/register_student.php"><i class="bi bi-person-plus me-2"></i>Register Student</a>
                <a class="nav-link text-white" href="/views/profile.php"><i class="bi bi-person-circle me-2"></i>Profile</a>
                <a class="nav-link text-white" href="/views/attendance/attendance_logs.php"><i class="bi bi-clock-history me-2"></i>Attendance Logs</a>
                <a class="nav-link text-white" href="/views/attendance/attendance_sessions.php"><i class="bi bi-calendar-check me-2"></i>Sessions</a>
                <a class="nav-link text-white" href="/views/attendance/attendance_reports.php"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reports</a>
                <a class="nav-link text-white" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
                    </div>
                    <button class="btn btn-outline-secondary d-md-none" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
    <?php
}

function render_footer(): void
{
    ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/script.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
    <?php
}
