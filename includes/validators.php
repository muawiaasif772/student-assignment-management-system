<?php
declare(strict_types=1);

function validate_new_password(string $password, string $confirm): array
{
    $errors = [];
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password must be 72 characters or fewer.';
    } elseif (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
        $errors['password'] = 'Password must include at least one letter and one number.';
    }

    if ($confirm === '') {
        $errors['confirm_password'] = 'Please confirm the password.';
    } elseif ($password !== '' && $password !== $confirm) {
        $errors['confirm_password'] = 'Password confirmation does not match.';
    }

    return $errors;
}

function validate_profile(array $source, ?int $exceptUserId, bool $passwordRequired): array
{
    $name = preg_replace('/\s+/u', ' ', trim((string) ($source['name'] ?? ''))) ?? '';
    $email = strtolower(trim((string) ($source['email'] ?? '')));
    $password = (string) ($source['password'] ?? '');
    $confirm = (string) ($source['confirm_password'] ?? '');
    $errors = [];

    if ($name === '') {
        $errors['name'] = 'Name is required.';
    } elseif (str_length($name) < 2 || str_length($name) > 100) {
        $errors['name'] = 'Name must be between 2 and 100 characters.';
    } elseif (preg_match("/^[\\p{L}\\p{M}\\s.'-]{2,100}$/u", $name) !== 1) {
        $errors['name'] = 'Name can contain letters, spaces, apostrophes, and hyphens only.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (str_length($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (email_taken($email, $exceptUserId)) {
        $errors['email'] = 'An account with this email address already exists.';
    }

    if ($passwordRequired || $password !== '' || $confirm !== '') {
        $errors = array_merge($errors, validate_new_password($password, $confirm));
    }

    return [
        'errors' => $errors,
        'data' => [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ],
    ];
}

function parse_deadline(string $raw): ?DateTime
{
    $raw = trim($raw);
    foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
        $date = DateTime::createFromFormat($format, $raw);
        $info = DateTime::getLastErrors();
        $clean = $info === false || (($info['warning_count'] ?? 0) === 0 && ($info['error_count'] ?? 0) === 0);
        if ($date instanceof DateTime && $clean) {
            return $date;
        }
    }
    return null;
}

function validate_assignment_input(array $source, bool $requireFuture): array
{
    $title = trim((string) ($source['title'] ?? ''));
    $description = trim((string) ($source['description'] ?? ''));
    $deadlineRaw = trim((string) ($source['deadline'] ?? ''));
    $maxRaw = trim((string) ($source['max_marks'] ?? ''));
    $errors = [];

    if ($title === '') {
        $errors['title'] = 'Title is required.';
    } elseif (str_length($title) < 3 || str_length($title) > 200) {
        $errors['title'] = 'Title must be between 3 and 200 characters.';
    }

    if ($description === '') {
        $errors['description'] = 'Description is required.';
    } elseif (str_length($description) < 10) {
        $errors['description'] = 'Description must be at least 10 characters.';
    } elseif (str_length($description) > 10000) {
        $errors['description'] = 'Description is too long.';
    }

    $deadlineSql = null;
    if ($deadlineRaw === '') {
        $errors['deadline'] = 'Deadline is required.';
    } else {
        $deadline = parse_deadline($deadlineRaw);
        if ($deadline === null) {
            $errors['deadline'] = 'Enter a valid deadline.';
        } else {
            $deadlineSql = $deadline->format('Y-m-d H:i:s');
            if ($requireFuture && $deadline->getTimestamp() <= time()) {
                $errors['deadline'] = 'Deadline must be in the future.';
            }
        }
    }

    $maxMarks = null;
    if ($maxRaw === '' || !is_numeric($maxRaw)) {
        $errors['max_marks'] = 'Maximum marks must be a number greater than zero.';
    } else {
        $maxMarks = round((float) $maxRaw, 2);
        if ($maxMarks <= 0 || $maxMarks > 1000) {
            $errors['max_marks'] = 'Maximum marks must be greater than zero and no more than 1000.';
        }
    }

    return [
        'errors' => $errors,
        'data' => [
            'title' => $title,
            'description' => $description,
            'deadline' => $deadlineSql,
            'max_marks' => $maxMarks,
        ],
    ];
}

function validate_grade(string $rawMarks, string $feedback, float $maxMarks): array
{
    $errors = [];
    $marks = null;
    if (trim($rawMarks) === '' || !is_numeric($rawMarks)) {
        $errors['marks'] = 'Marks are required and must be a number.';
    } else {
        $marks = round((float) $rawMarks, 2);
        if ($marks < 0) {
            $errors['marks'] = 'Marks cannot be negative.';
        } elseif ((int) round($marks * 100) > (int) round($maxMarks * 100)) {
            $errors['marks'] = 'Marks cannot exceed the maximum marks (' . format_marks($maxMarks) . ').';
        }
    }

    $feedback = trim($feedback);
    if ($feedback === '') {
        $errors['feedback'] = 'Feedback is required.';
    } elseif (str_length($feedback) < 2) {
        $errors['feedback'] = 'Feedback must be at least 2 characters.';
    } elseif (str_length($feedback) > 5000) {
        $errors['feedback'] = 'Feedback must be 5000 characters or fewer.';
    }

    return ['errors' => $errors, 'marks' => $marks, 'feedback' => $feedback];
}

function validate_settings(array $source): array
{
    $siteName = trim((string) ($source['site_name'] ?? ''));
    $institution = trim((string) ($source['institution_name'] ?? ''));
    $academicYear = trim((string) ($source['academic_year'] ?? ''));
    $contact = strtolower(trim((string) ($source['contact_email'] ?? '')));
    $announcement = trim((string) ($source['announcement'] ?? ''));
    $errors = [];

    if ($siteName === '' || str_length($siteName) > 150) {
        $errors['site_name'] = 'System name is required and must be 150 characters or fewer.';
    }
    if ($institution === '' || str_length($institution) > 150) {
        $errors['institution_name'] = 'Institution name is required and must be 150 characters or fewer.';
    }
    if ($academicYear === '' || str_length($academicYear) > 40) {
        $errors['academic_year'] = 'Academic session is required and must be 40 characters or fewer.';
    }
    if ($contact === '' || str_length($contact) > 150 || filter_var($contact, FILTER_VALIDATE_EMAIL) === false) {
        $errors['contact_email'] = 'Enter a valid contact email address.';
    }
    if (str_length($announcement) > 1000) {
        $errors['announcement'] = 'Announcement must be 1000 characters or fewer.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'site_name' => $siteName,
            'institution_name' => $institution,
            'academic_year' => $academicYear,
            'contact_email' => $contact,
            'announcement' => $announcement,
            'student_registration_enabled' => isset($source['student_registration_enabled']) ? '1' : '0',
            'teacher_registration_enabled' => isset($source['teacher_registration_enabled']) ? '1' : '0',
            'allow_late_submissions' => isset($source['allow_late_submissions']) ? '1' : '0',
        ],
    ];
}
