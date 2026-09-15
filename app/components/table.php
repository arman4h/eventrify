<?php
$columns = $columns ?? [];
$rows = $rows ?? [];
$emptyMessage = $emptyMessage ?? 'No records found.';
?>

<div class="table-wrapper">
    <table class="table">
        <?php if (!empty($columns)): ?>
        <thead>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th><?= e($col) ?></th>
                <?php endforeach; ?>
                <?php if (!empty($actionSlot)): ?>
                    <th class="text-right"><?= e($actionSlot) ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <?php endif; ?>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= count($columns) + (!empty($actionSlot) ? 1 : 0) ?>" class="py-12">
                        <div class="empty-state">
                            <span class="empty-state-icon"><?= icon('info', 'w-6 h-6 text-gray-400') ?></span>
                            <p class="empty-state-title text-sm"><?= e($emptyMessage) ?></p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= $cell ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>