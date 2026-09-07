<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();

$pageTitle = 'Edit Event';
$activePage = 'events';

$eventId = (int) get('event_id');
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param('i', $eventId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    abort(404, 'Event not found');
}

$errors = [];

if (isPost()) {
    $title = post('title');
    $description = post('description');
    $venue = post('venue');
    $eventDate = post('event_date');
    $capacity = (int) post('capacity');
    $status = post('status');

    if ($title === '' || $description === '' || $venue === '' || $eventDate === '') {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE events SET title = ?, description = ?, venue = ?, event_date = ?, capacity = ?, status = ? WHERE id = ?");
        $stmt->bind_param('ssssisi', $title, $description, $venue, $eventDate, $capacity, $status, $eventId);

        if ($stmt->execute()) {
            $_SESSION['flash']['success'] = 'Event updated successfully.';
            redirect('/club/events');
        } else {
            $errors[] = 'Failed to update event.';
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
            <h2 class="text-xl font-bold text-gray-900">Edit Event</h2>
            <p class="text-sm text-gray-500 mt-1">Update event details</p>
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
                <input type="text" name="title" class="input" value="<?= e(isPost() ? post('title') : $event['title']) ?>" required>
            </div>

            <div>
                <label class="label">Description</label>
                <textarea name="description" rows="4" class="input" required><?= e(isPost() ? post('description') : $event['description']) ?></textarea>
            </div>

            <div>
                <label class="label">Venue</label>
                <input type="text" name="venue" class="input" value="<?= e(isPost() ? post('venue') : $event['venue']) ?>" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Event Date</label>
                    <input type="datetime-local" name="event_date" class="input" value="<?= e(isPost() ? post('event_date') : $event['event_date']) ?>" required>
                </div>

                <div>
                    <label class="label">Capacity</label>
                    <input type="number" name="capacity" class="input" value="<?= isPost() ? post('capacity') : (int) $event['capacity'] ?>" min="1" required>
                </div>
            </div>

            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <?php foreach (['upcoming', 'ongoing', 'completed', 'cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $event['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Update Event</button>
                <a href="<?= url('/club/events') ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
