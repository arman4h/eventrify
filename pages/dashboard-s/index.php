<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireStudent();

$studentId = (int) currentUserId();

$stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$studentRow = $stmt->get_result()->fetch_assoc();
$student = $studentRow ?: currentUser();

$firstName = 'Friend';
if (!empty($student['full_name'])) {
    $firstName = explode(' ', trim($student['full_name']))[0] ?: 'Friend';
}

$upcomingCount = 0;
$registeredCount = 0;
$attendedCount = 0;
$discoveredCount = 0;

$stmt = $db->prepare("
    SELECT COUNT(*) AS c
    FROM event_registrations er
    JOIN events e ON e.event_id = er.event_id
    WHERE er.student_id = ? AND e.status = 'published' AND e.start_time >= NOW()
      AND er.status IN ('registered','waitlisted')
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$upcomingCount = (int) $stmt->get_result()->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM event_registrations WHERE student_id = ? AND status != 'cancelled'");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$registeredCount = (int) $stmt->get_result()->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM event_registrations WHERE student_id = ? AND status = 'attended'");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$attendedCount = (int) $stmt->get_result()->fetch_assoc()['c'];

$discoveredCount = (int) ($db->query("SELECT COUNT(*) AS c FROM events WHERE status = 'published'")->fetch_assoc()['c'] ?? 0);

$upcomingEvents = $db->prepare("
    SELECT e.event_id, e.title, e.start_time, e.capacity, c.club_name, er.status AS reg_status,
           (SELECT COUNT(*) FROM event_registrations x
            WHERE x.event_id = e.event_id AND x.status IN ('registered','waitlisted','attended')) AS reg_count
    FROM event_registrations er
    JOIN events e ON e.event_id = er.event_id
    LEFT JOIN clubs c ON c.club_id = e.club_id
    WHERE er.student_id = ? AND e.status = 'published' AND e.start_time >= NOW()
      AND er.status IN ('registered','waitlisted')
    ORDER BY e.start_time ASC
    LIMIT 5
");
$upcomingEvents->bind_param('i', $studentId);
$upcomingEvents->execute();
$upcomingEvents = $upcomingEvents->get_result();

$latestEvents = $db->query("
    SELECT e.event_id, e.title, e.start_time, c.club_name
    FROM events e
    LEFT JOIN clubs c ON c.club_id = e.club_id
    WHERE e.status = 'published'
    ORDER BY e.start_time ASC
    LIMIT 5
");

$pageTitle = 'Overview';
$activePage = 'overview';
require BASE_PATH . '/app/layouts/dashboard-s/header.php';
require BASE_PATH . '/app/layouts/dashboard-s/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-s/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <div class="page-header">
            <div>
                <h1 class="page-title">Good to see you, <?= e($firstName) ?></h1>
                <p class="page-subtitle">Here's what's happening on campus.</p>
            </div>
        </div>

        <div class="card p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="avatar-lg" aria-hidden="true"><?= e(substr($student['full_name'] ?? 'S', 0, 1)) ?></span>
                    <div>
                        <p class="text-lg font-bold text-gray-900"><?= e($student['full_name'] ?? '') ?></p>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <span class="badge badge-primary">Student</span>
                            <?php if (!empty($student['university_id'])): ?>
                            <span class="text-xs font-mono text-gray-500"><?= e($student['university_id']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($student['department'])): ?>
                            <span class="text-xs text-gray-500"><?= e($student['department']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="<?= url('/events') ?>" class="btn-primary shrink-0">Explore Events</a>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Upcoming</p>
                        <p class="stat-value"><?= $upcomingCount ?></p>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center"><?= icon('calendar', 'w-5 h-5') ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Registered</p>
                        <p class="stat-value"><?= $registeredCount ?></p>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 inline-flex items-center justify-center"><?= icon('ticket', 'w-5 h-5') ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Attended</p>
                        <p class="stat-value"><?= $attendedCount ?></p>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 inline-flex items-center justify-center"><?= icon('check-circle', 'w-5 h-5') ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Discovered Events</p>
                        <p class="stat-value"><?= $discoveredCount ?></p>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 inline-flex items-center justify-center"><?= icon('calendar', 'w-5 h-5') ?></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">My Upcoming Events</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Events you've registered for</p>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center"><?= icon('calendar', 'w-5 h-5') ?></span>
                </div>

                <?php if ($upcomingEvents && $upcomingEvents->num_rows === 0): ?>
                <div class="empty-state">
                    <span class="empty-state-icon text-gray-400"><?= icon('calendar', 'w-6 h-6') ?></span>
                    <p class="empty-state-title">No upcoming events</p>
                    <p class="empty-state-text">You haven't registered for any upcoming events yet.</p>
                    <a href="<?= url('/events') ?>" class="btn-secondary btn-sm">Discover Events</a>
                </div>
                <?php elseif ($upcomingEvents): while ($event = $upcomingEvents->fetch_assoc()): ?>
                <div class="flex items-center justify-between gap-4 py-3 border-b border-gray-100 last:border-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 text-blue-600 shrink-0"><?= icon('calendar', 'w-5 h-5') ?></span>
                        <div class="min-w-0">
                            <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="font-medium text-gray-900 hover:text-blue-600 truncate block"><?= e($event['title']) ?></a>
                            <p class="text-xs text-gray-500 truncate"><?= e($event['club_name'] ?? 'University Club') ?> · <?= formatDate($event['start_time'], 'M d, Y') ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <?php
                        $isFull = (int) $event['capacity'] > 0 && (int) $event['reg_count'] >= (int) $event['capacity'];
                        ?>
                        <span class="badge <?= $isFull ? 'badge-danger' : 'badge-primary' ?>"><?= $isFull ? 'Full' : 'Upcoming' ?></span>
                        <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="btn-ghost btn-sm">View</a>
                    </div>
                </div>
                <?php endwhile; endif; ?>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recommended Events</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Latest events from campus clubs</p>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 inline-flex items-center justify-center"><?= icon('ticket', 'w-5 h-5') ?></span>
                </div>

                <?php if (!$latestEvents || $latestEvents->num_rows === 0): ?>
                <div class="empty-state">
                    <span class="empty-state-icon text-gray-400"><?= icon('ticket', 'w-6 h-6') ?></span>
                    <p class="empty-state-title">No events yet</p>
                    <p class="empty-state-text">Clubs haven't published any events yet. Check back soon.</p>
                    <a href="<?= url('/events') ?>" class="btn-secondary btn-sm">Browse Events</a>
                </div>
                <?php else: while ($event = $latestEvents->fetch_assoc()): ?>
                <div class="flex items-center justify-between gap-4 py-3 border-b border-gray-100 last:border-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 shrink-0"><?= icon('ticket', 'w-5 h-5') ?></span>
                        <div class="min-w-0">
                            <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="font-medium text-gray-900 hover:text-blue-600 truncate block"><?= e($event['title']) ?></a>
                            <p class="text-xs text-gray-500 truncate"><?= e($event['club_name'] ?? 'University Club') ?> · <?= formatDate($event['start_time'], 'M d, Y') ?></p>
                        </div>
                    </div>
                    <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="btn-ghost btn-sm shrink-0">View</a>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <div class="text-center mt-2">
            <a href="<?= url('/student/registrations') ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-500">
                View all my registrations
                <?= icon('chevron-right', 'w-4 h-4') ?>
            </a>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-s/footer.php'; ?>
</div>