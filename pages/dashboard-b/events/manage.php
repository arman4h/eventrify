<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('events');

$pageTitle = 'Manage Event';
$activePage = 'events';

$clubId = (int) currentUser()['club_id'];
$eventId = (int) get('event_id');

$stmt = $db->prepare("SELECT * FROM events WHERE event_id = ? AND club_id = ?");
$stmt->bind_param('ii', $eventId, $clubId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    abort(404, 'Event not found');
}

if (isPost()) {
    $action = post('action');

    if ($action === 'cancel') {
        $stmt = $db->prepare("UPDATE events SET status = 'cancelled' WHERE event_id = ? AND club_id = ?");
        $stmt->bind_param('ii', $eventId, $clubId);
        $stmt->execute();
        $_SESSION['flash']['success'] = 'Event cancelled.';
        redirect('/club/events/manage?event_id=' . $eventId);
    } elseif ($action === 'duplicate') {
        $stmt = $db->prepare("SELECT * FROM events WHERE event_id = ? AND club_id = ?");
        $stmt->bind_param('ii', $eventId, $clubId);
        $stmt->execute();
        $orig = $stmt->get_result()->fetch_assoc();
        if ($orig) {
            $newStmt = $db->prepare("INSERT INTO events (club_id, title, description, category, poster, venue, start_time, end_time, registration_deadline, capacity, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)");
            $newStmt->bind_param('issssssssii', $clubId, $orig['title'], $orig['description'], $orig['category'], $orig['poster'], $orig['venue'], $orig['start_time'], $orig['end_time'], $orig['registration_deadline'], $orig['capacity'], currentUserId());
            if ($newStmt->execute()) {
                $newEventId = $newStmt->insert_id;

                $fieldSrc = $db->prepare("SELECT field_label, field_type, field_options, is_required, display_order FROM event_registration_fields WHERE event_id = ? ORDER BY display_order");
                $fieldSrc->bind_param('i', $eventId);
                $fieldSrc->execute();
                $fieldRows = $fieldSrc->get_result();

                $fieldDst = $db->prepare("INSERT INTO event_registration_fields (event_id, field_label, field_type, field_options, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                while ($f = $fieldRows->fetch_assoc()) {
                    $fieldDst->bind_param('isssii', $newEventId, $f['field_label'], $f['field_type'], $f['field_options'], $f['is_required'], $f['display_order']);
                    $fieldDst->execute();
                }

                $_SESSION['flash']['success'] = 'Event duplicated as draft.';
                redirect('/club/events/edit?event_id=' . $newEventId);
            }
        }
        $_SESSION['flash']['error'] = 'Failed to duplicate event.';
        redirect('/club/events/manage?event_id=' . $eventId);
    }
}

$regCount = $db->prepare("SELECT COUNT(*) as c FROM event_registrations WHERE event_id = ?");
$regCount->bind_param('i', $eventId);
$regCount->execute();
$totalRegistered = (int) $regCount->get_result()->fetch_assoc()['c'];

$waitCount = $db->prepare("SELECT COUNT(*) as c FROM event_registrations WHERE event_id = ? AND status = 'waitlisted'");
$waitCount->bind_param('i', $eventId);
$waitCount->execute();
$totalWaitlisted = (int) $waitCount->get_result()->fetch_assoc()['c'];

$attendedCount = $db->prepare("SELECT COUNT(*) as c FROM event_registrations WHERE event_id = ? AND status = 'attended'");
$attendedCount->bind_param('i', $eventId);
$attendedCount->execute();
$totalAttended = (int) $attendedCount->get_result()->fetch_assoc()['c'];

$regList = $db->prepare("SELECT er.*, er.guest_name as participant_name, er.guest_student_id as student_id FROM event_registrations er WHERE er.event_id = ? ORDER BY er.registered_at DESC");
$regList->bind_param('i', $eventId);
$regList->execute();
$registrations = $regList->get_result();

$fieldsList = $db->prepare("SELECT * FROM event_registration_fields WHERE event_id = ? ORDER BY display_order");
$fieldsList->bind_param('i', $eventId);
$fieldsList->execute();
$fields = $fieldsList->get_result();

