<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

$teacherId = (int) current_user()['id'];
$assignment = teacher_assignment(query_int('id'), $teacherId);
if ($assignment === null) {
    render_error('Assignment not found', 'The assignment you requested could not be found.', 404);
}

$id = (int) $assignment['id'];
$errors = [];
$values = [
    'title' => (string) $assignment['title'],
    'description' => (string) $assignment['description'],
    'deadline' => deadline_input((string) $assignment['deadline']),
    'max_marks' => format_marks($assignment['max_marks']),
];

if (is_post()) {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    }
    $checked = validate_assignment_input($_POST, false);
    $errors = array_merge($errors, $checked['errors']);
    $values['title'] = $checked['data']['title'];
    $values['description'] = $checked['data']['description'];
    $values['deadline'] = post('deadline');
    $values['max_marks'] = post('max_marks');
    if ($checked['data']['max_marks'] !== null) {
        $highest = (float) scalar(
            'SELECT COALESCE(MAX(marks), 0) FROM submissions WHERE assignment_id = ? AND marks IS NOT NULL',
            [$id]
        );
        if ((int) round((float) $checked['data']['max_marks'] * 100) < (int) round($highest * 100)) {
            $errors['max_marks'] = 'Maximum marks cannot be lower than marks already awarded (' . format_marks($highest) . ').';
        }
    }
    if ($errors === []) {
        db()->prepare(
            'UPDATE assignments SET title = ?, description = ?, deadline = ?, max_marks = ? WHERE id = ? AND teacher_id = ?'
        )->execute([
            $checked['data']['title'],
            $checked['data']['description'],
            $checked['data']['deadline'],
            number_format((float) $checked['data']['max_marks'], 2, '.', ''),
            $id,
            $teacherId,
        ]);
        $note = deadline_passed((string) $checked['data']['deadline'])
            ? ' The deadline is in the past, so new submissions stay closed unless late submission is enabled.'
            : '';
        flash('success', 'The assignment was updated.' . $note);
        redirect('teacher/assignments.php');
    }
}

$pageTitle = 'Edit assignment';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
page_heading('Edit assignment', 'Status: ' . ucfirst((string) $assignment['status']) . '. Closing and publishing are done from the assignment list.', '<a class="btn btn-outline-secondary" href="' . e(base_url('teacher/assignments.php')) . '">Back to assignments</a>');
?>
<section class="card-panel narrow-panel">
    <?php if ($assignment['status'] === 'closed'): ?>
        <div class="alert alert-secondary">This assignment is closed. Students cannot submit. You can still correct the text and grade existing work.</div>
    <?php endif; ?>
    <?php render_form_alert($errors); ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="title">Title</label>
            <input class="form-control<?= invalid($errors, 'title') ?>" id="title" name="title" value="<?= e($values['title']) ?>" maxlength="200" required>
            <?= field_error($errors, 'title') ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Instructions</label>
            <textarea class="form-control<?= invalid($errors, 'description') ?>" id="description" name="description" rows="6" maxlength="10000" required><?= e($values['description']) ?></textarea>
            <?= field_error($errors, 'description') ?>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="deadline">Deadline</label>
                <input class="form-control<?= invalid($errors, 'deadline') ?>" type="datetime-local" id="deadline" name="deadline" value="<?= e($values['deadline']) ?>" required>
                <?= field_error($errors, 'deadline') ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="max_marks">Maximum marks</label>
                <input class="form-control<?= invalid($errors, 'max_marks') ?>" type="number" id="max_marks" name="max_marks" min="1" max="1000" step="0.01" value="<?= e($values['max_marks']) ?>" required>
                <?= field_error($errors, 'max_marks') ?>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Save changes</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
