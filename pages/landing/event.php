<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$eventId = (int) get('event_id');

$stmt = $db->prepare("
    SELECT e.*, c.club_name
    FROM events e
    LEFT JOIN clubs c ON c.club_id = e.club_id
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

<<<<<<< HEAD
$errors = [];

if (isPost() && post('action') === 'register_event') {
    $errors = [];
    $guestName       = post('guest_name');
    $guestStudentId  = post('guest_student_id');

    if ($guestName === '') {
        $errors[] = 'Please enter your full name.';
    }

    if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) {
        $errors[] = 'Registration for this event has closed.';
    }

    if ($event['capacity'] > 0) {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS registered_count
            FROM event_registrations
            WHERE event_id = ? AND status NOT IN ('cancelled', 'no_show')
        ");
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $registeredCount = (int) $stmt->get_result()->fetch_assoc()['registered_count'];
    } else {
        $registeredCount = 0;
    }

    $associateStudentId = (isLoggedIn() && isStudent()) ? currentUserId() : null;

    if (empty($errors)) {
        if ($associateStudentId) {
            $stmt = $db->prepare("
                SELECT 1 FROM event_registrations
                WHERE event_id = ? AND student_id = ? AND status NOT IN ('cancelled')
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
                SELECT 1 FROM event_registrations
                WHERE event_id = ? AND guest_student_id = ? AND status NOT IN ('cancelled')
                LIMIT 1
            ");
            $stmt->bind_param('is', $eventId, $guestStudentId);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = 'This student ID is already registered for this event.';
            }
        }
    }

    if (empty($errors)) {
        if ($event['capacity'] > 0 && $registeredCount >= $event['capacity']) {
            $status            = 'waitlisted';
            $waitlistPosition  = $registeredCount - $event['capacity'] + 1;
        } else {
            $status            = 'registered';
            $waitlistPosition  = null;
        }

        $stmt = $db->prepare("
            INSERT INTO event_registrations
                (event_id, student_id, guest_name, guest_student_id, is_walkin, status, waitlist_position)
            VALUES
                (?, NULLIF(?, 0), ?, NULLIF(?, ''), 0, ?, NULLIF(?, 0))
        ");

        $bindEventId     = $eventId;
        $bindStudentId   = (int) $associateStudentId;
        $bindStudentId   = $bindStudentId === 0 ? null : $bindStudentId;
        $bindGuestName   = $guestName;
        $bindGuestStud   = $guestStudentId;
        $bindStatus      = $status;
        $bindWaitlistPos = $waitlistPosition === null ? null : (int) $waitlistPosition;

        $stmt->bind_param(
            'iisssi',
            $bindEventId,
            $bindStudentId,
            $bindGuestName,
            $bindGuestStud,
            $bindStatus,
            $bindWaitlistPos
        );

        if ($stmt->execute()) {
            setOld([]);
            if ($status === 'waitlisted') {
                $_SESSION['flash']['success'] = 'The event is full, so you have been placed on the waitlist at position #' . $waitlistPosition . '.';
            } else {
                $_SESSION['flash']['success'] = 'You have successfully registered for this event!';
            }
            redirect('/event?event_id=' . $eventId);
        } else {
            $errors[] = 'Registration failed. Please try again.';
=======
$registeredCount = 0;
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM registrations WHERE event_id = ? AND status = 'registered'");
if ($countStmt) {
    $countStmt->bind_param('i', $eventId);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $registeredCount = (int)$countResult['total'];
}

$totalCapacity = (int)$event['capacity'];
$availableSeats = $totalCapacity - $registeredCount;
if ($availableSeats < 0) {
    $availableSeats = 0;
}

$isAlreadyRegistered = false;
$isAlreadyWaitlisted = false;

if (isLoggedIn() && currentUserRole() === 'student') {
    $studentId = $_SESSION['user_id'] ?? 0;
    
    $checkReg = $db->prepare("SELECT status FROM registrations WHERE event_id = ? AND student_id = ?");
    $checkReg->bind_param('ii', $eventId, $studentId);
    $checkReg->execute();
    $regResult = $checkReg->get_result()->fetch_assoc();
    
    if ($regResult) {
        if ($regResult['status'] === 'registered') {
            $isAlreadyRegistered = true;
        } elseif ($regResult['status'] === 'waitlisted') {
            $isAlreadyWaitlisted = true;
>>>>>>> 2a383e58e40a56eb11f24442d52feade91b84c50
        }
    }
}

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<article class="card p-8 max-w-3xl mx-auto mb-8">
    <div class="flex items-center justify-between mb-4">
<<<<<<< HEAD
        <?php if ($event['category']): ?>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"><?= e($event['category']) ?></span>
        <?php else: ?>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Event</span>
        <?php endif; ?>
        <a href="<?= url('/events') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to all events</a>
=======
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 uppercase tracking-wide"><?= ucfirst(e($event['status'])) ?></span>
        <a href="<?= url('/') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to all events</a>
>>>>>>> 2a383e58e40a56eb11f24442d52feade91b84c50
    </div>

    <h1 class="text-3xl font-extrabold text-gray-900 mb-2"><?= e($event['title']) ?></h1>
    <?php if ($event['club_name']): ?>
    <p class="text-sm font-semibold text-primary-600 mb-4">Hosted by <?= e($event['club_name']) ?></p>
    <?php endif; ?>

    <dl class="grid sm:grid-cols-2 gap-4 mb-6 text-sm">
        <div class="p-4 bg-gray-50 rounded-lg">
<<<<<<< HEAD
            <dt class="text-gray-500">Start Time</dt>
            <dd class="font-semibold text-gray-900 mt-1"><?= formatDate($event['start_time'], 'M d, Y h:i A') ?></dd>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <dt class="text-gray-500">End Time</dt>
            <dd class="font-semibold text-gray-900 mt-1"><?= $event['end_time'] ? formatDate($event['end_time'], 'M d, Y h:i A') : '—' ?></dd>
=======
            <dt class="text-gray-500 font-medium">📅 Date &amp; Time</dt>
            <dd class="font-bold text-gray-900 mt-1"><?= formatDate($event['event_date'], 'M d, Y h:i A') ?></dd>
>>>>>>> 2a383e58e40a56eb11f24442d52feade91b84c50
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <dt class="text-gray-500 font-medium">📍 Venue</dt>
            <dd class="font-bold text-gray-900 mt-1"><?= e($event['venue']) ?></dd>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg sm:col-span-2">
            <dt class="text-gray-500 font-medium">👥 Registration Status</dt>
            <dd class="font-bold text-gray-900 mt-1 flex items-center gap-2">
                <span><?= $availableSeats ?> / <?= $totalCapacity ?> Seats Available</span>
                <?php if ($availableSeats === 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Waitlist Active</span>
                <?php endif; ?>
            </dd>
        </div>
        <?php if ($event['registration_deadline']): ?>
        <div class="p-4 bg-gray-50 rounded-lg sm:col-span-2">
            <dt class="text-gray-500">Registration Deadline</dt>
            <dd class="font-semibold text-gray-900 mt-1"><?= formatDate($event['registration_deadline'], 'M d, Y h:i A') ?></dd>
        </div>
        <?php endif; ?>
    </dl>

    <h2 class="text-lg font-semibold text-gray-900 mb-2">About this event</h2>
    <p class="text-gray-700 leading-relaxed mb-4"><?= e($event['description']) ?></p>

<<<<<<< HEAD
    <div class="border-t border-gray-200 pt-6">
        <a href="<?= url('/events') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to all events</a>
    </div>
</article>

<section class="card p-8 max-w-3xl mx-auto">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Register for this event</h2>
        <p class="text-sm text-gray-500 mt-1">
            <?php if ($event['capacity'] > 0): ?>
                <?= (int) $event['capacity'] ?> seats available. Fill in your details below to confirm your spot.
            <?php else: ?>
                Fill in your details below to confirm your spot.
            <?php endif; ?>
        </p>
    </div>

    <?php if (flash('success')): ?>
    <div class="mb-4">
        <?php
        $alertType = 'success';
        $alertMessage = flash('success');
        require_once BASE_PATH . '/app/components/alert.php';
        ?>
    </div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
    <div class="mb-2">
        <?php
        $alertType = 'error';
        $alertMessage = $error;
        require BASE_PATH . '/app/components/alert.php';
        ?>
    </div>
    <?php endforeach; ?>

    <?php if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()): ?>
    <div class="mb-2">
        <?php
        $alertType = 'error';
        $alertMessage = 'Registration for this event has closed.';
        require BASE_PATH . '/app/components/alert.php';
        ?>
    </div>
    <?php else: ?>
    <form method="POST" action="<?= url('/event?event_id=' . $eventId) ?>" class="space-y-4">
        <input type="hidden" name="action" value="register_event">

        <div>
            <label class="label">Full Name <span class="text-red-500">*</span></label>
            <input
                type="text"
                name="guest_name"
                class="input"
                placeholder="John Doe"
                value="<?= e(post('guest_name') ? post('guest_name') : (isStudent() ? currentUser()['name'] : '')) ?>"
                required
            >
        </div>

        <div>
            <label class="label">Student ID</label>
            <input
                type="text"
                name="guest_student_id"
                class="input"
                placeholder="e.g. 011 231 456"
                value="<?= e(post('guest_student_id')) ?>"
            >
            <p class="text-xs text-gray-500 mt-1">Optional — provide it if you'd like us to link this registration to your university ID.</p>
        </div>

        <div>
            <button type="submit" class="btn-primary w-full">Register Now</button>
        </div>
    </form>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>
=======
    <div class="flex items-center gap-3 border-t border-gray-200 pt-6">
        <?php if (isLoggedIn()): ?>

        <?php endif; ?>
    </div>
</article>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>
>>>>>>> 2a383e58e40a56eb11f24442d52feade91b84c50
