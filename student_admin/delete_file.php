<?php
/**
 * delete_file.php - Deletes one uploaded file (POST only)
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
if (!csrf_valid()) {
    set_flash('error', 'Security token expired. Please try again.');
    redirect('index.php');
}

$fileId = (int)($_POST['file_id'] ?? 0);

$stmt = $conn->prepare('SELECT * FROM student_files WHERE file_id = ?');
$stmt->bind_param('i', $fileId);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$file) {
    set_flash('error', 'File not found.');
    redirect('index.php');
}

try {
    delete_file_record($conn, $file);
    set_flash('success', '"' . $file['original_name'] . '" was deleted.');
} catch (mysqli_sql_exception $ex) {
    set_flash('error', 'Database error. The file could not be deleted.');
}

redirect('view_student.php?id=' . (int)$file['student_id']);
