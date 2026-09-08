<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();

$pageTitle = 'Settings';
$activePage = 'settings';

$message = null;

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Settings</h2>
        <p class="text-sm text-gray-500 mt-1">Workspace and profile settings</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Workspace</h3>
            <div class="space-y-4">
                <div>
                    <label class="label">Workspace Name</label>
                    <input type="text" class="input" value="<?= e(APP_NAME) ?> Club" disabled>
                </div>
                <div>
                    <label class="label">Plan</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700">University</span>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Profile</h3>
            <div class="space-y-4">
                <div>
                    <label class="label">Name</label>
                    <input type="text" class="input" value="<?= e(currentUser()['name']) ?>" disabled>
                </div>
                <div>
                    <label class="label">Email</label>
                    <input type="email" class="input" value="<?= e(currentUser()['email']) ?>" disabled>
                </div>
                <div>
                    <label class="label">Role</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 capitalize"><?= e(currentUserRole()) ?></span>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
