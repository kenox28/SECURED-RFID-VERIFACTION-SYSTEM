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

<<<<<<< HEAD
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
=======
<style>
    .students-wrap {
        padding: 1.5rem;
        font-family: 'Sora', sans-serif;
        background: #f8fafc;
        min-height: 100%;
    }

    .page-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header h1 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .page-header p {
        margin: 0.3rem 0 0;
        font-size: 0.8125rem;
        color: #94a3b8;
    }

    .add-btn {
        background: #fb8500;
        color: #fff;
        border: none;
        border-radius: 0.875rem;
        padding: 0.75rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: 0.15s ease;
    }

    .add-btn:hover {
        background: #ea7b00;
        transform: translateY(-1px);
        color: #fff;
    }

    .alert-box {
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        border: 1px solid;
    }

    .alert-success-custom {
        background: #ecfdf5;
        border-color: #d1fae5;
        color: #047857;
    }

    .alert-danger-custom {
        background: #fef2f2;
        border-color: #fecaca;
        color: #b91c1c;
    }

    .search-card,
    .table-card {
        background: #fff;
        border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .search-card {
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .search-form {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .search-input {
        flex: 1;
        min-width: 250px;
        height: 3rem;
        border-radius: 0.875rem;
        border: 1px solid #e2e8f0;
        padding: 0 1rem;
        font-size: 0.875rem;
        outline: none;
        transition: border-color 0.15s;
    }

    .search-input:focus {
        border-color: #fb8500;
    }

    .search-btn,
    .clear-btn {
        height: 3rem;
        border: none;
        border-radius: 0.875rem;
        padding: 0 1.25rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.15s ease;
    }

    .search-btn {
        background: #fb8500;
        color: #fff;
    }

    .search-btn:hover {
        background: #ea7b00;
    }

    .clear-btn {
        background: #f1f5f9;
        color: #475569;
    }

    .clear-btn:hover {
        background: #e2e8f0;
    }

    .table-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .table-card-header h2 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #0f172a;
    }

    .table-wrap {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    thead th {
        padding: 0.85rem 1.5rem;
        text-align: left;
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
        font-weight: 600;
        white-space: nowrap;
    }

    tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    tbody td {
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        color: #334155;
        vertical-align: middle;
        white-space: nowrap;
    }

    .student-photo {
        width: 3rem;
        height: 3rem;
        border-radius: 0.875rem;
        object-fit: cover;
        border: 1px solid #e2e8f0;
    }

    .photo-placeholder {
        width: 3rem;
        height: 3rem;
        border-radius: 0.875rem;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 1.25rem;
    }

    .student-id {
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
        font-weight: 700;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.3rem 0.55rem;
        color: #475569;
    }

    .student-name {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .student-name strong {
        color: #0f172a;
        font-size: 0.875rem;
    }

    .rfid-code {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 0.3rem 0.55rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        color: #475569;
    }

    .status-badge {
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .status-active {
        background: #dcfce7;
        color: #166534;
    }

    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .action-btn {
        border: none;
        border-radius: 0.7rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: 0.15s ease;
    }

    .edit-btn {
        background: #fff7ed;
        color: #ea580c;
    }

    .edit-btn:hover {
        background: #ffedd5;
    }

    .delete-btn {
        background: #fef2f2;
        color: #dc2626;
    }

    .delete-btn:hover {
        background: #fee2e2;
    }

    .empty-state {
        padding: 3rem 1.5rem;
        text-align: center;
    }

    .empty-state p {
        margin: 0;
        color: #94a3b8;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .pagination-wrap {
        padding: 1.25rem;
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .page-btn {
        min-width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.15s ease;
    }

    .page-btn:hover {
        border-color: #fb8500;
        color: #fb8500;
    }

    .page-btn.active {
        background: #fb8500;
        border-color: #fb8500;
        color: #fff;
    }

    .modal-content {
        border-radius: 1.25rem;
        border: none;
    }

    .modal-header,
    .modal-footer {
        border-color: #f1f5f9;
    }

    .modal-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 700;
    }
</style>

<div class="students-wrap">

    <div class="page-header">
        <div>
            <h1>Students</h1>
            <p>Manage registered student information</p>
        </div>

        <a href="register_student.php" class="add-btn">
            Add New Student
        </a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-box alert-success-custom">
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-danger-custom">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="search-card">
        <form method="GET" class="search-form">
            <input
                type="text"
                class="search-input"
                name="search"
                placeholder="Search by Student ID, Name, or Course"
                value="<?= htmlspecialchars($search); ?>"
            >

            <button type="submit" class="search-btn">
                Search
            </button>

            <a href="students.php" class="clear-btn">
                Clear
            </a>
        </form>
    </div>

    <div class="table-card">

        <div class="table-card-header">
            <h2>All Students (<?= $total_records; ?>)</h2>
        </div>

        <?php if (empty($students)): ?>

            <div class="empty-state">
                <p>No students found.</p>
>>>>>>> 042cc59 (with design)
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

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

                                        <img
                                            src="../uploads/<?= htmlspecialchars($student['photo']); ?>"
                                            alt="Photo"
                                            class="student-photo"
                                        >

                                    <?php else: ?>

                                        <div class="photo-placeholder">
                                            <i class="bi bi-person"></i>
                                        </div>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="student-id">
                                        <?= htmlspecialchars($student['student_id']); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="student-name">
                                        <strong>
                                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                        </strong>
                                    </div>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student['course']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student['year_level'] . ' - ' . $student['section']); ?>
                                </td>

                                <td>
                                    <span class="rfid-code">
                                        <?= htmlspecialchars($student['rfid_uid']); ?>
                                    </span>
                                </td>

                                <td>

                                    <span class="status-badge <?= $student['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?= htmlspecialchars($student['status']); ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="edit_student.php?id=<?= $student['id']; ?>"
                                            class="action-btn edit-btn"
                                        >
                                            Edit
                                        </a>

                                        <button
                                            type="button"
                                            class="action-btn delete-btn"
                                            onclick="confirmDelete(<?= $student['id']; ?>)"
                                        >
                                            Delete
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <?php if ($total_pages > 1): ?>

                <div class="pagination-wrap">

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>

                        <a
                            class="page-btn <?= $i == $page ? 'active' : ''; ?>"
                            href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>"
                        >
                            <?= $i; ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>
<<<<<<< HEAD
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
=======

    </div>

</div>
<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-delete-modal">
            <div class="delete-modal-body">

                <div class="delete-icon">
                    <i class="bi bi-trash3-fill"></i>
                </div>

                <h3 class="delete-title">Delete Student?</h3>

                <p class="delete-description">
                    This action cannot be undone.
                    The selected student record will be permanently deleted.
                </p>

                <div class="delete-actions">
                    <button type="button" class="cancel-delete-btn" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <a id="deleteLink" href="#" class="confirm-delete-btn">
                        Yes, Delete
                    </a>
                </div>

            </div>
>>>>>>> 042cc59 (with design)
        </div>
    </div>
</div>

<<<<<<< HEAD
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
=======
<style>
.custom-delete-modal {
    border: none;
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
}
>>>>>>> 042cc59 (with design)

.delete-modal-body {
    padding: 2rem;
    text-align: center;
    background: #ffffff;
}

.delete-icon {
    width: 5rem;
    height: 5rem;
    border-radius: 999px;
    background: #fef2f2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1.25rem auto; /* <-- centers the icon */
}

.delete-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.75rem;
}

.delete-description {
    font-size: 0.9rem;
    line-height: 1.7;
    color: #64748b;
    margin-bottom: 2rem;
}

.delete-actions {
    display: flex;
    gap: 0.75rem;
}

.cancel-delete-btn,
.confirm-delete-btn {
    flex: 1;
    height: 3rem;
    border-radius: 0.9rem;
    font-size: 0.85rem;
    font-weight: 600;
    border: none;
    transition: all 0.2s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.cancel-delete-btn {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.cancel-delete-btn:hover {
    background: #f1f5f9;
}

.confirm-delete-btn {
    background: #dc2626;
    color: white;
}

.confirm-delete-btn:hover {
    background: #b91c1c;
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modalEl = document.getElementById('deleteModal');

    const deleteModal = new bootstrap.Modal(modalEl);

    window.confirmDelete = function(studentId) {

        document.getElementById('deleteLink').href =
            'delete_student.php?id=' + studentId;

        deleteModal.show();
    };

});
</script>

<?php render_footer(); ?>