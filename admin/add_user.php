<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$errors = [];
$values = ['name' => '', 'email' => '', 'role' => 'student', 'status' => 'active'];

if (is_post()) {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    }
    $checked = validate_profile($_POST, null, true);
    $errors = array_merge($errors, $checked['errors']);
    $values['name'] = $checked['data']['name'];
    $values['email'] = $checked['data']['email'];
    $role = post('role');
    $status = post('status');
    if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
        $errors['role'] = 'Select a valid role.';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors['status'] = 'Select a valid status.';
    }
    $values['role'] = in_array($role, ['admin', 'teacher', 'student'], true) ? $role : 'student';
    $values['status'] = in_array($status, ['active', 'inactive'], true) ? $status : 'active';

    if ($errors === []) {
        try {
            $statement = db()->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([
                $values['name'],
                $values['email'],
                password_hash($checked['data']['password'], PASSWORD_DEFAULT),
                $values['role'],
                $values['status'],
            ]);
            flash('success', 'The user account was created.');
            redirect('admin/users.php');
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $errors['email'] = 'An account with this email address already exists.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Add user';
$activeNav = 'add-user';
require __DIR__ . '/../includes/header.php';
page_heading('Add user', 'Create an administrator, teacher, or student account.', '<a class="btn btn-outline-secondary" href="' . e(base_url('admin/users.php')) . '">Back to users</a>');
?>
<section class="card-panel narrow-panel">
    <?php render_form_alert($errors); ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="name">Full name</label>
            <input class="form-control<?= invalid($errors, 'name') ?>" id="name" name="name" value="<?= e($values['name']) ?>" maxlength="100" required>
            <?= field_error($errors, 'name') ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= e($values['email']) ?>" maxlength="150" required>
            <?= field_error($errors, 'email') ?>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="role">Role</label>
                <select class="form-select<?= invalid($errors, 'role') ?>" id="role" name="role">
                    <?php foreach (['student' => 'Student', 'teacher' => 'Teacher', 'admin' => 'Administrator'] as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $values['role'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'role') ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select<?= invalid($errors, 'status') ?>" id="status" name="status">
                    <option value="active"<?= $values['status'] === 'active' ? ' selected' : '' ?>>Active</option>
                    <option value="inactive"<?= $values['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                </select>
                <?= field_error($errors, 'status') ?>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
                <input class="form-control<?= invalid($errors, 'password') ?>" type="password" id="password" name="password" maxlength="72" autocomplete="new-password" required>
                <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            <div class="form-text">At least 8 characters, with one letter and one number.</div>
            <?= field_error($errors, 'password') ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm password</label>
            <input class="form-control<?= invalid($errors, 'confirm_password') ?>" type="password" id="confirm_password" name="confirm_password" maxlength="72" autocomplete="new-password" required>
            <?= field_error($errors, 'confirm_password') ?>
        </div>
        <button class="btn btn-primary" type="submit">Create user</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
