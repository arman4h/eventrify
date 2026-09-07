<aside class="hidden md:flex md:flex-shrink-0" data-sidebar>
    <div class="flex flex-col w-64 bg-sidebar">
        <div class="flex items-center h-16 px-6 bg-sidebar-light">
            <svg class="w-8 h-8 text-primary-400 mr-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-2.09 5.42-3.07 7.02-.68 1.1-1.35 1.48-1.98 1.48-.36 0-.66-.27-.66-.6 0-.41.31-.72.69-.77.15-.02.3-.05.3-.05l-1.7-1.02L8.9 16.4c-.21.21-.54.21-.76.02-.21-.21-.2-.54.01-.76l3.9-3.93c.21-.22.54-.35.63-.13.09.22-.07.55-.34.78l-2.62 2.62 3.27 1.95c1.39-2.49 2.18-4.44 2.31-5.89.05-.53-.21-.87-.72-.86-.39 0-.71.3-.93.82-.3.7-.96 1.6-1.96 2.01-.88.36-2.18-.21-2.1-1.06.04-.42.47-.41.55-.4.05.01-.26-.48-.32-.9-.07-.48.28-.7.56-.7h.02c1.37.07 2.82-.61 2.82-2.29 0-.47-.33-.9-.82-.9-.07 0-.14.01.01.09-.46.23-1.15.92-1.49 1.88-.22.62-.4 1.73-.4 2.76-.89-.18-1.57-.46-1.99-.85-.77-.71-.9-2.2.32-3.03.6-.41 1.45-.53 2.19-.45.43.05.84-.04 1.12-.15-.51-.19-1.11-.28-1.7-.22-.9.08-1.79.42-2.4.91-1.81 1.44-1.62 3.66-.53 4.64.2.18.44.28.68.36.29.75.93 1.13 1.66 1.59l.13.62c-.71.12-1.46-.1-1.97.15-.68.33-1.11.93-1.44 1.58-.34-.14-.65-.48-.88-.86-1.48-2.47-1.19-5.48-1.19-5.48s-1.54.72-2.3 1.18c-.23.14-.45.33-.64.53-.47.49-.82 1.06-1.03 1.68-.13-.21-.25-.41-.37-.63C4.88 9.05 6.5 5.4 10.4 4.4c2.32-.6 4.73.09 6.28 1.9.58.68.94 1.5 1.1 2.37.19 1.02.28 2.1.1 3.13h-.01z"/>
            </svg>
            <span class="text-white font-bold text-xl"><?= e(APP_NAME) ?></span>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1">
            <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</p>

            <a href="<?= url('/admin') ?>" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-sidebar-light hover:text-white transition-colors <?= ($activePage ?? '') === 'dashboard' ? 'bg-sidebar-light text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
                Dashboard
            </a>

            <a href="<?= url('/admin/users') ?>" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-sidebar-light hover:text-white transition-colors <?= ($activePage ?? '') === 'users' ? 'bg-sidebar-light text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Users
            </a>

            <a href="<?= url('/admin/reports') ?>" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-sidebar-light hover:text-white transition-colors <?= ($activePage ?? '') === 'reports' ? 'bg-sidebar-light text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reports
            </a>

            <a href="<?= url('/admin/settings') ?>" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-sidebar-light hover:text-white transition-colors <?= ($activePage ?? '') === 'settings' ? 'bg-sidebar-light text-white' : '' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <a href="<?= url('/logout') ?>" class="flex items-center px-3 py-2 text-sm font-medium text-gray-400 rounded-lg hover:bg-sidebar-light hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </a>
        </div>
    </div>
</aside>
