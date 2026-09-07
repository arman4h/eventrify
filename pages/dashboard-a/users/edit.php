<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Edit User';
$activePage = 'users';

$userId = (int) get('user_id');
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    abort(404, 'User not found');
}

$errors = [];

if (isPost()) {
    $name = post('name');
    $email = post('email');
    $role = post('role');
    $password = post('password');

    if ($name === '' || $email === '') {
        $errors[] = 'Name and email are required.';
    }

    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $name, $email, $role, $hashed, $userId);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param('sssi', $name, $email, $role, $userId);
        }

        if ($stmt->execute()) {
            $_SESSION['flash']['success'] = 'User updated successfully.';
            redirect('/admin/users');
        } else {
            $errors[] = 'Failed to update user.';
        }
    }
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Edit User</h2>
            <p class="text-sm text-gray-500 mt-1">Update user details for <?= e($user['name']) ?></p>
        </div>
        <a href="<?= url('/admin/users') ?>" class="btn-secondary">Back to Users</a>
    </div>

    <?php foreach ($errors as $error): ?>
    <div class="mb-2">
        <?php
        $alertType = 'error';
        $alertMessage = $error;
        require BASE_PATH . '/app/components/alert.php';
        ?>
    </div>
    <?php endforeach; ?>

    <div class="card p-6 max-w-2xl">
        <form method="POST" class="space-y-5">
            <div>
                <label class="label">Full Name</label>
                <input type="text" name="name" class="input" value="<?= e(isPost() ? post('name') : $user['name']) ?>" required>
            </div>

            <div>
                <label class="label">Email Address</label>
                <input type="email" name="email" class="input" value="<?= e(isPost() ? post('email') : $user['email']) ?>" required>
            </div>

            <div>
                <label class="label">Role</label>
                <select name="role" class="input">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="club_admin" <?= $user['role'] === 'club_admin' ? 'selected' : '' ?>>Club Admin</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div>
                <label class="label">New Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="input" placeholder="At least 6 characters">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Update User</button>
                <a href="<?= url('/admin/users') ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
