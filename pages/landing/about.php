<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$pageTitle = 'About Eventrify';

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 py-14">
    <div class="max-w-2xl mx-auto text-center">
        <span class="badge-primary">About</span>
        <h1 class="mt-5 text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900">About Eventrify</h1>
        <p class="mt-4 text-gray-600 leading-relaxed">
            Eventrify is the centralized platform for discovering, managing, and participating in university
            club events — built by students, for students, as part of the university community.
        </p>
    </div>

    <div class="card p-6 sm:p-8 mt-12 max-w-3xl mx-auto">
        <p class="text-gray-600 leading-relaxed">
            From technical workshops and programming contests to cultural nights and seminars, every club on
            campus publishes its events in one shared space. Students can browse what's happening, register in a
            couple of minutes, and get verified attendance on the day — while clubs get a clean dashboard to
            manage capacity, waitlists, and check-in without drowning in spreadsheets.
        </p>
    </div>

    <section class="mt-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900">How it works</h2>
            <p class="text-sm text-gray-500 mt-2">Three simple steps between you and your next great campus experience.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card p-6 relative">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('search', 'w-5 h-5') ?></span>
                <span class="absolute top-6 right-6 text-4xl font-extrabold text-gray-100">1</span>
                <h3 class="font-semibold text-gray-900">Discover events</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Browse published events from every approved campus club, filter by category and date, and find what suits you.</p>
            </div>
            <div class="card p-6 relative">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('check-circle', 'w-5 h-5') ?></span>
                <span class="absolute top-6 right-6 text-4xl font-extrabold text-gray-100">2</span>
                <h3 class="font-semibold text-gray-900">Register in minutes</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Answer a short form, grab a seat, and keep your registration ID and QR handy for the event day.</p>
            </div>
            <div class="card p-6 relative">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('qr', 'w-5 h-5') ?></span>
                <span class="absolute top-6 right-6 text-4xl font-extrabold text-gray-100">3</span>
                <h3 class="font-semibold text-gray-900">Participate & get verified</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Attend the event, get checked in with your QR or Student ID, and earn verified attendance.</p>
            </div>
        </div>
    </section>

    <section class="mt-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900">For everyone</h2>
            <p class="text-sm text-gray-500 mt-2">One platform that works for the whole campus.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card p-6">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('user', 'w-5 h-5') ?></span>
                <h3 class="font-semibold text-gray-900">Students</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Find events across every club, register in seconds, and track your registered and attended activities.</p>
            </div>
            <div class="card p-6">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('grad', 'w-5 h-5') ?></span>
                <h3 class="font-semibold text-gray-900">Verified Clubs</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Publish events, build custom registration forms, manage participants, and run attendance from one dashboard.</p>
            </div>
            <div class="card p-6">
                <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-4"><?= icon('shield', 'w-5 h-5') ?></span>
                <h3 class="font-semibold text-gray-900">Admins</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Review club requests, keep the community verified, and monitor activity across clubs and events.</p>
            </div>
        </div>
    </section>

    <section id="contact" class="mt-16 max-w-3xl mx-auto">
        <div class="card p-8 text-center">
            <span class="inline-flex w-12 h-12 rounded-full bg-blue-50 text-blue-600 items-center justify-center mx-auto mb-4"><?= icon('mail', 'w-6 h-6') ?></span>
            <h2 class="text-2xl font-bold text-gray-900">Get in touch</h2>
            <p class="text-sm text-gray-500 mt-2">Questions, feedback, or ideas? We'd love to hear from you.</p>
            <a href="mailto:contact@eventrify.com" class="btn-primary mt-5">
                <?= icon('mail', 'w-4 h-4') ?>
                contact@eventrify.com
            </a>
            <p class="text-xs text-gray-400 mt-4">Part of the university community.</p>
        </div>
    </section>
</div>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>