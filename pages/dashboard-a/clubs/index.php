<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'Clubs';
$activePage = 'clubs';

if (isPost()) {
    $action = post('action');
    $clubId = (int) post('club_id');

    if ($clubId > 0 && in_array($action, ['suspend', 'reactivate'], true)) {
        $status  = $action === 'suspend' ? 'rejected' : 'approved';

        $stmt = $db->prepare("UPDATE clubs SET status = ? WHERE club_id = ?");
        $stmt->bind_param('si', $status, $clubId);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash('success', $action === 'suspend' ? 'Club suspended. Re-verify to reactivate.' : 'Club reactivated.');
        } else {
            flash('error', 'Failed to update the club.');
        }
    }

    redirect('/admin/clubs');
}

$search = get('q');

$conditions = ["c.status IN ('approved', 'rejected')"];
$bindings   = [];
$types      = '';

if ($search !== '') {
    $conditions[] = "c.club_name LIKE ?";
    $bindings[]   = "%$search%";
    $types       .= 's';
}

$whereSql = 'WHERE ' . implode(' AND ', $conditions);

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

$sql = "SELECT c.club_id, c.club_name, c.status, c.created_at,
        (SELECT COUNT(*) FROM events e WHERE e.club_id = c.club_id) AS event_count,
        (SELECT COUNT(*) FROM club_users cu WHERE cu.club_id = c.club_id) AS member_count
        FROM clubs c
        $whereSql
        ORDER BY c.club_name ASC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$clubs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$activeCount = (int) $db->query("SELECT COUNT(*) AS n FROM clubs WHERE status = 'approved'")->fetch_assoc()['n'];

$columns = ['Club', 'University', 'Status', 'Events', 'Members', 'Registered'];

$rows = [];

foreach ($clubs as $club) {
    $isActive   = $club['status'] === 'approved';
    $reviewUrl  = url('/admin/club-requests/review?club_id=' . $club['club_id']);

    $actions = '<a href="' . e($reviewUrl) . '" class="btn-ghost btn-sm">View</a>';

    if ($isActive) {
        $actions .= '<form method="POST" action="' . e(url('/admin/clubs')) . '" class="inline">
            <input type="hidden" name="action" value="suspend">
            <input type="hidden" name="club_id" value="' . (int) $club['club_id'] . '">
            <button type="submit" data-confirm="Suspend ' . e($club['club_name']) . '? Users can no longer access this club until it is re-verified." class="btn-danger btn-sm">Suspend</button>
        </form>';
    } else {
        $actions .= '<form method="POST" action="' . e(url('/admin/clubs')) . '" class="inline">
            <input type="hidden" name="action" value="reactivate">
            <input type="hidden" name="club_id" value="' . (int) $club['club_id'] . '">
            <button type="submit" data-confirm="Reactivate ' . e($club['club_name']) . '?" class="btn-success btn-sm">Reactivate</button>
        </form>';
    }

    $actions .= '<a href="' . e($reviewUrl) . '" class="btn-ghost btn-sm">Edit</a>';

    $clubCell = '<div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 font-bold text-sm">' . e(strtoupper(substr($club['club_name'], 0, 1))) . '</span>
        <div class="min-w-0">
            <p class="font-medium text-gray-900 truncate">' . e($club['club_name']) . '</p>
            <p class="text-xs text-gray-500">Club ID: ' . (int) $club['club_id'] . '</p>
        </div>
    </div>';

    $statusCell = '';
    $badgeType  = $isActive ? 'active' : 'suspended';
    ob_start();
    require BASE_PATH . '/app/components/badge.php';
    $statusCell = ob_get_clean();

    $rows[] = [
        $clubCell,
        '<p class="text-sm text-gray-600">United International University</p>',
        $statusCell,
        '<p class="text-sm font-medium text-gray-900">' . (int) $club['event_count'] . '</p>',
        '<p class="text-sm font-medium text-gray-900">' . (int) $club['member_count'] . '</p>',
        formatDate($club['created_at'], 'M d, Y'),
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
                <h1 class="page-title">Clubs</h1>
                <p class="page-subtitle">Manage verified clubs and their active status.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <?= $activeCount ?> Active
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
                <form method="GET" action="<?= url('/admin/clubs') ?>" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"><?= icon('search', 'w-4 h-4') ?></span>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search clubs..." class="input pl-10">
                    </div>
                    <button type="submit" class="btn-secondary">Search</button>
                    <?php if ($search !== ''): ?>
                    <a href="<?= url('/admin/clubs') ?>" class="btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="p-4 sm:p-6">
                <?php
                $actionSlot = 'Actions';
                $emptyMessage = 'No verified clubs found.';
                require BASE_PATH . '/app/components/table.php';

                $paginationQuery = $_GET;
                unset($paginationQuery['page']);
                $paginationBase = url('/admin/clubs') . (count($paginationQuery) > 0 ? '?' . http_build_query($paginationQuery) : '');

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