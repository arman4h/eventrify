<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
    redirect('/');
}

require_once BASE_PATH . '/app/config/database.php';

$errors = [];

if (isPost()) {
    $name = post('name');
    $email = post('email');
    $password = post('password');
    $confirm = post('confirm_password');
    $role = post('role', 'user');

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
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
            $errors[] = 'An account with that email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $email, $hashed, $role);

            if ($stmt->execute()) {
                redirect('/login');
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-900">
    <div class="w-full max-w-md p-8">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto bg-primary-600 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Create Account</h1>
                <p class="text-sm text-gray-500 mt-1">Join <?= e(APP_NAME) ?> today</p>
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

            <form method="POST" action="<?= url('/register') ?>" class="space-y-5">
                <div>
                    <label class="label">Full Name</label>
                    <input type="text" name="name" class="input" placeholder="John Doe" value="<?= e(post('name')) ?>" required>
                </div>

                <div>
                    <label class="label">Email Address</label>
                    <input type="email" name="email" class="input" placeholder="you@example.com" value="<?= e(post('email')) ?>" required>
                </div>

                <div>
                    <label class="label">Role</label>
                    <select name="role" class="input">
                        <option value="user">User</option>
                        <option value="club_admin">Club Admin</option>
                    </select>
                </div>

                <div>
                    <label class="label">Password</label>
                    <input type="password" name="password" class="input" placeholder="At least 6 characters" required>
                </div>

                <div>
                    <label class="label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="input" placeholder="Re-enter your password" required>
                </div>

                <button type="submit" class="btn-primary w-full">Create Account</button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account?
                <a href="<?= url('/login') ?>" class="font-medium text-primary-600 hover:text-primary-500">Sign in</a>
            </p>
        </div>
    </div>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
