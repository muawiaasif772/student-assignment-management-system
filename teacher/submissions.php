<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

$teacherId = (int) current_user()['id'];
$assignment = teacher_assignment(query_int('assignment_id'), $teacherId);
if ($assignment === null) {
    render_error('Assignment not found', 'The assignment you requested could not be found.', 404);
}

$assignmentId = (int) $assignment['id'];
$total = scalar('SELECT COUNT(*) FROM submissions WHERE assignment_id = ?', [$assignmentId]);
$perPage = 10;
[$page, $pages, $offset] = pager($total, query_int('page', 1), $perPage);
$rows = fetch_all(
    'SELECT s.*, u.name AS student_name, u.email AS student_email
     FROM submissions s
     JOIN users u ON u.id = s.student_id
     WHERE s.assignment_id = ?
     ORDER BY s.submitted_at DESC
     LIMIT ' . $perPage . ' OFFSET ' . $offset,
    [$assignmentId]
);

$pageTitle = 'Submissions';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
page_heading((string) $assignment['title'], 'Maximum marks ' . format_marks($assignment['max_marks']) . ' · Deadline ' . format_dt((string) $assignment['deadline']), '<a class="btn btn-outline-secondary" href="' . e(base_url('teacher/assignments.php')) . '">Back to assignments</a>');
?>
<section class="card-panel mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?= assignment_badge((string) $assignment['status']) ?>
        <?= deadline_badge((string) $assignment['deadline']) ?>
        <span class="text-secondary"><?= e((string) $total) ?> submission<?= $total === 1 ? '' : 's' ?></span>
    </div>
    <p class="prose mt-3 mb-0"><?= e_nl((string) $assignment['description']) ?></p>
</section>
<section class="card-panel">
    <?php if ($rows === []): ?>
        <div class="empty-state"><i class="bi bi-inbox"></i>No students have submitted this assignment yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Student</th><th>File</th><th>Submitted</th><th>Status</th><th>Marks</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['student_name']) ?><div class="small text-secondary break-all"><?= e((string) $row['student_email']) ?></div></td>
                        <td><?= e((string) $row['file_name']) ?></td>
                        <td><?= e(format_dt((string) $row['submitted_at'])) ?><?php if (strtotime((string) $row['submitted_at']) > strtotime((string) $assignment['deadline'])): ?><div class="small text-danger">After the deadline</div><?php endif; ?></td>
                        <td><?= submission_badge((string) $row['status']) ?></td>
                        <td><?= $row['status'] === 'graded' ? e(format_marks($row['marks'])) . ' / ' . e(format_marks($assignment['max_marks'])) : 'Pending' ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('teacher/download_submission.php?id=' . (int) $row['id'])) ?>">Download</a>
                            <a class="btn btn-sm btn-primary" href="<?= e(base_url('teacher/grade_submission.php?id=' . (int) $row['id'])) ?>"><?= $row['status'] === 'graded' ? 'Update grade' : 'Grade' ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($page, $pages, ['assignment_id' => $assignmentId]) ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
