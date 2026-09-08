<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$pageTitle = 'Explore Events';

$search = get('search');
$category = get('category');

$where = "e.status = 'published'";

if ($search !== '') {
    $where .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
}

if ($category !== '') {
    $where .= " AND e.category = ?";
}

$sql = "SELECT e.*, c.club_name
        FROM events e
        LEFT JOIN clubs c ON c.club_id = e.club_id
        WHERE $where
        ORDER BY e.start_time ASC";

$stmt = $db->prepare($sql);
$types = '';
$args = [];

if ($search !== '') {
    $like = "%$search%";
    $types .= 'sss';
    $args[] = $like;
    $args[] = $like;
    $args[] = $like;
}

if ($category !== '') {
    $types .= 's';
    $args[] = $category;
}

if ($types !== '') {
    $stmt->bind_param($types, ...$args);
}

$stmt->execute();
$events = $stmt->get_result();

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<section class="text-center py-10">
<<<<<<< HEAD
    <h1 class="text-4xl font-extrabold text-gray-900">Explore Events</h1>
    <p class="mt-3 text-gray-600 max-w-xl mx-auto">Discover upcoming events from university clubs and organizations. Browse and register when you log in.</p>
=======
    <h1 class="text-4xl font-extrabold text-gray-900">Welcome to <span class="text-indigo-600">ClubEvent</span></h1>
    <p class="mt-3 text-gray-600 max-w-xl mx-auto">Discover upcoming events, workshops, and competitions from university clubs. Browse and register all in one centralized platform.</p>
>>>>>>> 2a383e58e40a56eb11f24442d52feade91b84c50
</section>

<section class="mb-8">
    <form method="GET" action="<?= url('/events') ?>" class="flex flex-col sm:flex-row gap-3 max-w-2xl mx-auto">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search events, venues, keywords..." class="input flex-1">
        <input type="text" name="category" value="<?= e($category) ?>" placeholder="Category (e.g. Technical)" class="input sm:w-48">
        <button type="submit" class="btn-primary whitespace-nowrap">Search</button>
        <?php if ($search !== '' || $category !== ''): ?>
        <a href="<?= url('/events') ?>" class="btn-secondary whitespace-nowrap">Clear</a>
        <?php endif; ?>
    </form>
</section>

<section>
    <?php if ($events && $events->num_rows > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php 
        $index = 0; 
        while ($event = $events->fetch_assoc()): 
            if ($index % 3 == 0) {
                $cardBg = "bg-blue-50/80 border border-blue-200/60 shadow-blue-100/50";
                $badgeBg = "bg-blue-100 text-blue-800";
            } elseif ($index % 3 == 1) {
                $cardBg = "bg-orange-50/80 border border-orange-200/60 shadow-yellow-100/50";
                $badgeBg = "bg-orange-100 text-orange-800";
            } else {
                $cardBg = "bg-purple-50/80 border border-purple-200/60 shadow-purple-100/50";
                $badgeBg = "bg-purple-100 text-purple-800";
            }
        ?>
        <article class="card p-6 flex flex-col hover:shadow-xl hover:-translate-y-1 transition-all duration-300 rounded-2xl shadow-sm <?= $cardBg ?>">
            <div class="flex items-start justify-between mb-3">
<<<<<<< HEAD
                <?php if ($event['category']): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"><?= e($event['category']) ?></span>
                <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Event</span>
                <?php endif; ?>
                <span class="text-xs text-gray-500"><?= formatDate($event['start_time']) ?></span>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-1">
                <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="hover:text-primary-600"><?= e($event['title']) ?></a>
            </h2>
            <?php if ($event['club_name']): ?>
            <p class="text-xs font-medium text-primary-600 mb-2"><?= e($event['club_name']) ?></p>
            <?php endif; ?>
            <p class="text-sm text-gray-500 mb-4 line-clamp-3"><?= e($event['description']) ?></p>
            <div class="text-sm text-gray-600 mb-4">
                <p>Venue: <?= e($event['venue']) ?></p>
                <p>Capacity: <?= (int) $event['capacity'] ?> seats</p>
            </div>
            <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="btn-secondary mt-auto">View Details</a>
=======
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $badgeBg ?>"><?= ucfirst(e($event['status'])) ?></span>
                <span class="text-xs font-medium text-gray-500">📅 <?= e(formatDate($event['event_date'])) ?></span>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2"><a href="<?= url('/event?event_id=' . $event['id']) ?>" class="hover:text-indigo-600 transition-colors"><?= e($event['title']) ?></a></h2>
            <p class="text-sm text-gray-600 mb-4 line-clamp-3 leading-relaxed"><?= e($event['description']) ?></p>
            <div class="text-sm text-gray-700 font-medium mb-4 space-y-1 bg-white/50 p-3 rounded-xl">
                <p>📍 Venue: <?= e($event['venue']) ?></p>
                <p>👥 Capacity: <?= (int) $event['capacity'] ?> seats</p>
            </div>
            <a href="<?= url('/event?event_id=' . $event['id']) ?>" class="btn-primary mt-auto text-center w-full shadow-sm py-2.5 rounded-xl font-semibold">Join Event</a>
>>>>>>> 2a383e58e40a56eb11f24442d52feade91b84c50
        </article>
        <?php 
            $index++; 
        endwhile; 
        ?>
    </div>
    <?php else: ?>
    <div class="card p-12 text-center">
        <p class="text-gray-500 mb-4">No events found.</p>
        <a href="<?= url('/events') ?>" class="btn-secondary">View all events</a>
    </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>
