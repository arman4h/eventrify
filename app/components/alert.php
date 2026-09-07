<?php
$alertType = $alertType ?? 'info';
$alertMessage = $alertMessage ?? '';

$styles = [
    'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
    'error'   => 'bg-red-50 border-red-200 text-red-800',
    'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
    'info'    => 'bg-blue-50 border-blue-200 text-blue-800',
];

$icons = [
    'success' => '✓',
    'error'   => '✕',
    'warning' => '!',
    'info'    => 'ℹ',
];
?>

<?php if ($alertMessage): ?>
<div class="mb-4 rounded-lg border px-4 py-3 text-sm <?= $styles[$alertType] ?? $styles['info'] ?>" data-auto-dismiss>
    <span class="font-semibold mr-2"><?= $icons[$alertType] ?? $icons['info'] ?></span>
    <?= e($alertMessage) ?>
</div>
<?php endif; ?>
