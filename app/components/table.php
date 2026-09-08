<?php
$columns = $columns ?? [];
$rows = $rows ?? [];
$emptyMessage = $emptyMessage ?? 'No records found.';
?>

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <?php if (!empty($columns)): ?>
        <thead class="table-header">
            <tr>
                <?php foreach ($columns as $column): ?>
                    <th scope="col" class="px-6 py-3"><?= e($column) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php endif; ?>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= count($columns) ?: 1 ?>" class="px-6 py-8 text-center text-sm text-gray-500">
                        <?= e($emptyMessage) ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr class="hover:bg-gray-50">
                        <?php foreach ($row as $cell): ?>
                            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap"><?= $cell ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
