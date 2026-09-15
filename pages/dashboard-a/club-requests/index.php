<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'Club Requests';
$activePage = 'club-requests';

if (isPost()) {
    $action = post('action');
    $clubId = (int) post('club_id');

    if ($clubId > 0 && in_array($action, ['approve', 'reject'], true)) {
        $status  = $action === 'approve' ? 'approved' : 'rejected';
        $adminId = currentUserId();

        $stmt = $db->prepare("UPDATE clubs SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE club_id = ? AND status = 'pending'");
        $stmt->bind_param('sii', $status, $adminId, $clubId);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash('success', $action === 'approve' ? 'Club approved successfully.' : 'Club application rejected.');
        } else {
            flash('error', 'Failed to update the club application.');
        }
    }

    redirect('/admin/club-requests');
}

$statusFilter = get('status');
$search       = get('q');

$conditions = [];
$bindings   = [];
$types      = '';

if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
    $conditions[] = "c.status = ?";
    $bindings[]   = $statusFilter;
    $types       .= 's';
}

if ($search !== '') {
    $conditions[] = "(c.club_name LIKE ? OR c.description LIKE ? OR c.university LIKE ? OR c.club_type LIKE ?)";
    $bindings[]   = "%$search%";
    $bindings[]   = "%$search%";
    $bindings[]   = "%$search%";
    $bindings[]   = "%$search%";
    $types       .= 'ssss';
}

$whereSql = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM clubs c $whereSql");
if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()['n'];

$page       = max(1, (int) get('page', 1));
$limit      = 10;
$offset     = ($page - 1) * $limit;
$totalPages = max(1, (int) ceil($total / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$sql = "SELECT c.club_id, c.club_name, c.description, c.university, c.club_type, c.status, c.created_at, c.reviewed_at,
        cu.full_name AS owner_name, cu.email AS owner_email
        FROM clubs c
        LEFT JOIN club_users cu ON cu.club_user_id = c.requested_by
        $whereSql
        ORDER BY CASE c.status WHEN 'pending' THEN 0 ELSE 1 END, c.created_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pendingCount = (int) $db->query("SELECT COUNT(*) AS n FROM clubs WHERE status = 'pending'")->fetch_assoc()['n'];

$columns = ['Club', 'Applicant', 'Submitted', 'Status'];

$rows = [];

foreach ($requests as $request) {
    $reviewUrl = url('/admin/club-requests/review?club_id=' . $request['club_id']);
    $isPending = $request['status'] === 'pending';

    $badgeType = $request['status'];

    $actions = '';

    if ($isPending) {
        $actions .= '<a href="' . e($reviewUrl) . '" class="btn-primary btn-sm mb-1">Review</a>';

        $actions .= '<form method="POST" action="' . e(url('/admin/club-requests')) . '" class="inline">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="club_id" value="' . (int) $request['club_id'] . '">
            <button type="submit" data-confirm="Approve this club application?" class="btn-success btn-sm mb-1">Approve</button>
        </form>';

        $actions .= '<form method="POST" action="' . e(url('/admin/club-requests')) . '" class="inline">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="club_id" value="' . (int) $request['club_id'] . '">
            <button type="submit" data-confirm="Reject this club application?" class="btn-danger btn-sm">Reject</button>
        </form>';
    } else {
        $actions .= '<a href="' . e($reviewUrl) . '" class="btn-ghost btn-sm">View</a>';
    }

    $metaParts = array_filter([$request['university'], $request['club_type']]);

$clubCell = '<div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 font-bold text-sm">' . e(strtoupper(substr($request['club_name'], 0, 1))) . '</span>
        <div class="min-w-0">
            <p class="font-medium text-gray-900 truncate">' . e($request['club_name']) . '</p>
            <p class="text-xs text-gray-500 truncate max-w-xs">' . e(implode(' · ', $metaParts)) . '</p>
            <p class="text-[11px] text-gray-400 truncate max-w-xs">' . e(substr((string) $request['description'], 0, 60)) . '</p>
        </div>
    </div>';

    $applicantCell = '<div class="min-w-0">
        <p class="font-medium text-gray-900 truncate">' . e($request['owner_name'] ?? '—') . '</p>
        <p class="text-xs text-gray-500 truncate">' . e($request['owner_email'] ?? 'No applicant on file') . '</p>
    </div>';

    $statusCell = '';
    $badgeType = $request['status'];
    ob_start();
    require BASE_PATH . '/app/components/badge.php';
    $statusCell = ob_get_clean();

    $rows[] = [
        $clubCell,
        $applicantCell,
        formatDate($request['created_at'], 'M d, Y'),
        $statusCell,
        $actions,
    ];
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">Club Requests</h1>
                <p class="page-subtitle">Review, approve, or reject club applications.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <?= $pendingCount ?> Pending
            </div>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="card overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <form method="GET" action="<?= url('/admin/club-requests') ?>" class="flex flex-col sm:flex-row gap-3">
                    <select name="status" class="select sm:w-44">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>

                    <div class="flex flex-1 gap-3">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"><?= icon('search', 'w-4 h-4') ?></span>
                            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by club name..." class="input pl-10">
                        </div>
                        <button type="submit" class="btn-secondary">Filter</button>
                        <?php if ($statusFilter !== '' || $search !== ''): ?>
                        <a href="<?= url('/admin/club-requests') ?>" class="btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-6">
                <?php
                $actionSlot = 'Actions';
                $emptyMessage = 'No club requests found.';
                require BASE_PATH . '/app/components/table.php';

                $paginationQuery = $_GET;
                unset($paginationQuery['page']);
                $paginationBase = url('/admin/club-requests') . (count($paginationQuery) > 0 ? '?' . http_build_query($paginationQuery) : '');

                $baseUrl = $paginationBase;
                $totalPages = $totalPages;
                $currentPage = $page;
                require BASE_PATH . '/app/components/pagination.php';
                ?>
            </div>
        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>