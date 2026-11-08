<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('student');

$studentId = (int) current_user()['id'];
$total = scalar('SELECT COUNT(*) FROM submissions WHERE student_id = ?', [$studentId]);
$perPage = 8;
[$page, $pages, $offset] = pager($total, query_int('page', 1), $perPage);
$rows = fetch_all(
    'SELECT s.*, a.title, a.max_marks, a.deadline, a.status AS assignment_status, u.name AS teacher_name
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN users u ON u.id = a.teacher_id
     WHERE s.student_id = ?
     ORDER BY s.submitted_at DESC
     LIMIT ' . $perPage . ' OFFSET ' . $offset,
    [$studentId]
);

$pageTitle = 'My submissions';
$activeNav = 'submissions';
require __DIR__ . '/../includes/header.php';
page_heading('My submissions', 'Every file you have uploaded, including work from assignments that are now closed.');
?>
<section class="card-panel">
    <p class="text-secondary"><?= e(showing_range($page, $perPage, $total)) ?></p>
    <?php if ($rows === []): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            You have not submitted an assignment yet.
            <div class="mt-3"><a class="btn btn-primary" href="<?= e(base_url('student/assignments.php')) ?>">View assignments</a></div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Assignment</th><th>Submitted</th><th>Status</th><th>Marks</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['title']) ?><div class="small text-secondary"><?= e((string) $row['teacher_name']) ?></div></td>
                        <td><?= e(format_dt((string) $row['submitted_at'])) ?><?php if (strtotime((string) $row['submitted_at']) > strtotime((string) $row['deadline'])): ?><div class="small text-danger">After the deadline</div><?php endif; ?></td>
                        <td><?= submission_badge((string) $row['status']) ?></td>
                        <td><?= $row['status'] === 'graded' ? e(format_marks($row['marks'])) . ' / ' . e(format_marks($row['max_marks'])) : 'Pending' ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('student/feedback.php?id=' . (int) $row['id'])) ?>">Feedback</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('student/download_submission.php?id=' . (int) $row['id'])) ?>">Download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($page, $pages) ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
