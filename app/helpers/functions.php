<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/icons.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function old(string $key, $default = ''): ?string
{
    return $_SESSION['old'][$key] ?? $default;
}

function setOld(array $data): void
{
    $_SESSION['old'] = $data;
}

function flash(string $key, ?string $value = null)
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    if (isset($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    return null;
}

function clearOld(): void
{
    unset($_SESSION['old']);
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function post(string $key, $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

function get(string $key, $default = ''): string
{
    return trim($_GET[$key] ?? $default);
}

function abort(int $code = 404, string $message = 'Not Found'): void
{
    http_response_code($code);
    echo "<h1>$code - $message</h1>";
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function formatDate(string $date, string $format = 'M d, Y'): string
{
    return date($format, strtotime($date));
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

function requireStudent(): void
{
    requireAuth();
    if (!isStudent()) {
        redirect('/');
    }
}

function requireClubUser(): void
{
    requireAuth();
    if (!isClubUser()) {
        redirect('/');
    }
}

function requireSystemAdmin(): void
{
    requireAuth();
    if (!isSystemAdmin()) {
        redirect('/admin/login');
    }
}

function requireAdmin(): void
{
    requireSystemAdmin();
}

function defaultRegistrationFields(): array
{
    return [
        ['label' => 'Full Name', 'type' => 'text', 'options' => '', 'required' => 1],
        ['label' => 'Email', 'type' => 'email', 'options' => '', 'required' => 1],
        ['label' => 'Student ID', 'type' => 'text', 'options' => '', 'required' => 1],
        ['label' => 'Department', 'type' => 'dropdown', 'options' => 'CSE,EEE,DS,English,BBA,EDS,Economics', 'required' => 1],
    ];
}

function departmentOptions(): array
{
    return ['CSE', 'EEE', 'DS', 'English', 'BBA', 'EDS', 'Economics'];
}

function departmentIdPrefixes(): array
{
    return [
        'CSE' => '011',
        'EEE' => '012',
        'DS' => '015',
        'English' => '211',
        'BBA' => '215',
        'EDS' => '133',
    ];
}

function departmentCode(string $dept): string
{
    $map = [
        'Computer Science & Engineering' => 'CSE',
        'Electrical & Electronic Engineering' => 'EEE',
        'Data Science' => 'DS',
        'English' => 'English',
        'Business Administration' => 'BBA',
        'Economics' => 'Economics',
        'EDS' => 'EDS',
    ];
    return $map[$dept] ?? (in_array($dept, departmentOptions(), true) ? $dept : '');
}

function insertRegistrationFields(mysqli $db, int $eventId, array $posted): void
{
    $fieldStmt = $db->prepare("INSERT INTO event_registration_fields (event_id, field_label, field_type, field_options, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");

    $order = 1;
    foreach (defaultRegistrationFields() as $df) {
        $fieldStmt->bind_param('isssii', $eventId, $df['label'], $df['type'], $df['options'], $df['required'], $order);
        $fieldStmt->execute();
        $order++;
    }

    $qLabels = $posted['label'] ?? [];
    $qTypes = $posted['type'] ?? [];
    $qRequired = $posted['required'] ?? [];
    $qOptions = $posted['options'] ?? [];

    $typeMap = [
        'short_text' => 'text',
        'long_text' => 'text',
        'number' => 'number',
        'email' => 'email',
        'dropdown' => 'dropdown',
        'radio' => 'dropdown',
        'checkbox' => 'checkbox',
        'date' => 'date',
    ];

    for ($i = 0; $i < count($qLabels); $i++) {
        $label = trim($qLabels[$i] ?? '');
        if ($label === '') continue;
        $type = $typeMap[trim($qTypes[$i] ?? '')] ?? 'text';
        $required = isset($qRequired[$i]) ? 1 : 0;
        $options = trim($qOptions[$i] ?? '');
        $fieldStmt->bind_param('isssii', $eventId, $label, $type, $options, $required, $order);
        $fieldStmt->execute();
        $order++;
    }
}
