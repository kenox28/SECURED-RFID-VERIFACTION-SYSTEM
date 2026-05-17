<?php
$page_title = 'Students';
require_once __DIR__ . '/layout.php';
render_header($page_title);

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
require_once dirname(__DIR__) . '/backend/admin/student_actions.php';
$studentData = get_students($search, $page, $per_page);
$students = $studentData['students'];
$total_records = $studentData['total_records'];
$total_pages = $studentData['total_pages'];
$page = $studentData['current_page'];
$search = $studentData['search'];
?>
<div class="row mb-4">
    <div class="col-12">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="search" placeholder="Search by Student ID, Name, or Course" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary me-2">Search</button>
                        <a href="students.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>All Students (<?php echo $total_records; ?>)</h5>
                <a href="register_student.php" class="btn btn-primary">Add New Student</a>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <p class="text-muted">No students found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Year/Section</th>
                                    <th>RFID UID</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <?php if ($student['photo']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" alt="Photo" class="rounded" width="50" height="50">
                                            <?php else: ?>
                                                <i class="bi bi-person-circle text-muted" style="font-size: 2rem;"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td><?php echo htmlspecialchars($student['year_level'] . ' - ' . $student['section']); ?></td>
                                        <td><code><?php echo htmlspecialchars($student['rfid_uid']); ?></code></td>
                                        <td>
                                            <span class="badge bg-<?php echo $student['status'] == 'Active' ? 'success' : 'danger'; ?>">
                                                <?php echo $student['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $student['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this student? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="deleteLink" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(studentId) {
    document.getElementById('deleteLink').href = 'delete_student.php?id=' + studentId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php render_footer(); ?>