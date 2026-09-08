<?php
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex flex-col bg-gray-50">
    <header class="sticky top-0 z-20 bg-white border-b border-gray-200">
        <div class="mx-auto max-w-6xl px-4 h-16 flex items-center justify-between">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 font-bold text-xl text-gray-900">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary-600 text-white text-sm font-bold">E</span>
                <?= e(APP_NAME) ?>
            </a>
            <nav class="flex items-center gap-4">
                <a href="<?= url('/') ?>" class="text-sm font-medium text-gray-600 hover:text-gray-900">Events</a>
                <?php if (isLoggedIn()): ?>
                    <span class="text-sm text-gray-500">Hi, <?= e(currentUser()['name']) ?></span>
                    <?php if (isSystemAdmin()): ?>
                        <a href="<?= url('/admin') ?>" class="btn-primary">Dashboard</a>
                    <?php elseif (isClubUser()): ?>
                        <a href="<?= url('/club') ?>" class="btn-primary">Dashboard</a>
                    <?php else: ?>
                        <a href="<?= url('/logout') ?>" class="btn-secondary">Logout</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="btn-primary">Login</a>
                    <a href="<?= url('/register-student') ?>" class="btn-secondary">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="flex-1 w-full max-w-6xl mx-auto px-4 py-8">