<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('student');

$studentId = (int) current_user()['id'];
$submission = fetch_one(
    'SELECT s.*, a.title, a.max_marks, a.deadline, a.status AS assignment_status, u.name AS teacher_name
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN users u ON u.id = a.teacher_id
     WHERE s.id = ? AND s.student_id = ?',
    [query_int('id'), $studentId]
);
if ($submission === null) {
    render_error('Submission not found', 'The submission you requested could not be found.', 404);
}

$pageTitle = 'Feedback';
$activeNav = 'submissions';
require __DIR__ . '/../includes/header.php';
page_heading('Marks and feedback', (string) $submission['title'], '<a class="btn btn-outline-secondary" href="' . e(base_url('student/submissions.php')) . '">My submissions</a>');
?>
<div class="row g-3">
    <div class="col-lg-5">
        <section class="card-panel">
            <dl class="meta-list">
                <div><dt>Teacher</dt><dd><?= e((string) $submission['teacher_name']) ?></dd></div>
                <div><dt>Submitted</dt><dd><?= e(format_dt((string) $submission['submitted_at'])) ?></dd></div>
                <div><dt>Deadline</dt><dd><?= e(format_dt((string) $submission['deadline'])) ?></dd></div>
                <div><dt>File</dt><dd><?= e((string) $submission['file_name']) ?></dd></div>
                <div><dt>Status</dt><dd><?= submission_badge((string) $submission['status']) ?></dd></div>
                <div><dt>Marks</dt><dd><?= $submission['status'] === 'graded' ? e(format_marks($submission['marks'])) . ' / ' . e(format_marks($submission['max_marks'])) : 'Pending' ?></dd></div>
            </dl>
            <?php if (strtotime((string) $submission['submitted_at']) > strtotime((string) $submission['deadline'])): ?>
                <div class="alert alert-warning mt-3 mb-0">You submitted this file after the deadline.</div>
            <?php endif; ?>
            <a class="btn btn-outline-primary mt-3" href="<?= e(base_url('student/download_submission.php?id=' . (int) $submission['id'])) ?>">Download your file</a>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="card-panel">
            <h2 class="h5">Teacher feedback</h2>
            <?php if ($submission['status'] !== 'graded' || trim((string) $submission['feedback']) === ''): ?>
                <p class="mb-0">Your teacher has not provided feedback yet.</p>
            <?php else: ?>
                <div class="prose"><?= e_nl((string) $submission['feedback']) ?></div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
