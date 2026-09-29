<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

$pageTitle = 'Help Centre';

$faqs = [
    [
        'q' => 'How do I register for an event?',
        'a' => 'Open the event from the Explore page and fill in the registration form. Some events need extra questions set by the club. '
            . 'If you log in first, your name, email and student ID are filled in for you. When an event is full you are added to a waitlist '
            . 'automatically and promoted when somebody cancels.',
    ],
    [
        'q' => 'I did not get a confirmation email — what now?',
        'a' => 'Eventrify does not send email automatically. Your registration is recorded the moment you submit the form, and you can see it '
            . 'under My Registrations in your student dashboard. Bring your student ID to check-in.',
    ],
    [
        'q' => 'Something is wrong with an event. Who do I tell?',
        'a' => 'Contact the club first — they organise the event, so they can usually fix it fastest. Use the "Contact the club" button on the '
            . 'event page. If the club cannot help or does not reply, use the "Report to administrator" button, or go to Report a Problem in your '
            . 'student dashboard. That report goes straight to the system administrator and you can follow its status on My Reports.',
    ],
    [
        'q' => 'How long does a report take?',
        'a' => 'The administration moves each report through Open, Under review, then Resolved or Dismissed. The status is visible on My Reports '
            . 'at all times. If a report has been open for several days, the club is emailed directly.',
    ],
    [
        'q' => 'How do I book a room?',
        'a' => 'Verified clubs can request a room from the club dashboard under Room Requests. Pick the event, the date, and one or two time slots. '
            . 'A 2:00 PM to 4:00 PM event needs two slots, for example 1:50 – 3:10 PM and 3:10 – 4:30 PM. Use "Recommend a room" and Eventrify will '
            . 'suggest the best free room for you, and the availability grid shows what is already taken that day.',
    ],
    [
        'q' => 'Can my club request more than one slot?',
        'a' => 'Yes, up to two slots per request. Two adjacent slots give you one continuous block of time, and both slots are approved or declined '
            . 'together by the administration.',
    ],
    [
        'q' => 'How do I register my club?',
        'a' => 'Use Register a Club and fill in the club details. You choose a password during the application, and the account stays locked until the '
            . 'system administrator approves it. You will see a Pending Review screen and an application reference while you wait.',
    ],
    [
        'q' => 'As a club executive, why can I not open some pages?',
        'a' => 'Owners and admins have access to everything. Executives can be given access to all sections or only to specific ones such as Events, '
            . 'Attendance or Registrations. Your club owner can change this at any time under Members.',
    ],
    [
        'q' => 'I forgot my password.',
        'a' => 'Password reset is handled by the system administrator for this deployment. Contact them with your registered email address and they '
            . 'will reset it.',
    ],
];

$pageTitle = 'Help Centre';

require BASE_PATH . '/app/layouts/landing/header.php';
?>

<div class="mx-auto max-w-4xl px-4 sm:px-6 py-12 sm:py-16">
    <nav class="breadcrumb">
        <a href="<?= url('/') ?>" class="breadcrumb-link">Home</a>
        <span>/</span>
        <span class="breadcrumb-current">Help Centre</span>
    </nav>

    <div class="text-center max-w-2xl mx-auto">
        <span class="badge-primary">Support</span>
        <h1 class="mt-5 text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900">How can we help?</h1>
        <p class="mt-4 text-gray-600 leading-relaxed">
            Answers to the questions students and clubs ask most often. If your question is not here,
            contact the club first and escalate to the system administrator if they cannot help.
        </p>
    </div>

    <div class="mt-10 space-y-3" data-tabs>
        <?php foreach ($faqs as $index => $faq): ?>
        <details class="card overflow-hidden group" <?= $index === 0 ? 'open' : '' ?>>
            <summary class="flex items-center justify-between gap-4 px-5 py-4 cursor-pointer select-none hover:bg-gray-50 transition-colors">
                <span class="font-semibold text-gray-900"><?= e($faq['q']) ?></span>
                <span class="inline-flex w-6 h-6 rounded-full bg-gray-100 text-gray-500 items-center justify-center shrink-0 group-open:bg-blue-100 group-open:text-blue-700">
                    <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </summary>
            <div class="px-5 pb-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                <p class="pt-4"><?= e($faq['a']) ?></p>
            </div>
        </details>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-10">
        <a href="<?= url('/events') ?>" class="card p-5 hover:border-blue-300 transition-colors">
            <span class="inline-flex w-10 h-10 rounded-lg bg-blue-50 text-blue-600 items-center justify-center mb-3">
                <?= icon('calendar', 'w-5 h-5') ?>
            </span>
            <p class="font-semibold text-gray-900 text-sm">Browse events</p>
            <p class="text-xs text-gray-500 mt-1">See what is happening on campus.</p>
        </a>
        <?php if (isStudent()): ?>
        <a href="<?= url('/student/report') ?>" class="card p-5 hover:border-blue-300 transition-colors">
            <span class="inline-flex w-10 h-10 rounded-lg bg-amber-50 text-amber-600 items-center justify-center mb-3">
                <?= icon('shield', 'w-5 h-5') ?>
            </span>
            <p class="font-semibold text-gray-900 text-sm">Report a problem</p>
            <p class="text-xs text-gray-500 mt-1">Contact the club, or escalate to the administrator.</p>
        </a>
        <?php else: ?>
        <a href="<?= url('/login') ?>" class="card p-5 hover:border-blue-300 transition-colors">
            <span class="inline-flex w-10 h-10 rounded-lg bg-amber-50 text-amber-600 items-center justify-center mb-3">
                <?= icon('user', 'w-5 h-5') ?>
            </span>
            <p class="font-semibold text-gray-900 text-sm">Student sign in</p>
            <p class="text-xs text-gray-500 mt-1">Track registrations and report issues.</p>
        </a>
        <?php endif; ?>
        <a href="<?= url('/about') ?>" class="card p-5 hover:border-blue-300 transition-colors">
            <span class="inline-flex w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 items-center justify-center mb-3">
                <?= icon('info', 'w-5 h-5') ?>
            </span>
            <p class="font-semibold text-gray-900 text-sm">About Eventrify</p>
            <p class="text-xs text-gray-500 mt-1">What the platform does and who uses it.</p>
        </a>
    </div>
</div>

<?php require BASE_PATH . '/app/layouts/landing/footer.php'; ?>
