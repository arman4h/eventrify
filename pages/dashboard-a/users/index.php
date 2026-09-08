<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Users';
$activePage = 'users';

$page = max(1, (int) get('page', 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$search = get('search');
$where = '';
$params = '';
if ($search !== '') {
    $where = "WHERE name LIKE ? OR email LIKE ?";
    $params = "%$search%";
}

$totalResult = $db->query("SELECT COUNT(*) as count FROM users $where")->fetch_assoc();
$total = (int) $totalResult['count'];
$totalPages = max(1, ceil($total / $limit));

if ($search !== '') {
    $stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $like = "%$search%";
    $stmt->bind_param('ssii', $like, $like, $limit, $offset);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
}

if (isPost() && isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['flash']['success'] = 'User deleted successfully.';
    }
    redirect('/admin/users');
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Users</h2>
            <p class="text-sm text-gray-500 mt-1">Manage all registered users</p>
        </div>
        <a href="<?= url('/admin/users/create') ?>" class="btn-primary">Add User</a>
    </div>

    <?php
    $alertType = 'success';
    $alertMessage = flash('success');
    require BASE_PATH . '/app/components/alert.php';
    $alertType = 'error';
    $alertMessage = flash('error');
    require BASE_PATH . '/app/components/alert.php';
    ?>

    <div class="card overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <form method="GET" action="<?= url('/admin/users') ?>" class="flex gap-3">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name or email..." class="input max-w-sm">
                <button type="submit" class="btn-secondary">Search</button>
                <?php if ($search !== ''): ?>
                <a href="<?= url('/admin/users') ?>" class="btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php
        $columns = ['#', 'Name', 'Email', 'Role', 'Created', 'Actions'];
        $rows = [];
        $i = $offset;
        while ($user = $users->fetch_assoc()) {
            $i++;
            $rows[] = [
                $i,
                e($user['name']),
                e($user['email']),
                ucfirst(e($user['role'])),
                formatDate($user['created_at']),
                '<div class="flex gap-2">
                    <a href="' . url('/admin/users/edit?user_id=' . $user['id']) . '" class="text-primary-600 hover:text-primary-700 font-medium">Edit</a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_id" value="' . (int) $user['id'] . '">
                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium" data-confirm="Delete this user?">Delete</button>
                    </form>
                </div>',
            ];
        }
        $emptyMessage = 'No users found.';
        require BASE_PATH . '/app/components/table.php';
        ?>

        <?php
        $baseUrl = url('/admin/users');
        require BASE_PATH . '/app/components/pagination.php';
        ?>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
