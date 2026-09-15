<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAdmin();

$pageTitle = 'Students';
$activePage = 'users';

$search       = get('q');
$deptFilter   = get('department');

$conditions = [];
$bindings   = [];
$types      = '';

if ($search !== '') {
    $conditions[] = "(s.full_name LIKE ? OR s.university_id LIKE ? OR s.email LIKE ?)";
    $bindings[]   = "%$search%";
    $bindings[]   = "%$search%";
    $bindings[]   = "%$search%";
    $types       .= 'sss';
}

if ($deptFilter !== '') {
    $conditions[] = "s.department = ?";
    $bindings[]   = $deptFilter;
    $types       .= 's';
}

$whereSql = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $db->prepare("SELECT COUNT(*) AS n FROM students s $whereSql");
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

$sql = "SELECT s.student_id, s.full_name, s.university_id, s.email, s.department, s.batch, s.is_active, s.created_at
        FROM students s
        $whereSql
        ORDER BY s.student_id DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$departments = [];
$deptRows = $db->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department <> '' ORDER BY department");
foreach ($deptRows as $deptRow) {
    $departments[] = $deptRow['department'];
}

$totalStudents = (int) $db->query("SELECT COUNT(*) AS n FROM students")->fetch_assoc()['n'];

$columns = ['Student', 'Student ID', 'Email', 'Department', 'Batch', 'Status', 'Joined'];

$rows = [];

foreach ($students as $student) {
    $isActive = (bool) $student['is_active'];

    $studentCell = '<div class="flex items-center gap-3">
        <span class="avatar-md shrink-0">' . e(strtoupper(substr($student['full_name'], 0, 1))) . '</span>
        <div class="min-w-0">
            <p class="font-medium text-gray-900 truncate">' . e($student['full_name']) . '</p>
        </div>
    </div>';

    $statusCell = '';
    $badgeType  = $isActive ? 'active' : 'suspended';
    ob_start();
    require BASE_PATH . '/app/components/badge.php';
    $statusCell = ob_get_clean();

    $rows[] = [
        $studentCell,
        '<span class="text-sm text-gray-600">' . e($student['university_id']) . '</span>',
        '<span class="text-sm text-gray-600 truncate max-w-xs block">' . e($student['email']) . '</span>',
        '<span class="text-sm text-gray-600">' . e($student['department'] ?? '—') . '</span>',
        '<span class="text-sm text-gray-600">' . e($student['batch'] ?? '—') . '</span>',
        $statusCell,
        formatDate($student['created_at'], 'M d, Y'),
        '<a href="#" class="btn-ghost btn-sm">View</a>',
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
                <h1 class="page-title">Students</h1>
                <p class="page-subtitle">Registered students on the platform.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <?= $totalStudents ?> Registered
            </div>
        </div>

        <?php
        $alertType = 'success'; $alertMessage = flash('success');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        $alertType = 'error'; $alertMessage = flash('error');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="card overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <form method="GET" action="<?= url('/admin/users') ?>" class="flex flex-col sm:flex-row gap-3">
                    <select name="department" class="select sm:w-56">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                        <option value="<?= e($department) ?>" <?= $deptFilter === $department ? 'selected' : '' ?>><?= e($department) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="flex flex-1 gap-3">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"><?= icon('search', 'w-4 h-4') ?></span>
                            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name, ID or email..." class="input pl-10">
                        </div>
                        <button type="submit" class="btn-secondary">Filter</button>
                        <?php if ($deptFilter !== '' || $search !== ''): ?>
                        <a href="<?= url('/admin/users') ?>" class="btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-6">
                <?php
                $actionSlot = 'Actions';
                $emptyMessage = 'No students found.';
                require BASE_PATH . '/app/components/table.php';

                $paginationQuery = $_GET;
                unset($paginationQuery['page']);
                $paginationBase = url('/admin/users') . (count($paginationQuery) > 0 ? '?' . http_build_query($paginationQuery) : '');

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