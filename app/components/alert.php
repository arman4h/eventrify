<?php
$alertType = $alertType ?? 'info';
$alertMessage = $alertMessage ?? '';

$styles = [
    'success' => 'alert-success',
    'error'   => 'alert-error',
    'warning' => 'alert-warning',
    'info'    => 'alert-info',
];

$icons = [
    'success' => 'check-circle',
    'error'   => 'x-circle',
    'warning' => 'alert',
    'info'    => 'info',
];
?>

<?php if ($alertMessage !== ''): ?>
<div class="mb-4 <?= $styles[$alertType] ?? $styles['info'] ?>" role="alert" data-auto-dismiss>
    <div class="flex items-start gap-3">
        <span class="mt-0.5 shrink-0"><?= icon($icons[$alertType] ?? 'info', 'w-5 h-5') ?></span>
        <p class="text-sm"><?= e($alertMessage) ?></p>
    </div>
</div>
<?php endif; ?>