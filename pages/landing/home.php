<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$pageTitle = 'Eventrify — University Club Events';

$recentEvents = $db->query("
    SELECT e.*, c.club_name
    FROM events e
    JOIN clubs c ON c.club_id = e.club_id
    WHERE e.status = 'published'
    ORDER BY e.start_time DESC
    LIMIT 6
");

$regCounts = [];
$countResult = $db->query("
    SELECT event_id, COUNT(*) AS cnt
    FROM event_registrations
    WHERE status IN ('registered', 'attended')
    GROUP BY event_id
");
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $regCounts[$row['event_id']] = (int) $row['cnt'];
    }
}

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<section class="bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-24 text-center">
        <span class="badge-primary">University Club Events Platform</span>

        <h1 class="mt-6 text-4xl sm:text-5xl font-extrabold tracking-tight text-gray-900">
            Discover. Register. Participate.
        </h1>

        <p class="mt-5 text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
            Find university events, workshops, competitions, and activities from clubs across campus — all in one place.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?= url('/events') ?>" class="btn-primary btn-lg w-full sm:w-auto">
                <?= icon('calendar', 'w-5 h-5') ?>
                Explore Events
            </a>
            <a href="<?= url('/club/register') ?>" class="btn-secondary btn-lg w-full sm:w-auto">
                <?= icon('plus', 'w-5 h-5') ?>
                Join as a Club
            </a>
        </div>

        <div class="mx-auto max-w-5xl mt-14 text-left">
            <div class="card overflow-hidden shadow-xl border-gray-200">
                <div class="flex items-center gap-1.5 px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    <span class="ml-3 flex-1 max-w-xs mx-auto inline-flex items-center justify-center rounded-md bg-white border border-gray-200 px-3 py-1 text-xs text-gray-400">
                        eventrify.edu / /events
                    </span>
                </div>

                <div class="p-5 sm:p-7">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Upcoming on campus</p>
                            <p class="text-xs text-gray-500 mt-0.5">Live registrations from university clubs</p>
                        </div>
                        <a href="<?= url('/events') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex w-9 h-9 rounded-lg bg-blue-50 text-blue-600 items-center justify-center"><?= icon('ticket', 'w-4 h-4') ?></span>
                                <span class="badge-primary">Workshop</span>
                            </div>
                            <p class="font-semibold text-gray-900 text-sm">Intro to AI Workshop</p>
                            <p class="text-xs text-gray-500 mt-0.5">Library Building, Lab 203</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-500">Oct 05, 2026</span>
                                <span class="badge-success">Open</span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 items-center justify-center"><?= icon('users', 'w-4 h-4') ?></span>
                                <span class="badge-primary">Competition</span>
                            </div>
                            <p class="font-semibold text-gray-900 text-sm">Inter-University Robotics</p>
                            <p class="text-xs text-gray-500 mt-0.5">Academic Building 3</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-500">Nov 14, 2026</span>
                                <span class="badge-warning">Filling fast</span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex w-9 h-9 rounded-lg bg-sky-50 text-sky-600 items-center justify-center"><?= icon('laptop', 'w-4 h-4') ?></span>
                                <span class="badge-primary">Bootcamp</span>
                            </div>
                            <p class="font-semibold text-gray-900 text-sm">Web Development Bootcamp</p>
                            <p class="text-xs text-gray-500 mt-0.5">Academic Building 1</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-500">Oct 16, 2026</span>
                                <span class="badge-success">Open</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="why" class="bg-gray-50 py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">Why Eventrify?</h2>
            <p class="text-sm text-gray-500 mt-3">Everything you need to run and join campus events, without the spreadsheet chaos.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="card p-6">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('calendar', 'w-5 h-5') ?></span>
                <h3 class="font-semibold text-gray-900">All Events in One Place</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Discover events from different university clubs from a single platform.</p>
            </div>

            <div class="card p-6">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('check-circle', 'w-5 h-5') ?></span>
                <h3 class="font-semibold text-gray-900">Smart Registration</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Real-time seat availability, registration limits, and waitlists.</p>
            </div>

            <div class="card p-6">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('clipboard', 'w-5 h-5') ?></span>
                <h3 class="font-semibold text-gray-900">Simple Event Management</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Clubs can manage registrations, attendees, and event operations from one dashboard.</p>
            </div>

            <div class="card p-6">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('qr', 'w-5 h-5') ?></span>
                <h3 class="font-semibold text-gray-900">Easy Check-in</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Verify attendance using QR codes or Student IDs.</p>
            </div>
        </div>
    </div>
</section>

