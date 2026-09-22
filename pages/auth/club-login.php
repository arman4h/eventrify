<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
    if (isClubUser()) {
        redirect('/club');
    }
    redirect('/');
}

require_once BASE_PATH . '/app/config/database.php';

$error = null;

if (isPost()) {
    $email    = post('email');
    $password = post('password');

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $db->prepare("SELECT cu.*, c.status AS club_status FROM club_users cu JOIN clubs c ON c.club_id = cu.club_id WHERE cu.email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $cu = $stmt->get_result()->fetch_assoc();

        if ($cu && password_verify($password, $cu['password_hash'])) {
            if ($cu['status'] !== 'active') {
                $error = 'This club account is not active.';
            } elseif ($cu['club_status'] === 'rejected') {
                $error = 'Your club application was not approved. Please contact the administration.';
            } else {
                loginClubUser($cu);
                redirect('/club');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Club Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col items-center justify-center px-4 py-12">
    <a href="<?= url('/') ?>" class="flex items-center gap-2.5 mb-8">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white text-lg font-bold">E</span>
        <span class="text-2xl font-bold tracking-tight text-gray-900">Eventrify</span>
    </a>

    <div class="w-full max-w-sm">
        <div class="card p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900">Club Administration</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your club's events, registrations, and attendance.</p>
            </div>

            <div class="alert alert-info mb-4" role="alert">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 shrink-0"><?= icon('info', 'w-5 h-5') ?></span>
                    <p class="text-sm">Registered clubs can sign in to track their approval status. Full dashboard access unlocks once your club is approved.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error mb-4" role="alert">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 shrink-0"><?= icon('x-circle', 'w-5 h-5') ?></span>
                        <p class="text-sm"><?= e($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/club/login') ?>" class="space-y-4">
                <div class="form-group">
                    <label for="email" class="label">Club Email</label>
                    <input type="email" id="email" name="email" class="input" placeholder="club@university.edu" value="<?= e(post('email')) ?>" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" class="input" placeholder="Enter your password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-primary btn-lg w-full">Login</button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-5">Representing a university club? <a href="<?= url('/club/register') ?>" class="font-medium text-blue-600 hover:text-blue-500">Request Club Access</a></p>
        </div>

        <p class="mt-4 text-center text-sm text-gray-500"><a href="<?= url('/login') ?>" class="font-medium text-gray-700 hover:text-gray-900">Student login &rarr;</a></p>
    </div>

    <p class="mt-6 text-sm text-gray-500">&copy; <?= date('Y') ?> Eventrify &middot; University Club Events</p>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
