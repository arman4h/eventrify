<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireStudent();

$studentId = (int) currentUserId();
$student   = currentUser();

$pageTitle  = 'Report a Problem';
$activePage = 'reports';

$errors = [];

// ── Pre-selection (from the event page or a direct link) ──────────────
$preselectEventId = (int) get('event_id');
$preselectClubId  = (int) get('club_id');

$events = reportableEventsForStudent($studentId);
$clubs  = reportableClubs();

// A target must exist and be something this student can actually report on.
$eventOptions = [];
foreach ($events as $ev) {
    $eventOptions[$ev['event_id']] = $ev;
}
$clubOptions = [];
foreach ($clubs as $c) {
    $clubOptions[$c['club_id']] = $c;
}

if ($preselectEventId > 0 && !isset($eventOptions[$preselectEventId])) {
    $preselectEventId = 0;
    flash('error', 'That event is not available to report on.');
}
if ($preselectClubId > 0 && !isset($clubOptions[$preselectClubId])) {
    $preselectClubId = 0;
}

// Picking an event implies its club.
if ($preselectEventId > 0 && $preselectClubId === 0) {
    $contact       = clubContactForReport($preselectEventId);
    $preselectClubId = $contact['club_id'] ?? 0;
}

$form = [
    'event_id'    => (string) ($preselectEventId ?: ''),
    'club_id'     => (string) ($preselectClubId ?: ''),
    'subject'     => '',
    'description' => '',
];

// ── Submit ────────────────────────────────────────────────────────────
if (isPost()) {
    $form['event_id']    = post('event_id');
    $form['club_id']     = post('club_id');
    $form['subject']     = post('subject');
    $form['description'] = post('description');

    $eventId = (int) $form['event_id'];
    $clubId  = (int) $form['club_id'];

    if ($eventId > 0 && !isset($eventOptions[$eventId])) {
        $errors[] = 'Please choose an event from the list.';
        $eventId  = 0;
    }
    if ($clubId > 0 && !isset($clubOptions[$clubId])) {
        $errors[] = 'Please choose a club from the list.';
        $clubId   = 0;
    }

    // An event always determines the club; keep the stored pair consistent.
    if ($eventId > 0) {
        $linked  = clubContactForReport($eventId);
        $clubId  = $linked['club_id'] ?? 0;
    }

    $errors = array_merge($errors, validateReportSubmission($form, $studentId, $eventId, $clubId));

    if (count($errors) === 0) {
        $reportId = storeReport(
            $studentId,
            $eventId,
            $clubId,
            trim($form['subject']),
            trim($form['description'])
        );

        if ($reportId > 0) {
            flash('success', 'Report #' . str_pad((string) $reportId, 5, '0', STR_PAD_LEFT)
                . ' was sent to the system administrator. We will review it and update you here.');
            redirect('/student/reports?view=' . $reportId);
        }

        $errors[] = 'Something went wrong while saving your report. Please try again.';
    }
}

// ── Step 1: who to contact directly ───────────────────────────────────
$contact = null;
if ((int) $form['event_id'] > 0) {
    $contact = clubContactForReport((int) $form['event_id']);
} elseif ((int) $form['club_id'] > 0) {
    $contact = clubContactForReport(0, (int) $form['club_id']);
}

$contactSubject = 'Eventrify: ' . ($contact['name'] ?? 'your club') . ' — query about an event';
$contactBody    = "Hello,\n\nI have a question / problem regarding "
    . ($contact['name'] ?? 'your event') . " on Eventrify.\n\n"
    . "What I need help with:\n\n"
    . "My student ID: " . ($student['university_id'] ?? '') . "\n\n"
    . "Thank you.";

$chips = reportReasonChips();
$openReports = openReportCountForStudent($studentId);
$atLimit = $openReports >= REPORT_MAX_OPEN_PER_STUDENT;

