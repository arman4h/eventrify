<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('attendance');

$pageTitle = 'Walk-in Registration';
$activePage = 'attendance';

$clubId = (int) currentUser()['club_id'];
$eventId = (int) get('event_id', 0);

$stmt = $db->prepare("SELECT * FROM events WHERE event_id = ? AND club_id = ?");
$stmt->bind_param('ii', $eventId, $clubId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    abort(404, 'Event not found');
}

$success = false;
$guestName = '';

if (isPost()) {
    $name = post('full_name');
    $studentId = post('student_id');
    $email = post('email');
    $phone = post('phone');
    $department = post('department');

    if ($name !== '') {
        $sid = null;
        if ($studentId !== '') {
            $stu = $db->prepare("SELECT student_id FROM students WHERE university_id = ? LIMIT 1");
            $stu->bind_param('s', $studentId);
            $stu->execute();
            $stuRow = $stu->get_result()->fetch_assoc();
            if ($stuRow) {
                $sid = (int) $stuRow['student_id'];
            }
        }
        $ins = $db->prepare("INSERT INTO event_registrations (event_id, student_id, guest_name, guest_student_id, is_walkin, status, checked_in_at, checked_in_by, registered_at) VALUES (?, ?, ?, ?, 1, 'attended', NOW(), ?, NOW())");
        $userId = currentUserId();
        $gid = $studentId !== '' ? $studentId : null;
        $ins->bind_param('iissi', $eventId, $sid, $name, $gid, $userId);
        if ($ins->execute()) {
            $success = true;
            $guestName = $name;
            $_SESSION['flash']['success'] = 'Walk-in registered and checked in.';
        } else {
            $_SESSION['flash']['error'] = 'Failed to register walk-in.';
        }
    } else {
        $_SESSION['flash']['error'] = 'Full name is required.';
    }
}

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $alertMessage = $flashSuccess ?: $flashError;
        $alertType = $flashSuccess ? 'success' : ($flashError ? 'error' : 'info');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <nav class="breadcrumb mb-6">
            <a href="<?= url('/club/attendance') ?>" class="breadcrumb-link">Attendance</a>
            <span class="text-gray-400">/</span>
            <span class="breadcrumb-current">Walk-in Registration</span>
        </nav>

        <?php if ($success): ?>
            <div class="card p-6 max-w-xl text-center">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                    <?= icon('check-circle', 'w-6 h-6') ?>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Walk-in Registration Successful</h3>
                <p class="text-sm text-gray-500 mb-1"><?= e($guestName) ?></p>
                <p class="text-sm text-gray-500 mb-4">has been registered and checked in for <?= e($event['title']) ?>.</p>
                <a href="<?= url('/club/attendance?event_id=' . $eventId) ?>" class="btn-primary">Done</a>
            </div>
        <?php else: ?>
            <div class="card p-6 sm:p-8 max-w-xl">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Walk-in Registration</h3>
                    <p class="text-sm text-gray-500 mt-1">Register an event-day participant &amp; check them in immediately.</p>
                </div>

                <form method="POST" class="space-y-4">
                    <div class="form-group">
                        <label class="label-required">Full Name</label>
                        <input type="text" name="full_name" class="input" value="<?= e(post('full_name')) ?>" placeholder="Participant's full name" required>
                    </div>
                    <div class="form-group">
                        <label class="label">Student ID</label>
                        <input type="text" name="student_id" class="input" value="<?= e(post('student_id')) ?>" placeholder="University student ID">
                    </div>
                    <div class="form-group">
                        <label class="label">University Email</label>
                        <input type="email" name="email" class="input" value="<?= e(post('email')) ?>" placeholder="participant@university.edu">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="label">Phone</label>
                            <input type="tel" name="phone" class="input" value="<?= e(post('phone')) ?>" placeholder="+880...">
                        </div>
                        <div class="form-group">
                            <label class="label">Department</label>
                            <input type="text" name="department" class="input" value="<?= e(post('department')) ?>" placeholder="e.g. CSE">
                        </div>
                    </div>
                    <p class="form-hint">Optional for guests; required fields marked.</p>
                    <button type="submit" class="btn-primary btn-lg w-full">Register &amp; Check In</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
