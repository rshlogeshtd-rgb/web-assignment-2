<?php
/**
 * config.php
 * Database connection + helper functions shared by ALL pages.
 * Every page starts with:  require_once 'config.php';
 */

session_start();
date_default_timezone_set('Asia/Kolkata');

/* ---------- 1. Database settings (XAMPP defaults) ---------- */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');               // XAMPP root has no password by default
define('DB_NAME', 'college_admin');

/* ---------- 2. Upload settings ---------- */
define('MAX_FILE_SIZE', 5 * 1024 * 1024);              // 5 MB
define('PROFILE_DIR', __DIR__ . '/uploads/profiles/');  // where files are saved
define('DOCUMENT_DIR', __DIR__ . '/uploads/documents/');
define('PROFILE_PATH', 'uploads/profiles/');            // path stored in MySQL / used in <img>
define('DOCUMENT_PATH', 'uploads/documents/');

/* ---------- 3. Connect using mysqli (used everywhere) ---------- */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);   // errors become exceptions
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('<h3 style="font-family:Arial;color:#b91c1c">Database connection failed.</h3>
         <p style="font-family:Arial">Start MySQL in XAMPP and import <b>database.sql</b> in phpMyAdmin.</p>');
}

/* ============================================================
   GENERAL HELPERS
   ============================================================ */

/** Safely print text in HTML (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Redirect to another page and stop the script. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Store a one-time message (success / error / warning) in the session. */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Print and clear all stored messages. */
function show_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/** Convert bytes to KB / MB text. */
function format_size($bytes): string
{
    $bytes = (int)$bytes;
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' bytes';
}

/** Date like 12 Mar 2025, 04:30 PM */
function format_date($date): string
{
    return date('d M Y, h:i A', strtotime($date));
}

/* ============================================================
   CSRF PROTECTION (stops fake form submissions)
   ============================================================ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/* ============================================================
   STUDENT HELPERS
   ============================================================ */

/** Available file categories (database value => label). */
function file_categories(): array
{
    return [
        'profile_photo' => 'Profile Photo',
        'id_proof'      => 'ID Proof',
        'certificate'   => 'Certificate',
        'other'         => 'Other Document',
    ];
}

/** Read and trim student form fields from $_POST. */
function read_student_input(): array
{
    $keys = ['student_name', 'roll_number', 'course', 'department', 'semester', 'email', 'phone'];
    $data = [];
    foreach ($keys as $k) {
        $data[$k] = trim($_POST[$k] ?? '');
    }
    return $data;
}

/**
 * Validate student data. Returns an array of error messages (empty = OK).
 * $excludeId is used on the edit page so a student is not "duplicate" of itself.
 */
function validate_student(mysqli $conn, array $d, int $excludeId = 0): array
{
    $errors = [];

    // Required fields
    $labels = [
        'student_name' => 'Student name', 'roll_number' => 'Roll number',
        'course' => 'Course', 'department' => 'Department',
        'semester' => 'Semester', 'email' => 'Email', 'phone' => 'Phone number',
    ];
    foreach ($labels as $key => $label) {
        if ($d[$key] === '') {
            $errors[] = $label . ' is required.';
        }
    }

    // Name: letters, spaces, dot, apostrophe, hyphen
    if ($d['student_name'] !== '' && !preg_match("/^[A-Za-z][A-Za-z .'-]{1,99}$/", $d['student_name'])) {
        $errors[] = 'Student name may contain only letters, spaces and . \' - (2 to 100 characters).';
    }

    // Roll number format
    if ($d['roll_number'] !== '' && !preg_match('/^[A-Za-z0-9\/-]{1,30}$/', $d['roll_number'])) {
        $errors[] = 'Roll number may contain only letters, digits, - and / (max 30 characters).';
    }

    // Semester 1 - 8
    if ($d['semester'] !== '' && (!ctype_digit($d['semester']) || (int)$d['semester'] < 1 || (int)$d['semester'] > 8)) {
        $errors[] = 'Semester must be a number from 1 to 8.';
    }

    // Email
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Phone: 10-15 digits, optional + at the start
    if ($d['phone'] !== '' && !preg_match('/^\+?[0-9]{10,15}$/', $d['phone'])) {
        $errors[] = 'Phone number must contain 10 to 15 digits (optional + at the start).';
    }

    // Length checks
    if (strlen($d['course']) > 100 || strlen($d['department']) > 100) {
        $errors[] = 'Course and Department must be 100 characters or less.';
    }
    if (strlen($d['email']) > 150) {
        $errors[] = 'Email must be 150 characters or less.';
    }

    // Duplicate roll number
    if ($d['roll_number'] !== '' && empty($errors)) {
        $stmt = $conn->prepare('SELECT student_id FROM students WHERE roll_number = ? AND student_id <> ?');
        $stmt->bind_param('si', $d['roll_number'], $excludeId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Roll number "' . $d['roll_number'] . '" already exists. Roll numbers must be unique.';
        }
        $stmt->close();
    }

    return $errors;
}

