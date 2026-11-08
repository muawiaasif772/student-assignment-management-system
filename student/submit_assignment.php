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
$errors = [];

if (is_post()) {
    if ($assignment['status'] !== 'published') {
        $errors['form'] = 'This assignment is not open for submissions.';
    } elseif (post_too_large()) {
        $errors['file'] = 'The file is too large. Maximum allowed size is 10 MB.';
    } elseif (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    } elseif (!isset($_FILES['submission_file']) || !is_array($_FILES['submission_file'])) {
        $errors['file'] = 'Please choose a file to upload.';
    } else {
        $result = submit_assignment_file((int) $assignment['id'], $studentId, $_FILES['submission_file']);
        if (!$result['ok']) {
            $errors['file'] = (string) $result['error'];
        } else {
            flash('success', !empty($result['late'])
                ? 'Your file was submitted after the deadline and marked as late.'
                : 'Your assignment file was submitted.');
            redirect('student/submissions.php');
        }
    }
}

$pageTitle = 'Submit assignment';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
page_heading('Submit assignment', (string) $assignment['title'], '<a class="btn btn-outline-secondary" href="' . e(base_url('student/assignment_details.php?id=' . (int) $assignment['id'])) . '">Back to details</a>');
?>
<section class="card-panel narrow-panel">
    <dl class="meta-list mb-3">
        <div><dt>Deadline</dt><dd><?= e(format_dt((string) $assignment['deadline'])) ?></dd></div>
        <div><dt>Maximum marks</dt><dd><?= e(format_marks($assignment['max_marks'])) ?></dd></div>
        <div><dt>Allowed files</dt><dd>PDF, DOC, DOCX, ZIP up to 10 MB</dd></div>
    </dl>
    <?php if ($assignment['status'] === 'closed'): ?>
        <div class="alert alert-secondary mb-0">This assignment is closed and is no longer available for submission.</div>
    <?php elseif (!$canSubmit): ?>
        <div class="alert alert-warning mb-0"><?= e($submitNote) ?></div>
        <?php if ($submission !== null): ?>
            <a class="btn btn-outline-primary mt-3" href="<?= e(base_url('student/feedback.php?id=' . (int) $submission['id'])) ?>">View your submission</a>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($submitNote !== ''): ?><div class="alert alert-info"><?= e($submitNote) ?></div><?php endif; ?>
        <?php if ($submission !== null): ?>
            <p>Current file: <strong><?= e((string) $submission['file_name']) ?></strong></p>
        <?php endif; ?>
        <?php render_form_alert($errors); ?>
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) MAX_UPLOAD_BYTES ?>">
            <div class="mb-3">
                <label class="form-label" for="submission_file">Assignment file</label>
                <input class="form-control js-file<?= invalid($errors, 'file') ?>" type="file" id="submission_file" name="submission_file" data-note="file-note" accept=".pdf,.doc,.docx,.zip,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip" required>
                <div class="form-text" id="file-note">Choose one PDF, DOC, DOCX, or ZIP file.</div>
                <?= field_error($errors, 'file') ?>
            </div>
            <button class="btn btn-primary" type="submit"><?= $submission === null ? 'Submit assignment' : 'Replace submission' ?></button>
        </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
