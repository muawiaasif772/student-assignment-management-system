<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('teacher');

$teacherId = (int) current_user()['id'];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', 'Your session expired or the form was invalid. Please try again.');
        redirect('teacher/assignments.php');
    }
    $action = post('action');
    $assignmentId = (int) post('assignment_id');
    $assignment = teacher_assignment($assignmentId, $teacherId);
    if ($assignment === null) {
        flash('danger', 'The assignment could not be found.');
        redirect('teacher/assignments.php');
    }
    if ($action === 'publish') {
        if ($assignment['status'] !== 'draft') {
            flash('danger', 'Only a draft assignment can be published.');
        } elseif (deadline_passed((string) $assignment['deadline'])) {
            flash('danger', 'The deadline has passed. Update the deadline before publishing.');
        } else {
            db()->prepare("UPDATE assignments SET status = 'published' WHERE id = ? AND teacher_id = ?")->execute([$assignmentId, $teacherId]);
            flash('success', 'The assignment is now published. Students can view it.');
        }
    } elseif ($action === 'close') {
        if ($assignment['status'] !== 'published') {
            flash('danger', 'Only a published assignment can be closed.');
        } else {
            db()->prepare("UPDATE assignments SET status = 'closed' WHERE id = ? AND teacher_id = ?")->execute([$assignmentId, $teacherId]);
            flash('success', 'The assignment is closed. Students can no longer submit.');
        }
    } else {
        flash('danger', 'Invalid request. Please try again.');
    }
    redirect('teacher/assignments.php');
}

$q = query_string('q', 100);
$where = 'a.teacher_id = ?';
$params = [$teacherId];
if ($q !== '') {
    $where .= ' AND a.title LIKE ? ESCAPE \'|\'';
    $params[] = like_param($q);
}
$total = scalar('SELECT COUNT(*) FROM assignments a WHERE ' . $where, $params);
$perPage = 8;
[$page, $pages, $offset] = pager($total, query_int('page', 1), $perPage);
$rows = fetch_all(
    'SELECT a.*,
        (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submission_count,
        (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.status = \'graded\') AS graded_count
     FROM assignments a
     WHERE ' . $where . '
     ORDER BY a.created_at DESC
     LIMIT ' . $perPage . ' OFFSET ' . $offset,
    $params
);

$pageTitle = 'My assignments';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
page_heading('My assignments', 'Drafts stay hidden from students until you publish them.', '<a class="btn btn-primary" href="' . e(base_url('teacher/create_assignment.php')) . '">Create assignment</a>');
?>
<section class="card-panel mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-8">
            <label class="form-label" for="q">Search by title</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" maxlength="100">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('teacher/assignments.php')) ?>">Reset</a>
        </div>
    </form>
</section>
<section class="card-panel">
    <p class="text-secondary"><?= e(showing_range($page, $perPage, $total)) ?></p>
    <?php if ($rows === []): ?>
        <div class="empty-state"><i class="bi bi-journal-plus"></i>No assignments yet. Create one to get started.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Title</th><th>Deadline</th><th>Marks</th><th>Status</th><th>Submissions</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $assignment): ?>
                    <?php $id = (int) $assignment['id']; ?>
                    <tr>
                        <td><?= e((string) $assignment['title']) ?></td>
                        <td><?= e(format_dt((string) $assignment['deadline'])) ?> <?= deadline_badge((string) $assignment['deadline']) ?></td>
                        <td><?= e(format_marks($assignment['max_marks'])) ?></td>
                        <td><?= assignment_badge((string) $assignment['status']) ?></td>
                        <td><?= e((string) $assignment['submission_count']) ?> total<br><span class="small text-secondary"><?= e((string) $assignment['graded_count']) ?> graded</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('teacher/edit_assignment.php?id=' . $id)) ?>">Edit</a>
                                <?php if ((int) $assignment['submission_count'] > 0 || $assignment['status'] !== 'draft'): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('teacher/submissions.php?assignment_id=' . $id)) ?>">Submissions</a>
                                <?php endif; ?>
                                <?php if ($assignment['status'] === 'draft'): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="publish">
                                        <input type="hidden" name="assignment_id" value="<?= $id ?>">
                                        <button class="btn btn-sm btn-success" type="submit">Publish</button>
                                    </form>
                                <?php elseif ($assignment['status'] === 'published'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-dark js-confirm" data-url="<?= e(base_url('teacher/assignments.php')) ?>" data-field="assignment_id" data-id="<?= $id ?>" data-action="close" data-title="Close assignment" data-message="Close this assignment? Students will no longer be able to submit." data-confirm-label="Close assignment" data-confirm-class="btn-dark">Close</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm" data-url="<?= e(base_url('teacher/delete_assignment.php')) ?>" data-field="assignment_id" data-id="<?= $id ?>" data-action="delete" data-title="Delete assignment" data-message="Delete <?= e((string) $assignment['title']) ?>? Submitted files for this assignment will also be removed." data-confirm-label="Delete assignment" data-confirm-class="btn-danger">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($page, $pages, $q !== '' ? ['q' => $q] : []) ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
