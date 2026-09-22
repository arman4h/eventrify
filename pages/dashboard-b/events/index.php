<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('events');

$pageTitle = 'Events';
$activePage = 'events';

$clubId = (int) currentUser()['club_id'];
$search = get('q');
$page = max(1, (int) get('page', 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$baseQuery = "FROM events WHERE club_id = ?";
$countQuery = "SELECT COUNT(*) as c $baseQuery";
$dataQuery = "SELECT * $baseQuery ORDER BY start_time DESC";

$params = [$clubId];
$types = 'i';

if ($search !== '') {
    $whereExtra = " AND (title LIKE ? OR description LIKE ? OR venue LIKE ?)";
    $countQuery .= $whereExtra;
    $dataQuery .= $whereExtra;
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$countStmt = $db->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = (int) $countStmt->get_result()->fetch_assoc()['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$dataQuery .= " LIMIT $perPage OFFSET $offset";
$dataStmt = $db->prepare($dataQuery);
$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$events = $dataStmt->get_result();

$baseUrl = url('/club/events') . ($search !== '' ? '?q=' . urlencode($search) : '');

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $alertMessage = $flashSuccess ?: $flashError;
        $alertType = $flashSuccess ? 'success' : ($flashError ? 'error' : 'info');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="page-header">
            <div>
                <h2 class="page-title">Events</h2>
                <p class="page-subtitle">Create and manage club events</p>
            </div>
            <a href="<?= url('/club/events/create') ?>" class="btn-primary">
                <?= icon('plus', 'w-4 h-4') ?> Create Event
            </a>
        </div>

        <div class="card overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <form method="GET" action="<?= url('/club/events') ?>" class="flex gap-3">
                    <div class="relative flex-1 max-w-sm">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><?= icon('search', 'w-4 h-4') ?></span>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search events..." class="input pl-10">
                    </div>
                    <button type="submit" class="btn-secondary btn-sm">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= url('/club/events') ?>" class="btn-ghost btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($events->num_rows === 0): ?>
                <?php
                $emptyIcon = 'calendar';
                $emptyTitle = 'No events found';
                $emptyText = $search !== '' ? 'Try a different search term.' : 'Create your first event to get started.';
                $emptyHref = url('/club/events/create');
                $emptyAction = 'Create Event';
                require BASE_PATH . '/app/components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($event = $events->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-900"><?= e($event['title']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e(truncate($event['description'], 60)) ?></div>
                                </td>
                                <td class="whitespace-nowrap text-sm text-gray-700"><?= formatDate($event['start_time']) ?></td>
                                <td class="text-sm text-gray-700"><?= e($event['venue']) ?></td>
                                <td class="text-sm text-gray-700"><?= (int) $event['capacity'] ?></td>
                                <td>
                                    <?php
                                    $status = $event['status'] ?? 'draft';
                                    $badge = match($status) {
                                        'draft' => 'badge-neutral',
                                        'published' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                        'completed' => 'badge-info',
                                        default => 'badge-neutral',
                                    };
                                    ?>
                                    <span class="<?= $badge ?>"><?= ucfirst(e($status)) ?></span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <a href="<?= url('/club/events/manage?event_id=' . $event['event_id']) ?>" class="btn-ghost btn-sm">
                                            <?= icon('eye', 'w-4 h-4') ?> Manage
                                        </a>
                                        <a href="<?= url('/club/events/edit?event_id=' . $event['event_id']) ?>" class="btn-ghost btn-sm">
                                            <?= icon('edit', 'w-4 h-4') ?> Edit
                                        </a>
                                        <form method="POST" action="<?= url('/club/events/delete') ?>" style="display:inline;">
                                            <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                                            <button type="submit" class="btn-danger btn-sm" data-confirm="Delete this event?">
                                                <?= icon('trash', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                $totalPages = $totalPages;
                $currentPage = $page;
                $baseUrl = $baseUrl;
                require BASE_PATH . '/app/components/pagination.php';
                ?>
            <?php endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
