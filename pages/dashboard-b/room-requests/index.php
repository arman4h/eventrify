<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('room_requests');

$pageTitle = 'Room Requests';
$activePage = 'room-requests';

$clubId = (int) currentUser()['club_id'];

$slots = roomTimeSlots();

if (isPost()) {
    $rawEventId = post('event_id');
    $eventId = $rawEventId === 'in_club' ? 0 : (int) $rawEventId;
    $requestedDate = post('requested_date');
    $timeSlot = post('time_slot');
    $expectedParticipants = (int) post('expected_participants');
    $preferredRoom = post('preferred_room');
    $reason = post('reason');

    if ($preferredRoom === '' && $eventId > 0) {
        $evStmt = $db->prepare("SELECT venue FROM events WHERE event_id = ? AND club_id = ?");
        $evStmt->bind_param('ii', $eventId, $clubId);
        $evStmt->execute();
        $preferredRoom = (string) ($evStmt->get_result()->fetch_assoc()['venue'] ?? '');
    }

    $startTime = '';
    $endTime = '';
    foreach ($slots as $slot) {
        if ($timeSlot === $slot['start'] . '|' . $slot['end']) {
            $startTime = $slot['start'] . ':00';
            $endTime = $slot['end'] . ':00';
            break;
        }
    }

    if ($requestedDate !== '' && $startTime !== '') {
        $ins = $db->prepare("INSERT INTO room_requests (club_id, event_id, requested_date, start_time, end_time, expected_participants, preferred_room, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $evId = $eventId > 0 ? $eventId : null;
        $ins->bind_param('iisssiss', $clubId, $evId, $requestedDate, $startTime, $endTime, $expectedParticipants, $preferredRoom, $reason);
        if ($ins->execute()) {
            $_SESSION['flash']['success'] = 'Room request submitted.';
            redirect('/club/room-requests');
        } else {
            $_SESSION['flash']['error'] = 'Failed to submit request.';
        }
    } else {
        $_SESSION['flash']['error'] = 'Date and time slot are required.';
    }
}

$eventsStmt = $db->prepare("SELECT event_id, title, venue FROM events WHERE club_id = ? ORDER BY start_time DESC");
$eventsStmt->bind_param('i', $clubId);
$eventsStmt->execute();
$eventsResult = $eventsStmt->get_result();

$eventsData = [];
while ($ev = $eventsResult->fetch_assoc()) {
    $eventsData[] = $ev;
}
$eventVenuesJson = json_encode(array_column($eventsData, 'venue', 'event_id'));

$requestsList = $db->prepare("SELECT rr.*, e.title as event_title FROM room_requests rr LEFT JOIN events e ON e.event_id = rr.event_id WHERE rr.club_id = ? ORDER BY rr.requested_date DESC");
$requestsList->bind_param('i', $clubId);
$requestsList->execute();
$requestsResult = $requestsList->get_result();

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $alertMessage = $flashSuccess ?: $flashError;
        $alertType = $flashSuccess ? 'success' : ($flashError ? 'error' : 'info');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Room Requests</h2>
                <p class="page-subtitle">Request room allotment for your events</p>
            </div>
        </div>

        <div class="card p-6 mb-8">
            <h3 class="text-base font-semibold text-gray-900 mb-4">New Request</h3>
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="label-required">Event</label>
                        <select name="event_id" id="roomEventSelect" class="select" required>
                            <option value="">Select event...</option>
                            <option value="in_club">In-Club Session</option>
                            <?php foreach ($eventsData as $ev): ?>
                                <option value="<?= (int) $ev['event_id'] ?>"><?= e($ev['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-hint">Choose an event, or select "In-Club Session" for a room request not tied to an event.</p>
                    </div>
                    <div class="form-group">
                        <label class="label-required">Requested Date</label>
                        <input type="date" name="requested_date" class="input" required>
                    </div>
                    <div class="form-group">
                        <label class="label-required">Time Slot</label>
                        <select name="time_slot" class="select" required>
                            <option value="">Select time slot...</option>
                            <?php foreach ($slots as $slot): ?>
                                <option value="<?= e($slot['start'] . '|' . $slot['end']) ?>"><?= e($slot['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Expected Participants</label>
                        <input type="number" name="expected_participants" class="input" min="1" value="<?= e(post('expected_participants')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Room Number</label>
                        <input type="text" name="preferred_room" id="preferredRoom" class="input" value="<?= e(post('preferred_room')) ?>" readonly placeholder="Select an event to auto-fill">
                        <p class="form-hint" id="roomHint">Auto-filled from the event's venue. To change the room, edit the event.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Reason / Additional Notes</label>
                    <textarea name="reason" rows="3" class="textarea" placeholder="Describe why you need this room..."><?= e(post('reason')) ?></textarea>
                </div>
                <button type="submit" class="btn-primary">Submit Room Request</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900">My Requests</h3>
            </div>
            <?php if ($requestsResult->num_rows === 0): ?>
                <?php
                $emptyIcon = 'building';
                $emptyTitle = 'No room requests';
                $emptyText = 'Submit a room request above for your events.';
                require BASE_PATH . '/app/components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Participants</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($req = $requestsResult->fetch_assoc()): ?>
                            <tr>
                                <td class="font-medium text-gray-900"><?= e($req['event_title'] ?: 'In-Club Session') ?></td>
                                <td class="whitespace-nowrap text-sm text-gray-600"><?= formatDate($req['requested_date']) ?></td>
                                <td class="text-sm text-gray-600"><?= e(roomSlotLabel($req['start_time'], $req['end_time'])) ?></td>
                                <td class="text-sm text-gray-600"><?= (int) $req['expected_participants'] ?></td>
                                <td class="text-sm text-gray-600">
                                    <?= e($req['preferred_room'] ?: '') ?: '—' ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $req['status'] ?? 'pending';
                                    $sb = match($status) {
                                        'pending' => 'badge-warning',
                                        'approved' => 'badge-success',
                                        'declined' => 'badge-danger',
                                        default => 'badge-neutral',
                                    };
                                    $label = $status === 'approved' ? 'Room Allocated' : ucfirst($status);
                                    ?>
                                    <span class="<?= $sb ?>"><?= e($label) ?></span>
                                </td>
                                <td class="whitespace-nowrap text-sm text-gray-600"><?= formatDate($req['requested_date']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var eventSelect = document.getElementById('roomEventSelect');
    var roomInput = document.getElementById('preferredRoom');
    var roomHint = document.getElementById('roomHint');
    var venues = <?= $eventVenuesJson ?>;

    function syncRoom() {
        if (!eventSelect || !roomInput || !roomHint) return;
        var val = eventSelect.value;
        if (val === 'in_club') {
            roomInput.value = '';
            roomInput.readOnly = false;
            roomInput.placeholder = 'e.g. Room 301';
            roomHint.textContent = 'No linked event - enter the room you need for this session.';
        } else if (val) {
            roomInput.value = venues[val] || '';
            roomInput.readOnly = true;
            roomInput.placeholder = 'Select an event to auto-fill';
            roomHint.textContent = "Auto-filled from the event's venue. To change the room, edit the event.";
        } else {
            roomInput.value = '';
            roomInput.readOnly = true;
            roomInput.placeholder = 'Select an event to auto-fill';
            roomHint.textContent = "Auto-filled from the event's venue. To change the room, edit the event.";
        }
    }

    if (eventSelect) {
        eventSelect.addEventListener('change', syncRoom);
        syncRoom();
    }
});
</script>
