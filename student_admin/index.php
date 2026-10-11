<?php
/**
 * index.php - Home / Dashboard + Student List with search
 */
require_once 'config.php';

// ---- Dashboard numbers ----
$totalStudents = (int)$conn->query('SELECT COUNT(*) FROM students')->fetch_row()[0];
$totalFiles    = (int)$conn->query('SELECT COUNT(*) FROM student_files')->fetch_row()[0];
$totalCourses  = (int)$conn->query('SELECT COUNT(DISTINCT course) FROM students')->fetch_row()[0];
$totalDepts    = (int)$conn->query('SELECT COUNT(DISTINCT department) FROM students')->fetch_row()[0];

// ---- Student list (with optional search) ----
$search = trim($_GET['search'] ?? '');

$sql = "SELECT s.*,
          (SELECT f.file_path FROM student_files f
            WHERE f.student_id = s.student_id AND f.file_category = 'profile_photo'
            ORDER BY f.file_id DESC LIMIT 1) AS photo,
          (SELECT COUNT(*) FROM student_files f2 WHERE f2.student_id = s.student_id) AS file_count
        FROM students s";

if ($search !== '') {
    $sql .= ' WHERE s.student_name LIKE ? OR s.roll_number LIKE ? OR s.course LIKE ? OR s.department LIKE ?';
    $stmt = $conn->prepare($sql . ' ORDER BY s.student_id DESC');
    $like = '%' . $search . '%';
    $stmt->bind_param('ssss', $like, $like, $like, $like);
} else {
    $stmt = $conn->prepare($sql . ' ORDER BY s.student_id DESC');
}
$stmt->execute();
$students = $stmt->get_result();

render_header('Home', 'home');
?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p class="muted">Manage student records, profile photos and documents.</p>
    </div>
    <a class="btn btn-primary" href="add_student.php">+ Add Student</a>
</div>

<section class="stats">
    <div class="stat"><span class="stat-num"><?= $totalStudents ?></span><span class="stat-label">Students</span></div>
    <div class="stat"><span class="stat-num"><?= $totalFiles ?></span><span class="stat-label">Uploaded files</span></div>
    <div class="stat"><span class="stat-num"><?= $totalCourses ?></span><span class="stat-label">Courses</span></div>
    <div class="stat"><span class="stat-num"><?= $totalDepts ?></span><span class="stat-label">Departments</span></div>
</section>

<section class="card" id="student-list">
    <div class="card-head">
        <h2>Student List</h2>
        <form method="get" action="index.php#student-list" class="search">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, roll no, course, department">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== ''): ?>
                <a class="btn btn-light" href="index.php#student-list">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($students->num_rows === 0): ?>
        <div class="empty">
            <p><?= $search !== '' ? 'No students match "' . e($search) . '".' : 'No students yet.' ?></p>
            <a class="btn btn-primary" href="add_student.php">Add the first student</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th>Course</th>
                    <th>Department</th>
                    <th>Sem</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Files</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($s = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$s['student_id'] ?></td>
                        <td>
                            <div class="person">
                                <?php if ($s['photo']): ?>
                                    <img class="avatar" src="<?= e($s['photo']) ?>" alt="Photo of <?= e($s['student_name']) ?>">
                                <?php else: ?>
                                    <span class="avatar avatar-initial"><?= e(strtoupper(mb_substr($s['student_name'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <strong><?= e($s['student_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= e($s['roll_number']) ?></td>
                        <td><?= e($s['course']) ?></td>
                        <td><?= e($s['department']) ?></td>
                        <td><?= (int)$s['semester'] ?></td>
                        <td><?= e($s['email']) ?></td>
                        <td><?= e($s['phone']) ?></td>
                        <td><span class="badge"><?= (int)$s['file_count'] ?></span></td>
                        <td class="actions">
                            <a class="btn btn-sm btn-info" href="view_student.php?id=<?= (int)$s['student_id'] ?>">View</a>
                            <a class="btn btn-sm btn-warn" href="edit_student.php?id=<?= (int)$s['student_id'] ?>">Edit</a>
                            <form method="post" action="delete_student.php" class="inline"
                                  data-confirm="Delete <?= e($s['student_name']) ?> and all uploaded files? This cannot be undone.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="student_id" value="<?= (int)$s['student_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
$stmt->close();
render_footer();
