<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();

$pageTitle = 'Tasks';
$activePage = 'tasks';

$tasks = $db->query("
    SELECT t.*, e.title as event_title
    FROM tasks t
    LEFT JOIN events e ON e.id = t.event_id
    ORDER BY t.due_date ASC
");

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Tasks</h2>
        <p class="text-sm text-gray-500 mt-1">Track event-related tasks and progress</p>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3">Task</th>
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3">Due Date</th>
                        <th class="px-6 py-3">Priority</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($tasks->num_rows === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No tasks yet.</td>
                    </tr>
                    <?php else: while ($task = $tasks->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900"><?= e($task['title']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700"><?= e($task['event_title'] ?? '—') ?></td>
                        <td class="px-6 py-4 text-sm text-gray-700"><?= $task['due_date'] ? formatDate($task['due_date']) : '—' ?></td>
                        <td class="px-6 py-4">
                            <?php
                            $priority = $task['priority'] ?? 'medium';
                            $badge = match ($priority) {
                                'high'   => 'bg-red-50 text-red-700',
                                'medium' => 'bg-amber-50 text-amber-700',
                                'low'    => 'bg-emerald-50 text-emerald-700',
                                default  => 'bg-gray-100 text-gray-600',
                            };
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?>"><?= ucfirst(e($priority)) ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $status = $task['status'] ?? 'pending';
                            $badge = $status === 'done' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700';
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?>"><?= ucfirst(e($status)) ?></span>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
