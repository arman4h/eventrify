<div data-sidebar-overlay class="fixed inset-0 z-30 bg-gray-900/60 backdrop-blur-sm hidden lg:hidden"></div>

<aside class="sidebar w-64 -translate-x-full lg:translate-x-0" data-sidebar>
    <div class="flex h-16 items-center gap-2.5 border-b border-white/10 px-5">
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-lg font-bold">E</span>
        <div>
            <p class="text-sm font-bold leading-tight text-white">Eventrify</p>
            <p class="text-[11px] font-medium text-gray-400 leading-tight">Student Portal</p>
        </div>
    </div>

    <div class="border-b border-white/10 px-4 py-4">
        <div class="flex items-center gap-3 rounded-lg px-2">
            <span class="avatar-md !bg-blue-500 !text-white" aria-hidden="true"><?= e(substr(currentUser()['name'] ?? 'U', 0, 1)) ?></span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate"><?= e(currentUser()['name'] ?? 'Student') ?></p>
                <p class="text-xs text-gray-400 truncate"><?= e(currentUser()['university_id'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <p class="sidebar-section-title !text-gray-600">Account</p>

        <a href="<?= url('/student') ?>" class="sidebar-link <?= $activePage === 'overview' ? 'sidebar-link-active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
            Overview
        </a>

        <a href="<?= url('/student/profile') ?>" class="sidebar-link <?= $activePage === 'profile' ? 'sidebar-link-active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            My Profile
        </a>

        <a href="<?= url('/student/registrations') ?>" class="sidebar-link <?= $activePage === 'registrations' ? 'sidebar-link-active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            My Registrations
        </a>

        <a href="<?= url('/events') ?>" class="sidebar-link <?= $activePage === 'discover' ? 'sidebar-link-active' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Discover Events
        </a>
    </nav>

    <div class="border-t border-white/10 p-3">
        <a href="<?= url('/') ?>" class="sidebar-link mb-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            View Site
        </a>
        <a href="<?= url('/logout') ?>" class="sidebar-link">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
    </div>
</aside>