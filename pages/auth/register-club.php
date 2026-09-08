<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
    redirect('/');
}

require_once BASE_PATH . '/app/config/database.php';

$errors = [];

if (isPost()) {
    $fullName   = post('full_name');
    $email      = post('email');
    $phone      = post('phone');
    $password   = post('password');
    $confirm    = post('confirm_password');
    $clubName   = post('club_name');
    $description = post('description');

    if ($fullName === '' || $email === '' || $password === '' || $clubName === '') {
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
        $checkEmail = $db->prepare("SELECT club_user_id FROM club_users WHERE email = ? LIMIT 1");
        $checkEmail->bind_param('s', $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        $checkClub = $db->prepare("SELECT club_id FROM clubs WHERE club_name = ? LIMIT 1");
        $checkClub->bind_param('s', $clubName);
        $checkClub->execute();
        $checkClub->store_result();

        if ($checkEmail->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        }

        if ($checkClub->num_rows > 0) {
            $errors[] = 'A club with that name already exists or is under review.';
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $db->begin_transaction();
            try {
                // 1. Insert the club (pending by default)
                $clubStmt = $db->prepare("INSERT INTO clubs (club_name, description, status) VALUES (?, ?, 'pending')");
                $clubStmt->bind_param('ss', $clubName, $description);
                $clubStmt->execute();
                $clubId = $clubStmt->insert_id;

                // 2. Insert the owner as a club_user
                $userStmt = $db->prepare("
                    INSERT INTO club_users (club_id, full_name, email, password_hash, phone, role, status)
                    VALUES (?, ?, ?, ?, ?, 'owner', 'active')
                ");
                $userStmt->bind_param('issss', $clubId, $fullName, $email, $hashed, $phone);
                $userStmt->execute();
                $clubUserId = $userStmt->insert_id;

                // 3. Link the owner as the requester of the club
                $linkStmt = $db->prepare("UPDATE clubs SET requested_by = ? WHERE club_id = ?");
                $linkStmt->bind_param('ii', $clubUserId, $clubId);
                $linkStmt->execute();

                $db->commit();

                setOld([]);
                $_SESSION['flash']['success'] = 'Your club application has been submitted! It will be reviewed by an admin before your club can be activated.';
                redirect('/login?tab=club');
            } catch (Exception $e) {
                $db->rollback();
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
    <title>Club Application | <?= e(APP_NAME) ?></title>
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
                <h1 class="text-2xl font-bold text-gray-900">Club Registration</h1>
                <p class="text-sm text-gray-500 mt-1">Submit an application to register your club</p>
            </div>

            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                <span class="font-semibold">Important:</span> A club cannot use the site directly. By submitting this application, your club will be reviewed by a system admin. You will only be able to log in once your club is <span class="font-semibold">approved</span>.
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

            <form method="POST" action="<?= url('/register-club') ?>" class="space-y-4">
                <div class="border-b border-gray-200 pb-3">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Club Information</h2>
                </div>

                <div>
                    <label class="label">Club Name <span class="text-red-500">*</span></label>
                    <input type="text" name="club_name" class="input" placeholder="e.g. UIU Programming Club" value="<?= e(post('club_name')) ?>" required>
                </div>

                <div>
                    <label class="label">Club Description</label>
                    <textarea name="description" class="input" rows="3" placeholder="Tell us about your club..."><?= e(post('description')) ?></textarea>
                </div>

                <div class="border-b border-gray-200 pb-3 pt-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Applicant Information</h2>
                </div>

                <div>
                    <label class="label">Your Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" class="input" placeholder="John Doe" value="<?= e(post('full_name')) ?>" required>
                </div>

                <div>
                    <label class="label">Your Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="input" placeholder="you@example.com" value="<?= e(post('email')) ?>" required>
                </div>

                <div>
                    <label class="label">Phone Number</label>
                    <input type="text" name="phone" class="input" placeholder="01XXXXXXXXX" value="<?= e(post('phone')) ?>">
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

                <button type="submit" class="btn-primary w-full">Submit Application</button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Already have a club account?
                <a href="<?= url('/login?tab=club') ?>" class="font-medium text-primary-600 hover:text-primary-500">Log in</a>
            </p>
        </div>
    </div>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
