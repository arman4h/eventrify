<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'Room Requests';
$activePage = 'room-requests';

if (isPost()) {
    $requestId = (int) post('request_id');
    $action    = post('action');

    if ($requestId > 0 && in_array($action, ['approve', 'decline'], true)) {
        if ($action === 'approve') {
            $status  = 'approved';
            $reviewNotes = post('review_notes', '');
            $adminId = currentUserId();

            $stmt = $db->prepare("UPDATE room_requests SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ? AND status = 'pending'");
            $stmt->bind_param('ssii', $status, $reviewNotes, $adminId, $requestId);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                flash('success', 'Room request approved. Room marked as allocated.');
            } else {
                flash('error', 'Room request could not be updated (it may already be reviewed).');
            }
        } else {
            $reason = post('review_reason');

            if ($reason === '') {
                flash('error', 'Please provide a reason for declining the request.');
                redirect('/admin/room-requests?review=' . $requestId);
            }

            $status  = 'declined';
            $adminId = currentUserId();

            $stmt = $db->prepare("UPDATE room_requests SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ? AND status = 'pending'");
            $stmt->bind_param('ssii', $status, $reason, $adminId, $requestId);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                flash('success', 'Room request declined.');
            } else {
                flash('error', 'Room request could not be updated (it may already be reviewed).');
            }
        }
    }

    redirect('/admin/room-requests');
}

$statusFilter = get('status');

$conditions = [];
$bindings   = [];
$types      = '';

if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'approved', 'declined'], true)) {
    $conditions[] = "r.status = ?";
    $bindings[]   = $statusFilter;
    $types       .= 's';
}

$whereSql = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM room_requests r $whereSql");
if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()['n'];

