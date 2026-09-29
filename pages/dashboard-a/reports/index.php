<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle  = 'Reports';
$activePage = 'reports';

$view = get('view', 'analytics');
if (!in_array($view, ['analytics', 'queue'], true)) {
    $view = 'analytics';
}

$errors = [];

// ── Analytics window (used by the Analytics tab) ──────────────────────
$dateFrom = get('date_from', date('Y-m-d', strtotime('first day of -5 months')));
$dateTo   = get('date_to', date('Y-m-d'));

if (!strtotime($dateFrom) || !strtotime($dateTo)) {
    $dateFrom = date('Y-m-d', strtotime('first day of -5 months'));
    $dateTo   = date('Y-m-d');
}
if ($dateTo < $dateFrom) {
    $dateTo = $dateFrom;
}

$fromDt = $dateFrom . ' 00:00:00';
$toDt   = $dateTo . ' 23:59:59';

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM events WHERE start_time BETWEEN ? AND ?");
$stmt->bind_param('ss', $fromDt, $toDt);
$stmt->execute();
$eventsCount = (int) $stmt->get_result()->fetch_assoc()['n'];

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM event_registrations WHERE registered_at BETWEEN ? AND ?");
$stmt->bind_param('ss', $fromDt, $toDt);
$stmt->execute();
$registrationsCount = (int) $stmt->get_result()->fetch_assoc()['n'];

$stmt = $db->prepare("
    SELECT COUNT(*) AS total, COALESCE(SUM(status = 'attended'), 0) AS attended
    FROM event_registrations
    WHERE registered_at BETWEEN ? AND ? AND status <> 'cancelled'
");
$stmt->bind_param('ss', $fromDt, $toDt);
$stmt->execute();
$attendanceRow = $stmt->get_result()->fetch_assoc();

$attendanceRate = '—';
if ((int) $attendanceRow['total'] > 0) {
    $attendanceRate = round(((int) $attendanceRow['attended'] / (int) $attendanceRow['total']) * 100) . '%';
}

$activeClubs = (int) $db->query("SELECT COUNT(*) AS n FROM clubs WHERE status = 'approved'")->fetch_assoc()['n'];

$months = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("first day of -$i months");
    $months[date('Y-m', $ts)] = ['label' => date('M Y', $ts), 'events' => 0, 'registrations' => 0];
}

foreach ($db->query("SELECT DATE_FORMAT(start_time, '%Y-%m') AS ym, COUNT(*) AS n FROM events GROUP BY ym") as $row) {
    if (isset($months[$row['ym']])) {
        $months[$row['ym']]['events'] = (int) $row['n'];
    }
}
foreach ($db->query("SELECT DATE_FORMAT(registered_at, '%Y-%m') AS ym, COUNT(*) AS n FROM event_registrations GROUP BY ym") as $row) {
    if (isset($months[$row['ym']])) {
        $months[$row['ym']]['registrations'] = (int) $row['n'];
    }
}

$maxEvents = 0;
$maxRegs   = 0;
foreach ($months as $month) {
    $maxEvents = max($maxEvents, $month['events']);
    $maxRegs   = max($maxRegs, $month['registrations']);
}

$topClubs = $db->query("
    SELECT c.club_name, COUNT(er.registration_id) AS n
    FROM event_registrations er
    JOIN events e ON e.event_id = er.event_id
    JOIN clubs c ON c.club_id = e.club_id
    GROUP BY c.club_id
    ORDER BY n DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC) ?? [];

$categories = $db->query("
    SELECT category, COUNT(*) AS n
    FROM events
    WHERE category IS NOT NULL AND category <> ''
    GROUP BY category
    ORDER BY n DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC) ?? [];

$maxTopClubs  = 1;
foreach ($topClubs as $club) {
    $maxTopClubs = max($maxTopClubs, (int) $club['n']);
}

$maxCategories = 1;
foreach ($categories as $category) {
    $maxCategories = max($maxCategories, (int) $category['n']);
}

