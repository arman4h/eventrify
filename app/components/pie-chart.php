<?php
/**
 * Doughnut chart.
 *
 * Drawn with SVG stroke-dasharray rather than a charting library, because the
 * project ships no JS build step or vendor directory and the existing charts
 * are plain CSS. The values are server-rendered, so the chart needs no
 * JavaScript to appear.
 *
 * Expects:
 *   $pieSlices  list of ['label' => string, 'value' => int, 'color' => '#rrggbb']
 *   $pieTotal   optional; summed from the slices when omitted
 *   $pieCenter  optional headline drawn in the hole
 *   $pieCaption optional line under the headline
 */

$pieSlices = $pieSlices ?? [];
$pieTotal  = isset($pieTotal)
    ? (int) $pieTotal
    : array_sum(array_map(static fn(array $s): int => (int) $s['value'], $pieSlices));
$pieCenter  = $pieCenter ?? null;
$pieCaption = $pieCaption ?? null;

$r            = 40;
$circumference = 2 * M_PI * $r;
$hasData      = $pieTotal > 0;

// Offset accumulates so each slice starts where the previous one ended.
$offset = 0.0;
$arcs   = [];

foreach ($pieSlices as $slice) {
    $value = (int) ($slice['value'] ?? 0);
    if ($value < 1) {
        continue;
    }

    $length = $hasData ? ($value / $pieTotal) * $circumference : 0.0;

    $arcs[] = [
        'color'  => (string) ($slice['color'] ?? '#9ca3af'),
        'label'  => (string) ($slice['label'] ?? ''),
        'value'  => $value,
        'dash'   => sprintf('%.2f %.2f', $length, $circumference - $length),
        'offset' => -$offset,
    ];

    $offset += $length;
}

$ariaLabel = $hasData
    ? 'Distribution: ' . implode(', ', array_map(
        static fn(array $s): string => sprintf('%s %d (%.0f%%)', $s['label'], $s['value'], ($s['value'] / $pieTotal) * 100),
        $arcs
    ))
    : 'No data yet';
?>

<div class="flex flex-col sm:flex-row items-center gap-6">
    <div class="relative shrink-0 w-40 h-40">
        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90" role="img" aria-label="<?= e($ariaLabel) ?>">
            <circle cx="50" cy="50" r="<?= $r ?>" fill="none"
                    stroke="#e5e7eb" stroke-width="14" />

            <?php foreach ($arcs as $arc): ?>
                <circle cx="50" cy="50" r="<?= $r ?>" fill="none"
                        stroke="<?= e($arc['color']) ?>"
                        stroke-width="14"
                        stroke-dasharray="<?= $arc['dash'] ?>"
                        stroke-dashoffset="<?= sprintf('%.2f', $arc['offset']) ?>">
                    <title><?= e($arc['label']) ?>: <?= (int) $arc['value'] ?></title>
                </circle>
            <?php endforeach; ?>
        </svg>

        <?php if ($pieCenter !== null || $pieCaption !== null): ?>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4">
                <?php if ($pieCenter !== null): ?>
                    <span class="text-2xl font-bold text-gray-900 leading-none"><?= e((string) $pieCenter) ?></span>
                <?php endif; ?>
                <?php if ($pieCaption !== null): ?>
                    <span class="text-xs text-gray-500 mt-1"><?= e($pieCaption) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($pieSlices): ?>
        <ul class="w-full space-y-2.5">
            <?php foreach ($pieSlices as $slice): ?>
                <?php
                $value = (int) ($slice['value'] ?? 0);
                $pct   = $hasData ? ($value / $pieTotal) * 100 : 0;
                ?>
                <li class="flex items-center justify-between gap-3 text-sm">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-sm shrink-0"
                              style="background-color: <?= e((string) ($slice['color'] ?? '#9ca3af')) ?>"></span>
                        <span class="text-gray-600 truncate"><?= e((string) ($slice['label'] ?? '')) ?></span>
                    </span>
                    <span class="flex items-baseline gap-1.5 shrink-0">
                        <span class="font-medium text-gray-900"><?= $value ?></span>
                        <span class="text-xs text-gray-400 tabular-nums"><?= round($pct) ?>%</span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
