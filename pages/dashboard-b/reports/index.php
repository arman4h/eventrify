<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('reports');

$pageTitle = 'Reports';
$activePage = 'reports';

$clubId = (int) currentUser()['club_id'];

$view = get('view', 'analytics');
if (!in_array($view, ['analytics', 'issues'], true)) {
    $view = 'analytics';
}

/**
 * Club-scoped report count. A report counts as "about this club" when the
 * student named the club directly, or named one of the club's events.
 */
$REPORT_SCOPE = "(
    r.reported_club_id = ?
    OR r.reported_event_id IN (SELECT event_id FROM events WHERE club_id = ?)
)";

/** Run a club-scoped count query and return it as an int. */
$clubCount = function (string $sql) use ($db, $REPORT_SCOPE): int {
    $stmt = $db->prepare($sql);
    $clubId = (int) currentUser()['club_id'];
    $stmt->bind_param('ii', $clubId, $clubId);
    $stmt->execute();

    return (int) $stmt->get_result()->fetch_assoc()['c'];
};

$totalReports = $clubCount("SELECT COUNT(*) AS c FROM reports r WHERE $REPORT_SCOPE");
$openReportCount = $clubCount(
    "SELECT COUNT(*) AS c FROM reports r WHERE $REPORT_SCOPE AND r.status IN ('open','under_review')"
);
$closedReportCount = $clubCount(
    "SELECT COUNT(*) AS c FROM reports r WHERE $REPORT_SCOPE AND r.status IN ('resolved','dismissed')"
);
$dismissedReportCount = $clubCount(
    "SELECT COUNT(*) AS c FROM reports r WHERE $REPORT_SCOPE AND r.status = 'dismissed'"
);
$closedRate = $totalReports > 0 ? round(($closedReportCount / $totalReports) * 100) : 0;

