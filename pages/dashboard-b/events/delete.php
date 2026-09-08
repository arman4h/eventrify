<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();

if (isPost()) {
    $eventId = (int) post('event_id');

    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param('i', $eventId);

    if ($stmt->execute()) {
        $_SESSION['flash']['success'] = 'Event deleted successfully.';
    } else {
        $_SESSION['flash']['error'] = 'Failed to delete event.';
    }
}

redirect('/club/events');
