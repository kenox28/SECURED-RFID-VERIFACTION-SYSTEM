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
        </div>
    </div>
</div>

<style>
.custom-delete-modal {
    border: none;
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
}

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