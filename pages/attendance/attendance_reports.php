<?php
// attendance_reports.php
require_once '../../includes/header.php';

$page_title = 'Attendance Reports';

// Fetch attendance sessions for report generation
$stmt = $pdo->query("SELECT * FROM attendance_sessions ORDER BY created_at DESC");
$sessions = $stmt->fetchAll();

?>
<div class="container">
    <h1 class="mt-4">Attendance Reports</h1>
    <form method="GET" action="generate_report.php">
        <div class="mb-3">
            <label for="session_id" class="form-label">Select Session</label>
            <select name="session_id" id="session_id" class="form-select">
                <?php foreach ($sessions as $session): ?>
                    <option value="<?php echo $session['id']; ?>">
                        <?php echo htmlspecialchars($session['session_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>