<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$eventId = (int) (isPost() ? post('event_id') : get('event_id'));

$stmt = $db->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param('i', $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    flash('error', 'That event no longer exists.');
    redirect('/admin/events');
}

// ── Act ────────────────────────────────────────────────────────
if (isPost()) {
    $reason = post('reason');
    $errors = [];

    vAdd($errors, vMaxLen($reason, 255, 'Reason'));

    if ($errors) {
        flash('error', implode("\n", $errors));
    } else {
        // The blocker is re-checked inside the helper; this only saves the
        // admin a pointless confirmation screen.
        $blocker = eventDeleteBlocker($db, $event);

        if ($blocker !== null) {
            flash('error', 'This event cannot be deleted yet. ' . $blocker);
        } else {
            $result = archiveAndDeleteEvent($db, $eventId, (int) currentUserId(), $reason);

            if ($result['ok']) {
                flash('success', $result['message']);
            } else {
                flash('error', $result['message']);
            }
        }
    }

    redirect('/admin/events');
}

// ── Confirm ────────────────────────────────────────────────────
$pageTitle   = 'Delete Event';
$activePage  = 'events';
$blocker     = eventDeleteBlocker($db, $event);

$regStmt = $db->prepare("SELECT COUNT(*) AS n FROM event_registrations WHERE event_id = ?");
$regStmt->bind_param('i', $eventId);
$regStmt->execute();
$regCount = (int) ($regStmt->get_result()->fetch_assoc()['n'] ?? 0);

$clubId   = (int) $event['club_id'];
$clubStmt = $db->prepare("SELECT club_name FROM clubs WHERE club_id = ?");
$clubStmt->bind_param('i', $clubId);
$clubStmt->execute();
$clubName = (string) ($clubStmt->get_result()->fetch_assoc()['club_name'] ?? '—');

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <nav class="breadcrumb mb-6">
            <a href="<?= url('/admin/events') ?>" class="breadcrumb-link">Events</a>
            <span class="text-gray-400">/</span>
            <span class="breadcrumb-current">Delete Event</span>
        </nav>

        <div class="page-header mb-6">
            <div>
                <h1 class="page-title">Delete Event</h1>
                <p class="page-subtitle">Remove this event from the platform and keep a copy in the archive.</p>
            </div>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="card p-6 sm:p-8 max-w-2xl space-y-6">

            <div class="flex gap-4">
                <?php if (!empty($event['poster'])): ?>
                    <img src="<?= e($event['poster']) ?>" alt="" class="w-28 h-28 rounded-lg object-cover border border-gray-200 shrink-0">
                <?php else: ?>
                    <span class="inline-flex w-28 h-28 rounded-lg bg-gray-100 items-center justify-center text-gray-400 shrink-0">
                        <?= icon('calendar', 'w-8 h-8') ?>
                    </span>
                <?php endif; ?>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900"><?= e($event['title']) ?></h2>
                    <p class="text-sm text-gray-500 mt-1"><?= e($clubName) ?></p>
                    <p class="text-sm text-gray-500 mt-1">
                        <?= e(formatDate($event['start_time'])) ?>
                        &middot; <?= e($event['venue'] ?: 'No venue') ?>
                        &middot; <?= (int) $event['capacity'] ?> capacity
                    </p>
                    <div class="mt-2">
                        <?php
                        $badgeType = $event['status'];
                        $badgeText = '';
                        require BASE_PATH . '/app/components/badge.php';
                        ?>
                    </div>
                </div>
            </div>

            <?php if ($blocker !== null): ?>
                <div class="alert-error" role="alert">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 shrink-0"><?= icon('alert', 'w-5 h-5') ?></span>
                        <div>
                            <p class="text-sm font-medium">This event cannot be deleted yet.</p>
                            <p class="text-sm mt-1"><?= e($blocker) ?></p>
                        </div>
                    </div>
                </div>
                <a href="<?= url('/admin/events') ?>" class="btn-secondary">Back to events</a>
            <?php else: ?>
                <?php
                $alertType = 'warning';
                $alertMessage = 'The event is removed from every public page and from the club dashboard straight away. '
                    . ($regCount > 0
                        ? sprintf('Its %d archived %s and the event details are kept in an admin-only history.',
                            $regCount, $regCount === 1 ? 'registration' : 'registrations')
                        : 'A copy of the event details is kept in an admin-only history.');
                require BASE_PATH . '/app/components/alert.php';
                ?>

                <form method="POST" action="<?= url('/admin/events/delete') ?>" class="space-y-5">
                    <?= csrfField() ?>
                    <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">

                    <div class="form-group">
                        <label class="label" for="reason">Reason <span class="form-hint">(optional, recorded in the history)</span></label>
                        <textarea name="reason" id="reason" rows="3" class="textarea" maxlength="255"
                                  placeholder="e.g. Duplicate of the AI Workshop, removed at the club's request."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="btn-danger" data-confirm="Delete &quot;<?= e($event['title']) ?>&quot;? It will disappear from the site immediately.">
                            Delete Event
                        </button>
                        <a href="<?= url('/admin/events') ?>" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>
