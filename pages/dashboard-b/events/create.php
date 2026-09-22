<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('events');

$pageTitle = 'Create Event';
$activePage = 'events';

$errors = [];

if (isPost()) {
    $title = post('title');
    $description = post('description');
    $category = post('category');
    $date = post('date');
    $startTime = post('start_time');
    $endTime = post('end_time');
    $venue = post('venue');
    $capacity = (int) post('capacity', 0);
    $registrationDeadline = post('registration_deadline');
    $status = post('status', 'draft');
    $poster = '';

    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['poster']['tmp_name'];
        $mime = mime_content_type($tmpPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $size = (int) $_FILES['poster']['size'];

        if (!in_array($mime, $allowedMimes, true)) {
            $errors[] = 'Poster must be a JPG, PNG, WebP, or GIF image.';
        } elseif ($size > 5 * 1024 * 1024) {
            $errors[] = 'Poster image must be 5MB or smaller.';
        } else {
            $poster = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($tmpPath));
        }
    }

    $allowed = ['draft', 'published', 'cancelled', 'completed'];
    if (!in_array($status, $allowed, true)) {
        $status = 'draft';
    }

    $startDateTime = ($date !== '' && $startTime !== '') ? "$date $startTime:00" : '';
    $endDateTime = ($date !== '' && $endTime !== '') ? "$date $endTime:00" : '';

    if ($title === '' || $venue === '' || $startDateTime === '') {
        $errors[] = 'Title, venue, and start time are required.';
    }

    if ($endTime !== '' && $startTime !== '' && $endTime < $startTime) {
        $errors[] = 'End time cannot be before the start time.';
    }

    if (empty($errors)) {
        $clubId = (int) currentUser()['club_id'];
        $createdBy = currentUserId();

        $stmt = $db->prepare("
            INSERT INTO events
                (club_id, title, description, category, poster, venue,
                 start_time, end_time, registration_deadline, capacity, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $end = $endDateTime !== '' ? $endDateTime : null;
        $deadline = $registrationDeadline !== '' ? $registrationDeadline : null;

        $stmt->bind_param('issssssssisi', $clubId, $title, $description, $category, $poster, $venue, $startDateTime, $end, $deadline, $capacity, $status, $createdBy);

        if ($stmt->execute()) {
            $newEventId = $stmt->insert_id;

            insertRegistrationFields($db, $newEventId, $_POST['questions'] ?? []);

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
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <nav class="breadcrumb mb-6">
            <a href="<?= url('/club/events') ?>" class="breadcrumb-link">Events</a>
            <span class="text-gray-400">/</span>
            <span class="breadcrumb-current">Create Event</span>
        </nav>

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Create Event</h2>
                <p class="page-subtitle">Set up a new club event</p>
            </div>
        </div>

        <?php foreach ($errors as $error): ?>
            <?php
            $alertType = 'error';
            $alertMessage = $error;
            require BASE_PATH . '/app/components/alert.php';
            ?>
        <?php endforeach; ?>

        <ol class="flex items-center gap-2 text-xs font-medium mb-8">
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center">1</span>
                <span class="hidden sm:inline text-gray-900">Basic</span>
            </li>
            <li class="text-gray-300">—</li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center">2</span>
                <span class="hidden sm:inline text-gray-900">Schedule</span>
            </li>
            <li class="text-gray-300">—</li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center">3</span>
                <span class="hidden sm:inline text-gray-900">Location</span>
            </li>
            <li class="text-gray-300">—</li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center">4</span>
                <span class="hidden sm:inline text-gray-900">Capacity</span>
            </li>
            <li class="text-gray-300">—</li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center">5</span>
                <span class="hidden sm:inline text-gray-900">Form</span>
            </li>
            <li class="text-gray-300">—</li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center">6</span>
                <span class="hidden sm:inline text-gray-900">Publish</span>
            </li>
        </ol>

        <form method="POST" enctype="multipart/form-data" class="card p-6 sm:p-8 space-y-8">
            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Basic Information</h3>
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="label-required">Event Name</label>
                        <input type="text" name="title" class="input" value="<?= e(post('title')) ?>" placeholder="e.g. Annual Tech Fest 2026" required>
                    </div>
                    <div class="form-group">
                        <label class="label">Event Description</label>
                        <textarea name="description" rows="4" class="textarea" placeholder="Describe the event..."><?= e(post('description')) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="label">Event Category</label>
                        <select name="category" class="select">
                            <option value="">Select category...</option>
                            <?php foreach (['Workshop', 'Seminar', 'Competition', 'Contest', 'Bootcamp', 'Hackathon', 'Cultural', 'Showcase', 'Sports', 'Other'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= post('category') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Event Poster</label>
                        <input type="file" name="poster" accept="image/*" class="input">
                        <p class="form-hint">Optional. Accepted formats: JPG, PNG, GIF, WEBP (max 5MB).</p>
                    </div>
                </div>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Schedule</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="label-required">Date</label>
                        <input type="date" name="date" class="input" value="<?= e(post('date')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label-required">Start Time</label>
                        <input type="time" name="start_time" class="input" value="<?= e(post('start_time')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label">End Time</label>
                        <input type="time" name="end_time" class="input" value="<?= e(post('end_time')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Registration Deadline</label>
                        <input type="datetime-local" name="registration_deadline" class="input" value="<?= e(post('registration_deadline')) ?>">
                    </div>
                </div>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Location</h3>
                <div class="form-group">
                    <label class="label-required">Venue</label>
                    <input type="text" name="venue" class="input" value="<?= e(post('venue')) ?>" placeholder="e.g. Auditorium, Lab 203" required>
                    <p class="form-hint">Describe where the event takes place.</p>
                </div>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Capacity</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="label">Max Participants</label>
                        <input type="number" name="capacity" id="capacityInput" class="input" value="<?= e(post('capacity', '0')) ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label class="label">Enable Waitlist</label>
                        <label class="flex items-center gap-2 mt-2">
                            <input type="checkbox" name="enable_waitlist" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">Allow waitlist when full</span>
                        </label>
                    </div>
                </div>
                <div class="card p-4 mt-4">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">0 / <span id="capDisplay"><?= e(post('capacity', '0')) ?></span> seats filled</span>
                        <span class="text-gray-500">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Registration Form</h3>
                <p class="text-sm text-gray-500 mb-4">Every event automatically collects these fields from participants.</p>
                <div class="card p-4 border border-blue-100 bg-blue-50/40 mb-4">
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center gap-2"><?= icon('user', 'w-4 h-4 text-blue-600') ?> Full Name <span class="badge-danger">Required</span></li>
                        <li class="flex items-center gap-2"><?= icon('mail', 'w-4 h-4 text-blue-600') ?> Email <span class="badge-danger">Required</span></li>
                        <li class="flex items-center gap-2"><?= icon('ticket', 'w-4 h-4 text-blue-600') ?> Student ID <span class="badge-danger">Required</span></li>
                        <li class="flex items-center gap-2"><?= icon('grad', 'w-4 h-4 text-blue-600') ?> Department <span class="badge-danger">Required</span> <span class="text-xs text-gray-500">CSE, EEE, DS, English, BBA, EDS, Economics</span></li>
                    </ul>
                </div>
                <p class="text-sm text-gray-500 mb-3">Add custom fields for this specific event (optional).</p>
                <div id="questionsContainer" class="space-y-4">
                    <div class="card p-4 space-y-3 question-row">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-group">
                                <label class="label">Question Label</label>
                                <input type="text" name="questions[label][]" class="input" placeholder="e.g. T-shirt size">
                            </div>
                            <div class="form-group">
                                <label class="label">Field Type</label>
                                <select name="questions[type][]" class="select">
                                    <option value="short_text">Short Text</option>
                                    <option value="long_text">Long Text</option>
                                    <option value="number">Number</option>
                                    <option value="dropdown">Dropdown</option>
                                    <option value="radio">Radio</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="date">Date</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group question-options">
                            <label class="label">Options <span class="form-hint">(comma-separated, for dropdown/checkbox)</span></label>
                            <input type="text" name="questions[options][]" class="input" placeholder="e.g. S, M, L, XL">
                        </div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="questions[required][]" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">Required</span>
                        </label>
                    </div>
                </div>
                <button type="button" id="addQuestion" class="btn-secondary btn-sm mt-3">
                    <?= icon('plus', 'w-4 h-4') ?> Add Question
                </button>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Publish</h3>
                <div class="card p-6">
                    <p class="text-sm text-gray-600 mb-1 font-medium" id="previewTitle"><?= e(post('title')) ?: 'Event Title' ?></p>
                    <p class="text-xs text-gray-500 mb-3"><?= e(post('category') ?: 'Category') ?></p>
                    <div class="flex flex-wrap gap-4 text-xs text-gray-500 mb-3">
                        <span class="inline-flex items-center gap-1"><?= icon('calendar', 'w-3 h-3') ?> <?= e(post('date') ?: 'Date') ?></span>
                        <span class="inline-flex items-center gap-1"><?= icon('clock', 'w-3 h-3') ?> <?= e(post('start_time') ?: 'Time') ?></span>
                        <span class="inline-flex items-center gap-1"><?= icon('map-pin', 'w-3 h-3') ?> <?= e(post('venue') ?: 'Venue') ?></span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Capacity: <?= e(post('capacity', '0')) ?> participants</p>
                    <p class="text-sm text-gray-500 line-clamp-2"><?= e(post('description')) ?: 'Event description will appear here.' ?></p>
                </div>
            </section>

            <div class="flex gap-3">
                <button type="submit" name="status" value="draft" class="btn-secondary">Save Draft</button>
                <button type="submit" name="status" value="published" class="btn-primary">Publish Event</button>
            </div>
        </form>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>

<script>
function toggleQuestionOptions(row) {
    var sel = row.querySelector('select[name="questions[type][]"]');
    var opts = row.querySelector('.question-options');
    if (!sel || !opts) return;
    var show = sel.value === 'dropdown' || sel.value === 'checkbox';
    opts.classList.toggle('hidden', !show);
    var input = opts.querySelector('input');
    if (input) input.disabled = !show;
}

document.addEventListener('DOMContentLoaded', function() {
    var capInput = document.getElementById('capacityInput');
    var capDisplay = document.getElementById('capDisplay');
    if (capInput && capDisplay) {
        capInput.addEventListener('input', function() { capDisplay.textContent = this.value || '0'; });
    }
    var container = document.getElementById('questionsContainer');
    container.addEventListener('change', function(e) {
        if (e.target.matches('select[name="questions[type][]"]')) {
            toggleQuestionOptions(e.target.closest('.question-row'));
        }
    });
    document.getElementById('addQuestion').addEventListener('click', function() {
        var first = container.querySelector('.question-row');
        var clone = first.cloneNode(true);
        clone.querySelectorAll('input, select').forEach(function(el) {
            if (el.type === 'checkbox') { el.checked = false; } else { el.value = ''; }
        });
        container.appendChild(clone);
        toggleQuestionOptions(clone);
    });
    container.querySelectorAll('.question-row').forEach(toggleQuestionOptions);
});
</script>
