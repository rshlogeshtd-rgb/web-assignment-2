<?php
/**
 * upload_file.php - Receives the upload form from view_student.php (POST only)
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$studentId = (int)($_POST['student_id'] ?? 0);
$student   = $studentId > 0 ? get_student($conn, $studentId) : null;

if (!$student) {
    set_flash('error', 'Student not found. File was not uploaded.');
    redirect('index.php');
}

$back = 'view_student.php?id=' . $studentId;

if (!csrf_valid()) {
    set_flash('error', 'Security token expired. Please try again.');
    redirect($back);
}

$category = $_POST['category'] ?? '';

// If the file is bigger than post_max_size, PHP empties $_FILES completely
if (empty($_FILES['file'])) {
    set_flash('error', 'No file received. The file may be larger than the server limit.');
    redirect($back);
}

[$ok, $message] = handle_upload($conn, $studentId, $category, $_FILES['file']);
set_flash($ok ? 'success' : 'error', $message);
redirect($back);
