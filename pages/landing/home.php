<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$pageTitle = 'Home';

$recentEvents = $db->query("
    SELECT e.*, c.club_name
    FROM events e
    LEFT JOIN clubs c ON c.club_id = e.club_id
    WHERE e.status = 'published'
    ORDER BY e.start_time ASC
    LIMIT 6
");

$recentCount = $recentEvents ? $recentEvents->num_rows : 0;

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-900 text-white">
    <div class="mx-auto max-w-6xl px-4 py-20 md:py-28 text-center relative">

        <div class="inline-flex items-center px-4 py-1.5 rounded-full bg-white/10 border border-white/20 text-sm mb-6">
            <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
            University club events, all in one place
        </div>

        <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4">
            Discover, Join &amp; Experience<br>
            <span class="text-amber-300">Campus Events</span>
        </h1>

        <p class="max-w-2xl mx-auto text-white/80 text-base md:text-lg mb-8">
            Eventrify helps you find upcoming university events from every club on campus.
            Browse what's happening, register your interest, and never miss out again.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?= url('/events') ?>" class="btn bg-white text-primary-700 hover:bg-gray-100 px-8 py-3 text-base w-full sm:w-auto">
                Explore Events
            </a>
            <?php if (!isLoggedIn()): ?>
            <a href="<?= url('/register-student') ?>" class="btn bg-white/10 text-white hover:bg-white/20 border border-white/30 px-8 py-3 text-base w-full sm:w-auto">
                Join as a Student
            </a>
            <?php else: ?>
            <a href="<?= url('/logout') ?>" class="btn bg-white/10 text-white hover:bg-white/20 border border-white/30 px-8 py-3 text-base w-full sm:w-auto">
                Logout
            </a>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- Recent Events Section -->
<section class="py-14">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Recent Events</h2>
                <p class="text-gray-500 mt-1">Upcoming events from campus clubs</p>
            </div>
            <a href="<?= url('/events') ?>" class="btn-secondary mt-3 sm:mt-0">View All Events</a>
        </div>

        <?php if ($recentCount > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($event = $recentEvents->fetch_assoc()): ?>
            <article class="card overflow-hidden flex flex-col hover:shadow-lg transition-shadow">
                <div class="h-24 bg-gradient-to-r from-indigo-500 to-purple-600 flex items-end p-4">
                    <?php if ($event['category']): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/20 text-white"><?= e($event['category']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="p-6 flex flex-col flex-1">
                    <h3 class="font-semibold text-gray-900 mb-1">
                        <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="hover:text-primary-600"><?= e($event['title']) ?></a>
                    </h3>
                    <?php if ($event['club_name']): ?>
                    <p class="text-xs font-medium text-primary-600 mb-2"><?= e($event['club_name']) ?></p>
                    <?php endif; ?>
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?= e($event['description']) ?></p>
                    <div class="text-xs text-gray-500 space-y-1 mb-4">
                        <p class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <?= formatDate($event['start_time'], 'M d, Y h:i A') ?>
                        </p>
                        <p class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <?= e($event['venue']) ?>
                        </p>
                    </div>
                    <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="btn-secondary mt-auto">View Details</a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="card p-12 text-center">
            <p class="text-gray-500 mb-4">No published events yet. Check back soon!</p>
            <a href="<?= url('/events') ?>" class="btn-secondary">Browse Events</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>