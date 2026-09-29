<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('room_requests');

$pageTitle = 'Room Requests';
$activePage = 'room-requests';
$canManage = clubCanManage('room_requests');

$clubId = (int) currentUser()['club_id'];
$slots  = roomTimeSlots();

/** Club's own events, used to pre-fill the room and derive required slots. */
$eventsStmt = $db->prepare("
    SELECT event_id, title, venue, start_time, end_time, capacity
    FROM events
    WHERE club_id = ? AND status <> 'cancelled'
    ORDER BY start_time DESC
");
$eventsStmt->bind_param('i', $clubId);
$eventsStmt->execute();
$eventsData = $eventsStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

$eventsById = [];
foreach ($eventsData as $ev) {
    $eventsById[(int) $ev['event_id']] = $ev;
}

// ── Selected event (from GET when browsing, POST when submitting) ─────
$eventId = (int) get('event_id', 0);
$event   = $eventsById[$eventId] ?? null;

// The event's own wall-clock window decides how many slots are required.
$requiredSlots = [];
$eventStart    = '';
$eventEnd      = '';
if ($event) {
    $eventStart = (string) $event['start_time'];
    $eventEnd   = (string) $event['end_time'];
    $requiredSlots = roomSlotsOverlapping($eventStart, $eventEnd);
    // Never demand more than the grid allows in one request.
    $requiredSlots = array_slice($requiredSlots, 0, ROOM_MAX_SLOTS_PER_REQUEST);
}

$requestedDate = (string) get('date', date('Y-m-d', strtotime('+7 days')));
if (strtotime($requestedDate) === false) {
    $requestedDate = date('Y-m-d', strtotime('+7 days'));
}

$participants   = max(0, (int) get('participants', 0));
$selectedRoom   = trim((string) get('room', ''));
$venuePreference = $event ? trim((string) $event['venue']) : '';

// ── JSON endpoint for the "Recommend a room" button ───────────────────
if ((string) get('recommend', '') === '1') {
    // An event's own window fixes how many slots it needs. Otherwise honour the
    // run the club has already ticked in the form, and fall back to the maximum
    // only when nothing is ticked yet.
    $askedFor  = (int) get('slot_count', 0);
    $slotCount = count($requiredSlots) > 0
        ? count($requiredSlots)
        : ($askedFor > 0 ? $askedFor : ROOM_MAX_SLOTS_PER_REQUEST);
    $slotCount = max(1, min($slotCount, ROOM_MAX_SLOTS_PER_REQUEST));

    $rec = roomRecommendation($requestedDate, $participants, $clubId, $venuePreference, $slotCount);

    // Offer the best free rooms for the winning slot run, each carrying the
    // slot indices it would book, so the page can tick them straight off.
    $busy   = roomAvailability($requestedDate, $clubId);
    $rooms  = array_column(roomCatalog(), 'room');
    $best   = ['free' => -1, 'indices' => [0], 'ranked' => []];

    for ($start = 0; $start + $slotCount - 1 < roomSlotCount(); $start++) {
        $indices   = range($start, $start + $slotCount - 1);
        $ranked    = recommendRooms($requestedDate, $indices, $participants, $clubId, $venuePreference);
        $freeCount = count(array_filter($ranked, static function (array $r): bool {
            return $r['free'];
        }));
        if ($freeCount > $best['free']) {
            $best = ['free' => $freeCount, 'indices' => $indices, 'ranked' => $ranked];
        }
    }

    $options = [];
    foreach ($best['ranked'] as $room) {
        if (!$room['free']) {
            continue;
        }
        $options[] = [
            'room'     => $room['room'],
            'capacity' => $room['capacity'],
            'reasons'  => $room['reasons'],
            'indices'  => $best['indices'],
        ];
        if (count($options) >= 3) {
            break;
        }
    }

    // Nothing free at all — still show the least-bad option so the club is not
    // left with an empty panel.
    if (count($options) === 0 && isset($best['ranked'][0])) {
        $options[] = [
            'room'     => $best['ranked'][0]['room'],
            'capacity' => $best['ranked'][0]['capacity'],
            'reasons'  => $best['ranked'][0]['reasons'],
            'indices'  => $best['indices'],
        ];
    }

    $labels = [];
    foreach ($best['indices'] as $index) {
        $labels[] = $slots[$index]['label'];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'date'        => $requestedDate,
        'primary'     => $options[0] ?? null,
        'options'     => $options,
        'slots'       => $labels,
        'indices'     => $best['indices'],
        'fullyBooked' => $best['free'] <= 0,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

// ── POST: create the booking ──────────────────────────────────────────
if (isPost()) {
    // View-only executives must not be able to write, even by hand-crafting a POST.
    requireClubManage('room_requests');

    $rawEventId   = post('event_id');
    $eventId      = $rawEventId === 'in_club' ? 0 : (int) $rawEventId;
    $event        = $eventId > 0 ? ($eventsById[$eventId] ?? null) : null;
    $requestedDate = trim(post('requested_date'));
    $participants  = (int) post('expected_participants');
    $preferredRoom = trim(post('preferred_room'));
    $reason        = trim(post('reason'));

    // The form posts selected slot keys as an array.
    $rawSlots   = $_POST['slots'] ?? [];
    $rawSlots   = is_array($rawSlots) ? $rawSlots : [$rawSlots];
    $slotKeys   = array_values(array_filter(array_map('strval', $rawSlots)));
    $slotIndices = normaliseSlotSelection(array_map('roomSlotIndex', $slotKeys));

    $errors = [];

    if (strtotime($requestedDate) === false) {
        $errors[] = 'Choose a valid date for the room.';
    } elseif ($requestedDate < date('Y-m-d')) {
        $errors[] = 'You cannot request a room for a date that has already passed.';
    }

    if ($eventId > 0 && $event === null) {
        $errors[] = 'That event does not belong to your club.';
    } elseif ($event && strtotime($requestedDate) !== false) {
        // A room request tied to an event must land on that event's own day.
        $eventDay = substr((string) $event['start_time'], 0, 10);
        if ($requestedDate !== $eventDay) {
            $errors[] = '"' . $event['title'] . '" is on ' . formatDate($eventDay, 'M j, Y')
                . '. Request that day instead of ' . formatDate($requestedDate, 'M j, Y') . '.';
        }
    }

    if (count($slotIndices) === 0) {
        $errors[] = 'Select at least one time slot.';
    } elseif (count($slotIndices) > ROOM_MAX_SLOTS_PER_REQUEST) {
        $errors[] = 'You can book at most ' . ROOM_MAX_SLOTS_PER_REQUEST . ' slots per request.';
    } else {
        $run = contiguousRun($slotIndices, roomSlotCount());
        if (count($run) !== count($slotIndices)) {
            $errors[] = 'The slots you picked are not next to each other. Book one slot, or two that run back to back.';
        }
    }

    if ($event && count($slotIndices) > 0 && !slotsCoverWindow($slotIndices, (string) $event['start_time'], (string) $event['end_time'])) {
        $overlapping = array_slice(
            roomSlotsOverlapping((string) $event['start_time'], (string) $event['end_time']),
            0,
            ROOM_MAX_SLOTS_PER_REQUEST
        );
        $needed = roomBatchSlotSummary(array_map(static function (int $i) use ($slots): array {
            return ['start' => $slots[$i]['start'], 'end' => $slots[$i]['end']];
        }, $overlapping));

        $from = formatClockTime((string) $event['start_time']);
        $to   = formatClockTime((string) $event['end_time']);
        $errors[] = '"' . $event['title'] . '" runs from ' . $from . ' to ' . $to
            . ', which is covered by ' . $needed . '. Please pick that slot.';
        if (count($overlapping) === 2) {
            // Build the example from the event's own window, never a fixed time.
            $errors[] = 'A ' . $from . ' to ' . $to . ' event therefore needs two slots: '
                . roomSlotKey($overlapping[0]) . ' and ' . roomSlotKey($overlapping[1]) . '.';
        }
    }

    if ($preferredRoom === '') {
        $errors[] = 'Choose a room.';
    } else {
        $known = array_column(roomCatalog(), 'room');
        if (!in_array($preferredRoom, $known, true)) {
            $errors[] = 'That room is not in the room list.';
        }
    }

    if ($participants < 1 || $participants > 100000) {
        $errors[] = 'Enter a realistic number of participants.';
    }

    $reasonErrors = [];
    vAdd($reasonErrors, vMinLen($reason, 10, 'Reason'));
    vAdd($reasonErrors, vMaxLen($reason, 1000, 'Reason'));
    $errors = array_merge($errors, $reasonErrors);

    // Refuse to double-book a room that is already held for these slots.
    if (count($errors) === 0) {
        $conflicts = roomSlotConflicts($requestedDate, $preferredRoom, $slotIndices, $clubId);
        if (count($conflicts) > 0) {
            $when = roomBatchSlotSummary(array_map(static function (array $c): array {
                return ['start' => $c['start'], 'end' => $c['end']];
            }, $conflicts));
            $errors[] = $when . ' in ' . $preferredRoom . ' is already allocated to ' . $conflicts[0]['club'] . '.'
                . ' Pick a different room or slot.';
        }
    }

    if (count($errors) > 0) {
        flash('error', implode("\n", $errors));
        $back = '/club/room-requests'
            . ($eventId > 0 ? '?event_id=' . $eventId : '')
            . '&date=' . urlencode($requestedDate)
            . ($participants > 0 ? '&participants=' . $participants : '')
            . ($preferredRoom !== '' ? '&room=' . urlencode($preferredRoom) : '');
        redirect($back);
    }

    // One row per slot, written in a single transaction so a two-slot booking
    // can never end up half-created.
    $db->begin_transaction();
    $ins = $db->prepare("
        INSERT INTO room_requests
            (club_id, event_id, requested_date, start_time, end_time,
             expected_participants, preferred_room, reason, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $evId = $eventId > 0 ? $eventId : null;
    $written = 0;
    // mysqli is in exception mode, so a failed insert throws; catching it keeps
    // the transaction from being left open and turns a 500 into a retry message.
    try {
        foreach ($slotIndices as $index) {
            [$startTime, $endTime] = roomSlotDatabaseTimes(roomSlotKey($index));
            $ins->bind_param('iisssiss', $clubId, $evId, $requestedDate, $startTime, $endTime, $participants, $preferredRoom, $reason);
            $ins->execute();
            $written++;
        }

        $db->commit();
    } catch (mysqli_sql_exception $e) {
        error_log('Room request insert failed: ' . $e->getMessage());
        $db->rollback();
        flash('error', 'The room request could not be saved. Please try again.');
        redirect('/club/room-requests?date=' . urlencode($requestedDate));
    }

    $slotSummary = roomBatchSlotSummary(array_map(static function (int $i) use ($slots): array {
        return ['start' => $slots[$i]['start'], 'end' => $slots[$i]['end']];
    }, $slotIndices));

    flash('success', 'Room request submitted for ' . formatDate($requestedDate, 'M d, Y') . ', '
        . $slotSummary . ' in ' . $preferredRoom . '. The administration will confirm it shortly.');
    redirect('/club/room-requests');
}

// ── Availability for the chosen date ──────────────────────────────────
$grid        = roomAvailabilityGrid($requestedDate, $clubId);
$availability = roomAvailability($requestedDate, $clubId);

// Pre-select the slots the linked event needs, else nothing.
$activeSlots = $requiredSlots;

// ── Request history, grouped into booking batches ─────────────────────
$listStmt = $db->prepare("
    SELECT r.*, e.title AS event_title, c.club_name
    FROM room_requests r
    LEFT JOIN events e ON e.event_id = r.event_id
    LEFT JOIN clubs c ON c.club_id = r.club_id
    WHERE r.club_id = ?
    ORDER BY r.requested_date DESC, r.created_at DESC, r.start_time ASC
");
$listStmt->bind_param('i', $clubId);
$listStmt->execute();
$batches = roomRequestBatches($listStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? []);

$eventVenuesJson = json_encode(array_column($eventsData, 'venue', 'event_id'));
$slotMetaJson    = json_encode(array_map(static function (array $s): array {
    return ['start' => $s['start'], 'end' => $s['end'], 'label' => $s['label']];
}, $slots));

$roomOptions = roomCatalog();

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
                <p class="page-subtitle">Book one or two time slots for your event, and see what is already taken.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- ── Booking form ─────────────────────────────────────── -->
            <div class="lg:col-span-2 space-y-6">
                <?php if (!$canManage): ?>
                <div class="alert alert-info">
                    You have view-only access to this section, so new room requests are disabled.
                    You can still review availability and your club's request history below.
                </div>
                <?php endif; ?>
                <form method="GET" action="<?= url('/club/room-requests') ?>" id="roomRequestForm" class="card p-6<?= $canManage ? '' : ' hidden' ?>">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">New Request</h3>
                    <p class="text-sm text-gray-500 mb-5">
                        Rooms are booked in fixed slots. Book one slot, or two back-to-back slots if you need longer.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="label-required" for="roomEventSelect">Event</label>
                            <select name="event_id" id="roomEventSelect" class="select">
                                <option value="">Select event...</option>
                                <option value="in_club" <?= $eventId === 0 && $requestedDate !== '' && !isset($eventsById[0]) && get('event_id') === 'in_club' ? 'selected' : '' ?>>In-Club Session</option>
                                <?php foreach ($eventsData as $ev): ?>
                                    <option value="<?= (int) $ev['event_id'] ?>" <?= $eventId === (int) $ev['event_id'] ? 'selected' : '' ?>>
                                        <?= e($ev['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-hint">Linking an event fills in the room and the time you need.</p>
                        </div>

                        <div class="form-group">
                            <label class="label-required" for="requested_date">Requested Date</label>
                            <input type="date" name="date" id="requested_date" class="input"
                                   min="<?= date('Y-m-d') ?>" value="<?= e($requestedDate) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="label" for="expected_participants">Expected Participants</label>
                            <input type="number" name="participants" id="expected_participants" class="input"
                                   min="0" max="100000" value="<?= $participants > 0 ? (int) $participants : '' ?>"
                                   placeholder="e.g. 60">
                            <p class="form-hint">Used to suggest a room that is big enough.</p>
                        </div>

                        <div class="form-group">
                            <label class="label-required" for="preferred_room">Room</label>
                            <select name="room" id="preferred_room" class="select" required>
                                <option value="">Select a room...</option>
                                <?php foreach ($roomOptions as $opt): ?>
                                    <option value="<?= e($opt['room']) ?>" <?= $selectedRoom === $opt['room'] ? 'selected' : '' ?>>
                                        <?= e($opt['room']) ?><?= $opt['capacity'] > 0 ? ' (holds ~' . (int) $opt['capacity'] . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-hint" id="roomHint">
                                <?= $venuePreference !== ''
                                    ? 'Your event is held in “' . e($venuePreference) . '”.'
                                    : 'Pick the room you need, or use the recommendation below.' ?>
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label-required">Time Slot(s)</label>
                        <p class="text-xs text-gray-500 mb-2">
                            Pick one slot, or two next to each other. Slots are shown for
                            <strong><?= e(formatDate($requestedDate, 'D, M d Y')) ?></strong>.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" id="slotPicker">
                            <?php foreach ($slots as $i => $slot):
                                $isRequired = in_array($i, $requiredSlots, true);
                                $busyHere   = [];
                                foreach ($availability as $roomKey => $bySlot) {
                                    if (($selectedRoom === '' || $selectedRoom === $roomKey) && isset($bySlot[$i])) {
                                        $busyHere[] = $roomKey;
                                    }
                                }
                            ?>
                                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-blue-300 transition-colors slot-option"
                                       data-slot-index="<?= $i ?>">
                                    <input type="checkbox" name="slots[]" value="<?= e($slot['start'] . '|' . $slot['end']) ?>"
                                           data-index="<?= $i ?>" class="mt-0.5 rounded border-gray-300 slot-checkbox"
                                           <?= in_array($i, $activeSlots, true) ? 'checked' : '' ?>>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-900"><?= e($slot['label']) ?></span>
                                        <?php if ($isRequired): ?>
                                            <span class="block text-xs text-blue-600 font-medium">
                                                Needed for this event's time
                                            </span>
                                        <?php endif; ?>
                                        <?php if (count($busyHere) > 0): ?>
                                            <span class="block text-xs text-amber-600">
                                                <?= e($selectedRoom !== '' ? 'Already taken in this room' : count($busyHere) . ' room(s) busy') ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="form-hint mt-2" id="slotHint">
                            <?php if (count($requiredSlots) > 0): ?>
                                <?= e(implode(' + ', array_map(static function (int $i) use ($slots): string {
                                    return $slots[$i]['label'];
                                }, $requiredSlots))) ?>
                                <?= count($requiredSlots) === 2
                                    ? ' — two slots for a longer event, already ticked for you.'
                                    : ' — ticked for you.' ?>
                            <?php else: ?>
                                Maximum <?= ROOM_MAX_SLOTS_PER_REQUEST ?> slots per request, and they must run back to back.
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="label" for="reason">Reason / Additional Notes</label>
                        <textarea name="reason_note" id="reason" rows="2" class="textarea"
                                  placeholder="Optional — anything the administration should know."></textarea>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">
                            <?= icon('calendar', 'w-4 h-4') ?>
                            Check availability
                        </button>
                        <button type="button" class="btn-secondary" id="recommendBtn">
                            <?= icon('check-circle', 'w-4 h-4') ?>
                            Recommend a room
                        </button>
                    </div>
                </form>

                <!-- ── Recommendation result ────────────────────────── -->
                <div id="recommendPanel" class="hidden"></div>

                <!-- ── Submit form (populated by the form above) ────── -->
                <form method="POST" action="<?= url('/club/room-requests') ?>" id="roomSubmitForm" class="card p-6 hidden"<?= $canManage ? '' : ' aria-hidden="true"' ?>>
<?= csrfField() ?>
                    <input type="hidden" name="event_id" id="submitEventId" value="<?= $eventId > 0 ? $eventId : 'in_club' ?>">
                    <input type="hidden" name="requested_date" id="submitDate" value="<?= e($requestedDate) ?>">
                    <input type="hidden" name="expected_participants" id="submitParticipants" value="<?= (int) $participants ?>">
                    <input type="hidden" name="preferred_room" id="submitRoom" value="<?= e($selectedRoom) ?>">
                    <div id="submitSlots"></div>
                    <input type="text" name="reason" id="submitReason" class="hidden" value="">

                    <h3 class="text-base font-semibold text-gray-900 mb-1">Confirm your booking</h3>
                    <p class="text-sm text-gray-500 mb-4" id="confirmSummary"></p>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn-primary">
                            <?= icon('check-circle', 'w-4 h-4') ?>
                            Submit room request
                        </button>
                        <button type="button" class="btn-secondary" id="editAgainBtn">Change selection</button>
                    </div>
                </form>
            </div>

            <!-- ── Availability grid ─────────────────────────────────── -->
            <div class="space-y-6">
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Availability</h3>
                    <p class="text-xs text-gray-500 mb-4">
                        <?= e(formatDate($requestedDate, 'D, M d Y')) ?> — grey slots are already taken by another club.
                    </p>

                    <?php if (count($grid['rooms']) === 0): ?>
                        <p class="text-sm text-gray-500 text-center py-6">No rooms recorded yet.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table text-xs">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap">Room</th>
                                        <?php foreach ($slots as $i => $slot): ?>
                                            <th class="text-center px-1" title="<?= e($slot['label']) ?>">
                                                <?= e(formatClockTime($slot['start'])) ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grid['rooms'] as $roomRow): ?>
                                        <tr class="<?= $selectedRoom === $roomRow['room'] ? 'bg-blue-50/60' : '' ?>">
                                            <td class="whitespace-nowrap font-medium text-gray-900">
                                                <?= e($roomRow['room']) ?>
                                                <?php if ($roomRow['capacity'] > 0): ?>
                                                    <span class="text-gray-400 font-normal">~<?= (int) $roomRow['capacity'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <?php foreach ($slots as $i => $slot):
                                                $cell = $roomRow['cells'][$i];
                                            ?>
                                                <td class="text-center px-1">
                                                    <?php if ($cell['free']): ?>
                                                        <span class="inline-block w-5 h-5 rounded bg-emerald-100 text-emerald-700"
                                                              title="Free"><?= icon('check', 'w-3 h-3 mx-auto') ?></span>
                                                    <?php else: ?>
                                                        <span class="inline-block w-5 h-5 rounded bg-gray-200 text-gray-500"
                                                              title="<?= e($cell['club'] . ' — ' . $cell['status']) ?>">
                                                            <?= icon('x', 'w-3 h-3 mx-auto') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex items-center gap-4 mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block w-3 h-3 rounded bg-emerald-100"></span>Free
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block w-3 h-3 rounded bg-gray-200"></span>Taken
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">How slots work</h3>
                    <div class="text-sm text-gray-600 space-y-2 leading-relaxed">
                        <p>Rooms are allocated in six fixed slots between 8:30 AM and 4:30 PM.</p>
                        <p>
                            A single request can hold up to <?= ROOM_MAX_SLOTS_PER_REQUEST ?> slots, and they must run
                            back to back so you get one continuous block of time.
                        </p>
                        <p class="text-gray-500">
                            For example, an event from 2:00 PM to 4:00 PM is covered by
                            <strong>1:50 – 3:10 PM</strong> plus <strong>3:10 – 4:30 PM</strong> — two slots, one request.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── History ──────────────────────────────────────────────── -->
        <div class="card overflow-hidden mt-6">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900">My Requests</h3>
            </div>

            <?php if (count($batches) === 0): ?>
                <?php
                $emptyIcon = 'building';
                $emptyTitle = 'No room requests';
                $emptyText = 'Pick a date above to see what is free, then submit your first request.';
                require BASE_PATH . '/app/components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($batches as $batch):
                        $status = $batch['status'];
                        $sb = match ($status) {
                            'pending'  => 'badge-warning',
                            'approved' => 'badge-success',
                            'declined' => 'badge-danger',
                            default    => 'badge-neutral',
                        };
                        $label = $status === 'approved' ? 'Room Allocated' : ucfirst($status);
                        $isMulti = count($batch['rows']) > 1;
                    ?>
                    <div class="p-5">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900">
                                    <?= e($batch['event'] !== '' ? $batch['event'] : 'In-Club Session') ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?= e(formatDate($batch['date'], 'D, M d Y')) ?>
                                    &middot; <?= e($batch['room'] !== '' ? $batch['room'] : '—') ?>
                                    <?php if ($batch['participants'] > 0): ?>
                                        &middot; <?= (int) $batch['participants'] ?> people
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="<?= $sb ?> shrink-0"><?= e($label) ?></span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <?php foreach ($batch['rows'] as $row): ?>
                                <span class="badge-neutral"><?= e(roomSlotLabel((string) $row['start_time'], (string) $row['end_time'])) ?></span>
                            <?php endforeach; ?>
                            <?php if ($isMulti): ?>
                                <span class="text-xs text-gray-500">
                                    Booked together as one request (#<?= e(implode(', ', array_map('strval', $batch['request_ids']))) ?>)
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (trim($batch['reason']) !== ''): ?>
                            <p class="text-sm text-gray-600 mt-3 leading-relaxed"><?= e($batch['reason']) ?></p>
                        <?php endif; ?>

                        <?php if ($batch['review_notes'] !== ''): ?>
                            <div class="mt-3 rounded-lg bg-gray-50 p-3">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Administration
                                </p>
                                <p class="text-sm text-gray-700"><?= e($batch['review_notes']) ?></p>
                            </div>
                        <?php endif; ?>

                        <p class="text-xs text-gray-400 mt-3">
                            Submitted <?= e(formatDate($batch['created_at'], 'M d, Y h:i A')) ?>
                            <?php if (!empty($batch['reviewed_at'])): ?>
                                &middot; reviewed <?= e(formatDate($batch['reviewed_at'], 'M d, Y')) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>

<script>
(function () {
    'use strict';

    var SLOTS = <?= $slotMetaJson ?>;
    var MAX_SLOTS = <?= ROOM_MAX_SLOTS_PER_REQUEST ?>;
    var REQUIRED = <?= json_encode($requiredSlots) ?>;

    var venues = <?= $eventVenuesJson ?>;
    var eventSelect = document.getElementById('roomEventSelect');
    var roomSelect = document.getElementById('preferred_room');
    var dateInput = document.getElementById('requested_date');
    var participantsInput = document.getElementById('expected_participants');
    var reasonInput = document.getElementById('reason');
    var roomHint = document.getElementById('roomHint');
    var slotPicker = document.getElementById('slotPicker');
    var slotHint = document.getElementById('slotHint');
    var submitForm = document.getElementById('roomSubmitForm');
    var recommendPanel = document.getElementById('recommendPanel');

    function checkedIndices() {
        var out = [];
        slotPicker.querySelectorAll('.slot-checkbox').forEach(function (cb) {
            if (cb.checked) { out.push(parseInt(cb.dataset.index, 10)); }
        });
        return out.sort(function (a, b) { return a - b; });
    }

    function setChecked(indices) {
        slotPicker.querySelectorAll('.slot-checkbox').forEach(function (cb) {
            cb.checked = indices.indexOf(parseInt(cb.dataset.index, 10)) !== -1;
        });
        updateSlotHint();
    }

    function isContiguous(indices) {
        for (var i = 1; i < indices.length; i++) {
            if (indices[i] !== indices[i - 1] + 1) { return false; }
        }
        return true;
    }

    function updateSlotHint() {
        var idx = checkedIndices();
        if (idx.length === 0) {
            slotHint.textContent = 'Select at least one slot. You can pick up to ' + MAX_SLOTS + ' that run back to back.';
            slotHint.className = 'form-hint mt-2';
            return;
        }
        if (idx.length > MAX_SLOTS) {
            slotHint.textContent = 'You can only book ' + MAX_SLOTS + ' slots per request.';
            slotHint.className = 'form-hint mt-2 text-red-600';
            return;
        }
        if (!isContiguous(idx)) {
            slotHint.textContent = 'Those slots are not next to each other — pick one slot, or two that run back to back.';
            slotHint.className = 'form-hint mt-2 text-red-600';
            return;
        }
        slotHint.textContent = 'Selected: ' + idx.map(function (i) { return SLOTS[i].label; }).join(' + ');
        slotHint.className = 'form-hint mt-2 text-emerald-600 font-medium';
    }

    slotPicker.addEventListener('change', function () {
        var idx = checkedIndices();
        if (idx.length > MAX_SLOTS) {
            // Reject the newest pick rather than silently dropping one.
            var last = idx[idx.length - 1];
            slotPicker.querySelectorAll('.slot-checkbox')[last].checked = false;
        }
        idx = checkedIndices();
        if (idx.length === 2 && !isContiguous(idx)) {
            slotHint.textContent = 'Those slots are not next to each other — pick one slot, or two that run back to back.';
            slotHint.className = 'form-hint mt-2 text-red-600';
            return;
        }
        updateSlotHint();
    });

    // Picking an event fills in the room it is held in.
    eventSelect.addEventListener('change', function () {
        var val = eventSelect.value;
        if (val && val !== 'in_club' && venues[val]) {
            if (roomSelect.value) { roomSelect.value = venues[val]; }
            roomHint.textContent = 'Filled in from the event venue: ' + venues[val] + '.';
        } else {
            roomHint.textContent = 'Pick the room you need, or use the recommendation below.';
        }
    });

    dateInput.addEventListener('change', function () {
        // Reload so the availability grid and slot hints match the new date.
        roomRequestForm.submit();
    });

    // ── Check availability: reveal the confirm step ───────────────────
    roomRequestForm.addEventListener('submit', function (ev) {
        ev.preventDefault();

        var idx = checkedIndices();
        if (idx.length === 0) {
            slotHint.textContent = 'Select at least one time slot.';
            slotHint.className = 'form-hint mt-2 text-red-600';
            return;
        }
        if (idx.length > MAX_SLOTS || !isContiguous(idx)) {
            slotHint.textContent = 'Pick one slot, or two that run back to back.';
            slotHint.className = 'form-hint mt-2 text-red-600';
            return;
        }
        if (!roomSelect.value) {
            roomSelect.focus();
            return;
        }

        document.getElementById('submitEventId').value = eventSelect.value || 'in_club';
        document.getElementById('submitDate').value = dateInput.value;
        document.getElementById('submitParticipants').value = participantsInput.value;
        document.getElementById('submitRoom').value = roomSelect.value;
        document.getElementById('submitReason').value = reasonInput.value;

        var holder = document.getElementById('submitSlots');
        holder.innerHTML = '';
        idx.forEach(function (i) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'slots[]';
            input.value = SLOTS[i].start + '|' + SLOTS[i].end;
            holder.appendChild(input);
        });

        var parts = [roomSelect.value, dateInput.value];
        parts.push(idx.map(function (i) { return SLOTS[i].label; }).join(' + '));
        if (participantsInput.value) { parts.push(participantsInput.value + ' participants'); }
        document.getElementById('confirmSummary').textContent = parts.join(' · ');

        submitForm.classList.remove('hidden');
        submitForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    document.getElementById('editAgainBtn').addEventListener('click', function () {
        submitForm.classList.add('hidden');
        roomRequestForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // ── Recommend a room ───────────────────────────────────────────────
    document.getElementById('recommendBtn').addEventListener('click', function () {
        var btn = this;
        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Finding the best slot&hellip;';

        var params = new URLSearchParams();
        params.set('recommend', '1');
        params.set('date', dateInput.value);
        params.set('participants', participantsInput.value || '0');
        // Recommend for the run the user has actually ticked, so the button
        // never silently books a second slot they did not ask for.
        var ticked = document.querySelectorAll('.slot-checkbox:checked').length;
        params.set('slot_count', ticked > 0 ? String(ticked) : '');
        if (eventSelect.value && eventSelect.value !== 'in_club') {
            params.set('event_id', eventSelect.value);
        }

        fetch('/club/room-requests?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                btn.innerHTML = original;
                renderRecommendation(data);
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = original;
                recommendPanel.classList.remove('hidden');
                recommendPanel.innerHTML =
                    '<div class="card p-6"><div class="alert-error"><p class="text-sm">The recommendation could not be loaded. Pick a room from the list above.</p></div></div>';
            });
    });

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function renderRecommendation(data) {
        recommendPanel.classList.remove('hidden');

        if (!data.primary) {
            recommendPanel.innerHTML =
                '<div class="card p-6"><h3 class="text-base font-semibold text-gray-900 mb-2">No recommendation</h3>' +
                '<p class="text-sm text-gray-500">Pick a date and try again.</p></div>';
            return;
        }

        var html = '<div class="card p-6">';
        html += '<h3 class="text-base font-semibold text-gray-900 mb-1">Recommended for you</h3>';

        var options = data.options || [];

        if (data.fullyBooked) {
            // Every room is held for this slot, and the server rejects a booking
            // against a held room, so nothing here may be selectable. Show the
            // room that came closest only to explain why it did not work out.
            html += '<div class="alert-warning mb-4 flex items-start gap-3"><p class="text-sm">'
                + 'Every room is already held for ' + esc(data.slots.join(' + ')) + ' on ' + esc(data.date) + '. '
                + 'Try a different slot or date.</p></div>';

            options.forEach(function (opt) {
                html += '<div class="rounded-lg border border-gray-200 p-4 mb-2 opacity-75">'
                    + '<span class="block font-semibold text-gray-900">' + esc(opt.room) + '</span>';
                if (opt.capacity > 0) {
                    html += '<span class="block text-xs text-gray-500">Known capacity ~' + opt.capacity + '</span>';
                }
                html += '<span class="block text-xs text-amber-700 font-medium mt-1">'
                    + esc(opt.reasons.join(' · ')) + '</span></div>';
            });

            html += '<div class="flex flex-wrap gap-3 mt-4">';
            html += '<button type="button" class="btn-secondary" id="dismissRecommendation">Choose another slot</button>';
            html += '</div></div>';

            recommendPanel.innerHTML = html;
            document.getElementById('dismissRecommendation').addEventListener('click', function () {
                recommendPanel.classList.add('hidden');
                roomRequestForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
            return;
        }

        html += '<p class="text-sm text-gray-500 mb-4">' + esc(data.slots.join(' + ')) + ' on ' + esc(data.date) + '</p>';

        options.forEach(function (opt, i) {
            html += '<label class="flex items-start gap-3 rounded-lg border p-4 mb-2 cursor-pointer hover:border-blue-300 transition-colors'
                + (i === 0 ? ' border-blue-300 bg-blue-50/50' : ' border-gray-200') + '">';
            html += '<input type="radio" name="rec" value="' + i + '" class="mt-1" ' + (i === 0 ? 'checked' : '') + '>';
            html += '<span class="min-w-0 flex-1">';
            html += '<span class="block font-semibold text-gray-900">' + esc(opt.room) + '</span>';
            if (opt.capacity > 0) {
                html += '<span class="block text-xs text-gray-500">Known capacity ~' + opt.capacity + '</span>';
            }
            html += '<span class="block text-xs text-emerald-700 font-medium mt-1">' + esc(opt.reasons.join(' · ')) + '</span>';
            html += '</span></label>';
        });

        html += '<div class="flex flex-wrap gap-3 mt-4">';
        html += '<button type="button" class="btn-primary" id="useRecommendation">Use this recommendation</button>';
        html += '</div></div>';

        recommendPanel.innerHTML = html;

        document.getElementById('useRecommendation').addEventListener('click', function () {
            var picked = options[parseInt(recommendPanel.querySelector('input[name=rec]:checked').value, 10)];
            if (!picked) { return; }
            roomSelect.value = picked.room;
            setChecked(picked.indices);
            roomHint.textContent = 'Recommended for you.';
            recommendPanel.classList.add('hidden');
            roomRequestForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    updateSlotHint();
})();
</script>
