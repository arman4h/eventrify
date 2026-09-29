<?php

/**
 * Room allocation: the fixed slot grid, the availability picture for a given
 * day, and the recommendation engine that picks a room + slot combination.
 *
 * The schema has no `rooms` table, so the room catalogue is *derived* from
 * data the project already stores:
 *
 *   - `events.venue`           – the venue strings clubs have actually used
 *   - `events.capacity`        – the largest headcount that room has hosted
 *   - `room_requests`          – the rooms clubs have asked for by name
 *
 * A booking for a long event is written as one `room_requests` row per slot,
 * all sharing the same club, event, date, room and creation timestamp. That
 * makes a two-slot booking atomic and lets the admin review it as one unit
 * without any schema change.
 */

const ROOM_MAX_SLOTS_PER_REQUEST = 2;

/**
 * The fixed slot grid. Slots are contiguous so two adjacent slots give a
 * continuous block (13:50-15:10 followed by 15:10-16:30 covers 14:00-16:00).
 */
function roomTimeSlots(): array
{
    return [
        ['start' => '08:30', 'end' => '09:50', 'label' => '8:30 - 9:50 AM'],
        ['start' => '09:50', 'end' => '11:10', 'label' => '9:50 - 11:10 AM'],
        ['start' => '11:10', 'end' => '12:30', 'label' => '11:10 AM - 12:30 PM'],
        ['start' => '12:30', 'end' => '13:40', 'label' => '12:30 - 1:40 PM'],
        ['start' => '13:50', 'end' => '15:10', 'label' => '1:50 - 3:10 PM'],
        ['start' => '15:10', 'end' => '16:30', 'label' => '3:10 - 4:30 PM'],
    ];
}

function roomSlotCount(): int
{
    return count(roomTimeSlots());
}

function roomSlotKey(int $index): string
{
    $slots = roomTimeSlots();
    if (!isset($slots[$index])) {
        return '';
    }

    return $slots[$index]['start'] . '|' . $slots[$index]['end'];
}

/**
 * Turn a "08:30|09:50" key into the slot index, or -1.
 */
function roomSlotIndex(string $key): int
{
    $parts = explode('|', trim($key));
    if (count($parts) !== 2) {
        return -1;
    }

    $slots = roomTimeSlots();
    foreach ($slots as $i => $slot) {
        if ($slot['start'] === trim($parts[0]) && $slot['end'] === trim($parts[1])) {
            return $i;
        }
    }

    return -1;
}

function roomSlotLabel(string $start, string $end): string
{
    $s = roomTimePart($start);
    $e = roomTimePart($end);
    if ($s === '' || $e === '') {
        return '—';
    }

    foreach (roomTimeSlots() as $slot) {
        if ($slot['start'] === $s && $slot['end'] === $e) {
            return $slot['label'];
        }
    }

    return formatClockTime($s) . ' - ' . formatClockTime($e);
}

function roomSlotDatabaseTimes(string $key): array
{
    $index = roomSlotIndex($key);
    $slots = roomTimeSlots();
    if ($index < 0) {
        return ['', ''];
    }

    return [$slots[$index]['start'] . ':00', $slots[$index]['end'] . ':00'];
}

/**
 * Reduce a TIME ('14:00:00') or DATETIME ('2026-10-20 14:00:00') to 'HH:MM'.
 *
 * Both shapes reach these helpers: `events.start_time` is a DATETIME while
 * `room_requests.start_time` is a TIME, so slicing the first five characters
 * blindly returns the date for one of them.
 */
function roomTimePart(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    // Drop a leading date if present.
    if (preg_match('/\d{4}-\d{2}-\d{2}[T ]/', $value)) {
        $value = (string) preg_replace('/^.*?[T ]/', '', $value);
    }

    if (!preg_match('/^(\d{1,2}):(\d{2})/', $value, $m)) {
        return '';
    }

    return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
}

function minutesSinceMidnight(string $time): int
{
    $part = roomTimePart($time);
    if ($part === '') {
        return 0;
    }

    $parts = explode(':', $part);

    return ((int) $parts[0] * 60) + (int) $parts[1];
}

