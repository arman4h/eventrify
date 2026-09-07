<?php
$totalPages = $totalPages ?? 1;
$currentPage = $currentPage ?? 1;
$baseUrl = $baseUrl ?? '/';
?>

<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex-1 flex justify-between sm:hidden">
        <a href="<?= e($baseUrl) ?>?page=<?= max(1, $currentPage - 1) ?>"
           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 <?= $currentPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
            Previous
        </a>
        <a href="<?= e($baseUrl) ?>?page=<?= min($totalPages, $currentPage + 1) ?>"
           class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
            Next
        </a>
    </div>
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Page <span class="font-medium"><?= $currentPage ?></span> of <span class="font-medium"><?= $totalPages ?></span>
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $currentPage): ?>
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-primary-600 z-10">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= e($baseUrl) ?>?page=<?= $i ?>"
                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>
