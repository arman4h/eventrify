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

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<article class="card p-8 max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"><?= ucfirst(e($event['status'])) ?></span>
        <a href="<?= url('/') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to all events</a>
    </div>

    <h1 class="text-3xl font-extrabold text-gray-900 mb-4"><?= e($event['title']) ?></h1>

    <dl class="grid sm:grid-cols-2 gap-4 mb-6 text-sm">
        <div class="p-4 bg-gray-50 rounded-lg">
            <dt class="text-gray-500">Date &amp; Time</dt>
            <dd class="font-semibold text-gray-900 mt-1"><?= formatDate($event['event_date'], 'M d, Y h:i A') ?></dd>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <dt class="text-gray-500">Venue</dt>
            <dd class="font-semibold text-gray-900 mt-1"><?= e($event['venue']) ?></dd>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <dt class="text-gray-500">Capacity</dt>
            <dd class="font-semibold text-gray-900 mt-1"><?= (int) $event['capacity'] ?> seats</dd>
        </div>
    </dl>

    <h2 class="text-lg font-semibold text-gray-900 mb-2">About this event</h2>
    <p class="text-gray-700 leading-relaxed mb-8"><?= e($event['description']) ?></p>

    <div class="flex items-center gap-3 border-t border-gray-200 pt-6">
        <?php if (isLoggedIn()): ?>
            <a href="<?= url(currentUserRole() === 'admin' ? '/admin' : '/club') ?>" class="btn-primary">Go to Dashboard</a>
        <?php else: ?>
            <a href="<?= url('/login') ?>" class="btn-primary">Login to Participate</a>
            <a href="<?= url('/register') ?>" class="btn-secondary">Create an Account</a>
        <?php endif; ?>
    </div>
</article>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>