$activeTab = get('tab', 'overview');

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
            <a href="<?= url('/club/events') ?>" class="breadcrumb-link">Events</a>
            <span class="text-gray-400">/</span>
            <span class="breadcrumb-current"><?= e($event['title']) ?></span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="page-title"><?= e($event['title']) ?></h2>
                    <?php
                    $status = $event['status'] ?? 'draft';
                    $badge = match($status) {
                        'draft' => 'badge-neutral',
                        'published' => 'badge-success',
                        'cancelled' => 'badge-danger',
                        'completed' => 'badge-info',
                        default => 'badge-neutral',
                    };
                    ?>
                    <span class="<?= $badge ?>"><?= ucfirst(e($status)) ?></span>
                </div>
                <p class="page-subtitle"><?= formatDate($event['start_time'], 'M d, Y') ?></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?= url('/club/events/edit?event_id=' . $eventId) ?>" class="btn-secondary btn-sm">
                    <?= icon('edit', 'w-4 h-4') ?> Edit Event
                </a>
                <button type="button" class="btn-ghost btn-sm" data-copy="<?= url('/event?event_id=' . $eventId) ?>">
                    <?= icon('share', 'w-4 h-4') ?> Share
                </button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="duplicate">
                    <button type="submit" class="btn-secondary btn-sm">
                        <?= icon('copy', 'w-4 h-4') ?> Duplicate
                    </button>
                </form>
                <?php if ($status !== 'cancelled'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn-danger btn-sm" data-confirm="Cancel this event?">
                            <?= icon('x', 'w-4 h-4') ?> Cancel Event
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="stat-card">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <?= icon('users', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Capacity</p>
                        <p class="stat-value"><?= (int) $event['capacity'] ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <?= icon('check-circle', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Registered</p>
                        <p class="stat-value"><?= $totalRegistered ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <?= icon('clock', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Waitlisted</p>
                        <p class="stat-value"><?= $totalWaitlisted ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <?= icon('check', 'w-5 h-5') ?>
                    </div>
                    <div>
                        <p class="stat-label">Checked In</p>
                        <p class="stat-value"><?= $totalAttended ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tabs mb-6">
            <button type="button" class="tab <?= $activeTab === 'overview' ? 'tab-active' : '' ?>" data-tab="overview" data-tab-group="manage">Overview</button>
            <button type="button" class="tab <?= $activeTab === 'registrations' ? 'tab-active' : '' ?>" data-tab="registrations" data-tab-group="manage">Registrations</button>
            <button type="button" class="tab <?= $activeTab === 'attendance' ? 'tab-active' : '' ?>" data-tab="attendance" data-tab-group="manage">Attendance</button>
            <button type="button" class="tab <?= $activeTab === 'form' ? 'tab-active' : '' ?>" data-tab="form" data-tab-group="manage">Form</button>
            <button type="button" class="tab <?= $activeTab === 'reports' ? 'tab-active' : '' ?>" data-tab="reports" data-tab-group="manage">Reports</button>
        </div>

        <div data-tab-panel="overview" data-tab-panel-group="manage" class="<?= $activeTab !== 'overview' ? 'hidden' : '' ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">About</h3>
                    <?php if (!empty($event['poster'])): ?>
                        <img src="<?= e($event['poster']) ?>" class="w-full h-48 object-cover rounded-lg border border-gray-200 mb-4">
                    <?php endif; ?>
                    <p class="text-sm text-gray-600"><?= nl2br(e($event['description'] ?: 'No description provided.')) ?></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <?php if ($event['category']): ?>
                            <span class="badge-primary"><?= e($event['category']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Schedule & Location</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <?= icon('calendar', 'w-4 h-4 text-gray-400') ?>
                            <span class="text-gray-600"><?= formatDate($event['start_time'], 'l, M d, Y') ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <?= icon('clock', 'w-4 h-4 text-gray-400') ?>
                            <span class="text-gray-600"><?= formatDate($event['start_time'], 'g:i A') ?><?= $event['end_time'] ? ' — ' . formatDate($event['end_time'], 'g:i A') : '' ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <?= icon('map-pin', 'w-4 h-4 text-gray-400') ?>
                            <span class="text-gray-600"><?= e($event['venue']) ?></span>
                        </div>
                        <?php if ($event['registration_deadline']): ?>
                        <div class="flex items-center gap-3">
                            <?= icon('ticket', 'w-4 h-4 text-gray-400') ?>
                            <span class="text-gray-600">Deadline: <?= formatDate($event['registration_deadline'], 'M d, Y g:i A') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600"><?= $totalRegistered ?> / <?= (int) $event['capacity'] ?> seats filled</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $event['capacity'] > 0 ? min(100, round(($totalRegistered / $event['capacity']) * 100)) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div data-tab-panel="registrations" data-tab-panel-group="manage" class="<?= $activeTab !== 'registrations' ? 'hidden' : '' ?>">
            <div class="card overflow-hidden">
                <?php if ($registrations->num_rows === 0): ?>
                    <?php
                    $emptyIcon = 'users';
                    $emptyTitle = 'No registrations yet';
                    $emptyText = 'Registrations will appear here once participants sign up.';
                    require BASE_PATH . '/app/components/empty-state.php';
                    ?>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Participant</th>
                                    <th>Student ID</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($reg = $registrations->fetch_assoc()): ?>
                                <tr>
                                    <td class="font-medium text-gray-900"><?= e($reg['participant_name'] ?: '—') ?></td>
                                    <td class="text-sm text-gray-600"><?= e($reg['student_id'] ?: '—') ?></td>
                                    <td class="whitespace-nowrap text-sm text-gray-600"><?= formatDate($reg['registered_at']) ?></td>
                                    <td>
                                        <?php
                                        $rs = $reg['status'];
                                        $rb = match($rs) {
                                            'registered' => 'badge-info',
                                            'waitlisted' => 'badge-warning',
                                            'attended' => 'badge-success',
                                            'cancelled' => 'badge-danger',
                                            'no_show' => 'badge-neutral',
                                            default => 'badge-neutral',
                                        };
                                        ?>
                                        <span class="<?= $rb ?>"><?= ucfirst(e($rs)) ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= url('/club/registrations?event_id=' . $eventId) ?>" class="btn-ghost btn-sm">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div data-tab-panel="attendance" data-tab-panel-group="manage" class="<?= $activeTab !== 'attendance' ? 'hidden' : '' ?>">
            <div class="card overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Attendance Status</h3>
                    <a href="<?= url('/club/attendance?event_id=' . $eventId) ?>" class="btn-primary btn-sm">
                        <?= icon('qr', 'w-4 h-4') ?> Go to Check-in
                    </a>
                </div>
                <?php if ($registrations->num_rows === 0): ?>
                    <p class="text-sm text-gray-500 text-center py-8">No registrations to track attendance for.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Participant</th>
                                    <th>Status</th>
                                    <th>Checked In</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $registrations->data_seek(0);
                                while ($reg = $registrations->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="font-medium text-gray-900"><?= e($reg['participant_name'] ?: '—') ?></td>
                                    <td>
                                        <?php
                                        $rs = $reg['status'];
                                        $rb = match($rs) {
                                            'registered' => 'badge-info',
                                            'waitlisted' => 'badge-warning',
                                            'attended' => 'badge-success',
                                            'cancelled' => 'badge-danger',
                                            'no_show' => 'badge-neutral',
                                            default => 'badge-neutral',
                                        };
                                        ?>
                                        <span class="<?= $rb ?>"><?= ucfirst(e($rs)) ?></span>
                                    </td>
                                    <td class="text-sm text-gray-600">
                                        <?= $reg['checked_in_at'] ? formatDate($reg['checked_in_at'], 'M d, Y g:i A') : '—' ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div data-tab-panel="form" data-tab-panel-group="manage" class="<?= $activeTab !== 'form' ? 'hidden' : '' ?>">
            <div class="card p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Registration Form Fields</h3>
                <?php if ($fields->num_rows === 0): ?>
                    <p class="text-sm text-gray-500">No custom registration fields configured for this event.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($field = $fields->fetch_assoc()): ?>
                                <tr>
                                    <td class="font-medium text-gray-900"><?= e($field['field_label']) ?></td>
                                    <td class="text-sm text-gray-600 capitalize"><?= e($field['field_type']) ?></td>
                                    <td>
                                        <?php if ($field['is_required']): ?>
                                            <span class="badge-danger">Required</span>
                                        <?php else: ?>
                                            <span class="badge-neutral">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-sm text-gray-600"><?= e($field['field_options'] ?: '—') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-4">Edit custom fields in the Create/Edit event form under the Registration Form step.</p>
            </div>
        </div>

        <div data-tab-panel="reports" data-tab-panel-group="manage" class="<?= $activeTab !== 'reports' ? 'hidden' : '' ?>">
            <div class="card p-6">
                <div class="text-center py-8">
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                        <?= icon('file', 'w-6 h-6') ?>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Event Reports</h3>
                    <p class="text-sm text-gray-500 mb-4">View detailed analytics for this event.</p>
                    <a href="<?= url('/club/reports') ?>" class="btn-primary btn-sm">
                        <?= icon('trending', 'w-4 h-4') ?> Visit Reports
                    </a>
                </div>
            </div>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
