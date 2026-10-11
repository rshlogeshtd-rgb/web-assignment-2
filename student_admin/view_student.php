<?php
/**
 * view_student.php - Student profile: details, profile photo, documents, upload form
 */
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
$student = $id > 0 ? get_student($conn, $id) : null;

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('index.php');
}

$photo = get_profile_photo($conn, $id);

$stmt = $conn->prepare('SELECT * FROM student_files WHERE student_id = ? ORDER BY upload_date DESC, file_id DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = file_categories();

render_header('Student Profile', '');
?>

<div class="page-head">
    <div>
        <h1><?= e($student['student_name']) ?></h1>
        <p class="muted">Student ID #<?= (int)$student['student_id'] ?> &middot; Roll No <?= e($student['roll_number']) ?></p>
    </div>
    <div class="head-actions">
        <a class="btn btn-warn" href="edit_student.php?id=<?= (int)$id ?>">Edit</a>
        <form method="post" action="delete_student.php" class="inline"
              data-confirm="Delete <?= e($student['student_name']) ?> and all uploaded files? This cannot be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= (int)$id ?>">
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
        <a class="btn btn-light" href="index.php">Back to list</a>
    </div>
</div>

<div class="profile-layout">
    <!-- Profile photo -->
    <aside class="card profile-photo">
        <?php if ($photo): ?>
            <img src="<?= e($photo['file_path']) ?>" alt="Profile photo of <?= e($student['student_name']) ?>">
            <p class="muted small"><?= e($photo['original_name']) ?><br><?= e(format_size($photo['file_size'])) ?></p>
        <?php else: ?>
            <div class="photo-empty"><?= e(strtoupper(mb_substr($student['student_name'], 0, 1))) ?></div>
            <p class="muted small">No profile photo uploaded yet.</p>
        <?php endif; ?>
    </aside>

    <!-- Student details -->
    <section class="card">
        <h2>Student Details</h2>
        <dl class="details">
            <div><dt>Student ID</dt><dd><?= (int)$student['student_id'] ?></dd></div>
            <div><dt>Student Name</dt><dd><?= e($student['student_name']) ?></dd></div>
            <div><dt>Roll Number</dt><dd><?= e($student['roll_number']) ?></dd></div>
            <div><dt>Course</dt><dd><?= e($student['course']) ?></dd></div>
            <div><dt>Department</dt><dd><?= e($student['department']) ?></dd></div>
            <div><dt>Semester</dt><dd><?= (int)$student['semester'] ?></dd></div>
            <div><dt>Email</dt><dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd></div>
            <div><dt>Phone</dt><dd><?= e($student['phone']) ?></dd></div>
            <div><dt>Added on</dt><dd><?= e(format_date($student['created_at'])) ?></dd></div>
        </dl>
    </section>
</div>

<!-- Upload form -->
<section class="card">
    <h2>Upload File</h2>
    <form method="post" action="upload_file.php" enctype="multipart/form-data" class="upload-form">
        <?= csrf_field() ?>
        <input type="hidden" name="student_id" value="<?= (int)$id ?>">
        <div class="field">
            <label for="category">File Type</label>
            <select name="category" id="category" required>
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field grow">
            <label for="file">Choose File</label>
            <input type="file" name="file" id="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
    <small class="muted">
        Profile Photo: JPG, JPEG, PNG only (a new photo replaces the old one).
        Other types: JPG, JPEG, PNG, PDF, DOC, DOCX. Maximum size <?= e(format_size(MAX_FILE_SIZE)) ?>.
    </small>
</section>

<!-- Uploaded files -->
<section class="card">
    <h2>Uploaded Files <span class="badge"><?= count($files) ?></span></h2>

    <?php if (!$files): ?>
        <div class="empty"><p>No files uploaded for this student yet.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $f): ?>
                    <tr>
                        <td><?= (int)$f['file_id'] ?></td>
                        <td><span class="badge badge-cat"><?= e($categories[$f['file_category']] ?? $f['file_category']) ?></span></td>
                        <td><?= e($f['original_name']) ?></td>
                        <td><?= e(strtoupper(pathinfo($f['stored_name'], PATHINFO_EXTENSION))) ?>
                            <small class="muted">(<?= e($f['file_type']) ?>)</small></td>
                        <td><?= e(format_size($f['file_size'])) ?></td>
                        <td><?= e(format_date($f['upload_date'])) ?></td>
                        <td class="actions">
                            <a class="btn btn-sm btn-info" href="<?= e($f['file_path']) ?>" target="_blank" rel="noopener">Open</a>
                            <a class="btn btn-sm btn-success" href="<?= e($f['file_path']) ?>" download="<?= e($f['original_name']) ?>">Download</a>
                            <form method="post" action="delete_file.php" class="inline"
                                  data-confirm="Delete this file? This cannot be undone.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="file_id" value="<?= (int)$f['file_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php render_footer();
