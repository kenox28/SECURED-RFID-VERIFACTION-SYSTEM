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

<!-- Alerts -->
<?php if (!empty($success)): ?>
    <div class="card-container mb-4" style="background: rgba(16, 185, 129, 0.05); border-color: var(--color-success);">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5" style="color: var(--color-success);" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="card-container mb-4" style="background: rgba(239, 68, 68, 0.05); border-color: var(--color-error);">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5" style="color: var(--color-error);" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- Search & Filter Card -->
<div class="card-container mb-6">
    <h3 class="text-lg-heading mb-4">Find Students</h3>
    <form method="GET" class="flex gap-2 flex-wrap">
        <input type="text" class="input-field flex-1" name="search" placeholder="Search by Student ID, Name, or Course..." value="<?php echo htmlspecialchars($search); ?>" style="min-width: 200px;">
        <button type="submit" class="btn-primary">
            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
            </svg>
            Search
        </button>
        <?php if (!empty($search)): ?>
            <a href="students.php" class="btn-outline">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Students Table Card -->
<div class="card-container">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg-heading">All Students <span style="color: var(--color-text-secondary); font-weight: 500;">(<?php echo number_format($total_records); ?>)</span></h3>
        <a href="register_student.php" class="btn-primary">
            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
            </svg>
            Add Student
        </a>
    </div>

    <?php if (empty($students)): ?>
        <div class="text-center p-8" style="color: var(--color-text-secondary);">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p>No students found. <a href="register_student.php" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Register one now →</a></p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year / Section</th>
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
                                    <img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 0.5rem; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 0.5rem; background: var(--color-accent-soft); display: flex; align-items: center; justify-content: center; color: var(--color-primary);">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><code class="text-mono"><?php echo htmlspecialchars($student['student_id']); ?></code></td>
                            <td class="font-semibold"><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'][0] . '. ' : '') . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['course']); ?></td>
                            <td class="text-sm" style="color: var(--color-text-secondary);"><?php echo htmlspecialchars($student['year_level'] . ' — ' . $student['section']); ?></td>
                            <td><code class="text-mono text-xs"><?php echo htmlspecialchars($student['rfid_uid']); ?></code></td>
                            <td>
                                <span class="badge <?php echo $student['status'] === 'Active' ? 'badge-success' : 'badge-error'; ?>">
                                    <?php echo $student['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn-secondary btn-sm">Edit</a>
                                    <button type="button" class="btn-outline btn-sm" onclick="confirmDelete(<?php echo $student['id']; ?>)" style="color: var(--color-error); border-color: var(--color-error);">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center items-center gap-2 mt-6 pt-4 border-t border-slate-100">
                <?php if ($page > 1): ?>
                    <a href="?search=<?php echo urlencode($search); ?>&page=1" class="btn-outline btn-sm">First</a>
                    <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="btn-outline btn-sm">← Prev</a>
                <?php endif; ?>
                
                <div class="flex gap-1">
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="btn-primary btn-sm" style="background: var(--color-primary);"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" class="btn-outline btn-sm"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <?php if ($page < $total_pages): ?>
                    <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="btn-outline btn-sm">Next →</a>
                    <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $total_pages; ?>" class="btn-outline btn-sm">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card-container" style="max-width: 400px;">
        <h3 class="text-lg-heading mb-2">Delete Student?</h3>
        <p style="color: var(--color-text-secondary); margin-bottom: 1.5rem;">Are you sure you want to delete <strong id="deleteStudentName"></strong>? This action cannot be undone.</p>
        <div class="flex gap-2 justify-end">
            <button type="button" class="btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteForm" method="POST" action="delete_student.php" style="display: inline;">
                <input type="hidden" name="id" id="deleteStudentId">
                <button type="submit" class="btn-primary" style="background: var(--color-error);">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(studentId) {
    document.getElementById('deleteStudentId').value = studentId;
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

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