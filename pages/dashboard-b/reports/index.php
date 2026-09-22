<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('reports');

$pageTitle = 'Reports';
$activePage = 'reports';

$clubId = (int) currentUser()['club_id'];

$totalEvents = $db->query("SELECT COUNT(*) as c FROM events WHERE club_id = $clubId")->fetch_assoc()['c'] ?? 0;
$totalRegistrations = $db->query("SELECT COUNT(*) as c FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = $clubId")->fetch_assoc()['c'] ?? 0;
$attendedCount = $db->query("SELECT COUNT(*) as c FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = $clubId AND er.status = 'attended'")->fetch_assoc()['c'] ?? 0;
$waitlistCount = $db->query("SELECT COUNT(*) as c FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = $clubId AND er.status = 'waitlisted'")->fetch_assoc()['c'] ?? 0;
$attendanceRate = $totalRegistrations > 0 ? round(($attendedCount / $totalRegistrations) * 100) : 0;

$regPerMonth = $db->query("SELECT DATE_FORMAT(er.registered_at, '%Y-%m') as month, COUNT(*) as count FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = $clubId AND er.registered_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC");
$regMonths = [];
while ($row = $regPerMonth->fetch_assoc()) {
    $regMonths[] = $row;
}

$eventPerMonth = $db->query("SELECT DATE_FORMAT(start_time, '%Y-%m') as month, COUNT(*) as count FROM events WHERE club_id = $clubId AND start_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC");
$eventMonths = [];
while ($row = $eventPerMonth->fetch_assoc()) {
    $eventMonths[] = $row;
}

$attendanceByEvent = $db->query("SELECT e.title, COUNT(*) as registered, SUM(CASE WHEN er.status = 'attended' THEN 1 ELSE 0 END) as attended FROM events e LEFT JOIN event_registrations er ON er.event_id = e.event_id WHERE e.club_id = $clubId GROUP BY e.event_id, e.title ORDER BY e.start_time DESC LIMIT 10");
$eventAttendance = [];
while ($row = $attendanceByEvent->fetch_assoc()) {
    $eventAttendance[] = $row;
}

$maxRegMonth = 1;
foreach ($regMonths as $m) {
    if ($m['count'] > $maxRegMonth) $maxRegMonth = $m['count'];
}
$maxEventMonth = 1;
foreach ($eventMonths as $m) {
    if ($m['count'] > $maxEventMonth) $maxEventMonth = $m['count'];
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
                <h2 class="page-title">Reports</h2>
                <p class="page-subtitle">Analytics and insights for your club</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="stat-card">
                <div class="flex items-center gap-3">
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
                <div class="flex items-center gap-3">
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
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <?= icon('check-circle', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Attendance Rate</p>
                        <p class="stat-value"><?= $attendanceRate ?>%</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <?= icon('clock', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Waitlist Count</p>
                        <p class="stat-value"><?= (int) $waitlistCount ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Registrations per Month</h3>
                <?php if (empty($regMonths)): ?>
                    <p class="text-sm text-gray-500 text-center py-6">No data yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($regMonths as $m): ?>
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-gray-600"><?= formatDate($m['month'] . '-01', 'M Y') ?></span>
                                    <span class="font-medium text-gray-900"><?= (int) $m['count'] ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= round(($m['count'] / $maxRegMonth) * 100) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Events per Month</h3>
                <?php if (empty($eventMonths)): ?>
                    <p class="text-sm text-gray-500 text-center py-6">No data yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($eventMonths as $m): ?>
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-gray-600"><?= formatDate($m['month'] . '-01', 'M Y') ?></span>
                                    <span class="font-medium text-gray-900"><?= (int) $m['count'] ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= round(($m['count'] / $maxEventMonth) * 100) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Attendance by Event</h3>
            <?php if (empty($eventAttendance)): ?>
                <p class="text-sm text-gray-500 text-center py-6">No event data yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($eventAttendance as $ea):
                        $reg = (int) $ea['registered'];
                        $att = (int) $ea['attended'];
                        $pct = $reg > 0 ? round(($att / $reg) * 100) : 0;
                    ?>
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 truncate"><?= e($ea['title']) ?></span>
                                <span class="font-medium text-gray-900 whitespace-nowrap ml-2"><?= $att ?> / <?= $reg ?> (<?= $pct ?>%)</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
