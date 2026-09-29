<?php

/**
 * The issue-reporting segment.
 *
 * A student who has a problem with an event follows a two-step path:
 *
 *   1. Contact the club directly  — the club owns the event, so most issues
 *      are fixed fastest by the organiser. `clubContactForReport()` returns
 *      the club's official email, website and social link.
 *   2. Escalate to the system administrator — if the club does not respond
 *      or cannot help, the student files a report that lands in the admin
 *      queue at `/admin/reports?view=queue` and is written to the `reports`
 *      table.
 */

const REPORT_MAX_OPEN_PER_STUDENT = 5;
const REPORT_SUBJECT_MIN         = 5;
const REPORT_SUBJECT_MAX         = 150;
const REPORT_DESCRIPTION_MIN     = 20;
const REPORT_DESCRIPTION_MAX     = 5000;

/**
 * Quick-pick reasons. These prefill the subject so the student does not have
 * to invent a title; the free-text description is still required.
 */
function reportReasonChips(): array
{
    return [
        'Event cancelled or rescheduled without notice',
        'Event details do not match reality',
        'Venue was not as advertised',
        'Registration or waitlist problem',
        'Unprofessional behaviour by an organizer',
        'Safety or conduct concern',
        'Duplicate or incorrect entry',
        'Other',
    ];
}

function reportStatusBadge(string $status): string
{
    return match ($status) {
        'open'         => 'badge-danger',
        'under_review' => 'badge-warning',
        'resolved'     => 'badge-success',
        'dismissed'    => 'badge-neutral',
        default        => 'badge-neutral',
    };
}

function reportStatusLabel(string $status): string
{
    return match ($status) {
        'open'         => 'Open',
        'under_review' => 'Under review',
        'resolved'     => 'Resolved',
        'dismissed'    => 'Dismissed',
        default        => ucfirst($status),
    };
}

/**
 * The next statuses an admin may move a report to, keyed by current status.
 * Keeping this in one place stops the admin form from offering a transition
 * that makes no sense (e.g. re-opening a resolved report).
 */
function reportStatusTransitions(): array
{
    return [
        'open'         => ['under_review', 'resolved', 'dismissed'],
        'under_review' => ['resolved', 'dismissed', 'open'],
        'resolved'     => ['open'],
        'dismissed'    => ['open'],
    ];
}

function reportAllStatuses(): array
{
    return ['open', 'under_review', 'resolved', 'dismissed'];
}

/**
 * Contact details for the club behind an event, so step 1 of the escalation
 * path has something actionable in it.
 *
 * @return array{name:string, email:string, website:string, facebook:string, club_id:int}|null
 */
