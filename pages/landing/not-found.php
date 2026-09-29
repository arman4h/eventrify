<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

http_response_code(404);

$pageTitle = 'Page not found';

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<div class="mx-auto max-w-3xl px-4 sm:px-6 py-20 sm:py-28 text-center">
    <p class="text-7xl sm:text-8xl font-extrabold tracking-tight text-blue-600">404</p>
    <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900">We couldn't find that page</h1>
    <p class="mt-4 text-gray-600 leading-relaxed">
        The link may be out of date, or the event or club you were looking for may have been removed.
        Here is the way back.
    </p>

    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="<?= url('/') ?>" class="btn-primary btn-lg w-full sm:w-auto">
            <?= icon('dashboard', 'w-5 h-5') ?>
            Back to home
        </a>
        <a href="<?= url('/events') ?>" class="btn-secondary btn-lg w-full sm:w-auto">
            <?= icon('calendar', 'w-5 h-5') ?>
            Explore events
        </a>
    </div>

    <?php if (isLoggedIn()): ?>
    <div class="mt-10 pt-8 border-t border-gray-200">
        <p class="text-sm text-gray-500 mb-4">Or jump straight back to your dashboard</p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            <?php if (isStudent()): ?>
                <a href="<?= url('/student') ?>" class="btn-secondary btn-sm">Student dashboard</a>
                <a href="<?= url('/student/reports') ?>" class="btn-secondary btn-sm">My reports</a>
            <?php elseif (isClubUser()): ?>
                <a href="<?= url('/club') ?>" class="btn-secondary btn-sm">Club dashboard</a>
                <a href="<?= url('/club/room-requests') ?>" class="btn-secondary btn-sm">Room requests</a>
            <?php elseif (isSystemAdmin()): ?>
                <a href="<?= url('/admin') ?>" class="btn-secondary btn-sm">Admin dashboard</a>
                <a href="<?= url('/admin/reports?view=queue') ?>" class="btn-secondary btn-sm">Report queue</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>
