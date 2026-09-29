<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/rooms.php';

requireAdmin();

$pageTitle = 'Room Requests';
$activePage = 'room-requests';

if (isPost()) {
    $batchKey = post('batch_key');
    $action   = post('action');
    $notes    = trim((string) post('review_notes', ''));

    $errors = [];
    if ($batchKey === '') {
        $errors[] = 'That booking could not be identified. Reopen it and try again.';
    }
    if ($action === 'decline' && $notes === '') {
        $errors[] = 'Give a reason for declining so the club knows what to change.';
    }

    $noteErrors = [];
    vAdd($noteErrors, vMaxLen($notes, 1000, 'Review note'));
    $errors = array_merge($errors, $noteErrors);

    if (count($errors) > 0) {
        flash('error', implode("\n", $errors));
        redirect('/admin/room-requests');
    }

    $result = roomReviewBatch($batchKey, $action, $notes, currentUserId());
    flash($result['ok'] ? 'success' : 'error', $result['message']);
    redirect('/admin/room-requests' . ($result['ok'] ? '' : '?status=pending'));
}

$statusFilter = get('status');
if (!in_array($statusFilter, ['pending', 'approved', 'declined'], true)) {
    $statusFilter = '';
}
$search = trim((string) get('q', ''));

$batches = roomBatchesForAdmin($statusFilter === '' ? null : $statusFilter, $search);

