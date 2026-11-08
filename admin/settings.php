<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$errors = [];
$values = [
    'site_name' => get_setting('site_name', APP_NAME),
    'institution_name' => get_setting('institution_name'),
    'academic_year' => get_setting('academic_year'),
    'contact_email' => get_setting('contact_email'),
    'announcement' => get_setting('announcement'),
    'student_registration_enabled' => get_setting('student_registration_enabled', '1'),
    'teacher_registration_enabled' => get_setting('teacher_registration_enabled', '1'),
    'allow_late_submissions' => get_setting('allow_late_submissions', '0'),
];

if (is_post()) {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    }
    $checked = validate_settings($_POST);
    $errors = array_merge($errors, $checked['errors']);
    $values = array_merge($values, $checked['data']);
    if ($errors === []) {
        save_settings($values);
        flash('success', 'System information was saved.');
        redirect('admin/settings.php');
    }
}

$pageTitle = 'System information';
$activeNav = 'settings';
require __DIR__ . '/../includes/header.php';
page_heading('System information', 'These details appear across the site and control who can register.');
?>
<section class="card-panel narrow-panel">
    <?php render_form_alert($errors); ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="site_name">System name</label>
            <input class="form-control<?= invalid($errors, 'site_name') ?>" id="site_name" name="site_name" value="<?= e($values['site_name']) ?>" maxlength="150" required>
            <?= field_error($errors, 'site_name') ?>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="institution_name">Institution</label>
                <input class="form-control<?= invalid($errors, 'institution_name') ?>" id="institution_name" name="institution_name" value="<?= e($values['institution_name']) ?>" maxlength="150" required>
                <?= field_error($errors, 'institution_name') ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="academic_year">Academic session</label>
                <input class="form-control<?= invalid($errors, 'academic_year') ?>" id="academic_year" name="academic_year" value="<?= e($values['academic_year']) ?>" maxlength="40" required>
                <?= field_error($errors, 'academic_year') ?>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="contact_email">Contact email</label>
            <input class="form-control<?= invalid($errors, 'contact_email') ?>" type="email" id="contact_email" name="contact_email" value="<?= e($values['contact_email']) ?>" maxlength="150" required>
            <?= field_error($errors, 'contact_email') ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="announcement">Announcement</label>
            <textarea class="form-control<?= invalid($errors, 'announcement') ?>" id="announcement" name="announcement" rows="3" maxlength="1000"><?= e($values['announcement']) ?></textarea>
            <div class="form-text">Shown on every dashboard. Leave it blank to hide it.</div>
            <?= field_error($errors, 'announcement') ?>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="student_registration_enabled" name="student_registration_enabled" value="1"<?= $values['student_registration_enabled'] === '1' ? ' checked' : '' ?>>
            <label class="form-check-label" for="student_registration_enabled">Allow students to register</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="teacher_registration_enabled" name="teacher_registration_enabled" value="1"<?= $values['teacher_registration_enabled'] === '1' ? ' checked' : '' ?>>
            <label class="form-check-label" for="teacher_registration_enabled">Allow teachers to register</label>
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" id="allow_late_submissions" name="allow_late_submissions" value="1"<?= $values['allow_late_submissions'] === '1' ? ' checked' : '' ?>>
            <label class="form-check-label" for="allow_late_submissions">Allow submissions after the deadline</label>
        </div>
        <button class="btn btn-primary" type="submit">Save information</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