$page       = max(1, (int) get('page', 1));
$limit      = 10;
$offset     = ($page - 1) * $limit;
$totalPages = max(1, (int) ceil($total / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$sql = "SELECT r.request_id, r.requested_date, r.start_time, r.end_time, r.expected_participants,
        r.preferred_room, r.reason, r.review_notes, r.status, r.reviewed_at,
        c.club_name, e.title AS event_title
        FROM room_requests r
        LEFT JOIN clubs c ON c.club_id = r.club_id
        LEFT JOIN events e ON e.event_id = r.event_id
        $whereSql
        ORDER BY r.created_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pendingCount = (int) $db->query("SELECT COUNT(*) AS n FROM room_requests WHERE status = 'pending'")->fetch_assoc()['n'];

$reviewId = (int) get('review');
$reviewRequest = null;

if ($reviewId > 0) {
    $stmt = $db->prepare("
        SELECT r.*, c.club_name, c.description AS club_description, e.title AS event_title, e.venue AS event_venue, e.start_time AS event_start
        FROM room_requests r
        LEFT JOIN clubs c ON c.club_id = r.club_id
        LEFT JOIN events e ON e.event_id = r.event_id
        WHERE r.request_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $reviewId);
    $stmt->execute();
    $reviewRequest = $stmt->get_result()->fetch_assoc();
}

$columns = ['Club', 'Event', 'Requested Date', 'Time', 'Participants', 'Requested Room', 'Status'];

$rows = [];

foreach ($requests as $request) {
    $reviewUrl = url('/admin/room-requests?review=' . $request['request_id']);

    $actions = '';
    if ($request['status'] === 'pending') {
        $actions .= '<a href="' . e($reviewUrl) . '" class="btn-primary btn-sm">Review</a>';
    } else {
        $actions .= '<a href="' . e($reviewUrl) . '" class="btn-ghost btn-sm">View</a>';
    }

    $roomCell = '<div class="min-w-0">
        <p class="font-medium text-gray-900 truncate">' . e($request['preferred_room'] ?? '—') . '</p>
    </div>';

    $statusCell = '';
    $badgeType  = $request['status'];
    $badgeText  = $request['status'] === 'approved' ? 'Room Allocated' : '';
    ob_start();
    require BASE_PATH . '/app/components/badge.php';
    $statusCell = ob_get_clean();

    $rows[] = [
        '<span class="text-sm font-medium text-gray-900">' . e($request['club_name'] ?? '—') . '</span>',
        '<span class="text-sm text-gray-600">' . e($request['event_title'] ?? '—') . '</span>',
        formatDate($request['requested_date'], 'M d, Y'),
        '<span class="text-sm text-gray-600 whitespace-nowrap">' . e(roomSlotLabel((string) $request['start_time'], (string) $request['end_time'])) . '</span>',
        '<span class="text-sm text-gray-600">' . (int) $request['expected_participants'] . '</span>',
        $roomCell,
        $statusCell,
        $actions,
    ];
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">Room Requests</h1>
                <p class="page-subtitle">Approve or decline venue allotment requests from clubs.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <?= $pendingCount ?> Pending
            </div>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <?php if ($reviewRequest): ?>
        <div class="card p-6 mb-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Review Request #<?= (int) $reviewRequest['request_id'] ?></h3>
                    <p class="text-sm text-gray-500 mt-1">Submitted by <?= e($reviewRequest['club_name'] ?? 'Unknown club') ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <?php
                    $badgeType = $reviewRequest['status'];
                    $badgeText = $reviewRequest['status'] === 'approved' ? 'Room Allocated' : '';
                    require BASE_PATH . '/app/components/badge.php';
                    ?>
                    <a href="<?= url('/admin/room-requests') ?>" class="btn-secondary btn-sm"><?= icon('x', 'w-4 h-4') ?> Close</a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Requested Date</p>
                    <p class="text-sm font-medium text-gray-900"><?= formatDate($reviewRequest['requested_date'], 'M d, Y') ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Time</p>
                    <p class="text-sm font-medium text-gray-900"><?= e(roomSlotLabel((string) $reviewRequest['start_time'], (string) $reviewRequest['end_time'])) ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Participants</p>
                    <p class="text-sm font-medium text-gray-900"><?= (int) $reviewRequest['expected_participants'] ?> people</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Requested Room</p>
                    <p class="text-sm font-medium text-gray-900"><?= e($reviewRequest['preferred_room'] ?? '—') ?></p>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Requested For</p>
                    <div class="space-y-1 text-sm">
                        <p><span class="text-gray-500">Event:</span> <span class="font-medium text-gray-900"><?= e($reviewRequest['event_title'] ?? '—') ?></span></p>
                        <p><span class="text-gray-500">Event venue:</span> <span class="font-medium text-gray-900"><?= e($reviewRequest['event_venue'] ?? '—') ?></span></p>
                    </div>

                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-5 mb-2">Reason</p>
                    <p class="text-sm text-gray-700 leading-relaxed"><?= e($reviewRequest['reason'] ?? '—') ?></p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Room Availability</p>
                    <div class="flex items-start gap-3 rounded-lg bg-blue-50 p-4 text-sm text-blue-800">
                        <?= icon('info', 'w-5 h-5 mt-0.5 shrink-0') ?>
                        <p>Venue availability is managed offline by the administration. Confirm the schedule is free before approving this request.</p>
                    </div>

                    <?php if ($reviewRequest['status'] === 'pending'): ?>

                    <div class="flex flex-wrap items-center gap-3 mt-5 pt-5 border-t border-gray-100">
                        <form method="POST" action="<?= url('/admin/room-requests') ?>" class="inline">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="request_id" value="<?= (int) $reviewRequest['request_id'] ?>">
                            <button type="submit" data-confirm="Approve this room request and mark the room as allocated?" class="btn-success"><?= icon('check-circle', 'w-4 h-4') ?> Approve</button>
                        </form>

                        <form method="POST" action="<?= url('/admin/room-requests') ?>" class="flex-1 min-w-[220px]">
                            <input type="hidden" name="action" value="decline">
                            <input type="hidden" name="request_id" value="<?= (int) $reviewRequest['request_id'] ?>">
                            <label for="review_reason" class="label">Decline reason</label>
                            <div class="flex gap-3">
                                <input type="text" id="review_reason" name="review_reason" class="input" placeholder="Required before declining..." required>
                                <button type="submit" class="btn-danger"><?= icon('x', 'w-4 h-4') ?> Decline</button>
                            </div>
                        </form>
                    </div>

                    <?php else: ?>

                    <div class="mt-5 pt-5 border-t border-gray-100 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Reviewed On</span>
                            <span class="font-medium text-gray-900"><?= $reviewRequest['reviewed_at'] ? formatDate($reviewRequest['reviewed_at'], 'M d, Y h:i A') : '—' ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Reviewed By</span>
                            <span class="font-medium text-gray-900">Administration</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Review Notes</span>
                            <p class="font-medium text-gray-900 mt-1"><?= e($reviewRequest['review_notes'] ?? '—') ?></p>
                        </div>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <form method="GET" action="<?= url('/admin/room-requests') ?>" class="flex flex-col sm:flex-row gap-3">
                    <select name="status" class="select sm:w-44">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="declined" <?= $statusFilter === 'declined' ? 'selected' : '' ?>>Declined</option>
                    </select>

                    <div class="flex-1"></div>

                    <button type="submit" class="btn-secondary">Filter</button>
                    <?php if ($statusFilter !== ''): ?>
                    <a href="<?= url('/admin/room-requests') ?>" class="btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="p-4 sm:p-6">
                <?php
                $actionSlot = 'Actions';
                $emptyMessage = 'No room requests found.';
                require BASE_PATH . '/app/components/table.php';

                $paginationQuery = $_GET;
                unset($paginationQuery['page'], $paginationQuery['review']);
                $paginationBase = url('/admin/room-requests') . (count($paginationQuery) > 0 ? '?' . http_build_query($paginationQuery) : '');

                $baseUrl = $paginationBase;
                $totalPages = $totalPages;
                $currentPage = $page;
                require BASE_PATH . '/app/components/pagination.php';
                ?>
            </div>
        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>