// ── Workflow actions ──────────────────────────────────────────────────
if (isPost()) {
    $action    = post('action');
    $reportId  = (int) post('report_id');
    $newStatus = post('status');

    if (!in_array($action, ['set_status'], true)) {
        $errors[] = 'Unknown action.';
    } elseif ($reportId <= 0) {
        $errors[] = 'No report was selected.';
    } elseif (!in_array($newStatus, reportAllStatuses(), true)) {
        $errors[] = 'That is not a valid status.';
    } else {
        $stmt = $db->prepare("SELECT status FROM reports WHERE report_id = ?");
        $stmt->bind_param('i', $reportId);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();

        if (!$current) {
            $errors[] = 'That report no longer exists.';
        } else {
            $allowed = reportStatusTransitions()[(string) $current['status']] ?? [];
            if (!in_array($newStatus, $allowed, true)) {
                $errors[] = 'A ' . reportStatusLabel((string) $current['status'])
                    . ' report cannot be moved to ' . reportStatusLabel($newStatus) . '.';
            } else {
                $adminId = (int) currentUserId();
                if ($newStatus === 'resolved' || $newStatus === 'dismissed') {
                    $upd = $db->prepare("
                        UPDATE reports
                        SET status = ?, reviewed_by = ?, resolved_at = NOW()
                        WHERE report_id = ?
                    ");
                    $upd->bind_param('sii', $newStatus, $adminId, $reportId);
                } else {
                    $upd = $db->prepare("
                        UPDATE reports
                        SET status = ?, reviewed_by = ?, resolved_at = NULL
                        WHERE report_id = ?
                    ");
                    $upd->bind_param('sii', $newStatus, $adminId, $reportId);
                }

                if ($upd->execute() && $upd->affected_rows > 0) {
                    flash('success', 'Report marked as ' . strtolower(reportStatusLabel($newStatus)) . '.');
                } else {
                    flash('error', 'The report could not be updated. Please refresh and try again.');
                }
            }
        }
    }

    if (count($errors) === 0) {
        redirect('/admin/reports?view=queue&focus=' . $reportId);
    }
}

// ── Queue filters ─────────────────────────────────────────────────────
$search       = get('q');
$statusFilter = get('status');
$clubFilter   = (int) get('club');
$eventFilter  = (int) get('event');

if (!in_array($statusFilter, reportAllStatuses(), true)) {
    $statusFilter = '';
}

$where  = '1 = 1';
$types  = '';
$params = [];

if ($statusFilter !== '') {
    $where .= ' AND r.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
if ($clubFilter > 0) {
    $where .= ' AND r.reported_club_id = ?';
    $types .= 'i';
    $params[] = $clubFilter;
}
if ($eventFilter > 0) {
    $where .= ' AND r.reported_event_id = ?';
    $types .= 'i';
    $params[] = $eventFilter;
}
if ($search !== '') {
    $where .= ' AND (r.subject LIKE ? OR r.description LIKE ? OR s.full_name LIKE ?'
           . ' OR s.university_id LIKE ? OR s.email LIKE ? OR e.title LIKE ? OR c.club_name LIKE ?)';
    $types .= 'sssssss';
    for ($i = 0; $i < 7; $i++) {
        $params[] = "%$search%";
    }
}

$joins = "
    FROM reports r
    JOIN students s ON s.student_id = r.reporter_id
    LEFT JOIN events e ON e.event_id = r.reported_event_id
    LEFT JOIN clubs c  ON c.club_id = r.reported_club_id
";

$countStmt = $db->prepare("SELECT COUNT(*) AS c $joins WHERE $where");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int) $countStmt->get_result()->fetch_assoc()['c'];

$perPage     = 10;
$currentPage = max(1, (int) get('page', 1));
$totalPages  = max(1, (int) ceil($totalRows / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;

$listStmt = $db->prepare("
    SELECT r.report_id, r.subject, r.description, r.status, r.created_at, r.resolved_at,
           r.reviewed_by, r.reported_event_id, r.reported_club_id,
           s.full_name AS reporter_name, s.university_id AS reporter_uid,
           s.email AS reporter_email, s.department AS reporter_department,
           e.title AS event_title, c.club_name, c.official_email AS club_email,
           a.full_name AS reviewer_name
    $joins
    LEFT JOIN system_admins a ON a.admin_id = r.reviewed_by
    WHERE $where
    ORDER BY FIELD(r.status, 'open', 'under_review', 'resolved', 'dismissed'), r.created_at DESC
    LIMIT ? OFFSET ?
");
if ($types !== '') {
    $listStmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
} else {
    $listStmt->bind_param('ii', $perPage, $offset);
}
$listStmt->execute();
$reports = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

// ── Status summary + filter options ───────────────────────────────────
$summary = $db->query("SELECT status, COUNT(*) AS c FROM reports GROUP BY status")->fetch_all(MYSQLI_ASSOC) ?? [];
$statusCounts = array_fill_keys(reportAllStatuses(), 0);
$totalAll = 0;
foreach ($summary as $row) {
    $statusCounts[(string) $row['status']] = (int) $row['c'];
    $totalAll += (int) $row['c'];
}
$openTotal = $statusCounts['open'] + $statusCounts['under_review'];

$clubOptions = $db->query("
    SELECT DISTINCT c.club_id, c.club_name
    FROM reports r JOIN clubs c ON c.club_id = r.reported_club_id
    ORDER BY c.club_name
")->fetch_all(MYSQLI_ASSOC) ?? [];

$eventOptions = $db->query("
    SELECT DISTINCT e.event_id, e.title
    FROM reports r JOIN events e ON e.event_id = r.reported_event_id
    ORDER BY e.start_time DESC
    LIMIT 60
")->fetch_all(MYSQLI_ASSOC) ?? [];

$focusId = (int) get('focus');
$focus   = null;
if ($focusId > 0) {
    $stmt = $db->prepare("
        SELECT r.*, s.full_name AS reporter_name, s.university_id AS reporter_uid,
               s.email AS reporter_email, s.phone AS reporter_phone,
               s.department AS reporter_department, s.batch AS reporter_batch,
               e.title AS event_title, e.start_time AS event_start, e.end_time AS event_end,
               e.venue AS event_venue,
               e.status AS event_status, c.club_name, c.official_email AS club_email,
               c.club_id AS resolved_club_id, a.full_name AS reviewer_name
        FROM reports r
        JOIN students s ON s.student_id = r.reporter_id
        LEFT JOIN events e ON e.event_id = r.reported_event_id
        LEFT JOIN clubs c  ON c.club_id = r.reported_club_id
        LEFT JOIN system_admins a ON a.admin_id = r.reviewed_by
        WHERE r.report_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $focusId);
    $stmt->execute();
    $focus = $stmt->get_result()->fetch_assoc();
}

// Oldest unresolved report, so a slow queue is obvious.
$oldestOpen = $db->query("
    SELECT DATEDIFF(NOW(), created_at) AS age
    FROM reports
    WHERE status IN ('open', 'under_review')
    ORDER BY created_at ASC LIMIT 1
")->fetch_assoc();
$oldestOpenDays = $oldestOpen ? (int) $oldestOpen['age'] : null;

$queryParams = [];
foreach (['q' => $search, 'status' => $statusFilter] as $k => $v) {
    if ($v !== '') {
        $queryParams[$k] = $v;
    }
}
if ($clubFilter > 0) {
    $queryParams['club'] = $clubFilter;
}
if ($eventFilter > 0) {
    $queryParams['event'] = $eventFilter;
}
$queueBase = '/admin/reports?view=queue' . (count($queryParams) > 0 ? '&' . http_build_query($queryParams) : '');

$baseUrl = $queueBase;

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">

        <div class="page-header">
            <div>
                <h1 class="page-title">Reports</h1>
                <p class="page-subtitle"><?= $view === 'queue'
                        ? 'Student issue reports submitted to the system administrator.'
                        : 'Analytics and activity across the platform.' ?></p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <?= $openTotal ?> Awaiting action
            </div>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        if (count($errors) > 0):
            $alertType = 'error';
            $alertMessage = implode(' ', $errors);
            require BASE_PATH . '/app/components/alert.php';
        endif;
        ?>

        <div class="tabs mb-6">
            <a href="<?= url('/admin/reports?view=analytics') ?>" class="tab <?= $view === 'analytics' ? 'tab-active' : '' ?>">Analytics</a>
            <a href="<?= url('/admin/reports?view=queue') ?>" class="tab <?= $view === 'queue' ? 'tab-active' : '' ?>">
                Student Reports
                <?php if ($openTotal > 0): ?>
                <span class="ml-1.5 badge-danger"><?= $openTotal ?></span>
                <?php endif; ?>
            </a>
        </div>

<?php if ($view === 'queue'): ?>
<?php require BASE_PATH . '/app/views/admin/report-queue.php'; ?>
<?php else: ?>
<?php require BASE_PATH . '/app/views/admin/report-analytics.php'; ?>
<?php endif; ?>

    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>
</div>
