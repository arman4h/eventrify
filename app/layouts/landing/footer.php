</main>

    <footer class="border-t border-gray-200 bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-12">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div class="col-span-2 md:col-span-1">
                    <a href="<?= url('/') ?>" class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-lg font-bold">E</span>
                        <span class="text-xl font-bold tracking-tight text-gray-900">Eventrify</span>
                    </a>
                    <p class="text-sm text-gray-500 mt-3 leading-relaxed">
                        The centralized platform for discovering, managing, and participating in university club events.
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Explore</h4>
                    <ul class="space-y-2">
                        <li><a href="<?= url('/events') ?>" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Explore Events</a></li>
                        <li><a href="<?= url('/') ?>#clubs" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Club Requests</a></li>
                        <li><a href="<?= url('/about') ?>" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">About</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Platform</h4>
                    <ul class="space-y-2">
                        <li><a href="<?= url('/club/register') ?>" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">For Clubs</a></li>
                        <li><a href="<?= url('/login') ?>" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Student Login</a></li>
                        <li><a href="<?= url('/about') ?>#contact" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-sm text-gray-500">© <?= date('Y') ?> Eventrify. All rights reserved.</p>
                <p class="text-sm text-gray-400">Made for campus communities.</p>
            </div>
        </div>
    </footer>

    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>