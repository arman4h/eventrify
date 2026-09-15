<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubUser();

$pageTitle = 'Members';
$activePage = 'members';

$clubId = (int) currentUser()['club_id'];

if (isPost()) {
    $action = post('action');
    if ($action === 'remove') {
        $_SESSION['flash']['success'] = 'Member removed (demo).';
        redirect('/club/members');
    }
    if ($action === 'invite') {
        $_SESSION['flash']['success'] = 'Invitation sent (demo).';
        redirect('/club/members');
    }
}

$members = $db->prepare("SELECT * FROM club_users WHERE club_id = ? ORDER BY role ASC, full_name ASC");
$members->bind_param('i', $clubId);
$members->execute();
$membersResult = $members->get_result();

$roles = [
    ['name' => 'Club Admin', 'desc' => 'Full access to all club settings and features.', 'color' => 'bg-blue-500'],
    ['name' => 'Event Manager', 'desc' => 'Can create, edit, and manage all events.', 'color' => 'bg-emerald-500'],
    ['name' => 'Registration Manager', 'desc' => 'Can view and manage event registrations.', 'color' => 'bg-purple-500'],
    ['name' => 'Check-in Volunteer', 'desc' => 'Can scan QR codes and verify attendance.', 'color' => 'bg-amber-500'],
    ['name' => 'Viewer', 'desc' => 'Read-only access to dashboard and reports.', 'color' => 'bg-gray-400'],
];

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $alertType = flash('success') ? 'success' : 'error';
        $alertMessage = flash('success') ?: flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Members</h2>
                <p class="page-subtitle">Manage your club members and roles</p>
            </div>
            <button type="button" class="btn-primary" data-modal-open="inviteModal">
                <?= icon('plus', 'w-4 h-4') ?> Invite Member
            </button>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
            <?php foreach ($roles as $role): ?>
                <div class="card p-3">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full <?= $role['color'] ?>"></span>
                        <span class="text-sm font-medium text-gray-900"><?= e($role['name']) ?></span>
                    </div>
                    <p class="text-xs text-gray-500"><?= e($role['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card overflow-hidden">
            <?php if ($membersResult->num_rows === 0): ?>
                <?php
                $emptyIcon = 'users';
                $emptyTitle = 'No members yet';
                $emptyText = 'Invite your first team member to get started.';
                require BASE_PATH . '/app/components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Position</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = $membersResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="avatar-sm"><?= e(substr($member['full_name'], 0, 1)) ?></span>
                                        <div>
                                            <div class="font-medium text-gray-900"><?= e($member['full_name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= e($member['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm text-gray-600 capitalize"><?= e($member['role']) ?></td>
                                <td>
                                    <?php
                                    $rb = match($member['role']) {
                                        'owner' => 'badge-danger',
                                        'admin' => 'badge-primary',
                                        'executive' => 'badge-info',
                                        default => 'badge-neutral',
                                    };
                                    ?>
                                    <span class="<?= $rb ?>"><?= ucfirst(e($member['role'])) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $sb = match($member['status']) {
                                        'active' => 'badge-success',
                                        'inactive' => 'badge-neutral',
                                        'removed' => 'badge-danger',
                                        default => 'badge-neutral',
                                    };
                                    ?>
                                    <span class="<?= $sb ?>"><?= ucfirst(e($member['status'])) ?></span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <button type="button" class="btn-ghost btn-sm">
                                            <?= icon('eye', 'w-4 h-4') ?>
                                        </button>
                                        <?php if ($member['role'] !== 'owner'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="member_id" value="<?= (int) $member['club_user_id'] ?>">
                                                <button type="submit" class="btn-danger btn-sm" data-confirm="Remove this member?">
                                                    <?= icon('trash', 'w-4 h-4') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>

<?php
$modalId = 'inviteModal';
$modalTitle = 'Invite Member';
ob_start();
?>
<form method="POST" class="space-y-4">
    <input type="hidden" name="action" value="invite">
    <div class="form-group">
        <label class="label-required">Email Address</label>
        <input type="email" name="invite_email" class="input" placeholder="member@university.edu" required>
    </div>
    <div class="form-group">
        <label class="label-required">Role</label>
        <select name="invite_role" class="select" required>
            <option value="executive">Executive</option>
            <option value="admin">Admin</option>
        </select>
    </div>
    <div class="form-group">
        <label class="label">Event-specific Access</label>
        <select name="invite_scope" class="select">
            <option value="all">All events</option>
            <option value="specific">Specific event</option>
        </select>
    </div>
    <div class="flex gap-3 justify-end">
        <button type="button" class="btn-secondary" data-modal-dismiss="inviteModal">Cancel</button>
        <button type="submit" class="btn-primary">Send Invite</button>
    </div>
</form>
<?php
$modalBody = ob_get_clean();
require BASE_PATH . '/app/components/modal.php';
?>
