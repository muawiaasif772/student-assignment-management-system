<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('student');

$studentId = (int) current_user()['id'];
$allowLate = get_setting('allow_late_submissions', '0') === '1' ? 1 : 0;
$available = scalar(
    "SELECT COUNT(*) FROM assignments WHERE status = 'published' AND (deadline >= NOW() OR ? = 1)",
    [$allowLate]
);
$submitted = scalar('SELECT COUNT(*) FROM submissions WHERE student_id = ?', [$studentId]);
$pending = scalar(
    "SELECT COUNT(*) FROM assignments a
     WHERE a.status = 'published'
       AND (a.deadline >= NOW() OR ? = 1)
       AND NOT EXISTS (SELECT 1 FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = ?)",
    [$allowLate, $studentId]
);
$graded = scalar("SELECT COUNT(*) FROM submissions WHERE student_id = ? AND status = 'graded'", [$studentId]);
$upcoming = fetch_all(
    "SELECT a.id, a.title, a.deadline, a.max_marks, u.name AS teacher_name
     FROM assignments a
     JOIN users u ON u.id = a.teacher_id
     WHERE a.status = 'published' AND a.deadline >= NOW()
     ORDER BY a.deadline ASC
     LIMIT 5"
);
$feedback = fetch_all(
    "SELECT s.id, s.marks, a.title, a.max_marks
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     WHERE s.student_id = ? AND s.status = 'graded'
     ORDER BY s.updated_at DESC
     LIMIT 4",
    [$studentId]
);

$pageTitle = 'Student dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
page_heading('Student dashboard', 'Open assignments, your uploads, and feedback from teachers.', '<a class="btn btn-primary" href="' . e(base_url('student/assignments.php')) . '">View assignments</a>');
render_announcement();
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><?= stat_card('Available assignments', $available, 'journal-text', 'teal') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_card('Submitted assignments', $submitted, 'upload', 'navy') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_card('Pending assignments', $pending, 'hourglass-split', 'gold') ?></div>
    <div class="col-sm-6 col-xl-3"><?= stat_card('Graded assignments', $graded, 'award', 'rose') ?></div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <section class="card-panel">
            <h2 class="h5">Coming up</h2>
            <?php if ($upcoming === []): ?>
                <div class="empty-state"><i class="bi bi-calendar-check"></i>No published assignments have a future deadline.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Assignment</th><th>Teacher</th><th>Deadline</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($upcoming as $assignment): ?>
                            <tr>
                                <td><?= e((string) $assignment['title']) ?><div class="small text-secondary"><?= e(format_marks($assignment['max_marks'])) ?> marks</div></td>
                                <td><?= e((string) $assignment['teacher_name']) ?></td>
                                <td><?= e(format_dt((string) $assignment['deadline'])) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('student/assignment_details.php?id=' . (int) $assignment['id'])) ?>">Open</a></td>
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
                <h2 class="h5 mb-0">Recent feedback</h2>
                <a href="<?= e(base_url('student/submissions.php')) ?>">My submissions</a>
            </div>
            <?php if ($feedback === []): ?>
                <p class="text-secondary mb-0">Graded work will appear here.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($feedback as $item): ?>
                        <li class="mb-3">
                            <a href="<?= e(base_url('student/feedback.php?id=' . (int) $item['id'])) ?>"><?= e((string) $item['title']) ?></a>
                            <div class="small"><?= e(format_marks($item['marks'])) ?> / <?= e(format_marks($item['max_marks'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
