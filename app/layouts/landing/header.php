<?php
$pageTitle = $pageTitle ?? APP_NAME;

$navLinks = [
    ['label' => 'Explore Events', 'url' => url('/events')],
    ['label' => 'About',          'url' => url('/about')],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex flex-col bg-white text-gray-900">
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= url('/') ?>" class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-lg font-bold">E</span>
                <span class="text-xl font-bold tracking-tight text-gray-900">Eventrify</span>
            </a>

            <nav class="hidden md:flex items-center gap-8">
                <?php foreach ($navLinks as $link): ?>
                    <a href="<?= e($link['url']) ?>" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"><?= e($link['label']) ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button type="button" data-dropdown-toggle class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 hover:bg-gray-100 transition-colors">
                            <span class="avatar-sm" aria-hidden="true"><?= e(substr(currentUser()['name'] ?? 'U', 0, 1)) ?></span>
                            <span class="text-sm font-medium text-gray-700"><?= e(currentUser()['name']) ?></span>
                            <?= icon('chevron-down', 'w-4 h-4 text-gray-400') ?>
                        </button>
                        <div class="dropdown-menu hidden">
                            <?php if (isStudent()): ?>
                                <a href="<?= url('/student/profile') ?>" class="dropdown-item"><?= icon('user', 'w-4 h-4') ?> My Profile</a>
                                <a href="<?= url('/student') ?>" class="dropdown-item"><?= icon('dashboard', 'w-4 h-4') ?> Dashboard</a>
                            <?php elseif (isClubUser()): ?>
                                <a href="<?= url('/club') ?>" class="dropdown-item"><?= icon('dashboard', 'w-4 h-4') ?> Dashboard</a>
                            <?php elseif (isSystemAdmin()): ?>
                                <a href="<?= url('/admin') ?>" class="dropdown-item"><?= icon('dashboard', 'w-4 h-4') ?> Dashboard</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= url('/logout') ?>" class="dropdown-item"><?= icon('logout', 'w-4 h-4') ?> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="btn-ghost btn-sm">Login</a>
                    <a href="<?= url('/club/register') ?>" class="btn-primary btn-sm">Club Registration</a>
                <?php endif; ?>
            </div>

            <button type="button" data-mobile-menu-toggle class="md:hidden -mr-2 rounded-lg p-2 text-gray-600 hover:bg-gray-100" aria-label="Open menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        <div data-mobile-menu class="md:hidden hidden border-t border-gray-200 bg-white px-4 py-3">
            <nav class="flex flex-col gap-1">
                <?php foreach ($navLinks as $link): ?>
                    <a href="<?= e($link['url']) ?>" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"><?= e($link['label']) ?></a>
                <?php endforeach; ?>
                <div class="my-2 border-t border-gray-100"></div>
                <?php if (isLoggedIn()): ?>
                    <?php if (isStudent()): ?>
                        <a href="<?= url('/student/profile') ?>" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">My Profile</a>
                        <a href="<?= url('/student') ?>" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Dashboard</a>
                    <?php elseif (isClubUser()): ?>
                        <a href="<?= url('/club') ?>" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Dashboard</a>
                    <?php elseif (isSystemAdmin()): ?>
                        <a href="<?= url('/admin') ?>" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Dashboard</a>
                    <?php endif; ?>
                    <a href="<?= url('/logout') ?>" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Logout</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="btn-secondary btn-sm w-full">Login</a>
                    <a href="<?= url('/club/register') ?>" class="btn-primary btn-sm w-full mt-2">Club Registration</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-1">