require BASE_PATH . '/app/layouts/dashboard-s/header.php';
require BASE_PATH . '/app/layouts/dashboard-s/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-s/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">Report a Problem</h1>
                <p class="page-subtitle">Tell us what went wrong and we will get it looked at.</p>
            </div>
            <a href="<?= url('/student/reports') ?>" class="btn-secondary btn-sm">
                <?= icon('clipboard', 'w-4 h-4') ?>
                My Reports
            </a>
        </div>

        <?php
        $alertType = 'success';
        $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error';
        $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <?php if (count($errors) > 0): ?>
        <div class="alert-error mb-6">
            <p class="font-semibold mb-2">Please fix the following before sending:</p>
            <ul class="list-disc pl-5 space-y-1 text-sm">
                <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($atLimit): ?>
        <div class="alert-warning mb-6 flex items-start gap-3">
            <?= icon('info', 'w-5 h-5 mt-0.5 shrink-0') ?>
            <div>
                <p class="font-semibold">You have <?= (int) $openReports ?> reports waiting for a response.</p>
                <p class="text-sm mt-1">The administration reviews reports one at a time. Please give them a chance to respond
                    before sending more — you can follow the status of each one on the
                    <a href="<?= url('/student/reports') ?>" class="underline font-medium">My Reports</a> page.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- ── Step 1 ─────────────────────────────────────── -->
            <div class="lg:col-span-1 space-y-6 lg:sticky lg:top-6">
                <div class="card p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 items-center justify-center font-bold text-sm shrink-0">1</span>
                        <h2 class="font-semibold text-gray-900">Contact the club first</h2>
                    </div>

                    <p class="text-sm text-gray-600 leading-relaxed mb-4">
                        The club organises the event, so they can usually sort it out fastest. Try them before
                        escalating to the administration.
                    </p>

                    <?php if ($contact): ?>
                        <div class="rounded-lg border border-gray-200 p-4 bg-gray-50">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Organiser</p>
                            <p class="font-semibold text-gray-900"><?= e($contact['name']) ?></p>

                            <?php if ($contact['email'] !== ''): ?>
                            <p class="text-sm text-gray-600 mt-2 break-all"><?= e($contact['email']) ?></p>
                            <a href="<?= e(reportMailtoLink($contact['email'], $contactSubject, $contactBody)) ?>"
                               class="btn-primary btn-sm w-full mt-3">
                                <?= icon('mail', 'w-4 h-4') ?>
                                Email the club
                            </a>
                            <button type="button" class="btn-secondary btn-sm w-full mt-2" data-copy="<?= e($contact['email']) ?>">
                                <?= icon('check', 'w-4 h-4') ?>
                                Copy email address
                            </button>
                            <?php else: ?>
                            <p class="text-sm text-amber-700 mt-2">This club has not published an email address yet.</p>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-3 mt-3 pt-3 border-t border-gray-200 text-sm">
                                <?php if ($contact['website'] !== ''): ?>
                                <a href="<?= e($contact['website']) ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">Website</a>
                                <?php endif; ?>
                                <?php if ($contact['facebook'] !== ''): ?>
                                <a href="<?= e($contact['facebook']) ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">Facebook</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center">
                            <span class="empty-state-icon mx-auto"><?= icon('mail', 'w-5 h-5 text-gray-400') ?></span>
                            <p class="text-sm text-gray-500">Choose the event or club on the right and their contact
                                details will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex w-8 h-8 rounded-full bg-blue-100 text-blue-700 items-center justify-center font-bold text-sm shrink-0">2</span>
                        <h2 class="font-semibold text-gray-900">Still not fixed?</h2>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        If the club could not help, or did not reply, send the problem to the
                        <strong class="text-gray-900">system administrator</strong>. Your report goes straight into the
                        administration queue and you can track its status here.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 mt-0.5"><?= icon('check', 'w-3.5 h-3.5') ?></span>
                            Reviewed by the system administrator
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 mt-0.5"><?= icon('check', 'w-3.5 h-3.5') ?></span>
                            The club is notified through its dashboard
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 mt-0.5"><?= icon('check', 'w-3.5 h-3.5') ?></span>
                            You can see the status change on My Reports
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ── Step 2: the form ────────────────────────────── -->
            <div class="lg:col-span-2">
                <form method="POST" action="<?= url('/student/report') ?>" class="card p-6 space-y-5" id="reportForm">
                    <?= csrfField() ?>

                    <div>
                        <h2 class="font-semibold text-gray-900">Send a report to the administrator</h2>
                        <p class="text-sm text-gray-500 mt-1">Fields marked <span class="text-red-500">*</span> are required.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="event_id" class="label">Which event?</label>
                            <select name="event_id" id="reportEvent" class="select">
                                <option value="">Not about a specific event</option>
                                <?php foreach ($events as $ev): ?>
                                    <option value="<?= (int) $ev['event_id'] ?>"
                                        <?= (string) $form['event_id'] === (string) $ev['event_id'] ? 'selected' : '' ?>>
                                        <?= $ev['registered'] ? '★ ' : '' ?><?= e($ev['title']) ?> — <?= e($ev['club_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-hint">★ marks events you are registered for.</p>
                        </div>

                        <div class="form-group">
                            <label for="club_id" class="label">Which club?</label>
                            <select name="club_id" id="reportClub" class="select">
                                <option value="">Not about a specific club</option>
                                <?php foreach ($clubs as $c): ?>
                                    <option value="<?= (int) $c['club_id'] ?>"
                                        <?= (string) $form['club_id'] === (string) $c['club_id'] ? 'selected' : '' ?>>
                                        <?= e($c['club_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-hint">Filled in automatically when you pick an event.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <span class="label">Common reasons</span>
                        <div class="flex flex-wrap gap-2" id="reasonChips">
                            <?php foreach ($chips as $chip): ?>
                            <button type="button" class="badge-neutral hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200 transition-colors cursor-pointer"
                                    data-subject="<?= e($chip) ?>"><?= e($chip) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <p class="form-hint">Tap one to fill in the subject. You can edit it afterwards.</p>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="label label-required">Subject</label>
                        <input type="text" name="subject" id="subject" class="input" required
                               minlength="<?= REPORT_SUBJECT_MIN ?>" maxlength="<?= REPORT_SUBJECT_MAX ?>"
                               placeholder="Short summary of the problem"
                               value="<?= e($form['subject']) ?>">
                        <p class="form-hint">Between <?= REPORT_SUBJECT_MIN ?> and <?= REPORT_SUBJECT_MAX ?> characters.</p>
                    </div>

                    <div class="form-group">
                        <label for="description" class="label label-required">What happened?</label>
                        <textarea name="description" id="description" rows="6" class="textarea" required
                                  minlength="<?= REPORT_DESCRIPTION_MIN ?>" maxlength="<?= REPORT_DESCRIPTION_MAX ?>"
                                  placeholder="Describe the problem in detail — dates, times, what you expected, and what actually happened. The more specific you are, the faster it can be resolved."><?= e($form['description']) ?></textarea>
                        <p class="form-hint flex items-center justify-between gap-2">
                            <span>At least <?= REPORT_DESCRIPTION_MIN ?> characters so we have enough to act on.</span>
                            <span id="descCount" class="font-medium text-gray-600">0 / <?= REPORT_DESCRIPTION_MAX ?></span>
                        </p>
                    </div>

                    <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800 flex items-start gap-3">
                        <?= icon('info', 'w-5 h-5 mt-0.5 shrink-0') ?>
                        <p>Your name, university ID and the event you picked are shared with the system administrator
                            and the club concerned so the issue can be investigated. Please do not include
                            anyone else's personal details.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-1">
                        <button type="submit" class="btn-primary btn-lg" <?= $atLimit ? 'disabled' : '' ?>>
                            <?= icon('check-circle', 'w-5 h-5') ?>
                            Send report to administrator
                        </button>
                        <a href="<?= url('/events') ?>" class="btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-s/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var eventSelect  = document.getElementById('reportEvent');
    var clubSelect   = document.getElementById('reportClub');
    var subjectInput = document.getElementById('subject');
    var descInput    = document.getElementById('description');
    var descCount    = document.getElementById('descCount');
    var maxDesc      = <?= REPORT_DESCRIPTION_MAX ?>;

    // Events carry their club, so keep the two selects consistent.
    function eventToClub() {
        if (!eventSelect) return;
        var option = eventSelect.options[eventSelect.selectedIndex];
        if (!option || !option.value) return;
        var text = option.textContent || '';
        var dash = text.lastIndexOf('—');
        if (dash === -1) return;
        var clubName = text.substring(dash + 1).trim();
        if (!clubSelect) return;
        for (var i = 0; i < clubSelect.options.length; i++) {
            if (clubSelect.options[i].text.trim() === clubName) {
                clubSelect.value = clubSelect.options[i].value;
                return;
            }
        }
    }

    if (eventSelect) {
        eventSelect.addEventListener('change', eventToClub);
    }

    // Reason chips fill the subject only when the student has not typed one.
    var chips = document.querySelectorAll('#reasonChips [data-subject]');
    Array.prototype.forEach.call(chips, function (chip) {
        chip.addEventListener('click', function () {
            if (subjectInput && subjectInput.value.trim() === '') {
                subjectInput.value = chip.getAttribute('data-subject') || '';
                subjectInput.focus();
            }
        });
    });

    function updateCount() {
        if (!descInput || !descCount) return;
        var n = descInput.value.length;
        descCount.textContent = n + ' / ' + maxDesc;
        descCount.className = 'font-medium ' + (n > maxDesc ? 'text-red-600' : 'text-gray-600');
    }

    if (descInput) {
        descInput.addEventListener('input', updateCount);
        updateCount();
    }
});
</script>
