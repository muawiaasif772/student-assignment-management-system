<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$id = query_int('id');
$account = fetch_one('SELECT id, name, email, role, status FROM users WHERE id = ?', [$id]);
if ($account === null) {
    render_error('User not found', 'The user you requested could not be found.', 404);
}

$isSelf = $id === (int) current_user()['id'];
$errors = [];
$values = [
    'name' => (string) $account['name'],
    'email' => (string) $account['email'],
    'role' => (string) $account['role'],
    'status' => (string) $account['status'],
];

if (is_post()) {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    }
    $checked = validate_profile($_POST, $id, false);
    $errors = array_merge($errors, $checked['errors']);
    $values['name'] = $checked['data']['name'];
    $values['email'] = $checked['data']['email'];
    $role = $isSelf ? (string) $account['role'] : post('role');
    $status = $isSelf ? (string) $account['status'] : post('status');
    if (!$isSelf && !in_array($role, ['admin', 'teacher', 'student'], true)) {
        $errors['role'] = 'Select a valid role.';
    }
    if (!$isSelf && !in_array($status, ['active', 'inactive'], true)) {
        $errors['status'] = 'Select a valid status.';
    }
    if (!$isSelf && is_last_active_admin($id) && ($role !== 'admin' || $status !== 'active')) {
        $errors['role'] = 'The only active administrator must remain an active administrator.';
    }
    if (!$isSelf && ($role !== $account['role']) && user_has_related_records($id)) {
        $errors['role'] = 'This role cannot be changed because the account already has assignments or submissions.';
    }
    $values['role'] = in_array($role, ['admin', 'teacher', 'student'], true) ? $role : (string) $account['role'];
    $values['status'] = in_array($status, ['active', 'inactive'], true) ? $status : (string) $account['status'];

    if ($errors === []) {
        try {
            if ($checked['data']['password'] !== '') {
                $statement = db()->prepare('UPDATE users SET name = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?');
                $statement->execute([
                    $values['name'],
                    $values['email'],
                    password_hash($checked['data']['password'], PASSWORD_DEFAULT),
                    $values['role'],
                    $values['status'],
                    $id,
                ]);
            } else {
                $statement = db()->prepare('UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?');
                $statement->execute([$values['name'], $values['email'], $values['role'], $values['status'], $id]);
            }
            flash('success', 'The user account was updated.');
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

$pageTitle = 'Edit user';
$activeNav = 'users';
require __DIR__ . '/../includes/header.php';
page_heading('Edit user', $isSelf ? 'You can update your own name, email, and password.' : 'Update this account. Leave the password blank to keep the current one.', '<a class="btn btn-outline-secondary" href="' . e(base_url('admin/users.php')) . '">Back to users</a>');
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
                <?php if ($isSelf): ?>
                    <input class="form-control" id="role" value="<?= e(role_label($values['role'])) ?>" readonly>
                <?php else: ?>
                    <select class="form-select<?= invalid($errors, 'role') ?>" id="role" name="role">
                        <?php foreach (['student' => 'Student', 'teacher' => 'Teacher', 'admin' => 'Administrator'] as $value => $label): ?>
                            <option value="<?= e($value) ?>"<?= $values['role'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'role') ?>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="status">Status</label>
                <?php if ($isSelf): ?>
                    <input class="form-control" id="status" value="<?= e(ucfirst($values['status'])) ?>" readonly>
                <?php else: ?>
                    <select class="form-select<?= invalid($errors, 'status') ?>" id="status" name="status">
                        <option value="active"<?= $values['status'] === 'active' ? ' selected' : '' ?>>Active</option>
                        <option value="inactive"<?= $values['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                    </select>
                    <?= field_error($errors, 'status') ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <div class="input-group">
                <input class="form-control<?= invalid($errors, 'password') ?>" type="password" id="password" name="password" maxlength="72" autocomplete="new-password">
                <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            <div class="form-text">Leave this blank to keep the current password.</div>
            <?= field_error($errors, 'password') ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm new password</label>
            <input class="form-control<?= invalid($errors, 'confirm_password') ?>" type="password" id="confirm_password" name="confirm_password" maxlength="72" autocomplete="new-password">
            <?= field_error($errors, 'confirm_password') ?>
        </div>
        <button class="btn btn-primary" type="submit">Save changes</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
