<?php

/**
 * Admin-only event archive.
 *
 * A system admin may remove any event from the platform, but a hard DELETE
 * would cascade away the registrations, the custom form fields and the answers
 * with it — and a moderation decision that leaves no record is worthless. So
 * the row is copied into deleted_events first, the people on the list are
 * snapshotted into deleted_event_registrations, and only then is the live row
 * removed.
 *
 * Nothing outside the admin dashboard reads these two tables. Clubs and
 * students see the event simply disappear, exactly as before.
 */

/**
 * Registration states that still hold a place on an event.
 *
 * 'cancelled' has given the place up, and 'attended'/'no_show' are outcomes of
 * an event that has already run, so none of them should stand in the way of
 * clearing out the archive. 'registered' and 'waitlisted' are the states where
 * somebody is still counting on showing up, so they do.
 */
const LIVE_REGISTRATION_STATUSES = ['registered', 'waitlisted'];

/**
 * How many people are still holding a place on an event.
 */
function liveRegistrationCount(mysqli $db, int $eventId): int
{
    // The statuses are a fixed list, never user input, so they are quoted into
    // the statement rather than bound. Binding them would mean passing them
    // through an 'i' type alongside the event id, and mysqli would then compare
    // status against 0 and report every event as free to delete.
    $quoted = [];
    foreach (LIVE_REGISTRATION_STATUSES as $status) {
        $quoted[] = "'" . $db->real_escape_string($status) . "'";
    }

    $stmt = $db->prepare(
        "SELECT COUNT(*) AS n FROM event_registrations
         WHERE event_id = ? AND status IN (" . implode(', ', $quoted) . ')'
    );
    $stmt->bind_param('i', $eventId);
    $stmt->execute();

    return (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 0);
}

/**
 * Why an event cannot be removed right now, or null when it can.
 */
function eventDeleteBlocker(mysqli $db, array $event): ?string
{
    $live = liveRegistrationCount($db, (int) $event['event_id']);

    if ($live < 1) {
        return null;
    }

    return sprintf(
        '%d %s still %s a place on this event. Cancel %s first, then delete the event.',
        $live,
        $live === 1 ? 'person' : 'people',
        $live === 1 ? 'holds' : 'hold',
        $live === 1 ? 'that registration' : 'those registrations'
    );
}

/**
 * Copy an event and its registration list into the archive, then delete the
 * live row. The whole thing runs in one transaction so a failure part way
 * through cannot leave an event that is both archived and still published.
 *
 * @return array{ok: bool, message: string}
 */
function archiveAndDeleteEvent(mysqli $db, int $eventId, int $adminId, string $reason = ''): array
{
    $stmt = $db->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();

    if (!$event) {
        return ['ok' => false, 'message' => 'That event no longer exists.'];
    }

    // Re-checked here, not only on the confirmation screen: the form is
    // reachable by URL, and somebody can register between the two requests.
    $blocker = eventDeleteBlocker($db, $event);

    if ($blocker !== null) {
        return ['ok' => false, 'message' => 'This event cannot be deleted yet. ' . $blocker];
    }

    $adminName = (string) (currentUser()['name'] ?? 'System administrator');
    $reason    = trim($reason);

    $totalStmt = $db->prepare("SELECT COUNT(*) AS n FROM event_registrations WHERE event_id = ?");
    $totalStmt->bind_param('i', $eventId);
    $totalStmt->execute();
    $totalRegs = (int) ($totalStmt->get_result()->fetch_assoc()['n'] ?? 0);

    $clubId   = (int) $event['club_id'];
    $capacity = (int) $event['capacity'];

    $clubStmt = $db->prepare("SELECT club_name FROM clubs WHERE club_id = ?");
    $clubStmt->bind_param('i', $clubId);
    $clubStmt->execute();
    $clubName = (string) ($clubStmt->get_result()->fetch_assoc()['club_name'] ?? '');

    // bind_param() takes its values by reference, so every one of them has to
    // be a plain variable — a cast expression would be a fatal error.
    $archivedEventId = (int) $event['event_id'];
    $createdBy       = (int) $event['created_by'];
    $description     = $event['description'];
    $category        = $event['category'];
    $poster          = $event['poster'];
    $venue           = $event['venue'];
    $startTime       = $event['start_time'];
    $endTime         = $event['end_time'];
    $deadline        = $event['registration_deadline'];
    $eventStatus     = $event['status'];
    $eventTitle      = $event['title'];
    $createdAt       = $event['created_at'];

    $db->begin_transaction();

    try {
        $archive = $db->prepare("
            INSERT INTO deleted_events
                (event_id, club_id, club_name, title, description, category, poster,
                 venue, start_time, end_time, registration_deadline, capacity, status,
                 created_by, created_at, registration_count,
                 deleted_by, deleted_by_name, delete_reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        // One type letter per column, in this exact order:
        //   i event_id, i club_id, s club_name, s title, s description, s category,
        //   s poster, s venue, s start_time, s end_time, s registration_deadline,
        //   i capacity, s status, i created_by, s created_at, i registration_count,
        //   i deleted_by, s deleted_by_name, s delete_reason
        $archive->bind_param(
            'iisssssssssisisiiss',
            $archivedEventId,
            $clubId,
            $clubName,
            $eventTitle,
            $description,
            $category,
            $poster,
            $venue,
            $startTime,
            $endTime,
            $deadline,
            $capacity,
            $eventStatus,
            $createdBy,
            $createdAt,
            $totalRegs,
            $adminId,
            $adminName,
            $reason
        );
        $archive->execute();

        $archiveId = (int) $db->insert_id;

        // The student columns are resolved in the INSERT so a guest row and a
        // student row are archived by the same statement.
        $db->query("
            INSERT INTO deleted_event_registrations
                (deleted_event_id, registration_id, student_id, student_name,
                 university_id, student_email, guest_name, guest_student_id,
                 is_walkin, status, waitlist_position, registered_at,
                 checked_in_at, check_in_method)
            SELECT $archiveId, er.registration_id, er.student_id, s.full_name,
                   s.university_id, s.email, er.guest_name, er.guest_student_id,
                   er.is_walkin, er.status, er.waitlist_position, er.registered_at,
                   er.checked_in_at, er.check_in_method
            FROM event_registrations er
            LEFT JOIN students s ON s.student_id = er.student_id
            WHERE er.event_id = $eventId
        ");

        // Cascades away event_registration_fields, event_registrations and
        // registration_field_responses, and nulls room_requests.event_id.
        $delete = $db->prepare("DELETE FROM events WHERE event_id = ?");
        $delete->bind_param('i', $eventId);
        $delete->execute();

        if ($delete->affected_rows < 1) {
            throw new RuntimeException('Event vanished before it could be archived.');
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        error_log('Event archive failed: ' . $e->getMessage());

        return [
            'ok'      => false,
            'message' => 'The event could not be deleted. Please try again.',
        ];
    }

    $suffix = $totalRegs > 0
        ? sprintf(' %d archived %s kept in history.', $totalRegs, $totalRegs === 1 ? 'registration' : 'registrations')
        : '';

    return [
        'ok'      => true,
        'message' => sprintf('"%s" was deleted.%s', $event['title'], $suffix),
    ];
}
