<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
    redirect('/');
}

require_once BASE_PATH . '/app/config/database.php';

$errors = [];

if (isPost()) {
    $universityId = post('university_id');
    $fullName     = post('full_name');
    $email        = post('email');
    $department   = post('department');
    $batch        = post('batch');
    $phone        = post('phone');
    $interests    = post('interests');
    $password     = post('password');
    $confirm      = post('confirm_password');

    if ($universityId === '' || $fullName === '' || $email === '' || $password === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $checkEmail = $db->prepare("SELECT student_id FROM students WHERE email = ? LIMIT 1");
        $checkEmail->bind_param('s', $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        $checkId = $db->prepare("SELECT student_id FROM students WHERE university_id = ? LIMIT 1");
        $checkId->bind_param('s', $universityId);
        $checkId->execute();
        $checkId->store_result();

        if ($checkEmail->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        }

        if ($checkId->num_rows > 0) {
            $errors[] = 'A student with that university ID already exists.';
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO students (university_id, full_name, email, password_hash, department, batch, phone, interests, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param(
                'ssssssss',
                $universityId,
                $fullName,
                $email,
                $hashed,
                $department,
                $batch,
                $phone,
                $interests
            );

            if ($stmt->execute()) {
                setOld([]);
                $_SESSION['flash']['success'] = 'Registration successful! Please log in.';
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
    <title>Student Registration | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-900 py-10">
    <div class="w-full max-w-lg p-8">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto bg-primary-600 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Student Registration</h1>
                <p class="text-sm text-gray-500 mt-1">Register with your university information</p>
            </div>

            <?php if (flash('success')): ?>
            <div class="mb-4">
                <?php
                $alertType = 'success';
                $alertMessage = flash('success');
                require_once BASE_PATH . '/app/components/alert.php';
                ?>
            </div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
            <div class="mb-2">
                <?php
                $alertType = 'error';
                $alertMessage = $error;
                require BASE_PATH . '/app/components/alert.php';
                ?>
            </div>
            <?php endforeach; ?>

            <form method="POST" action="<?= url('/register-student') ?>" class="space-y-4">
                <div>
                    <label class="label">University ID <span class="text-red-500">*</span></label>
                    <input type="text" name="university_id" class="input" placeholder="e.g. 011 231 456" value="<?= e(post('university_id')) ?>" required>
                </div>

                <div>
                    <label class="label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" class="input" placeholder="John Doe" value="<?= e(post('full_name')) ?>" required>
                </div>

                <div>
                    <label class="label">University Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="input" placeholder="student@uiu.ac.bd" value="<?= e(post('email')) ?>" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Department</label>
                        <input type="text" name="department" class="input" placeholder="CSE" value="<?= e(post('department')) ?>">
                    </div>
                    <div>
                        <label class="label">Batch</label>
                        <input type="text" name="batch" class="input" placeholder="e.g. 55" value="<?= e(post('batch')) ?>">
                    </div>
                </div>

                <div>
                    <label class="label">Phone Number</label>
                    <input type="text" name="phone" class="input" placeholder="01XXXXXXXXX" value="<?= e(post('phone')) ?>">
                </div>

                <div>
                    <label class="label">Interests <span class="text-xs text-gray-400">(comma separated)</span></label>
                    <textarea name="interests" class="input" rows="2" placeholder="e.g. Programming, Sports, Music"><?= e(post('interests')) ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" class="input" placeholder="At least 6 characters" required>
                    </div>
                    <div>
                        <label class="label">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" name="confirm_password" class="input" placeholder="Re-enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full">Register</button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account?
                <a href="<?= url('/login?tab=student') ?>" class="font-medium text-primary-600 hover:text-primary-500">Log in</a>
            </p>
        </div>
    </div>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
