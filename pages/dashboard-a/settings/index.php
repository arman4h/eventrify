<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'System Settings';
$activePage = 'settings';

if (isPost() && post('save_settings') !== '') {
    flash('success', 'Settings saved (demo).');
    redirect('/admin/settings');
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">System Settings</h1>
                <p class="page-subtitle">Platform configuration and administrator account.</p>
            </div>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="card p-6 max-w-2xl">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">General Settings</h3>

            <div class="divide-y divide-gray-100">
                <div class="flex items-center justify-between gap-4 py-3">
                    <p class="text-sm text-gray-500">Platform Name</p>
                    <p class="text-sm font-medium text-gray-900"><?= e(APP_NAME) ?></p>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <p class="text-sm text-gray-500">Base URL</p>
                    <p class="text-sm font-medium text-gray-900 break-all"><?= e(BASE_URL) ?></p>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <p class="text-sm text-gray-500">Environment</p>
                    <p class="text-sm font-medium text-gray-900"><?= e(APP_ENV) ?></p>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-5 mt-2">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Administrator</h3>

                <div class="flex items-center gap-3 mb-5">
                    <span class="avatar-md"><?= e(strtoupper(substr(currentUser()['name'] ?? 'A', 0, 1))) ?></span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900"><?= e(currentUser()['name'] ?? '—') ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= e(currentUser()['email'] ?? '—') ?></p>
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3">
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="text-sm font-medium text-gray-900"><?= e(currentUser()['name'] ?? '—') ?></p>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="text-sm font-medium text-gray-900"><?= e(currentUser()['email'] ?? '—') ?></p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-5 mt-2">
                <form method="POST" action="<?= url('/admin/settings') ?>">
                    <input type="hidden" name="save_settings" value="1">
                    <button type="submit" class="btn-primary">Save Settings</button>
                </form>
                <p class="form-hint mt-3">These are display values for the demo.</p>
            </div>
        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>