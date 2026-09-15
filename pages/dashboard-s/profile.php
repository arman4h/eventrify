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

$regBadgeClass = function (string $status): string {
    return match ($status) {
        'registered' => 'badge-info',
        'waitlisted' => 'badge-warning',
        'attended'   => 'badge-success',
        'cancelled'  => 'badge-danger',
        'no_show'    => 'badge-neutral',
        default      => 'badge-neutral',
    };
};

$stmt = $db->prepare("
    SELECT er.status AS reg_status, er.checked_in_at, e.event_id, e.title, e.start_time,
           e.status AS event_status, c.club_name
    FROM event_registrations er
    JOIN events e ON e.event_id = er.event_id
    LEFT JOIN clubs c ON c.club_id = e.club_id
    WHERE er.student_id = ?
    ORDER BY e.start_time DESC
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$allRegistrations = $stmt->get_result();

$upcomingRegs = [];
$registeredRegs = [];
$attendedRegs = [];

while ($reg = $allRegistrations->fetch_assoc()) {
    $isCancelled = $reg['reg_status'] === 'cancelled';

    if ($reg['reg_status'] === 'attended') {
        $attendedRegs[] = $reg;
    }

    if (!$isCancelled) {
        $registeredRegs[] = $reg;
    }

    if (
        !$isCancelled
        && $reg['event_status'] === 'published'
        && strtotime($reg['start_time']) >= time()
    ) {
        $upcomingRegs[] = $reg;
    }
}

$pageTitle = 'My Profile';
$activePage = 'profile';
require BASE_PATH . '/app/layouts/dashboard-s/header.php';
require BASE_PATH . '/app/layouts/dashboard-s/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-s/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View your personal and academic information.</p>
            </div>
            <a href="<?= url('/student/profile/edit') ?>" class="btn-secondary">
                <?= icon('edit', 'w-4 h-4') ?>
                Edit Profile
            </a>
        </div>

        <?php
        $alertType = 'success';
        $alertMessage = flash('success');
        require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error';
        $alertMessage = flash('error');
        require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="card p-6 sm:p-8 mb-6 overflow-hidden relative">
            <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-r from-blue-50 to-indigo-50"></div>
            <div class="relative flex flex-col sm:flex-row sm:items-center gap-4 pt-8 sm:pt-10">
                <span class="avatar-lg !w-16 !h-16 !text-xl border-4 border-white shadow-sm" aria-hidden="true"><?= e(substr($student['full_name'] ?? 'S', 0, 1)) ?></span>
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-gray-900 truncate"><?= e($student['full_name'] ?? '') ?></h2>
                    <p class="text-sm font-mono text-gray-500"><?= e($student['university_id'] ?? '') ?></p>
                    <div class="flex flex-wrap items-center gap-2 mt-1.5">
                        <?php if (!empty($student['department'])): ?>
                        <span class="badge badge-neutral"><?= e($student['department']) ?></span>
                        <?php endif; ?>
                        <span class="badge badge-neutral">United International University</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1.5"><?= e($student['email'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Personal Information</h3>
                <p class="text-sm text-gray-500">Your basic personal details.</p>
                <div class="mt-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">Full Name</span>
                        <span class="text-sm font-medium text-gray-900 text-right"><?= e($student['full_name'] ?? '—') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">Student ID</span>
                        <span class="text-sm font-medium text-gray-900 text-right font-mono"><?= e($student['university_id'] ?? '—') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">Email</span>
                        <span class="text-sm font-medium text-gray-900 text-right"><?= e($student['email'] ?? '—') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">Phone</span>
                        <span class="text-sm font-medium text-gray-900 text-right"><?= e($student['phone'] ?: '—') ?></span>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Academic Information</h3>
                <p class="text-sm text-gray-500">Your university information.</p>
                <div class="mt-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">University</span>
                        <span class="text-sm text-gray-500">United International University</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">Department</span>
                        <span class="text-sm font-medium text-gray-900 text-right"><?= e($student['department'] ?: '—') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">Program</span>
                        <span class="text-sm font-medium text-gray-900 text-right"><?= e($student['interests'] ?: '—') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-500">Trimester/Semester</span>
                        <span class="text-sm font-medium text-gray-900 text-right"><?= e($student['batch'] ?: '—') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <h3 class="text-lg font-semibold text-gray-900">Event Activity</h3>
            <p class="text-sm text-gray-500 mt-0.5">Your registrations and attendance at a glance.</p>
        </div>

        <div class="tabs mb-0">
            <button type="button" class="tab tab-active" data-tab="upcoming" data-tab-group="activity">Upcoming</button>
            <button type="button" class="tab" data-tab="registered" data-tab-group="activity">Registered</button>
            <button type="button" class="tab" data-tab="attended" data-tab-group="activity">Attended</button>
        </div>

        <div class="card p-6" data-tab-panel="upcoming" data-tab-panel-group="activity">
            <?php if (count($upcomingRegs) === 0): ?>
            <div class="empty-state">
                <span class="empty-state-icon text-gray-400"><?= icon('calendar', 'w-6 h-6') ?></span>
                <p class="empty-state-title">No upcoming registrations</p>
                <p class="empty-state-text">You have no upcoming event registrations right now.</p>
                <a href="<?= url('/events') ?>" class="btn-secondary btn-sm">Discover Events</a>
            </div>
            <?php else: foreach ($upcomingRegs as $reg): ?>
            <div class="flex items-center justify-between gap-4 py-3 border-b border-gray-100 last:border-0">
                <div class="min-w-0">
                    <a href="<?= url('/event?event_id=' . $reg['event_id']) ?>" class="font-medium text-gray-900 hover:text-blue-600 block truncate"><?= e($reg['title']) ?></a>
                    <p class="text-xs text-gray-500 mt-0.5 truncate"><?= e($reg['club_name'] ?? 'University Club') ?> · <?= formatDate($reg['start_time'], 'M d, Y') ?></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="badge <?= $regBadgeClass($reg['reg_status']) ?>"><?= ucfirst(e($reg['reg_status'])) ?></span>
                    <span class="text-xs text-gray-400">—</span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="card p-6 hidden" data-tab-panel="registered" data-tab-panel-group="activity">
            <?php if (count($registeredRegs) === 0): ?>
            <div class="empty-state">
                <span class="empty-state-icon text-gray-400"><?= icon('ticket', 'w-6 h-6') ?></span>
                <p class="empty-state-title">No registrations yet</p>
                <p class="empty-state-text">Register for an event to see it here.</p>
                <a href="<?= url('/events') ?>" class="btn-secondary btn-sm">Discover Events</a>
            </div>
            <?php else: foreach ($registeredRegs as $reg): ?>
            <div class="flex items-center justify-between gap-4 py-3 border-b border-gray-100 last:border-0">
                <div class="min-w-0">
                    <a href="<?= url('/event?event_id=' . $reg['event_id']) ?>" class="font-medium text-gray-900 hover:text-blue-600 block truncate"><?= e($reg['title']) ?></a>
                    <p class="text-xs text-gray-500 mt-0.5 truncate"><?= e($reg['club_name'] ?? 'University Club') ?> · <?= formatDate($reg['start_time'], 'M d, Y') ?></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="badge <?= $regBadgeClass($reg['reg_status']) ?>"><?= ucfirst(e($reg['reg_status'])) ?></span>
                    <?php if ($reg['reg_status'] === 'attended'): ?>
                    <span class="badge badge-success"><?= icon('check', 'w-3 h-3') ?> Attended</span>
                    <?php else: ?>
                    <span class="text-xs text-gray-400">—</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="card p-6 hidden" data-tab-panel="attended" data-tab-panel-group="activity">
            <?php if (count($attendedRegs) === 0): ?>
            <div class="empty-state">
                <span class="empty-state-icon text-gray-400"><?= icon('check-circle', 'w-6 h-6') ?></span>
                <p class="empty-state-title">Nothing attended yet</p>
                <p class="empty-state-text">Events you attend will show up here once checked in.</p>
                <a href="<?= url('/events') ?>" class="btn-secondary btn-sm">Discover Events</a>
            </div>
            <?php else: foreach ($attendedRegs as $reg): ?>
            <div class="flex items-center justify-between gap-4 py-3 border-b border-gray-100 last:border-0">
                <div class="min-w-0">
                    <a href="<?= url('/event?event_id=' . $reg['event_id']) ?>" class="font-medium text-gray-900 hover:text-blue-600 block truncate"><?= e($reg['title']) ?></a>
                    <p class="text-xs text-gray-500 mt-0.5 truncate"><?= e($reg['club_name'] ?? 'University Club') ?> · <?= formatDate($reg['start_time'], 'M d, Y') ?></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="badge badge-success"><?= ucfirst(e($reg['reg_status'])) ?></span>
                    <span class="badge badge-success"><?= icon('check', 'w-3 h-3') ?> Attended</span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-s/footer.php'; ?>
</div>