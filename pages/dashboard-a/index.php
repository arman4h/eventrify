<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'Overview';
$activePage = 'overview';

$totalClubs          = (int) $db->query("SELECT COUNT(*) AS n FROM clubs")->fetch_assoc()['n'];
$pendingClubRequests = (int) $db->query("SELECT COUNT(*) AS n FROM clubs WHERE status = 'pending'")->fetch_assoc()['n'];
$totalStudents       = (int) $db->query("SELECT COUNT(*) AS n FROM students")->fetch_assoc()['n'];
$activeEvents        = (int) $db->query("SELECT COUNT(*) AS n FROM events WHERE status = 'published' AND start_time >= NOW()")->fetch_assoc()['n'];
$totalRegistrations  = (int) $db->query("SELECT COUNT(*) AS n FROM event_registrations")->fetch_assoc()['n'];
$pendingRoomRequests = (int) $db->query("SELECT COUNT(*) AS n FROM room_requests WHERE status = 'pending'")->fetch_assoc()['n'];

$recentClubRequests = $db->query("
    SELECT c.club_id, c.club_name, c.created_at, c.status, cu.email AS owner_email
    FROM clubs c
    LEFT JOIN club_users cu ON cu.club_user_id = c.requested_by
    WHERE c.status = 'pending'
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$recentEvents = $db->query("
    SELECT e.event_id, e.title, e.start_time, e.status, c.club_name
    FROM events e
    LEFT JOIN clubs c ON c.club_id = e.club_id
    ORDER BY e.start_time DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$recentReports = $db->query("
    SELECT r.report_id, r.subject, r.status, r.created_at, s.full_name AS reporter_name
    FROM reports r
    LEFT JOIN students s ON s.student_id = r.reporter_id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">Overview</h1>
                <p class="page-subtitle">
                    Welcome back, <?= e(currentUser()['name'] ?? 'Admin') ?>. Here's what's happening across Eventrify.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><?= icon('building', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Total Clubs</p>
                    <p class="stat-value"><?= $totalClubs ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"><?= icon('clipboard', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Pending Requests</p>
                    <p class="stat-value"><?= $pendingClubRequests ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><?= icon('users', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Total Students</p>
                    <p class="stat-value"><?= $totalStudents ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0"><?= icon('calendar', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Active Events</p>
                    <p class="stat-value"><?= $activeEvents ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"><?= icon('ticket', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Registrations</p>
                    <p class="stat-value"><?= $totalRegistrations ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"><?= icon('map-pin', 'w-6 h-6') ?></div>
                <div class="min-w-0">
                    <p class="stat-label">Room Requests</p>
                    <p class="stat-value"><?= $pendingRoomRequests ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recent Club Requests</h3>
                        <p class="text-sm text-gray-500 mt-1">Latest applications awaiting review</p>
                    </div>
                    <a href="<?= url('/admin/club-requests') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-700">Review all</a>
                </div>

                <?php if (count($recentClubRequests) === 0): ?>
                    <?php
                    $emptyIcon = 'clipboard';
                    $emptyTitle = 'No club requests';
                    $emptyText = 'New club applications will appear here.';
                    require BASE_PATH . '/app/components/empty-state.php';
                    ?>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($recentClubRequests as $club): ?>
                        <a href="<?= url('/admin/club-requests/review?club_id=' . $club['club_id']) ?>" class="flex items-center gap-3 py-3 group">
                            <span class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"><?= icon('briefcase', 'w-5 h-5') ?></span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-medium text-gray-900 truncate group-hover:text-blue-600"><?= e($club['club_name']) ?></span>
                                <span class="block text-xs text-gray-500 truncate"><?= e($club['owner_email'] ?? 'No applicant email') ?></span>
                            </span>
                            <span class="text-right shrink-0">
                                <?php
                                $badgeType = 'warning';
                                $badgeText = 'Pending';
                                require BASE_PATH . '/app/components/badge.php';
                                ?>
                                <span class="block text-xs text-gray-400 mt-1"><?= formatDate($club['created_at'], 'M d, Y') ?></span>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recent Events</h3>
                        <p class="text-sm text-gray-500 mt-1">Latest events created on the platform</p>
                    </div>
                    <a href="<?= url('/admin/events') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
                </div>

                <?php if (count($recentEvents) === 0): ?>
                    <?php
                    $emptyIcon = 'calendar';
                    $emptyTitle = 'No events yet';
                    $emptyText = 'Events created by clubs will show up here.';
                    require BASE_PATH . '/app/components/empty-state.php';
                    ?>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($recentEvents as $event): ?>
                        <a href="<?= url('/admin/events') ?>" class="flex items-center gap-3 py-3 group">
                            <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><?= icon('calendar', 'w-5 h-5') ?></span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-medium text-gray-900 truncate group-hover:text-blue-600"><?= e($event['title']) ?></span>
                                <span class="block text-xs text-gray-500 truncate"><?= e($event['club_name'] ?? 'Unknown club') ?></span>
                            </span>
                            <span class="text-right shrink-0">
                                <?php
                                $badgeType = $event['status'];
                                $badgeText = '';
                                require BASE_PATH . '/app/components/badge.php';
                                ?>
                                <span class="block text-xs text-gray-400 mt-1"><?= formatDate($event['start_time'], 'M d, Y') ?></span>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-5 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recent Reports</h3>
                        <p class="text-sm text-gray-500 mt-1">Latest reports submitted by students</p>
                    </div>
                    <a href="<?= url('/admin/reports') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
                </div>

                <?php if (count($recentReports) === 0): ?>
                    <?php
                    $emptyIcon = 'info';
                    $emptyTitle = 'No reports yet';
                    $emptyText = 'Student reports will appear here when submitted.';
                    require BASE_PATH . '/app/components/empty-state.php';
                    ?>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($recentReports as $report): ?>
                        <a href="<?= url('/admin/reports') ?>" class="flex items-center gap-3 py-3 group">
                            <span class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center shrink-0"><?= icon('alert', 'w-5 h-5') ?></span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-medium text-gray-900 truncate group-hover:text-blue-600"><?= e($report['subject']) ?></span>
                                <span class="block text-xs text-gray-500 truncate"><?= e($report['reporter_name'] ?? 'Anonymous') ?></span>
                            </span>
                            <span class="text-right shrink-0">
                                <?php
                                $badgeType = $report['status'];
                                $badgeText = '';
                                require BASE_PATH . '/app/components/badge.php';
                                ?>
                                <span class="block text-xs text-gray-400 mt-1"><?= formatDate($report['created_at'], 'M d, Y') ?></span>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>