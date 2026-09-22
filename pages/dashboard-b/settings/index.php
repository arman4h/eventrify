<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('club_profile');

$pageTitle = 'Settings';
$activePage = 'settings';

$clubId = (int) currentUser()['club_id'];

$clubStmt = $db->prepare("SELECT * FROM clubs WHERE club_id = ?");
$clubStmt->bind_param('i', $clubId);
$clubStmt->execute();
$club = $clubStmt->get_result()->fetch_assoc();

if (isPost()) {
    $_SESSION['flash']['success'] = 'Settings saved (demo).';
    redirect('/club/settings');
}

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $alertMessage = $flashSuccess ?: $flashError;
        $alertType = $flashSuccess ? 'success' : ($flashError ? 'error' : 'info');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Club Settings</h2>
                <p class="page-subtitle">View and manage your club profile</p>
            </div>
        </div>

        <div class="card p-6 max-w-2xl">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Club Profile</h3>
            <div class="space-y-4">
                <div class="form-group">
                    <label class="label">Club Name</label>
                    <input type="text" class="input" value="<?= e($club['club_name'] ?? '') ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="label">Description</label>
                    <textarea class="textarea" rows="3" disabled><?= e($club['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="label">Logo</label>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                            <?= e(substr($club['club_name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <span class="text-sm text-gray-500">Logo placeholder</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Status</label>
                    <?php
                    $status = $club['status'] ?? 'active';
                    $sb = match($status) {
                        'active' => 'badge-success',
                        'inactive' => 'badge-neutral',
                        default => 'badge-neutral',
                    };
                    ?>
                    <span class="<?= $sb ?>"><?= ucfirst(e($status)) ?></span>
                </div>
            </div>
        </div>

        <div class="card p-6 max-w-2xl mt-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Your Account</h3>
            <div class="space-y-4">
                <div class="form-group">
                    <label class="label">Name</label>
                    <input type="text" class="input" value="<?= e(currentUser()['name']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="label">Email</label>
                    <input type="email" class="input" value="<?= e(currentUser()['email']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="label">Role</label>
                    <?php
                    $role = currentUser()['role'] ?? 'member';
                    $rb = match($role) {
                        'owner' => 'badge-danger',
                        'admin' => 'badge-primary',
                        'executive' => 'badge-info',
                        default => 'badge-neutral',
                    };
                    ?>
                    <span class="<?= $rb ?>"><?= ucfirst(e($role)) ?></span>
                </div>
            </div>
        </div>

        <div class="max-w-2xl mt-6">
            <form method="POST">
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
            <p class="form-hint mt-2">Demo build &mdash; editing is not persisted yet.</p>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
