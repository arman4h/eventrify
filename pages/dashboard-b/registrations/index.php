<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubUser();

$pageTitle = 'Registrations';
$activePage = 'registrations';

$clubId = (int) currentUser()['club_id'];
$filterEventId = (int) get('event_id', 0);
$search = get('q');
$page = max(1, (int) get('page', 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

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
        $alertType = flash('success') ? 'success' : 'error';
        $alertMessage = flash('success') ?: flash('error');
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
