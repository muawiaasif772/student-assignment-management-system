<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if (current_user() !== null) {
    redirect(dashboard_route());
}

$errors = [];
$email = '';

if (is_post()) {
    $email = strtolower(trim(post('email')));
    $password = post('password');
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired or the form was invalid. Please try again.';
    } else {
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter the email address for your account.';
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }
        if ($errors === []) {
            $result = attempt_login($email, $password);
            if (!$result['ok']) {
                $errors['form'] = (string) $result['error'];
            } else {
                $user = $result['user'];
                login_user($user);
                $destination = take_intended_url((string) $user['role']);
                flash('success', 'Welcome back, ' . $user['name'] . '.');
                redirect($destination ?? dashboard_route());
            }
        }
    }
}

$publicLayout = true;
$pageTitle = 'Log in';
require __DIR__ . '/../includes/header.php';
?>
<div class="card-panel auth-card p-4">
    <h1 class="h3 mb-1">Log in</h1>
    <p class="text-secondary">Use your administrator, teacher, or student account. SAMS opens the matching dashboard.</p>
    <?php render_form_alert($errors); ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= e($email) ?>" maxlength="150" autocomplete="username" required>
            <?= field_error($errors, 'email') ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
                <input class="form-control<?= invalid($errors, 'password') ?>" type="password" id="password" name="password" maxlength="72" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            <?= field_error($errors, 'password') ?>
        </div>
        <button class="btn btn-primary w-100" type="submit">Log in</button>
    </form>
    <?php if (registration_open()): ?>
        <p class="mt-3 mb-0">Need an account? <a href="<?= e(base_url('auth/register.php')) ?>">Register</a></p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
