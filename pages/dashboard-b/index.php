<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubUser();

$pageTitle = 'Overview';
$activePage = 'overview';

$clubId = (int) currentUser()['club_id'];

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$clubStmt = $db->prepare("SELECT club_name, status FROM clubs WHERE club_id = ?");
$clubStmt->bind_param('i', $clubId);
$clubStmt->execute();
$clubRow = $clubStmt->get_result()->fetch_assoc();
$clubName   = $clubRow['club_name'] ?? 'Your Club';
$clubStatus = $clubRow['status'] ?? 'pending';
$_SESSION['user']['club_status'] = $clubStatus;

if ($clubStatus !== 'approved') {
    $totalEvents = 0;
    $upcomingEvents = 0;
    $totalRegistrations = 0;
    $attendedCount = 0;
    $attendanceRate = '—';
    $upcomingResult = null;
    $recentRegsResult = null;
} else {
    $totalEvents = $db->query("SELECT COUNT(*) as c FROM events WHERE club_id = $clubId")->fetch_assoc()['c'] ?? 0;
$upcomingEvents = $db->query("SELECT COUNT(*) as c FROM events WHERE club_id = $clubId AND start_time >= NOW() AND status = 'published'")->fetch_assoc()['c'] ?? 0;
$totalRegistrations = $db->query("SELECT COUNT(*) as c FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = $clubId")->fetch_assoc()['c'] ?? 0;
$attendedCount = $db->query("SELECT COUNT(*) as c FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = $clubId AND er.status = 'attended'")->fetch_assoc()['c'] ?? 0;
$attendanceRate = $totalRegistrations > 0 ? round(($attendedCount / $totalRegistrations) * 100) : '—';

$upcomingList = $db->prepare("SELECT e.*, (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id) as reg_count FROM events e WHERE e.club_id = ? AND e.start_time >= NOW() AND e.status = 'published' ORDER BY e.start_time ASC LIMIT 5");
$upcomingList->bind_param('i', $clubId);
$upcomingList->execute();
$upcomingResult = $upcomingList->get_result();

$recentRegs = $db->prepare("SELECT er.*, e.title as event_title FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = ? ORDER BY er.registered_at DESC LIMIT 5");
$recentRegs->bind_param('i', $clubId);
$recentRegs->execute();
$recentRegsResult = $recentRegs->get_result();
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

        <?php if ($clubStatus !== 'approved'): ?>
            <div class="flex items-center justify-center min-h-[70vh]">
                <div class="card p-8 sm:p-10 max-w-lg w-full text-center">
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-50 text-amber-600 mb-5"><?= icon('clock', 'w-8 h-8') ?></span>
                    <h2 class="text-xl font-bold text-gray-900">Account Not Active Yet</h2>
                    <p class="text-sm text-gray-500 mt-1"><?= e($clubName) ?></p>
                    <div class="flex items-center justify-center gap-3 mt-4">
                        <?php if ($clubStatus === 'rejected'): ?>
                            <span class="badge badge-danger">Not Approved</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pending Review</span>
                        <?php endif; ?>
                        <?php $applicationId = $_SESSION['user']['application_id'] ?? null; ?>
                        <?php if ($applicationId): ?>
                            <span class="text-sm text-gray-500">Application ID: <span class="font-semibold text-gray-900"><?= e($applicationId) ?></span></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-gray-600 mt-5 leading-relaxed">
                        <?php if ($clubStatus === 'rejected'): ?>
                            Your club application was not approved. For more details, please contact the Eventrify administration.
                        <?php else: ?>
                            Your club registration is under review by the Eventrify administration. You'll be able to manage events, registrations, attendance, and more once your club is approved. Please check back later.
                        <?php endif; ?>
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center mt-7">
                        <a href="<?= url('/club') ?>" class="btn-primary"><?= icon('refresh', 'w-4 h-4') ?> Refresh Status</a>
                        <a href="<?= url('/') ?>" class="btn-secondary">View Site</a>
                        <a href="<?= url('/logout') ?>" class="btn-ghost">Logout</a>
                    </div>
                </div>
            </div>
        <?php else: ?>

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900"><?= $greeting ?>, <?= e($clubName) ?> 👋</h2>
            <p class="text-sm text-gray-500 mt-1">Here's what's happening with your club.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="stat-card">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <?= icon('calendar', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Total Events</p>
                        <p class="stat-value"><?= (int) $totalEvents ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <?= icon('trending', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Upcoming Events</p>
                        <p class="stat-value"><?= (int) $upcomingEvents ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <?= icon('users', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Total Registrations</p>
                        <p class="stat-value"><?= (int) $totalRegistrations ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <?= icon('check-circle', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Attendance Rate</p>
                        <p class="stat-value"><?= $attendanceRate === '—' ? '—' : $attendanceRate . '%' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-8">
            <?php if (clubCanAccess('events')): ?>
            <a href="<?= url('/club/events/create') ?>" class="btn-primary">
                <?= icon('plus', 'w-4 h-4') ?> Create Event
            </a>
            <?php endif; ?>
            <?php if (clubCanAccess('registrations')): ?>
            <a href="<?= url('/club/registrations') ?>" class="btn-secondary">
                <?= icon('clipboard', 'w-4 h-4') ?> Manage Registrations
            </a>
            <?php endif; ?>
            <?php if (clubCanAccess('attendance')): ?>
            <a href="<?= url('/club/attendance') ?>" class="btn-secondary">
                <?= icon('qr', 'w-4 h-4') ?> Check Attendance
            </a>
            <?php endif; ?>
            <?php if (clubCanAccess('room_requests')): ?>
            <a href="<?= url('/club/room-requests') ?>" class="btn-secondary">
                <?= icon('building', 'w-4 h-4') ?> Request Room
            </a>
            <?php endif; ?>
        </div>

        <div class="card p-5 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Upcoming Events</h3>
                <?php if (clubCanAccess('events')): ?>
                <a href="<?= url('/club/events') ?>" class="text-sm font-medium text-primary-600 hover:text-primary-500">View all →</a>
                <?php endif; ?>
            </div>
            <?php if ($upcomingResult->num_rows === 0): ?>
                <?php
                $emptyIcon = 'calendar';
                $emptyTitle = 'No upcoming events';
                $emptyText = 'Create your first event to get started.';
                $emptyHref = clubCanAccess('events') ? url('/club/events/create') : null;
                $emptyAction = clubCanAccess('events') ? 'Create Event' : null;
                require BASE_PATH . '/app/components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Registrations</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ev = $upcomingResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-900"><?= e($ev['title']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e(truncate($ev['description'], 50)) ?></div>
                                </td>
                                <td class="whitespace-nowrap"><?= formatDate($ev['start_time']) ?></td>
                                <td><?= (int) $ev['reg_count'] ?></td>
                                <td><?= (int) $ev['capacity'] ?></td>
                                <td><span class="badge-success">Published</span></td>
                                <td class="whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <?php if (clubCanAccess('events')): ?>
                                        <a href="<?= url('/club/events/manage?event_id=' . $ev['event_id']) ?>" class="btn-ghost btn-sm">View</a>
                                        <?php endif; ?>
                                        <?php if (clubCanAccess('registrations')): ?>
                                        <a href="<?= url('/club/registrations?event_id=' . $ev['event_id']) ?>" class="btn-ghost btn-sm">Manage</a>
                                        <?php endif; ?>
                                        <?php if (clubCanAccess('events')): ?>
                                        <a href="<?= url('/club/events/edit?event_id=' . $ev['event_id']) ?>" class="btn-ghost btn-sm">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (clubCanAccess('registrations')): ?>
        <div class="card p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Registrations</h3>
            <?php if ($recentRegsResult->num_rows === 0): ?>
                <p class="text-sm text-gray-500 text-center py-6">No registrations yet.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Participant</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reg = $recentRegsResult->fetch_assoc()): ?>
                            <tr>
                                <td class="font-medium text-gray-900"><?= e($reg['guest_name'] ?: 'ID: ' . $reg['guest_student_id']) ?></td>
                                <td><?= e($reg['event_title']) ?></td>
                                <td class="whitespace-nowrap"><?= formatDate($reg['registered_at']) ?></td>
                                <td>
                                    <?php
                                    $rs = $reg['status'] ?? 'registered';
                                    $rb = match($rs) {
                                        'registered' => 'badge-info',
                                        'waitlisted' => 'badge-warning',
                                        'attended' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                        'no_show' => 'badge-neutral',
                                        default => 'badge-neutral',
                                    };
                                    ?>
                                    <span class="<?= $rb ?>"><?= ucfirst(e($rs)) ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
