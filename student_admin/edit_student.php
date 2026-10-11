<?php
/**
 * edit_student.php - Edit student details (and optionally replace profile photo)
 */
require_once 'config.php';

$id = (int)($_GET['id'] ?? $_POST['student_id'] ?? 0);
$student = $id > 0 ? get_student($conn, $id) : null;

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('index.php');
}

$errors = [];
$data = $student;   // form starts with the saved values

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Security token expired. Please reload the page and try again.';
    } else {
        $data   = read_student_input();
        $errors = validate_student($conn, $data, $id);
        $hasPhoto = isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE;

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare(
                    'UPDATE students SET student_name = ?, roll_number = ?, course = ?, department = ?,
                            semester = ?, email = ?, phone = ? WHERE student_id = ?'
                );
                $sem = (int)$data['semester'];
                $stmt->bind_param('ssssissi', $data['student_name'], $data['roll_number'], $data['course'],
                    $data['department'], $sem, $data['email'], $data['phone'], $id);
                $stmt->execute();
                $stmt->close();

                set_flash('success', 'Student details updated successfully.');

                if ($hasPhoto) {
                    [$ok, $msg] = handle_upload($conn, $id, 'profile_photo', $_FILES['profile_photo']);
                    set_flash($ok ? 'success' : 'warning',
                        $ok ? $msg : 'Details saved, but the photo was not uploaded: ' . $msg);
                }
                redirect('view_student.php?id=' . $id);
            } catch (mysqli_sql_exception $ex) {
                $errors[] = ($ex->getCode() === 1062)
                    ? 'Roll number "' . $data['roll_number'] . '" already exists.'
                    : 'Database error. Changes could not be saved.';
            }
        }
    }
}

$photo = get_profile_photo($conn, $id);

render_header('Edit Student', '');
?>

<div class="page-head">
    <div>
        <h1>Edit Student</h1>
        <p class="muted">Student ID #<?= (int)$id ?></p>
    </div>
    <a class="btn btn-light" href="view_student.php?id=<?= (int)$id ?>">Back to profile</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <strong>Please fix the following:</strong>
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="edit_student.php?id=<?= (int)$id ?>" enctype="multipart/form-data" class="card form" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="student_id" value="<?= (int)$id ?>">

    <div class="grid-2">
        <div class="field">
            <label>Student ID</label>
            <input type="text" value="<?= (int)$id ?>" disabled>
        </div>
        <div class="field">
            <label for="student_name">Student Name *</label>
            <input type="text" id="student_name" name="student_name" value="<?= e($data['student_name']) ?>" maxlength="100" required>
        </div>
        <div class="field">
            <label for="roll_number">Roll Number *</label>
            <input type="text" id="roll_number" name="roll_number" value="<?= e($data['roll_number']) ?>" maxlength="30" required>
        </div>
        <div class="field">
            <label for="course">Course *</label>
            <input type="text" id="course" name="course" value="<?= e($data['course']) ?>" maxlength="100" required>
        </div>
        <div class="field">
            <label for="department">Department *</label>
            <input type="text" id="department" name="department" value="<?= e($data['department']) ?>" maxlength="100" required>
        </div>
        <div class="field">
            <label for="semester">Semester *</label>
            <select id="semester" name="semester" required>
                <option value="">-- Select --</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?>" <?= (string)$data['semester'] === (string)$i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="field">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="<?= e($data['email']) ?>" maxlength="150" required>
        </div>
        <div class="field">
            <label for="phone">Phone Number *</label>
            <input type="text" id="phone" name="phone" value="<?= e($data['phone']) ?>" maxlength="16" required>
        </div>
    </div>

    <div class="field">
        <label for="profile_photo">Profile Photo</label>
        <?php if ($photo): ?>
            <div class="current-photo">
                <img src="<?= e($photo['file_path']) ?>" alt="Current profile photo">
                <small class="muted">Current photo: <?= e($photo['original_name']) ?>. Choose a new file below to replace it.</small>
            </div>
        <?php endif; ?>
        <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
        <small class="muted">JPG, JPEG or PNG, up to <?= e(format_size(MAX_FILE_SIZE)) ?>. Leave empty to keep the current photo.</small>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Student</button>
        <a class="btn btn-light" href="view_student.php?id=<?= (int)$id ?>">Cancel</a>
    </div>
</form>

<?php render_footer();
