<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireStudent();

$studentId = (int) currentUserId();

$pageTitle  = 'My Reports';
$activePage = 'reports';

// ── Withdraw a report that has not been picked up yet ────────────────
if (isPost() && post('action') === 'withdraw') {
    $reportId = (int) post('report_id');

    $stmt = $db->prepare("
        DELETE FROM reports
        WHERE report_id = ? AND reporter_id = ? AND status = 'open'
    ");
    $stmt->bind_param('ii', $reportId, $studentId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        flash('success', 'Report withdrawn.');
    } else {
        flash('error', 'Only reports that have not been picked up yet can be withdrawn.');
    }

    redirect('/student/reports');
}

$search       = get('q');
$statusFilter = get('status');
$viewId       = (int) get('view');

if (!in_array($statusFilter, reportAllStatuses(), true)) {
    $statusFilter = '';
}

$where  = 'r.reporter_id = ?';
$types  = 'i';
$params = [$studentId];

if ($search !== '') {
    $where .= ' AND (r.subject LIKE ? OR r.description LIKE ?)';
    $types .= 'ss';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter !== '') {
    $where .= ' AND r.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

// ── Counts for the summary strip ──────────────────────────────────────
$countStmt = $db->prepare("SELECT status, COUNT(*) AS c FROM reports WHERE reporter_id = ? GROUP BY status");
$countStmt->bind_param('i', $studentId);
$countStmt->execute();

$counts = array_fill_keys(reportAllStatuses(), 0);
$totalReports = 0;
foreach ($countStmt->get_result() as $row) {
    $counts[(string) $row['status']] = (int) $row['c'];
    $totalReports += (int) $row['c'];
}
$unresolved = $counts['open'] + $counts['under_review'];

// ── Paginated list ────────────────────────────────────────────────────
$perPage     = 10;
$currentPage = max(1, (int) get('page', 1));

$countStmt = $db->prepare("SELECT COUNT(*) AS c FROM reports r WHERE $where");
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = (int) $countStmt->get_result()->fetch_assoc()['c'];

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;

$listStmt = $db->prepare("
    SELECT r.report_id, r.subject, r.description, r.status, r.created_at, r.resolved_at,
           r.reported_event_id, r.reported_club_id,
           e.title AS event_title, c.club_name
    FROM reports r
    LEFT JOIN events e ON e.event_id = r.reported_event_id
    LEFT JOIN clubs c  ON c.club_id = r.reported_club_id
    WHERE $where
    ORDER BY FIELD(r.status, 'open', 'under_review', 'resolved', 'dismissed'), r.created_at DESC
    LIMIT ? OFFSET ?
");
$listStmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
$listStmt->execute();
$reports = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

// ── Full detail for one report ────────────────────────────────────────
$detail = null;
if ($viewId > 0) {
    $stmt = $db->prepare("
        SELECT r.*, e.title AS event_title, c.club_name,
               a.full_name AS reviewed_by_name
        FROM reports r
        LEFT JOIN events e ON e.event_id = r.reported_event_id
        LEFT JOIN clubs c  ON c.club_id = r.reported_club_id
        LEFT JOIN system_admins a ON a.admin_id = r.reviewed_by
        WHERE r.report_id = ? AND r.reporter_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $viewId, $studentId);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
}

$statusSteps = [
    'open'         => ['Open', 'Received by the system administrator'],
    'under_review' => ['Under review', 'An administrator is looking into it'],
    'resolved'     => ['Resolved', 'The issue has been dealt with'],
    'dismissed'    => ['Dismissed', 'Closed without further action'],
];

$queryParams = [];
if ($search !== '') {
    $queryParams['q'] = $search;
}
if ($statusFilter !== '') {
    $queryParams['status'] = $statusFilter;
}
$baseUrl = '/student/reports' . (count($queryParams) > 0 ? '?' . http_build_query($queryParams) : '');

require BASE_PATH . '/app/layouts/dashboard-s/header.php';
require BASE_PATH . '/app/layouts/dashboard-s/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-s/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">My Reports</h1>
                <p class="page-subtitle">Every problem you have sent to the system administrator, and where it stands.</p>
            </div>
            <a href="<?= url('/student/report') ?>" class="btn-primary btn-sm">
                <?= icon('plus', 'w-4 h-4') ?>
                New Report
            </a>
        </div>

        <?php
        $alertType = 'success';
        $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error';
        $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <?php if ($unresolved > 0): ?>
        <div class="alert-info mb-6 flex items-start gap-3">
            <?= icon('info', 'w-5 h-5 mt-0.5 shrink-0') ?>
            <p class="text-sm">
                You have <strong><?= $unresolved ?></strong> <?= $unresolved === 1 ? 'report' : 'reports' ?> waiting for a
                response. If the club involved has not helped, you can contact them directly from the
                <a href="<?= url('/student/report') ?>" class="underline font-medium">Report a Problem</a> page.
            </p>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <?php
            $summaryCards = [
                ['label' => 'Total Sent', 'value' => $totalReports, 'icon' => 'clipboard', 'tone' => 'bg-blue-50 text-blue-600'],
                ['label' => 'Open',        'value' => $counts['open'], 'icon' => 'alert', 'tone' => 'bg-red-50 text-red-600'],
                ['label' => 'Under Review','value' => $counts['under_review'], 'icon' => 'clock', 'tone' => 'bg-amber-50 text-amber-600'],
                ['label' => 'Resolved',    'value' => $counts['resolved'], 'icon' => 'check-circle', 'tone' => 'bg-emerald-50 text-emerald-600'],
            ];
            foreach ($summaryCards as $card):
            ?>
            <div class="stat-card flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg <?= $card['tone'] ?> inline-flex items-center justify-center shrink-0">
                    <?= icon($card['icon'], 'w-5 h-5') ?>
                </span>
                <div class="min-w-0">
                    <p class="stat-label truncate"><?= e($card['label']) ?></p>
                    <p class="stat-value"><?= $card['value'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($detail): ?>
        <div class="card p-6 mb-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div class="min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-lg font-semibold text-gray-900"><?= e($detail['subject']) ?></h2>
                        <span class="<?= reportStatusBadge((string) $detail['status']) ?>">
                            <?= e(reportStatusLabel((string) $detail['status'])) ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Report #<?= str_pad((string) $detail['report_id'], 5, '0', STR_PAD_LEFT) ?>
                        &middot; sent <?= formatDate($detail['created_at'], 'M d, Y \a\t h:i A') ?>
                    </p>
                </div>
                <a href="<?= url('/student/reports') ?>" class="btn-secondary btn-sm shrink-0">
                    <?= icon('x', 'w-4 h-4') ?> Close
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-5">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">What you reported</p>
                        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"><?= e($detail['description']) ?></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="rounded-lg border border-gray-200 p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Event</p>
                            <?php if (!empty($detail['event_title'])): ?>
                            <a href="<?= url('/event?event_id=' . (int) $detail['reported_event_id']) ?>"
                               class="text-sm font-medium text-blue-600 hover:underline"><?= e($detail['event_title']) ?></a>
                            <?php else: ?>
                            <p class="text-sm text-gray-500">Not about a specific event</p>
                            <?php endif; ?>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Club</p>
                            <p class="text-sm font-medium text-gray-900"><?= e($detail['club_name'] ?: 'Not about a specific club') ?></p>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Progress</p>
                    <ol class="space-y-4">
                        <?php
                        $order = ['open', 'under_review', 'resolved', 'dismissed'];
                        $currentPos = array_search((string) $detail['status'], $order, true);
                        $isDismissed = $detail['status'] === 'dismissed';
                        foreach ($order as $stepIndex => $step):
                            if ($isDismissed && $step === 'resolved') {
                                continue;
                            }
                            $done = $stepIndex <= (int) $currentPos;
                            $isCurrent = $step === (string) $detail['status'];
                        ?>
                        <li class="flex items-start gap-3">
                            <span class="inline-flex w-6 h-6 rounded-full items-center justify-center shrink-0 mt-0.5
                                <?= $done ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' ?>">
                                <?php if ($done): ?><?= icon('check', 'w-3.5 h-3.5') ?><?php else: ?><span class="text-[10px] font-bold"><?= $stepIndex + 1 ?></span><?php endif; ?>
                            </span>
                            <div>
                                <p class="text-sm font-medium <?= $isCurrent ? 'text-gray-900' : 'text-gray-600' ?>">
                                    <?= e($statusSteps[$step][0]) ?>
                                    <?php if ($isCurrent): ?><span class="badge-primary ml-1">Current</span><?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5"><?= e($statusSteps[$step][1]) ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ol>

                    <?php if (!empty($detail['reviewed_at'])): ?>
                    <div class="mt-5 pt-4 border-t border-gray-100 text-sm space-y-1">
                        <p class="text-gray-500">Last updated <?= formatDate($detail['reviewed_at'], 'M d, Y h:i A') ?></p>
                        <p class="text-gray-500">By <?= e($detail['reviewed_by_name'] ?: 'the administration') ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($detail['status'] === 'open'): ?>
                    <form method="POST" action="<?= url('/student/reports') ?>" class="mt-5">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="withdraw">
                        <input type="hidden" name="report_id" value="<?= (int) $detail['report_id'] ?>">
                        <button type="submit" class="btn-ghost btn-sm w-full" data-confirm="Withdraw this report? It will be removed permanently.">
                            Withdraw this report
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card p-4 mb-6">
            <form method="GET" action="<?= url('/student/reports') ?>" class="flex flex-col sm:flex-row gap-3">
                <div class="search-wrapper flex-1">
                    <?= icon('search', 'search-icon w-4 h-4') ?>
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search your reports..." class="input w-full pl-10">
                </div>
                <select name="status" class="select sm:w-52">
                    <option value="">All statuses</option>
                    <?php foreach (reportAllStatuses() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(reportStatusLabel($s)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-secondary"><?= icon('filter', 'w-4 h-4') ?> Filter</button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a href="<?= url('/student/reports') ?>" class="btn-ghost btn-sm self-center">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card overflow-hidden">
            <?php if (count($reports) === 0): ?>
            <div class="empty-state">
                <span class="empty-state-icon text-gray-400"><?= icon('clipboard', 'w-6 h-6') ?></span>
                <p class="empty-state-title"><?= ($search !== '' || $statusFilter !== '') ? 'No matching reports' : 'You have not reported anything yet' ?></p>
                <p class="empty-state-text">
                    <?= ($search !== '' || $statusFilter !== '')
                        ? 'Try a different search or clear the filters.'
                        : 'If something goes wrong with an event, contact the club first — and if they cannot help, send it to us.' ?>
                </p>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a href="<?= url('/student/reports') ?>" class="btn-secondary btn-sm">Clear filters</a>
                <?php else: ?>
                <a href="<?= url('/student/report') ?>" class="btn-primary btn-sm">Report a problem</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-wrapper !rounded-none !border-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>About</th>
                            <th>Sent</th>
                            <th>Status</th>
                            <th class="!text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                        <tr>
                            <td class="max-w-md">
                                <p class="font-medium text-gray-900"><?= e($report['subject']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-1"><?= e(truncate($report['description'], 80)) ?></p>
                            </td>
                            <td class="text-sm text-gray-600">
                                <?php if (!empty($report['event_title'])): ?>
                                <p class="font-medium text-gray-900"><?= e($report['event_title']) ?></p>
                                <p class="text-xs text-gray-500"><?= e($report['club_name'] ?? '') ?></p>
                                <?php elseif (!empty($report['club_name'])): ?>
                                <p class="font-medium text-gray-900"><?= e($report['club_name']) ?></p>
                                <p class="text-xs text-gray-500">Club</p>
                                <?php else: ?>
                                <p class="text-gray-500">General platform issue</p>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap text-sm text-gray-600"><?= formatDate($report['created_at'], 'M d, Y') ?></td>
                            <td>
                                <span class="<?= reportStatusBadge((string) $report['status']) ?>">
                                    <?= e(reportStatusLabel((string) $report['status'])) ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="<?= url('/student/reports?view=' . (int) $report['report_id']) ?>" class="btn-ghost btn-sm">View</a>
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
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-s/footer.php'; ?>
</div>
