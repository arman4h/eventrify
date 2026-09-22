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

function truncate(?string $text, int $width = 50, string $suffix = '…'): string
{
    $text = (string) ($text ?? '');
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $width, $suffix, 'UTF-8');
    }

    preg_match_all('/./us', $text, $m);
    $chars = $m[0];
    if (count($chars) <= $width) {
        return $text;
    }

    return implode('', array_slice($chars, 0, max(0, $width - 1))) . $suffix;
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

function clubStatus(): ?string
{
    return $_SESSION['user']['club_status'] ?? null;
}

function requireApprovedClub(): void
{
    requireClubUser();

    $status  = null;
    $clubId = (int) (currentUser()['club_id'] ?? 0);
    if ($clubId > 0 && isset($GLOBALS['db'])) {
        $result = $GLOBALS['db']->query("SELECT status FROM clubs WHERE club_id = " . $clubId);
        $row    = $result ? $result->fetch_assoc() : null;
        $status = $row['status'] ?? null;
    }

    $status = $status ?? clubStatus() ?? 'pending';
    if ($status !== 'approved') {
        $_SESSION['user']['club_status'] = $status;
        redirect('/club');
    }

    $_SESSION['user']['club_status'] = 'approved';
}

/**
 * Whether the current club user can access a dashboard section.
 * Owners and admins always have full access. Executives with an
 * 'all' access scope have full access; executives with a 'limited'
 * scope may only open pages granted via executive_permissions.
 */
