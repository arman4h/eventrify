<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Add User';
$activePage = 'users';

$errors = [];

if (isPost()) {
    $name = post('name');
    $email = post('email');
    $password = post('password');
    $role = post('role', 'user');

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'All fields are required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (empty($errors)) {
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = 'Email already in use.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $email, $hashed, $role);

            if ($stmt->execute()) {
                $_SESSION['flash']['success'] = 'User created successfully.';
                redirect('/admin/users');
            } else {
                $errors[] = 'Failed to create user.';
            }
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
            <h2 class="text-xl font-bold text-gray-900">Add New User</h2>
            <p class="text-sm text-gray-500 mt-1">Create a new user account</p>
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
                <input type="text" name="name" class="input" value="<?= e(post('name')) ?>" required>
            </div>

            <div>
                <label class="label">Email Address</label>
                <input type="email" name="email" class="input" value="<?= e(post('email')) ?>" required>
            </div>

            <div>
                <label class="label">Password</label>
                <input type="password" name="password" class="input" placeholder="At least 6 characters" required>
            </div>

            <div>
                <label class="label">Role</label>
                <select name="role" class="input">
                    <option value="user">User</option>
                    <option value="club_admin">Club Admin</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Create User</button>
                <a href="<?= url('/admin/users') ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
