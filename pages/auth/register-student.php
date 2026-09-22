<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
    redirect('/');
}

require_once BASE_PATH . '/app/config/database.php';

$errors = [];
$registeredEmail = null;

if (isPost()) {
    $universityId = post('university_id');
    $fullName     = post('full_name');
    $email        = post('email');
    $department   = post('department');
    $phone        = post('phone');
    $password     = post('password');
    $confirm      = post('confirm_password');
    $agree        = isset($_POST['agree']);

    if ($fullName === '' || $email === '' || $universityId === '' || $department === '' || $password === '' || $confirm === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$agree) {
        $errors[] = 'You must agree to the Terms and Privacy Policy to register.';
    }

    if ($department !== '') {
        $prefixes = departmentIdPrefixes();
        $prefix = $prefixes[$department] ?? null;

        if ($prefix !== null && substr($universityId, 0, 3) !== $prefix) {
            $errors[] = 'Student ID must start with ' . $prefix . ' for the ' . $department . ' department.';
        }
    }

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
            INSERT INTO students (university_id, full_name, email, password_hash, department, phone, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param(
            'ssssss',
            $universityId,
            $fullName,
            $email,
            $hashed,
            $department,
            $phone
        );

        if ($stmt->execute()) {
            $newStudentId = (int) $db->insert_id;
            linkGuestRegistrations($newStudentId, $email);
            $registeredEmail = $email;
            setOld([]);
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Create Account';
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

    <div class="w-full max-w-lg">
        <div class="card p-6 sm:p-8">
            <?php if ($registeredEmail !== null): ?>
                <div class="flex flex-col items-center text-center py-4">
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 mb-4"><?= icon('check-circle', 'w-7 h-7') ?></span>
                    <h1 class="text-xl font-bold text-gray-900">Verify your email</h1>
                    <p class="text-sm text-gray-500 mt-2 max-w-sm">We sent a verification link to <span class="font-medium text-gray-700"><?= e($registeredEmail) ?></span>. Please check your inbox to activate your Eventrify account.</p>
                    <a href="<?= url('/login') ?>" class="btn-primary mt-6">Go to Login</a>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-gray-900">Create your account</h1>
                    <p class="text-sm text-gray-500 mt-1">Register for events, track attendance, and more.</p>
                </div>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error mb-4" role="alert">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 shrink-0"><?= icon('x-circle', 'w-5 h-5') ?></span>
                            <p class="text-sm"><?= e($error) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="<?= url('/register-student') ?>" class="space-y-4">
                    <div class="form-group">
                        <label for="full_name" class="label label-required">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="input" placeholder="John Doe" value="<?= e(post('full_name')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="label label-required">University Email</label>
                        <input type="email" id="email" name="email" class="input" placeholder="your.name@university.edu" value="<?= e(post('email')) ?>" required>
                        <?php if (in_array('An account with that email already exists.', $errors, true)): ?>
                            <p class="text-xs text-red-600 mt-1"><?= icon('x-circle', 'w-3.5 h-3.5 inline -mt-0.5') ?> This email is already registered.</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="department" class="label label-required">Department</label>
                        <select id="department" name="department" class="select" required>
                            <option value="">Select your department...</option>
                            <?php foreach (departmentOptions() as $dept): ?>
                                <option value="<?= e($dept) ?>" <?= post('department') === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="university_id" class="label label-required">Student ID</label>
                        <input type="text" id="university_id" name="university_id" class="input" placeholder="e.g. 0112211234" value="<?= e(post('university_id')) ?>" required>
                        <p class="form-hint" id="id-prefix-hint">Student ID starts with your department prefix.</p>
                        <p class="text-xs text-red-600 mt-1 hidden" id="id-prefix-error"></p>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="input" placeholder="01XXXXXXXXX" value="<?= e(post('phone')) ?>">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="password" class="label label-required">Password</label>
                            <input type="password" id="password" name="password" class="input" placeholder="At least 8 characters" minlength="8" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="label label-required">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="input" placeholder="Re-enter password" minlength="8" required>
                        </div>
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer pt-1">
                        <input type="checkbox" name="agree" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= isset($_POST['agree']) ? 'checked' : '' ?> required>
                        <span class="text-sm text-gray-600">I agree to the Terms and Privacy Policy</span>
                    </label>

                    <button type="submit" class="btn-primary btn-lg w-full">Create Student Account</button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">Already have an account? <a href="<?= url('/login') ?>" class="font-medium text-blue-600 hover:text-blue-500">Log in</a></p>
            <?php endif; ?>
        </div>
    </div>

    <p class="mt-6 text-sm text-gray-500">&copy; <?= date('Y') ?> Eventrify &middot; University Club Events</p>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
    <script>
    (function () {
        var prefixMap = <?= json_encode(departmentIdPrefixes()) ?>;
        var dept = document.getElementById('department');
        var idInput = document.getElementById('university_id');
        var hint = document.getElementById('id-prefix-hint');
        var errorEl = document.getElementById('id-prefix-error');
        if (!dept || !idInput || !hint || !errorEl) return;

        function check() {
            var prefix = prefixMap[dept.value] || null;
            if (!prefix) {
                hint.textContent = 'Student ID starts with your department prefix.';
                hint.classList.remove('hidden');
                errorEl.classList.add('hidden');
                return;
            }
            var val = idInput.value.trim();
            if (val === '') {
                hint.textContent = 'Student ID starts with ' + prefix + ' for ' + dept.value + '.';
                hint.classList.remove('hidden');
                errorEl.classList.add('hidden');
                return;
            }
            if (val.substring(0, 3) !== prefix) {
                hint.classList.add('hidden');
                errorEl.textContent = 'Student ID must start with ' + prefix + ' for the ' + dept.value + ' department.';
                errorEl.classList.remove('hidden');
            } else {
                errorEl.classList.add('hidden');
                hint.textContent = 'Student ID matches the ' + dept.value + ' department.';
                hint.classList.remove('hidden');
            }
        }

        dept.addEventListener('change', check);
        idInput.addEventListener('input', check);
    })();
    </script>
</body>
</html>