<?php
$totalPages = $totalPages ?? 1;
$currentPage = $currentPage ?? 1;
$baseUrl = $baseUrl ?? '/';
?>

<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between gap-4 px-1 py-4">
    <p class="hidden sm:block text-sm text-gray-500">
        Page <span class="font-medium text-gray-900"><?= $currentPage ?></span> of <span class="font-medium text-gray-900"><?= $totalPages ?></span>
    </p>

    <div class="flex items-center gap-2">
        <?php $sep = str_contains($baseUrl, '?') ? '&' : '?'; ?>
        <a href="<?= e($baseUrl) ?><?= $sep ?>page=<?= max(1, $currentPage - 1) ?>"
           class="btn-secondary btn-sm <?= $currentPage <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
            <?= icon('chevron-left', 'w-4 h-4') ?>
            Previous
        </a>

        <div class="hidden sm:flex items-center gap-1">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $currentPage): ?>
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-sm font-semibold text-white"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e($baseUrl) ?><?= $sep ?>page=<?= $i ?>"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <a href="<?= e($baseUrl) ?><?= $sep ?>page=<?= min($totalPages, $currentPage + 1) ?>"
           class="btn-secondary btn-sm <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>">
            Next
            <?= icon('chevron-right', 'w-4 h-4') ?>
        </a>
    </div>
</div>
<?php endif; ?>