<?php
declare(strict_types=1);

function e(null|string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function e_nl(null|string $value): string
{
    return nl2br(e($value ?? ''), false);
}

function str_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function str_clip(string $value, int $max): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function normalize_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    if (DIRECTORY_SEPARATOR === '\\') {
        $path = strtolower($path);
    }
    return rtrim($path, '/');
}

function base_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $base = detect_base_path();
    }

    $path = ltrim($path, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base;
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function detect_base_path(): string
{
    if (PHP_SAPI === 'cli') {
        return '/sams';
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $appRoot = realpath(APP_ROOT);
    $scriptFile = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if ($appRoot === false || $scriptFile === false) {
        return '/sams';
    }

    $appNorm = normalize_path($appRoot);
    $scriptNorm = normalize_path($scriptFile);
    if (!str_starts_with($scriptNorm, $appNorm)) {
        return '/sams';
    }

    $relative = substr($scriptNorm, strlen($appNorm));
    if ($relative === '' || $relative === false) {
        return rtrim($scriptName, '/');
    }

    $scriptLower = strtolower($scriptName);
    $relativeLower = strtolower($relative);
    if (str_ends_with($scriptLower, $relativeLower)) {
        return rtrim(substr($scriptName, 0, strlen($scriptName) - strlen($relative)), '/');
    }

    return '/sams';
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, '/')) {
        $target = $path;
    } else {
        $target = base_url($path);
    }
    header('Location: ' . $target, true, 303);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function query_string(string $key, int $max = 100): string
{
    $value = $_GET[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return str_clip(trim($value), $max);
}

function query_int(string $key, int $default = 0): int
{
    $value = $_GET[$key] ?? null;
    if (!is_scalar($value) || preg_match('/^-?\d+$/', (string) $value) !== 1) {
        return $default;
    }
    return (int) $value;
}

function post_too_large(): bool
{
    if (!is_post()) {
        return false;
    }
    $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    return $length > 0 && $_POST === [] && $_FILES === [];
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    if (empty($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

function render_flash(): void
{
    $flash = pull_flash();
    if ($flash === null) {
        return;
    }
    $type = (string) ($flash['type'] ?? 'info');
    if (!in_array($type, ['success', 'danger', 'warning', 'info'], true)) {
        $type = 'info';
    }
    echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">';
    echo e((string) ($flash['message'] ?? ''));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && is_string($known) && $known !== '' && hash_equals($known, $token);
}

function scalar(string $sql, array $params = []): int
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();
    return $row === false ? null : $row;
}

function fetch_all(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function like_param(string $value): string
{
    $value = str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value);
    return '%' . $value . '%';
}

function email_taken(string $email, ?int $exceptId = null): bool
{
    if ($exceptId !== null && $exceptId > 0) {
        return scalar('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, $exceptId]) > 0;
    }
    return scalar('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0;
}

function settings(): array
{
    static $all = null;
    if ($all !== null) {
        return $all;
    }
    $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $all = [];
    foreach ($rows as $row) {
        $all[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
    }
    return $all;
}

function get_setting(string $key, string $default = ''): string
{
    $all = settings();
    return $all[$key] ?? $default;
}

function save_settings(array $pairs): void
{
    $statement = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = ?'
    );
    foreach ($pairs as $key => $value) {
        $statement->execute([(string) $key, (string) $value, (string) $value]);
    }
}

function registration_open(): bool
{
    return get_setting('student_registration_enabled', '0') === '1'
        || get_setting('teacher_registration_enabled', '0') === '1';
}

function format_dt(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '—';
    }
    return date('d M Y, h:i A', $timestamp);
}

function format_marks(mixed $marks): string
{
    if ($marks === null || $marks === '' || !is_numeric($marks)) {
        return '—';
    }
    $formatted = number_format((float) $marks, 2, '.', '');
    $trimmed = rtrim(rtrim($formatted, '0'), '.');
    return $trimmed === '' ? '0' : $trimmed;
}

function deadline_input(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $timestamp);
}

function deadline_passed(string $deadline): bool
{
    $timestamp = strtotime($deadline);
    return $timestamp !== false && $timestamp <= time();
}

function assignment_badge(string $status): string
{
    $map = [
        'draft' => ['secondary', 'Draft'],
        'published' => ['success', 'Published'],
        'closed' => ['dark', 'Closed'],
    ];
    [$class, $label] = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge text-bg-' . e($class) . '">' . e($label) . '</span>';
}

function submission_badge(?string $status): string
{
    $map = [
        'submitted' => ['primary', 'Submitted'],
        'late' => ['warning', 'Late'],
        'graded' => ['success', 'Graded'],
    ];
    [$class, $label] = $map[$status ?? ''] ?? ['secondary', 'Unknown'];
    return '<span class="badge text-bg-' . e($class) . '">' . e($label) . '</span>';
}

function deadline_badge(string $deadline): string
{
    if (deadline_passed($deadline)) {
        return '<span class="badge text-bg-danger">Deadline passed</span>';
    }
    return '<span class="badge text-bg-success">Open</span>';
}

function role_badge(string $role): string
{
    $map = [
        'admin' => 'dark',
        'teacher' => 'primary',
        'student' => 'info',
    ];
    $class = $map[$role] ?? 'secondary';
    return '<span class="badge text-bg-' . e($class) . '">' . e(ucfirst($role)) . '</span>';
}

function status_badge(string $status): string
{
    if ($status === 'active') {
        return '<span class="badge text-bg-success">Active</span>';
    }
    return '<span class="badge text-bg-secondary">Inactive</span>';
}

function invalid(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' is-invalid' : '';
}

function field_error(array $errors, string $key): string
{
    if (!isset($errors[$key])) {
        return '';
    }
    return '<div class="field-error">' . e((string) $errors[$key]) . '</div>';
}

function render_form_alert(array $errors): void
{
    if ($errors === []) {
        return;
    }
    $message = (string) ($errors['form'] ?? 'Please correct the highlighted fields.');
    echo '<div class="alert alert-danger">' . e($message) . '</div>';
}

function page_heading(string $title, string $subtitle = '', string $actions = ''): void
{
    echo '<div class="page-heading"><div><h1>' . e($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p>' . e($subtitle) . '</p>';
    }
    echo '</div>';
    if ($actions !== '') {
        echo '<div class="page-actions">' . $actions . '</div>';
    }
    echo '</div>';
}

function stat_card(string $label, int|string $value, string $icon, string $tone = 'teal'): string
{
    $allowed = ['teal', 'navy', 'gold', 'rose'];
    if (!in_array($tone, $allowed, true)) {
        $tone = 'teal';
    }
    return '<div class="stat-card stat-' . e($tone) . '">'
        . '<div class="stat-icon"><i class="bi bi-' . e($icon) . '"></i></div>'
        . '<div><div class="stat-value">' . e((string) $value) . '</div>'
        . '<div class="stat-label">' . e($label) . '</div></div></div>';
}

function render_pagination(int $page, int $totalPages, array $params = []): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
    $html = '<nav class="mt-3" aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">';
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);

    $link = static function (int $target, string $label, bool $disabled, bool $active) use ($path, $params): string {
        if ($disabled) {
            return '<li class="page-item disabled"><span class="page-link">' . e($label) . '</span></li>';
        }
        if ($active) {
            return '<li class="page-item active" aria-current="page"><span class="page-link">' . e($label) . '</span></li>';
        }
        $query = $params;
        $query['page'] = $target;
        $href = $path . '?' . http_build_query($query);
        return '<li class="page-item"><a class="page-link" href="' . e($href) . '">' . e($label) . '</a></li>';
    };

    $html .= $link(max(1, $page - 1), 'Previous', $page <= 1, false);
    for ($i = $start; $i <= $end; $i++) {
        $html .= $link($i, (string) $i, false, $i === $page);
    }
    $html .= $link(min($totalPages, $page + 1), 'Next', $page >= $totalPages, false);
    $html .= '</ul></nav>';
    return $html;
}

function showing_range(int $page, int $perPage, int $total): string
{
    if ($total === 0) {
        return 'No records to show.';
    }
    $from = (($page - 1) * $perPage) + 1;
    $to = min($total, $page * $perPage);
    return 'Showing ' . $from . '–' . $to . ' of ' . $total;
}

function pager(int $total, int $requestedPage, int $perPage = 10): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $requestedPage), $pages);
    $offset = ($page - 1) * $perPage;
    return [$page, $pages, $offset];
}

