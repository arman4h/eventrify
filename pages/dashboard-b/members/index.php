<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('members');

$pageTitle = 'User';
$activePage = 'members';

$me = currentUser();
$clubId = (int) ($me['club_id'] ?? 0);
$isOwner = isClubOwner();

if (isPost()) {
    $action = post('action');

    if (!isClubOwner()) {
        $_SESSION['flash']['error'] = 'Only the club owner can manage users.';
        redirect('/club/members');
    }

    if ($action === 'create') {
        $fullName   = post('full_name');
        $email      = strtolower(trim(post('email')));
        $password   = post('password');
        $phone      = trim(post('phone'));
        $studentId  = trim(post('student_id'));
        $position   = trim(post('position'));
        $role       = post('role');
        $accessScope = post('access_scope');
        $accessPages = isset($_POST['access_pages']) ? array_map('intval', (array) $_POST['access_pages']) : [];

        if ($fullName === '' || $email === '' || $password === '' || !in_array($role, ['admin', 'executive'], true)) {
            $_SESSION['flash']['error'] = 'Please fill in all required fields.';
            redirect('/club/members');
        }
        if (strlen($password) < 6) {
            $_SESSION['flash']['error'] = 'Password must be at least 6 characters.';
            redirect('/club/members');
        }

        $chk = $db->prepare("SELECT 1 FROM club_users WHERE email = ? LIMIT 1");
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $_SESSION['flash']['error'] = 'A user with that email already exists.';
            redirect('/club/members');
        }

        $hash      = password_hash($password, PASSWORD_DEFAULT);
        $phoneNull = $phone !== '' ? $phone : null;
        $idNull    = $studentId !== '' ? $studentId : null;
        $posNull   = $position !== '' ? $position : null;

        $stmt = $db->prepare("
            INSERT INTO club_users (club_id, full_name, email, password_hash, phone, student_id, position, role, access_scope, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $scopeVal = $accessScope === 'specific' ? 'limited' : 'all';
        $stmt->bind_param('issssssss', $clubId, $fullName, $email, $hash, $phoneNull, $idNull, $posNull, $role, $scopeVal);
        $stmt->execute();
        $newUserId = (int) $stmt->insert_id;

        if ($accessScope === 'specific' && $newUserId > 0) {
            $perm = $db->prepare("INSERT INTO executive_permissions (club_user_id, page_id, access_level) VALUES (?, ?, 'manage')");
            foreach ($accessPages as $pid) {
                if ($pid <= 0) {
                    continue;
                }
                $perm->bind_param('ii', $newUserId, $pid);
                $perm->execute();
            }
        }

        $_SESSION['flash']['success'] = 'User "' . $fullName . '" created. They can log in at Club Login with this email.';
        redirect('/club/members');
    }

    if ($action === 'remove') {
        $memberId = (int) post('member_id');
        $stmt = $db->prepare("
            UPDATE club_users
            SET status = 'removed'
            WHERE club_user_id = ? AND club_id = ? AND role != 'owner' AND club_user_id != ?
        ");
        $stmt->bind_param('iii', $memberId, $clubId, $me['id']);
        $stmt->execute();

        $_SESSION['flash']['success'] = 'User removed.';
        redirect('/club/members');
    }

    if ($action === 'toggle_status') {
        $memberId = (int) post('member_id');
        $stmt = $db->prepare("
            UPDATE club_users
            SET status = IF(status = 'active', 'inactive', 'active')
            WHERE club_user_id = ? AND club_id = ? AND role != 'owner' AND club_user_id != ? AND status != 'removed'
        ");
        $stmt->bind_param('iii', $memberId, $clubId, $me['id']);
        $stmt->execute();

        $_SESSION['flash']['success'] = 'User status updated.';
        redirect('/club/members');
    }
}

$members = $db->prepare("SELECT * FROM club_users WHERE club_id = ? ORDER BY FIELD(role, 'owner', 'admin', 'executive'), created_at ASC");
$members->bind_param('i', $clubId);
$members->execute();
$membersResult = $members->get_result();

$roleStats = ['owner' => 0, 'admin' => 0, 'executive' => 0];
$membersResult->data_seek(0);
while ($row = $membersResult->fetch_assoc()) {
    if (isset($roleStats[$row['role']])) {
        $roleStats[$row['role']]++;
    }
}
$membersResult->data_seek(0);
$totalUsers = array_sum($roleStats);

$permissionPages = [];
$ppResult = $db->query("SELECT page_id, page_key, page_name FROM club_permission_pages ORDER BY page_id");
if ($ppResult) {
    $permissionPages = $ppResult->fetch_all(MYSQLI_ASSOC);
}

$roles = [
    ['name' => 'Owner', 'desc' => 'Full control over the club and its users. Only the owner can create, deactivate, or remove users.', 'color' => 'bg-red-500'],
    ['name' => 'Admin', 'desc' => 'Manages events, registrations, and attendance for the club.', 'color' => 'bg-blue-500'],
    ['name' => 'Executive', 'desc' => 'Gains access only to the pages granted by the owner.', 'color' => 'bg-emerald-500'],
];

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
                <h2 class="page-title">User</h2>
                <p class="page-subtitle">Manage your club users and their roles</p>
            </div>
            <?php if ($isOwner): ?>
                <button type="button" class="btn-primary" data-modal-open="createUserModal">
                    <?= icon('plus', 'w-4 h-4') ?> Create User
                </button>
            <?php else: ?>
                <span class="text-xs text-gray-400">Only the Owner can create or manage users.</span>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="card p-4">
                <p class="text-xs text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= $totalUsers ?></p>
            </div>
            <?php foreach (['owner', 'admin', 'executive'] as $rc): ?>
                <div class="card p-4">
                    <p class="text-xs text-gray-500 capitalize"><?= e($rc) ?>s</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $roleStats[$rc] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-8">
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
                $emptyTitle = 'No users yet';
                $emptyText = 'Create your first user to get started.';
                require BASE_PATH . '/app/components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Position</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Added</th>
                                <?php if ($isOwner): ?><th class="text-right">Actions</th><?php endif; ?>
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
                                <td class="text-sm text-gray-600">
                                    <?php if ($member['position'] !== null && $member['position'] !== ''): ?>
                                        <?= e($member['position']) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $rb = match($member['role']) {
                                        'owner' => 'badge-danger',
                                        'admin' => 'badge-primary',
                                        default => 'badge-info',
                                    };
                                    ?>
                                    <span class="<?= $rb ?>"><?= ucfirst(e($member['role'])) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $sb = match($member['status']) {
                                        'active' => 'badge-success',
                                        'removed' => 'badge-danger',
                                        default => 'badge-neutral',
                                    };
                                    ?>
                                    <span class="<?= $sb ?>"><?= ucfirst(e($member['status'])) ?></span>
                                </td>
                                <td class="text-sm text-gray-600 whitespace-nowrap">
                                    <?= e(date('M j, Y', strtotime($member['created_at']))) ?>
                                </td>
                                <?php if ($isOwner): ?>
                                <td class="whitespace-nowrap text-right">
                                    <?php if ($member['role'] !== 'owner'): ?>
                                        <div class="flex gap-2 justify-end">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="member_id" value="<?= (int) $member['club_user_id'] ?>">
                                                <button type="submit" class="btn-ghost btn-sm" title="<?= $member['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                    <?= $member['status'] === 'active' ? icon('pause', 'w-4 h-4') : icon('play', 'w-4 h-4') ?>
                                                </button>
                                            </form>
                                            <?php if ($member['status'] !== 'removed'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="member_id" value="<?= (int) $member['club_user_id'] ?>">
                                                    <button type="submit" class="btn-danger btn-sm" data-confirm="Remove this user?">
                                                        <?= icon('trash', 'w-4 h-4') ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
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

<?php if ($isOwner): ?>
<?php
$modalId = 'createUserModal';
$modalTitle = 'Create User';
ob_start();
?>
<form method="POST" class="space-y-4">
    <input type="hidden" name="action" value="create">
    <div class="grid grid-cols-2 gap-4">
        <div class="form-group col-span-2">
            <label class="label-required">Full Name</label>
            <input type="text" name="full_name" class="input" placeholder="e.g. Sadia Rahman" required>
        </div>
    </div>
    <div class="form-group">
        <label class="label-required">Email Address</label>
        <input type="email" name="email" class="input" placeholder="user@university.edu" required>
        <p class="form-hint mt-1">This email and password will be used to log in at Club Login.</p>
    </div>
    <div class="form-group">
        <label class="label-required">Temporary Password</label>
        <input type="password" name="password" class="input" placeholder="Minimum 6 characters" required>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div class="form-group">
            <label class="label">Phone</label>
            <input type="text" name="phone" class="input" placeholder="01XXXXXXXXX">
        </div>
        <div class="form-group">
            <label class="label">Student ID</label>
            <input type="text" name="student_id" class="input" placeholder="011XXXXXXX">
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div class="form-group">
            <label class="label">Position</label>
            <input type="text" name="position" class="input" placeholder="e.g. Treasurer">
        </div>
        <div class="form-group">
            <label class="label-required">Role</label>
            <select name="role" class="select" required>
                <option value="admin">Admin</option>
                <option value="executive">Executive</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="label-required">Dashboard Access</label>
        <select name="access_scope" id="accessScope" class="select" required onchange="toggleAccessOptions()">
            <option value="all">All dashboard sections</option>
            <option value="specific">Specific dashboard sections</option>
        </select>
        <p class="form-hint mt-1">Pick which dashboard sections this user can access.</p>
    </div>
    <div class="form-group user-access-options" id="accessOptions" style="display:none;">
        <label class="label">Sections</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <?php foreach ($permissionPages as $pg): ?>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="access_pages[]" value="<?= (int) $pg['page_id'] ?>" class="rounded border-gray-300">
                    <?= e($pg['page_name']) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="flex gap-3 justify-end">
        <button type="button" class="btn-secondary" data-modal-dismiss="createUserModal">Cancel</button>
        <button type="submit" class="btn-primary">Create User</button>
    </div>
</form>
<?php
$modalBody = ob_get_clean();
require BASE_PATH . '/app/components/modal.php';
?>
<script>
function toggleAccessOptions() {
    var sel = document.getElementById('accessScope');
    var box = document.getElementById('accessOptions');
    if (sel && box) {
        box.style.display = sel.value === 'specific' ? 'block' : 'none';
    }
}
toggleAccessOptions();
</script>
<?php endif; ?>