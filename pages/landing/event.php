<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$eventId = (int) get('event_id');
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param('i', $eventId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    abort(404, 'Event not found');
}

$pageTitle = $event['title'];

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
        }
    }
}

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<article class="card p-8 max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 uppercase tracking-wide"><?= ucfirst(e($event['status'])) ?></span>
        <a href="<?= url('/') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to all events</a>
    </div>

    <h1 class="text-3xl font-extrabold text-gray-900 mb-4"><?= e($event['title']) ?></h1>

    <dl class="grid sm:grid-cols-2 gap-4 mb-6 text-sm">
        <div class="p-4 bg-gray-50 rounded-lg">
            <dt class="text-gray-500 font-medium">📅 Date &amp; Time</dt>
            <dd class="font-bold text-gray-900 mt-1"><?= formatDate($event['event_date'], 'M d, Y h:i A') ?></dd>
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
    </dl>

    <h2 class="text-lg font-semibold text-gray-900 mb-2">About this event</h2>
    <p class="text-gray-700 leading-relaxed mb-8"><?= e($event['description']) ?></p>

    <div class="flex items-center gap-3 border-t border-gray-200 pt-6">
        <?php if (isLoggedIn()): ?>
            <?php if (currentUserRole() === 'student'): ?>
                
                <?php if ($isAlreadyRegistered): ?>
                    <button class="btn-secondary text-green-700 cursor-not-allowed font-semibold" disabled>✓ Already Registered</button>
                <?php elseif ($isAlreadyWaitlisted): ?>
                    <button class="btn-secondary text-yellow-700 cursor-not-allowed font-semibold" disabled>⌛ On Waitlist</button>
                <?php else: ?>
                    <?php if ($availableSeats > 0): ?>
                        <a href="<?= url('/register-event?event_id=' . $eventId) ?>" class="btn-primary px-6 py-2.5 rounded-xl font-semibold shadow-sm">Register Now</a>
                    <?php else: ?>
                        <a href="<?= url('/join-waitlist?event_id=' . $eventId) ?>" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 px-6 py-2.5 rounded-xl font-semibold shadow-sm transition-colors">Join Waitlist</a>
                    <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <a href="<?= url(currentUserRole() === 'admin' ? '/admin' : '/club') ?>" class="btn-primary px-6 py-2.5 rounded-xl font-semibold shadow-sm">Go to Dashboard</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?= url('/login') ?>" class="btn-primary px-6 py-2.5 rounded-xl font-semibold shadow-sm">Login to Participate</a>
            <a href="<?= url('/register') ?>" class="btn-secondary px-6 py-2.5 rounded-xl font-semibold shadow-sm">Create an Account</a>
        <?php endif; ?>
    </div>
</article>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>