function render_announcement(): void
{
    $announcement = trim(get_setting('announcement', ''));
    if ($announcement === '') {
        return;
    }
    echo '<div class="alert alert-info announcement">' . e_nl($announcement) . '</div>';
}

function render_error(string $title, string $message, int $status = 404): void
{
    http_response_code($status);
    $pageTitle = $title;
    $activeNav = '';
    $publicLayout = current_user() === null;
    require APP_ROOT . '/includes/header.php';
    echo '<div class="card-panel p-4 p-md-5">';
    echo '<h1 class="h3 mb-3">' . e($title) . '</h1>';
    echo '<p class="mb-4">' . e($message) . '</p>';
    if ($publicLayout) {
        $href = base_url('auth/login.php');
        $label = 'Go to login';
    } else {
        $href = base_url(dashboard_route());
        $label = 'Go to your dashboard';
    }
    echo '<a class="btn btn-primary" href="' . e($href) . '">' . e($label) . '</a>';
    echo '</div>';
    require APP_ROOT . '/includes/footer.php';
    exit;
}

function ensure_upload_dir(): void
{
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('Upload directory is not available.');
    }
    $htaccess = UPLOAD_DIR . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, "Require all denied\n");
    }
}

function delete_stored_file(?string $stored): void
{
    if ($stored === null || $stored === '') {
        return;
    }
    $stored = basename($stored);
    if (preg_match('/^[a-f0-9]{32}\.(pdf|doc|docx|zip)$/i', $stored) !== 1) {
        return;
    }
    $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored;
    if (is_file($path)) {
        if (!@unlink($path)) {
            error_log('Could not delete uploaded file: ' . $stored);
        }
    }
}

