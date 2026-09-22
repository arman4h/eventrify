<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$eventId = (int) get('event_id');

$stmt = $db->prepare("
    SELECT e.*, c.club_name, c.description AS club_description
    FROM events e
    JOIN clubs c ON c.club_id = e.club_id
    WHERE e.event_id = ? AND e.status = 'published'
    LIMIT 1
");
$stmt->bind_param('i', $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    abort(404, 'Event not found');
}

$pageTitle = $event['title'];

$errors = [];

$fStmt = $db->prepare("SELECT * FROM event_registration_fields WHERE event_id = ? ORDER BY display_order");
$fStmt->bind_param('i', $eventId);
$fStmt->execute();
$regFields = $fStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$hasCustomFields = false;
foreach ($regFields as $f) {
    if (!in_array($f['field_label'], ['Full Name', 'Email', 'Student ID', 'Department'], true)) {
        $hasCustomFields = true;
        break;
    }
}

$studentSession = isStudent() ? currentUser() : null;
$responsesOld = $_POST['responses'] ?? [];
$fieldDefaults = [];
foreach ($regFields as $f) {
    $value = '';
    if (isset($responsesOld[$f['field_id']])) {
        $value = $responsesOld[$f['field_id']];
    } elseif ($studentSession) {
        $value = match ($f['field_label']) {
            'Full Name' => $studentSession['name'] ?? '',
            'Email' => $studentSession['email'] ?? '',
            'Student ID' => $studentSession['university_id'] ?? '',
            'Department' => departmentCode($studentSession['department'] ?? ''),
            default => '',
        };
    }
    $fieldDefaults[$f['field_id']] = $value;
}

$fieldInput = function (array $f) use ($fieldDefaults): string {
    $id = (int) $f['field_id'];
    $val = $fieldDefaults[$id] ?? '';
    $required = $f['is_required'] ? ' required' : '';

    switch ($f['field_type']) {
        case 'dropdown':
            $options = $f['field_options'] ? explode(',', $f['field_options']) : [];
            $html = "<select name=\"responses[$id]\" class=\"select\"$required>";
            $html .= '<option value="">Select...</option>';
            foreach ($options as $opt) {
                $opt = trim($opt);
                if ($opt === '') continue;
                $html .= '<option value="' . e($opt) . '"' . ((string) $val === $opt ? ' selected' : '') . '>' . e($opt) . '</option>';
            }
            return $html . '</select>';
        case 'checkbox':
            return '<input type="checkbox" name="responses[' . $id . ']" value="1" class="rounded border-gray-300"' . (!empty($val) ? ' checked' : '') . '>';
        case 'date':
            return '<input type="date" name="responses[' . $id . ']" class="input" value="' . e($val) . '"' . $required . '>';
        case 'number':
            return '<input type="number" name="responses[' . $id . ']" class="input" value="' . e($val) . '"' . $required . '>';
        case 'email':
            return '<input type="email" name="responses[' . $id . ']" class="input" value="' . e($val) . '"' . $required . '>';
        default:
            return '<input type="text" name="responses[' . $id . ']" class="input" value="' . e($val) . '"' . $required . '>';
    }
};

if (isPost() && post('action') === 'register_event') {
    $responses = $_POST['responses'] ?? [];

    $guestName = '';
    $guestEmail = '';
    $guestStudentId = '';
    $guestDepartment = '';
    $guestPhone = '';

    foreach ($regFields as $f) {
        $val = isset($responses[$f['field_id']]) ? trim((string) $responses[$f['field_id']]) : '';
        if ($f['is_required'] && $val === '') {
            $errors[] = $f['field_label'] . ' is required.';
        }
        $label = strtolower(trim($f['field_label']));
        if ($label === 'full name') $guestName = $val;
        if ($label === 'email') $guestEmail = $val;
        if ($label === 'student id') $guestStudentId = $val;
        if ($label === 'department') $guestDepartment = $val;
        if (strpos($label, 'phone') === 0) $guestPhone = $val;
    }

    if ($guestEmail !== '' && !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) {
        $errors[] = 'Registration for this event has closed.';
    }

    $countStmt = $db->prepare("
        SELECT COUNT(*) AS registered_count
        FROM event_registrations
        WHERE event_id = ?
          AND status IN ('registered', 'attended')
    ");
    $countStmt->bind_param('i', $eventId);
    $countStmt->execute();
    $registeredCount = (int) $countStmt->get_result()->fetch_assoc()['registered_count'];

    $associateStudentId = (isLoggedIn() && isStudent()) ? currentUserId() : null;

    if (empty($errors)) {
        if ($associateStudentId) {
            $stmt = $db->prepare("
                SELECT 1
                FROM event_registrations
                WHERE event_id = ? AND student_id = ?
                  AND status NOT IN ('cancelled')
                LIMIT 1
            ");
            $stmt->bind_param('ii', $eventId, $associateStudentId);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = 'You are already registered for this event.';
            }
        }

        if ($guestStudentId !== '') {
            $stmt = $db->prepare("
                SELECT 1
                FROM event_registrations
                WHERE event_id = ? AND guest_student_id = ?
                  AND status NOT IN ('cancelled')
                LIMIT 1
            ");
            $stmt->bind_param('is', $eventId, $guestStudentId);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = 'This Student ID is already registered for this event.';
            }
        }

        if ($guestEmail !== '' && eventRegistrationValueTaken($eventId, 'email', $guestEmail)) {
            $errors[] = 'This email is already registered for this event.';
        }

        if ($guestPhone !== '' && eventRegistrationValueTaken($eventId, 'phone', $guestPhone)) {
            $errors[] = 'This phone number is already registered for this event.';
        }
    }

    if (empty($errors)) {
        $capacity = (int) $event['capacity'];
        $isFull = $capacity > 0 && $registeredCount >= $capacity;

        if ($isFull) {
            $status = 'waitlisted';
            $waitlistPosition = $registeredCount - $capacity + 1;
        } else {
            $status = 'registered';
            $waitlistPosition = null;
        }

        $stmt = $db->prepare("
            INSERT INTO event_registrations
                (event_id, student_id, guest_name, guest_student_id, is_walkin, status, waitlist_position)
            VALUES
                (?, ?, ?, ?, 0, ?, ?)
        ");

        $bindEventId = $eventId;
        $bindStudentId = $associateStudentId ?? null;
        $bindGuestName = $guestName;
        $bindGuestStud = $guestStudentId !== '' ? $guestStudentId : null;
        $bindStatus = $status;
        $bindWaitlistPos = $waitlistPosition === null ? null : (int) $waitlistPosition;

        $stmt->bind_param('iisssi', $bindEventId, $bindStudentId, $bindGuestName, $bindGuestStud, $bindStatus, $bindWaitlistPos);

        if ($stmt->execute()) {
            $registrationId = (int) $db->insert_id;

            $respStmt = $db->prepare("INSERT INTO registration_field_responses (registration_id, field_id, response_value) VALUES (?, ?, ?)");
            foreach ($regFields as $f) {
                $val = $responses[$f['field_id']] ?? '';
                if ($val === '') continue;
                $respStmt->bind_param('iis', $registrationId, $f['field_id'], $val);
                $respStmt->execute();
            }

            setOld([]);
            if ($guestEmail !== '') {
                $_SESSION['flash']['success'] = 'You have successfully registered for this event! A confirmation has been sent to ' . $guestEmail . '.';
            } elseif ($status === 'waitlisted') {
                $_SESSION['flash']['success'] = 'The event is full, so you have been placed on the waitlist at position #' . $waitlistPosition . '.';
            } else {
                $_SESSION['flash']['success'] = 'You have successfully registered for this event!';
            }

            redirect('/event?event_id=' . $eventId . '&reg=' . $registrationId);
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$countStmt = $db->prepare("
    SELECT COUNT(*) AS total
    FROM event_registrations
    WHERE event_id = ? AND status IN ('registered', 'attended')
");
$countStmt->bind_param('i', $eventId);
$countStmt->execute();
$registeredCount = (int) $countStmt->get_result()->fetch_assoc()['total'];

$capacity = (int) $event['capacity'];
$isFull = $capacity > 0 && $registeredCount >= $capacity;
$availableSeats = $capacity > 0 ? max(0, $capacity - $registeredCount) : 0;
$capacityPercent = $capacity > 0 ? (int) round($registeredCount / $capacity * 100) : 0;

$deadlinePassed = $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
$regOpen = $event['status'] !== 'cancelled' && !$deadlinePassed;

if ($event['status'] === 'cancelled' || $deadlinePassed) {
    $eventStatusLabel = 'Closed';
    $eventStatusBadge = 'badge-neutral';
} elseif ($isFull) {
    $eventStatusLabel = 'Full';
    $eventStatusBadge = 'badge-danger';
} else {
    $eventStatusLabel = 'Open';
    $eventStatusBadge = 'badge-success';
}

$alreadyRegistered = false;
$alreadyWaitlisted = false;
if (isStudent()) {
    $checkReg = $db->prepare("
        SELECT status
        FROM event_registrations
        WHERE event_id = ? AND student_id = ?
          AND status NOT IN ('cancelled')
        LIMIT 1
    ");
    $checkReg->bind_param('ii', $eventId, currentUserId());
    $checkReg->execute();
    $regResult = $checkReg->get_result()->fetch_assoc();
    if ($regResult) {
        if ($regResult['status'] === 'registered' || $regResult['status'] === 'attended') {
            $alreadyRegistered = true;
        } elseif ($regResult['status'] === 'waitlisted') {
            $alreadyWaitlisted = true;
        }
    }
}

$startTs = strtotime($event['start_time']);
$endTs = $event['end_time'] ? strtotime($event['end_time']) : $startTs + 3 * 3600;
$deadlineTs = $event['registration_deadline'] ? strtotime($event['registration_deadline']) : $startTs - 3600;

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 py-10">
    <nav class="breadcrumb">
        <a href="<?= url('/events') ?>" class="breadcrumb-link">Explore Events</a>
        <span>/</span>
        <span class="breadcrumb-current"><?= e($event['title']) ?></span>
    </nav>

    <?php
    $alertType = 'success';
    $alertMessage = flash('success');
    require BASE_PATH . '/app/components/alert.php';

    $alertType = 'error';
    $alertMessage = flash('error');
    require BASE_PATH . '/app/components/alert.php';

    foreach ($errors as $error) {
        $alertType = 'error';
        $alertMessage = $error;
        require BASE_PATH . '/app/components/alert.php';
    }
    ?>

    <div class="rounded-xl border border-gray-200 bg-gray-100 h-48 sm:h-64 flex items-center justify-center text-gray-400 relative mb-8 overflow-hidden">
        <?php if (!empty($event['poster'])): ?>
            <img src="<?= e($event['poster']) ?>" alt="<?= e($event['title']) ?> poster" class="w-full h-full object-cover">
        <?php else: ?>
            <?= icon('calendar', 'w-16 h-16') ?>
        <?php endif; ?>
        <span class="badge-primary absolute left-4 top-4"><?= e($event['category'] ?? 'Event') ?></span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900"><?= e($event['title']) ?></h1>
            <p class="text-sm text-gray-500 mt-2">
                Hosted by <span class="font-medium text-gray-900"><?= e($event['club_name']) ?></span>
                <span class="badge-primary ml-2"><?= e($event['category'] ?? 'Event') ?></span>
            </p>
        </div>
        <div>
            <?php if (!$regOpen): ?>
                <a class="btn-secondary opacity-50 pointer-events-none cursor-not-allowed">Registration Closed</a>
            <?php elseif ($alreadyRegistered): ?>
                <span class="badge-success"><?= icon('check', 'w-3.5 h-3.5') ?> You're registered</span>
            <?php elseif ($alreadyWaitlisted): ?>
                <span class="badge-warning">You're on the waitlist</span>
            <?php elseif ($isFull): ?>
                <a href="<?= url('/event?event_id=' . $eventId) . '#register' ?>" class="btn-secondary">Join Waitlist</a>
            <?php else: ?>
                <a href="<?= url('/event?event_id=' . $eventId) . '#register' ?>" class="btn-primary">Register Now</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200 rounded-xl overflow-hidden mb-10">
        <div class="bg-white p-3 sm:p-4">
            <p class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">
                <?= icon('calendar', 'w-4 h-4') ?>
                Date
            </p>
            <p class="mt-1.5 text-sm font-semibold text-gray-900"><?= formatDate($event['start_time']) ?></p>
        </div>
        <div class="bg-white p-3 sm:p-4">
            <p class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">
                <?= icon('clock', 'w-4 h-4') ?>
                Time
            </p>
            <p class="mt-1.5 text-sm font-semibold text-gray-900"><?= date('g:i A', $startTs) ?> - <?= date('g:i A', $endTs) ?></p>
        </div>
        <div class="bg-white p-3 sm:p-4">
            <p class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">
                <?= icon('map-pin', 'w-4 h-4') ?>
                Location
            </p>
            <p class="mt-1.5 text-sm font-semibold text-gray-900"><?= e($event['venue']) ?></p>
        </div>
        <div class="bg-white p-3 sm:p-4">
            <p class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">
                <?= icon('ticket', 'w-4 h-4') ?>
                Status
            </p>
            <p class="mt-1.5 text-sm font-semibold text-gray-900"><span class="<?= $eventStatusBadge ?>"><?= e($eventStatusLabel) ?></span></p>
        </div>
    </div>

    <div class="space-y-10">
        <section>
            <h2 class="text-xl font-bold text-gray-900 mb-3">About the Event</h2>
            <p class="text-gray-600 leading-relaxed whitespace-pre-line"><?= e($event['description']) ?></p>
        </section>

        <section id="register" class="card p-6 space-y-5">
            <h2 class="text-lg font-bold text-gray-900">Registration</h2>

            <div>
                <?php if ($capacity > 0): ?>
                    <p class="mt-1 text-sm text-gray-600">
                        <span class="font-semibold text-gray-900"><?= $isFull ? 0 : $availableSeats ?></span>
                        / <?= $capacity ?> seats available
                    </p>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill <?= $isFull ? 'bg-red-600' : '' ?>" style="width: <?= min(100, $capacityPercent) ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <?= $isFull ? 'Waitlist active — free spots appear as registrations are cancelled.' : $registeredCount . ' of ' . $capacity . ' seats filled.' ?>
                    </p>
                <?php else: ?>
                    <p class="mt-1 text-sm text-gray-600">No seat limit — open registration.</p>
                <?php endif; ?>

                <?php if ($event['registration_deadline']): ?>
                    <p class="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-500 flex items-center gap-2">
                        <?= icon('clock', 'w-4 h-4 text-gray-400') ?>
                        Registration closes <?= formatDate($event['registration_deadline']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <?php if ($alreadyRegistered): ?>
                    <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                        <p class="font-semibold">You're registered for this event.</p>
                        <p class="mt-1">Show your registration confirmation at check-in.</p>
                    </div>
                <?php elseif ($alreadyWaitlisted): ?>
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                        <p class="font-semibold">You're on the waitlist.</p>
                        <p class="mt-1">You'll be upgraded automatically if a spot opens up.</p>
                    </div>
                <?php elseif (!$regOpen): ?>
                    <a class="btn-secondary btn-lg w-full opacity-50 pointer-events-none cursor-not-allowed">Registration Closed</a>
                    <p class="text-xs text-gray-500 mt-3 text-center">Registration for this event has closed.</p>
                <?php else: ?>
                    <form method="POST" action="<?= url('/event?event_id=' . $eventId) ?>" class="space-y-4">
                        <input type="hidden" name="action" value="register_event">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Required Information</p>
                            <?php foreach ($regFields as $f): ?>
                                <div>
                                    <label class="label <?= $f['is_required'] ? 'label-required' : '' ?>">
                                        <?= e($f['field_label']) ?>
                                        <?php if ($f['field_type'] === 'checkbox'): ?>
                                            <span class="ml-2"><?= $fieldInput($f) ?></span>
                                        <?php else: ?>
                                            <?= $fieldInput($f) ?>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$hasCustomFields): ?>
                                <p class="text-xs text-gray-400"><?= icon('info', 'w-3.5 h-3.5 inline -mt-0.5') ?> This form is provided by the organizing club.</p>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-primary btn-lg w-full">
                            <?= $isFull ? 'Join Waitlist' : 'Register Now' ?>
                            <?= icon('arrow-right', 'w-5 h-5') ?>
                        </button>
                        <p class="text-xs text-gray-500 text-center">
                            No account needed. <a href="<?= url('/login') ?>" class="text-blue-600 hover:underline">Log in</a> to pre-fill and track your registrations.
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <div class="card p-5">
            <div class="flex items-start gap-4">
                <span class="inline-flex w-12 h-12 rounded-lg bg-blue-50 text-blue-600 items-center justify-center shrink-0"><?= icon('grad', 'w-6 h-6') ?></span>
                <div class="min-w-0">
                    <h3 class="font-semibold text-gray-900"><?= e($event['club_name']) ?></h3>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed line-clamp-2">
                        <?= e($event['club_description'] ?? 'A university club organizing events for the campus community.') ?>
                    </p>
                    <a href="<?= url('/') ?>#clubs" class="btn-secondary btn-sm mt-3">View Club</a>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Location</h3>
            <p class="text-sm text-gray-600"><?= e($event['venue']) ?></p>
            <div class="mt-4 h-40 rounded-lg bg-gray-100 border border-gray-200 flex flex-col items-center justify-center text-gray-400">
                <?= icon('map-pin', 'w-8 h-8') ?>
                <p class="text-xs mt-2">Map preview unavailable</p>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>