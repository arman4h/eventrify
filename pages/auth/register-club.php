<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

if (isLoggedIn()) {
    redirect('/');
}

require_once BASE_PATH . '/app/config/database.php';

$errors = [];
$application = null;

if (isPost()) {
    $clubName      = post('club_name');
    $universityName = post('university');
    $clubType      = post('club_type');
    $established   = post('established_year');
    $description   = post('description');
    $clubEmail     = post('official_email');
    $website       = post('website');
    $facebook      = post('facebook');
    $social        = post('social_links');
    $applicantName = post('applicant_name');
    $studentId     = post('student_id');
    $position      = post('position');
    $applicantEmail = post('applicant_email');
    $phone         = post('phone');
    $declaration   = isset($_POST['declaration']);

    if (
        $clubName === '' || $universityName === '' || $clubType === '' || $description === '' ||
        $clubEmail === '' || $applicantName === '' || $studentId === '' || $position === '' ||
        $applicantEmail === '' || $phone === ''
    ) {
        $errors[] = 'Please fill in all required fields.';
    }

    if (!filter_var($clubEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid official club email address.';
    }

    if (!filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid university email address.';
    }

    if (!$declaration) {
        $errors[] = 'You must confirm that you are authorized to represent this club.';
    }

    if (empty($errors)) {
        $checkEmail = $db->prepare("SELECT club_user_id FROM club_users WHERE email = ? LIMIT 1");
        $checkEmail->bind_param('s', $clubEmail);
        $checkEmail->execute();
        $checkEmail->store_result();

        $checkClub = $db->prepare("SELECT club_id FROM clubs WHERE club_name = ? LIMIT 1");
        $checkClub->bind_param('s', $clubName);
        $checkClub->execute();
        $checkClub->store_result();

        if ($checkEmail->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        }

        if ($checkClub->num_rows > 0) {
            $errors[] = 'A club with that name already exists or is under review.';
        }

        if (empty($errors)) {
            $passwordHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

            $db->begin_transaction();
            try {
                $clubStmt = $db->prepare("INSERT INTO clubs (club_name, description, status) VALUES (?, ?, 'pending')");
                $clubStmt->bind_param('ss', $clubName, $description);
                $clubStmt->execute();
                $clubId = $clubStmt->insert_id;

                $userStmt = $db->prepare("
                    INSERT INTO club_users (club_id, full_name, email, password_hash, phone, role, status)
                    VALUES (?, ?, ?, ?, ?, 'owner', 'active')
                ");
                $userStmt->bind_param('issss', $clubId, $applicantName, $clubEmail, $passwordHash, $phone);
                $userStmt->execute();
                $clubUserId = $userStmt->insert_id;

                $linkStmt = $db->prepare("UPDATE clubs SET requested_by = ? WHERE club_id = ?");
                $linkStmt->bind_param('ii', $clubUserId, $clubId);
                $linkStmt->execute();

                $db->commit();

                $application = sprintf('EVENTRIFY-%05d', $clubId);
                setOld([]);
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Application failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Request Club Access';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col items-center justify-center px-4 py-12">
    <a href="<?= url('/') ?>" class="flex items-center gap-2.5 mb-8">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white text-lg font-bold">E</span>
        <span class="text-2xl font-bold tracking-tight text-gray-900">Eventrify</span>
    </a>

    <div class="w-full max-w-2xl">
        <div class="card p-6 sm:p-8">
            <?php if ($application !== null): ?>
                <div class="flex flex-col items-center text-center py-4">
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 mb-4"><?= icon('check-circle', 'w-7 h-7') ?></span>
                    <h1 class="text-xl font-bold text-gray-900">Application Submitted</h1>
                    <div class="flex items-center gap-3 mt-4">
                        <span class="badge badge-warning">Pending Review</span>
                        <span class="text-sm text-gray-500">Your application ID: <span class="font-semibold text-gray-900"><?= e($application) ?></span></span>
                    </div>
                    <p class="text-sm text-gray-500 mt-4 max-w-md">Our team will review your request. You'll receive access to the Club Dashboard once approved.</p>
                    <a href="<?= url('/') ?>" class="btn-secondary btn-lg mt-6">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-gray-900">Request Club Access</h1>
                    <p class="text-sm text-gray-500 mt-1">Representing a university club? Submit your club information for verification. Once approved, you'll receive access to the Eventrify Club Dashboard.</p>
                </div>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error mb-4" role="alert">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 shrink-0"><?= icon('x-circle', 'w-5 h-5') ?></span>
                            <p class="text-sm"><?= e($error) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="<?= url('/club/register') ?>" enctype="multipart/form-data" class="space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Club Information</h2>
                        <div class="separator mt-3 mb-5"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="club_name" class="label label-required">Club Name</label>
                                <input type="text" id="club_name" name="club_name" class="input" placeholder="e.g. Programming Club" value="<?= e(post('club_name')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="university" class="label label-required">University</label>
                                <input type="text" id="university" name="university" class="input" placeholder="United International University" value="<?= e(post('university') !== '' ? post('university') : 'United International University') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="club_type" class="label label-required">Club Type</label>
                                <select id="club_type" name="club_type" class="select" required>
                                    <option value="">Select a type</option>
                                    <?php
                                    $clubTypes = ['Academic', 'Technical', 'Cultural', 'Sports', 'Social', 'Other'];
                                    foreach ($clubTypes as $type):
                                    ?>
                                    <option value="<?= e($type) ?>" <?= post('club_type') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="established_year" class="label">Established Year</label>
                                <input type="number" id="established_year" name="established_year" class="input" min="1990" max="2026" value="<?= e(post('established_year')) ?>">
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <label for="description" class="label label-required">Club Description</label>
                            <textarea id="description" name="description" class="textarea" rows="3" placeholder="Tell us about your club's mission and activities..." required><?= e(post('description')) ?></textarea>
                        </div>

                        <div class="form-group mt-4">
                            <label for="logo" class="label">Club Logo</label>
                            <input type="file" id="logo" name="logo" class="input" accept="image/*">
                            <p class="form-hint">PNG or JPG, max 2MB</p>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Official Information</h2>
                        <div class="separator mt-3 mb-5"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="official_email" class="label label-required">Official Club Email</label>
                                <input type="email" id="official_email" name="official_email" class="input" placeholder="club@university.edu" value="<?= e(post('official_email')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="website" class="label">Website</label>
                                <input type="url" id="website" name="website" class="input" placeholder="https://" value="<?= e(post('website')) ?>">
                            </div>

                            <div class="form-group">
                                <label for="facebook" class="label">Facebook Page</label>
                                <input type="url" id="facebook" name="facebook" class="input" placeholder="https://facebook.com/yourclub" value="<?= e(post('facebook')) ?>">
                            </div>

                            <div class="form-group">
                                <label for="social_links" class="label">Other Social Links</label>
                                <input type="text" id="social_links" name="social_links" class="input" placeholder="Instagram, LinkedIn, etc." value="<?= e(post('social_links')) ?>">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Club Authority</h2>
                        <div class="separator mt-3 mb-5"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="applicant_name" class="label label-required">Applicant Name</label>
                                <input type="text" id="applicant_name" name="applicant_name" class="input" placeholder="John Doe" value="<?= e(post('applicant_name')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="student_id" class="label label-required">Student ID</label>
                                <input type="text" id="student_id" name="student_id" class="input" placeholder="e.g. 0112211234" value="<?= e(post('student_id')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="position" class="label label-required">Position</label>
                                <input type="text" id="position" name="position" class="input" placeholder="e.g. President" value="<?= e(post('position')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="applicant_email" class="label label-required">University Email</label>
                                <input type="email" id="applicant_email" name="applicant_email" class="input" placeholder="your.name@university.edu" value="<?= e(post('applicant_email')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="label label-required">Phone</label>
                                <input type="tel" id="phone" name="phone" class="input" placeholder="01XXXXXXXXX" value="<?= e(post('phone')) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Verification Documents</h2>
                        <div class="separator mt-3 mb-5"></div>

                        <div class="form-group">
                            <label for="documents" class="label">Organizational documents (constitution, advisor letter, etc.)</label>
                            <input type="file" id="documents" name="documents[]" class="input" multiple>
                            <p class="form-hint">You can attach multiple files.</p>
                        </div>
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="declaration" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= isset($_POST['declaration']) ? 'checked' : '' ?> required>
                        <span class="text-sm text-gray-600">I confirm that I am authorized to represent this club and the information provided is accurate.</span>
                    </label>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-1">
                        <button type="submit" class="btn-primary btn-lg w-full sm:w-auto">Submit Club Request</button>
                        <a href="<?= url('/login') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to student login</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <p class="mt-6 text-sm text-gray-500">&copy; <?= date('Y') ?> Eventrify &middot; University Club Events</p>
    <script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>