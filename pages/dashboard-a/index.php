<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$totalUsers = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalEvents = $db->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$newUsers = $db->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$totalRegistrations = $db->query("SELECT COUNT(*) as count FROM event_registrations")->fetch_assoc()['count'];

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <div class="w-10 h-10 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= (int) $totalUsers ?></p>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Total Events</p>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= (int) $totalEvents ?></p>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">New Users Today</p>
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= (int) $newUsers ?></p>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Total Registrations</p>
                <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= (int) $totalRegistrations ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Users</h3>
            <?php
            $recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
            $columns = ['Name', 'Email', 'Role', 'Created'];
            $rows = [];
            while ($user = $recentUsers->fetch_assoc()) {
                $rows[] = [
                    e($user['name']),
                    e($user['email']),
                    ucfirst(e($user['role'])),
                    formatDate($user['created_at']),
                ];
            }
            $emptyMessage = 'No users yet.';
            require BASE_PATH . '/app/components/table.php';
            ?>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Events</h3>
            <?php
            $recentEvents = $db->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 5");
            $columns = ['Title', 'Venue', 'Date', 'Status'];
            $rows = [];
            while ($event = $recentEvents->fetch_assoc()) {
                $rows[] = [
                    e($event['title']),
                    e($event['venue']),
                    formatDate($event['event_date']),
                    e($event['status'] ?? 'upcoming'),
                ];
            }
            $emptyMessage = 'No events yet.';
            require BASE_PATH . '/app/components/table.php';
            ?>
        </div>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
