<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (current_user() !== null) {
    redirect(dashboard_route());
}

$teacherCount = scalar("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active'");
$studentCount = scalar("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'");
$openCount = scalar("SELECT COUNT(*) FROM assignments WHERE status = 'published' AND deadline >= NOW()");
$publicLayout = true;
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <p class="mb-2 eyebrow">Final year project</p>
            <h1>Submit coursework. Review it. Return feedback.</h1>
            <p class="mb-0">SAMS gives teachers one place to publish assignments and gives students a clear way to upload work, track status, and read marks.</p>
            <div class="hero-actions">
                <a class="btn btn-warning" href="<?= e(base_url('auth/login.php')) ?>">Log in</a>
                <?php if (registration_open()): ?>
                    <a class="btn btn-outline-light" href="<?= e(base_url('auth/register.php')) ?>">Create an account</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="hero-stats">
                <div><strong><?= e((string) $teacherCount) ?></strong><span>Active teachers</span></div>
                <div><strong><?= e((string) $studentCount) ?></strong><span>Active students</span></div>
                <div><strong><?= e((string) $openCount) ?></strong><span>Open assignments</span></div>
            </div>
        </div>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <article class="feature-card">
            <i class="bi bi-shield-lock"></i>
            <h2 class="h5 mt-3">Administrator</h2>
            <p class="mb-0 text-secondary">Create accounts, activate or deactivate users, and keep the institution name, session, and registration rules up to date.</p>
        </article>
    </div>
    <div class="col-md-4">
        <article class="feature-card">
            <i class="bi bi-journal-text"></i>
            <h2 class="h5 mt-3">Teacher</h2>
            <p class="mb-0 text-secondary">Draft an assignment, publish it, close it when the work is finished, download files, and record marks with feedback.</p>
        </article>
    </div>
    <div class="col-md-4">
        <article class="feature-card">
            <i class="bi bi-mortarboard"></i>
            <h2 class="h5 mt-3">Student</h2>
            <p class="mb-0 text-secondary">Register, open published assignments, upload a PDF, DOC, DOCX, or ZIP file, and return later for the grade.</p>
        </article>
    </div>
</div>

<section class="card-panel p-4">
    <h2 class="h4 mb-3">How a submission moves through SAMS</h2>
    <div class="row g-3">
        <div class="col-md-3 d-flex gap-3">
            <span class="step-no">1</span>
            <div><strong>Sign in</strong><div class="text-secondary small">Each person lands on the dashboard for their role.</div></div>
        </div>
        <div class="col-md-3 d-flex gap-3">
            <span class="step-no">2</span>
            <div><strong>Publish</strong><div class="text-secondary small">A teacher sets the instructions, deadline, and maximum marks.</div></div>
        </div>
        <div class="col-md-3 d-flex gap-3">
            <span class="step-no">3</span>
            <div><strong>Upload</strong><div class="text-secondary small">A student submits one file before the deadline.</div></div>
        </div>
        <div class="col-md-3 d-flex gap-3">
            <span class="step-no">4</span>
            <div><strong>Grade</strong><div class="text-secondary small">The teacher records marks and written feedback.</div></div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
