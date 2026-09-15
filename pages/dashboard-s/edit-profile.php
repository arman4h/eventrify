<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireStudent();

$studentId = (int) currentUserId();

$stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$studentRow = $stmt->get_result()->fetch_assoc();
$student = $studentRow ?: currentUser();

$errors = [];

if (isPost()) {
    $fullName   = post('full_name');
    $phone      = post('phone');
    $department = post('department');
    $interests  = post('interests');
    $batch      = post('batch');

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("
            UPDATE students
            SET full_name = ?, phone = ?, department = ?, interests = ?, batch = ?
            WHERE student_id = ?
        ");
        $stmt->bind_param('sssssi', $fullName, $phone, $department, $interests, $batch, $studentId);

        if ($stmt->execute()) {
            $_SESSION['user']['name'] = $fullName;
            $_SESSION['user']['department'] = $department;
            flash('success', 'Your profile has been updated successfully.');
            clearOld();
            redirect('/student/profile');
        } else {
            $errors[] = 'Could not save your changes. Please try again.';
        }
    }

    setOld($_POST);
}

$pageTitle = 'Edit Profile';
$activePage = 'profile';
require BASE_PATH . '/app/layouts/dashboard-s/header.php';
require BASE_PATH . '/app/layouts/dashboard-s/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-s/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <div class="page-header">
            <div>
                <h1 class="page-title">Edit Profile</h1>
                <p class="page-subtitle">Update your personal and academic details.</p>
            </div>
            <a href="<?= url('/student/profile') ?>" class="btn-ghost">
                <?= icon('arrow-left', 'w-4 h-4') ?>
                Back to Profile
            </a>
        </div>

        <?php foreach ($errors as $error): ?>
        <div class="alert alert-error mb-4" role="alert">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 shrink-0"><?= icon('x-circle', 'w-5 h-5') ?></span>
                <p class="text-sm"><?= e($error) ?></p>
            </div>
        </div>
        <?php endforeach; ?>

        <form method="POST" action="<?= url('/student/profile/edit') ?>" enctype="multipart/form-data" class="card p-6 sm:p-8 max-w-2xl">
            <div class="flex items-center gap-5 pb-6 border-b border-gray-200">
                <span class="avatar-lg !w-16 !h-16 !text-xl" aria-hidden="true"><?= e(substr($student['full_name'] ?? 'S', 0, 1)) ?></span>
                <div>
                    <label class="btn-secondary btn-sm cursor-not-allowed">
                        <?= icon('image', 'w-4 h-4') ?>
                        Upload
                        <input type="file" name="profile_picture" class="hidden" disabled>
                    </label>
                    <p class="form-hint">Profile picture uploads aren't supported yet.</p>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-base font-semibold text-gray-900">Personal Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div class="form-group sm:col-span-2">
                        <label for="full_name" class="label label-required">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="input" value="<?= e(old('full_name', $student['full_name'] ?? '')) ?>" placeholder="John Doe" required>
                    </div>
                    <div class="form-group sm:col-span-2">
                        <label for="phone" class="label">Phone</label>
                        <input type="tel" id="phone" name="phone" class="input" value="<?= e(old('phone', $student['phone'] ?? '')) ?>" placeholder="01XXXXXXXXX">
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-base font-semibold text-gray-900">Academic Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div class="form-group sm:col-span-2">
                        <label for="department" class="label">Department</label>
                        <input type="text" id="department" name="department" class="input" value="<?= e(old('department', $student['department'] ?? '')) ?>" placeholder="Computer Science & Engineering">
                    </div>
                    <div class="form-group">
                        <label for="interests" class="label">Program</label>
                        <input type="text" id="interests" name="interests" class="input" value="<?= e(old('interests', $student['interests'] ?? '')) ?>" placeholder="BSc in CSE">
                    </div>
                    <div class="form-group">
                        <label for="batch" class="label">Trimester/Semester</label>
                        <input type="text" id="batch" name="batch" class="input" value="<?= e(old('batch', $student['batch'] ?? '')) ?>" placeholder="e.g. Summer 2026">
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-base font-semibold text-gray-900">Verified Fields</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div class="form-group">
                        <label for="email" class="label">University Email</label>
                        <input type="email" id="email" class="input opacity-60 cursor-not-allowed" value="<?= e($student['email'] ?? '') ?>" disabled readonly>
                    </div>
                    <div class="form-group">
                        <label for="university_id" class="label">Student ID</label>
                        <input type="text" id="university_id" class="input opacity-60 cursor-not-allowed" value="<?= e($student['university_id'] ?? '') ?>" disabled readonly>
                    </div>
                </div>
                <p class="form-hint mt-2">University email and Student ID are verified and cannot be changed.</p>
            </div>

            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <a href="<?= url('/student/profile') ?>" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-s/footer.php'; ?>
</div>