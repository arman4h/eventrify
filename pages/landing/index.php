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
