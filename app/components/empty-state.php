<?php
$emptyIcon = $emptyIcon ?? 'info';
$emptyTitle = $emptyTitle ?? 'Nothing here yet';
$emptyText = $emptyText ?? '';
$emptyHref = $emptyHref ?? null;
$emptyAction = $emptyAction ?? '';
$emptyActionClass = $emptyActionClass ?? 'btn-primary btn-sm';
?>

<div class="empty-state">
    <span class="empty-state-icon"><?= icon($emptyIcon, 'w-6 h-6 text-gray-400') ?></span>
    <h3 class="empty-state-title"><?= e($emptyTitle) ?></h3>
    <?php if ($emptyText !== ''): ?>
        <p class="empty-state-text"><?= e($emptyText) ?></p>
    <?php endif; ?>
    <?php if ($emptyAction !== '' && $emptyHref): ?>
        <a href="<?= e($emptyHref) ?>" class="<?= e($emptyActionClass) ?>"><?= e($emptyAction) ?></a>
    <?php elseif ($emptyAction !== ''): ?>
        <span class="<?= e($emptyActionClass) ?>"><?= e($emptyAction) ?></span>
    <?php endif; ?>
</div>