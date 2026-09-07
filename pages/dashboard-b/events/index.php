<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();

$pageTitle = 'Events';
$activePage = 'events';

$search = get('search');
$where = '';
$params = '';

if ($search !== '') {
    $where = "WHERE title LIKE ? OR description LIKE ? OR venue LIKE ?";
    $params = "%$search%";
}

$stmt = null;

if ($where !== '') {
    $stmt = $db->prepare("SELECT * FROM events $where ORDER BY event_date DESC");
    $like = "%$search%";
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $events = $stmt->get_result();
} else {
    $events = $db->query("SELECT * FROM events ORDER BY event_date DESC");
}

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Events</h2>
            <p class="text-sm text-gray-500 mt-1">Create and manage club events</p>
        </div>
        <a href="<?= url('/club/events/create') ?>" class="btn-primary">Create Event</a>
    </div>

    <?php
    $alertType = 'success';
    $alertMessage = flash('success');
    require BASE_PATH . '/app/components/alert.php';
    $alertType = 'error';
    $alertMessage = flash('error');
    require BASE_PATH . '/app/components/alert.php';
    ?>

    <div class="card overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <form method="GET" action="<?= url('/club/events') ?>" class="flex gap-3">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search events..." class="input max-w-sm">
                <button type="submit" class="btn-secondary">Search</button>
                <?php if ($search !== ''): ?>
                <a href="<?= url('/club/events') ?>" class="btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3">Title</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Venue</th>
                        <th class="px-6 py-3">Capacity</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($events->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            No events found.
                        </td>
                    </tr>
                    <?php else: while ($event = $events->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900"><?= e($event['title']) ?></div>
                            <div class="text-xs text-gray-500"><?= e(substr($event['description'], 0, 60)) ?>...</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap"><?= formatDate($event['event_date']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-700"><?= e($event['venue']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-700"><?= (int) $event['capacity'] ?></td>
                        <td class="px-6 py-4">
                            <?php
                            $status = $event['status'] ?? 'upcoming';
                            $badge = match ($status) {
                                'upcoming' => 'bg-blue-50 text-blue-700',
                                'ongoing'  => 'bg-emerald-50 text-emerald-700',
                                'completed' => 'bg-gray-100 text-gray-600',
                                'cancelled' => 'bg-red-50 text-red-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?>"><?= ucfirst(e($status)) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-3">
                                <a href="<?= url('/club/events/edit?event_id=' . $event['id']) ?>" class="text-primary-600 hover:text-primary-700 font-medium text-sm">Edit</a>
                                <form method="POST" action="<?= url('/club/events/delete') ?>" style="display:inline;">
                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-700 font-medium text-sm" data-confirm="Delete this event?">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
