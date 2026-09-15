<div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-6">
    <div class="flex items-center gap-3">
        <button type="button" class="-ml-1 rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" data-sidebar-toggle aria-label="Toggle sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="text-base font-semibold text-gray-900"><?= e($pageTitle ?? 'Club Dashboard') ?></h1>
    </div>

    <div class="flex items-center gap-2 sm:gap-4">
        <div class="search-wrapper hidden lg:block">
            <svg class="search-icon w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search..." class="search-input w-48 !py-2">
        </div>

        <div class="dropdown">
            <button type="button" data-dropdown-toggle class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left hover:bg-gray-50 transition-colors">
                <span class="avatar-sm" aria-hidden="true"><?= e(substr(currentUser()['name'] ?? 'C', 0, 1)) ?></span>
                <span class="hidden sm:block min-w-0">
                    <span class="block text-sm font-medium text-gray-900 leading-tight truncate"><?= e(currentUser()['name'] ?? 'Club User') ?></span>
                    <span class="block text-xs text-gray-500 leading-tight truncate"><?= e($clubName) ?></span>
                </span>
                <?= icon('chevron-down', 'w-4 h-4 text-gray-400 shrink-0') ?>
            </button>

            <div class="dropdown-menu hidden">
                <a href="<?= url('/club/settings') ?>" class="dropdown-item">
                    <?= icon('settings', 'w-4 h-4') ?>
                    Club Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= url('/logout') ?>" class="dropdown-item">
                    <?= icon('logout', 'w-4 h-4') ?>
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>