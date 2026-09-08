<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$totalReports = 15 ;
$totalApplications = 10 ;

$totalUsers = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalEvents = $db->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$newUsers = $db->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$totalRegistrations = $db->query("SELECT COUNT(*) as count FROM event_registrations")->fetch_assoc()['count'];

require BASE_PATH . '/app/layouts/dashboard-a/header.php';
require BASE_PATH . '/app/layouts/dashboard-a/sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">

    <?php require BASE_PATH . '/app/layouts/dashboard-a/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                Dashboard Overview
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Welcome back, Admin. Here's what's happening in Eventrify.
            </p>
        </div>


     
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">


          
            <div class="card p-6 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Total Reports
                        </p>

                        <p class="text-4xl font-bold text-gray-900 mt-3">
                            <?= (int) $totalReports ?>
                        </p>
                    </div>

                    <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">

                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a2 2 0 011.414.586l3.414 3.414A2 2 0 0118 8.414V19a2 2 0 01-2 2z"
                            />
                        </svg>

                    </div>

                </div>

                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        Reports currently in the system
                    </p>
                </div>

            </div>


           
            <div class="card p-6 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Total Users
                        </p>

                        <p class="text-4xl font-bold text-gray-900 mt-3">
                            <?= (int) $totalUsers ?>
                        </p>
                    </div>

                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">

                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-2.761 2.239-5 5-5s5 2.239 5 5m-5-10a3 3 0 110-6 3 3 0 010 6z"
                            />
                        </svg>

                    </div>

                </div>

                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        Registered users on Eventrify
                    </p>
                </div>

            </div>


            <div class="card p-6 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Total Applications
                        </p>

                        <p class="text-4xl font-bold text-gray-900 mt-3">
                            <?= (int) $totalApplications ?>
                        </p>
                    </div>

                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">

                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"
                            />
                        </svg>

                    </div>

                </div>

                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        Applications submitted for events
                    </p>
                </div>

            </div>

        </div>



        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">


            <div class="card p-6">

                <div class="flex items-center justify-between mb-5">

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Recent Reports
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Latest reports in the system
                        </p>
                    </div>

                    <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a2 2 0 011.414.586l3.414 3.414A2 2 0 0118 8.414V19a2 2 0 01-2 2z"
                            />
                        </svg>

                    </div>

                </div>


                <?php

                $recentReports = $db->query(
                    "SELECT * FROM tasks
                     ORDER BY created_at DESC
                     LIMIT 5"
                );

                $columns = [
                    'Report',
                    'Status',
                    'Created'
                ];

                $rows = [];

                // while ($report = $recentReports->fetch_assoc()) {

                //     $reportName = $report['title']
                //         ?? $report['name']
                //         ?? 'Report #' . ($report['id'] ?? '');

                //     $reportStatus = $report['status']
                //         ?? 'Pending';

                //     $reportCreated = $report['created_at']
                //         ?? null;

                //     $rows[] = [
                //         e($reportName),
                //         ucfirst(e($reportStatus)),
                //         $reportCreated ? formatDate($reportCreated) : '-',
                //     ];
                // }

                $emptyMessage = 'No reports yet.';

                require BASE_PATH . '/app/components/table.php';

                ?>

            </div>



            <div class="card p-6">

                <div class="flex items-center justify-between mb-5">

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Recent Events
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Latest events created
                        </p>
                    </div>

                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>

                    </div>

                </div>


                <?php

                $recentEvents = $db->query(
                    "SELECT * FROM events
                     ORDER BY created_at DESC
                     LIMIT 5"
                );

                $columns = [
                    'Title',
                    'Venue',
                    'Date',
                    'Status'
                ];

                $rows = [];

                while ($event = $recentEvents->fetch_assoc()) {

                    $rows[] = [
                        e($event['title']),
                        e($event['venue']),
                        formatDate($event['event_date']),
                        e($event['status'] ?? 'upcoming'),
                    ];

                }

                $emptyMessage = 'No events yet.';

                require BASE_PATH . '/app/components/table.php';

                ?>

            </div>

        </div>

    </main>

    <?php require BASE_PATH . '/app/layouts/dashboard-a/footer.php'; ?>

</div>