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
    <?php
    // Newline-separated messages render as a list so a form can report every
    // problem at once instead of one run-on sentence.
    $alertLines = array_values(array_filter(
        array_map('trim', preg_split('/\R/', $alertMessage) ?: []),
        static fn(string $line): bool => $line !== ''
    ));
    ?>
<div class="mb-4 <?= $styles[$alertType] ?? $styles['info'] ?>" role="alert" data-auto-dismiss>
    <div class="flex items-start gap-3">
        <span class="mt-0.5 shrink-0"><?= icon($icons[$alertType] ?? 'info', 'w-5 h-5') ?></span>
        <?php if (count($alertLines) > 1): ?>
        <ul class="text-sm list-disc list-inside space-y-1">
            <?php foreach ($alertLines as $alertLine): ?>
            <li><?= e($alertLine) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="text-sm"><?= e($alertLines[0] ?? $alertMessage) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
