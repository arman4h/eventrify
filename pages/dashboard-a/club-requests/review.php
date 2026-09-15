<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$clubId = (int) get('club_id');

if ($clubId <= 0) {
    abort(404, 'Club application not found');
}

$stmt = $db->prepare("
    SELECT c.club_id, c.club_name, c.description, c.logo, c.status, c.created_at, c.reviewed_at,
        cu.full_name AS owner_name, cu.email AS owner_email, cu.phone AS owner_phone, cu.role AS owner_role
    FROM clubs c
    LEFT JOIN club_users cu ON cu.club_user_id = c.requested_by
    WHERE c.club_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $clubId);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

if (!$club) {
    abort(404, 'Club application not found');
}

if (isPost()) {
    $action = post('action');

    if ($action === 'approve') {
        $adminId = currentUserId();
        $stmt = $db->prepare("UPDATE clubs SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE club_id = ? AND status = 'pending'");
        $stmt->bind_param('ii', $adminId, $clubId);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash('success', 'Club approved successfully.');
            redirect('/admin/club-requests/review?club_id=' . $clubId);
        }

        flash('error', 'This club application has already been reviewed.');
        redirect('/admin/club-requests/review?club_id=' . $clubId);
    }

    if ($action === 'reject') {
        $reason = post('review_reason');

        if ($reason === '') {
            flash('error', 'Please provide a reason for rejection.');
            redirect('/admin/club-requests/review?club_id=' . $clubId);
        }

        $adminId = currentUserId();
        $stmt = $db->prepare("UPDATE clubs SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE club_id = ? AND status = 'pending'");
        $stmt->bind_param('ii', $adminId, $clubId);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash('success', 'Club application rejected.');
            redirect('/admin/club-requests/review?club_id=' . $clubId);
        }

        flash('error', 'This club application has already been reviewed.');
        redirect('/admin/club-requests/review?club_id=' . $clubId);
    }

    redirect('/admin/club-requests/review?club_id=' . $clubId);
}

$pageTitle = 'Review Club Request';
$activePage = 'club-requests';

$isPending = $club['status'] === 'pending';

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <nav class="breadcrumb">
            <a href="<?= url('/admin/club-requests') ?>" class="breadcrumb-link inline-flex items-center gap-1">
                <?= icon('arrow-left', 'w-4 h-4') ?> Club Requests
            </a>
            <span class="text-gray-300">/</span>
            <span class="breadcrumb-current">Review Request</span>
        </nav>

        <div class="page-header">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="page-title"><?= e($club['club_name']) ?></h1>
                    <p class="page-subtitle">Review the details below before making a decision.</p>
                </div>
            </div>
            <?php
            $badgeType = $club['status'];
            $badgeText = '';
            require BASE_PATH . '/app/components/badge.php';
            ?>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Club Information</h3>
                    <div class="flex items-start gap-4">
                        <span class="w-14 h-14 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center shrink-0"><?= icon('image', 'w-7 h-7') ?></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-lg font-bold text-gray-900"><?= e($club['club_name']) ?></p>
                            <p class="text-sm text-gray-600 leading-relaxed mt-1">
                                <?= e($club['description'] !== null && $club['description'] !== '' ? $club['description'] : 'No description provided.') ?>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5 pt-5 border-t border-gray-100">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Submitted On</p>
                            <p class="text-sm text-gray-700"><?= formatDate($club['created_at'], 'M d, Y h:i A') ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Established Year</p>
                            <p class="text-sm text-gray-700"><?= formatDate($club['created_at'], 'Y') ?></p>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Applicant</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Name</p>
                            <p class="text-sm text-gray-900 font-medium"><?= e($club['owner_name'] ?? '—') ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Student ID</p>
                            <p class="text-sm text-gray-700">—</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Position</p>
                            <p class="text-sm text-gray-700 capitalize"><?= e($club['owner_role'] ?? 'Owner') ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Phone</p>
                            <p class="text-sm text-gray-700"><?= e($club['owner_phone'] ?? '—') ?></p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Email</p>
                            <p class="text-sm text-gray-700"><?= e($club['owner_email'] ?? '—') ?></p>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Official Links</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-gray-600">Website</span>
                            <span class="text-gray-400">—</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-gray-600">Facebook</span>
                            <span class="text-gray-400">—</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-gray-600">Social</span>
                            <span class="text-gray-400">—</span>
                        </li>
                    </ul>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Verification Documents</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 rounded-lg border border-dashed border-gray-300 p-4">
                            <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><?= icon('file', 'w-4 h-4') ?></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800">Advisor letter.pdf</p>
                                <p class="text-xs text-gray-500">Pending upload</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-lg border border-dashed border-gray-300 p-4">
                            <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><?= icon('file', 'w-4 h-4') ?></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800">Proposed constitution.pdf</p>
                                <p class="text-xs text-gray-500">Pending upload</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="space-y-6">
                <?php if ($isPending): ?>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Decision</h3>
                    <p class="text-sm text-gray-600 mb-5">Approve to activate this club on the platform, or reject the application with a reason.</p>

                    <form method="POST" action="<?= url('/admin/club-requests/review?club_id=' . $clubId) ?>" class="mb-3">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="club_id" value="<?= (int) $clubId ?>">
                        <button type="submit" data-confirm="Approve <?= e($club['club_name']) ?> and let them start using Eventrify?" class="btn-success btn-lg w-full">
                            <?= icon('check-circle', 'w-5 h-5') ?> Approve Club
                        </button>
                    </form>

                    <form method="POST" action="<?= url('/admin/club-requests/review?club_id=' . $clubId) ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="club_id" value="<?= (int) $clubId ?>">
                        <label for="review_reason" class="label">Reason for rejection</label>
                        <textarea id="review_reason" name="review_reason" rows="3" class="textarea" placeholder="Required before rejecting..." required></textarea>
                        <button type="submit" class="btn-danger btn-lg w-full mt-3">
                            <?= icon('x', 'w-5 h-5') ?> Reject Request
                        </button>
                    </form>
                </div>

                <?php else: ?>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Decision Summary</h3>
                    <div class="flex items-center gap-3 mb-4">
                        <?php
                        $badgeType = $club['status'];
                        $badgeText = '';
                        require BASE_PATH . '/app/components/badge.php';
                        ?>
                    </div>
                    <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Reviewed On</span>
                            <span class="font-medium text-gray-900"><?= $club['reviewed_at'] ? formatDate($club['reviewed_at'], 'M d, Y h:i A') : '—' ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Reviewed By</span>
                            <span class="font-medium text-gray-900">Administration</span>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <div class="card p-6 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2"><?= icon('info', 'w-4 h-4 text-gray-400') ?> Notes</h3>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Approved clubs instantly appear across the platform and their owners can log in and start creating events. Rejections require a reason and can be re-verified later.
                    </p>
                </div>
            </div>

        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>