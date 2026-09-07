<aside class="hidden md:flex md:flex-shrink-0" data-sidebar>
    <div class="flex flex-col w-72 bg-gradient-to-b from-indigo-900 to-slate-900 text-white">
        <div class="flex items-center h-16 px-6 border-b border-white/10">
            <div class="w-9 h-9 rounded-lg bg-primary-500 flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <span class="font-bold text-lg block leading-tight"><?= e(APP_NAME) ?> Club</span>
            </div>
        </div>

        <div class="px-5 py-4 border-b border-white/10">
            <button class="w-full flex items-center justify-between px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-sm">
                <span>All Workspaces</span>
                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="<?= url('/club') ?>" class="flex items-center px-4 py-2.5 text-sm font-medium text-white/70 rounded-lg hover:bg-white/10 hover:text-white transition-colors <?= ($activePage ?? '') === 'overview' ? 'bg-white/10 text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
                Overview
            </a>

            <a href="<?= url('/club/events') ?>" class="flex items-center px-4 py-2.5 text-sm font-medium text-white/70 rounded-lg hover:bg-white/10 hover:text-white transition-colors <?= ($activePage ?? '') === 'events' ? 'bg-white/10 text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Events
            </a>

            <a href="<?= url('/club/tasks') ?>" class="flex items-center px-4 py-2.5 text-sm font-medium text-white/70 rounded-lg hover:bg-white/10 hover:text-white transition-colors <?= ($activePage ?? '') === 'tasks' ? 'bg-white/10 text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Tasks
            </a>

            <a href="<?= url('/club/settings') ?>" class="flex items-center px-4 py-2.5 text-sm font-medium text-white/70 rounded-lg hover:bg-white/10 hover:text-white transition-colors <?= ($activePage ?? '') === 'settings' ? 'bg-white/10 text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826 2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
        </nav>

        <div class="p-4 border-t border-white/10">
            <a href="<?= url('/logout') ?>" class="flex items-center px-4 py-2.5 text-sm font-medium text-white/60 rounded-lg hover:bg-white/10 hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </a>
        </div>
    </div>
</aside>
