<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$pageTitle = 'Explore Events';

$q = get('q');
$category = get('category');
$clubId = get('club');
$dateFilter = get('date');
$customDate = get('custom_date');
$statusFilter = get('status');

$categories = $db->query("
    SELECT DISTINCT category
    FROM events
    WHERE category IS NOT NULL AND category != ''
    ORDER BY category
");

$clubs = $db->query("
    SELECT club_id, club_name
    FROM clubs
    WHERE status = 'approved'
    ORDER BY club_name
");

$hasFilters = $q !== '' || $category !== '' || $clubId !== '' || $dateFilter !== '' || $statusFilter !== '' || $customDate !== '';

$conditions = ["e.status = 'published'"];
$params = [];
$types = '';

if ($q !== '') {
    $conditions[] = "(e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($category !== '') {
    $conditions[] = "e.category = ?";
    $params[] = $category;
    $types .= 's';
}

if ($clubId !== '') {
    $conditions[] = "e.club_id = ?";
    $params[] = (int) $clubId;
    $types .= 'i';
}

if ($dateFilter === 'today') {
    $conditions[] = "DATE(e.start_time) = CURDATE()";
} elseif ($dateFilter === 'week') {
    $conditions[] = "e.start_time >= CURDATE() AND e.start_time < DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($dateFilter === 'month') {
    $conditions[] = "e.start_time >= CURDATE() AND e.start_time < DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
} elseif ($dateFilter === 'custom' && $customDate !== '') {
    $conditions[] = "DATE(e.start_time) = ?";
    $params[] = $customDate;
    $types .= 's';
}

if ($statusFilter === 'open') {
    $conditions[] = "(e.capacity = 0 OR e.capacity > (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id AND er.status IN ('registered','attended'))) AND (e.registration_deadline IS NULL OR e.registration_deadline >= NOW())";
} elseif ($statusFilter === 'full') {
    $conditions[] = "e.capacity > 0 AND e.capacity <= (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id AND er.status IN ('registered','attended'))";
} elseif ($statusFilter === 'closed') {
    $conditions[] = "e.registration_deadline IS NOT NULL AND e.registration_deadline < NOW()";
}

$where = implode(' AND ', $conditions);

$perPage = 6;
$currentPage = max(1, (int) get('page', 1));

$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM events e JOIN clubs c ON c.club_id = e.club_id WHERE $where");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = (int) $countStmt->get_result()->fetch_assoc()['total'];

$totalPages = max(1, (int) ceil($total / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$stmt = $db->prepare("SELECT e.*, c.club_name FROM events e JOIN clubs c ON c.club_id = e.club_id WHERE $where ORDER BY e.start_time DESC LIMIT ? OFFSET ?");
if ($types !== '') {
    $types .= 'ii';
    $params[] = $perPage;
    $params[] = $offset;
} else {
    $types = 'ii';
    $params = [$perPage, $offset];
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result();

$regCounts = [];
$regResult = $db->query("
    SELECT event_id, COUNT(*) AS cnt
    FROM event_registrations
    WHERE status IN ('registered', 'attended')
    GROUP BY event_id
");
if ($regResult) {
    while ($row = $regResult->fetch_assoc()) {
        $regCounts[$row['event_id']] = (int) $row['cnt'];
    }
}

$baseParams = [];
if ($q !== '') $baseParams['q'] = $q;
if ($category !== '') $baseParams['category'] = $category;
if ($clubId !== '') $baseParams['club'] = $clubId;
if ($dateFilter !== '') $baseParams['date'] = $dateFilter;
if ($customDate !== '') $baseParams['custom_date'] = $customDate;
if ($statusFilter !== '') $baseParams['status'] = $statusFilter;
$baseUrl = '/events' . (count($baseParams) > 0 ? '?' . http_build_query($baseParams) : '');

$selected = function ($value, $current): string {
    return $value === $current ? 'selected' : '';
};

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 py-10">
    <div class="page-header">
        <div>
            <h1 class="page-title">Explore Events</h1>
            <p class="page-subtitle">Find workshops, competitions, seminars, and activities happening around campus.</p>
        </div>
    </div>

    <form method="GET" action="<?= url('/events') ?>" class="card p-4 mb-6 hidden lg:block">
        <div class="flex flex-wrap items-start gap-3">
            <div class="search-wrapper w-72">
                <span class="search-icon"><?= icon('search', 'w-5 h-5') ?></span>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search events..." class="search-input">
            </div>

            <select name="category" class="select w-44">
                <option value="">All Categories</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= e($cat['category']) ?>" <?= $selected($cat['category'], $category) ?>><?= e($cat['category']) ?></option>
                <?php endwhile; ?>
            </select>

            <select name="club" class="select w-56">
                <option value="">All Clubs</option>
                <?php if ($clubs): ?>
                    <?php while ($club = $clubs->fetch_assoc()): ?>
                        <option value="<?= (int) $club['club_id'] ?>" <?= $selected((string) $club['club_id'], $clubId) ?>><?= e($club['club_name']) ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <select name="date" class="select w-40">
                <option value="">Any date</option>
                <option value="today" <?= $selected('today', $dateFilter) ?>>Today</option>
                <option value="week" <?= $selected('week', $dateFilter) ?>>This week</option>
                <option value="month" <?= $selected('month', $dateFilter) ?>>This month</option>
                <option value="custom" <?= $selected('custom', $dateFilter) ?>>Custom date</option>
            </select>

            <select name="status" class="select w-40">
                <option value="">Any status</option>
                <option value="open" <?= $selected('open', $statusFilter) ?>>Registration Open</option>
                <option value="full" <?= $selected('full', $statusFilter) ?>>Event Full</option>
                <option value="closed" <?= $selected('closed', $statusFilter) ?>>Closed</option>
            </select>

            <?php if ($dateFilter === 'custom'): ?>
                <input type="date" name="custom_date" value="<?= e($customDate) ?>" class="input w-44">
            <?php endif; ?>

            <button type="submit" class="btn-primary">Filter</button>
            <?php if ($hasFilters): ?>
                <a href="<?= url('/events') ?>" class="btn-ghost btn-sm">Clear Filters</a>
            <?php endif; ?>
        </div>
    </form>

    <details class="lg:hidden card p-4 mb-6">
        <summary class="btn-secondary btn-sm w-full flex items-center justify-center gap-2 cursor-pointer list-none">
            <?= icon('filter', 'w-4 h-4') ?>
            Filters
        </summary>
        <form method="GET" action="<?= url('/events') ?>" class="mt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="search-wrapper sm:col-span-2">
                    <span class="search-icon"><?= icon('search', 'w-5 h-5') ?></span>
                    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search events..." class="search-input">
                </div>

                <select name="category" class="select">
                    <option value="">All Categories</option>
                    <?php if ($categories): $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= e($cat['category']) ?>" <?= $selected($cat['category'], $category) ?>><?= e($cat['category']) ?></option>
                    <?php endwhile; endif; ?>
                </select>

                <select name="club" class="select">
                    <option value="">All Clubs</option>
                    <?php if ($clubs): $clubs->data_seek(0); while ($club = $clubs->fetch_assoc()): ?>
                        <option value="<?= (int) $club['club_id'] ?>" <?= $selected((string) $club['club_id'], $clubId) ?>><?= e($club['club_name']) ?></option>
                    <?php endwhile; endif; ?>
                </select>

                <select name="date" class="select">
                    <option value="">Any date</option>
                    <option value="today" <?= $selected('today', $dateFilter) ?>>Today</option>
                    <option value="week" <?= $selected('week', $dateFilter) ?>>This week</option>
                    <option value="month" <?= $selected('month', $dateFilter) ?>>This month</option>
                    <option value="custom" <?= $selected('custom', $dateFilter) ?>>Custom date</option>
                </select>

                <select name="status" class="select">
                    <option value="">Any status</option>
                    <option value="open" <?= $selected('open', $statusFilter) ?>>Registration Open</option>
                    <option value="full" <?= $selected('full', $statusFilter) ?>>Event Full</option>
                    <option value="closed" <?= $selected('closed', $statusFilter) ?>>Closed</option>
                </select>

                <?php if ($dateFilter === 'custom'): ?>
                    <input type="date" name="custom_date" value="<?= e($customDate) ?>" class="input sm:col-span-2">
                <?php endif; ?>

                <div class="sm:col-span-2 flex items-center gap-2">
                    <button type="submit" class="btn-primary flex-1">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= url('/events') ?>" class="btn-ghost btn-sm">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </details>

    <p class="text-sm text-gray-500 mb-6">
        Showing <?= $total ?> event<?= $total === 1 ? '' : 's' ?>.
    </p>

    <?php if ($events && $events->num_rows > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($event = $events->fetch_assoc()): ?>
                <?php
                $capacity = (int) $event['capacity'];
                $count = $regCounts[$event['event_id']] ?? 0;
                $isFull = $capacity > 0 && $count >= $capacity;
                $deadlinePassed = $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
                $closed = $event['status'] === 'cancelled' || $deadlinePassed;

                if ($isFull) {
                    $statusBadgeClass = 'badge-danger';
                    $statusBadgeText = 'Event Full';
                } elseif ($closed) {
                    $statusBadgeClass = 'badge-neutral';
                    $statusBadgeText = 'Closed';
                } else {
                    $statusBadgeClass = 'badge-success';
                    $statusBadgeText = 'Open';
                }
                ?>
                <article class="card-hover overflow-hidden flex flex-col">
                    <div class="h-36 bg-gray-100 flex items-center justify-center text-gray-400 overflow-hidden">
                        <?php if (!empty($event['poster'])): ?>
                            <img src="<?= e($event['poster']) ?>" alt="<?= e($event['title']) ?> poster" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= icon('calendar', 'w-10 h-10') ?>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="badge-primary"><?= e($event['category'] ?? 'Event') ?></span>
                            <span class="<?= $statusBadgeClass ?>"><?= e($statusBadgeText) ?></span>
                        </div>
                        <h3 class="font-semibold text-gray-900 leading-snug">
                            <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="hover:text-blue-600 transition-colors"><?= e($event['title']) ?></a>
                        </h3>
                        <p class="text-sm text-gray-500 mt-1"><?= e($event['club_name']) ?></p>

                        <div class="mt-3 space-y-1.5 text-sm text-gray-500">
                            <p class="flex items-center gap-2">
                                <?= icon('calendar', 'w-4 h-4 text-gray-400') ?>
                                <span><?= formatDate($event['start_time']) ?></span>
                            </p>
                            <p class="flex items-center gap-2">
                                <?= icon('clock', 'w-4 h-4 text-gray-400') ?>
                                <span><?= date('g:i A', strtotime($event['start_time'])) ?></span>
                            </p>
                            <p class="flex items-center gap-2">
                                <?= icon('map-pin', 'w-4 h-4 text-gray-400') ?>
                                <span class="truncate"><?= e($event['venue']) ?></span>
                            </p>
                        </div>

                        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <?php if ($capacity > 0): ?>
                                <?php if ($isFull): ?>
                                    <span class="badge-danger">Full</span>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500"><?= $count ?> / <?= $capacity ?> seats</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-sm text-gray-500">No seat limit</span>
                            <?php endif; ?>
                        </div>

                        <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="btn-secondary btn-sm w-full mt-4">
                            View Event
                            <?= icon('arrow-right', 'w-4 h-4') ?>
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <?php require BASE_PATH . '/app/components/pagination.php'; ?>
    <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon"><?= icon('search', 'w-6 h-6') ?></span>
                <h3 class="empty-state-title">No events found</h3>
                <p class="empty-state-text">Try adjusting your search or clearing the filters to see more events.</p>
                <a href="<?= url('/events') ?>" class="btn-secondary">Clear Filters</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>