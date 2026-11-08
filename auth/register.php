<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if (current_user() !== null) {
    redirect(dashboard_route());
}

$allowedRoles = [];
if (get_setting('student_registration_enabled', '0') === '1') {
    $allowedRoles[] = 'student';
}
if (get_setting('teacher_registration_enabled', '0') === '1') {
    $allowedRoles[] = 'teacher';
}

$errors = [];
$values = ['name' => '', 'email' => '', 'role' => $allowedRoles[0] ?? 'student'];

if ($allowedRoles !== [] && is_post()) {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    }
    $checked = validate_profile($_POST, null, true);
    $errors = array_merge($errors, $checked['errors']);
    $values['name'] = $checked['data']['name'];
    $values['email'] = $checked['data']['email'];
    $role = post('role');
    if (!in_array($role, $allowedRoles, true)) {
        $errors['role'] = 'Select a valid account type.';
        $role = $allowedRoles[0];
    }
    $values['role'] = $role;

    if ($errors === []) {
        try {
            $statement = db()->prepare(
                'INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $values['name'],
                $values['email'],
                password_hash($checked['data']['password'], PASSWORD_DEFAULT),
                $role,
                'active',
            ]);
            flash('success', 'Your account has been created. You can now log in.');
            redirect('auth/login.php');
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $errors['email'] = 'An account with this email address already exists.';
            } else {
                throw $exception;
            }
        }
    }
}

$publicLayout = true;
$pageTitle = 'Register';
require __DIR__ . '/../includes/header.php';
?>
<div class="card-panel auth-card p-4">
    <h1 class="h3 mb-1">Create an account</h1>
    <?php if ($allowedRoles === []): ?>
        <div class="alert alert-warning mb-0">Registration is currently turned off. Please contact the administrator.</div>
    <?php else: ?>
        <p class="text-secondary">Students can register here<?php if (in_array('teacher', $allowedRoles, true)): ?>, and teacher registration is currently open<?php endif; ?>.</p>
        <?php render_form_alert($errors); ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control<?= invalid($errors, 'name') ?>" type="text" id="name" name="name" value="<?= e($values['name']) ?>" maxlength="100" required>
                <?= field_error($errors, 'name') ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= e($values['email']) ?>" maxlength="150" autocomplete="username" required>
                <?= field_error($errors, 'email') ?>
            </div>
            <?php if (count($allowedRoles) === 1): ?>
                <input type="hidden" name="role" value="<?= e($allowedRoles[0]) ?>">
                <p class="small text-secondary">This form creates a <?= e($allowedRoles[0]) ?> account.</p>
            <?php else: ?>
                <div class="mb-3">
                    <label class="form-label" for="role">Account type</label>
                    <select class="form-select<?= invalid($errors, 'role') ?>" id="role" name="role">
                        <?php foreach ($allowedRoles as $option): ?>
                            <option value="<?= e($option) ?>"<?= $values['role'] === $option ? ' selected' : '' ?>><?= e(ucfirst($option)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'role') ?>
                </div>
            <?php endif; ?>
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
            <button class="btn btn-primary w-100" type="submit">Create account</button>
        </form>
    <?php endif; ?>
    <p class="mt-3 mb-0">Already registered? <a href="<?= e(base_url('auth/login.php')) ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
