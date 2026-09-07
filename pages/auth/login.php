<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
    redirect('/');
}

require_once BASE_PATH . '/app/config/database.php';

$error = null;

if (isPost()) {
    $email = post('email');
    $password = post('password');

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];

            if ($user['role'] === 'admin') {
                redirect('/admin');
            }

            redirect('/club');
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
    <title>Login | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-900">
    <div class="w-full max-w-md p-8">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto bg-primary-600 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-2.09 5.42-3.07 7.02-.68 1.1-1.35 1.48-1.98 1.48-.36 0-.66-.27-.66-.6 0-.41.31-.72.69-.77.15-.02.3-.05.3-.05l-1.7-1.02L8.9 16.4c-.21.21-.54.21-.76.02-.21-.21-.2-.54.01-.76l3.9-3.93c.21-.22.54-.35.63-.13.09.22-.07.55-.34.78l-2.62 2.62 3.27 1.95c1.39-2.49 2.18-4.44 2.31-5.89.05-.53-.21-.87-.72-.86-.39 0-.71.3-.93.82-.3.7-.96 1.6-1.96 2.01-.88.36-2.18-.21-2.1-1.06.04-.42.47-.41.55-.4.05.01-.26-.48-.32-.9-.07-.48.28-.7.56-.7h.02c1.37.07 2.82-.61 2.82-2.29 0-.47-.33-.9-.82-.9-.07 0-.14.01.01.09-.46.23-1.15.92-1.49 1.88-.22.62-.4 1.73-.4 2.76-.89-.18-1.57-.46-1.99-.85-.77-.71-.9-2.2.32-3.03.6-.41 1.45-.53 2.19-.45.43.05.84-.04 1.12-.15-.51-.19-1.11-.28-1.7-.22-.9.08-1.79.42-2.4.91-1.81 1.44-1.62 3.66-.53 4.64.2.18.44.28.68.36.29.75.93 1.13 1.66 1.59l.13.62c-.71.12-1.46-.1-1.97.15-.68.33-1.11.93-1.44 1.58-.34-.14-.65-.48-.88-.86-1.48-2.47-1.19-5.48-1.19-5.48s-1.54.72-2.3 1.18c-.23.14-.45.33-.64.53-.47.49-.82 1.06-1.03 1.68-.13-.21-.25-.41-.37-.63C4.88 9.05 6.5 5.4 10.4 4.4c2.32-.6 4.73.09 6.28 1.9.58.68.94 1.5 1.1 2.37.19 1.02.28 2.1.1 3.13h-.01z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900"><?= e(APP_NAME) ?></h1>
                <p class="text-sm text-gray-500 mt-1">Sign in to your account</p>
            </div>

            <?php
            $alertType = 'error';
            $alertMessage = $error;
            require_once BASE_PATH . '/app/components/alert.php';
            ?>

            <form method="POST" action="<?= url('/login') ?>" class="space-y-5">
                <div>
                    <label class="label">Email Address</label>
                    <input type="email" name="email" class="input" placeholder="you@example.com" value="<?= e(old('email')) ?>" required>
                </div>

                <div>
                    <label class="label">Password</label>
                    <input type="password" name="password" class="input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-primary w-full">Sign In</button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Don't have an account?
                <a href="<?= url('/register') ?>" class="font-medium text-primary-600 hover:text-primary-500">Register</a>
            </p>
        </div>
    </div>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
