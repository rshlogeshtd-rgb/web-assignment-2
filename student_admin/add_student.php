<?php
/**
 * add_student.php - Form to add a new student (+ optional profile photo)
 */
require_once 'config.php';

$errors = [];
$data = [
    'student_name' => '', 'roll_number' => '', 'course' => '',
    'department' => '', 'semester' => '', 'email' => '', 'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Security token expired. Please reload the page and try again.';
    } else {
        $data   = read_student_input();
        $errors = validate_student($conn, $data);

        // Validate the photo choice early so the user gets one combined error list
        $hasPhoto = isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE;

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare(
                    'INSERT INTO students (student_name, roll_number, course, department, semester, email, phone)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $sem = (int)$data['semester'];
                $stmt->bind_param('ssssiss', $data['student_name'], $data['roll_number'], $data['course'],
                    $data['department'], $sem, $data['email'], $data['phone']);
                $stmt->execute();
                $newId = $conn->insert_id;
                $stmt->close();

                set_flash('success', 'Student "' . $data['student_name'] . '" was added successfully.');

                if ($hasPhoto) {
                    [$ok, $msg] = handle_upload($conn, $newId, 'profile_photo', $_FILES['profile_photo']);
                    set_flash($ok ? 'success' : 'warning',
                        $ok ? $msg : 'Student saved, but the photo was not uploaded: ' . $msg);
                }
                redirect('view_student.php?id=' . $newId);
            } catch (mysqli_sql_exception $ex) {
                $errors[] = ($ex->getCode() === 1062)
                    ? 'Roll number "' . $data['roll_number'] . '" already exists.'
                    : 'Database error. The student could not be saved.';
            }
        }
    }
}

render_header('Add Student', 'add');
?>

<div class="page-head">
    <div>
        <h1>Add Student</h1>
        <p class="muted">Fill in the details. Fields marked * are required.</p>
    </div>
    <a class="btn btn-light" href="index.php">Back to list</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <strong>Please fix the following:</strong>
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="add_student.php" enctype="multipart/form-data" class="card form" novalidate>
    <?= csrf_field() ?>
    <div class="grid-2">
        <div class="field">
            <label>Student ID</label>
            <input type="text" value="Auto-generated" disabled>
        </div>
        <div class="field">
            <label for="student_name">Student Name *</label>
            <input type="text" id="student_name" name="student_name" value="<?= e($data['student_name']) ?>" maxlength="100" required>
        </div>
        <div class="field">
            <label for="roll_number">Roll Number *</label>
            <input type="text" id="roll_number" name="roll_number" value="<?= e($data['roll_number']) ?>" maxlength="30" placeholder="e.g. CS2024001" required>
        </div>
        <div class="field">
            <label for="course">Course *</label>
            <input type="text" id="course" name="course" value="<?= e($data['course']) ?>" maxlength="100" placeholder="e.g. B.Tech" required>
        </div>
        <div class="field">
            <label for="department">Department *</label>
            <input type="text" id="department" name="department" value="<?= e($data['department']) ?>" maxlength="100" placeholder="e.g. Computer Science" required>
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
            <input type="text" id="phone" name="phone" value="<?= e($data['phone']) ?>" maxlength="16" placeholder="10 to 15 digits" required>
        </div>
    </div>

    <div class="field">
        <label for="profile_photo">Profile Photo (optional)</label>
        <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
        <small class="muted">JPG, JPEG or PNG, up to <?= e(format_size(MAX_FILE_SIZE)) ?>. You can add ID proof and certificates after saving.</small>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Student</button>
        <a class="btn btn-light" href="index.php">Cancel</a>
    </div>
</form>

<?php render_footer();
