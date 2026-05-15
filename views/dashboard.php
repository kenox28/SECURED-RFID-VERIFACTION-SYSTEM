<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';
render_header($page_title);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
$total_students = $stmt->fetch()['total_students'];

// Total RFID cards (same as total students since each has one)
$total_rfid = $total_students;

// Recently registered students (last 5)
$stmt = $pdo->query("SELECT student_id, first_name, last_name, created_at FROM students ORDER BY created_at DESC LIMIT 5");
$recent_students = $stmt->fetchAll();
?>
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Students</h5>
                        <h2><?php echo $total_students; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-people display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">RFID Cards Registered</h5>
                        <h2><?php echo $total_rfid; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-credit-card display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Recently Registered Students</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_students)): ?>
                    <p class="text-muted">No students registered yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Registered Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($student['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>