function formatClockTime(string $time): string
{
    $part = roomTimePart($time);
    if ($part === '') {
        return '—';
    }

    $parts = explode(':', $part);
    $h     = (int) $parts[0];
    $m     = (int) $parts[1];
    $ampm  = $h >= 12 ? 'PM' : 'AM';
    $h12   = $h % 12;
    if ($h12 === 0) {
        $h12 = 12;
    }

    return $h12 . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . ' ' . $ampm;
}

/**
 * Which slot indices overlap a given wall-clock window?
 * A 14:00-16:00 event lands on the 1:50-3:10 and 3:10-4:30 slots.
 */
function roomSlotsOverlapping(string $start, string $end): array
{
    $s = minutesSinceMidnight($start);
    $e = minutesSinceMidnight($end);
    if ($e <= $s) {
        $e = $s + 60;
    }

    $hits = [];
    foreach (roomTimeSlots() as $i => $slot) {
        $ss = minutesSinceMidnight($slot['start']);
        $se = minutesSinceMidnight($slot['end']);
        if ($s < $se && $e > $ss) {
            $hits[] = $i;
        }
    }

    if (count($hits) === 0) {
        return [nearestSlotIndex($s)];
    }

    return contiguousRun($hits, roomSlotCount());
}

function nearestSlotIndex(int $minutes): int
{
    $bestIndex = 0;
    $bestDelta = PHP_INT_MAX;

    foreach (roomTimeSlots() as $i => $slot) {
        $mid  = (minutesSinceMidnight($slot['start']) + minutesSinceMidnight($slot['end'])) / 2;
        $delta = abs($mid - $minutes);
        if ($delta < $bestDelta) {
            $bestDelta = $delta;
            $bestIndex = $i;
        }
    }

    return $bestIndex;
}

/**
 * Expand a set of slot indices into the smallest contiguous run covering it.
 */
function contiguousRun(array $indices, int $total): array
{
    if (count($indices) === 0) {
        return [];
    }

    $min = min($indices);
    $max = max($indices);

    return range($min, $max);
}

/**
 * Keep only a contiguous, non-overlapping run of at most $max indices that is
 * a prefix of the requested set (so a 3-slot need degrades to 2 slots).
 */
function normaliseSlotSelection(array $indices, int $max = ROOM_MAX_SLOTS_PER_REQUEST): array
{
    $indices = array_values(array_unique(array_map('intval', $indices)));
    $indices = array_values(array_filter($indices, static function (int $i): bool {
        return $i >= 0 && $i < roomSlotCount();
    }));

    sort($indices);
    if (count($indices) === 0) {
        return [];
    }

    $run = contiguousRun($indices, roomSlotCount());
    if (count($run) > $max) {
        $run = array_slice($run, 0, $max);
    }

    return $run;
}

function slotIndicesToKeys(array $indices): array
{
    $keys = [];
    foreach ($indices as $index) {
        $key = roomSlotKey((int) $index);
        if ($key !== '') {
            $keys[$key] = true;
        }
    }

    return array_keys($keys);
}

/**
 * Do the chosen slots fully cover the event's start/end window?
 */
function slotsCoverWindow(array $indices, string $eventStart, string $eventEnd): bool
{
    if (count($indices) === 0) {
        return false;
    }

    $s = minutesSinceMidnight($eventStart);
    $e = minutesSinceMidnight($eventEnd);
    if ($e <= $s) {
        $e = $s + 60;
    }

    $coveredStart = PHP_INT_MAX;
    $coveredEnd   = -1;
    foreach ($indices as $index) {
        $slots = roomTimeSlots();
        if (!isset($slots[$index])) {
            continue;
        }
        $coveredStart = min($coveredStart, minutesSinceMidnight($slots[$index]['start']));
        $coveredEnd   = max($coveredEnd, minutesSinceMidnight($slots[$index]['end']));
    }

    return $coveredStart <= $s && $coveredEnd >= $e;
}

/**
 * The derived room catalogue.
 *
 * @return array<int, array{room:string, capacity:int, events:int, requested:int}>
 */
