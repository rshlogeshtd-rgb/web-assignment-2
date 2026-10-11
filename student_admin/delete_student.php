<?php
/**
 * delete_student.php - Deletes a student and all of their files (POST only)
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
if (!csrf_valid()) {
    set_flash('error', 'Security token expired. Please try again.');
    redirect('index.php');
}

$id = (int)($_POST['student_id'] ?? 0);
$student = $id > 0 ? get_student($conn, $id) : null;

if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('index.php');
}

try {
    // 1. Delete the physical files from the uploads folders
    $stmt = $conn->prepare('SELECT * FROM student_files WHERE student_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($files as $f) {
        delete_file_record($conn, $f);
    }

    // 2. Delete the student (any remaining file rows are removed by ON DELETE CASCADE)
    $stmt = $conn->prepare('DELETE FROM students WHERE student_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    set_flash('success', 'Student "' . $student['student_name'] . '" and ' . count($files) . ' file(s) were deleted.');
} catch (mysqli_sql_exception $ex) {
    set_flash('error', 'Database error. The student could not be deleted.');
}

redirect('index.php');