// Mean days the administration took to close a report about this club.
$avgCloseStmt = $db->prepare("
    SELECT AVG(DATEDIFF(r.resolved_at, r.created_at)) AS days
    FROM reports r
    WHERE $REPORT_SCOPE AND r.resolved_at IS NOT NULL
");
$cid = (int) currentUser()['club_id'];
$avgCloseStmt->bind_param('ii', $cid, $cid);
$avgCloseStmt->execute();
$avgCloseRow = $avgCloseStmt->get_result()->fetch_assoc();
$avgCloseDays = $avgCloseRow && $avgCloseRow['days'] !== null
    ? round((float) $avgCloseRow['days'], 1)
    : null;

// Which event attracts the most complaints — the useful signal for a club.
$worstStmt = $db->prepare("
    SELECT e.event_id, e.title, COUNT(*) AS count
    FROM reports r
    JOIN events e ON e.event_id = r.reported_event_id
    WHERE e.club_id = ?
    GROUP BY e.event_id, e.title
    ORDER BY count DESC, e.title ASC
    LIMIT 1
");
$worstStmt->bind_param('i', $cid);
$worstStmt->execute();
$worstEvent = $worstStmt->get_result()->fetch_assoc() ?: null;

// ── Issues raised about this club or its events ───────────────────────
$issues = reportsForClub($clubId);
$openIssues = 0;
foreach ($issues as $issue) {
    if ($issue['status'] === 'open' || $issue['status'] === 'under_review') {
        $openIssues++;
    }
}

// ── Monthly series: reports filed vs reports closed ───────────────────
$series = $db->prepare("
    SELECT DATE_FORMAT(r.created_at, '%Y-%m') AS month, COUNT(*) AS count
    FROM reports r
    WHERE $REPORT_SCOPE AND r.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month ASC
");
$series->bind_param('ii', $cid, $cid);
$series->execute();
$reportMonths = $series->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

$series = $db->prepare("
    SELECT DATE_FORMAT(r.resolved_at, '%Y-%m') AS month, COUNT(*) AS count
    FROM reports r
    WHERE $REPORT_SCOPE
      AND r.resolved_at IS NOT NULL
      AND r.resolved_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month ASC
");
$series->bind_param('ii', $cid, $cid);
$series->execute();
$closedMonths = $series->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

// Build a dense 6-month axis so gaps render as real zero-height bars
// instead of silently disappearing from the chart.
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("first day of -$i month"));
    $months[] = ['key' => $key, 'label' => date('M', strtotime("first day of -$i month"))];
}
$filedBy = [];
foreach ($reportMonths as $row) {
    $filedBy[$row['month']] = (int) $row['count'];
}
$closedBy = [];
foreach ($closedMonths as $row) {
    $closedBy[$row['month']] = (int) $row['count'];
}
$maxSeries = 1;
foreach ($filedBy as $v) {
    $maxSeries = max($maxSeries, $v);
}
foreach ($closedBy as $v) {
    $maxSeries = max($maxSeries, $v);
}

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Reports</h2>
                <p class="page-subtitle"><?= $view === 'issues'
                        ? 'Problems students have reported about your club or your events.'
                        : 'Analytics and insights for your club.' ?></p>
            </div>
        </div>

        <div class="tabs mb-6">
            <a href="<?= url('/club/reports?view=analytics') ?>" class="tab <?= $view === 'analytics' ? 'tab-active' : '' ?>">Analytics</a>
            <a href="<?= url('/club/reports?view=issues') ?>" class="tab <?= $view === 'issues' ? 'tab-active' : '' ?>">
                Reported Issues
                <?php if ($openIssues > 0): ?>
                <span class="ml-1.5 badge-danger"><?= (int) $openIssues ?></span>
                <?php endif; ?>
            </a>
        </div>

<?php if ($view === 'issues'): ?>

    <div class="alert-info mb-6 flex items-start gap-3">
        <?= icon('info', 'w-5 h-5 mt-0.5 shrink-0') ?>
        <p class="text-sm">
            Students are encouraged to <strong>email your club first</strong> and only escalate to the system
            administrator if you cannot help. Anything that reaches this list was escalated — please try to resolve it
            with the student directly, then let the administration know so the report can be closed.
        </p>
    </div>

    <div class="card overflow-hidden">
        <?php if (count($issues) === 0): ?>
        <div class="empty-state">
            <span class="empty-state-icon text-gray-400"><?= icon('check-circle', 'w-6 h-6') ?></span>
            <p class="empty-state-title">No issues reported</p>
            <p class="empty-state-text">Nothing has been escalated to the administration about your club or your events.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($issues as $issue): ?>
            <div class="p-5 hover:bg-gray-50/60 transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h3 class="font-semibold text-gray-900"><?= e($issue['subject']) ?></h3>
                            <span class="<?= reportStatusBadge((string) $issue['status']) ?>">
                                <?= e(reportStatusLabel((string) $issue['status'])) ?>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Report #<?= str_pad((string) $issue['report_id'], 5, '0', STR_PAD_LEFT) ?>
                            &middot; <?= formatDate($issue['created_at'], 'M d, Y') ?>
                            &middot; <?= $issue['status'] === 'resolved' && !empty($issue['resolved_at'])
                                ? 'resolved ' . formatDate($issue['resolved_at'], 'M d, Y')
                                : 'still open' ?>
                        </p>
                    </div>
                    <span class="badge-neutral shrink-0">
                        <?= $issue['event_title'] ? 'Event issue' : 'Club issue' ?>
                    </span>
                </div>

                <p class="text-sm text-gray-700 leading-relaxed mb-3"><?= e($issue['description']) ?></p>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5">
                        <?= icon('user', 'w-3.5 h-3.5') ?>
                        <?= e($issue['reporter_name']) ?>
                        <span class="text-gray-400">(<?= e($issue['reporter_id_text']) ?>)</span>
                    </span>
                    <?php if (!empty($issue['event_title'])): ?>
                    <a href="<?= url('/event?event_id=' . (int) $issue['reported_event_id']) ?>"
                       class="flex items-center gap-1.5 text-blue-600 hover:underline">
                        <?= icon('calendar', 'w-3.5 h-3.5') ?>
                        <?= e($issue['event_title']) ?>
                    </a>
                    <?php endif; ?>
                    <span class="flex items-center gap-1.5">
                        <?= icon('clock', 'w-3.5 h-3.5') ?>
                        <?= $issue['status'] === 'open' ? 'Waiting for the administration to pick this up'
                            : ($issue['status'] === 'under_review' ? 'The administration is reviewing this'
                            : 'Closed by the administration') ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        </div>

<?php else: ?>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="stat-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <?= icon('clipboard', 'w-5 h-5') ?>
                </div>
                <div>
                    <p class="stat-label">Reports About Us</p>
                    <p class="stat-value"><?= (int) $totalReports ?></p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <?= icon('clock', 'w-5 h-5') ?>
                </div>
                <div>
                    <p class="stat-label">Still Unresolved</p>
                    <p class="stat-value"><?= (int) $openReportCount ?></p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <?= icon('check-circle', 'w-5 h-5') ?>
                </div>
                <div>
                    <p class="stat-label">Closed Rate</p>
                    <p class="stat-value"><?= (int) $closedRate ?>%</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= icon('calendar', 'w-5 h-5') ?>
                </div>
                <div>
                    <p class="stat-label">Avg. Resolution</p>
                    <p class="stat-value"><?= $avgCloseDays === null ? '—' : $avgCloseDays . 'd' ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="card p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-1">Reports filed vs closed</h3>
            <p class="text-xs text-gray-500 mb-4">Last 6 months. A widening gap means reports arrive faster than the administration closes them.</p>
            <div class="space-y-3">
                <?php foreach ($months as $m):
                    $filed = $filedBy[$m['key']] ?? 0;
                    $closed = $closedBy[$m['key']] ?? 0;
                ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600"><?= e($m['label']) ?></span>
                            <span class="font-medium text-gray-900">
                                <?= (int) $filed ?> filed
                                <span class="text-gray-400">&middot;</span>
                                <?= (int) $closed ?> closed
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div class="progress-bar">
                                <div class="progress-fill bg-red-400" style="width: <?= round(($filed / $maxSeries) * 100) ?>%"></div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-emerald-400" style="width: <?= round(($closed / $maxSeries) * 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-red-400"></span>Filed</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-400"></span>Closed</span>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Status breakdown</h3>
                <p class="text-xs text-gray-500 mb-5">How every report filed against this club ended up.</p>
                <?php
                $breakdown = [
                    ['label' => 'Open',         'color' => '#f87171', 'value' => $clubCount("SELECT COUNT(*) AS c FROM reports r WHERE $REPORT_SCOPE AND r.status = 'open'")],
                    ['label' => 'Under review', 'color' => '#fbbf24', 'value' => $clubCount("SELECT COUNT(*) AS c FROM reports r WHERE $REPORT_SCOPE AND r.status = 'under_review'")],
                    ['label' => 'Resolved',     'color' => '#34d399', 'value' => $clubCount("SELECT COUNT(*) AS c FROM reports r WHERE $REPORT_SCOPE AND r.status = 'resolved'")],
                    ['label' => 'Dismissed',    'color' => '#9ca3af', 'value' => (int) $dismissedReportCount],
                ];
                ?>
                <?php
                $pieSlices  = $breakdown;
                $pieCenter  = (string) (int) $totalReports;
                $pieCaption = $totalReports === 1 ? 'report' : 'reports';
                require BASE_PATH . '/app/components/pie-chart.php';
                ?>
            </div>

            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Most reported event</h3>
                <?php if ($worstEvent === null): ?>
                    <p class="text-sm text-gray-500">No event has been reported yet — that is a good sign.</p>
                <?php else: ?>
                    <a href="<?= url('/event?event_id=' . (int) $worstEvent['event_id']) ?>"
                       class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 transition-colors">
                        <span class="text-sm font-semibold text-gray-900 truncate"><?= e($worstEvent['title']) ?></span>
                        <span class="badge-danger shrink-0"><?= (int) $worstEvent['count'] ?> report<?= (int) $worstEvent['count'] === 1 ? '' : 's' ?></span>
                    </a>
                    <p class="text-xs text-gray-500 mt-3">
                        Students are asked to email the club before escalating, so a rising count usually means the club is
                        slow to reply rather than that the event itself is broken.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php endif; ?>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
