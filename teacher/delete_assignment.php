<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

if (!is_post() || !csrf_ok()) {
    flash('danger', 'Invalid request. Please try again.');
    redirect('teacher/assignments.php');
}

$teacherId = (int) current_user()['id'];
$id = (int) post('assignment_id');
$assignment = teacher_assignment($id, $teacherId);
if ($assignment === null) {
    flash('danger', 'The assignment could not be found.');
    redirect('teacher/assignments.php');
}

$files = fetch_all('SELECT file_path FROM submissions WHERE assignment_id = ?', [$id]);
db()->prepare('DELETE FROM assignments WHERE id = ? AND teacher_id = ?')->execute([$id, $teacherId]);
foreach ($files as $file) {
    delete_stored_file((string) $file['file_path']);
}

flash('success', 'The assignment and its submissions were deleted.');
redirect('teacher/assignments.php');
