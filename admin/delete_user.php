<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('admin');

if (!is_post() || !csrf_ok()) {
    flash('danger', 'Invalid request. Please try again.');
    redirect('admin/users.php');
}

$action = post('action');
$userId = (int) post('user_id');
$actorId = (int) current_user()['id'];

if ($userId < 1 || !in_array($action, ['delete', 'deactivate', 'activate'], true)) {
    flash('danger', 'Invalid request. Please try again.');
    redirect('admin/users.php');
}

$account = fetch_one('SELECT id, name, role, status FROM users WHERE id = ?', [$userId]);
if ($account === null) {
    flash('danger', 'The user could not be found.');
    redirect('admin/users.php');
}

if ($userId === $actorId && $action !== 'activate') {
    flash('danger', 'You cannot delete or deactivate the account you are currently using.');
    redirect('admin/users.php');
}

if (is_last_active_admin($userId) && $action !== 'activate') {
    flash('danger', 'The only active administrator account cannot be removed or deactivated.');
    redirect('admin/users.php');
}

if ($action === 'delete') {
    if (user_has_related_records($userId)) {
        flash('danger', 'This user has assignments or submissions, so the account was not deleted. Deactivate it instead.');
        redirect('admin/users.php');
    }
    try {
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    } catch (PDOException $exception) {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1451) {
            flash('danger', 'This user has related records, so the account was not deleted. Deactivate it instead.');
            redirect('admin/users.php');
        }
        throw $exception;
    }
    flash('success', 'The user account was deleted.');
    redirect('admin/users.php');
}

$newStatus = $action === 'activate' ? 'active' : 'inactive';
db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
flash('success', $newStatus === 'active' ? 'The user account was reactivated.' : 'The user account was deactivated.');
redirect('admin/users.php');
