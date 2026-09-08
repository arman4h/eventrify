<div class="relative z-10 flex-shrink-0 flex h-16 bg-white border-b border-gray-200">
    <button type="button" class="px-4 text-gray-500 focus:outline-none md:hidden" data-sidebar-toggle>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div class="flex-1 px-4 flex justify-between items-center">
        <div class="flex items-center">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 mr-3">Workspace</span>
            <h1 class="text-lg font-semibold text-gray-900"><?= e($pageTitle ?? 'Workspace') ?></h1>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative">
                <input type="text" placeholder="Search events..." class="rounded-full bg-gray-100 border-0 px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500 w-56 focus:bg-white">
                <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <button class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-semibold hover:bg-indigo-700 transition-colors">
                <?= substr(e(($_SESSION['user']['name'] ?? 'U')), 0, 1) ?>
            </button>
        </div>
    </div>
</div>
