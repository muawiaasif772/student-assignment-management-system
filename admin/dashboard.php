<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$counts = [
    'users' => scalar('SELECT COUNT(*) FROM users'),
    'students' => scalar("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'teachers' => scalar("SELECT COUNT(*) FROM users WHERE role = 'teacher'"),
    'assignments' => scalar('SELECT COUNT(*) FROM assignments'),
    'submissions' => scalar('SELECT COUNT(*) FROM submissions'),
    'active' => scalar("SELECT COUNT(*) FROM users WHERE status = 'active'"),
];
$recentUsers = fetch_all('SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 6');
$recentSubmissions = fetch_all(
    'SELECT s.submitted_at, s.status, u.name AS student_name, a.title
     FROM submissions s
     JOIN users u ON u.id = s.student_id
     JOIN assignments a ON a.id = s.assignment_id
     ORDER BY s.submitted_at DESC
     LIMIT 6'
);

$pageTitle = 'Admin dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
page_heading('Administrator dashboard', 'Accounts, roles, and a snapshot of coursework on the system.');
render_announcement();
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4"><?= stat_card('Total users', $counts['users'], 'people', 'navy') ?></div>
    <div class="col-sm-6 col-xl-4"><?= stat_card('Total students', $counts['students'], 'mortarboard', 'teal') ?></div>
    <div class="col-sm-6 col-xl-4"><?= stat_card('Total teachers', $counts['teachers'], 'person-workspace', 'gold') ?></div>
    <div class="col-sm-6 col-xl-4"><?= stat_card('Total assignments', $counts['assignments'], 'journal-text', 'navy') ?></div>
    <div class="col-sm-6 col-xl-4"><?= stat_card('Total submissions', $counts['submissions'], 'inbox', 'rose') ?></div>
    <div class="col-sm-6 col-xl-4"><?= stat_card('Active accounts', $counts['active'], 'check-circle', 'teal') ?></div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <section class="card-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Newest accounts</h2>
                <a href="<?= e(base_url('admin/users.php')) ?>">Manage users</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $account): ?>
                        <tr>
                            <td><div><?= e((string) $account['name']) ?></div><div class="small text-secondary break-all"><?= e((string) $account['email']) ?></div></td>
                            <td><?= role_badge((string) $account['role']) ?></td>
                            <td><?= status_badge((string) $account['status']) ?></td>
                            <td><?= e(format_dt((string) $account['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="card-panel mb-3">
            <h2 class="h5">System information</h2>
            <dl class="meta-list">
                <div><dt>Institution</dt><dd><?= e(get_setting('institution_name')) ?></dd></div>
                <div><dt>Session</dt><dd><?= e(get_setting('academic_year')) ?></dd></div>
                <div><dt>Student registration</dt><dd><?= get_setting('student_registration_enabled') === '1' ? 'Open' : 'Closed' ?></dd></div>
                <div><dt>Teacher registration</dt><dd><?= get_setting('teacher_registration_enabled') === '1' ? 'Open' : 'Closed' ?></dd></div>
                <div><dt>Late submissions</dt><dd><?= get_setting('allow_late_submissions') === '1' ? 'Allowed' : 'Blocked' ?></dd></div>
            </dl>
            <a class="btn btn-outline-primary btn-sm mt-3" href="<?= e(base_url('admin/settings.php')) ?>">Edit system information</a>
        </section>
        <section class="card-panel">
            <h2 class="h5">Recent submissions</h2>
            <?php if ($recentSubmissions === []): ?>
                <p class="text-secondary mb-0">No files have been submitted yet.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($recentSubmissions as $item): ?>
                        <li class="mb-3">
                            <strong><?= e((string) $item['student_name']) ?></strong>
                            <div class="small text-secondary"><?= e((string) $item['title']) ?></div>
                            <div class="small"><?= submission_badge((string) $item['status']) ?> <?= e(format_dt((string) $item['submitted_at'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
