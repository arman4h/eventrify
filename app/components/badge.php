<?php
$badgeType = $badgeType ?? 'neutral';
$badgeText = $badgeText ?? '';

$classes = [
    'primary'  => 'badge-primary',
    'success'  => 'badge-success',
    'danger'   => 'badge-danger',
    'warning'  => 'badge-warning',
    'info'     => 'badge-info',
    'neutral'  => 'badge-neutral',
];

$map = [
    'pending'      => ['warning', 'Pending'],
    'approved'     => ['success', 'Approved'],
    'rejected'     => ['danger', 'Rejected'],
    'published'    => ['success', 'Published'],
    'draft'        => ['neutral', 'Draft'],
    'full'         => ['danger', 'Full'],
    'cancelled'    => ['danger', 'Cancelled'],
    'completed'    => ['info', 'Completed'],
    'ongoing'      => ['primary', 'Ongoing'],
    'checked-in'   => ['success', 'Checked In'],
    'attended'     => ['success', 'Attended'],
    'registered'   => ['info', 'Registered'],
    'waitlisted'   => ['warning', 'Waitlisted'],
    'active'       => ['success', 'Active'],
    'inactive'     => ['neutral', 'Inactive'],
    'suspended'    => ['danger', 'Suspended'],
    'allocated'    => ['success', 'Allocated'],
    'open'         => ['info', 'Open'],
];

if (isset($map[strtolower($badgeType)])) {
    $badgeText = $badgeText ?: $map[strtolower($badgeType)][1];
    $classes = $classes[$map[strtolower($badgeType)][0]];
} else {
    $classes = $classes[$badgeType] ?? 'badge-neutral';
}
?>

<span class="<?= e($classes) ?>"><?= e($badgeText !== '' ? $badgeText : ucfirst($badgeType)) ?></span>