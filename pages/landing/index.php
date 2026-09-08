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
    <h1 class="text-4xl font-extrabold text-gray-900">Welcome to <span class="text-indigo-600">ClubEvent</span></h1>
    <p class="mt-3 text-gray-600 max-w-xl mx-auto">Discover upcoming events, workshops, and competitions from university clubs. Browse and register all in one centralized platform.</p>
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
        </article>
        <?php 
            $index++; 
        endwhile; 
        ?>
    </div>
    <?php else: ?>
    <div class="card p-12 text-center">
        <p class="text-gray-500 mb-4">No events found.</p>
        <a href="<?= url('/') ?>" class="btn-secondary">View all events</a>
    </div>
    <?php endif; ?>
</section>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>
