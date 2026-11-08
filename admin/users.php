<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$q = query_string('q', 100);
$roleFilter = query_string('role', 20);
$statusFilter = query_string('status', 20);
if (!in_array($roleFilter, ['', 'admin', 'teacher', 'student'], true)) {
    $roleFilter = '';
}
if (!in_array($statusFilter, ['', 'active', 'inactive'], true)) {
    $statusFilter = '';
}

$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? ESCAPE \'|\' OR email LIKE ? ESCAPE \'|\')';
    $like = like_param($q);
    $params[] = $like;
    $params[] = $like;
}
if ($roleFilter !== '') {
    $where[] = 'role = ?';
    $params[] = $roleFilter;
}
if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$whereSql = implode(' AND ', $where);
$total = scalar('SELECT COUNT(*) FROM users WHERE ' . $whereSql, $params);
$perPage = 8;
[$page, $pages, $offset] = pager($total, query_int('page', 1), $perPage);
$users = fetch_all(
    'SELECT id, name, email, role, status, created_at FROM users WHERE ' . $whereSql
    . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
    $params
);
$activeAdmins = scalar("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
$actorId = (int) current_user()['id'];
$query = array_filter([
    'q' => $q,
    'role' => $roleFilter,
    'status' => $statusFilter,
], static fn (string $value): bool => $value !== '');

$activeNav = match ($roleFilter) {
    'student' => 'students',
    'teacher' => 'teachers',
    default => 'users',
};
$pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
$heading = match ($roleFilter) {
    'student' => 'Students',
    'teacher' => 'Teachers',
    default => 'All users',
};
page_heading($heading, 'Search accounts, then edit, deactivate, or delete them.', '<a class="btn btn-primary" href="' . e(base_url('admin/add_user.php')) . '"><i class="bi bi-person-plus me-1"></i>Add user</a>');
?>
<section class="card-panel mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="q">Search name or email</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" maxlength="100">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="role">Role</label>
            <select class="form-select" id="role" name="role">
                <option value="">All roles</option>
                <?php foreach (['admin' => 'Administrator', 'teacher' => 'Teacher', 'student' => 'Student'] as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= $roleFilter === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Any status</option>
                <option value="active"<?= $statusFilter === 'active' ? ' selected' : '' ?>>Active</option>
                <option value="inactive"<?= $statusFilter === 'inactive' ? ' selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('admin/users.php')) ?>">Reset</a>
        </div>
    </form>
</section>
<section class="card-panel">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <p class="text-secondary mb-0"><?= e(showing_range($page, $perPage, $total)) ?></p>
    </div>
    <?php if ($users === []): ?>
        <div class="empty-state"><i class="bi bi-people"></i>No users match this search.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Name</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $account): ?>
                    <?php
                    $id = (int) $account['id'];
                    $isSelf = $id === $actorId;
                    $onlyAdmin = $account['role'] === 'admin' && $account['status'] === 'active' && $activeAdmins <= 1;
                    ?>
                    <tr>
                        <td><div><?= e((string) $account['name']) ?></div><div class="small text-secondary break-all"><?= e((string) $account['email']) ?></div></td>
                        <td><?= role_badge((string) $account['role']) ?></td>
                        <td><?= status_badge((string) $account['status']) ?></td>
                        <td><?= e(format_dt((string) $account['created_at'])) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('admin/edit_user.php?id=' . $id)) ?>">Edit</a>
                                <?php if ($isSelf): ?>
                                    <span class="badge text-bg-light border align-self-center">Current account</span>
                                <?php elseif ($onlyAdmin): ?>
                                    <span class="badge text-bg-light border align-self-center">Only administrator</span>
                                <?php else: ?>
                                    <?php if ($account['status'] === 'active'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning js-confirm" data-url="<?= e(base_url('admin/delete_user.php')) ?>" data-field="user_id" data-id="<?= $id ?>" data-action="deactivate" data-title="Deactivate user" data-message="Deactivate <?= e((string) $account['name']) ?>? They will not be able to log in." data-confirm-label="Deactivate" data-confirm-class="btn-warning">Deactivate</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-success js-confirm" data-url="<?= e(base_url('admin/delete_user.php')) ?>" data-field="user_id" data-id="<?= $id ?>" data-action="activate" data-title="Reactivate user" data-message="Allow <?= e((string) $account['name']) ?> to log in again?" data-confirm-label="Reactivate" data-confirm-class="btn-success">Reactivate</button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger js-confirm" data-url="<?= e(base_url('admin/delete_user.php')) ?>" data-field="user_id" data-id="<?= $id ?>" data-action="delete" data-title="Delete user" data-message="Delete <?= e((string) $account['name']) ?> permanently? Accounts with assignments or submissions should be deactivated instead." data-confirm-label="Delete user" data-confirm-class="btn-danger">Delete</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($page, $pages, $query) ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
