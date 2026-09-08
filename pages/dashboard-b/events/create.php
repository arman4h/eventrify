<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubUser();

$pageTitle = 'Create Event';
$activePage = 'events';

$errors = [];

if (isPost()) {
    $title                = post('title');
    $description          = post('description');
    $category             = post('category');
    $venue                = post('venue');
    $startTime            = post('start_time');
    $endTime              = post('end_time');
    $registrationDeadline = post('registration_deadline');
    $capacity             = (int) post('capacity', 0);
    $status               = post('status', 'draft');

    $allowed = ['draft', 'published', 'cancelled', 'completed'];
    if (!in_array($status, $allowed, true)) {
        $status = 'draft';
    }

    if ($title === '' || $venue === '' || $startTime === '') {
        $errors[] = 'Title, venue, and start time are required.';
    }

    if ($endTime !== '' && $endTime < $startTime) {
        $errors[] = 'End time cannot be before the start time.';
    }

    if (empty($errors)) {
        $clubId   = (int) currentUser()['club_id'];
        $createdBy = currentUserId();

        $stmt = $db->prepare("
            INSERT INTO events
                (club_id, title, description, category, venue,
                 start_time, end_time, registration_deadline, capacity, status, created_by)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $end = $endTime !== '' ? $endTime : null;
        $deadline = $registrationDeadline !== '' ? $registrationDeadline : null;

        $stmt->bind_param(
            'isssssssisi',
            $clubId,
            $title,
            $description,
            $category,
            $venue,
            $startTime,
            $end,
            $deadline,
            $capacity,
            $status,
            $createdBy
        );

        if ($stmt->execute()) {
            $_SESSION['flash']['success'] = 'Event created successfully.';
            redirect('/club/events');
        } else {
            $errors[] = 'Failed to create event.';
        }
    }
}

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Create New Event</h2>
            <p class="text-sm text-gray-500 mt-1">Set up a new club event</p>
        </div>
        <a href="<?= url('/club/events') ?>" class="btn-secondary">Back to Events</a>
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

    <div class="card p-6 max-w-2xl">
        <form method="POST" class="space-y-5">
            <div>
                <label class="label">Event Title</label>
                <input type="text" name="title" class="input" value="<?= e(post('title')) ?>" placeholder="e.g. Annual Tech Fest 2026" required>
            </div>

            <div>
                <label class="label">Description</label>
                <textarea name="description" rows="4" class="input" placeholder="Describe the event..." required><?= e(post('description')) ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Category</label>
                    <input type="text" name="category" class="input" value="<?= e(post('category')) ?>" placeholder="e.g. Technical, Cultural, Sports">
                </div>

                <div>
                    <label class="label">Venue</label>
                    <input type="text" name="venue" class="input" value="<?= e(post('venue')) ?>" placeholder="e.g. Auditorium, Campus Building" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Start Time</label>
                    <input type="datetime-local" name="start_time" class="input" value="<?= e(post('start_time')) ?>" required>
                </div>

                <div>
                    <label class="label">End Time</label>
                    <input type="datetime-local" name="end_time" class="input" value="<?= e(post('end_time')) ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Registration Deadline</label>
                    <input type="datetime-local" name="registration_deadline" class="input" value="<?= e(post('registration_deadline')) ?>">
                </div>

                <div>
                    <label class="label">Capacity</label>
                    <input type="number" name="capacity" class="input" value="<?= e(post('capacity', 0)) ?>" min="0" required>
                </div>
            </div>

            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="draft" <?= post('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= post('status') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="cancelled" <?= post('status') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="completed" <?= post('status') === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Create Event</button>
                <a href="<?= url('/club/events') ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