function roomCatalog(): array
{
    // Memoised for the lifetime of the request: the catalogue is derived from
    // events and room_requests, neither of which changes mid-render.
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $db    = $GLOBALS['db'];
    $rooms = [];

    $result = $db->query("
        SELECT venue AS room,
               COUNT(*) AS events,
               MAX(CASE WHEN capacity > 0 THEN capacity ELSE 0 END) AS capacity
        FROM events
        WHERE venue IS NOT NULL AND TRIM(venue) <> ''
        GROUP BY venue
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $room = trim((string) $row['room']);
            if ($room === '') {
                continue;
            }
            $rooms[$room] = [
                'room'      => $room,
                'capacity'  => (int) $row['capacity'],
                'events'    => (int) $row['events'],
                'requested' => 0,
            ];
        }
    }

    $result = $db->query("
        SELECT preferred_room AS room, COUNT(*) AS requested
        FROM room_requests
        WHERE preferred_room IS NOT NULL AND TRIM(preferred_room) <> ''
        GROUP BY preferred_room
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $room = trim((string) $row['room']);
            if ($room === '') {
                continue;
            }
            if (isset($rooms[$room])) {
                $rooms[$room]['requested'] = (int) $row['requested'];
            } else {
                $rooms[$room] = [
                    'room'      => $room,
                    'capacity'  => 0,
                    'events'    => 0,
                    'requested' => (int) $row['requested'],
                ];
            }
        }
    }

    if (count($rooms) === 0) {
        $rooms = array_map(static function (string $room): array {
            return ['room' => $room, 'capacity' => 0, 'events' => 0, 'requested' => 0];
        }, [
            'Academic Building 1, Room 501',
            'Academic Building 3, Ground Floor Hall',
            'Auditorium, Admin Building',
            'Computer Center, CC-3',
            'Lab 104, Science Building',
            'Library Building, Lab 203',
        ]);
    }

    ksort($rooms);

    $cache = array_values($rooms);
    return $cache;
}

/**
 * Which rooms are already taken for a given date, and in which slot?
 *
 * Approved and pending requests both block, so two clubs do not get told the
 * same room is free. The caller's own club is excluded so re-booking is
 * possible.
 *
 * @return array<string, array<int, array{club:string, event:string, status:string}>>
 */
function roomAvailability(string $date, int $ignoreClubId = 0, array $ignoreRequestIds = []): array
{
    $db  = $GLOBALS['db'];
    $busy = [];
    $ignoreRequestIds = array_values(array_unique(array_map('intval', $ignoreRequestIds)));

    $sql = "
        SELECT r.request_id, r.club_id, r.preferred_room, r.start_time, r.end_time, r.status,
               COALESCE(c.club_name, 'Unknown club') AS club_name,
               COALESCE(e.title, 'In-Club Session') AS event_title
        FROM room_requests r
        LEFT JOIN clubs c ON c.club_id = r.club_id
        LEFT JOIN events e ON e.event_id = r.event_id
        WHERE r.requested_date = ?
          AND r.status IN ('pending', 'approved')
          AND r.preferred_room IS NOT NULL AND TRIM(r.preferred_room) <> ''
    ";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $date);
    $stmt->execute();

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $clubId = (int) ($row['club_id'] ?? 0);
        if ($ignoreClubId > 0 && $clubId === $ignoreClubId) {
            continue;
        }
        // Skip rows belonging to the booking being inspected, otherwise a club's
        // own pending request is reported as somebody else holding the room.
        if (in_array((int) ($row['request_id'] ?? 0), $ignoreRequestIds, true)) {
            continue;
        }

        $room  = trim((string) $row['preferred_room']);
        $index = roomSlotIndex(roomTimePart((string) $row['start_time']) . '|' . roomTimePart((string) $row['end_time']));
        if ($index < 0) {
            continue;
        }

        $busy[$room][$index] = [
            'club'    => (string) $row['club_name'],
            'event'   => (string) $row['event_title'],
            'status'  => (string) $row['status'],
            'club_id' => $clubId,
        ];
    }

    return $busy;
}

/**
 * Rooms this club has been allocated before — used as a preference signal.
 *
 * @return array<string, int>
 */
