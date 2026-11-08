<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_role('student');

$studentId = (int) current_user()['id'];
$q = query_string('q', 100);
$where = "a.status = 'published'";
$params = [$studentId];
if ($q !== '') {
    $where .= ' AND a.title LIKE ? ESCAPE \'|\'';
    $params[] = like_param($q);
}
$total = scalar(
    'SELECT COUNT(*) FROM assignments a WHERE ' . $where,
    array_slice($params, 1)
);
$perPage = 8;
[$page, $pages, $offset] = pager($total, query_int('page', 1), $perPage);
$rows = fetch_all(
    'SELECT a.id, a.title, a.deadline, a.max_marks, u.name AS teacher_name,
            s.id AS submission_id, s.status AS submission_status, s.marks
     FROM assignments a
     JOIN users u ON u.id = a.teacher_id
     LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
     WHERE ' . $where . '
     ORDER BY (a.deadline < NOW()), a.deadline ASC
     LIMIT ' . $perPage . ' OFFSET ' . $offset,
    $params
);

$pageTitle = 'Assignments';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
page_heading('Available assignments', 'Only published assignments are listed. Drafts and closed work stay off this page.');
?>
<section class="card-panel mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-8">
            <label class="form-label" for="q">Search by title</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" maxlength="100">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('student/assignments.php')) ?>">Reset</a>
        </div>
    </form>
</section>
<section class="card-panel">
    <p class="text-secondary"><?= e(showing_range($page, $perPage, $total)) ?></p>
    <?php if ($rows === []): ?>
        <div class="empty-state"><i class="bi bi-journal"></i>No published assignments match this search.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Title</th><th>Teacher</th><th>Deadline</th><th>Marks</th><th>Your work</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['title']) ?></td>
                        <td><?= e((string) $row['teacher_name']) ?></td>
                        <td><?= e(format_dt((string) $row['deadline'])) ?><div><?= deadline_badge((string) $row['deadline']) ?></div></td>
                        <td><?= e(format_marks($row['max_marks'])) ?></td>
                        <td>
                            <?php if ($row['submission_id'] === null): ?>
                                <span class="badge text-bg-light border">Not submitted</span>
                            <?php else: ?>
                                <?= submission_badge((string) $row['submission_status']) ?>
                                <?php if ($row['submission_status'] === 'graded'): ?>
                                    <div class="small"><?= e(format_marks($row['marks'])) ?> / <?= e(format_marks($row['max_marks'])) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><a class="btn btn-sm btn-primary" href="<?= e(base_url('student/assignment_details.php?id=' . (int) $row['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($page, $pages, $q !== '' ? ['q' => $q] : []) ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
