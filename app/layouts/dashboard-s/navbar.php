<div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-6">
    <div class="flex items-center gap-3">
        <button type="button" class="-ml-1 rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" data-sidebar-toggle aria-label="Toggle sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="text-base font-semibold text-gray-900"><?= e($pageTitle ?? 'Student Portal') ?></h1>
    </div>

    <div class="flex items-center gap-2 sm:gap-4">
        <div class="search-wrapper hidden md:block">
            <svg class="search-icon w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search events..." class="search-input w-56 !py-2">
        </div>

        <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Notifications">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </button>

        <div class="flex items-center gap-3 border-l border-gray-200 pl-3">
            <span class="avatar-sm" aria-hidden="true"><?= e(substr(currentUser()['name'] ?? 'S', 0, 1)) ?></span>
            <div class="hidden sm:block">
                <p class="text-sm font-medium text-gray-900 leading-tight"><?= e(currentUser()['name'] ?? 'Student') ?></p>
                <p class="text-xs text-gray-500 leading-tight"><?= e(currentUser()['department'] ?? 'Student') ?></p>
            </div>
        </div>
    </div>
</div>