function clubRoomHistory(int $clubId): array
{
    $db   = $GLOBALS['db'];
    $stmt = $db->prepare("
        SELECT preferred_room, COUNT(*) AS n
        FROM room_requests
        WHERE club_id = ? AND preferred_room IS NOT NULL AND TRIM(preferred_room) <> ''
        GROUP BY preferred_room
    ");
    $stmt->bind_param('i', $clubId);
    $stmt->execute();

    $history = [];
    foreach ($stmt->get_result() as $row) {
        $history[trim((string) $row['preferred_room'])] = (int) $row['n'];
    }

    return $history;
}

/**
 * Rank rooms for a date and a contiguous run of slots.
 *
 * Scoring (higher is better):
 *   +45  room is free for every requested slot
 *   +35  matches the event's own venue
 *   +30  headcount fits the room's known capacity
 *   +20  the club has used this room before
 *   +10  room is completely free for the whole day (easy to extend)
 *    -8  per already-booked slot (only used when nothing is fully free)
 *   -25  headcount exceeds the room's known capacity
 *
 * @param array<int> $slotIndices
 * @return array<int, array{room:string, capacity:int, score:int, free:bool, reasons:array<int,string>}>
 */
function recommendRooms(string $date, array $slotIndices, int $participants, int $clubId, string $preferredVenue = ''): array
{
    $catalog = roomCatalog();
    $busy    = roomAvailability($date, $clubId);
    $history = $clubId > 0 ? clubRoomHistory($clubId) : [];
    $daySlots = roomSlotCount();

    $scored = [];
    foreach ($catalog as $entry) {
        $room    = $entry['room'];
        $conflicts = [];
        foreach ($slotIndices as $index) {
            if (isset($busy[$room][$index])) {
                $conflicts[] = $index;
            }
        }

        $score   = 0;
        $reasons = [];
        $free    = count($conflicts) === 0;

        if ($free) {
            $score += 45;
        } else {
            $score -= 40 * count($conflicts);
            foreach ($conflicts as $index) {
                $reasons[] = roomTimeSlots()[$index]['label'] . ' is already taken';
            }
        }

        if ($preferredVenue !== '' && strcasecmp($room, $preferredVenue) === 0) {
            $score += 35;
            $reasons[] = 'Matches this event\'s venue';
        }

        $capacity = (int) $entry['capacity'];
        if ($participants > 0 && $capacity > 0) {
            if ($participants <= $capacity) {
                $score += 30;
                $reasons[] = 'Fits ' . $participants . ' participants (holds ' . $capacity . ')';
            } else {
                $score -= 25;
                $reasons[] = 'Known capacity is only ' . $capacity;
            }
        } elseif ($participants > 0) {
            $reasons[] = 'Capacity not recorded for this room';
        }

        if (isset($history[$room])) {
            $score += 20;
            $reasons[] = $history[$room] === 1 ? 'Used by your club before' : 'Your club\'s usual room';
        }

        $bookedToday = count($busy[$room] ?? []);
        if ($bookedToday === 0) {
            $score += 10;
            $reasons[] = 'Free for the whole day';
        }

        // Small tie-breaker so equally good rooms are ordered deterministically.
        $score += max(0, 5 - $bookedToday);

        if (!$free && $reasons === []) {
            $reasons[] = 'Partially booked';
        }

        $scored[] = [
            'room'     => $room,
            'capacity' => $capacity,
            'score'    => $score,
            'free'     => $free,
            'reasons'  => array_values(array_unique($reasons)),
        ];
    }

    usort($scored, static function (array $a, array $b): int {
        if ($a['free'] !== $b['free']) {
            return $a['free'] ? -1 : 1;
        }
        if ($a['score'] === $b['score']) {
            return strcasecmp($a['room'], $b['room']);
        }
        return $b['score'] <=> $a['score'];
    });

    return $scored;
}

/**
 * When a date is fully booked, suggest the slot run with the most free rooms.
 *
 * @return array{indices:array<int>, free:int, rooms:array<int,string>}
 */
function recommendSlotsForDate(string $date, int $participants, int $clubId, string $preferredVenue = '', int $slotCount = ROOM_MAX_SLOTS_PER_REQUEST): array
{
    $busy   = roomAvailability($date, $clubId);
    $rooms  = array_column(roomCatalog(), 'room');
    $total  = roomSlotCount();
    $slotCount = max(1, min($slotCount, ROOM_MAX_SLOTS_PER_REQUEST));

    $best = ['indices' => [], 'free' => -1, 'rooms' => []];
    for ($start = 0; $start + $slotCount - 1 < $total; $start++) {
        $indices = range($start, $start + $slotCount - 1);
        $freeRooms = [];
        foreach ($rooms as $room) {
            $ok = true;
            foreach ($indices as $index) {
                if (isset($busy[$room][$index])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $freeRooms[] = $room;
            }
        }

        $free = count($freeRooms);
        if ($free > $best['free']) {
            $ranked = recommendRooms($date, $indices, $participants, $clubId, $preferredVenue);
            $best = ['indices' => $indices, 'free' => $free, 'rooms' => array_slice($ranked, 0, 3)];
        }
    }

    if ($best['free'] < 0) {
        $best = ['indices' => [0], 'free' => 0, 'rooms' => []];
    }

    return $best;
}

/**
 * Build the top-N suggestion shown by the club's "Recommend a room" button.
 *
 * @return array{
 *   date:string, indices:array<int>, slots:array<int,string>, primary:?array,
 *   alternatives:array<int, array>, fullyBooked:bool, preferredVenue:string,
 *   participantCount:int, slotOptions:array<int, array>
 * }
 */
function roomRecommendation(string $date, int $participants, int $clubId, string $preferredVenue = '', int $slotCount = ROOM_MAX_SLOTS_PER_REQUEST): array
{
    $slotOptions = [];
    foreach (roomTimeSlots() as $i => $slot) {
        $slotOptions[$i] = $slot;
    }

    $candidates = [];
    for ($start = 0; $start + $slotCount - 1 < roomSlotCount(); $start++) {
        $candidates[] = range($start, $start + $slotCount - 1);
    }

    $bestOverall = null;
    foreach ($candidates as $indices) {
        $ranked = recommendRooms($date, $indices, $participants, $clubId, $preferredVenue);
        $top    = $ranked[0] ?? null;
        if ($top === null) {
            continue;
        }
        $key = ($top['free'] ? 1 : 0) * 100000 + $top['score'];
        if ($bestOverall === null || $key > $bestOverall['key']) {
            $bestOverall = ['key' => $key, 'indices' => $indices, 'ranked' => $ranked];
        }
    }

    $indices  = $bestOverall['indices'] ?? [0];
    $ranked   = $bestOverall['ranked'] ?? [];
    $primary  = $ranked[0] ?? null;
    $free     = array_values(array_filter($ranked, static function (array $r): bool {
        return $r['free'];
    }));
    $taken    = array_values(array_filter($ranked, static function (array $r): bool {
        return !$r['free'];
    }));

    $labels = [];
    foreach ($indices as $index) {
        $labels[] = $slotOptions[$index]['label'];
    }

    return [
        'date'            => $date,
        'indices'         => $indices,
        'slots'           => $labels,
        'primary'         => $primary,
        'alternatives'    => array_slice($free, 1, 2),
        'conflicts'       => array_slice($taken, 0, 2),
        'fullyBooked'     => count($free) === 0,
        'preferredVenue'  => $preferredVenue,
        'participantCount' => $participants,
        'slotOptions'     => $slotOptions,
    ];
}

/**
 * Does any *other* club already hold this room on this date for one of these
 * slots? Used to refuse a request that would double-book a room.
 *
 * @param array<int> $slotIndices
 * @return array<int, array{club:string, start:string, end:string, status:string}> conflicting rows
 */
function roomSlotConflicts(string $date, string $room, array $slotIndices, int $ignoreClubId = 0, array $ignoreRequestIds = []): array
{
    $indices = array_values(array_unique(array_map('intval', $slotIndices)));
    $ignoreRequestIds = array_values(array_unique(array_map('intval', $ignoreRequestIds)));
    if (count($indices) === 0 || trim($room) === '') {
        return [];
    }

    $times = [];
    foreach ($indices as $index) {
        [$start, $end] = roomSlotDatabaseTimes(roomSlotKey($index));
        if ($start !== '' && $end !== '') {
            $times[] = [$start, $end];
        }
    }
    if (count($times) === 0) {
        return [];
    }

    // Explicit OR pairs rather than a row-constructor IN list, which MariaDB
    // rejects in a prepared statement.
    $slotClauses = [];
    foreach ($times as $pair) {
        $slotClauses[] = '(r.start_time = ? AND r.end_time = ?)';
    }

    $sql = "
        SELECT r.request_id, r.start_time, r.end_time, r.status, c.club_name
        FROM room_requests r
        LEFT JOIN clubs c ON c.club_id = r.club_id
        WHERE r.requested_date = ?
          AND r.status IN ('pending', 'approved')
          AND r.preferred_room = ?
          AND r.club_id <> ?
          AND (" . implode(' OR ', $slotClauses) . ")
        ORDER BY r.start_time
    ";

    $stmt = $GLOBALS['db']->prepare($sql);
    $types = 'sis';
    $args  = [$date, trim($room), $ignoreClubId];
    foreach ($times as $pair) {
        $types .= 'ss';
        $args[]  = $pair[0];
        $args[]  = $pair[1];
    }
    $stmt->bind_param($types, ...$args);
    $stmt->execute();

    $rows = [];
    foreach ($stmt->get_result() as $row) {
        // Never report the rows of the booking being inspected against itself.
        if (in_array((int) ($row['request_id'] ?? 0), $ignoreRequestIds, true)) {
            continue;
        }

        $rows[] = [
            'club'   => (string) ($row['club_name'] ?? 'Another club'),
            'start'  => (string) $row['start_time'],
            'end'    => (string) $row['end_time'],
            'status' => (string) $row['status'],
        ];
    }

    return $rows;
}

/**
 * The room × slot availability matrix for a date, ready for rendering.
 *
 * @return array{date:string, rooms:array<int, array{room:string, capacity:int, cells:array<int, array{free:bool, club:string, status:string}>}>, slots:array<int, array{start:string, end:string, label:string}>}
 */
function roomAvailabilityGrid(string $date, int $ignoreClubId = 0, array $ignoreRequestIds = []): array
{
    $busy = roomAvailability($date, $ignoreClubId, $ignoreRequestIds);
    $out  = ['date' => $date, 'rooms' => [], 'slots' => roomTimeSlots()];

    foreach (roomCatalog() as $entry) {
        $room  = $entry['room'];
        $cells = [];
        foreach (roomTimeSlots() as $i => $slot) {
            $hit        = $busy[$room][$i] ?? null;
            $cells[$i] = [
                'free'   => $hit === null,
                'club'   => $hit['club'] ?? '',
                'status' => $hit['status'] ?? '',
            ];
        }
        $out['rooms'][] = [
            'room'     => $room,
            'capacity' => (int) $entry['capacity'],
            'cells'    => $cells,
        ];
    }

    return $out;
}

/**
 * Group request rows into booking batches, newest first.
 *
 * @param array<int, array<string,mixed>> $rows
 * @return array<int, array{key:string, rows:array<int,array<string,mixed>>, club:string, event:string, date:string, room:string, participants:int, reason:string, status:string, created_at:string, reviewed_at:?string, review_notes:string, request_ids:array<int>}>
 */
function roomRequestBatches(array $rows): array
{
    $batches = [];
    $order   = [];

    foreach ($rows as $row) {
        $key = roomRequestBatchKey($row);
        if (!isset($batches[$key])) {
            $batches[$key] = [
                'key'          => $key,
                'rows'         => [],
                'request_ids'  => [],
                'club'         => (string) ($row['club_name'] ?? 'Unknown club'),
                'event'        => (string) ($row['event_title'] ?? ''),
                'event_id'     => (int) ($row['event_id'] ?? 0),
                'date'         => (string) ($row['requested_date'] ?? ''),
                'room'         => trim((string) ($row['preferred_room'] ?? '')),
                'participants' => (int) ($row['expected_participants'] ?? 0),
                'reason'       => (string) ($row['reason'] ?? ''),
                'status'       => (string) ($row['status'] ?? 'pending'),
                'created_at'   => (string) ($row['created_at'] ?? ''),
                'reviewed_at'  => $row['reviewed_at'] ?? null,
                'review_notes' => (string) ($row['review_notes'] ?? ''),
            ];
            $order[] = $key;
        }

        $batches[$key]['rows'][] = $row;
        $batches[$key]['request_ids'][] = (int) $row['request_id'];

        // A batch is only as open as its most open row, and its notes are
        // taken from whichever row actually carries a decision.
        if ($row['status'] === 'pending') {
            $batches[$key]['status'] = 'pending';
        }
        if (!empty($row['review_notes'])) {
            $batches[$key]['review_notes'] = (string) $row['review_notes'];
        }
        if (!empty($row['reviewed_at'])) {
            $batches[$key]['reviewed_at'] = $row['reviewed_at'];
        }
    }

    // Newest batch first, then newest slot inside a batch.
    usort($order, static function (string $a, string $b) use ($batches): int {
        return strcmp((string) $batches[$b]['created_at'], (string) $batches[$a]['created_at']);
    });

    $result = [];
    foreach ($order as $key) {
        $rowsForBatch = $batches[$key]['rows'];
        usort($rowsForBatch, static function (array $x, array $y): int {
            return strcmp((string) $x['start_time'], (string) $y['start_time']);
        });
        $batches[$key]['rows']         = $rowsForBatch;
        $batches[$key]['slot_summary'] = roomBatchSlotSummary($rowsForBatch);
        $result[]                      = $batches[$key];
    }

    return $result;
}

/**
 * Group rows written by one two-slot submission.
 *
 * Both rows of a multi-slot booking share club, event, date, room and the
 * second-level `created_at` timestamp of the transaction that wrote them, so
 * those four columns plus the timestamp identify a booking batch.
 */
function roomRequestBatchKey(array $row): string
{
    return implode('|', [
        (int) ($row['club_id'] ?? 0),
        (int) ($row['event_id'] ?? 0),
        (string) ($row['requested_date'] ?? ''),
        trim((string) ($row['preferred_room'] ?? '')),
        (string) ($row['created_at'] ?? ''),
    ]);
}

/**
 * Collect every request_id that belongs to the same booking batch.
 *
 * @return array<int>
 */
function roomRequestBatchIds(array $row, ?mysqli $db = null): array
{
    $db = $db ?? $GLOBALS['db'];

    $stmt = $db->prepare("
        SELECT request_id
        FROM room_requests
        WHERE club_id = ?
          AND COALESCE(event_id, 0) = ?
          AND requested_date = ?
          AND COALESCE(preferred_room, '') = ?
          AND created_at = ?
        ORDER BY request_id
    ");
    $stmt->bind_param(
        'isiss',
        (int) $row['club_id'],
        (int) ($row['event_id'] ?? 0),
        (string) $row['requested_date'],
        (string) ($row['preferred_room'] ?? ''),
        (string) $row['created_at']
    );
    $stmt->execute();

    $ids = [];
    foreach ($stmt->get_result() as $r) {
        $ids[] = (int) $r['request_id'];
    }

    return $ids;
}

/**
 * Human summary of a booking batch: "1:50 - 3:10 PM, 3:10 - 4:30 PM".
 *
 * Accepts both database rows (start_time/end_time) and synthetic slot rows
 * (start/end), so callers can summarise a batch before it has been written.
 */
function roomBatchSlotSummary(array $rows): string
{
    $labels = [];
    foreach ($rows as $row) {
        $start = (string) ($row['start_time'] ?? $row['start'] ?? '');
        $end   = (string) ($row['end_time'] ?? $row['end'] ?? '');
        if ($start === '' || $end === '') {
            continue;
        }

        $label = roomSlotLabel($start, $end);
        if (!in_array($label, $labels, true)) {
            $labels[] = $label;
        }
    }

    return implode(' + ', $labels);
}

/**
 * All room requests, joined and grouped into one entry per booking batch.
 *
 * Batches are assembled in PHP rather than SQL because the grouping key spans
 * five columns and there is no batch id to group on.
 *
 * @return array<int, array<string, mixed>>
 */
function roomBatchesForAdmin(?string $status = null, ?string $search = null): array
{
    $db = $GLOBALS['db'];

    $sql = "
        SELECT r.request_id, r.club_id, r.event_id, r.requested_date, r.start_time, r.end_time,
               r.expected_participants, r.preferred_room, r.reason, r.status,
               r.review_notes, r.reviewed_at, r.reviewed_by, r.created_at,
               c.club_name, e.title AS event_title
        FROM room_requests r
        LEFT JOIN clubs c  ON c.club_id = r.club_id
        LEFT JOIN events e ON e.event_id = r.event_id
        ORDER BY r.created_at DESC, r.start_time ASC
    ";

    $result = $db->query($sql);
    $rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $batches = roomRequestBatches($rows);

    if ($status !== null && $status !== '') {
        $batches = array_values(array_filter($batches, static function (array $b) use ($status): bool {
            return $b['status'] === $status;
        }));
    }

    if ($search !== null && trim($search) !== '') {
        // strtolower, not mb_strtolower: mbstring is not guaranteed to exist.
        $needle = strtolower(trim($search));
        $batches = array_values(array_filter($batches, static function (array $b) use ($needle): bool {
            $haystack = strtolower($b['club'] . ' ' . $b['event'] . ' ' . $b['room']);
            return str_contains($haystack, $needle);
        }));
    }

    return $batches;
}

/**
 * Approve or decline every row of one booking batch in a single transaction.
 *
 * Rows are re-locked with FOR UPDATE and the batch key is re-derived from the
 * locked rows, so a tampered key cannot reach rows outside the batch. A batch
 * is only actionable when every one of its rows is still pending, which keeps
 * the two slots of a two-slot booking from being decided separately.
 *
 * @return array{ok: bool, message: string, updated: int}
 */
function roomReviewBatch(string $batchKey, string $action, string $notes, int $adminId): array
{
    if (!in_array($action, ['approve', 'decline'], true)) {
        return ['ok' => false, 'message' => 'Unknown review action.', 'updated' => 0];
    }

    $db = $GLOBALS['db'];
    $db->begin_transaction();

    try {
        $stmt = $db->prepare("
            SELECT r.request_id, r.club_id, r.event_id, r.requested_date,
                   r.preferred_room, r.status, r.created_at
            FROM room_requests r
            WHERE r.status = 'pending'
            FOR UPDATE
        ");
        $stmt->execute();
        $pending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $batch = [];
        foreach ($pending as $row) {
            if (roomRequestBatchKey($row) === $batchKey) {
                $batch[] = $row;
            }
        }

        if (count($batch) === 0) {
            $db->rollback();
            return ['ok' => false, 'message' => 'That booking is no longer pending, so nothing was changed.', 'updated' => 0];
        }

        $ids = array_map(static fn(array $r): int => (int) $r['request_id'], $batch);
        $in  = implode(',', array_fill(0, count($ids), '?'));

        $status = $action === 'approve' ? 'approved' : 'declined';

        // Bind in the order the placeholders appear in the SQL text: the three
        // SET values first, then one value per id in the IN list.
        $types = 'ssi' . str_repeat('i', count($ids));
        $args  = [$status, $notes, $adminId];
        foreach ($ids as $id) {
            $args[] = $id;
        }

        $update = $db->prepare("
            UPDATE room_requests
            SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE request_id IN ($in) AND status = 'pending'
        ");
        $update->bind_param($types, ...$args);
        $update->execute();
        $affected = $update->affected_rows;
        $update->close();

        if ($affected !== count($ids)) {
            // Another reviewer decided part of this batch mid-transaction.
            $db->rollback();
            return ['ok' => false, 'message' => 'This booking changed while you were reviewing it. Reload and try again.', 'updated' => 0];
        }

        $db->commit();

        $verb = $action === 'approve' ? 'approved' : 'declined';
        return [
            'ok'      => true,
            'message' => 'Booking ' . $verb . ' for all ' . count($ids) . ' slot' . (count($ids) > 1 ? 's' : '') . '.',
            'updated' => $affected,
        ];
    } catch (Throwable $e) {
        $db->rollback();
        error_log('roomReviewBatch failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'The review could not be saved. Please try again.', 'updated' => 0];
    }
}
