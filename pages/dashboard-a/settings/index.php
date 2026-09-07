<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Settings';
$activePage = 'settings';

$message = null;

if (isPost() && isset($_POST['app_name'])) {
    $_SESSION['flash']['success'] = 'Settings saved (APP_NAME is hardcoded in config for this version).';
    redirect('/admin/settings');
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Settings</h2>
        <p class="text-sm text-gray-500 mt-1">Application configuration</p>
    </div>

    <?php
    $alertType = 'success';
    $alertMessage = flash('success');
    require BASE_PATH . '/app/components/alert.php';
    ?>

    <div class="card p-6 max-w-2xl">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">General Settings</h3>
        <form method="POST" class="space-y-5">
            <div>
                <label class="label">Application Name</label>
                <input type="text" name="app_name" class="input" value="<?= e(APP_NAME) ?>" disabled>
                <p class="text-xs text-gray-500 mt-1">Defined in app/config/app.php</p>
            </div>

            <div>
                <label class="label">Application URL</label>
                <input type="text" class="input" value="<?= e(BASE_URL) ?>" disabled>
            </div>

            <div>
                <label class="label">Environment</label>
                <input type="text" class="input" value="<?= e(APP_ENV) ?>" disabled>
            </div>

            <div class="border-t border-gray-200 pt-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Profile</h3>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="label">Name</label>
                        <input type="text" class="input" value="<?= e(currentUser()['name']) ?>" disabled>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input type="email" class="input" value="<?= e(currentUser()['email']) ?>" disabled>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary">Save Settings</button>
        </form>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
