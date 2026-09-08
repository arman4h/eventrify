<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubUser();

if (isPost()) {
    $eventId = (int) post('event_id');
    $clubId = (int) currentUser()['club_id'];

    $stmt = $db->prepare("DELETE FROM events WHERE event_id = ? AND club_id = ?");
    $stmt->bind_param('ii', $eventId, $clubId);

    if ($stmt->execute()) {
        $_SESSION['flash']['success'] = 'Event deleted successfully.';
    } else {
        $_SESSION['flash']['error'] = 'Failed to delete event.';
    }
}

redirect('/club/events');
