<?php
/**
 * Student report queue for the system administrator.
 *
 * Expects: $reports, $totalRows, $totalPages, $currentPage, $baseUrl,
 * $search, $statusFilter, $clubFilter, $eventFilter, $statusCounts,
 * $totalAll, $openTotal, $oldestOpenDays, $focus, $focusId, $clubOptions,
 * $eventOptions, $queueBase.
 */
?>

<?php if ($totalAll > 0): ?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $queueCards = [
        ['status' => 'open',         'label' => 'New & unassigned', 'icon' => 'alert',       'tone' => 'bg-red-50 text-red-600'],
        ['status' => 'under_review', 'label' => 'Being reviewed',   'icon' => 'clock',       'tone' => 'bg-amber-50 text-amber-600'],
        ['status' => 'resolved',     'label' => 'Resolved',         'icon' => 'check-circle','tone' => 'bg-emerald-50 text-emerald-600'],
        ['status' => 'dismissed',    'label' => 'Dismissed',        'icon' => 'x-circle',    'tone' => 'bg-gray-100 text-gray-500'],
    ];
    foreach ($queueCards as $card):
    ?>
    <a href="<?= url('/admin/reports?view=queue&status=' . $card['status']) ?>"
       class="stat-card flex items-center gap-3 hover:border-blue-300 transition-colors <?= $statusFilter === $card['status'] ? 'ring-2 ring-blue-500' : '' ?>">
        <span class="w-10 h-10 rounded-lg <?= $card['tone'] ?> inline-flex items-center justify-center shrink-0">
            <?= icon($card['icon'], 'w-5 h-5') ?>
        </span>
        <div class="min-w-0">
            <p class="stat-label truncate"><?= e($card['label']) ?></p>
            <p class="stat-value"><?= (int) $statusCounts[$card['status']] ?></p>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($oldestOpenDays !== null && $oldestOpenDays >= 3): ?>
<div class="alert-warning mb-6 flex items-start gap-3">
    <?= icon('clock', 'w-5 h-5 mt-0.5 shrink-0') ?>
    <p class="text-sm">The oldest unresolved report has been waiting <strong><?= (int) $oldestOpenDays ?> days</strong>.
        Students see the status on their dashboard, so a quick acknowledgement keeps them informed.</p>
</div>
<?php endif; ?>

