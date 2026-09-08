<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Reports';
$activePage = 'reports';

$reports = $db->query("
    SELECT
        r.report_id,
        r.subject,
        r.description,
        r.status,
        r.created_at,
        r.resolved_at,
        s.full_name      AS reporter_name,
        s.university_id  AS reporter_university_id,
        s.email          AS reporter_email,
        c.club_name      AS reported_club,
        e.title          AS reported_event
    FROM reports r
    LEFT JOIN students s ON s.student_id = r.reporter_id
    LEFT JOIN clubs     c ON c.club_id   = r.reported_club_id
    LEFT JOIN events    e ON e.event_id  = r.reported_event_id
    ORDER BY r.created_at DESC
");

$reportList = [];
while ($row = $reports->fetch_assoc()) {
    $reportList[] = $row;
}

$openCount = 0;
foreach ($reportList as $report) {
    if ($report['status'] === 'open') {
        $openCount++;
    }
}

$statusBadges = [
    'open'          => 'bg-amber-50 text-amber-700',
    'under_review'  => 'bg-blue-50 text-blue-700',
    'resolved'      => 'bg-emerald-50 text-emerald-700',
    'dismissed'     => 'bg-gray-100 text-gray-600',
];

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">

    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Reports</h1>
                <p class="text-sm text-gray-500 mt-1">All reports submitted by students</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <div class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-50 text-amber-700">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-2"></span>
                    <span class="font-semibold"><?= $openCount ?> Open</span>
                </div>
            </div>
        </div>

        <?php if (flash('success')): ?>
        <div class="mb-4">
            <?php
            $alertType = 'success';
            $alertMessage = flash('success');
            require_once BASE_PATH . '/app/components/alert.php';
            ?>
        </div>
        <?php endif; ?>

        <?php if (flash('error')): ?>
        <div class="mb-4">
            <?php
            $alertType = 'error';
            $alertMessage = flash('error');
            require_once BASE_PATH . '/app/components/alert.php';
            ?>
        </div>
        <?php endif; ?>

        <div class="card overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">All Reports</h2>
                <p class="text-sm text-gray-500 mt-1">Reports submitted against clubs, events, or general issues.</p>
            </div>

            <?php if (count($reportList) === 0): ?>
            <div class="p-12 text-center">
                <p class="text-gray-500">No reports submitted yet.</p>
            </div>
            <?php else: ?>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reporter</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reported Against</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($reportList as $report): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-9 h-9 rounded-full bg-red-50 text-red-600 flex items-center justify-center font-semibold text-sm mr-3">
                                        <?= e(strtoupper(substr($report['reporter_name'] ?? '?', 0, 1))) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?= e($report['reporter_name'] ?? 'Unknown') ?></p>
                                        <p class="text-xs text-gray-500"><?= e($report['reporter_university_id'] ?? '—') ?></p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 max-w-xs">
                                <p class="font-medium text-gray-900"><?= e($report['subject']) ?></p>
                                <p class="text-sm text-gray-500 truncate"><?= e($report['description']) ?></p>
                            </td>

                            <td class="px-6 py-4">
                                <?php if ($report['reported_club']): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Club: <?= e($report['reported_club']) ?></span>
                                <?php elseif ($report['reported_event']): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700">Event: <?= e($report['reported_event']) ?></span>
                                <?php else: ?>
                                    <span class="text-sm text-gray-400">General</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-700"><?= formatDate($report['created_at'], 'M d, Y') ?></p>
                                <p class="text-xs text-gray-500"><?= formatDate($report['created_at'], 'h:i A') ?></p>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $statusBadges[$report['status']] ?? $statusBadges['open'] ?>">
                                    <?= ucwords(str_replace('_', ' ', $report['status'])) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>
        </div>

    </main>

    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>

</div>