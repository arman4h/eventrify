<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'Reports';
$activePage = 'reports';

$dateFrom = get('date_from', date('Y-m-d', strtotime('first day of -5 months')));
$dateTo   = get('date_to', date('Y-m-d'));

if (!strtotime($dateFrom) || !strtotime($dateTo)) {
    $dateFrom = date('Y-m-d', strtotime('first day of -5 months'));
    $dateTo   = date('Y-m-d');
}

if ($dateTo < $dateFrom) {
    $dateTo = $dateFrom;
}

$fromDt = $dateFrom . ' 00:00:00';
$toDt   = $dateTo . ' 23:59:59';

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM events WHERE start_time BETWEEN ? AND ?");
$stmt->bind_param('ss', $fromDt, $toDt);
$stmt->execute();
$eventsCount = (int) $stmt->get_result()->fetch_assoc()['n'];

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM event_registrations WHERE registered_at BETWEEN ? AND ?");
$stmt->bind_param('ss', $fromDt, $toDt);
$stmt->execute();
$registrationsCount = (int) $stmt->get_result()->fetch_assoc()['n'];

$stmt = $db->prepare("
    SELECT COUNT(*) AS total, COALESCE(SUM(status = 'attended'), 0) AS attended
    FROM event_registrations
    WHERE registered_at BETWEEN ? AND ? AND status <> 'cancelled'
");
$stmt->bind_param('ss', $fromDt, $toDt);
$stmt->execute();
$attendanceRow = $stmt->get_result()->fetch_assoc();

$attendanceRate = '—';
if ((int) $attendanceRow['total'] > 0) {
    $attendanceRate = round(((int) $attendanceRow['attended'] / (int) $attendanceRow['total']) * 100) . '%';
}

$activeClubs = (int) $db->query("SELECT COUNT(*) AS n FROM clubs WHERE status = 'approved'")->fetch_assoc()['n'];

$months = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("first day of -$i months");
    $months[date('Y-m', $ts)] = [
        'label' => date('M Y', $ts),
        'events' => 0,
        'registrations' => 0,
    ];
}

$eventRows = $db->query("SELECT DATE_FORMAT(start_time, '%Y-%m') AS ym, COUNT(*) AS n FROM events GROUP BY ym");
foreach ($eventRows as $eventRow) {
    if (isset($months[$eventRow['ym']])) {
        $months[$eventRow['ym']]['events'] = (int) $eventRow['n'];
    }
}

$regRows = $db->query("SELECT DATE_FORMAT(registered_at, '%Y-%m') AS ym, COUNT(*) AS n FROM event_registrations GROUP BY ym");
foreach ($regRows as $regRow) {
    if (isset($months[$regRow['ym']])) {
        $months[$regRow['ym']]['registrations'] = (int) $regRow['n'];
    }
}

$maxEvents = 0;
$maxRegs   = 0;
foreach ($months as $month) {
    $maxEvents = max($maxEvents, $month['events']);
    $maxRegs   = max($maxRegs, $month['registrations']);
}

$topClubs = $db->query("
    SELECT c.club_name, COUNT(er.registration_id) AS n
    FROM event_registrations er
    JOIN events e ON e.event_id = er.event_id
    JOIN clubs c ON c.club_id = e.club_id
    GROUP BY c.club_id
    ORDER BY n DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC) ?? [];

$categories = $db->query("
    SELECT category, COUNT(*) AS n
    FROM events
    WHERE category IS NOT NULL AND category <> ''
    GROUP BY category
    ORDER BY n DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC) ?? [];

$maxTopClubs = 1;
foreach ($topClubs as $club) {
    $maxTopClubs = max($maxTopClubs, (int) $club['n']);
}

$maxCategories = 1;
foreach ($categories as $category) {
    $maxCategories = max($maxCategories, (int) $category['n']);
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">Reports</h1>
                <p class="page-subtitle">Analytics and activity across the platform.</p>
            </div>
        </div>

        <div class="card p-4 mb-6">
            <form method="GET" action="<?= url('/admin/reports') ?>" class="flex flex-col sm:flex-row items-end sm:items-center gap-3">
                <div class="w-full sm:w-auto">
                    <label for="date_from" class="label">From</label>
                    <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>" class="input sm:w-44">
                </div>
                <div class="w-full sm:w-auto">
                    <label for="date_to" class="label">To</label>
                    <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>" class="input sm:w-44">
                </div>
                <button type="submit" class="btn-primary">Apply</button>
                <a href="<?= url('/admin/reports') ?>" class="btn-secondary">Clear</a>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><?= icon('calendar', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Events</p>
                    <p class="stat-value"><?= $eventsCount ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"><?= icon('ticket', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Registrations</p>
                    <p class="stat-value"><?= $registrationsCount ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><?= icon('check-circle', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Attendance Rate</p>
                    <p class="stat-value"><?= e((string) $attendanceRate) ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0"><?= icon('building', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Active Clubs</p>
                    <p class="stat-value"><?= $activeClubs ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div class="card p-6">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Events per Month</h3>
                    <p class="text-sm text-gray-500 mt-1">Last 6 months</p>
                </div>

                <div class="space-y-4">
                    <?php foreach ($months as $month): ?>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-gray-700"><?= e($month['label']) ?></span>
                            <span class="text-sm font-semibold text-gray-900"><?= $month['events'] ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $maxEvents > 0 ? round(($month['events'] / $maxEvents) * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card p-6">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Registrations per Month</h3>
                    <p class="text-sm text-gray-500 mt-1">Last 6 months</p>
                </div>

                <div class="space-y-4">
                    <?php foreach ($months as $month): ?>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-gray-700"><?= e($month['label']) ?></span>
                            <span class="text-sm font-semibold text-gray-900"><?= $month['registrations'] ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill bg-emerald-500" style="width: <?= $maxRegs > 0 ? round(($month['registrations'] / $maxRegs) * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card p-6">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Most Active Clubs</h3>
                    <p class="text-sm text-gray-500 mt-1">Top 5 by registrations</p>
                </div>

                <?php if (count($topClubs) === 0): ?>
                    <?php
                    $emptyIcon = 'building';
                    $emptyTitle = 'No registration data';
                    $emptyText = 'Club activity will appear here once students register for events.';
                    require BASE_PATH . '/app/components/empty-state.php';
                    ?>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($topClubs as $club): ?>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-gray-700 truncate"><?= e($club['club_name']) ?></span>
                            <span class="text-sm font-semibold text-gray-900"><?= (int) $club['n'] ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill bg-purple-500" style="width: <?= round(((int) $club['n'] / $maxTopClubs) * 100) ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="card p-6">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Popular Event Categories</h3>
                    <p class="text-sm text-gray-500 mt-1">By number of events</p>
                </div>

                <?php if (count($categories) === 0): ?>
                    <?php
                    $emptyIcon = 'clipboard';
                    $emptyTitle = 'No categories yet';
                    $emptyText = 'Event categories will appear once clubs categorize their events.';
                    require BASE_PATH . '/app/components/empty-state.php';
                    ?>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($categories as $category): ?>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-gray-700"><?= e($category['category']) ?></span>
                            <span class="text-sm font-semibold text-gray-900"><?= (int) $category['n'] ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill bg-amber-500" style="width: <?= round(((int) $category['n'] / $maxCategories) * 100) ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>