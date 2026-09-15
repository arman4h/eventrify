<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'Events';
$activePage = 'events';

$search       = get('q');
$statusFilter = get('status');

$conditions = [];
$bindings   = [];
$types      = '';

if ($search !== '') {
    $conditions[] = "e.title LIKE ?";
    $bindings[]   = "%$search%";
    $types       .= 's';
}

if ($statusFilter !== '' && in_array($statusFilter, ['draft', 'published', 'cancelled', 'completed'], true)) {
    $conditions[] = "e.status = ?";
    $bindings[]   = $statusFilter;
    $types       .= 's';
}

$whereSql = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM events e $whereSql");
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

$sql = "SELECT e.event_id, e.title, e.category, e.start_time, e.capacity, e.status, e.venue,
        c.club_name,
        (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id) AS reg_count
        FROM events e
        LEFT JOIN clubs c ON c.club_id = e.club_id
        $whereSql
        ORDER BY e.start_time DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$columns = ['Event', 'Club', 'Category', 'Date', 'Registrations', 'Capacity', 'Status'];

$rows = [];

foreach ($events as $event) {
    $actions = '<div class="flex items-center justify-end gap-2">'
    . '<a href="' . e(url('/event?event_id=' . $event['event_id'])) . '" class="btn-ghost btn-sm">View</a>'
    . '<form method="POST" action="' . e(url('/admin/events/delete')) . '" onsubmit="return confirm(\'Are you sure you want to delete this event? This cannot be undone.\');">'
    . '<input type="hidden" name="event_id" value="' . (int) $event['event_id'] . '">'
    . '<button type="submit" class="btn-ghost btn-sm text-red-600 hover:text-red-700">Delete</button>'
    . '</form>'
    . '</div>';

    $eventCell = '<div class="min-w-0">
        <p class="font-medium text-gray-900 truncate">' . e($event['title']) . '</p>
        <p class="text-xs text-gray-500 truncate max-w-xs">' . e($event['venue'] ?? 'No venue') . '</p>
    </div>';

    $statusCell = '';
    $badgeType  = $event['status'];
    ob_start();
    require BASE_PATH . '/app/components/badge.php';
    $statusCell = ob_get_clean();

    $rows[] = [
        $eventCell,
        '<span class="text-sm text-gray-600">' . e($event['club_name'] ?? '—') . '</span>',
        '<span class="text-sm text-gray-600">' . e($event['category'] ?? '—') . '</span>',
        formatDate($event['start_time'], 'M d, Y'),
        '<span class="text-sm font-medium text-gray-900">' . (int) $event['reg_count'] . '</span>',
        '<span class="text-sm text-gray-600">' . (int) $event['capacity'] . '</span>',
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
                <h1 class="page-title">Event Manage</h1>
                <p class="page-subtitle">All events across the platform. Admins can view or delete any event from any club at any time.</p>
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
                <form method="GET" action="<?= url('/admin/events') ?>" class="flex flex-col sm:flex-row gap-3">
                    <select name="status" class="select sm:w-44">
                        <option value="">All Statuses</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>

                    <div class="flex flex-1 gap-3">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"><?= icon('search', 'w-4 h-4') ?></span>
                            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search events..." class="input pl-10">
                        </div>
                        <button type="submit" class="btn-secondary">Filter</button>
                        <?php if ($statusFilter !== '' || $search !== ''): ?>
                        <a href="<?= url('/admin/events') ?>" class="btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-6">
                <?php
                $actionSlot = 'Actions';
                $emptyMessage = 'No events found.';
                require BASE_PATH . '/app/components/table.php';

                $paginationQuery = $_GET;
                unset($paginationQuery['page']);
                $paginationBase = url('/admin/events') . (count($paginationQuery) > 0 ? '?' . http_build_query($paginationQuery) : '');

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