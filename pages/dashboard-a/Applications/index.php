<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Applications';
$activePage = 'applications';

// Handle approve / reject actions
if (isPost()) {
    $action = post('action');
    $clubId = (int) post('club_id');
    $adminId = currentUserId();

    if ($clubId > 0 && in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        $stmt = $db->prepare("UPDATE clubs SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE club_id = ?");
        $stmt->bind_param('sii', $status, $adminId, $clubId);

        if ($stmt->execute()) {
            $_SESSION['flash']['success'] = $action === 'approve'
                ? 'Club application approved successfully.'
                : 'Club application rejected.';
        } else {
            $_SESSION['flash']['error'] = 'Failed to update the application.';
        }
    }

    redirect('/admin/applications');
}

// All club applications with applicant details
$applications = $db->query("
    SELECT
        c.club_id,
        c.club_name,
        c.description,
        c.status,
        c.created_at,
        c.reviewed_at,
        cu.full_name AS owner_name,
        cu.email     AS owner_email,
        cu.phone     AS owner_phone,
        cu.created_at AS owner_created_at
    FROM clubs c
    LEFT JOIN club_users cu ON cu.club_user_id = c.requested_by
    ORDER BY
        CASE c.status WHEN 'pending' THEN 0 ELSE 1 END,
        c.created_at DESC
");

$statusBadges = [
    'pending'  => ['bg-amber-50 text-amber-700', 'bg-amber-500', 'Pending'],
    'approved' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500', 'Approved'],
    'rejected' => ['bg-red-50 text-red-700', 'bg-red-500', 'Rejected'],
];

$pendingCount = 0;
$applicationsList = [];
foreach ($applications as $app) {
    if ($app['status'] === 'pending') {
        $pendingCount++;
    }
    $applicationsList[] = $app;
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">

    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Applications</h1>
                <p class="text-sm text-gray-500 mt-1">Review and approve club registration applications.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <div class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-50 text-amber-700">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-2"></span>
                    <span class="font-semibold"><?= $pendingCount ?> Pending</span>
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

        <?php if (count($applicationsList) === 0): ?>
        <div class="card p-12 text-center">
            <p class="text-gray-500">No club applications yet.</p>
        </div>
        <?php else: ?>

        <div class="space-y-4">
            <?php foreach ($applicationsList as $app): ?>
            <?php
            $badge = $statusBadges[$app['status']] ?? $statusBadges['pending'];
            $isPending = $app['status'] === 'pending';
            ?>
            <div class="card overflow-hidden" data-application-card="<?= (int) $app['club_id'] ?>">

                <!-- Card header -->
                <div class="p-6 flex flex-wrap items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center text-lg font-bold">
                        <?= e(strtoupper(substr($app['club_name'], 0, 1))) ?>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 truncate"><?= e($app['club_name']) ?></h3>
                        <p class="text-sm text-gray-500">
                            Submitted <?= formatDate($app['created_at']) ?>
                            <?php if ($app['owner_name']): ?> by <?= e($app['owner_name']) ?><?php endif; ?>
                        </p>
                    </div>

                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $badge[0] ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= $badge[1] ?> mr-2"></span>
                        <?= $badge[2] ?>
                    </span>
                </div>

                <!-- Avatar letter row is separate; below: action row -->
                <div class="px-6 pb-5 flex flex-wrap items-center gap-3">

                    <?php if ($isPending): ?>

                        <button
                            type="button"
                            data-review-toggle="<?= (int) $app['club_id'] ?>"
                            class="px-4 py-2 rounded-lg text-sm font-medium bg-primary-600 text-white hover:bg-primary-700 transition"
                        >
                            Review
                        </button>

                        <form method="POST" action="<?= url('/admin/applications') ?>" class="inline">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="club_id" value="<?= (int) $app['club_id'] ?>">
                            <button type="submit" data-confirm="Approve this club application?" class="px-4 py-2 rounded-lg text-sm font-medium bg-green-500 text-white hover:bg-emerald-700 transition">
                                Approve
                            </button>
                        </form>

                        <form method="POST" action="<?= url('/admin/applications') ?>" class="inline">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="club_id" value="<?= (int) $app['club_id'] ?>">
                            <button type="submit" data-confirm="Reject this club application?" class="px-4 py-2 rounded-lg text-sm font-medium bg-red-50 text-red-600 hover:bg-red-100 transition">
                                Reject
                            </button>
                        </form>

                    <?php else: ?>

                        <button
                            type="button"
                            data-review-toggle="<?= (int) $app['club_id'] ?>"
                            class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-600 hover:bg-gray-50 transition"
                        >
                            View Details
                        </button>

                        <span class="text-sm font-medium <?= $app['status'] === 'approved' ? 'text-emerald-600' : 'text-red-600' ?>">
                            Reviewed <?= $app['reviewed_at'] ? formatDate($app['reviewed_at'], 'M d, Y h:i A') : '' ?>
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Expanded details (hidden until Review / View Details clicked) -->
                <div data-review-details="<?= (int) $app['club_id'] ?>" class="hidden border-t border-gray-100 bg-gray-50">
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Club Description</p>
                            <p class="text-sm text-gray-700 leading-relaxed">
                                <?= e($app['description'] !== null && $app['description'] !== '' ? $app['description'] : 'No description provided.') ?>
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Applicant</p>
                                <div class="flex items-center">
                                    <div class="w-9 h-9 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-semibold text-sm mr-3">
                                        <?= e(strtoupper(substr($app['owner_name'] ?? '?', 0, 1))) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?= e($app['owner_name'] ?? '—') ?></p>
                                        <p class="text-xs text-gray-500"><?= e($app['owner_email'] ?? '—') ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Phone</p>
                                    <p class="text-gray-700"><?= e($app['owner_phone'] ?? '—') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Submitted On</p>
                                    <p class="text-gray-700"><?= formatDate($app['created_at'], 'M d, Y h:i A') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

    </main>

    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-review-toggle]').forEach(function (btn) {
        btn.dataset.originalLabel = btn.textContent.trim();
        btn.addEventListener('click', function () {
            const id = btn.dataset.reviewToggle;
            const details = document.querySelector('[data-review-details="' + id + '"]');
            if (details) {
                const isShowing = !details.classList.contains('hidden');
                details.classList.toggle('hidden', isShowing);
                btn.textContent = isShowing ? btn.dataset.originalLabel : 'Hide Details';
            }
        });
    });
});
</script>