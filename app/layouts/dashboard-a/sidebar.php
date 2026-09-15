<div data-sidebar-overlay class="fixed inset-0 z-30 bg-gray-900/60 backdrop-blur-sm hidden lg:hidden"></div>

<aside class="flex w-64 flex-col border-r border-gray-200 bg-white fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform lg:static lg:z-auto lg:translate-x-0" data-sidebar>
    <div class="flex h-16 items-center gap-2.5 border-b border-gray-200 px-5">
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-lg font-bold">E</span>
        <div>
            <p class="text-sm font-bold leading-tight text-gray-900">Eventrify</p>
            <p class="text-xs text-gray-500 leading-tight">Administration</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Platform</p>

        <a href="<?= url('/admin') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'overview' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
            Overview
        </a>

        <a href="<?= url('/admin/club-requests') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'club-requests' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Club Requests
        </a>

        <a href="<?= url('/admin/clubs') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'clubs' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-2.209 2.239-4 5-4s5 1.791 5 4m-5-10a3 3 0 100-6 3 3 0 000 6zm6-2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Clubs
        </a>

        <a href="<?= url('/admin/events') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'events' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Event Manage
        </a>

        <a href="<?= url('/admin/users') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'users' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-2.209 2.239-4 5-4s5 1.791 5 4m-5-10a3 3 0 100-6 3 3 0 000 6z"/></svg>
            Users
        </a>

        <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Operations</p>

        <a href="<?= url('/admin/room-requests') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'room-requests' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM9 14h1m4 0h1"/></svg>
            Room Requests
        </a>

        <a href="<?= url('/admin/reports') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'reports' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reports
        </a>

        <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</p>

        <a href="<?= url('/admin/settings') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $activePage === 'settings' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            System Settings
        </a>
    </nav>

    <div class="border-t border-gray-200 p-3">
        <a href="<?= url('/') ?>" class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            View Site
        </a>
        <a href="<?= url('/logout') ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
    </div>
</aside>