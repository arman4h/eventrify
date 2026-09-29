<?php
/**
 * Analytics tab of /admin/reports.
 *
 * Expects: $dateFrom, $dateTo, $eventsCount, $registrationsCount,
 * $attendanceRate, $activeClubs, $months, $maxEvents, $maxRegs,
 * $topClubs, $maxTopClubs, $categories, $maxCategories, $statusCounts,
 * $totalAll, $queueBase.
 */
?>

<form method="GET" action="<?= url('/admin/reports') ?>" class="card p-4 mb-6">
    <input type="hidden" name="view" value="analytics">
    <div class="flex flex-col sm:flex-row items-end sm:items-center gap-3">
        <div class="w-full sm:w-auto">
            <label for="date_from" class="label">From</label>
            <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>" class="input sm:w-44">
        </div>
        <div class="w-full sm:w-auto">
            <label for="date_to" class="label">To</label>
            <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>" class="input sm:w-44">
        </div>
        <button type="submit" class="btn-primary">Apply</button>
        <a href="<?= url('/admin/reports?view=analytics') ?>" class="btn-secondary">Clear</a>
        <p class="text-xs text-gray-400 sm:ml-auto"><?= icon('info', 'w-3.5 h-3.5 inline -mt-0.5') ?> Counts events and registrations inside the selected window.</p>
    </div>
</form>

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

<div class="card p-6 mt-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Report resolution</h3>
            <p class="text-sm text-gray-500 mt-1">How student issue reports have been handled, all time.</p>
        </div>
        <a href="<?= url($queueBase) ?>" class="btn-primary btn-sm">Open the queue</a>
    </div>

    <?php if ($totalAll === 0): ?>
        <?php
        $emptyIcon = 'shield';
        $emptyTitle = 'No reports submitted yet';
        $emptyText = 'When a student escalates a problem to the system administrator it will appear here.';
        require BASE_PATH . '/app/components/empty-state.php';
        ?>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach (reportAllStatuses() as $status): ?>
        <a href="<?= url('/admin/reports?view=queue&status=' . $status) ?>"
           class="rounded-lg border border-gray-200 p-4 hover:border-blue-300 hover:bg-blue-50/40 transition-colors">
            <div class="flex items-center justify-between mb-2">
                <span class="<?= reportStatusBadge($status) ?>"><?= e(reportStatusLabel($status)) ?></span>
                <span class="text-xl font-bold text-gray-900"><?= (int) $statusCounts[$status] ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill <?= $status === 'resolved' ? 'bg-emerald-500' : ($status === 'dismissed' ? 'bg-gray-400' : ($status === 'under_review' ? 'bg-amber-500' : 'bg-red-500')) ?>"
                     style="width: <?= $totalAll > 0 ? round(((int) $statusCounts[$status] / $totalAll) * 100) : 0 ?>%"></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
