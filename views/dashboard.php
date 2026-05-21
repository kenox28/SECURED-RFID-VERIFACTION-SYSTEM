<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';

render_header($page_title);

$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
$total_students = $stmt->fetch()['total_students'];
$total_rfid = $total_students;

$stmt = $pdo->query("SELECT student_id, first_name, last_name, created_at FROM students ORDER BY created_at DESC LIMIT 5");
$recent_students = $stmt->fetchAll();
?>

<style>
    .dashboard-wrap {
        padding: 1.5rem;
        font-family: 'Sora', sans-serif;
        background: #f8fafc;
        min-height: 100%;
    }

    /* Page header */
    .page-header {
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header h1 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.02em;
        margin: 0;
    }

    .page-header p {
        font-size: 0.8125rem;
        color: #94a3b8;
        margin: 0.25rem 0 0;
    }

    .live-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.5rem 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #34d399;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.4; }
    }

    .live-badge span:last-child {
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
    }

    /* Stat cards */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: #fff;
        border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        padding: 1.375rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -2rem;
        right: -2rem;
        width: 6rem;
        height: 6rem;
        border-radius: 50%;
        opacity: 0.4;
    }

    .stat-card.blue::before  { background: #caf0f8; }
    .stat-card.green::before { background: #d1fae5; }

    .stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }

    .stat-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
    }

    .stat-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.625rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon.blue  { background: #caf0f8; }
    .stat-icon.green { background: #d1fae5; }

    .stat-icon svg {
        width: 1.125rem;
        height: 1.125rem;
    }

    .stat-icon.blue  svg { stroke: #0369a1; }
    .stat-icon.green svg { stroke: #059669; }

    .stat-number {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
        position: relative;
    }

    .stat-sub {
        font-size: 0.75rem;
        color: #94a3b8;
        position: relative;
    }

    /* Quick action cards (super admin) */
    .section-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 1rem;
        letter-spacing: -0.01em;
    }

    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .action-card {
        background: #fff;
        border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        padding: 1.375rem;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        transition: border-color 0.15s, box-shadow 0.15s, transform 0.1s;
    }

    .action-card:hover {
        border-color: #fb8500;
        box-shadow: 0 4px 12px rgba(251,133,0,0.12);
        transform: translateY(-1px);
    }

    .action-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .action-icon.orange { background: #fff3e0; }
    .action-icon.slate  { background: #f1f5f9; }
    .action-icon.blue   { background: #eff6ff; }

    .action-icon svg { width: 1.25rem; height: 1.25rem; }
    .action-icon.orange svg { stroke: #fb8500; }
    .action-icon.slate  svg { stroke: #475569; }
    .action-icon.blue   svg { stroke: #3b82f6; }

    .action-card h3 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .action-card p {
        font-size: 0.8125rem;
        color: #94a3b8;
        margin: 0;
        line-height: 1.5;
    }

    .action-link {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #fb8500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* Recent students table */
    .table-card {
        background: #fff;
        border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .table-card-header {
        padding: 1.125rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .table-card-header h2 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .view-all {
        font-size: 0.75rem;
        font-weight: 600;
        color: #fb8500;
        text-decoration: none;
    }

    .view-all:hover { color: #e07600; }

    .table-wrap { overflow-x: auto; }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    thead th {
        padding: 0.75rem 1.5rem;
        text-align: left;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
        white-space: nowrap;
    }

    tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.1s;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }

    tbody td {
        padding: 0.875rem 1.5rem;
        color: #334155;
        vertical-align: middle;
    }

    .student-id {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        background: #f8fafc;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid #e2e8f0;
    }

    .student-name {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .avatar {
        width: 1.875rem;
        height: 1.875rem;
        border-radius: 50%;
        background: #caf0f8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6875rem;
        font-weight: 700;
        color: #0369a1;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    .date-badge {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .empty-state {
        padding: 3rem 1.5rem;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }

    .empty-icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-icon svg { width: 1.25rem; height: 1.25rem; stroke: #94a3b8; }

    .empty-state p {
        font-size: 0.875rem;
        color: #94a3b8;
        font-weight: 500;
    }
</style>

<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p><?= date('l, F j, Y') ?> · Overview</p>
        </div>
        <div class="live-badge">
            <span class="live-dot"></span>
            <span>Live</span>
        </div>
    </div>

    <div class="stat-grid">

        <div class="stat-card blue">
            <div class="stat-top">
                <span class="stat-label">Total Students</span>
                <div class="stat-icon blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="stat-number"><?= $total_students ?></p>
            <p class="stat-sub">Registered in the system</p>
        </div>

        <div class="stat-card green">
            <div class="stat-top">
                <span class="stat-label">RFID Cards</span>
                <div class="stat-icon green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
            <p class="stat-number"><?= $total_rfid ?></p>
            <p class="stat-sub">Cards registered</p>
        </div>

    </div>

    <?php if (is_super_admin()): ?>

        <p class="section-title">Quick Actions</p>

        <div class="action-grid">

            <a href="manage_admins.php" class="action-card">
                <div class="action-icon orange">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3>Manage Admins</h3>
                    <p>Create, edit, and delete system admin accounts.</p>
                </div>
                <span class="action-link">Go to Admins →</span>
            </a>

            <a href="manage_departments.php" class="action-card">
                <div class="action-icon slate">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3>Departments</h3>
                    <p>Create and manage department entries.</p>
                </div>
                <span class="action-link">Go to Departments →</span>
            </a>

            <a href="activity_logs.php" class="action-card">
                <div class="action-icon blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <h3>Activity Logs</h3>
                    <p>View recent system activity and delete old logs.</p>
                </div>
                <span class="action-link">Go to Logs →</span>
            </a>

        </div>

    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <h2>Recently Registered Students</h2>
            <a href="students.php" class="view-all">View all →</a>
        </div>

        <?php if (empty($recent_students)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p>No students registered yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_students as $student): ?>
                            <tr>
                                <td>
                                    <span class="student-id"><?= htmlspecialchars($student['student_id']) ?></span>
                                </td>
                                <td>
                                    <div class="student-name">
                                        <div class="avatar">
                                            <?= strtoupper(substr($student['first_name'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="date-badge"><?= date('M d, Y · H:i', strtotime($student['created_at'])) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php render_footer(); ?>