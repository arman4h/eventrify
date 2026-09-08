<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';

requireAuth();
requireAdmin();

$pageTitle = 'Applications';
$activePage = 'applications';


$applications = [
    [
        'id' => 1,
        'name' => 'Rahim Ahmed',
        'email' => 'rahim@example.com',
        'event' => 'Tech Fest 2026',
        'date' => '10 Sep 2026',
        'status' => 'Pending',
    ],
    [
        'id' => 2,
        'name' => 'Nusrat Jahan',
        'email' => 'nusrat@example.com',
        'event' => 'Cultural Night 2026',
        'date' => '15 Sep 2026',
        'status' => 'Pending',
    ],
    [
        'id' => 3,
        'name' => 'Arif Hasan',
        'email' => 'arif@example.com',
        'event' => 'Sports Carnival 2026',
        'date' => '20 Sep 2026',
        'status' => 'Approved',
    ],
    [
        'id' => 4,
        'name' => 'Sadia Akter',
        'email' => 'sadia@example.com',
        'event' => 'Programming Workshop',
        'date' => '25 Sep 2026',
        'status' => 'Pending',
    ],
    [
        'id' => 5,
        'name' => 'Tanvir Hossain',
        'email' => 'tanvir@example.com',
        'event' => 'Photography Exhibition',
        'date' => '30 Sep 2026',
        'status' => 'Rejected',
    ],
];

$pendingCount = 0;

foreach ($applications as $application) {
    if ($application['status'] === 'Pending') {
        $pendingCount++;
    }
}

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">

    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">

     

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Applications
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Review and manage event applications.
                </p>
            </div>

            <div class="mt-4 sm:mt-0">

                <div class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-50 text-amber-700">

                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-2"></span>

                    <span class="font-semibold">
                        <?= $pendingCount ?> Pending
                    </span>

                </div>

            </div>

        </div>


       

        <div class="card overflow-hidden">

            <div class="p-6 border-b border-gray-100">

                <h2 class="text-lg font-semibold text-gray-900">
                    Event Applications
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Review applications submitted by users.
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Applicant
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Event
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Event Date
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <?php foreach ($applications as $application): ?>

                            <tr class="hover:bg-gray-50 transition">

                            

                                <td class="px-6 py-5">

                                    <div class="flex items-center">

                                        <div class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center font-semibold mr-3">

                                            <?= strtoupper(substr($application['name'], 0, 1)) ?>

                                        </div>

                                        <div>

                                            <p class="font-medium text-gray-900">
                                                <?= e($application['name']) ?>
                                            </p>

                                            <p class="text-sm text-gray-500">
                                                <?= e($application['email']) ?>
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                <td class="px-6 py-5">

                                    <p class="font-medium text-gray-900">
                                        <?= e($application['event']) ?>
                                    </p>

                                </td>


                               

                                <td class="px-6 py-5">

                                    <p class="text-sm text-gray-600">
                                        <?= e($application['date']) ?>
                                    </p>

                                </td>



                                <td class="px-6 py-5">

                                    <?php if ($application['status'] === 'Pending'): ?>

                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">

                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-2"></span>

                                            Pending

                                        </span>

                                    <?php elseif ($application['status'] === 'Approved'): ?>

                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">

                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>

                                            Approved

                                        </span>

                                    <?php else: ?>

                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700">

                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-2"></span>

                                            Rejected

                                        </span>

                                    <?php endif; ?>

                                </td>


                               

                                <td class="px-6 py-5">

                                    <div class="flex items-center justify-end gap-2">

                                        <?php if ($application['status'] === 'Pending'): ?>

                                            <button
                                                type="button"
                                                onclick="approveApplication(<?= $application['id'] ?>)"
                                                class="px-3 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition"
                                            >
                                                Approve
                                            </button>

                                            <button
                                                type="button"
                                                onclick="rejectApplication(<?= $application['id'] ?>)"
                                                class="px-3 py-2 rounded-lg text-sm font-medium bg-red-50 text-red-600 hover:bg-red-100 transition"
                                            >
                                                Reject
                                            </button>

                                        <?php elseif ($application['status'] === 'Approved'): ?>

                                            <span class="text-sm text-emerald-600 font-medium">
                                                ✓ Approved
                                            </span>

                                        <?php else: ?>

                                            <span class="text-sm text-red-600 font-medium">
                                                ✕ Rejected
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>

</div>



<script>

function approveApplication(id) {

    const confirmed = confirm(
        "Are you sure you want to approve this application?"
    );

    if (confirmed) {

        alert(
            "Application #" + id + " has been approved."
        );

    }

}


function rejectApplication(id) {

    const confirmed = confirm(
        "Are you sure you want to reject this application?"
    );

    if (confirmed) {

        alert(
            "Application #" + id + " has been rejected."
        );

    }

}

</script>