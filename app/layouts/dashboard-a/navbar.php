<div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
    <button type="button" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none md:hidden" data-sidebar-toggle>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div class="flex-1 px-4 flex justify-between items-center">
        <h1 class="text-lg font-semibold text-gray-900"><?= e($pageTitle ?? 'Dashboard') ?></h1>

        <div class="flex items-center space-x-4">
            <div class="relative hidden sm:block">
                <input type="text" placeholder="Search..." class="input w-64 !py-1.5">
                <svg class="absolute right-3 top-2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <button class="relative p-1 text-gray-400 hover:text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </button>

            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-sm font-semibold">
                    <?= substr(e(($_SESSION['user']['name'] ?? 'A')), 0, 1) ?>
                </div>
                <div class="hidden sm:block">
                    <p class="text-sm font-medium text-gray-900"><?= e($_SESSION['user']['name'] ?? 'Admin') ?></p>
                    <p class="text-xs text-gray-500"><?= e($_SESSION['user']['role'] ?? 'user') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
