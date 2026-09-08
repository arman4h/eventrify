<?php
$buttonType = $buttonType ?? 'button';
$buttonClass = $buttonClass ?? 'btn-primary';
$buttonText = $buttonText ?? 'Submit';
$buttonHref = $buttonHref ?? null;
$buttonExtra = $buttonExtra ?? '';
?>

<?php if ($buttonHref): ?>
<a href="<?= e($buttonHref) ?>" class="<?= e($buttonClass) ?>" <?= $buttonExtra ?>><?= e($buttonText) ?></a>
<?php else: ?>
<button type="<?= e($buttonType) ?>" class="<?= e($buttonClass) ?>" <?= $buttonExtra ?>><?= e($buttonText) ?></button>
<?php endif; ?>
