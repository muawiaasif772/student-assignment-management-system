<?php
declare(strict_types=1);

function forget_current_user(): void
{
    $GLOBALS['sams_user_resolved'] = false;
    unset($GLOBALS['sams_user']);
}

function current_user(): ?array
{
    if (!empty($GLOBALS['sams_user_resolved'])) {
        return $GLOBALS['sams_user'] ?? null;
    }
    $GLOBALS['sams_user_resolved'] = true;
    $GLOBALS['sams_user'] = null;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $user = fetch_one(
        'SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1',
        [(int) $_SESSION['user_id']]
    );
    if ($user === null || $user['status'] !== 'active') {
        end_session();
        $GLOBALS['sams_user_resolved'] = true;
        $GLOBALS['sams_user'] = null;
        return null;
    }

    $GLOBALS['sams_user'] = $user;
    return $user;
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'teacher' => 'Teacher',
        'student' => 'Student',
        default => 'User',
    };
}

function dashboard_route(): string
{
    return match (current_user()['role'] ?? '') {
        'admin' => 'admin/dashboard.php',
        'teacher' => 'teacher/dashboard.php',
        'student' => 'student/dashboard.php',
        default => 'auth/login.php',
    };
}

function end_session(?string $flashType = null, ?string $message = null): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
    forget_current_user();
    if ($flashType !== null && $message !== null) {
        flash($flashType, $message);
    }
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (is_string($uri) && $uri !== '') {
            $_SESSION['intended'] = $uri;
        }
        flash('warning', 'Please log in to continue.');
        redirect('auth/login.php');
    }

    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        end_session('warning', 'Your session has expired. Please log in again.');
        redirect('auth/login.php');
    }

    $_SESSION['last_activity'] = time();
    if (current_user() === null) {
        flash('danger', 'Your account is not available. Please log in again.');
        redirect('auth/login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if ($user === null || $user['role'] !== $role) {
        render_error('Unauthorized', 'You do not have permission to access this page.', 403);
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['_created'] = time();
    unset($_SESSION['csrf_token']);
    forget_current_user();
}

function take_intended_url(string $role): ?string
{
    $uri = $_SESSION['intended'] ?? null;
    unset($_SESSION['intended']);
    if (!is_string($uri) || $uri === '') {
        return null;
    }

    $parts = parse_url($uri);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return null;
    }
    $path = (string) ($parts['path'] ?? '');
    if ($path === '' || !str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, '\\')) {
        return null;
    }
    if (preg_match('/\.php$/', $path) !== 1) {
        return null;
    }

    $base = base_url();
    if ($base !== '' && $path !== $base && !str_starts_with($path, $base . '/')) {
        return null;
    }

    $relative = $base !== '' && str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    if (str_starts_with($relative, '/admin/') && $role !== 'admin') {
        return null;
    }
    if (str_starts_with($relative, '/teacher/') && $role !== 'teacher') {
        return null;
    }
    if (str_starts_with($relative, '/student/') && $role !== 'student') {
        return null;
    }
    if (str_starts_with($relative, '/auth/')) {
        return null;
    }

    return $uri;
}

function login_is_locked(string $email): bool
{
    return scalar(
        'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)',
        [$email]
    ) >= LOGIN_MAX_ATTEMPTS;
}

function record_login_failure(string $email): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!is_string($ip) || strlen($ip) > 45) {
        $ip = '0.0.0.0';
    }
    db()->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)')->execute([$email, $ip]);
    db()->prepare('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)')->execute();
}

function clear_login_failures(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);
}

function attempt_login(string $email, string $password): array
{
    if (login_is_locked($email)) {
        return [
            'ok' => false,
            'error' => 'Too many failed login attempts. Please wait 15 minutes and try again.',
            'user' => null,
        ];
    }

    $user = fetch_one(
        'SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1',
        [$email]
    );
    $valid = $user !== null && password_verify($password, (string) $user['password']);
    if (!$valid) {
        record_login_failure($email);
        return ['ok' => false, 'error' => 'Invalid email or password.', 'user' => null];
    }

    if ($user['status'] !== 'active') {
        return [
            'ok' => false,
            'error' => 'Your account has been deactivated. Please contact the administrator.',
            'user' => null,
        ];
    }

    if (password_needs_rehash((string) $user['password'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, (int) $user['id']]);
    }

    clear_login_failures($email);
    return ['ok' => true, 'error' => '', 'user' => $user];
}
