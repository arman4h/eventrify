<div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-6">
    <div class="flex items-center gap-3">
        <button type="button" class="-ml-1 rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" data-sidebar-toggle aria-label="Toggle sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="text-base font-semibold text-gray-900"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    </div>

    <div class="flex items-center gap-2 sm:gap-4">
        <div class="search-wrapper hidden lg:block">
            <svg class="search-icon w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search..." class="search-input w-56 !py-2">
        </div>

        <div class="dropdown">
            <button type="button" class="flex items-center gap-3 rounded-lg p-1.5 text-left transition-colors hover:bg-gray-50" data-dropdown-toggle aria-label="Account menu">
                <span class="avatar-sm" aria-hidden="true"><?= e(substr(currentUser()['name'] ?? 'A', 0, 1)) ?></span>
                <span class="hidden sm:block">
                    <span class="block text-sm font-medium leading-tight text-gray-900"><?= e(currentUser()['name'] ?? 'Admin') ?></span>
                    <span class="block text-xs leading-tight text-gray-500">System Administrator</span>
                </span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="dropdown-menu hidden">
                <a href="<?= url('/admin/settings') ?>" class="dropdown-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    System Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= url('/logout') ?>" class="dropdown-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>