function accept_upload(array $file): array
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        $message = match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large. Maximum allowed size is 10 MB.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
            default => 'The file could not be uploaded. Please try again.',
        };
        return ['ok' => false, 'error' => $message];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'The file could not be uploaded. Please try again.'];
    }
    if ($size <= 0) {
        return ['ok' => false, 'error' => 'The selected file is empty.'];
    }
    if ($size > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'The file is too large. Maximum allowed size is 10 MB.'];
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'zip'];
    if (!in_array($extension, $allowed, true)) {
        return ['ok' => false, 'error' => 'Invalid file. Allowed types are PDF, DOC, DOCX, and ZIP.'];
    }

    if (!class_exists('finfo')) {
        return ['ok' => false, 'error' => 'The server cannot validate uploaded files. Please contact the administrator.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowedMimes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    ];
    if (!is_string($mime) || !in_array($mime, $allowedMimes[$extension], true)) {
        return ['ok' => false, 'error' => 'Invalid file. Allowed types are PDF, DOC, DOCX, and ZIP.'];
    }

    $handle = fopen($tmp, 'rb');
    if ($handle === false) {
        return ['ok' => false, 'error' => 'The file could not be uploaded. Please try again.'];
    }
    $signature = fread($handle, 8) ?: '';
    fclose($handle);
    $signatureOk = match ($extension) {
        'pdf' => str_starts_with($signature, '%PDF'),
        'doc' => str_starts_with($signature, "\xD0\xCF\x11\xE0"),
        'docx', 'zip' => str_starts_with($signature, 'PK'),
        default => false,
    };
    if (!$signatureOk) {
        return ['ok' => false, 'error' => 'Invalid file. Allowed types are PDF, DOC, DOCX, and ZIP.'];
    }

    try {
        ensure_upload_dir();
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        return ['ok' => false, 'error' => 'The file could not be uploaded. Please try again.'];
    }

    $stored = '';
    $destination = '';
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $stored = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored;
        if (!is_file($destination)) {
            break;
        }
    }

    if (!move_uploaded_file($tmp, $destination)) {
        return ['ok' => false, 'error' => 'The file could not be uploaded. Please try again.'];
    }
    @chmod($destination, 0644);

    $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
    $original = preg_replace('/[\x00-\x1F\x7F]/u', '', $original) ?? '';
    $original = preg_replace('/[^A-Za-z0-9._ -]/', '_', $original) ?? '';
    $original = trim($original, '. ');
    if ($original === '' || strlen($original) > 180) {
        $original = 'submission.' . $extension;
    }
    if (!str_ends_with(strtolower($original), '.' . $extension)) {
        $original .= '.' . $extension;
    }

    return ['ok' => true, 'error' => '', 'stored' => $stored, 'original' => $original];
}

