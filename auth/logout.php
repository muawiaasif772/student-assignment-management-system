<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if (current_user() === null && empty($_SESSION['user_id'])) {
    redirect('auth/login.php');
}

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', 'Your session expired or the form was invalid. Please try again.');
        redirect('auth/logout.php');
    }
    end_session('success', 'You have been logged out.');
    redirect('auth/login.php');
}

$publicLayout = true;
$pageTitle = 'Log out';
require __DIR__ . '/../includes/header.php';
?>
<div class="card-panel auth-card p-4">
    <h1 class="h3">Log out</h1>
    <p>This will end your SAMS session on this browser.</p>
    <form method="post">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit">Log out</button>
        <a class="btn btn-outline-secondary" href="<?= e(base_url(dashboard_route())) ?>">Stay signed in</a>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
