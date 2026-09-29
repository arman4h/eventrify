<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle  = 'Event History';
$activePage = 'event-history';

$search = get('q');

$conditions = ['1 = 1'];
$bindings   = [];
$types      = '';

if ($search !== '') {
    $conditions[] = "(de.title LIKE ? OR de.club_name LIKE ?)";
    $bindings[]   = "%$search%";
    $bindings[]   = "%$search%";
    $types       .= 'ss';
}

$whereSql = 'WHERE ' . implode(' AND ', $conditions);

$count = $db->prepare("SELECT COUNT(*) AS n FROM deleted_events de $whereSql");
if ($bindings) {
    $count->bind_param($types, ...$bindings);
}
$count->execute();
$total = (int) $count->get_result()->fetch_assoc()['n'];

$page       = max(1, (int) get('page', 1));
$limit      = 10;
$offset     = ($page - 1) * $limit;
$totalPages = max(1, (int) ceil($total / $limit));
if ($page > $totalPages) {
    $page       = $totalPages;
    $offset     = ($page - 1) * $limit;
}

// An archive entry is a frozen copy, so the club name and the reason are
// denormalised onto the row: reading them through clubs/system_admins would
// rewrite history whenever those accounts changed.
$stmt = $db->prepare("
    SELECT de.deleted_event_id, de.event_id, de.club_name, de.title, de.category,
           de.venue, de.start_time, de.end_time, de.capacity, de.status,
           de.registration_count, de.deleted_by_name, de.deleted_at, de.delete_reason,
           (SELECT COUNT(*) FROM deleted_event_registrations der
             WHERE der.deleted_event_id = de.deleted_event_id) AS archived_regs
    FROM deleted_events de
    $whereSql
    ORDER BY de.deleted_at DESC, de.deleted_event_id DESC
    LIMIT $limit OFFSET $offset
");
if ($bindings) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$listColumns = ['Archived Event', 'Club', 'Was Scheduled', 'Archived', 'Registrations', 'Reason'];

$listRows = [];

foreach ($entries as $entry) {
    $actions = '<div class="flex items-center justify-end gap-2">'
    . '<a href="' . e(url('/admin/events/history?view=' . $entry['deleted_event_id'])) . '" class="btn-ghost btn-sm">Details</a>'
    . '</div>';

    $eventCell = '<div class="min-w-0">'
        . '<p class="font-medium text-gray-900 truncate">' . e($entry['title']) . '</p>'
        . '<p class="text-xs text-gray-500 truncate max-w-xs">'
        . 'was #' . (int) $entry['event_id'] . ' &middot; ' . e($entry['venue'] ?: 'No venue')
        . '</p>'
        . '</div>';

    $statusCell = '';
    ob_start();
    $badgeType = in_array($entry['status'], ['draft', 'published', 'cancelled', 'completed'], true)
        ? $entry['status']
        : 'neutral';
    $badgeText = $badgeType === 'neutral' ? $entry['status'] : '';
    require BASE_PATH . '/app/components/badge.php';
    $statusCell = ob_get_clean();

    $listRows[] = [
        $eventCell,
        '<span class="text-sm text-gray-600">' . e($entry['club_name'] ?: '—') . '</span>',
        '<div>' . formatDate($entry['start_time'], 'M d, Y') . $statusCell . '</div>',
        '<div>'
            . '<p class="text-sm text-gray-900">' . e(formatDate($entry['deleted_at'], 'M d, Y')) . '</p>'
            . '<p class="text-xs text-gray-500">by ' . e($entry['deleted_by_name'] ?: '—') . '</p>'
            . '</div>',
        '<span class="text-sm font-medium text-gray-900">' . (int) $entry['archived_regs'] . '</span>',
        '<span class="text-sm text-gray-600">' . e($entry['delete_reason'] ?: '—') . '</span>',
        $actions,
    ];
}

// ── Detail view ────────────────────────────────────────────────
$detail    = null;
$detailReg = [];
$viewId    = (int) get('view');

if ($viewId > 0) {
    $view = $db->prepare("SELECT * FROM deleted_events WHERE deleted_event_id = ?");
    $view->bind_param('i', $viewId);
    $view->execute();
    $detail = $view->get_result()->fetch_assoc();

    if ($detail) {
        $regs = $db->prepare("
            SELECT student_name, university_id, student_email, guest_name, guest_student_id,
                   is_walkin, status, waitlist_position, registered_at
            FROM deleted_event_registrations
            WHERE deleted_event_id = ?
            ORDER BY is_walkin, waitlist_position, registered_at
        ");
        $regs->bind_param('i', $viewId);
        $regs->execute();
        $detailReg = $regs->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <nav class="breadcrumb mb-6">
            <a href="<?= url('/admin') ?>" class="breadcrumb-link">Administration</a>
            <span class="text-gray-400">/</span>
            <span class="breadcrumb-current">Event History</span>
        </nav>

        <div class="page-header mb-6">
            <div>
                <h1 class="page-title">Event History</h1>
                <p class="page-subtitle">Events removed by a system administrator, with a copy of who was registered. Visible to administrators only.</p>
            </div>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <?php if ($detail): ?>
            <a href="<?= url('/admin/events/history') ?>" class="btn-ghost btn-sm mb-4">&larr; Back to history</a>

            <div class="card p-6 sm:p-8 mb-6">
                <div class="flex flex-col sm:flex-row gap-6">
                    <?php if (!empty($detail['poster'])): ?>
                        <img src="<?= e($detail['poster']) ?>" alt="" class="w-40 h-40 rounded-lg object-cover border border-gray-200 shrink-0">
                    <?php else: ?>
                        <span class="inline-flex w-40 h-40 rounded-lg bg-gray-100 items-center justify-center text-gray-400 shrink-0">
                            <?= icon('calendar', 'w-10 h-10') ?>
                        </span>
                    <?php endif; ?>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-gray-900"><?= e($detail['title']) ?></h2>
                        <p class="text-sm text-gray-500 mt-1">
                            <?= e($detail['club_name'] ?: 'Unknown club') ?>
                            &middot; was event #<?= (int) $detail['event_id'] ?>
                        </p>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mt-5 text-sm">
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Scheduled</dt>
                                <dd class="text-gray-900 mt-0.5">
                                    <?= e(formatDate($detail['start_time'], 'M d, Y')) ?>
                                    &ndash; <?= e(formatDate($detail['end_time'], 'H:i')) ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Venue</dt>
                                <dd class="text-gray-900 mt-0.5"><?= e($detail['venue'] ?: '—') ?></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Category</dt>
                                <dd class="text-gray-900 mt-0.5"><?= e($detail['category'] ?: '—') ?></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Capacity</dt>
                                <dd class="text-gray-900 mt-0.5"><?= (int) $detail['capacity'] ?></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Status when deleted</dt>
                                <dd class="text-gray-900 mt-0.5"><?= e(ucfirst($detail['status'])) ?></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Deleted</dt>
                                <dd class="text-gray-900 mt-0.5">
                                    <?= e(formatDate($detail['deleted_at'], 'M d, Y')) ?>
                                    by <?= e($detail['deleted_by_name'] ?: '—') ?>
                                </dd>
                            </div>
                        </dl>

                        <?php if (!empty($detail['delete_reason'])): ?>
                            <div class="mt-5">
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Reason given</dt>
                                <p class="text-sm text-gray-900 mt-1"><?= e($detail['delete_reason']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($detail['description'])): ?>
                            <div class="mt-5">
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Description</dt>
                                <p class="text-sm text-gray-700 mt-1 whitespace-pre-line"><?= e($detail['description']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">
                        Archived registrations (<?= count($detailReg) ?>)
                    </h3>
                </div>
                <div class="p-4 sm:p-6">
                    <?php
                    $columns = ['Person', 'University ID', 'Contact', 'Type', 'Status', 'Registered'];
                    $rows    = [];

                    foreach ($detailReg as $reg) {
                        $isWalkin  = (bool) $reg['is_walkin'];
                        $person    = $isWalkin
                            ? ($reg['guest_name'] ?: 'Walk-in')
                            : ($reg['student_name'] ?: '—');
                        $contact   = $isWalkin
                            ? ($reg['guest_student_id'] ?: '—')
                            : ($reg['student_email'] ?: '—');

                        $regBadge = '';
                        ob_start();
                        $badgeType = $reg['status'];
                        $badgeText = $reg['status'] === 'waitlisted' && $reg['waitlist_position']
                            ? 'Waitlist #' . (int) $reg['waitlist_position']
                            : '';
                        require BASE_PATH . '/app/components/badge.php';
                        $regBadge = ob_get_clean();

                        $rows[] = [
                            '<span class="text-sm font-medium text-gray-900">' . e($person) . '</span>',
                            '<span class="text-sm text-gray-600">' . e($isWalkin ? ($reg['guest_student_id'] ?: '—') : ($reg['university_id'] ?: '—')) . '</span>',
                            '<span class="text-sm text-gray-600">' . e($contact) . '</span>',
                            '<span class="text-sm text-gray-600">' . ($isWalkin ? 'Walk-in' : 'Student') . '</span>',
                            $regBadge,
                            '<span class="text-sm text-gray-600">' . e(formatDate($reg['registered_at'])) . '</span>',
                        ];
                    }

                    $actionSlot   = null;
                    $emptyMessage = 'Nobody was registered when this event was deleted.';
                    require BASE_PATH . '/app/components/table.php';
                    ?>
                </div>
            </div>

        <?php else: ?>

            <div class="card overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <form method="GET" action="<?= url('/admin/events/history') ?>" class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"><?= icon('search', 'w-4 h-4') ?></span>
                            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search archived events or clubs..." class="input pl-10">
                        </div>
                        <button type="submit" class="btn-secondary">Search</button>
                        <?php if ($search !== ''): ?>
                            <a href="<?= url('/admin/events/history') ?>" class="btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="p-4 sm:p-6">
                    <?php
                    $columns = $listColumns;
                    $rows    = $listRows;

                    $actionSlot   = null;
                    $emptyMessage = $total === 0 && $search === ''
                        ? 'No events have been deleted yet.'
                        : 'No archived events found.';

                    require BASE_PATH . '/app/components/table.php';

                    $paginationQuery = $_GET;
                    unset($paginationQuery['page']);
                    $paginationBase = url('/admin/events/history') . (count($paginationQuery) > 0 ? '?' . http_build_query($paginationQuery) : '');

                    $baseUrl     = $paginationBase;
                    $currentPage = $page;
                    require BASE_PATH . '/app/components/pagination.php';
                    ?>
                </div>
            </div>

        <?php endif; ?>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>
