<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('attendance');

$pageTitle = 'Event Check-in';
$activePage = 'attendance';

$clubId = (int) currentUser()['club_id'];
$eventId = (int) get('event_id', 0);

$eventsList = $db->prepare("SELECT event_id, title, start_time, status FROM events WHERE club_id = ? AND status IN ('published', 'completed') ORDER BY start_time DESC");
$eventsList->bind_param('i', $clubId);
$eventsList->execute();
$eventsResult = $eventsList->get_result();
$events = [];
while ($row = $eventsResult->fetch_assoc()) {
    $events[] = $row;
}

if ($eventId === 0 && !empty($events)) {
    $eventId = (int) $events[0]['event_id'];
}

$event = null;
if ($eventId > 0) {
    $evStmt = $db->prepare("SELECT * FROM events WHERE event_id = ? AND club_id = ?");
    $evStmt->bind_param('ii', $eventId, $clubId);
    $evStmt->execute();
    $event = $evStmt->get_result()->fetch_assoc();
}

$verifiedReg = null;
$searchPerformed = false;

if (isPost()) {
    $postAction = post('action');
    $postEventId = (int) post('event_id');

    if ($postAction === 'lookup' && post('lookup_student_id') !== '' && $postEventId > 0) {
        $searchValue = trim(post('lookup_student_id'));
        $searchPerformed = true;
        $like = "%$searchValue%";
        $lookup = $db->prepare("SELECT er.*, e.title AS event_title, s.email AS student_email
            FROM event_registrations er
            JOIN events e ON e.event_id = er.event_id
            LEFT JOIN students s ON s.university_id = er.guest_student_id
            WHERE er.event_id = ? AND e.club_id = ?
              AND (er.guest_student_id = ? OR er.guest_name LIKE ? OR LOWER(COALESCE(s.email, '')) LIKE LOWER(?))
            LIMIT 1");
        $lookup->bind_param('iisss', $postEventId, $clubId, $searchValue, $like, $like);
        $lookup->execute();
        $lookupResult = $lookup->get_result();
        if ($lookupResult->num_rows > 0) {
            $verifiedReg = $lookupResult->fetch_assoc();
            $eventId = $postEventId;
        }
    }

    $regId = (int) post('registration_id');
    if ($regId > 0 && $postEventId > 0 && $postAction !== 'lookup') {
        $chk = $db->prepare("SELECT er.*, e.title as event_title FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE er.registration_id = ? AND er.event_id = ? AND e.club_id = ?");
        $chk->bind_param('iii', $regId, $postEventId, $clubId);
        $chk->execute();
        $chkResult = $chk->get_result();
        if ($chkResult->num_rows > 0) {
            $reg = $chkResult->fetch_assoc();
            if ($reg['status'] !== 'attended') {
                $upd = $db->prepare("UPDATE event_registrations SET status = 'attended', checked_in_at = NOW(), check_in_method = 'manual', checked_in_by = ? WHERE registration_id = ?");
                $userId = currentUserId();
                $upd->bind_param('ii', $userId, $regId);
                $upd->execute();
                $_SESSION['flash']['success'] = 'Attendance recorded.';
                redirect('/club/attendance?event_id=' . $postEventId);
            }
        }
    }
}

if ($eventId > 0 && $event === null) {
    printf('Event not found.');
}

$attendedCount = 0;
$totalRegistered = 0;
$registrations = null;
if ($event) {
    $ac = $db->prepare("SELECT COUNT(*) as c FROM event_registrations WHERE event_id = ? AND status = 'attended'");
    $ac->bind_param('i', $eventId);
    $ac->execute();
    $attendedCount = (int) $ac->get_result()->fetch_assoc()['c'];

    $tr = $db->prepare("SELECT COUNT(*) as c FROM event_registrations WHERE event_id = ?");
    $tr->bind_param('i', $eventId);
    $tr->execute();
    $totalRegistered = (int) $tr->get_result()->fetch_assoc()['c'];

    $regsStmt = $db->prepare("SELECT er.*, s.email AS student_email FROM event_registrations er LEFT JOIN students s ON s.university_id = er.guest_student_id WHERE er.event_id = ? ORDER BY er.registered_at DESC");
    $regsStmt->bind_param('i', $eventId);
    $regsStmt->execute();
    $registrations = $regsStmt->get_result();
}

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
                <h2 class="page-title">Event Check-in</h2>
                <p class="page-subtitle">Pick an event, search by student ID or email, then check in participants.</p>
            </div>
            <?php if ($event): ?>
                <a href="<?= url('/club/attendance/walkin?event_id=' . $eventId) ?>" class="btn-secondary btn-sm">
                    <?= icon('plus', 'w-4 h-4') ?> Walk-in Registration
                </a>
            <?php endif; ?>
        </div>

        <div class="card p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
                <form method="GET" action="<?= url('/club/attendance') ?>" class="flex gap-3 items-end">
                    <div class="form-group flex-1 mb-0">
                        <label class="label-required">Select Event</label>
                        <select name="event_id" class="select" onchange="this.form.submit()">
                            <option value="">Choose an event...</option>
                            <?php foreach ($events as $ev): ?>
                                <option value="<?= (int) $ev['event_id'] ?>" <?= $eventId === (int) $ev['event_id'] ? 'selected' : '' ?>><?= e($ev['title']) ?> (<?= formatDate($ev['start_time']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary btn-sm">Load</button>
                </form>

                <form method="POST" action="<?= url('/club/attendance') ?>" class="flex gap-3 items-end lg:col-span-2">
                    <input type="hidden" name="action" value="lookup">
                    <input type="hidden" name="event_id" value="<?= $event ? (int) $eventId : (int) ($events[0]['event_id'] ?? 0) ?>">
                    <div class="form-group flex-1 mb-0">
                        <label class="label-required">Search Participant</label>
                        <input type="text" name="lookup_student_id" value="<?= e(post('lookup_student_id')) ?>" placeholder="Search by student ID or email..." class="input" required>
                    </div>
                    <button type="submit" class="btn-primary btn-sm">Check</button>
                </form>
            </div>
        </div>

        <?php if (!$event): ?>
            <div class="card p-6 text-center text-sm text-gray-500">Select an event to start checking in.</div>
        <?php else: ?>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="stat-card">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <?= icon('check-circle', 'w-5 h-5') ?>
                        </div>
                        <div>
                            <p class="stat-label">Current Attendance</p>
                            <p class="stat-value"><?= $attendedCount ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <?= icon('users', 'w-5 h-5') ?>
                        </div>
                        <div>
                            <p class="stat-label">Total Registered</p>
                            <p class="stat-value"><?= $totalRegistered ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-span-2">
                    <div class="stat-card">
                        <div class="text-sm text-gray-600 mb-2">Check-in Progress</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $totalRegistered > 0 ? round(($attendedCount / $totalRegistered) * 100) : 0 ?>%"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1"><?= $totalRegistered > 0 ? round(($attendedCount / $totalRegistered) * 100) : 0 ?>% checked in</div>
                    </div>
                </div>
            </div>

            <?php if ($searchPerformed): ?>
                <div class="card p-6 mb-6 max-w-md">
                    <?php if ($verifiedReg): ?>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full <?= $verifiedReg['status'] === 'attended' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center">
                                <?= icon($verifiedReg['status'] === 'attended' ? 'info' : 'check-circle', 'w-6 h-6') ?>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900"><?= $verifiedReg['status'] === 'attended' ? 'Already Checked In' : 'Participant Found' ?></h3>
                                <p class="text-sm text-gray-500"><?= e($verifiedReg['event_title']) ?></p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Name</span>
                                <span class="font-medium text-gray-900"><?= e($verifiedReg['guest_name'] ?: '—') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Student ID</span>
                                <span class="font-medium text-gray-900"><?= e($verifiedReg['guest_student_id'] ?: '—') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Email</span>
                                <span class="font-medium text-gray-900"><?= e($verifiedReg['student_email'] ?: '—') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Status</span>
                                <span class="badge-<?= $verifiedReg['status'] === 'attended' ? 'success' : 'info' ?>"><?= ucfirst(e($verifiedReg['status'])) ?></span>
                            </div>
                        </div>
                        <?php if ($verifiedReg['status'] !== 'attended'): ?>
                            <form method="POST" action="<?= url('/club/attendance') ?>" class="mt-4">
                                <input type="hidden" name="registration_id" value="<?= (int) $verifiedReg['registration_id'] ?>">
                                <input type="hidden" name="event_id" value="<?= (int) $verifiedReg['event_id'] ?>">
                                <button type="submit" class="btn-success btn-sm w-full">Confirm Check-in</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center">
                                <?= icon('x-circle', 'w-6 h-6') ?>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Participant Not Found</h3>
                                <p class="text-sm text-gray-500">No registration matched in this event.</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= url('/club/attendance?event_id=' . $eventId) ?>" class="btn-secondary btn-sm">Search Again</a>
                            <a href="<?= url('/club/attendance/walkin?event_id=' . $eventId) ?>" class="btn-primary btn-sm">Register Walk-in</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Registered Participants</h3>
                    <p class="text-sm text-gray-500"><?= e($event['title']) ?></p>
                </div>
                <?php if ($registrations && $registrations->num_rows === 0): ?>
                    <div class="p-6 text-center text-sm text-gray-500">No participants registered for this event yet.</div>
                <?php elseif ($registrations): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Participant</th>
                                    <th>Student ID</th>
                                    <th>Email</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th class="whitespace-nowrap text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($reg = $registrations->fetch_assoc()): ?>
                                <tr>
                                    <td class="font-medium text-gray-900"><?= e($reg['guest_name'] ?: '—') ?></td>
                                    <td class="text-sm text-gray-600"><?= e($reg['guest_student_id'] ?: '—') ?></td>
                                    <td class="text-sm text-gray-600"><?= e($reg['student_email'] ?: '—') ?></td>
                                    <td class="whitespace-nowrap text-sm text-gray-600"><?= formatDate($reg['registered_at']) ?></td>
                                    <td>
                                        <?php
                                        $rs = $reg['status'];
                                        $rb = match($rs) {
                                            'registered' => 'badge-info',
                                            'waitlisted' => 'badge-warning',
                                            'attended' => 'badge-success',
                                            'no_show' => 'badge-neutral',
                                            default => 'badge-neutral',
                                        };
                                        ?>
                                        <span class="<?= $rb ?>"><?= ucfirst(e($rs)) ?></span>
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <?php if ($reg['status'] !== 'attended'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="registration_id" value="<?= (int) $reg['registration_id'] ?>">
                                                <input type="hidden" name="event_id" value="<?= (int) $reg['event_id'] ?>">
                                                <button type="submit" class="btn-success btn-sm" data-confirm="Confirm check-in for <?= e($reg['guest_name'] ?: 'this participant') ?>?">
                                                    <?= icon('check-circle', 'w-4 h-4') ?> Check In
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge-success">Checked In</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            /* ============================================================
               QR SCANNER SECTION (disabled for now - commented out)
               ============================================================ */
            ?>
            <!--
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                <div class="lg:col-span-2">
                    <div class="card p-8 text-center">
                        <div class="w-24 h-24 mx-auto text-gray-300 mb-4">
                            <svg class="w-24 h-24" ...qr icon...></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Scan QR Code</h3>
                        <p class="text-sm text-gray-500 mb-6">Use the Eventrify scanner to verify participant registration.</p>
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 mb-6">
                            <p class="text-sm text-gray-500">QR Scanner placeholder</p>
                        </div>
                        <div class="flex flex-wrap justify-center gap-3">
                            <button type="button" class="btn-primary" id="modeScan">Scan QR Code</button>
                            <button type="button" class="btn-secondary" id="modeSearch">Search Student ID</button>
                        </div>
                        <div id="searchMode" class="mt-6 hidden">...</div>
                    </div>
                </div>
                <div>Scan result panel</div>
            </div>
            -->
        <?php endif; ?>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>