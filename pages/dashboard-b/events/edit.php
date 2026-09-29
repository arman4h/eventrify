<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('events');

// A view-only executive may not open a write form at all.
if (!clubCanManage('events')) {
    $_SESSION['flash']['error'] = 'You have view-only access to this section.';
    redirect('/club');
}

$pageTitle = 'Edit Event';
$activePage = 'events';

$eventId = (int) get('event_id');
$clubId = (int) currentUser()['club_id'];
$stmt = $db->prepare("SELECT * FROM events WHERE event_id = ? AND club_id = ?");
$stmt->bind_param('ii', $eventId, $clubId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    abort(404, 'Event not found');
}

$errors = [];

if (isPost()) {
    // View-only executives must not be able to write, even by hand-crafting a POST.
    requireClubManage('events');

    $title = post('title');
    $description = post('description');
    $category = post('category');
    $date = post('date');
    $startTime = post('start_time');
    $endTime = post('end_time');
    $endDate = post('end_date');
    $venue = post('venue');
    $capacity = (int) post('capacity');
    $registrationDeadline = post('registration_deadline');
    $status = post('status');

    $allowed = ['draft', 'published', 'cancelled', 'completed'];
    if (!in_array($status, $allowed, true)) {
        $status = 'draft';
    }

    $startDateTime = ($date !== '' && $startTime !== '') ? "$date $startTime:00" : '';
    // An end date is only needed for an event that finishes after midnight, so it
    // falls back to the event date. Without this an overnight event could not be
    // saved at all, and a two-day event would be silently pulled back to one day.
    $endDay = ($endDate !== '' ? $endDate : $date);
    $endDateTime = ($endDay !== '' && $endTime !== '') ? "$endDay $endTime:00" : '';

    if ($title === '' || $venue === '' || $startDateTime === '') {
        $errors[] = 'Title, venue, and start time are required.';
    }
    if ($startDateTime !== '' && $endDateTime === '') {
        $errors[] = 'End time is required.';
    }

    // Match the column widths so oversized input is refused up front instead of
    // failing later as a "Data too long" error on UPDATE.
    vAdd($errors, vMaxLen($title, 150, 'Title'));
    vAdd($errors, vMaxLen($category, 50, 'Category'));
    vAdd($errors, vMaxLen($venue, 150, 'Venue'));
    vAdd($errors, vInt(post('capacity'), 'Capacity', 0, 1000000));

    $startTs = $startDateTime !== '' ? strtotime($startDateTime) : false;
    $endTs = $endDateTime !== '' ? strtotime($endDateTime) : false;
    $deadlineRaw = $registrationDeadline !== '' ? $registrationDeadline : '';

    if ($startTs !== false && $endTs !== false && $endTs <= $startTs) {
        $errors[] = 'End time must be after the start time.';
    }

    if ($deadlineRaw !== '') {
        $deadlineTs = strtotime($deadlineRaw);
        if ($deadlineTs === false) {
            $errors[] = 'Registration deadline is not a valid date and time.';
        } elseif ($startTs !== false && $deadlineTs >= $startTs) {
            $errors[] = 'Registration deadline must be before the event start date and time.';
        }
    }

    $poster = null;
    [$poster, $posterError] = readImageUpload($_FILES['poster'] ?? null, 'Poster');
    vAdd($errors, $posterError);

    if (empty($errors)) {
        if ($poster !== null) {
            $stmt = $db->prepare("
                UPDATE events
                SET title = ?, description = ?, category = ?, poster = ?, venue = ?,
                    start_time = ?, end_time = ?, registration_deadline = ?,
                    capacity = ?, status = ?
                WHERE event_id = ? AND club_id = ?
            ");
            $bindTypes = 'ssssssssisii';
        } else {
            $stmt = $db->prepare("
                UPDATE events
                SET title = ?, description = ?, category = ?, venue = ?,
                    start_time = ?, end_time = ?, registration_deadline = ?,
                    capacity = ?, status = ?
                WHERE event_id = ? AND club_id = ?
            ");
            $bindTypes = 'sssssssisii';
        }

        $end = $endDateTime !== '' ? $endDateTime : null;
        $deadline = $registrationDeadline !== '' ? $registrationDeadline : null;

        try {
            if ($poster !== null) {
                $stmt->bind_param($bindTypes, $title, $description, $category, $poster, $venue, $startDateTime, $end, $deadline, $capacity, $status, $eventId, $clubId);
            } else {
                $stmt->bind_param($bindTypes, $title, $description, $category, $venue, $startDateTime, $end, $deadline, $capacity, $status, $eventId, $clubId);
            }

            $stmt->execute();

            $delFields = $db->prepare("DELETE FROM event_registration_fields WHERE event_id = ?");
            $delFields->bind_param('i', $eventId);
            $delFields->execute();

            insertRegistrationFields($db, $eventId, $_POST['questions'] ?? []);

            $_SESSION['flash']['success'] = 'Event updated successfully.';
            redirect('/club/events');
        } catch (mysqli_sql_exception $e) {
            // A dropped connection (2006) is unrecoverable on this handle, so
            // sending the user back is the only way to stop every later query
            // in this request from failing with a fatal 500.
            error_log('Event update failed: ' . $e->getMessage());
            flash('error', dbErrorMessage($e, 'Failed to update event. Please try again.'));
            redirect('/club/events/edit?event_id=' . $eventId);
        }
    }
}

$evDate = isPost() ? post('date') : date('Y-m-d', strtotime($event['start_time']));
$evStartTime = isPost() ? post('start_time') : date('H:i', strtotime($event['start_time']));
$evEndTime = isPost() ? post('end_time') : ($event['end_time'] ? date('H:i', strtotime($event['end_time'])) : '');
$evEndDate = isPost() ? post('end_date') : ($event['end_time'] ? date('Y-m-d', strtotime($event['end_time'])) : '');
$evDeadline = isPost() ? post('registration_deadline') : ($event['registration_deadline'] ? date('Y-m-d\TH:i', strtotime($event['registration_deadline'])) : '');

$existingFields = [];
$fStmt = $db->prepare("SELECT * FROM event_registration_fields WHERE event_id = ? ORDER BY display_order");
$fStmt->bind_param('i', $eventId);
$fStmt->execute();
$fResult = $fStmt->get_result();
while ($fRow = $fResult->fetch_assoc()) {
    $existingFields[] = $fRow;
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
            <span class="breadcrumb-current">Edit Event</span>
        </nav>

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Edit Event</h2>
                <p class="page-subtitle">Update event details</p>
            </div>
        </div>

        <?php
        $flashError = flash('error');
        if ($flashError !== null) {
            $alertType = 'error';
            $alertMessage = $flashError;
            require BASE_PATH . '/app/components/alert.php';
        }
        ?>

        <?php foreach ($errors as $error): ?>
            <?php
            $alertType = 'error';
            $alertMessage = $error;
            require BASE_PATH . '/app/components/alert.php';
            ?>
        <?php endforeach; ?>

        <form method="POST" enctype="multipart/form-data" class="card p-6 sm:p-8 space-y-8 max-w-2xl">
<?= csrfField() ?>
            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Basic Information</h3>
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="label-required">Event Name</label>
                        <input type="text" name="title" class="input" value="<?= e(isPost() ? post('title') : $event['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label">Event Description</label>
                        <textarea name="description" rows="4" class="textarea"><?= e(isPost() ? post('description') : $event['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="label">Event Category</label>
                        <select name="category" class="select">
                            <option value="">Select category...</option>
                            <?php foreach (['Workshop', 'Seminar', 'Competition', 'Contest', 'Bootcamp', 'Hackathon', 'Cultural', 'Showcase', 'Sports', 'Other'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= (isPost() ? post('category') : $event['category']) === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Event Poster</label>
                        <?php if (!empty($event['poster'])): ?>
                            <div class="mb-2">
                                <img src="<?= e($event['poster']) ?>" class="h-24 rounded-lg object-cover border border-gray-200">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="poster" accept="image/*" class="input">
                        <p class="form-hint">Leave empty to keep the current poster. Accepted: JPG, PNG, WebP, GIF (max <?= e(formatBytes(imageUploadLimitBytes())) ?>).</p>
                    </div>
                </div>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Schedule</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="label-required">Date</label>
                        <input type="date" name="date" class="input" value="<?= e($evDate) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label-required">Start Time</label>
                        <input type="time" name="start_time" class="input" value="<?= e($evStartTime) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label-required">End Time</label>
                        <input type="time" name="end_time" class="input" value="<?= e($evEndTime) ?>" required>
                        <p class="form-hint">The event must end after it starts.</p>
                    </div>
                    <div class="form-group">
                        <label class="label">End Date</label>
                        <input type="date" name="end_date" class="input" value="<?= e($evEndDate) ?>">
                        <p class="form-hint">Only change this when the event finishes after midnight, such as a two-day event.</p>
                    </div>
                    <div class="form-group">
                        <label class="label">Registration Deadline</label>
                        <input type="datetime-local" name="registration_deadline" class="input" value="<?= e($evDeadline) ?>">
                        <p class="form-hint">Must be before the event start date and time.</p>
                    </div>
                </div>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Location</h3>
                <div class="form-group">
                    <label class="label-required">Venue</label>
                    <input type="text" name="venue" class="input" value="<?= e(isPost() ? post('venue') : $event['venue']) ?>" placeholder="e.g. Auditorium, Lab 203" required>
                    <p class="form-hint">Describe where the event takes place.</p>
                </div>
            </section>

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Capacity</h3>
                <div class="form-group">
                    <label class="label">Max Participants</label>
                    <input type="number" name="capacity" class="input" value="<?= isPost() ? post('capacity') : (int) $event['capacity'] ?>" min="0">
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
                        <li class="flex items-center gap-2"><?= icon('grad', 'w-4 h-4 text-blue-600') ?> Department <span class="text-xs text-gray-500">CSE, EEE, DS, English, BBA, EDS, Economics</span></li>
                    </ul>
                </div>
                <p class="text-sm text-gray-500 mb-3">Add or edit custom fields for this event.</p>
                <div id="questionsContainer" class="space-y-4">
                    <?php
                    $defaultLabels = ['Full Name', 'Email', 'Student ID', 'Department'];
                    $typeLabels = ['short_text' => 'Short Text', 'long_text' => 'Long Text', 'number' => 'Number', 'email' => 'Email', 'dropdown' => 'Dropdown', 'radio' => 'Radio', 'checkbox' => 'Checkbox', 'date' => 'Date'];
                    $storedToSelect = ['text' => 'short_text', 'number' => 'number', 'email' => 'email', 'dropdown' => 'dropdown', 'checkbox' => 'checkbox', 'date' => 'date'];
                    foreach ($existingFields as $f) {
                        if (in_array($f['field_label'], $defaultLabels, true)) continue;
                        $fieldSelect = $storedToSelect[$f['field_type']] ?? $f['field_type'];
                    ?>
                    <div class="card p-4 space-y-3 question-row">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Custom Field</span>
                            <button type="button" class="remove-field btn-danger btn-sm" title="Delete this field">
                                <?= icon('trash', 'w-4 h-4') ?> Delete
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-group">
                                <label class="label">Field Label</label>
                                <input type="text" name="questions[label][]" class="input" value="<?= e($f['field_label']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="label">Field Type</label>
                                <select name="questions[type][]" class="select">
                                    <?php foreach ($typeLabels as $tKey => $tLabel): ?>
                                        <option value="<?= $tKey ?>" <?= $fieldSelect === $tKey ? 'selected' : '' ?>><?= $tLabel ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group question-options hidden">
                            <label class="label">Options <span class="form-hint">(comma-separated, for dropdown/radio/checkbox)</span></label>
                            <input type="text" name="questions[options][]" class="input" value="<?= e($f['field_options']) ?>" placeholder="e.g. S, M, L, XL">
                        </div>
                        <label class="flex items-center gap-2">
                            <input type="hidden" name="questions[required][]" value="0">
                            <input type="checkbox" name="questions[required][]" value="1" class="rounded border-gray-300" <?= $f['is_required'] ? 'checked' : '' ?>>
                            <span class="text-sm text-gray-700">Required</span>
                        </label>
                    </div>
                    <?php } ?>
                </div>
                <button type="button" id="addQuestion" class="btn-secondary btn-sm mt-3">
                    <?= icon('plus', 'w-4 h-4') ?> Add Field
                </button>
                <template id="questionRowTemplate">
                    <div class="card p-4 space-y-3 question-row">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Custom Field</span>
                            <button type="button" class="remove-field btn-danger btn-sm" title="Delete this field">
                                <?= icon('trash', 'w-4 h-4') ?> Delete
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-group">
                                <label class="label">Field Label</label>
                                <input type="text" name="questions[label][]" class="input" placeholder="e.g. T-shirt size">
                            </div>
                            <div class="form-group">
                                <label class="label">Field Type</label>
                                <select name="questions[type][]" class="select">
                                    <option value="short_text">Short Text</option>
                                    <option value="long_text">Long Text</option>
                                    <option value="dropdown">Dropdown</option>
                                    <option value="radio">Radio</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="number">Number</option>
                                    <option value="email">Email</option>
                                    <option value="date">Date</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group question-options hidden">
                            <label class="label">Options <span class="form-hint">(comma-separated, for dropdown/radio/checkbox)</span></label>
                            <input type="text" name="questions[options][]" class="input" placeholder="e.g. S, M, L, XL">
                        </div>
                        <label class="flex items-center gap-2">
                            <input type="hidden" name="questions[required][]" value="0">
                            <input type="checkbox" name="questions[required][]" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">Required</span>
                        </label>
                    </div>
                </template>
            </section>

            <hr class="border-gray-200">

            <section>
                <h3 class="text-base font-semibold text-gray-900 mb-4">Status</h3>
                <div class="form-group">
                    <label class="label">Event Status</label>
                    <select name="status" class="select">
                        <?php foreach (['draft', 'published', 'cancelled', 'completed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $event['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </section>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Update Event</button>
                <a href="<?= url('/club/events') ?>" class="btn-secondary">Cancel</a>
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
    var show = sel.value === 'dropdown' || sel.value === 'radio' || sel.value === 'checkbox';
    opts.classList.toggle('hidden', !show);
}

document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('questionsContainer');
    var tpl = document.getElementById('questionRowTemplate');

    // Keep the browser in step with the server rules: the deadline has to sit
    // before the start, and the end date can never fall before the start date.
    var dateInput = document.querySelector('input[name="date"]');
    var startInput = document.querySelector('input[name="start_time"]');
    var endDateInput = document.querySelector('input[name="end_date"]');
    var deadlineInput = document.querySelector('input[name="registration_deadline"]');
    function syncScheduleBounds() {
        if (dateInput && startInput && deadlineInput && dateInput.value && startInput.value) {
            deadlineInput.max = dateInput.value + 'T' + startInput.value;
        }
        if (dateInput && endDateInput) {
            endDateInput.min = dateInput.value;
        }
    }
    if (dateInput) dateInput.addEventListener('change', syncScheduleBounds);
    if (startInput) startInput.addEventListener('input', syncScheduleBounds);
    syncScheduleBounds();

    function addQuestionRow() {
        var node = tpl.content.cloneNode(true);
        var row = node.querySelector('.question-row');
        toggleQuestionOptions(row);
        container.appendChild(node);
    }

    if (container && tpl) {
        container.addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-field');
            if (btn) {
                var row = btn.closest('.question-row');
                if (row) row.remove();
            }
        });
        container.addEventListener('change', function(e) {
            if (e.target.matches('select[name="questions[type][]"]')) {
                toggleQuestionOptions(e.target.closest('.question-row'));
            }
        });
        document.getElementById('addQuestion').addEventListener('click', addQuestionRow);
    }

    container.querySelectorAll('.question-row').forEach(toggleQuestionOptions);
});
</script>
