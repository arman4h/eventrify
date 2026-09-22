<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
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
        $stmt = $db->prepare("SELECT * FROM students WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if ($student && password_verify($password, $student['password_hash'])) {
            if (!$student['is_active']) {
                $error = 'Your account is deactivated. Please contact support.';
            } else {
                linkGuestRegistrations((int) $student['student_id'], $student['email']);
                loginStudent($student);
                redirect('/student');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Log In';
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

    <div class="w-full max-w-md">
        <div class="card p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900">Welcome back</h1>
                <p class="text-sm text-gray-500 mt-1">Log in to your Eventrify student account.</p>
            </div>

            <p class="form-hint mb-5">Student accounts require an official university email.</p>

            <?php if ($error): ?>
                <div class="alert alert-error mb-4" role="alert">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 shrink-0"><?= icon('x-circle', 'w-5 h-5') ?></span>
                        <p class="text-sm"><?= e($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/login') ?>" class="space-y-4">
                <div class="form-group">
                    <label for="email" class="label">University Email</label>
                    <input type="email" id="email" name="email" class="input" placeholder="your.name@university.edu" value="<?= e(post('email')) ?>" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" class="input" placeholder="Enter your password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-primary btn-lg w-full">Login</button>
            </form>

            <div class="flex items-center justify-between mt-5">
                <a href="#" class="text-sm text-blue-600 hover:text-blue-500">Forgot Password?</a>
                <p class="text-sm text-gray-500">Don't have an account? <a href="<?= url('/register-student') ?>" class="font-medium text-blue-600 hover:text-blue-500">Register</a></p>
            </div>
        </div>

        <p class="mt-4 text-center text-sm text-gray-500">Are you a club? <a href="<?= url('/club/login') ?>" class="font-medium text-gray-700 hover:text-gray-900">Club Login</a></p>
    </div>

    <p class="mt-6 text-sm text-gray-500">&copy; <?= date('Y') ?> Eventrify &middot; University Club Events</p>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
