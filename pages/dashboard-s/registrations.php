<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireStudent();

$studentId = (int) currentUserId();
$search = get('q');
$statusFilter = get('status');
$perPage = 10;
$currentPage = max(1, (int) get('page', 1));

$allowedStatuses = ['registered', 'waitlisted', 'attended', 'cancelled', 'no_show'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$regBadgeClass = function (string $status): string {
    return match ($status) {
        'registered' => 'badge-info',
        'waitlisted' => 'badge-warning',
        'attended'   => 'badge-success',
        'cancelled'  => 'badge-danger',
        'no_show'    => 'badge-neutral',
        default      => 'badge-neutral',
    };
};

$whereClause = "WHERE er.student_id = ?";
$types = 'i';
$params = [$studentId];

if ($search !== '') {
    $whereClause .= " AND e.title LIKE ?";
    $types .= 's';
    $params[] = "%$search%";
}

if ($statusFilter !== '') {
    $whereClause .= " AND er.status = ?";
    $types .= 's';
    $params[] = $statusFilter;
}

$countStmt = $db->prepare("SELECT COUNT(*) AS c FROM event_registrations er JOIN events e ON e.event_id = er.event_id $whereClause");
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = (int) $countStmt->get_result()->fetch_assoc()['c'];

if ($totalRows === 0) {
    $totalPages = 1;
} else {
    $totalPages = (int) ceil($totalRows / $perPage);
}

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

$listSql = "
    SELECT er.registration_id, er.registered_at, er.status AS reg_status,
           e.event_id, e.title, e.start_time, e.status AS event_status, c.club_name
    FROM event_registrations er
    JOIN events e ON e.event_id = er.event_id
    LEFT JOIN clubs c ON c.club_id = e.club_id
    $whereClause
    ORDER BY er.registered_at DESC
    LIMIT ? OFFSET ?
";
$listTypes = $types . 'ii';
$listParams = array_merge($params, [$perPage, $offset]);

$listStmt = $db->prepare($listSql);
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$registrations = $listStmt->get_result();

$rows = [];
while ($row = $registrations->fetch_assoc()) {
    $rows[] = $row;
}

$totalCount = 0;
$attendedCount = 0;

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM event_registrations WHERE student_id = ? AND status != 'cancelled'");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$totalCount = (int) $stmt->get_result()->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT COUNT(*) AS c FROM event_registrations WHERE student_id = ? AND status = 'attended'");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$attendedCount = (int) $stmt->get_result()->fetch_assoc()['c'];

$queryParams = [];
if ($search !== '') {
    $queryParams['q'] = $search;
}
if ($statusFilter !== '') {
    $queryParams['status'] = $statusFilter;
}
$baseUrl = '/student/registrations' . (count($queryParams) > 0 ? '?' . http_build_query($queryParams) : '');

$pageTitle = 'My Registrations';
$activePage = 'registrations';
require BASE_PATH . '/app/layouts/dashboard-s/header.php';
require BASE_PATH . '/app/layouts/dashboard-s/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-s/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Registrations</h1>
                <p class="page-subtitle">Track every event you've signed up for.</p>
            </div>
            <a href="<?= url('/events') ?>" class="btn-primary btn-sm">
                <?= icon('plus', 'w-4 h-4') ?>
                Explore Events
            </a>
        </div>

        <?php
        $alertType = 'success';
        $alertMessage = flash('success');
        require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <div class="stat-card flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center"><?= icon('ticket', 'w-5 h-5') ?></span>
                <div>
                    <p class="stat-label">Registrations</p>
                    <p class="stat-value"><?= $totalCount ?></p>
                </div>
            </div>
            <div class="stat-card flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 inline-flex items-center justify-center"><?= icon('check-circle', 'w-5 h-5') ?></span>
                <div>
                    <p class="stat-label">Attended</p>
                    <p class="stat-value"><?= $attendedCount ?></p>
                </div>
            </div>
        </div>

        <div class="card p-4 mb-6">
            <form method="GET" action="<?= url('/student/registrations') ?>" class="flex flex-col sm:flex-row gap-3">
                <div class="search-wrapper flex-1">
                    <?= icon('search', 'search-icon w-4 h-4') ?>
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by event title..." class="input w-full !py-2.5 pl-10">
                </div>
                <select name="status" class="select sm:w-52">
                    <option value="">All statuses</option>
                    <option value="registered" <?= $statusFilter === 'registered' ? 'selected' : '' ?>>Registered</option>
                    <option value="waitlisted" <?= $statusFilter === 'waitlisted' ? 'selected' : '' ?>>Waitlisted</option>
                    <option value="attended" <?= $statusFilter === 'attended' ? 'selected' : '' ?>>Attended</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn-secondary">
                    <?= icon('search', 'w-4 h-4') ?>
                    Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a href="<?= url('/student/registrations') ?>" class="btn-ghost btn-sm self-center">Clear filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card overflow-hidden">
            <?php if (count($rows) === 0): ?>
            <div class="empty-state">
                <span class="empty-state-icon text-gray-400"><?= icon('ticket', 'w-6 h-6') ?></span>
                <p class="empty-state-title"><?= ($search !== '' || $statusFilter !== '') ? 'No matching registrations' : 'No registrations yet' ?></p>
                <p class="empty-state-text">
                    <?= ($search !== '' || $statusFilter !== '') ? 'Try adjusting your search or filters.' : 'Discover events happening on campus and register to see them here.' ?>
                </p>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a href="<?= url('/student/registrations') ?>" class="btn-secondary btn-sm">Clear filters</a>
                <?php else: ?>
                <a href="<?= url('/events') ?>" class="btn-primary btn-sm">Discover Events</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-wrapper !rounded-none !border-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Club</th>
                            <th>Date</th>
                            <th>Registered On</th>
                            <th>Status</th>
                            <th class="!text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $reg): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/event?event_id=' . $reg['event_id']) ?>" class="font-medium text-gray-900 hover:text-blue-600"><?= e($reg['title']) ?></a>
                            </td>
                            <td class="whitespace-nowrap"><?= e($reg['club_name'] ?? 'University Club') ?></td>
                            <td class="whitespace-nowrap"><?= formatDate($reg['start_time'], 'M d, Y') ?></td>
                            <td class="whitespace-nowrap"><?= formatDate($reg['registered_at'], 'M d, Y') ?></td>
                            <td>
                                <span class="badge <?= $regBadgeClass($reg['reg_status']) ?>"><?= ucfirst(e($reg['reg_status'])) ?></span>
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="<?= url('/event?event_id=' . $reg['event_id']) ?>" class="btn-ghost btn-sm">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            require BASE_PATH . '/app/components/pagination.php';
            ?>
            <?php endif; ?>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-s/footer.php'; ?>
</div>