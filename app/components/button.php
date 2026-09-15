<?php
$buttonType = $buttonType ?? 'button';
$buttonClass = $buttonClass ?? 'btn-primary';
$buttonText = $buttonText ?? 'Submit';
$buttonHref = $buttonHref ?? null;
$buttonExtra = $buttonExtra ?? '';
$buttonIcon = $buttonIcon ?? null;
?>

<?php if ($buttonHref): ?>
<a href="<?= e($buttonHref) ?>" class="<?= e($buttonClass) ?>" <?= $buttonExtra ?>>
    <?php if ($buttonIcon): ?><?= icon($buttonIcon, 'w-4 h-4') ?><?php endif; ?>
    <?= e($buttonText) ?>
</a>
<?php else: ?>
<button type="<?= e($buttonType) ?>" class="<?= e($buttonClass) ?>" <?= $buttonExtra ?>>
    <?php if ($buttonIcon): ?><?= icon($buttonIcon, 'w-4 h-4') ?><?php endif; ?>
    <?= e($buttonText) ?>
</button>
<?php endif; ?>