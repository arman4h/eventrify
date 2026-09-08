<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isSystemAdmin()) {
    redirect('/admin');
}

require_once BASE_PATH . '/app/config/database.php';

$error = null;

if (isPost()) {
    $email    = post('email');
    $password = post('password');

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $db->prepare("SELECT * FROM system_admins WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            loginSystemAdmin($admin);
            redirect('/admin');
        } else {
            $error = 'Email or password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-800 via-gray-900 to-black">
    <div class="w-full max-w-md p-8">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto bg-gray-900 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>
                <p class="text-sm text-gray-500 mt-1">System administrator login</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-4">
                <?php
                $alertType = 'error';
                $alertMessage = $error;
                require_once BASE_PATH . '/app/components/alert.php';
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/admin/login') ?>" class="space-y-5">
                <div>
                    <label class="label">Admin Email</label>
                    <input type="email" name="email" class="input" placeholder="admin@eventrify.com" value="<?= e(post('email')) ?>" required autocomplete="username">
                </div>
                <div>
                    <label class="label">Password</label>
                    <input type="password" name="password" class="input" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">Log In</button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                <a href="<?= url('/login') ?>" class="font-medium text-gray-600 hover:text-gray-800">&larr; Back to main login</a>
            </p>
        </div>
    </div>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
