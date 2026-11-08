<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

$teacherId = (int) current_user()['id'];
$counts = [
    'total' => scalar('SELECT COUNT(*) FROM assignments WHERE teacher_id = ?', [$teacherId]),
    'published' => scalar("SELECT COUNT(*) FROM assignments WHERE teacher_id = ? AND status = 'published'", [$teacherId]),
    'pending' => scalar(
        "SELECT COUNT(*) FROM submissions s JOIN assignments a ON a.id = s.assignment_id
         WHERE a.teacher_id = ? AND s.status IN ('submitted', 'late')",
        [$teacherId]
    ),
    'graded' => scalar(
        "SELECT COUNT(*) FROM submissions s JOIN assignments a ON a.id = s.assignment_id
         WHERE a.teacher_id = ? AND s.status = 'graded'",
        [$teacherId]
    ),
];
$pendingRows = fetch_all(
    "SELECT s.id, s.submitted_at, s.status, u.name AS student_name, a.title, a.id AS assignment_id
     FROM submissions s
     JOIN users u ON u.id = s.student_id
     JOIN assignments a ON a.id = s.assignment_id
     WHERE a.teacher_id = ? AND s.status IN ('submitted', 'late')
     ORDER BY s.submitted_at ASC
     LIMIT 6",
    [$teacherId]
);
$recentAssignments = fetch_all(
    'SELECT id, title, deadline, status FROM assignments WHERE teacher_id = ? ORDER BY updated_at DESC LIMIT 5',
    [$teacherId]
);

$pageTitle = 'Teacher dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
page_heading('Teacher dashboard', 'Your assignments, open submissions, and work that still needs a grade.', '<a class="btn btn-primary" href="' . e(base_url('teacher/create_assignment.php')) . '">Create assignment</a>');
render_announcement();
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><?= stat_card('Total assignments', $counts['total'], 'journal-text', 'navy') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_card('Published assignments', $counts['published'], 'megaphone', 'teal') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_card('Pending submissions', $counts['pending'], 'hourglass-split', 'gold') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_card('Graded submissions', $counts['graded'], 'check2-circle', 'rose') ?></div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <section class="card-panel">
            <h2 class="h5">Waiting for a grade</h2>
            <?php if ($pendingRows === []): ?>
                <div class="empty-state"><i class="bi bi-check2-circle"></i>No submissions are waiting. New uploads will appear here.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Student</th><th>Assignment</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($pendingRows as $row): ?>
                            <tr>
                                <td><?= e((string) $row['student_name']) ?></td>
                                <td><?= e((string) $row['title']) ?></td>
                                <td><?= submission_badge((string) $row['status']) ?><div class="small text-secondary"><?= e(format_dt((string) $row['submitted_at'])) ?></div></td>
                                <td><a class="btn btn-sm btn-primary" href="<?= e(base_url('teacher/grade_submission.php?id=' . (int) $row['id'])) ?>">Grade</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="card-panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Recent assignments</h2>
                <a href="<?= e(base_url('teacher/assignments.php')) ?>">View all</a>
            </div>
            <?php if ($recentAssignments === []): ?>
                <p class="text-secondary mb-0">You have not created an assignment yet.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($recentAssignments as $assignment): ?>
                        <li class="mb-3">
                            <a href="<?= e(base_url('teacher/edit_assignment.php?id=' . (int) $assignment['id'])) ?>"><?= e((string) $assignment['title']) ?></a>
                            <div class="small"><?= assignment_badge((string) $assignment['status']) ?> <?= e(format_dt((string) $assignment['deadline'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