<?php if ($focus): ?>
<div class="card p-6 mb-6 ring-2 ring-blue-200">
    <div class="flex items-start justify-between gap-4 mb-5">
        <div class="min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-900"><?= e($focus['subject']) ?></h2>
                <span class="<?= reportStatusBadge((string) $focus['status']) ?>">
                    <?= e(reportStatusLabel((string) $focus['status'])) ?>
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Report #<?= str_pad((string) $focus['report_id'], 5, '0', STR_PAD_LEFT) ?>
                &middot; filed <?= formatDate($focus['created_at'], 'M d, Y \a\t h:i A') ?>
                &middot; <?= (int) $focus['reporter_uid'] !== 0 ? e($focus['reporter_uid']) : '' ?>
            </p>
        </div>
        <a href="<?= url('/admin/reports?view=queue') ?>" class="btn-secondary btn-sm shrink-0">
            <?= icon('x', 'w-4 h-4') ?> Close
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Student's description</p>
                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"><?= e($focus['description']) ?></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Reported student</p>
                    <p class="text-sm font-semibold text-gray-900"><?= e($focus['reporter_name']) ?></p>
                    <p class="text-xs text-gray-600 mt-0.5"><?= e($focus['reporter_uid']) ?></p>
                    <p class="text-xs text-gray-600"><?= e($focus['reporter_department'] ?: '—') ?><?= $focus['reporter_batch'] ? ' · ' . e($focus['reporter_batch']) : '' ?></p>
                    <div class="flex flex-wrap gap-3 mt-3 pt-3 border-t border-gray-100 text-sm">
                        <a href="mailto:<?= e($focus['reporter_email']) ?>" class="text-blue-600 hover:underline break-all"><?= e($focus['reporter_email']) ?></a>
                        <?php if (!empty($focus['reporter_phone'])): ?>
                        <a href="tel:<?= e($focus['reporter_phone']) ?>" class="text-blue-600 hover:underline"><?= e($focus['reporter_phone']) ?></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Target</p>
                    <?php if (!empty($focus['event_title'])): ?>
                    <a href="<?= url('/event?event_id=' . (int) $focus['reported_event_id']) ?>"
                       class="text-sm font-semibold text-blue-600 hover:underline"><?= e($focus['event_title']) ?></a>
                    <p class="text-xs text-gray-600 mt-1">
                        <?= formatDate($focus['event_start'], 'M d, Y') ?>
                        &middot; <?= formatDate($focus['event_start'], 'g:i A') ?>–<?= formatDate($focus['event_end'], 'g:i A') ?>
                    </p>
                    <p class="text-xs text-gray-600">Venue: <?= e($focus['event_venue'] ?: '—') ?></p>
                    <p class="text-xs text-gray-600 mt-1">
                        Event status: <span class="badge-neutral"><?= e(ucfirst((string) $focus['event_status'])) ?></span>
                    </p>
                    <?php else: ?>
                    <p class="text-sm text-gray-500">Not about a specific event</p>
                    <?php endif; ?>

                    <?php if (!empty($focus['club_name'])): ?>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs text-gray-500">Organising club</p>
                        <p class="text-sm font-semibold text-gray-900"><?= e($focus['club_name']) ?></p>
                        <?php if (!empty($focus['club_email'])): ?>
                        <a href="mailto:<?= e($focus['club_email']) ?>" class="text-xs text-blue-600 hover:underline break-all"><?= e($focus['club_email']) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Take action</p>

            <?php
            $allowedNext = reportStatusTransitions()[(string) $focus['status']] ?? [];
            $actionMeta = [
                'under_review' => ['label' => 'Start reviewing', 'btn' => 'btn-primary', 'icon' => 'eye',
                                   'help' => 'Marks you as the person handling this and tells the student it is being looked into.'],
                'resolved'     => ['label' => 'Mark resolved',  'btn' => 'btn-success', 'icon' => 'check-circle',
                                   'help' => 'Use when the issue has actually been put right.'],
                'dismissed'    => ['label' => 'Dismiss',         'btn' => 'btn-secondary', 'icon' => 'x-circle',
                                   'help' => 'Closes the report without action — e.g. it is not a platform issue.'],
                'open'         => ['label' => 'Reopen',          'btn' => 'btn-secondary', 'icon' => 'refresh',
                                   'help' => 'Puts the report back in the queue for review.'],
            ];
            ?>

            <?php if (count($allowedNext) === 0): ?>
            <p class="text-sm text-gray-500">This report is closed and has no further actions available.</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($allowedNext as $next): ?>
                <?php $meta = $actionMeta[$next] ?? null; ?>
                <?php if ($meta === null) { continue; } ?>
                <form method="POST" action="<?= url('/admin/reports?view=queue') ?>" class="rounded-lg border border-gray-200 p-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="set_status">
                    <input type="hidden" name="report_id" value="<?= (int) $focus['report_id'] ?>">
                    <input type="hidden" name="status" value="<?= e($next) ?>">
                    <button type="submit" class="<?= $meta['btn'] ?> btn-sm w-full"><?= icon($meta['icon'], 'w-4 h-4') ?> <?= e($meta['label']) ?></button>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed"><?= e($meta['help']) ?></p>
                </form>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="mt-5 pt-4 border-t border-gray-100 text-sm space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Current status</span>
                    <span class="<?= reportStatusBadge((string) $focus['status']) ?>"><?= e(reportStatusLabel((string) $focus['status'])) ?></span>
                </div>
                <?php if (!empty($focus['resolved_at'])): ?>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Closed on</span>
                    <span class="font-medium text-gray-900"><?= formatDate($focus['resolved_at'], 'M d, Y') ?></span>
                </div>
                <?php endif; ?>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Handled by</span>
                    <span class="font-medium text-gray-900"><?= e($focus['reviewer_name'] ?: '—') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card p-4 mb-6">
    <form method="GET" action="<?= url('/admin/reports') ?>" class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
        <input type="hidden" name="view" value="queue">
        <div class="lg:col-span-4">
            <label for="q" class="label">Search</label>
            <div class="search-wrapper">
                <?= icon('search', 'search-icon w-4 h-4') ?>
                <input type="text" id="q" name="q" value="<?= e($search) ?>" class="input w-full pl-10"
                       placeholder="Subject, description, student, event, club...">
            </div>
        </div>
        <div class="lg:col-span-2">
            <label for="status" class="label">Status</label>
            <select id="status" name="status" class="select">
                <option value="">All</option>
                <?php foreach (reportAllStatuses() as $s): ?>
                <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>>
                    <?= e(reportStatusLabel($s)) ?> (<?= (int) $statusCounts[$s] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lg:col-span-3">
            <label for="club" class="label">Club</label>
            <select id="club" name="club" class="select">
                <option value="">All clubs</option>
                <?php foreach ($clubOptions as $club): ?>
                <option value="<?= (int) $club['club_id'] ?>" <?= $clubFilter === (int) $club['club_id'] ? 'selected' : '' ?>>
                    <?= e($club['club_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lg:col-span-3">
            <label for="event" class="label">Event</label>
            <select id="event" name="event" class="select">
                <option value="">All events</option>
                <?php foreach ($eventOptions as $ev): ?>
                <option value="<?= (int) $ev['event_id'] ?>" <?= $eventFilter === (int) $ev['event_id'] ? 'selected' : '' ?>>
                    <?= e(truncate($ev['title'], 44)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lg:col-span-12 flex flex-wrap gap-3">
            <button type="submit" class="btn-primary btn-sm"><?= icon('filter', 'w-4 h-4') ?> Apply filters</button>
            <?php if ($search !== '' || $statusFilter !== '' || $clubFilter > 0 || $eventFilter > 0): ?>
            <a href="<?= url('/admin/reports?view=queue') ?>" class="btn-ghost btn-sm">Clear all</a>
            <span class="text-xs text-gray-500 self-center"><?= (int) $totalRows ?> matching <?= $totalRows === 1 ? 'report' : 'reports' ?></span>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <?php if (count($reports) === 0): ?>
    <div class="empty-state">
        <span class="empty-state-icon text-gray-400"><?= icon('shield', 'w-6 h-6') ?></span>
        <p class="empty-state-title"><?= $totalRows === 0 && $search === '' && $statusFilter === '' && $clubFilter === 0 && $eventFilter === 0
            ? 'No reports waiting'
            : 'No matching reports' ?></p>
        <p class="empty-state-text">
            <?= $totalRows === 0 && $search === '' && $statusFilter === '' && $clubFilter === 0 && $eventFilter === 0
                ? 'Nothing here. When a student escalates a problem to the system administrator it lands in this queue.'
                : 'Try widening the search or clearing the filters.' ?>
        </p>
        <?php if ($search !== '' || $statusFilter !== '' || $clubFilter > 0 || $eventFilter > 0): ?>
        <a href="<?= url('/admin/reports?view=queue') ?>" class="btn-secondary btn-sm">Clear filters</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-wrapper !rounded-none !border-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Report</th>
                    <th>Student</th>
                    <th>Target</th>
                    <th>Filed</th>
                    <th>Status</th>
                    <th class="!text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                <tr class="<?= $focusId === (int) $report['report_id'] ? 'bg-blue-50/60' : '' ?>">
                    <td class="max-w-sm">
                        <p class="font-medium text-gray-900"><?= e($report['subject']) ?></p>
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2"><?= e(truncate($report['description'], 110)) ?></p>
                    </td>
                    <td class="whitespace-nowrap">
                        <p class="text-sm font-medium text-gray-900"><?= e($report['reporter_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= e($report['reporter_uid']) ?></p>
                    </td>
                    <td class="text-sm text-gray-600 max-w-[16rem]">
                        <?php if (!empty($report['event_title'])): ?>
                        <p class="font-medium text-gray-900 line-clamp-1"><?= e($report['event_title']) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500 line-clamp-1"><?= e($report['club_name'] ?: 'Platform-wide') ?></p>
                    </td>
                    <td class="whitespace-nowrap text-sm text-gray-600"><?= formatDate($report['created_at'], 'M d, Y') ?></td>
                    <td>
                        <span class="<?= reportStatusBadge((string) $report['status']) ?>">
                            <?= e(reportStatusLabel((string) $report['status'])) ?>
                        </span>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= url('/admin/reports?view=queue&focus=' . (int) $report['report_id']) ?>" class="btn-ghost btn-sm">
                            <?= $report['status'] === 'open' || $report['status'] === 'under_review' ? 'Review' : 'View' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    require BASE_PATH . '/app/components/pagination.php';
    ?>
    <?php endif; ?>
</div>