function clubContactForReport(int $eventId = 0, int $clubId = 0): ?array
{
    $db = $GLOBALS['db'];

    if ($eventId > 0) {
        $stmt = $db->prepare("
            SELECT c.club_id, c.club_name, c.official_email, c.website, c.facebook
            FROM events e
            JOIN clubs c ON c.club_id = e.club_id
            WHERE e.event_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
    } else {
        $stmt = $db->prepare("
            SELECT club_id, club_name, official_email, website, facebook
            FROM clubs
            WHERE club_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $clubId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
    }

    if (!$row) {
        return null;
    }

    return [
        'club_id'  => (int) $row['club_id'],
        'name'     => (string) $row['club_name'],
        'email'    => (string) ($row['official_email'] ?? ''),
        'website'  => (string) ($row['website'] ?? ''),
        'facebook' => (string) ($row['facebook'] ?? ''),
    ];
}

/**
 * A mailto: link with the subject and body pre-filled.
 */
function reportMailtoLink(string $email, string $subject = '', string $body = ''): string
{
    $params = [];
    if ($subject !== '') {
        $params['subject'] = $subject;
    }
    if ($body !== '') {
        $params['body'] = $body;
    }

    $query = count($params) > 0 ? '?' . http_build_query($params) : '';

    return 'mailto:' . $email . $query;
}

/**
 * Events the student can pick when filing a report: the ones they are
 * registered for first, then everything else that is still visible.
 *
 * @return array<int, array{event_id:int, title:string, club_name:string, registered:bool, start_time:string}>
 */
function reportableEventsForStudent(int $studentId): array
{
    $db = $db ?? $GLOBALS['db'];

    $stmt = $db->prepare("
        SELECT e.event_id, e.title, e.start_time, e.status, c.club_name,
               EXISTS(
                   SELECT 1 FROM event_registrations er
                   WHERE er.event_id = e.event_id
                     AND er.student_id = ?
                     AND er.status NOT IN ('cancelled')
               ) AS registered
        FROM events e
        JOIN clubs c ON c.club_id = e.club_id
        WHERE e.status IN ('published', 'completed')
          AND c.status = 'approved'
        ORDER BY registered DESC, e.start_time DESC
        LIMIT 300
    ");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();

    $rows = [];
    foreach ($stmt->get_result() as $row) {
        $rows[] = [
            'event_id'   => (int) $row['event_id'],
            'title'      => (string) $row['title'],
            'club_name'  => (string) $row['club_name'],
            'registered' => (int) $row['registered'] === 1,
            'start_time' => (string) $row['start_time'],
        ];
    }

    return $rows;
}

/**
 * Approved clubs a student can name in a report.
 *
 * @return array<int, array{club_id:int, club_name:string}>
 */
function reportableClubs(): array
{
    $rows = $GLOBALS['db']->query("
        SELECT club_id, club_name
        FROM clubs
        WHERE status = 'approved'
        ORDER BY club_name
    ")->fetch_all(MYSQLI_ASSOC) ?? [];

    return array_map(static function (array $row): array {
        return ['club_id' => (int) $row['club_id'], 'club_name' => (string) $row['club_name']];
    }, $rows);
}

/**
 * How many unresolved reports this student already has open.
 */
function openReportCountForStudent(int $studentId): int
{
    $stmt = $GLOBALS['db']->prepare("
        SELECT COUNT(*) AS c
        FROM reports
        WHERE reporter_id = ? AND status IN ('open', 'under_review')
    ");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();

    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

/**
 * True when the student already filed the same thing for the same target.
 */
function studentHasDuplicateReport(int $studentId, string $subject, int $eventId, int $clubId): bool
{
    $stmt = $GLOBALS['db']->prepare("
        SELECT 1
        FROM reports
        WHERE reporter_id = ?
          AND LOWER(TRIM(subject)) = LOWER(TRIM(?))
          AND COALESCE(reported_event_id, 0) = ?
          AND COALESCE(reported_club_id, 0) = ?
          AND status IN ('open', 'under_review')
        LIMIT 1
    ");
    $stmt->bind_param('isii', $studentId, $subject, $eventId, $clubId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() !== null;
}

/**
 * Validate a report submission. Returns a list of human-readable problems.
 *
 * @return array<int, string>
 */
function validateReportSubmission(array $input, int $studentId, int $eventId, int $clubId): array
{
    $errors = [];

    $subject     = trim((string) ($input['subject'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));

    vAdd($errors, vRequired($subject, 'Subject'));
    vAdd($errors, vMinLen($subject, REPORT_SUBJECT_MIN, 'Subject'));
    vAdd($errors, vMaxLen($subject, REPORT_SUBJECT_MAX, 'Subject'));

    vAdd($errors, vRequired($description, 'Description'));
    vAdd($errors, vMinLen($description, REPORT_DESCRIPTION_MIN, 'Description'));
    vAdd($errors, vMaxLen($description, REPORT_DESCRIPTION_MAX, 'Description'));

    if ($eventId === 0 && $clubId === 0) {
        $errors[] = 'Tell us what the problem is about — pick the event or the club involved.';
    }

    if (openReportCountForStudent($studentId) >= REPORT_MAX_OPEN_PER_STUDENT) {
        $errors[] = 'You already have ' . REPORT_MAX_OPEN_PER_STUDENT . ' reports waiting for a response. '
            . 'Please wait for the administration to reply before filing more.';
    }

    if ($subject !== '' && $description !== '' && studentHasDuplicateReport($studentId, $subject, $eventId, $clubId)) {
        $errors[] = 'You have already sent this report and it is still being reviewed.';
    }

    return array_values(array_unique($errors));
}

/**
 * Store a validated report. `reported_club_id` is derived from the event when
 * the student named an event but no club, so the club sees the issue too.
 */
function storeReport(int $studentId, int $eventId, int $clubId, string $subject, string $description): int
{
    $db = $GLOBALS['db'];

    if ($eventId > 0 && $clubId === 0) {
        $stmt = $db->prepare("SELECT club_id FROM events WHERE event_id = ?");
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $clubId = (int) ($stmt->get_result()->fetch_assoc()['club_id'] ?? 0);
    }

    $eventId = $eventId > 0 ? $eventId : null;
    $clubId  = $clubId > 0 ? $clubId : null;

    $stmt = $db->prepare("
        INSERT INTO reports (reporter_id, reported_club_id, reported_event_id, subject, description, status)
        VALUES (?, ?, ?, ?, ?, 'open')
    ");
    $stmt->bind_param('iisss', $studentId, $clubId, $eventId, $subject, $description);
    $stmt->execute();

    return (int) $db->insert_id;
}

/**
 * Reports relevant to a club: named directly, or about one of its events.
 *
 * @return array<int, array<string,mixed>>
 */
function reportsForClub(int $clubId, int $limit = 50): array
{
    $stmt = $GLOBALS['db']->prepare("
        SELECT r.report_id, r.subject, r.description, r.status, r.created_at, r.resolved_at,
               r.reported_event_id, s.full_name AS reporter_name, s.university_id AS reporter_id_text,
               e.title AS event_title
        FROM reports r
        JOIN students s ON s.student_id = r.reporter_id
        LEFT JOIN events e ON e.event_id = r.reported_event_id
        WHERE r.reported_club_id = ?
           OR r.reported_event_id IN (SELECT event_id FROM events WHERE club_id = ?)
        ORDER BY
            FIELD(r.status, 'open', 'under_review', 'resolved', 'dismissed'),
            r.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param('iii', $clubId, $clubId, $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
}
