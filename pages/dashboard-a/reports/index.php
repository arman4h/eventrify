<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Reports';
$activePage = 'reports';

$usersByRole = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$usersRoles = [];
$usersTotal = 0;
while ($row = $usersByRole->fetch_assoc()) {
    $usersRoles[] = $row;
    $usersTotal += (int) $row['count'];
}

$eventsByStatus = $db->query("SELECT status, COUNT(*) as count FROM events GROUP BY status");
$eventsStatuses = [];
$eventsTotal = 0;
while ($row = $eventsByStatus->fetch_assoc()) {
    $eventsStatuses[] = $row;
    $eventsTotal += (int) $row['count'];
}

$registrationsByEvent = $db->query("
    SELECT e.title, COUNT(r.id) as count
    FROM registrations r
    JOIN events e ON e.id = r.event_id
    GROUP BY r.event_id
    ORDER BY count DESC
    LIMIT 5
");

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Reports</h2>
        <p class="text-sm text-gray-500 mt-1">Platform analytics and summary</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Users by Role</h3>
            <?php if ($usersTotal === 0): ?>
                <p class="text-sm text-gray-500">No users yet.</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php
                $colors = ['bg-primary-500', 'bg-emerald-500', 'bg-amber-500'];
                $i = 0;
                foreach ($usersRoles as $row):
                    $color = $colors[$i % count($colors)];
                    $i++;
                    $pct = (int) round($row['count'] / $usersTotal * 100);
                ?>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700 capitalize"><?= e($row['role']) ?></span>
                        <span class="text-sm font-semibold text-gray-900"><?= (int) $row['count'] ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="<?= $color ?> h-2 rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Events by Status</h3>
            <?php if ($eventsTotal === 0): ?>
                <p class="text-sm text-gray-500">No events yet.</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php
                $colors = ['bg-emerald-500', 'bg-blue-500', 'bg-red-500', 'bg-gray-400'];
                $i = 0;
                foreach ($eventsStatuses as $row):
                    $color = $colors[$i % count($colors)];
                    $i++;
                    $pct = (int) round($row['count'] / $eventsTotal * 100);
                ?>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700 capitalize"><?= e($row['status']) ?></span>
                        <span class="text-sm font-semibold text-gray-900"><?= (int) $row['count'] ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="<?= $color ?> h-2 rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Registrations by Event</h3>
            <?php
            $columns = ['Event', 'Registrations'];
            $rows = [];
            while ($row = $registrationsByEvent->fetch_assoc()) {
                $rows[] = [e($row['title']), (int) $row['count']];
            }
            $emptyMessage = 'No registrations yet.';
            require BASE_PATH . '/app/components/table.php';
            ?>
        </div>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>