function clubCanAccess(string $pageKey): bool
{
    if (!isClubUser()) {
        return false;
    }
    $me = currentUser();
    $role = $me['role'] ?? '';
    if ($role === 'owner' || $role === 'admin') {
        return true;
    }
    if ($role !== 'executive') {
        return false;
    }
    if (($me['access_scope'] ?? 'all') !== 'limited') {
        return true;
    }
    $db = $GLOBALS['db'];
    $stmt = $db->prepare("
        SELECT 1
        FROM executive_permissions ep
        JOIN club_permission_pages p ON p.page_id = ep.page_id
        WHERE ep.club_user_id = ? AND p.page_key = ?
        LIMIT 1
    ");
    $stmt->bind_param('is', $me['id'], $pageKey);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

/**
 * Require an approved club membership AND permission for a dashboard section.
 */
function requireClubAccess(string $pageKey): void
{
    requireApprovedClub();
    if (!clubCanAccess($pageKey)) {
        $_SESSION['flash']['error'] = 'You do not have access to this section.';
        redirect('/club');
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

/**
 * Return true when another active (non-cancelled) registration for the same
 * event already submitted the same value for a labelled registration field
 * (e.g. Email, Phone, Student ID). Field labels are matched loosely so
 * "Phone", "Phone Number", "Phone Number ?" etc. are all recognised.
 */
function eventRegistrationValueTaken(int $eventId, string $fieldLabel, string $value): bool
{
    if ($value === '') {
        return false;
    }
    $db = $GLOBALS['db'];

    if ($fieldLabel === 'email') {
        $stmt = $db->prepare("
            SELECT 1
            FROM event_registrations er
            JOIN registration_field_responses rfr ON rfr.registration_id = er.registration_id
            JOIN event_registration_fields erf ON erf.field_id = rfr.field_id
            WHERE er.event_id = ?
              AND er.status NOT IN ('cancelled')
              AND erf.field_label = 'Email'
              AND LOWER(TRIM(rfr.response_value)) = LOWER(TRIM(?))
            LIMIT 1
        ");
        $stmt->bind_param('is', $eventId, $value);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() !== null;
    }

    if ($fieldLabel === 'phone') {
        $candidates = function (string $value): array {
            $digits = preg_replace('/[^0-9]/', '', $value);
            $out = [$digits];
            if (strlen($digits) === 13 && strpos($digits, '880') === 0) {
                $out[] = substr($digits, 3); // +8801... -> 01...
            }
            if (strlen($digits) === 12 && strpos($digits, '88') === 0) {
                $out[] = substr($digits, 2);
            }
            if (strlen($digits) === 11 && $digits[0] === '0') {
                $out[] = substr($digits, 1); // 01... -> 1...
            }
            return array_unique(array_filter($out));
        };
        $needle = $candidates($value);
        if (empty($needle)) {
            return false;
        }
        $stmt = $db->prepare("
            SELECT rfr.response_value AS phone
            FROM event_registrations er
            JOIN registration_field_responses rfr ON rfr.registration_id = er.registration_id
            JOIN event_registration_fields erf ON erf.field_id = rfr.field_id
            WHERE er.event_id = ?
              AND er.status NOT IN ('cancelled')
              AND UPPER(erf.field_label) LIKE 'PHONE%'
        ");
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        foreach ($stmt->get_result() as $row) {
            if (array_intersect($needle, $candidates((string) $row['phone']))) {
                return true;
            }
        }
        return false;
    }

    return false;
}

/**
 * Attach guest (anonymous) registrations made with the given student email to
 * that student's account, so the registration shows up on their dashboard.
 * Returns the number of registrations linked.
 */
function linkGuestRegistrations(int $studentId, string $email): int
{
    if ($studentId <= 0 || $email === '') {
        return 0;
    }
    $db = $GLOBALS['db'];

    $stmt = $db->prepare("
        SELECT er.registration_id, er.event_id
        FROM event_registrations er
        JOIN registration_field_responses rfr ON rfr.registration_id = er.registration_id
        JOIN event_registration_fields erf ON erf.field_id = rfr.field_id
        WHERE erf.field_label = 'Email'
          AND LOWER(TRIM(rfr.response_value)) = LOWER(TRIM(?))
          AND er.student_id IS NULL
          AND er.status NOT IN ('cancelled')
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $rows = $stmt->get_result();

    $linked = 0;
    while ($row = $rows->fetch_assoc()) {
        $regId = (int) $row['registration_id'];
        $evId  = (int) $row['event_id'];

        $dup = $db->prepare("SELECT 1 FROM event_registrations WHERE event_id = ? AND student_id = ? LIMIT 1");
        $dup->bind_param('ii', $evId, $studentId);
        $dup->execute();

        if ($dup->get_result()->fetch_assoc()) {
            $cancel = $db->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE registration_id = ?");
            $cancel->bind_param('i', $regId);
            $cancel->execute();
            continue;
        }

        $upd = $db->prepare("UPDATE event_registrations SET student_id = ? WHERE registration_id = ?");
        $upd->bind_param('ii', $studentId, $regId);
        $upd->execute();
        $linked++;
    }
    return $linked;
}

/**
 * Fixed room time slots used for room requests.
 */
function roomTimeSlots(): array
{
    return [
        ['start' => '08:30', 'end' => '09:50', 'label' => '8:30 - 9:50'],
        ['start' => '09:51', 'end' => '11:10', 'label' => '9:51 - 11:10'],
        ['start' => '11:11', 'end' => '12:30', 'label' => '11:11 - 12:30'],
        ['start' => '12:31', 'end' => '13:40', 'label' => '12:31 - 1:40'],
        ['start' => '13:50', 'end' => '15:10', 'label' => '1:50 - 3:10'],
        ['start' => '15:11', 'end' => '16:30', 'label' => '3:11 - 4:30'],
    ];
}

/**
 * Display label for a room request time slot (falls back to raw times).
 */
function roomSlotLabel(string $start, string $end): string
{
    $s = substr($start, 0, 5);
    $e = substr($end, 0, 5);
    foreach (roomTimeSlots() as $slot) {
        if ($slot['start'] === $s && $slot['end'] === $e) {
            return $slot['label'];
        }
    }
    return $s . ' - ' . $e;
}
