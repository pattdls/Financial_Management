<?php
date_default_timezone_set('Asia/Manila');
include('../dbcon.php');
include('../layout/session_check.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs</title>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/chartAccounts.css">
</head>

<body>
    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <!-- Main Content -->
        <div class="main-content container-fluid">

            <!-- Top Nav -->
            <?php
            $page_title = 'System Activity Logs';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-0">
                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link active" href="exp_logs_list.php">
                            <?php if ($has_new_recording): ?>
                                <span class="dot"></span>
                            <?php endif; ?>
                            Recording Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="arch_logs_list.php">
                            <?php if ($has_new_archive): ?>
                                <span class="dot"></span>
                            <?php endif; ?>
                            Archive/Restore Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="session_logs_list.php">
                            <?php if ($has_new_sessions): ?>
                                <span class="dot"></span>
                            <?php endif; ?>
                            Session Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fs_access_logs.php">
                            <?php if ($has_new_fsAccess): ?>
                                <span class="dot"></span>
                            <?php endif; ?>
                            FS Access Logs
                        </a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Table for Project Expense Archives -->
                <section id="project_expense_archives" class="account-section">
                    <div class="card-body px-0">
                        <div class="cl-head mt-3">
                            <h3 style="font-weight: bold;">Activity Logs</h3>
                        </div>
                        <div class="card-body px-0 pe-3">
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    var table = $('#project_expense').DataTable({
                                        responsive: true,
                                        dom: 'lfrtip',
                                        ordering: true,
                                        order: [],
                                        buttons: [],
                                        autoWidth: false,
                                        language: {
                                            search: '',
                                            searchPlaceholder: "Search record...",
                                            paginate: {
                                                previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                                next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                            }

                                        }
                                    });

                                    // To custom calendar for custom date range
                                    flatpickr("#customDateRangeLogs", {
                                        mode: "range",
                                        dateFormat: "Y-m-d",
                                        onClose: function(selectedDates, dateStr) {
                                            filterByDate('custom', selectedDates);
                                        }
                                    });

                                    // If "Custom" option is selected
                                    $('#dateFilterLogs').on('change', function() {
                                        const value = $(this).val();
                                        if (value === 'custom') {
                                            $('#customDateRangeLogs').show();
                                        } else {
                                            $('#customDateRangeLogs').hide();
                                            filterByDate(value);
                                        }
                                    });

                                    // Modal when no data is foundFiolete
                                    var noDataModal = new bootstrap.Modal(document.getElementById('noDataModal'), {
                                        backdrop: 'static',
                                        keyboard: false
                                    });

                                    function filterByDate(range, customDates = []) {
                                        const today = new Date();
                                        let startDate, endDate;

                                        if (range === 'weekly') {
                                            const today = new Date();
                                            const day = today.getDay();
                                            const monday = new Date(today);
                                            monday.setDate(today.getDate() - (day === 0 ? 6 : day - 1));
                                            const sunday = new Date(monday);
                                            sunday.setDate(monday.getDate() + 6);
                                            startDate = monday;
                                            endDate = sunday;
                                        } else if (range === 'monthly') {
                                            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                                            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                                        } else if (range === 'custom' && customDates.length === 2) {
                                            startDate = customDates[0];
                                            endDate = customDates[1];
                                        } else {
                                            $.fn.dataTable.ext.search.pop();
                                            table.draw();
                                            if (table.rows({
                                                    filter: 'applied'
                                                }).count() === 0) {
                                                noDataModal.show();
                                            }
                                            return;
                                        }

                                        $.fn.dataTable.ext.search.push(function(settings, data) {
                                            const dateStr = data[1];
                                            const rowDate = new Date(dateStr);
                                            rowDate.setHours(0, 0, 0, 0);

                                            const start = new Date(startDate);
                                            const end = new Date(endDate);
                                            start.setHours(0, 0, 0, 0);
                                            end.setHours(0, 0, 0, 0);

                                            return rowDate >= start && rowDate <= end;
                                        });

                                        table.draw();
                                        $.fn.dataTable.ext.search.pop();
                                        if (table.rows({
                                                filter: 'applied'
                                            }).count() === 0) {
                                            noDataModal.show();
                                        }
                                    }

                                    // Activity filter
                                    $('#activityFilter').on('change', function() {
                                        table.draw();
                                    });

                                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                                        const selectedActivity = $('#activityFilter').val();
                                        const rowActivity = data[5].trim();

                                        if (!selectedActivity) return true;

                                        // Allow "startsWith" match for activities like "Added New Client (Name)"
                                        if (rowActivity.startsWith(selectedActivity)) {
                                            return true;
                                        }
                                        return false;
                                    });


                                    // Toolbar setup
                                    setTimeout(function() {
                                        var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');
                                        toolbar.append($('#project_expense_wrapper .dataTables_length'));
                                        toolbar.append($('#dateRangeContainer'));
                                        toolbar.append($('#project_expense_wrapper .dataTables_filter'));
                                        $('#project_expense_wrapper').prepend(toolbar);
                                    }, 0);
                                });
                            </script>

                            <div id="dateRangeWrapper" style="display: contents">
                                <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <label for="dateFilter" class="mb-0" style="font-size: 14px;">Filter Date by:</label>
                                    <select id="dateFilterLogs" class="form-select form-select-sm w-auto" style="height: 30px;">
                                        <option value="all">All</option>
                                        <option value="weekly">This Week</option>
                                        <option value="monthly">This Month</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                    <input type="text" id="customDateRangeLogs" class="forDate form-control form-control-sm w-auto"
                                        placeholder="Select date range" style="display: none; height: 30px" />

                                    <label for="activityFilter" class="mb-0 ms-5" style="font-size: 14px;">Filter by Activity:</label>
                                    <select id="activityFilter" class="form-select form-select-sm w-auto" style="height: 30px;">
                                        <option value="">All Activities</option>
                                        <option value="Recorded Project Expense">Project Expenses</option>
                                        <option value="Recorded Company Expense">Company Expenses</option>
                                        <option value="Recorded Client Payment">Client Payments</option>
                                        <option value="Added Account Title">Chart of Accounts</option>
                                        <option value="Added New Client">Clients - Added</option>
                                        <option value="Added New Project">Projects - Added</option>
                                        <!-- NEW: Client Edit Activities -->

                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="project_expense" style="overflow: hidden;">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Timestamp</th>
                                            <th scope="col">User Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Role</th>
                                            <th scope="col">Component/Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $con = mysqli_connect("localhost", "root", "", "financial_management");

                                        // Fetch user info
                                        $user_info_query = "SELECT * FROM users";
                                        $user_info_run = mysqli_query($con, $user_info_query);
                                        $users = [];
                                        while ($user = mysqli_fetch_assoc($user_info_run)) {
                                            $users[$user['id']] = $user;
                                        }

                                        $logs = [];

                                        // Project Expenses
                                        $proj_expense_query = "SELECT user_id, created_at, project_id FROM expenses WHERE restored_date IS NULL";
                                        $proj_expense_logs = mysqli_query($con, $proj_expense_query);
                                        while ($log = mysqli_fetch_assoc($proj_expense_logs)) {
                                            $log['activity'] = 'Recorded Project Expense';
                                            $log['client_details'] = 'N/A';

                                            if (!empty($log['project_id'])) {
                                                $proj_id = $log['project_id'];
                                                $project_name_query = mysqli_query($con, "SELECT project_name FROM projects WHERE project_id = '$proj_id' LIMIT 1");
                                                if ($proj_row = mysqli_fetch_assoc($project_name_query)) {
                                                    $project_name = $proj_row['project_name'];

                                                    // add project name into activity
                                                    $log['activity'] = "Recorded Project Expense - $project_name";
                                                    $log['client_details'] = $project_name;
                                                }
                                            }

                                            $logs[] = $log;
                                        }

                                        // Company Expenses
                                        $comp_expense_query = "SELECT user_id, created_at FROM company_expense WHERE restored_date IS NULL";
                                        $comp_expense_logs = mysqli_query($con, $comp_expense_query);
                                        while ($log = mysqli_fetch_assoc($comp_expense_logs)) {
                                            $log['activity'] = 'Recorded Company Expense';
                                            $log['client_details'] = 'N/A';
                                            $logs[] = $log;
                                        }

                                        // Client Payments
                                        $client_payment_query = "SELECT user_id, created_at, client_id FROM payment_clients WHERE restored_date IS NULL";
                                        $client_payment_logs = mysqli_query($con, $client_payment_query);
                                        while ($log = mysqli_fetch_assoc($client_payment_logs)) {
                                            $log['activity'] = 'Recorded Client Payment';
                                            $log['client_details'] = 'N/A';

                                            if (!empty($log['client_id'])) {
                                                $client_id = $log['client_id'];
                                                $client_name_query = mysqli_query($con, "SELECT client_name FROM clients WHERE client_id = '$client_id' LIMIT 1");
                                                if ($client_row = mysqli_fetch_assoc($client_name_query)) {
                                                    $client_name = $client_row['client_name'];

                                                    // add project name into activity
                                                    $log['activity'] = "Recorded Client Payment - $client_name";
                                                    $log['client_details'] = $client_name;
                                                }
                                            }
                                            $logs[] = $log;
                                        }

                                        // Chart of Accounts
                                        $chart_accounts_query = "SELECT user_id, created_at FROM chart_accounts";
                                        $chart_accounts_logs = mysqli_query($con, $chart_accounts_query);
                                        while ($log = mysqli_fetch_assoc($chart_accounts_logs)) {
                                            $log['activity'] = 'Added Account Title';
                                            $log['client_details'] = 'N/A';
                                            $logs[] = $log;
                                        }

                                        // Clients Added Logs
                                        $client_query = "SELECT c.added_by as user_id, c.created_at, u.name as added_by_name, u.email, c.client_name 
                                                        FROM clients c
                                                        LEFT JOIN users u ON c.added_by = u.id";

                                        $client_logs = mysqli_query($con, $client_query);
                                        while ($log = mysqli_fetch_assoc($client_logs)) {
                                            $client_name = $log['client_name'] ?? '';
                                            $log['activity'] = 'Added New Client';
                                            $log['activity'] .= !empty($client_name) ? " ({$client_name})" : '';
                                            $log['client_details'] = 'N/A';
                                            $logs[] = $log;
                                        }

                                        // Projects Added Logs
                                        $project_query = "SELECT p.added_by as user_id, p.created_at, u.name as added_by_name, u.email, p.project_name 
                                                        FROM projects p
                                                        LEFT JOIN users u ON p.added_by = u.id";
                                        $project_logs = mysqli_query($con, $project_query);
                                        while ($log = mysqli_fetch_assoc($project_logs)) {
                                            $project_name = $log['project_name'] ?? '';
                                            $log['activity'] = 'Added New Project';
                                            $log['activity'] .= !empty($project_name) ? " ({$project_name})" : '';
                                            $log['client_details'] = 'N/A';
                                            $logs[] = $log;
                                        }

                                        // Cancelled Projects Logs
                                        $cancelled_proj_query = "
                                            SELECT cpa.user_id, cpa.cancelled_at AS created_at,
                                                u.name AS cancelled_by_name, u.email, cpa.project_name, cpa.cancellation_reason
                                            FROM cancelled_project_archive cpa
                                            LEFT JOIN users u ON cpa.user_id = u.id
                                            ORDER BY cpa.cancelled_at DESC
                                        ";
                                        $cancelled_proj_logs = mysqli_query($con, $cancelled_proj_query);

                                        while ($log = mysqli_fetch_assoc($cancelled_proj_logs)) {
                                            $project_name = $log['project_name'] ?? '';
                                            $reason = $log['cancellation_reason'] ?? '';
                                            $log['activity'] = 'Cancelled Project';
                                            $log['activity'] .= !empty($project_name) ? " ({$project_name})" : '';
                                            if (!empty($reason)) {
                                                $log['activity'] .= " - Reason: " . $reason;
                                            }
                                            $log['client_details'] = 'N/A';
                                            $logs[] = $log;
                                        }


                                        // Edit Logs (Clients and Projects) - THIS IS WHERE CLIENT EDITS ARE TRACKED
                                        $edit_logs_query = "SELECT el.*, u.name AS user_name, u.email, c.client_name 
                                            FROM edit_logs el 
                                            LEFT JOIN users u ON el.user_id = u.id
                                            LEFT JOIN clients c ON el.client_id = c.client_id
                                            WHERE el.activity LIKE 'Edited%'  -- Only get edit activities
                                            ORDER BY el.timestamp DESC";
                                        $edit_logs = mysqli_query($con, $edit_logs_query);

                                        while ($log = mysqli_fetch_assoc($edit_logs)) {
                                            $log['user_id'] = $log['user_id'];
                                            $log['created_at'] = $log['timestamp'];
                                            $log['added_by_name'] = $log['user_name'];
                                            $log['email'] = $log['email'];

                                            // NEW: Format activity for edits
                                            if (strpos($log['activity'], 'Edited') !== false && !empty($log['client_name'])) {
                                                if (strpos($log['activity'], $log['client_name']) === false) {
                                                    $log['activity'] .= " (" . $log['client_name'] . ")";
                                                }
                                                $log['client_details'] = 'N/A';
                                            } else {
                                                $log['client_details'] = $log['client_name'] ?? 'N/A';
                                            }

                                            $logs[] = $log;
                                        }


                                        // Sort logs by latest first        
                                        usort($logs, function ($a, $b) {
                                            $timeDiff = strtotime($b['created_at']) - strtotime($a['created_at']);
                                            if ($timeDiff !== 0) {
                                                return $timeDiff;
                                            }
                                            return strcmp($b['activity'], $a['activity']); // fallback to activity name
                                        });


                                        $last_viewed_recording = $_SESSION['last_viewed_recording'] ?? null;

                                        $has_new_recording = $latest_recording  && (!$last_viewed_recording || strtotime($latest_recording) > strtotime($last_viewed_recording));

                                        // Output rows
                                        if (!empty($logs)) {
                                            $row_num = 1;
                                            foreach ($logs as $log) {
                                                $user_id = $log['user_id'];
                                                $timestamp = $log['created_at'];
                                                $activity = $log['activity'];
                                                $username = $users[$user_id]['name'] ?? ($log['added_by_name'] ?? 'Unknown');
                                                $email = $users[$user_id]['email'] ?? ($log['email'] ?? 'Unknown');
                                                $role  = $users[$user_id]['role'] ?? 'Unknown';
                                                $client_details = $log['client_details'] ?? 'N/A';

                                        ?>
                                                <tr>
                                                    <td><?php echo $row_num++; ?></td>
                                                    <td><?php echo $timestamp; ?></td>
                                                    <td><?php echo $username; ?></td>
                                                    <td><?php echo $email; ?></td>
                                                    <td><?php echo $role; ?></td>
                                                    <td>
                                                        <span style="
                                                            display: inline-block;
                                                            padding: 4px 8px;
                                                            border-radius: 12px;
                                                            font-size: 13px;
                                                            <?php
                                                            if (str_contains($activity, 'Recorded Project Expense')) {
                                                                echo 'background-color: #537D5D; color: #fbfdfe;';
                                                            } else if ($activity === 'Recorded Company Expense') {
                                                                echo 'background-color: #9ebc8a; color: #fbfdfe;';
                                                            } else if (str_contains($activity, 'Recorded Client Payment')) {
                                                                echo 'background-color: #E1EEBC; color: #183b4e;';
                                                            } else if ($activity === 'Added Account Title') {
                                                                echo 'background-color: #456882; color: #fbfdfe;';
                                                            } else if (str_contains($activity, 'Added New Client') || str_contains($activity, 'Added New Project')) {
                                                                echo 'background-color: #0d6efd; color: #fff;';
                                                            } else if (strpos($activity, 'Edited') === 0) {
                                                                // NEW: Different colors for edit activities
                                                                echo 'background-color: #f7ecb5; color: #212529;';
                                                            } else if (str_contains($activity, 'Cancelled Project')) {
                                                                echo 'background-color: #dc3545; color: #fff;'; // red badge

                                                            } else {
                                                                echo 'background-color: #f8f9fa; color: #000;';
                                                            }
                                                            ?>
                                                        ">
                                                            <?php echo $activity; ?>
                                                        </span>
                                                    </td>

                                                </tr>
                                            <?php
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="7" style="text-align: center;">No Record Found</td>
                                            </tr>
                                        <?php
                                        }
                                        if ($latest_recording) {
                                            $_SESSION['last_viewed_recording'] = $latest_recording;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- No record found modal -->
    <div class="modal fade" id="noDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <i class="bi bi-x-circle-fill text-danger" style="font-size:70px;"></i>
                <h4 class="mt-3">No Record Found</h4>
                <p style="text-align: center; font-size: 16px;">
                    There are no records found for the selected criteria.</p>
                <div class="modal-footer border-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <script src="./resources/js/auto_logout.js"></script>

    <script>
        // Close modal
        let modalElement = document.getElementById("addClientModal");
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }

        // Date and Time Function
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };

            document.getElementById('datetime').innerHTML = now.toLocaleDateString('en-US', options);
        }

        // Update every second
        setInterval(updateDateTime, 1000);

        // Initial call to display time immediately
        updateDateTime();

        document.getElementById('clearBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('[name="start_archive"]').value = '';
            document.querySelector('[name="end_archive"]').value = '';
            window.location.href = '../forms_logic/archive.php?clear=true';
        });
    </script>
</body>

</html>