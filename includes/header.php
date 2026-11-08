<?php
declare(strict_types=1);

require_once __DIR__ . '/navbar.php';

$pageTitle = $pageTitle ?? 'Home';
$activeNav = $activeNav ?? '';
$publicLayout = !empty($publicLayout);
$user = current_user();
$siteName = get_setting('site_name', APP_NAME);
$institution = get_setting('institution_name', '');
$academicYear = get_setting('academic_year', '');
$contactEmail = get_setting('contact_email', '');
$showRegister = registration_open();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> | <?= e($siteName) ?></title>
    <link rel="icon" href="<?= e(base_url('assets/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<?php if ($publicLayout): ?>
<body class="layout-public">
<a class="skip-link" href="#content">Skip to content</a>
<div class="public-wrap">
    <nav class="public-nav" aria-label="Public">
        <div class="container public-nav-inner">
            <a class="brand" href="<?= e(base_url('index.php')) ?>">
                <span class="brand-mark">S</span>
                <span><strong><?= e(APP_SHORT) ?></strong><small><?= e($siteName) ?></small></span>
            </a>
            <div class="d-flex gap-2">
                <?php if ($user !== null): ?>
                    <a class="btn btn-sm btn-outline-light" href="<?= e(base_url(dashboard_route())) ?>">Dashboard</a>
                    <?= logout_button('btn btn-sm btn-warning') ?>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('auth/login.php')) ?>">Log in</a>
                    <?php if ($showRegister): ?>
                        <a class="btn btn-sm btn-warning" href="<?= e(base_url('auth/register.php')) ?>">Register</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main id="content" class="container py-4 py-lg-5">
        <?php render_flash(); ?>
<?php else: ?>
<body class="layout-app" data-max-upload="<?= (int) MAX_UPLOAD_BYTES ?>">
<a class="skip-link" href="#content">Skip to content</a>
<?php render_sidebar($activeNav); ?>
<?php render_mobile_nav($activeNav); ?>
<div class="app-main">
    <header class="topbar">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title"><?= e($institution !== '' ? $institution : $siteName) ?></div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <?php if ($user !== null): ?>
                <div class="text-end d-none d-sm-block">
                    <div class="user-name"><?= e((string) $user['name']) ?></div>
                    <span class="badge rounded-pill text-bg-light border"><?= e(role_label((string) $user['role'])) ?></span>
                </div>
            <?php endif; ?>
            <?= logout_button('btn btn-sm btn-outline-secondary') ?>
        </div>
    </header>
    <main id="content" class="content">
        <div class="container-fluid page-wrap">
            <?php render_flash(); ?>
<?php endif; ?>
