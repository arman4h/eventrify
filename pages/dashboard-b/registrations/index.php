<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('registrations');

$pageTitle = 'Registrations';
$activePage = 'registrations';

$clubId = (int) currentUser()['club_id'];
$filterEventId = (int) get('event_id', 0);
$search = get('q');
$page = max(1, (int) get('page', 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

if (isPost()) {
    $action = post('action');
    $regId = (int) post('registration_id');

    if ($regId > 0) {
        $ownerStmt = $db->prepare("SELECT er.*, e.title AS event_title FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE er.registration_id = ? AND e.club_id = ?");
        $ownerStmt->bind_param('ii', $regId, $clubId);
        $ownerStmt->execute();
        $owner = $ownerStmt->get_result()->fetch_assoc();

        if ($owner) {
            if ($action === 'checkin' && $owner['status'] !== 'attended') {
                $upd = $db->prepare("UPDATE event_registrations SET status = 'attended', checked_in_at = NOW(), check_in_method = 'manual', checked_in_by = ? WHERE registration_id = ?");
                $upd->bind_param('ii', currentUserId(), $regId);
                $upd->execute();
                $_SESSION['flash']['success'] = ($owner['guest_name'] ?: 'Participant') . ' checked in.';
            } elseif ($action === 'checkin' && $owner['status'] === 'attended') {
                $_SESSION['flash']['info'] = 'Already checked in.';
            } elseif ($action === 'delete') {
                $del = $db->prepare("DELETE FROM event_registrations WHERE registration_id = ? AND event_id IN (SELECT event_id FROM events WHERE club_id = ?)");
                $del->bind_param('ii', $regId, $clubId);
                $del->execute();
                $_SESSION['flash']['success'] = 'Registration deleted.';
            }
        }
    }

    $qs = [];
    if ($filterEventId > 0) $qs['event_id'] = $filterEventId;
    if ($search !== '') $qs['q'] = $search;
    if ($page > 1) $qs['page'] = $page;
    redirect('/club/registrations' . (!empty($qs) ? '?' . http_build_query($qs) : ''));
}

$baseQuery = "FROM event_registrations er JOIN events e ON e.event_id = er.event_id WHERE e.club_id = ?";
$countQuery = "SELECT COUNT(*) as c $baseQuery";
$dataQuery = "SELECT er.*, e.title as event_title $baseQuery";

$params = [$clubId];
$types = 'i';

if ($filterEventId > 0) {
    $extra = " AND er.event_id = ?";
    $countQuery .= $extra;
    $dataQuery .= $extra;
    $params[] = $filterEventId;
    $types .= 'i';
}

if ($search !== '') {
    $extra = " AND (er.guest_name LIKE ? OR er.guest_student_id LIKE ? OR e.title LIKE ?)";
    $countQuery .= $extra;
    $dataQuery .= $extra;
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$countStmt = $db->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = (int) $countStmt->get_result()->fetch_assoc()['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$dataQuery .= " ORDER BY er.registered_at DESC LIMIT $perPage OFFSET $offset";
$dataStmt = $db->prepare($dataQuery);
$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$registrations = $dataStmt->get_result();

$eventsList = $db->prepare("SELECT event_id, title FROM events WHERE club_id = ? ORDER BY start_time DESC");
$eventsList->bind_param('i', $clubId);
$eventsList->execute();
$eventsResult = $eventsList->get_result();

$baseUrl = url('/club/registrations');
$queryParams = [];
if ($filterEventId > 0) $queryParams['event_id'] = $filterEventId;
if ($search !== '') $queryParams['q'] = $search;
if (!empty($queryParams)) $baseUrl .= '?' . http_build_query($queryParams);

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

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Registrations</h2>
                <p class="page-subtitle">Manage event registrations and participants</p>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <form method="GET" action="<?= url('/club/registrations') ?>" class="flex flex-wrap gap-3">
                    <select name="event_id" class="select max-w-xs">
                        <option value="">All Events</option>
                        <?php while ($ev = $eventsResult->fetch_assoc()): ?>
                            <option value="<?= $ev['event_id'] ?>" <?= $filterEventId === (int) $ev['event_id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <div class="relative flex-1 max-w-sm">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><?= icon('search', 'w-4 h-4') ?></span>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name or ID..." class="input pl-10">
                    </div>
                    <button type="submit" class="btn-secondary btn-sm">Filter</button>
                    <?php if ($search !== '' || $filterEventId > 0): ?>
                        <a href="<?= url('/club/registrations') ?>" class="btn-ghost btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($registrations->num_rows === 0): ?>
                <?php
                $emptyIcon = 'clipboard';
                $emptyTitle = 'No registrations found';
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
                                <th>Event</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th class="whitespace-nowrap text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reg = $registrations->fetch_assoc()): ?>
                            <tr>
                                <td class="font-medium text-gray-900"><?= e($reg['guest_name'] ?: '—') ?></td>
                                <td class="text-sm text-gray-600"><?= e($reg['guest_student_id'] ?: '—') ?></td>
                                <td class="text-sm text-gray-600"><?= e($reg['event_title']) ?></td>
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
                                <td class="text-sm text-gray-600">
                                    <?php if ($reg['is_walkin']): ?>
                                        <span class="badge-warning">Walk-in</span>
                                    <?php else: ?>
                                        Online
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap">
                                    <div class="flex gap-2 justify-end">
                                        <button type="button" class="btn-secondary btn-sm" data-modal-open="checkinModal-<?= (int) $reg['registration_id'] ?>" <?= $reg['status'] === 'attended' ? 'disabled' : '' ?>>
                                            <?= icon('check-circle', 'w-4 h-4') ?> Check In
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="registration_id" value="<?= (int) $reg['registration_id'] ?>">
                                            <button type="submit" class="btn-danger btn-sm" data-confirm="Delete this registration? This cannot be undone.">
                                                <?= icon('trash', 'w-4 h-4') ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                $totalPages = $totalPages;
                $currentPage = $page;
                require BASE_PATH . '/app/components/pagination.php';
                ?>
            <?php endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>

<?php
$registrations->data_seek(0);
while ($reg = $registrations->fetch_assoc()):
    $modalId = 'checkinModal-' . (int) $reg['registration_id'];
    $modalTitle = 'Check in Attendance';
    ob_start();
    $name = $reg['guest_name'] ?: 'Unnamed participant';
    $rs = $reg['status'];
?>
<div class="space-y-2 text-sm mb-4">
    <div class="flex justify-between">
        <span class="text-gray-500">Participant</span>
        <span class="font-medium text-gray-900"><?= e($name) ?></span>
    </div>
    <div class="flex justify-between">
        <span class="text-gray-500">Student ID</span>
        <span class="font-medium text-gray-900"><?= e($reg['guest_student_id'] ?: '—') ?></span>
    </div>
    <div class="flex justify-between">
        <span class="text-gray-500">Event</span>
        <span class="font-medium text-gray-900"><?= e($reg['event_title']) ?></span>
    </div>
    <div class="flex justify-between">
        <span class="text-gray-500">Status</span>
        <span class="badge-info"><?= ucfirst(e($rs)) ?></span>
    </div>
</div>
<?php if ($rs === 'attended'): ?>
    <div class="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 rounded-lg p-3">
        <?= icon('check-circle', 'w-5 h-5') ?> This participant has already been checked in.
    </div>
<?php else: ?>
    <form method="POST">
        <input type="hidden" name="action" value="checkin">
        <input type="hidden" name="registration_id" value="<?= (int) $reg['registration_id'] ?>">
        <div class="flex gap-3 justify-end">
            <button type="button" class="btn-secondary" data-modal-dismiss="<?= e($modalId) ?>">Cancel</button>
            <button type="submit" class="btn-success">Confirm Check In</button>
        </div>
    </form>
<?php endif; ?>
<?php
    $modalBody = ob_get_clean();
    require BASE_PATH . '/app/components/modal.php';
endwhile;
?>
