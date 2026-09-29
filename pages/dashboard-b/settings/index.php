<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireClubAccess('club_profile');

$pageTitle = 'Settings';
$activePage = 'settings';

$clubId = (int) currentUser()['club_id'];

// Columns a club is allowed to edit. status / reviewed_by / reviewed_at /
// requested_by / club_id are decided by the system admin and stay read-only.
$editableFields = [
    'club_name'      => 'Club name',
    'university'     => 'University',
    'club_type'      => 'Club type',
    'established_year' => 'Established year',
    'description'    => 'Description',
    'official_email' => 'Official email',
    'website'        => 'Website',
    'facebook'       => 'Facebook page',
    'social_links'   => 'Social links',
    'logo'           => 'Logo',
];

$clubStmt = $db->prepare("SELECT * FROM clubs WHERE club_id = ?");
$clubStmt->bind_param('i', $clubId);
$clubStmt->execute();
$club = $clubStmt->get_result()->fetch_assoc() ?: [];

if (!$club) {
    $_SESSION['flash']['error'] = 'Club profile not found.';
    redirect('/club');
}

$errors = [];
$form = $club;

if (isPost()) {
    // View-only executives must not be able to write, even by hand-crafting a POST.
    requireClubManage('club_profile');

    foreach ($editableFields as $field => $label) {
        $form[$field] = trim(post($field));
    }

    vAdd($errors, vRequired($form['club_name'], 'Club name'));
    vAdd($errors, vMaxLen($form['club_name'], 100, 'Club name'));
    vAdd($errors, vRequired($form['university'], 'University'));
    vAdd($errors, vMaxLen($form['university'], 150, 'University'));
    vAdd($errors, vMaxLen($form['club_type'], 50, 'Club type'));

    if ($form['established_year'] !== '') {
        vAdd($errors, vInt($form['established_year'], 'Established year', 1900, (int) date('Y') + 1));
    } else {
        $form['established_year'] = null;
    }

    vAdd($errors, vMaxLen($form['description'], 2000, 'Description'));

    if ($form['official_email'] !== '') {
        vAdd($errors, vEmail($form['official_email'], 'Official email'));
        vAdd($errors, vMaxLen($form['official_email'], 100, 'Official email'));
    }
    if ($form['website'] !== '') {
        vAdd($errors, vUrl($form['website'], 'Website'));
        vAdd($errors, vMaxLen($form['website'], 255, 'Website'));
    }
    if ($form['facebook'] !== '') {
        vAdd($errors, vUrl($form['facebook'], 'Facebook page'));
        vAdd($errors, vMaxLen($form['facebook'], 255, 'Facebook page'));
    }
    vAdd($errors, vMaxLen($form['social_links'], 255, 'Social links'));
    vAdd($errors, vMaxLen($form['logo'], 255, 'Logo'));

    // Another approved club must not end up with the same name.
    $dupe = $db->prepare("
        SELECT club_id FROM clubs
        WHERE club_name = ? AND club_id <> ?
        LIMIT 1
    ");
    $dupe->bind_param('si', $form['club_name'], $clubId);
    $dupe->execute();
    if ($dupe->get_result()->fetch_assoc()) {
        $errors[] = 'Another club already uses that name.';
    }

    if ($errors) {
        $_SESSION['flash']['error'] = implode("\n", $errors);
        redirect('/club/settings');
    }

    $update = $db->prepare("
        UPDATE clubs SET
            club_name = ?, university = ?, club_type = ?, established_year = ?,
            description = ?, official_email = ?, website = ?, facebook = ?,
            social_links = ?, logo = ?
        WHERE club_id = ?
    ");
    $year = $form['established_year'] === '' || $form['established_year'] === null
        ? null : (int) $form['established_year'];

    // club_name, university, club_type, established_year, description,
    // official_email, website, facebook, social_links, logo, club_id
    $update->bind_param(
        'sssissssssi',
        $form['club_name'],
        $form['university'],
        $form['club_type'],
        $year,
        $form['description'],
        $form['official_email'],
        $form['website'],
        $form['facebook'],
        $form['social_links'],
        $form['logo'],
        $clubId
    );

    if (dbExec($update)) {
        $_SESSION['flash']['success'] = 'Club settings saved.';
    } else {
        $_SESSION['flash']['error'] = 'Could not save your club settings. Please try again.';
    }
    redirect('/club/settings');
}

require BASE_PATH . '/app/layouts/dashboard-b/header.php';
require BASE_PATH . '/app/layouts/dashboard-b/sidebar.php';
?>
<div class="flex-1 flex flex-col overflow-hidden">
    <?php require BASE_PATH . '/app/layouts/dashboard-b/navbar.php'; ?>
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <?php
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $alertMessage = $flashSuccess ?: $flashError;
        $alertType = $flashSuccess ? 'success' : ($flashError ? 'error' : 'info');
        if (!empty($alertMessage)) require BASE_PATH . '/app/components/alert.php';
        ?>

        <div class="page-header mb-6">
            <div>
                <h2 class="page-title">Club Settings</h2>
                <p class="page-subtitle">View and manage your club profile</p>
            </div>
        </div>

        <?php $canEdit = clubCanManage('club_profile'); ?>
        <form method="POST" class="card p-6 max-w-2xl">
            <?= csrfField() ?>
            <h3 class="text-base font-semibold text-gray-900 mb-1">Club Profile</h3>
            <p class="text-sm text-gray-500 mb-5">These details appear on your public club page.</p>

            <div class="space-y-4">
                <div class="form-group">
                    <label class="label-required" for="club_name">Club Name</label>
                    <input type="text" id="club_name" name="club_name" class="input"
                           value="<?= e($form['club_name'] ?? '') ?>" maxlength="100" required
                           <?= $canEdit ? '' : 'disabled' ?>>
                </div>

                <div class="form-group">
                    <label class="label-required" for="university">University</label>
                    <input type="text" id="university" name="university" class="input"
                           value="<?= e($form['university'] ?? '') ?>" maxlength="150" required
                           <?= $canEdit ? '' : 'disabled' ?>>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="label" for="club_type">Club Type</label>
                        <input type="text" id="club_type" name="club_type" class="input"
                               value="<?= e($form['club_type'] ?? '') ?>" maxlength="50"
                               placeholder="e.g. Technical, Cultural, Debate"
                               <?= $canEdit ? '' : 'disabled' ?>>
                    </div>
                    <div class="form-group">
                        <label class="label" for="established_year">Established</label>
                        <input type="number" id="established_year" name="established_year" class="input"
                               value="<?= e($form['established_year'] ?? '') ?>"
                               min="1900" max="<?= (int) date('Y') + 1 ?>" placeholder="2019"
                               <?= $canEdit ? '' : 'disabled' ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label class="label" for="description">Description</label>
                    <textarea id="description" name="description" rows="4" class="textarea" maxlength="2000"
                              <?= $canEdit ? '' : 'disabled' ?>><?= e($form['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="label" for="official_email">Official Email</label>
                    <input type="email" id="official_email" name="official_email" class="input"
                           value="<?= e($form['official_email'] ?? '') ?>" maxlength="100"
                           placeholder="club@university.edu"
                           <?= $canEdit ? '' : 'disabled' ?>>
                </div>

                <div class="form-group">
                    <label class="label" for="website">Website</label>
                    <input type="url" id="website" name="website" class="input"
                           value="<?= e($form['website'] ?? '') ?>" maxlength="255"
                           placeholder="https://example.com"
                           <?= $canEdit ? '' : 'disabled' ?>>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="label" for="facebook">Facebook Page</label>
                        <input type="url" id="facebook" name="facebook" class="input"
                               value="<?= e($form['facebook'] ?? '') ?>" maxlength="255"
                               placeholder="https://facebook.com/club"
                               <?= $canEdit ? '' : 'disabled' ?>>
                    </div>
                    <div class="form-group">
                        <label class="label" for="social_links">Other Social Links</label>
                        <input type="text" id="social_links" name="social_links" class="input"
                               value="<?= e($form['social_links'] ?? '') ?>" maxlength="255"
                               placeholder="Instagram, LinkedIn, Discord..."
                               <?= $canEdit ? '' : 'disabled' ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label class="label" for="logo">Logo URL</label>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg shrink-0">
                            <?= e(substr($form['club_name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <input type="text" id="logo" name="logo" class="input"
                               value="<?= e($form['logo'] ?? '') ?>" maxlength="255"
                               placeholder="https://example.com/logo.png"
                               <?= $canEdit ? '' : 'disabled' ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label class="label">Status</label>
                    <?php
                    $status = $club['status'] ?? 'pending';
                    $sb = match($status) {
                        'approved' => 'badge-success',
                        'rejected' => 'badge-danger',
                        default    => 'badge-neutral',
                    };
                    ?>
                    <span class="<?= $sb ?>"><?= ucfirst(e($status)) ?></span>
                    <p class="form-hint">Only the system administration can change this.</p>
                </div>
            </div>

            <?php if ($canEdit): ?>
                <div class="flex items-center gap-3 pt-4 mt-6 border-t border-gray-200">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="<?= url('/club/settings') ?>" class="btn-ghost">Reset</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-6">
                    You have view-only access to this section, so club settings are read-only.
                </div>
            <?php endif; ?>
        </form>

        <div class="card p-6 max-w-2xl mt-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Your Account</h3>
            <div class="space-y-4">
                <div class="form-group">
                    <label class="label">Name</label>
                    <input type="text" class="input" value="<?= e(currentUser()['name']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="label">Email</label>
                    <input type="email" class="input" value="<?= e(currentUser()['email']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="label">Role</label>
                    <?php
                    $role = currentUser()['role'] ?? 'member';
                    $rb = match($role) {
                        'owner' => 'badge-danger',
                        'admin' => 'badge-primary',
                        'executive' => 'badge-info',
                        default => 'badge-neutral',
                    };
                    ?>
                    <span class="<?= $rb ?>"><?= ucfirst(e($role)) ?></span>
                </div>
            </div>
        </div>
    </main>
    <?php require BASE_PATH . '/app/layouts/dashboard-b/footer.php'; ?>
</div>
