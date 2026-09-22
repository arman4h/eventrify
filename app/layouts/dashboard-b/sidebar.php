<?php
$clubName = 'Club';
if (isClubUser()) {
    $clubId = (int) (currentUser()['club_id'] ?? 0);
    if ($clubId > 0) {
        try {
            $cresult = $GLOBALS['db']->query("SELECT club_name FROM clubs WHERE club_id = " . (int) $clubId);
            if ($cresult && $crow = $cresult->fetch_assoc()) {
                $clubName = $crow['club_name'];
            }
        } catch (Throwable $e) {}
    }
}
?>
<div data-sidebar-overlay class="fixed inset-0 z-30 bg-gray-900/60 backdrop-blur-sm hidden lg:hidden"></div>

<aside class="bg-white border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transition-transform duration-300 lg:translate-x-0 lg:static lg:z-auto" data-sidebar>
    <div class="flex h-16 items-center gap-2.5 border-b border-gray-200 px-5">
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-lg font-bold">E</span>
        <div class="min-w-0">
            <p class="text-sm font-bold leading-tight text-gray-900 truncate">Eventrify</p>
            <p class="text-xs font-medium text-gray-500 leading-tight truncate"><?= e($clubName) ?></p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Manage</p>

        <a href="<?= url('/club') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'overview' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
            Overview
        </a>

        <?php if (clubCanAccess('events')): ?>
        <a href="<?= url('/club/events') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'events' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Events
        </a>
        <?php endif; ?>

        <?php if (clubCanAccess('registrations')): ?>
        <a href="<?= url('/club/registrations') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'registrations' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Registrations
        </a>
        <?php endif; ?>

        <?php if (clubCanAccess('attendance')): ?>
        <a href="<?= url('/club/attendance') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'attendance' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Attendance
        </a>
        <?php endif; ?>

        <?php if (clubCanAccess('members')): ?>
        <a href="<?= url('/club/members') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'members' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-2.209 2.239-4 5-4s5 1.791 5 4m-5-10a3 3 0 100-6 3 3 0 000 6z"/></svg>
            User
        </a>
        <?php endif; ?>

        <?php if (clubCanAccess('room_requests')): ?>
        <a href="<?= url('/club/room-requests') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'room-requests' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Room Requests
        </a>
        <?php endif; ?>

        <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Insights</p>

        <?php if (clubCanAccess('reports')): ?>
        <a href="<?= url('/club/reports') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'reports' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reports
        </a>
        <?php endif; ?>

        <?php if (clubCanAccess('club_profile')): ?>
        <a href="<?= url('/club/settings') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activePage === 'settings' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            Club Settings
        </a>
        <?php endif; ?>
    </nav>

    <div class="border-t border-gray-200 p-3">
        <a href="<?= url('/') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 mb-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            View Site
        </a>
        <a href="<?= url('/logout') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
    </div>
</aside>