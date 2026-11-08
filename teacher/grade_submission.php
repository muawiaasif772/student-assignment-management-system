<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

$teacherId = (int) current_user()['id'];
$submission = fetch_one(
    'SELECT s.*, a.title, a.max_marks, a.teacher_id, a.deadline, u.name AS student_name, u.email AS student_email
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN users u ON u.id = s.student_id
     WHERE s.id = ? AND a.teacher_id = ?',
    [query_int('id'), $teacherId]
);
if ($submission === null) {
    render_error('Submission not found', 'The submission you requested could not be found.', 404);
}

$errors = [];
$marksValue = $submission['marks'] === null ? '' : format_marks($submission['marks']);
$feedbackValue = (string) ($submission['feedback'] ?? '');

if (is_post()) {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    }
    $checked = validate_grade(post('marks'), post('feedback'), (float) $submission['max_marks']);
    $errors = array_merge($errors, $checked['errors']);
    $marksValue = post('marks');
    $feedbackValue = $checked['feedback'];
    if ($errors === []) {
        db()->prepare('UPDATE submissions SET marks = ?, feedback = ?, status = ? WHERE id = ?')->execute([
            number_format((float) $checked['marks'], 2, '.', ''),
            $checked['feedback'],
            'graded',
            (int) $submission['id'],
        ]);
        flash('success', 'Marks and feedback were saved.');
        redirect('teacher/submissions.php?assignment_id=' . (int) $submission['assignment_id']);
    }
}

$pageTitle = 'Grade submission';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
page_heading('Grade submission', (string) $submission['title'], '<a class="btn btn-outline-secondary" href="' . e(base_url('teacher/submissions.php?assignment_id=' . (int) $submission['assignment_id'])) . '">Back to submissions</a>');
?>
<div class="row g-3">
    <div class="col-lg-5">
        <section class="card-panel">
            <h2 class="h5">Submission details</h2>
            <dl class="meta-list">
                <div><dt>Student</dt><dd><?= e((string) $submission['student_name']) ?></dd></div>
                <div><dt>Email</dt><dd class="break-all"><?= e((string) $submission['student_email']) ?></dd></div>
                <div><dt>Assignment</dt><dd><?= e((string) $submission['title']) ?></dd></div>
                <div><dt>Submitted</dt><dd><?= e(format_dt((string) $submission['submitted_at'])) ?></dd></div>
                <div><dt>Deadline</dt><dd><?= e(format_dt((string) $submission['deadline'])) ?></dd></div>
                <div><dt>File</dt><dd><?= e((string) $submission['file_name']) ?></dd></div>
                <div><dt>Status</dt><dd><?= submission_badge((string) $submission['status']) ?></dd></div>
            </dl>
            <?php if (strtotime((string) $submission['submitted_at']) > strtotime((string) $submission['deadline'])): ?>
                <div class="alert alert-warning mt-3 mb-0">This file was submitted after the deadline.</div>
            <?php endif; ?>
            <a class="btn btn-outline-primary mt-3" href="<?= e(base_url('teacher/download_submission.php?id=' . (int) $submission['id'])) ?>">Download file</a>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="card-panel">
            <?php render_form_alert($errors); ?>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="marks">Marks out of <?= e(format_marks($submission['max_marks'])) ?></label>
                    <input class="form-control<?= invalid($errors, 'marks') ?>" type="number" id="marks" name="marks" min="0" max="<?= e(format_marks($submission['max_marks'])) ?>" step="0.01" value="<?= e($marksValue) ?>" required>
                    <?= field_error($errors, 'marks') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="feedback">Feedback</label>
                    <textarea class="form-control<?= invalid($errors, 'feedback') ?>" id="feedback" name="feedback" rows="7" maxlength="5000" required><?= e($feedbackValue) ?></textarea>
                    <?= field_error($errors, 'feedback') ?>
                </div>
                <button class="btn btn-primary" type="submit">Save grade</button>
            </form>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
