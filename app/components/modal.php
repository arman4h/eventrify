<?php
$modalId = $modalId ?? 'modal';
$modalTitle = $modalTitle ?? 'Modal';
$modalBody = $modalBody ?? '';
?>

<div id="<?= e($modalId) ?>" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-label="<?= e($modalTitle) ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?= e($modalTitle) ?></h3>
            <button type="button" data-modal-dismiss="<?= e($modalId) ?>" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <?= icon('x', 'w-5 h-5') ?>
            </button>
        </div>
        <div class="modal-body">
            <?= $modalBody ?>
        </div>
    </div>
</div>