<section id="recent" class="bg-white pb-16 sm:pb-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-8">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">Recent Events</h2>
                <p class="text-sm text-gray-500 mt-2">The latest published events from clubs across campus.</p>
            </div>
        </div>

        <?php if ($recentEvents && $recentEvents->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($event = $recentEvents->fetch_assoc()): ?>
                    <?php
                    $capacity = (int) $event['capacity'];
                    $count = $regCounts[$event['event_id']] ?? 0;
                    $isFull = $capacity > 0 && $count >= $capacity;
                    $deadlinePassed = $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
                    $closed = $event['status'] === 'cancelled' || $deadlinePassed;

                    if ($isFull) {
                        $statusBadgeClass = 'badge-danger';
                        $statusBadgeText = 'Full';
                    } elseif ($closed) {
                        $statusBadgeClass = 'badge-neutral';
                        $statusBadgeText = 'Registration Closed';
                    } else {
                        $statusBadgeClass = 'badge-success';
                        $statusBadgeText = 'Registration Open';
                    }
                    ?>
                    <article class="card-hover overflow-hidden flex flex-col">
                        <div class="h-36 bg-gray-100 flex items-center justify-center text-gray-400 overflow-hidden">
                            <?php if (!empty($event['poster'])): ?>
                                <img src="<?= e($event['poster']) ?>" alt="<?= e($event['title']) ?> poster" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= icon('calendar', 'w-10 h-10') ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="badge-primary"><?= e($event['category'] ?? 'Event') ?></span>
                                <span class="<?= $statusBadgeClass ?>"><?= e($statusBadgeText) ?></span>
                            </div>
                            <h3 class="font-semibold text-gray-900 leading-snug">
                                <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="hover:text-blue-600 transition-colors"><?= e($event['title']) ?></a>
                            </h3>
                            <p class="text-sm text-gray-500 mt-1"><?= e($event['club_name']) ?></p>

                            <div class="mt-3 space-y-1.5 text-sm text-gray-500">
                                <p class="flex items-center gap-2">
                                    <?= icon('calendar', 'w-4 h-4 text-gray-400') ?>
                                    <span><?= formatDate($event['start_time']) ?></span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <?= icon('clock', 'w-4 h-4 text-gray-400') ?>
                                    <span><?= date('g:i A', strtotime($event['start_time'])) ?></span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <?= icon('map-pin', 'w-4 h-4 text-gray-400') ?>
                                    <span class="truncate"><?= e($event['venue']) ?></span>
                                </p>
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                                <?php if ($capacity > 0): ?>
                                    <?php if ($isFull): ?>
                                        <span class="badge-danger">Full</span>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500"><?= $count ?> / <?= $capacity ?> seats</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500">No seat limit</span>
                                <?php endif; ?>
                            </div>

                            <a href="<?= url('/event?event_id=' . $event['event_id']) ?>" class="btn-secondary btn-sm w-full mt-4">
                                View Event
                                <?= icon('arrow-right', 'w-4 h-4') ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <div class="mt-8 flex justify-center">
                <a href="<?= url('/events') ?>" class="btn-secondary">
                    View all events
                    <?= icon('arrow-right', 'w-4 h-4') ?>
                </a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="empty-state">
                    <span class="empty-state-icon"><?= icon('calendar', 'w-6 h-6') ?></span>
                    <h3 class="empty-state-title">No published events yet</h3>
                    <p class="empty-state-text">Clubs haven't published any events yet. Check back soon, or explore what's available.</p>
                    <a href="<?= url('/events') ?>" class="btn-secondary">Browse Events</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="clubs" class="bg-white py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div>
            <span class="badge-primary">For Clubs</span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900">
                Run your club events without the spreadsheet chaos.
            </h2>
            <p class="mt-4 text-gray-600 leading-relaxed max-w-lg">
                Eventrify gives verified clubs a dedicated dashboard to publish events, collect registrations, and manage attendance — so your executive team can focus on the event, not the admin.
            </p>
            <ul class="mt-6 space-y-3">
                <?php $clubBenefits = ['Create events with custom registration forms', 'Build registration forms', 'Manage participants and waitlists', 'Track attendance', 'Manage club executives']; ?>
                <?php foreach ($clubBenefits as $benefit): ?>
                    <li class="flex items-center gap-3 text-sm text-gray-700">
                        <span class="inline-flex w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 items-center justify-center shrink-0"><?= icon('check', 'w-3.5 h-3.5') ?></span>
                        <?= e($benefit) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= url('/club/register') ?>" class="btn-primary mt-8">
                Request Club Access
                <?= icon('arrow-right', 'w-4 h-4') ?>
            </a>
        </div>

        <div class="card overflow-hidden shadow-xl border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900">UIU Robotics Club Dashboard</p>
                    <p class="text-xs text-gray-500 mt-0.5">Manage your event operations</p>
                </div>
                <span class="badge-success">Live</span>
            </div>

            <div class="p-5 grid grid-cols-3 gap-3 border-b border-gray-200">
                <div class="rounded-lg border border-gray-200 p-3">
                    <p class="text-xs text-gray-500">Total Events</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">12</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3">
                    <p class="text-xs text-gray-500">Participants</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">486</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3">
                    <p class="text-xs text-gray-500">Attendance</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">92%</p>
                </div>
            </div>

            <div class="px-5 py-2">
                <?php $mockRows = [
                    ['title' => 'Inter-University Robotics Competition', 'date' => 'Nov 14, 2026', 'regs' => '164', 'badge' => 'badge-success', 'label' => 'Open'],
                    ['title' => 'Beginner Arduino Bootcamp', 'date' => 'Sep 28, 2026', 'regs' => '48', 'badge' => 'badge-warning', 'label' => 'Filling'],
                    ['title' => 'Intro to AI Workshop', 'date' => 'Oct 05, 2026', 'regs' => '80', 'badge' => 'badge-danger', 'label' => 'Full'],
                ]; ?>
                <?php foreach ($mockRows as $row): ?>
                    <div class="flex items-center justify-between gap-3 py-3 <?= $row !== end($mockRows) ? 'border-b border-gray-100' : '' ?>">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate"><?= e($row['title']) ?></p>
                            <p class="text-xs text-gray-500 mt-0.5"><?= e($row['date']) ?></p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs text-gray-500"><?= e($row['regs']) ?></span>
                            <span class="<?= $row['badge'] ?>"><?= e($row['label']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section id="about" class="bg-gray-50 py-16 sm:py-20">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 text-center">
        <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">About Eventrify</h2>
        <p class="mt-4 text-gray-600 leading-relaxed">
            Eventrify is the campus events platform built for university clubs. It brings event discovery, registration, check-in, and attendance tracking into one simple place — so students can find great events, and clubs can run them smoothly.
        </p>
        <a href="<?= url('/about') ?>" class="btn-secondary mt-8">
            Learn More About Eventrify
            <?= icon('arrow-right', 'w-4 h-4') ?>
        </a>
    </div>
</section>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>