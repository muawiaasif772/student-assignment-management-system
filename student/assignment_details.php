<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('student');

$studentId = (int) current_user()['id'];
$assignment = find_assignment(query_int('id'));
if ($assignment === null || $assignment['status'] === 'draft') {
    render_error('Assignment not found', 'The assignment you requested could not be found.', 404);
}

$submission = fetch_one(
    'SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?',
    [(int) $assignment['id'], $studentId]
);
[$canSubmit, $submitNote] = can_student_submit($assignment, $submission);

$pageTitle = (string) $assignment['title'];
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
page_heading((string) $assignment['title'], 'Teacher: ' . $assignment['teacher_name'], '<a class="btn btn-outline-secondary" href="' . e(base_url('student/assignments.php')) . '">All assignments</a>');
?>
<div class="row g-3">
    <div class="col-lg-8">
        <section class="card-panel">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?= assignment_badge((string) $assignment['status']) ?>
                <?= deadline_badge((string) $assignment['deadline']) ?>
            </div>
            <?php if ($assignment['status'] === 'closed'): ?>
                <div class="alert alert-secondary">This assignment is closed and is no longer available for submission.</div>
            <?php endif; ?>
            <h2 class="h5">Instructions</h2>
            <div class="prose"><?= e_nl((string) $assignment['description']) ?></div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card-panel">
            <dl class="meta-list">
                <div><dt>Deadline</dt><dd><?= e(format_dt((string) $assignment['deadline'])) ?></dd></div>
                <div><dt>Maximum marks</dt><dd><?= e(format_marks($assignment['max_marks'])) ?></dd></div>
                <div><dt>Your status</dt><dd><?= $submission === null ? 'Not submitted' : submission_badge((string) $submission['status']) ?></dd></div>
                <?php if ($submission !== null && $submission['status'] === 'graded'): ?>
                    <div><dt>Marks</dt><dd><?= e(format_marks($submission['marks'])) ?> / <?= e(format_marks($assignment['max_marks'])) ?></dd></div>
                <?php endif; ?>
            </dl>
            <div class="d-grid gap-2 mt-3">
                <?php if ($assignment['status'] === 'published' && $canSubmit): ?>
                    <a class="btn btn-primary" href="<?= e(base_url('student/submit_assignment.php?id=' . (int) $assignment['id'])) ?>"><?= $submission === null ? 'Submit file' : 'Replace file' ?></a>
                <?php elseif ($submitNote !== '' && $assignment['status'] === 'published'): ?>
                    <div class="alert alert-warning mb-0"><?= e($submitNote) ?></div>
                <?php endif; ?>
                <?php if ($submission !== null): ?>
                    <a class="btn btn-outline-primary" href="<?= e(base_url('student/feedback.php?id=' . (int) $submission['id'])) ?>">View submission</a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
