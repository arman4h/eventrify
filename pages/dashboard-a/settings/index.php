<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'System Settings';
$activePage = 'settings';

$adminId = (int) currentUser()['id'];

// The system admin's own row is the only writable platform configuration:
// there is deliberately no site-wide settings table in the schema.
$adminStmt = $db->prepare("SELECT * FROM system_admins WHERE admin_id = ?");
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$admin = $adminStmt->get_result()->fetch_assoc() ?: [];

$errors = [];
$form = [
    'full_name' => $admin['full_name'] ?? '',
    'email'     => $admin['email'] ?? '',
];

if (isPost() && post('save_settings') !== '') {
    $form['full_name'] = trim(post('full_name'));
    $form['email']     = strtolower(trim(post('email')));

    $newPassword = post('new_password');
    $confirm     = post('confirm_password');

    vAdd($errors, vRequired($form['full_name'], 'Full name'));
    vAdd($errors, vMaxLen($form['full_name'], 100, 'Full name'));
    vAdd($errors, vRequired($form['email'], 'Email'));
    vAdd($errors, vEmail($form['email'], 'Email'));
    vAdd($errors, vMaxLen($form['email'], 100, 'Email'));

    if ($newPassword !== '' || $confirm !== '') {
        vAdd($errors, vPassword($newPassword, 'New password'));
        vAdd($errors, vPasswordNotSimilar($newPassword, [
            $form['full_name'], $form['email'],
        ], 'New password'));
        if ($newPassword !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        }
    }

    $dupe = $db->prepare("SELECT admin_id FROM system_admins WHERE email = ? AND admin_id <> ? LIMIT 1");
    $dupe->bind_param('si', $form['email'], $adminId);
    $dupe->execute();
    if ($dupe->get_result()->fetch_assoc()) {
        $errors[] = 'Another administrator already uses that email.';
    }

    if ($errors) {
        flash('error', implode("\n", $errors));
        redirect('/admin/settings');
    }

    if ($newPassword !== '') {
        $update = $db->prepare("UPDATE system_admins SET full_name = ?, email = ?, password_hash = ? WHERE admin_id = ?");
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update->bind_param('sssi', $form['full_name'], $form['email'], $hash, $adminId);
    } else {
        $update = $db->prepare("UPDATE system_admins SET full_name = ?, email = ? WHERE admin_id = ?");
        $update->bind_param('ssi', $form['full_name'], $form['email'], $adminId);
    }

    if (dbExec($update)) {
        // Keep the live session in step with the new identity.
        $_SESSION['user']['name']  = $form['full_name'];
        $_SESSION['user']['email'] = $form['email'];

        if ($newPassword !== '') {
            flash('success', 'Settings and password saved. Your new password is active immediately.');
        } else {
            flash('success', 'Settings saved.');
        }
    } else {
        flash('error', 'Could not save settings. Please try again.');
    }
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
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Your Administrator Account</h3>
                <p class="text-sm text-gray-500 mb-4">Used to sign in to this dashboard.</p>

                <form method="POST" action="<?= url('/admin/settings') ?>" class="space-y-4 max-w-md">
                    <?= csrfField() ?>
                    <input type="hidden" name="save_settings" value="1">

                    <div class="form-group">
                        <label class="label-required" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="input"
                               value="<?= e($form['full_name']) ?>" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label class="label-required" for="email">Email</label>
                        <input type="email" id="email" name="email" class="input"
                               value="<?= e($form['email']) ?>" maxlength="100" required>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-sm font-medium text-gray-900 mb-1">Change Password</p>
                        <p class="text-xs text-gray-500 mb-3">Leave both fields empty to keep your current password.</p>

                        <div class="form-group">
                            <label class="label" for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password"
                                   class="input" autocomplete="new-password"
                                   minlength="<?= (int) MIN_PASSWORD_LENGTH ?>">
                        </div>

                        <div class="form-group">
                            <label class="label" for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="input" autocomplete="new-password"
                                   minlength="<?= (int) MIN_PASSWORD_LENGTH ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Save Settings</button>
                </form>
            </div>
        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>