$page       = max(1, (int) get('page', 1));
$limit      = 8;
$total      = count($batches);
$totalPages = max(1, (int) ceil($total / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$pageBatches = array_slice($batches, ($page - 1) * $limit, $limit);

// Counts are per batch, so the badge reflects bookings rather than rows.
$statusCounts = ['pending' => 0, 'approved' => 0, 'declined' => 0];
foreach (roomBatchesForAdmin() as $batch) {
    if (isset($statusCounts[$batch['status']])) {
        $statusCounts[$batch['status']]++;
    }
}
$pendingCount = $statusCounts['pending'];

$focusKey = (string) get('review');
$focusBatch = null;
if ($focusKey !== '') {
    foreach ($batches as $batch) {
        if ($batch['key'] === $focusKey) {
            $focusBatch = $batch;
            break;
        }
    }
}

// Availability is only needed for the booking being reviewed.
$focusAvailability = null;
$focusConflicts = [];
if ($focusBatch && $focusBatch['status'] === 'pending' && $focusBatch['room'] !== '') {
    // Ignore the booking's own rows so it is never shown as clashing with itself.
    $ownIds = $focusBatch['request_ids'];
    $focusAvailability = roomAvailabilityGrid($focusBatch['date'], 0, $ownIds);
    foreach ($focusBatch['rows'] as $row) {
        $key   = roomTimePart((string) $row['start_time']) . '|' . roomTimePart((string) $row['end_time']);
        $found = roomSlotConflicts($focusBatch['date'], $focusBatch['room'], [roomSlotIndex($key)], 0, $ownIds);
        if (count($found) > 0) {
            $focusConflicts[$key] = $found;
        }
    }
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
                <p class="page-subtitle">Every slot in a booking is approved or declined together.</p>
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

        <?php if ($focusBatch): ?>
        <div class="card p-6 mb-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        <?= e($focusBatch['club']) ?>
                        <?php if ($focusBatch['event'] !== ''): ?>
                        <span class="font-normal text-gray-500">&middot; <?= e($focusBatch['event']) ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Booking of <?= count($focusBatch['rows']) ?> slot<?= count($focusBatch['rows']) > 1 ? 's' : '' ?>
                        &middot; requests <?php
                        echo e(implode(', ', array_map(static fn(int $id): string => '#' . $id, $focusBatch['request_ids'])));
                        ?>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <?php
                    $badgeType = $focusBatch['status'];
                    $badgeText = $focusBatch['status'] === 'approved' ? 'Room Allocated' : '';
                    require BASE_PATH . '/app/components/badge.php';
                    ?>
                    <a href="<?= url('/admin/room-requests') ?>" class="btn-secondary btn-sm">
                        <?= icon('x', 'w-4 h-4') ?> Close
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Requested Date</p>
                    <p class="text-sm font-medium text-gray-900"><?= formatDate($focusBatch['date'], 'M d, Y') ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Time</p>
                    <p class="text-sm font-medium text-gray-900"><?= e($focusBatch['slot_summary']) ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Participants</p>
                    <p class="text-sm font-medium text-gray-900"><?= (int) $focusBatch['participants'] ?> people</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Requested Room</p>
                    <p class="text-sm font-medium text-gray-900"><?= e($focusBatch['room'] !== '' ? $focusBatch['room'] : '—') ?></p>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Reason</p>
                    <p class="text-sm text-gray-700 leading-relaxed"><?= e($focusBatch['reason'] !== '' ? $focusBatch['reason'] : '—') ?></p>

                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-5 mb-2">Slots in this booking</p>
                    <ul class="space-y-1 text-sm">
                        <?php foreach ($focusBatch['rows'] as $row): ?>
                        <li class="flex items-center justify-between gap-3">
                            <span class="font-medium text-gray-900"><?= e(roomSlotLabel((string) $row['start_time'], (string) $row['end_time'])) ?></span>
                            <span class="text-gray-500">request #<?= (int) $row['request_id'] ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Room Availability</p>
                    <?php if ($focusBatch['room'] === ''): ?>
                    <p class="text-sm text-gray-600">This request did not name a specific room.</p>
                    <?php elseif ($focusAvailability === null): ?>
                    <p class="text-sm text-gray-600">
                        This booking is already <?= $focusBatch['status'] === 'approved' ? 'allocated' : 'closed' ?>, so the live grid is not shown.
                    </p>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr>
                                    <th class="text-left font-semibold text-gray-600 py-1">Room</th>
                                    <?php foreach (roomTimeSlots() as $slot): ?>
                                    <th class="px-1 py-1 font-semibold text-gray-600 whitespace-nowrap" title="<?= e($slot['label']) ?>">
                                        <?= e(substr($slot['label'], 0, 5)) ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $focusIndices = [];
                                foreach ($focusBatch['rows'] as $row) {
                                    $focusIndices[] = roomSlotIndex(
                                        roomTimePart((string) $row['start_time']) . '|' . roomTimePart((string) $row['end_time'])
                                    );
                                }
                                ?>
                                <?php foreach ($focusAvailability['rooms'] as $line): ?>
                                <tr>
                                    <td class="py-1 pr-2 text-gray-700 whitespace-nowrap"><?= e($line['room']) ?></td>
                                    <?php foreach (roomTimeSlots() as $slot): ?>
                                    <?php
                                    $slotIndex   = roomSlotIndex($slot['start'] . '|' . $slot['end']);
                                    $isRequested = in_array($slotIndex, $focusIndices, true);
                                    $isFree      = $line['cells'][$slotIndex]['free'] ?? true;
                                    $cellClass   = $isRequested
                                        ? ($isFree ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800')
                                        : ($isFree ? 'bg-white text-gray-300' : 'bg-gray-100 text-gray-400');
                                    ?>
                                    <td class="px-1 py-1 text-center rounded <?= $cellClass ?>">
                                        <?= $isRequested ? ($isFree ? '&#10003;' : '!') : ($isFree ? '' : '&middot;') ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (count($focusConflicts) > 0): ?>
                    <div class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                        <?= icon('alert', 'w-4 h-4 inline-block align-text-bottom') ?>
                        Another club already holds
                        <?= e(implode(', ', array_map(static fn(array $c): string => $c['club'], array_merge(...array_values($focusConflicts))))) ?>
                        for this room and date.
                    </div>
                    <?php else: ?>
                    <p class="mt-3 text-xs text-gray-500">
                        A dot marks a slot another club already holds. A tick marks a slot in this booking.
                    </p>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($focusBatch['status'] === 'pending'): ?>
                    <form method="POST" action="<?= url('/admin/room-requests') ?>" class="mt-5 pt-5 border-t border-gray-100 space-y-3">
                        <?= csrfField() ?>
                        <input type="hidden" name="batch_key" value="<?= e($focusBatch['key']) ?>">
                        <input type="hidden" name="action" value="decline">
                        <label for="review_notes" class="label">Review note</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" id="review_notes" name="review_notes" class="input flex-1"
                                   placeholder="Required when declining, optional when approving...">
                            <div class="flex gap-3">
                                <button type="submit" name="action" value="approve"
                                        data-confirm="Approve all <?= count($focusBatch['rows']) ?> slot(s) in this booking?"
                                        class="btn-success"><?= icon('check-circle', 'w-4 h-4') ?> Approve</button>
                                <button type="submit" name="action" value="decline"
                                        data-confirm="Decline all <?= count($focusBatch['rows']) ?> slot(s) in this booking?"
                                        class="btn-danger"><?= icon('x', 'w-4 h-4') ?> Decline</button>
                            </div>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="mt-5 pt-5 border-t border-gray-100 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Reviewed On</span>
                            <span class="font-medium text-gray-900">
                                <?= $focusBatch['reviewed_at'] ? formatDate((string) $focusBatch['reviewed_at'], 'M d, Y h:i A') : '—' ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Review Note</span>
                            <p class="font-medium text-gray-900 mt-1"><?= e($focusBatch['review_notes'] !== '' ? $focusBatch['review_notes'] : '—') ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php elseif ($focusKey !== ''): ?>
        <?php flash('error', 'That booking is no longer in the list.'); ?>
        <?php endif; ?>

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="<?= url('/admin/room-requests') ?>"
               class="btn-sm rounded-full px-3 py-1.5 <?= $statusFilter === '' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 border border-gray-300' ?>">
                All (<?= array_sum($statusCounts) ?>)
            </a>
            <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined'] as $key => $label): ?>
            <a href="<?= url('/admin/room-requests?status=' . $key) ?>"
               class="btn-sm rounded-full px-3 py-1.5 <?= $statusFilter === $key ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 border border-gray-300' ?>">
                <?= $label ?> (<?= $statusCounts[$key] ?>)
            </a>
            <?php endforeach; ?>
        </div>

        <div class="card overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <form method="GET" action="<?= url('/admin/room-requests') ?>" class="flex flex-col sm:flex-row gap-3">
                    <input type="search" name="q" value="<?= e($search) ?>" class="input sm:max-w-xs"
                           placeholder="Search club, event, or room...">
                    <?php if ($statusFilter !== ''): ?>
                    <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn-secondary">Search</button>
                    <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="<?= url('/admin/room-requests') ?>" class="btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="divide-y divide-gray-100">
                <?php if (count($pageBatches) === 0): ?>
                <div class="p-10 text-center text-sm text-gray-500">No room requests found.</div>
                <?php endif; ?>

                <?php foreach ($pageBatches as $batch): ?>
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900">
                            <?= e($batch['club']) ?>
                            <?php if ($batch['event'] !== ''): ?>
                            <span class="font-normal text-gray-500">&middot; <?= e($batch['event']) ?></span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?= formatDate($batch['date'], 'M d, Y') ?>
                            &middot; <?= e($batch['room'] !== '' ? $batch['room'] : 'No room named') ?>
                            &middot; <?= (int) $batch['participants'] ?> people
                        </p>
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <?php foreach ($batch['rows'] as $row): ?>
                            <span class="badge-neutral"><?= e(roomSlotLabel((string) $row['start_time'], (string) $row['end_time'])) ?></span>
                            <?php endforeach; ?>
                            <?php if (count($batch['rows']) > 1): ?>
                            <span class="text-xs text-gray-500">
                                one booking (#<?= e(implode(', ', array_map(static fn(int $id): string => (string) $id, $batch['request_ids']))) ?>)
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <?php
                        $badgeType = $batch['status'];
                        $badgeText = $batch['status'] === 'approved' ? 'Room Allocated' : '';
                        require BASE_PATH . '/app/components/badge.php';
                        ?>
                        <a href="<?= url('/admin/room-requests?review=' . urlencode($batch['key'])) ?>"
                           class="<?= $batch['status'] === 'pending' ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
                            <?= $batch['status'] === 'pending' ? 'Review' : 'View' ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-gray-200">
                <?php
                $query = $_GET;
                unset($query['page'], $query['review']);
                $baseUrl = url('/admin/room-requests') . (count($query) > 0 ? '?' . http_build_query($query) : '');
                $totalPages = $totalPages;
                $currentPage = $page;
                require BASE_PATH . '/app/components/pagination.php';
                ?>
            </div>
            <?php endif; ?>
        </div>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>
