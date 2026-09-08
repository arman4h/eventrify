<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$pageTitle = 'Explore Events';

$search = get('search');

if ($search !== '') {
    $stmt = $db->prepare("SELECT * FROM events WHERE status IN ('upcoming','ongoing') AND (title LIKE ? OR description LIKE ? OR venue LIKE ?) ORDER BY event_date ASC");
    $like = "%$search%";
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $events = $stmt->get_result();
} else {
    $events = $db->query("SELECT * FROM events WHERE status IN ('upcoming','ongoing') ORDER BY event_date ASC");
}

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<section class="text-center py-10">
    <h1 class="text-4xl font-extrabold text-gray-900">Explore Events</h1>
    <p class="mt-3 text-gray-600 max-w-xl mx-auto">Discover upcoming events from university clubs and organizations. Browse or register when you log in.</p>
</section>

<section class="mb-8">
    <form method="GET" action="<?= url('/') ?>" class="flex gap-3 max-w-xl mx-auto">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search events, venues, keywords..." class="input">
        <button type="submit" class="btn-primary whitespace-nowrap">Search</button>
        <?php if ($search !== ''): ?>
        <a href="<?= url('/') ?>" class="btn-secondary whitespace-nowrap">Clear</a>
        <?php endif; ?>
    </form>
</section>

<section>
    <?php if ($events && $events->num_rows > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($event = $events->fetch_assoc()): ?>
        <article class="card p-6 flex flex-col hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"><?= ucfirst(e($event['status'])) ?></span>
                <span class="text-xs text-gray-500"><?= e(formatDate($event['event_date'])) ?></span>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2"><a href="<?= url('/event?event_id=' . $event['id']) ?>" class="hover:text-primary-600"><?= e($event['title']) ?></a></h2>
            <p class="text-sm text-gray-500 mb-4 line-clamp-3"><?= e($event['description']) ?></p>
            <div class="text-sm text-gray-600 mb-4">
                <p>Venue: <?= e($event['venue']) ?></p>
                <p>Capacity: <?= (int) $event['capacity'] ?> seats</p>
            </div>
            <a href="<?= url('/event?event_id=' . $event['id']) ?>" class="btn-secondary mt-auto">View Details</a>
        </article>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="card p-12 text-center">
        <p class="text-gray-500 mb-4">No events found.</p>
        <a href="<?= url('/') ?>" class="btn-secondary">View all events</a>
    </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>