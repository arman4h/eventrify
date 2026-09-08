<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubUser();

$pageTitle = 'Overview';
$activePage = 'overview';

$clubId = (int) currentUser()['club_id'];

$totalEvents = $db->query("SELECT COUNT(*) as count FROM events WHERE club_id = $clubId")->fetch_assoc()['count'];
$upcomingEvents = $db->query("SELECT COUNT(*) as count FROM events WHERE club_id = $clubId AND start_time >= NOW() AND status != 'cancelled'")->fetch_assoc()['count'];
$totalRegistrations = $db->query("SELECT COUNT(*) as count FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = $clubId")->fetch_assoc()['count'];
$featuredEvents = $db->query("SELECT * FROM events WHERE club_id = $clubId ORDER BY start_time ASC LIMIT 4");

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Welcome back, <?= e(currentUser()['name']) ?> 👋</h2>
        <p class="text-sm text-gray-500 mt-1">Manage your club events and activities.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="card p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Events</p>
                <p class="text-2xl font-bold text-gray-900"><?= (int) $totalEvents ?></p>
            </div>
        </div>

        <div class="card p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Upcoming</p>
                <p class="text-2xl font-bold text-gray-900"><?= (int) $upcomingEvents ?></p>
            </div>
        </div>

        <div class="card p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Registrations</p>
                <p class="text-2xl font-bold text-gray-900"><?= (int) $totalRegistrations ?></p>
            </div>
        </div>
    </div>

    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Upcoming Events</h3>
        <a href="<?= url('/club/events') ?>" class="text-sm font-medium text-primary-600 hover:text-primary-500">View all →</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php if ($featuredEvents->num_rows === 0): ?>
        <div class="card p-10 text-center lg:col-span-4">
            <p class="text-gray-500 mb-4">No events yet.</p>
            <a href="<?= url('/club/events/create') ?>" class="btn-primary">Create your first event</a>
        </div>
        <?php else: while ($event = $featuredEvents->fetch_assoc()): ?>
        <div class="card overflow-hidden hover:shadow-lg transition-shadow">
            <div class="h-10 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
            <div class="p-5">
                <h4 class="font-semibold text-gray-900 mb-1"><?= e($event['title']) ?></h4>
                <p class="text-sm text-gray-500 mb-3 line-clamp-2"><?= e($event['description']) ?></p>
                <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <?= formatDate($event['start_time']) ?>
                    </span>
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?= e($event['venue']) ?>
                    </span>
                </div>
                <div class="flex gap-2">
                    <a href="<?= url('/club/events/edit?event_id=' . $event['event_id']) ?>" class="text-xs font-medium text-primary-600 hover:text-primary-700">Edit</a>
                    <form method="POST" action="<?= url('/club/events/delete') ?>" style="display:inline;">
                        <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700" data-confirm="Delete this event?">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
