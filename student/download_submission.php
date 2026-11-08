<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('student');

$studentId = (int) current_user()['id'];
$row = fetch_one(
    'SELECT file_path, file_name FROM submissions WHERE id = ? AND student_id = ?',
    [query_int('id'), $studentId]
);
if ($row === null) {
    render_error('Submission not found', 'The submission you requested could not be found.', 404);
}

stream_download((string) $row['file_path'], (string) $row['file_name']);
