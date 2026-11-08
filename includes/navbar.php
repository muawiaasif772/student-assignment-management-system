<?php
declare(strict_types=1);

function nav_items(): array
{
    $role = current_user()['role'] ?? '';
    if ($role === 'admin') {
        return [
            ['dashboard', 'admin/dashboard.php', 'speedometer2', 'Dashboard'],
            ['users', 'admin/users.php', 'people', 'All users'],
            ['students', 'admin/users.php?role=student', 'mortarboard', 'Students'],
            ['teachers', 'admin/users.php?role=teacher', 'person-workspace', 'Teachers'],
            ['add-user', 'admin/add_user.php', 'person-plus', 'Add user'],
            ['settings', 'admin/settings.php', 'gear', 'System information'],
        ];
    }
    if ($role === 'teacher') {
        return [
            ['dashboard', 'teacher/dashboard.php', 'speedometer2', 'Dashboard'],
            ['assignments', 'teacher/assignments.php', 'journal-text', 'My assignments'],
            ['create', 'teacher/create_assignment.php', 'plus-circle', 'Create assignment'],
        ];
    }
    if ($role === 'student') {
        return [
            ['dashboard', 'student/dashboard.php', 'speedometer2', 'Dashboard'],
            ['assignments', 'student/assignments.php', 'journal-text', 'Assignments'],
            ['submissions', 'student/submissions.php', 'inbox', 'My submissions'],
        ];
    }
    return [];
}

function render_nav_links(string $activeNav): void
{
    foreach (nav_items() as [$key, $path, $icon, $label]) {
        $active = $activeNav === $key ? ' active' : '';
        $current = $activeNav === $key ? ' aria-current="page"' : '';
        echo '<a class="nav-link-app' . $active . '" href="' . e(base_url($path)) . '"' . $current . '>';
        echo '<i class="bi bi-' . e($icon) . '"></i><span>' . e($label) . '</span></a>';
    }
}

function logout_button(string $buttonClass): string
{
    return '<form method="post" action="' . e(base_url('auth/logout.php')) . '">'
        . csrf_field()
        . '<button type="submit" class="' . e($buttonClass) . '"><i class="bi bi-box-arrow-right me-1"></i>Log out</button></form>';
}

function render_sidebar(string $activeNav): void
{
    $user = current_user();
    echo '<aside class="sidebar d-none d-lg-flex">';
    echo '<a class="sidebar-brand" href="' . e(base_url(dashboard_route())) . '"><span class="brand-mark">S</span><span><strong>' . e(APP_SHORT) . '</strong><small>Assignment portal</small></span></a>';
    if ($user !== null) {
        echo '<div class="sidebar-user"><div class="user-name">' . e((string) $user['name']) . '</div><div class="user-role">' . e(role_label((string) $user['role'])) . '</div></div>';
    }
    echo '<nav class="sidebar-nav" aria-label="Dashboard">';
    render_nav_links($activeNav);
    echo '</nav>';
    echo '<div class="sidebar-footer">' . logout_button('btn btn-outline-light w-100') . '</div>';
    echo '</aside>';
}

function render_mobile_nav(string $activeNav): void
{
    echo '<div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">';
    echo '<div class="offcanvas-header"><h2 class="offcanvas-title h5" id="mobileNavLabel">' . e(APP_SHORT) . '</h2>';
    echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>';
    echo '<div class="offcanvas-body d-flex flex-column"><nav aria-label="Dashboard">';
    render_nav_links($activeNav);
    echo '</nav><div class="mt-auto pt-3">' . logout_button('btn btn-outline-light w-100') . '</div></div></div>';
}