function submit_assignment_file(int $assignmentId, int $studentId, array $file): array
{
    $accepted = accept_upload($file);
    if (!$accepted['ok']) {
        return $accepted;
    }

    $stored = (string) $accepted['stored'];
    $original = (string) $accepted['original'];
    $allowLate = get_setting('allow_late_submissions', '0') === '1';
    $pdo = db();

    try {
        $pdo->beginTransaction();
        $assignmentStatement = $pdo->prepare('SELECT id, status, deadline FROM assignments WHERE id = ? FOR UPDATE');
        $assignmentStatement->execute([$assignmentId]);
        $assignment = $assignmentStatement->fetch();
        if ($assignment === false || $assignment['status'] !== 'published') {
            $pdo->rollBack();
            delete_stored_file($stored);
            return ['ok' => false, 'error' => 'This assignment is not open for submissions.'];
        }

        $existingStatement = $pdo->prepare(
            'SELECT id, file_path, status FROM submissions WHERE assignment_id = ? AND student_id = ? FOR UPDATE'
        );
        $existingStatement->execute([$assignmentId, $studentId]);
        $existing = $existingStatement->fetch();
        if ($existing !== false && $existing['status'] === 'graded') {
            $pdo->rollBack();
            delete_stored_file($stored);
            return ['ok' => false, 'error' => 'This submission has already been graded and can no longer be replaced.'];
        }

        $overdue = deadline_passed((string) $assignment['deadline']);
        if ($overdue && !$allowLate) {
            $pdo->rollBack();
            delete_stored_file($stored);
            return ['ok' => false, 'error' => 'The deadline has passed. Late submissions are not allowed.'];
        }

        $status = ($overdue && $allowLate) ? 'late' : 'submitted';
        $oldFile = null;
        if ($existing !== false) {
            $update = $pdo->prepare(
                'UPDATE submissions
                 SET file_name = ?, file_path = ?, submitted_at = NOW(), marks = NULL, feedback = NULL, status = ?
                 WHERE id = ? AND student_id = ?'
            );
            $update->execute([$original, $stored, $status, (int) $existing['id'], $studentId]);
            $oldFile = (string) $existing['file_path'];
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO submissions (assignment_id, student_id, file_name, file_path, status)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $insert->execute([$assignmentId, $studentId, $original, $stored, $status]);
        }
        $pdo->commit();
        if ($oldFile !== null && $oldFile !== $stored) {
            delete_stored_file($oldFile);
        }
        return ['ok' => true, 'error' => '', 'late' => $status === 'late'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        delete_stored_file($stored);
        $driverCode = $exception instanceof PDOException ? (int) ($exception->errorInfo[1] ?? 0) : 0;
        error_log($exception->getMessage());
        if ($driverCode === 1062) {
            return ['ok' => false, 'error' => 'You have already submitted this assignment. Refresh the page and try again.'];
        }
        return ['ok' => false, 'error' => 'The file could not be uploaded. Please try again.'];
    }
}

function can_student_submit(array $assignment, ?array $submission): array
{
    if (($assignment['status'] ?? '') !== 'published') {
        return [false, 'This assignment is not open for submissions.'];
    }
    if ($submission !== null && ($submission['status'] ?? '') === 'graded') {
        return [false, 'This submission has already been graded and can no longer be replaced.'];
    }
    $overdue = deadline_passed((string) $assignment['deadline']);
    $allowLate = get_setting('allow_late_submissions', '0') === '1';
    if ($overdue && !$allowLate) {
        return [false, 'The deadline has passed. Late submissions are not allowed.'];
    }
    if ($overdue && $allowLate) {
        return [true, 'Late submissions are currently allowed. This submission will be marked as late.'];
    }
    if ($submission !== null) {
        return [true, 'Uploading a new file replaces your current submission.'];
    }
    return [true, ''];
}

function stream_download(string $stored, string $downloadName): void
{
    $stored = basename($stored);
    if (preg_match('/^[a-f0-9]{32}\.(pdf|doc|docx|zip)$/i', $stored) !== 1) {
        render_error('File not available', 'The submitted file could not be found.', 404);
    }

    $root = realpath(UPLOAD_DIR);
    $path = realpath(UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored);
    if ($root === false || $path === false || !is_file($path)) {
        render_error('File not available', 'The submitted file could not be found.', 404);
    }
    if (!str_starts_with(normalize_path($path), normalize_path($root) . '/')) {
        render_error('File not available', 'The submitted file could not be found.', 404);
    }

    $downloadName = trim(str_replace(['"', "\r", "\n", '\\', '/'], '', $downloadName));
    if ($downloadName === '') {
        $downloadName = $stored;
    }
    $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $downloadName) ?: $stored;

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $asciiName . '"');
    header('Content-Length: ' . (string) filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

function find_assignment(int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    return fetch_one(
        'SELECT a.*, u.name AS teacher_name
         FROM assignments a
         JOIN users u ON u.id = a.teacher_id
         WHERE a.id = ?',
        [$id]
    );
}

function teacher_assignment(int $assignmentId, int $teacherId): ?array
{
    if ($assignmentId < 1) {
        return null;
    }
    return fetch_one(
        'SELECT * FROM assignments WHERE id = ? AND teacher_id = ?',
        [$assignmentId, $teacherId]
    );
}

function user_has_related_records(int $userId): bool
{
    $assignments = scalar('SELECT COUNT(*) FROM assignments WHERE teacher_id = ?', [$userId]);
    $submissions = scalar('SELECT COUNT(*) FROM submissions WHERE student_id = ?', [$userId]);
    return $assignments > 0 || $submissions > 0;
}

function is_last_active_admin(int $userId): bool
{
    $user = fetch_one('SELECT role, status FROM users WHERE id = ?', [$userId]);
    if ($user === null || $user['role'] !== 'admin' || $user['status'] !== 'active') {
        return false;
    }
    return scalar("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'") <= 1;
}
