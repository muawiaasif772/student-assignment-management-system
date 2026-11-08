<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

$teacherId = (int) current_user()['id'];
$row = fetch_one(
    'SELECT s.file_path, s.file_name
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     WHERE s.id = ? AND a.teacher_id = ?',
    [query_int('id'), $teacherId]
);
if ($row === null) {
    render_error('Submission not found', 'The submission you requested could not be found.', 404);
}

stream_download((string) $row['file_path'], (string) $row['file_name']);
