<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubUser();

$pageTitle = 'Event Check-in';
$activePage = 'attendance';

$clubId = (int) currentUser()['club_id'];
$eventId = (int) get('event_id', 0);

$verifiedReg = null;
$searchPerformed = false;

if (isPost()) {
    $postAction = post('action');

    if ($postAction === 'lookup' && post('lookup_student_id') !== '' && $eventId > 0) {
        $searchStudentId = post('lookup_student_id');
        $searchPerformed = true;
        $lookup = $db->prepare("SELECT er.*, e.title as event_title FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE er.event_id = ? AND e.club_id = ? AND er.guest_student_id = ?");
        $lookup->bind_param('iis', $eventId, $clubId, $searchStudentId);
        $lookup->execute();
        $lookupResult = $lookup->get_result();
        if ($lookupResult->num_rows > 0) {
            $verifiedReg = $lookupResult->fetch_assoc();
        }
    }

    $regId = (int) post('registration_id');
    $postEventId = (int) post('event_id');
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

if ($eventId > 0) {
    $evStmt = $db->prepare("SELECT * FROM events WHERE event_id = ? AND club_id = ?");
    $evStmt->bind_param('ii', $eventId, $clubId);
    $evStmt->execute();
    $event = $evStmt->get_result()->fetch_assoc();
} else {
    $event = null;
}

$eventsList = $db->prepare("SELECT event_id, title, start_time, status FROM events WHERE club_id = ? AND status IN ('published', 'completed', 'ongoing') ORDER BY start_time DESC");
$eventsList->bind_param('i', $clubId);
$eventsList->execute();
$eventsResult = $eventsList->get_result();

$attendedCount = 0;
$totalRegistered = 0;
if ($eventId > 0) {
    $ac = $db->prepare("SELECT COUNT(*) as c FROM event_registrations WHERE event_id = ? AND status = 'attended'");
    $ac->bind_param('i', $eventId);
    $ac->execute();
    $attendedCount = (int) $ac->get_result()->fetch_assoc()['c'];

    $tr = $db->prepare("SELECT COUNT(*) as c FROM event_registrations WHERE event_id = ?");
    $tr->bind_param('i', $eventId);
    $tr->execute();
    $totalRegistered = (int) $tr->get_result()->fetch_assoc()['c'];
}

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $alertType = flash('success') ? 'success' : 'error';
        $alertMessage = flash('success') ?: flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <?php if (!$event && $eventId > 0): ?>
            <p class="text-red-600">Event not found.</p>
        <?php elseif (!$event): ?>
            <div class="page-header mb-6">
                <div>
                    <h2 class="page-title">Event Check-in</h2>
                    <p class="page-subtitle">Select an event to begin check-in</p>
                </div>
            </div>
            <div class="card p-6 max-w-lg">
                <form method="GET" action="<?= url('/club/attendance') ?>" class="space-y-4">
                    <div class="form-group">
                        <label class="label-required">Select Event</label>
                        <select name="event_id" class="select" required>
                            <option value="">Choose an event...</option>
                            <?php while ($ev = $eventsResult->fetch_assoc()): ?>
                                <option value="<?= $ev['event_id'] ?>"><?= e($ev['title']) ?> (<?= formatDate($ev['start_time']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Continue</button>
                </form>
            </div>
        <?php else: ?>
            <nav class="breadcrumb mb-6">
                <a href="<?= url('/club/attendance') ?>" class="breadcrumb-link">Attendance</a>
                <span class="text-gray-400">/</span>
                <span class="breadcrumb-current"><?= e($event['title']) ?></span>
            </nav>

            <div class="page-header mb-6">
                <div>
                    <h2 class="page-title">Event Check-in</h2>
                    <p class="page-subtitle"><?= e($event['title']) ?> &mdash; <?= formatDate($event['start_time']) ?></p>
                </div>
                <a href="<?= url('/club/attendance/walkin?event_id=' . $eventId) ?>" class="btn-secondary btn-sm">
                    <?= icon('plus', 'w-4 h-4') ?> Walk-in Registration
                </a>
            </div>

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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="card p-8 text-center">
                        <div class="w-24 h-24 mx-auto text-gray-300 mb-4">
                            <?= icon('qr', 'w-24 h-24') ?>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Scan QR Code</h3>
                        <p class="text-sm text-gray-500 mb-6">Use the Eventrify scanner to verify participant registration.</p>

                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 mb-6">
                            <div class="w-12 h-12 mx-auto text-gray-400 mb-3">
                                <?= icon('camera', 'w-12 h-12') ?>
                            </div>
                            <p class="text-sm text-gray-500">QR Scanner placeholder</p>
                        </div>

                        <div class="flex flex-wrap justify-center gap-3">
                            <button type="button" class="btn-primary" id="modeScan">
                                <?= icon('qr', 'w-4 h-4') ?> Scan QR Code
                            </button>
                            <button type="button" class="btn-secondary" id="modeSearch">
                                <?= icon('search', 'w-4 h-4') ?> Search Student ID
                            </button>
                        </div>

                        <div id="searchMode" class="mt-6 hidden">
                            <form method="POST" action="<?= url('/club/attendance?event_id=' . $eventId) ?>" class="flex gap-3 max-w-sm mx-auto">
                                <input type="hidden" name="action" value="lookup">
                                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                <input type="text" name="lookup_student_id" value="<?= e(post('lookup_student_id')) ?>" placeholder="Enter student ID" class="input flex-1" required>
                                <button type="submit" class="btn-primary">Check</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div>
                    <?php if ($verifiedReg): ?>
                        <div class="card p-6">
                            <div class="text-center mb-4">
                                <?php if ($verifiedReg['status'] === 'attended'): ?>
                                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                                        <?= icon('info', 'w-6 h-6') ?>
                                    </div>
                                    <h3 class="font-semibold text-gray-900">Already Checked In</h3>
                                    <p class="text-sm text-gray-500 mt-1">This participant was already verified.</p>
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                                        <?= icon('check-circle', 'w-6 h-6') ?>
                                    </div>
                                    <h3 class="font-semibold text-gray-900">Attendance Verified</h3>
                                <?php endif; ?>
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
                                    <span class="text-gray-500">Registration</span>
                                    <span class="font-medium text-gray-900">#<?= (int) $verifiedReg['registration_id'] ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Status</span>
                                    <?php
                                    $rs = $verifiedReg['status'];
                                    $rb = match($rs) {
                                        'attended' => 'badge-success',
                                        'registered' => 'badge-info',
                                        default => 'badge-neutral',
                                    };
                                    ?>
                                    <span class="<?= $rb ?>"><?= ucfirst(e($rs)) ?></span>
                                </div>
                            </div>
                            <?php if ($verifiedReg['status'] === 'registered'): ?>
                                <form method="POST" action="<?= url('/club/attendance?event_id=' . $eventId) ?>" class="mt-4">
                                    <input type="hidden" name="registration_id" value="<?= (int) $verifiedReg['registration_id'] ?>">
                                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                    <button type="submit" class="btn-success btn-sm w-full">Confirm Check-in</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($searchPerformed): ?>
                        <div class="card p-6 text-center">
                            <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-3">
                                <?= icon('x-circle', 'w-6 h-6') ?>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Participant Not Found</h3>
                            <p class="text-sm text-gray-500 mb-4">No registration found for this student ID.</p>
                            <div class="flex flex-col gap-2">
                                <a href="<?= url('/club/attendance?event_id=' . $eventId) ?>" class="btn-secondary btn-sm">Search Again</a>
                                <a href="<?= url('/club/attendance/walkin?event_id=' . $eventId) ?>" class="btn-primary btn-sm">Register Walk-in</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card p-6 text-center">
                            <div class="w-12 h-12 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                <?= icon('info', 'w-6 h-6') ?>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Scan Result</h3>
                            <p class="text-sm text-gray-500">Scan a QR code or search a student ID to verify attendance.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchMode = document.getElementById('searchMode');
    var modeSearch = document.getElementById('modeSearch');
    var modeScan = document.getElementById('modeScan');
    if (modeSearch && searchMode) {
        modeSearch.addEventListener('click', function() {
            searchMode.classList.remove('hidden');
        });
    }
    if (modeScan && searchMode) {
        modeScan.addEventListener('click', function() {
            searchMode.classList.add('hidden');
        });
    }
});
</script>
