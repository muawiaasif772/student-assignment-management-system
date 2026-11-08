<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

$teacherId = (int) current_user()['id'];
$errors = [];
$values = ['title' => '', 'description' => '', 'deadline' => '', 'max_marks' => ''];

if (is_post()) {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    }
    $checked = validate_assignment_input($_POST, true);
    $errors = array_merge($errors, $checked['errors']);
    $values['title'] = $checked['data']['title'];
    $values['description'] = $checked['data']['description'];
    $values['deadline'] = post('deadline');
    $values['max_marks'] = post('max_marks');
    $status = post('save_action') === 'publish' ? 'published' : 'draft';

    if ($errors === []) {
        $statement = db()->prepare(
            'INSERT INTO assignments (teacher_id, title, description, deadline, max_marks, status) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $teacherId,
            $checked['data']['title'],
            $checked['data']['description'],
            $checked['data']['deadline'],
            number_format((float) $checked['data']['max_marks'], 2, '.', ''),
            $status,
        ]);
        flash('success', $status === 'published' ? 'The assignment was created and published.' : 'The assignment was saved as a draft.');
        redirect('teacher/assignments.php');
    }
}

$pageTitle = 'Create assignment';
$activeNav = 'create';
require __DIR__ . '/../includes/header.php';
page_heading('Create assignment', 'A draft is visible only to you. Publish it when students should see it.');
?>
<section class="card-panel narrow-panel">
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
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" type="submit" name="save_action" value="draft">Save as draft</button>
            <button class="btn btn-primary" type="submit" name="save_action" value="publish">Save and publish</button>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