/** Get one student row (or null). */
function get_student(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM students WHERE student_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Latest profile photo row of a student (or null). */
function get_profile_photo(mysqli $conn, int $studentId): ?array
{
    $stmt = $conn->prepare(
        "SELECT * FROM student_files
         WHERE student_id = ? AND file_category = 'profile_photo'
         ORDER BY file_id DESC LIMIT 1"
    );
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/* ============================================================
   FILE UPLOAD HELPERS
   ============================================================ */

/** Delete a file from disk (only inside the uploads folder) and from MySQL. */
function delete_file_record(mysqli $conn, array $file): void
{
    $full = realpath(__DIR__ . '/' . ltrim($file['file_path'], '/'));
    $base = realpath(__DIR__ . '/uploads');
    if ($full !== false && $base !== false && strpos($full, $base) === 0 && is_file($full)) {
        unlink($full);
    }
    $stmt = $conn->prepare('DELETE FROM student_files WHERE file_id = ?');
    $stmt->bind_param('i', $file['file_id']);
    $stmt->execute();
    $stmt->close();
}

/**
 * Validate and save one uploaded file.
 * Returns [true|false, "message"].
 *
 * $file     = one entry of $_FILES (e.g. $_FILES['file'])
 * $category = profile_photo | id_proof | certificate | other
 */
function handle_upload(mysqli $conn, int $studentId, string $category, array $file): array
{
    if (!array_key_exists($category, file_categories())) {
        return [false, 'Invalid file category selected.'];
    }

    // ---- 1. PHP upload errors ----
    if (!isset($file['error']) || is_array($file['error'])) {
        return [false, 'Invalid upload request.'];
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return [false, 'Please choose a file to upload.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [false, 'File is too large. Maximum allowed size is ' . format_size(MAX_FILE_SIZE) . '.'];
        default:
            return [false, 'Upload failed (error code ' . (int)$file['error'] . '). Please try again.'];
    }

    // ---- 2. Size check ----
    if ($file['size'] <= 0) {
        return [false, 'The selected file is empty.'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return [false, 'File is too large. Maximum allowed size is ' . format_size(MAX_FILE_SIZE) . '.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [false, 'Invalid upload request.'];
    }

    // ---- 3. File name & extension check ----
    $original = basename($file['name']);
    $original = preg_replace('/[^\w\s.\-()]/u', '_', $original);   // remove strange characters
    $original = mb_substr($original, 0, 150);
    $parts    = explode('.', strtolower($original));
    $ext      = count($parts) > 1 ? end($parts) : '';

    // Block dangerous extensions anywhere in the name (e.g. shell.php.jpg)
    $blocked = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar', 'exe', 'js', 'bat', 'cmd',
                'sh', 'com', 'msi', 'dll', 'jar', 'vbs', 'cgi', 'pl', 'py', 'html', 'htm', 'htaccess'];
    foreach (array_slice($parts, 1) as $part) {
        if (in_array($part, $blocked, true)) {
            return [false, 'Executable or script files (like .' . $part . ') are not allowed.'];
        }
    }

    // Allowed extensions
    $allowedExt = ($category === 'profile_photo')
        ? ['jpg', 'jpeg', 'png']
        : ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

    if (!in_array($ext, $allowedExt, true)) {
        $msg = ($category === 'profile_photo')
            ? 'Profile photo must be a JPG, JPEG or PNG image.'
            : 'Only JPG, JPEG, PNG, PDF, DOC and DOCX files are allowed.';
        return [false, $msg];
    }

    // ---- 4. Real content (MIME) check - extension alone can be faked ----
    $mimeMap = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    if (!in_array($realMime, $mimeMap[$ext], true)) {
        return [false, 'File content does not match its extension (.' . $ext . '). Upload rejected.'];
    }
    if (in_array($ext, ['jpg', 'jpeg', 'png'], true) && @getimagesize($file['tmp_name']) === false) {
        return [false, 'The image file is corrupted or not a real image.'];
    }

    // ---- 5. Choose folder and create a UNIQUE stored name ----
    $isProfile = ($category === 'profile_photo');
    $dir       = $isProfile ? PROFILE_DIR : DOCUMENT_DIR;
    $webPath   = $isProfile ? PROFILE_PATH : DOCUMENT_PATH;

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return [false, 'Upload folder is missing and could not be created.'];
    }
    if (!is_writable($dir)) {
        return [false, 'Upload folder is not writable. Check folder permissions.'];
    }

    do {
        $stored = $category . '_' . $studentId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    } while (file_exists($dir . $stored));

    // ---- 6. Move file, then save metadata ----
    if (!move_uploaded_file($file['tmp_name'], $dir . $stored)) {
        return [false, 'Could not save the uploaded file. Please try again.'];
    }

    // Remember the old profile photo (a student has only one)
    $oldPhoto = $isProfile ? get_profile_photo($conn, $studentId) : null;

    $path = $webPath . $stored;
    $size = (int)$file['size'];
    try {
        $stmt = $conn->prepare(
            'INSERT INTO student_files (student_id, file_category, original_name, stored_name, file_path, file_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssssi', $studentId, $category, $original, $stored, $path, $realMime, $size);
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $ex) {
        unlink($dir . $stored);   // do not keep a file that has no database record
        return [false, 'Database error while saving file information.'];
    }

    if ($oldPhoto) {
        delete_file_record($conn, $oldPhoto);   // replace old profile photo
    }

    return [true, '"' . $original . '" uploaded successfully as ' . file_categories()[$category] . '.'];
}

/* ============================================================
   PAGE LAYOUT (header + footer used on every page)
   ============================================================ */
function render_header(string $title, string $active = ''): void
{
    $nav = [
        'home' => ['index.php', 'Home'],
        'add'  => ['add_student.php', 'Add Student'],
        'list' => ['index.php#student-list', 'Student List'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | College Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php">
            <span class="brand-mark">CA</span>
            <span>College Admin Panel<small>Student Records</small></span>
        </a>
        <nav class="nav">
            <?php foreach ($nav as $key => [$url, $label]): ?>
                <a href="<?= e($url) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<main class="container">
    <?php show_flash(); ?>
<?php
}

function render_footer(): void
{
    ?>
</main>
<footer class="footer">College Student Admin Panel &middot; PHP &amp; MySQL practical project</footer>
<script>
// Ask for confirmation before any form that has a data-confirm attribute is submitted
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.getAttribute('data-confirm'))) {
            event.preventDefault();
        }
    });
});
</script>
</body>
</html>
<?php
}
