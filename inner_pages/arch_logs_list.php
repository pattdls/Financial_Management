<?php
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
      <!-- Main Content -->
        <div class="main-content container-fluid ">

            <!-- Top Nav -->
            <?php
            $page_title = 'System Activity Logs';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-0">
                <ul class="financial-nav mt-5 p-0">
                            <li class="nav-item">
                        <a class="nav-link" href="exp_logs_list.php">
                            <?php if ($has_new_recording): ?>
                                <span class="dot"></span>
                            <?php endif; ?>
                            Recording Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="arch_logs_list.php">
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
                        <h4 style="font-weight: bold;">Archive & Restore Logs</h4>
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

                            // // If "Custom" option is selected
                            $('#dateFilterLogs').on('change', function() {
                                const value = $(this).val();
                                if (value === 'custom') {
                                    $('#customDateRangeLogs').show();
                                } else {
                                    $('#customDateRangeLogs').hide();
                                    filterByDate(value);
                                }
                            });
                             // Modal when no data is found
                            var noDataModal = new bootstrap.Modal(document.getElementById('noDataModal'), {
                                backdrop: 'static',
                                keyboard: false
                                });

                            function filterByDate(range, customDates = []) {
                                const today = new Date();
                                let startDate, endDate;

                                if (range === 'weekly') {
                                    const today = new Date();
                                    const day = today.getDay(); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

                                    const monday = new Date(today);
                                    monday.setDate(today.getDate() - (day === 0 ? 6 : day - 1)); // Go back to Monday (Sunday = 0 -> -6)

                                    const sunday = new Date(monday);
                                    sunday.setDate(monday.getDate() + 6); // Add 6 days to reach Sunday

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
                                    table.draw();
                                    if (table.rows({
                                            filter: 'applied'
                                        }).count() === 0) {
                                        noDataModal.show();
                                    }
                                    return;
                                }
                               
                                $.fn.dataTable.ext.search.push(function(settings, data) {
                                    const dateStr = data[1]; // Get the date from the 5th column (0-based index)
                                    const rowDate = new Date(dateStr);
                                    rowDate.setHours(0, 0, 0, 0);

                                    const start = new Date(startDate);
                                    const end = new Date(endDate);
                                    start.setHours(0, 0, 0, 0);
                                    end.setHours(0, 0, 0, 0);

                                    return rowDate >= start && rowDate <= end;

                                     const selectedActivity = $('#activityFilter').val();
                                     const rowActivity = data[5];
                                });

                                table.draw();
                                $.fn.dataTable.ext.search.pop();
                                if (table.rows({
                                        filter: 'applied'
                                    }).count() === 0) {
                                    noDataModal.show();
                                }
                            }
                            // To make the activity filter work:
                             $('#activityFilter').on('change', function () {
                                    table.draw(); 
                                });
                                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                                    const selectedActivity = $('#activityFilter').val();
                                    const rowActivity = data[5].trim(); // 6th column = Component/Activity

                                    // Allow "startsWith" match for activities like "Added New Client (Name)"
                                        if (rowActivity.startsWith(selectedActivity)) {
                                            return true;
                                        }
                                        return false;
                                });

                            // Ensure DataTables wrapper is ready before manipulating
                            setTimeout(function() {
                                // Create toolbar container
                                var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');

                                // Append elements in correct order
                                toolbar.append($('#project_expense_wrapper .dataTables_length'));
                                toolbar.append($('#dateRangeContainer'));
                                toolbar.append($('#project_expense_wrapper .dataTables_filter'));

                                // Insert toolbar before the table wrapper
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
                                        placeholder="Select date range" style="display: none;" />
                                        
                                    <label for="activityFilter" class="mb-0 ms-5" style="font-size: 14px;">Filter by Activity:</label>
                                    <select id="activityFilter" class="form-select form-select-sm w-auto" style="height: 30px;">
                                        <option value="">All Activities</option>
                                        <option value="Archived Project Expense">Archived Project Expenses</option>
                                        <option value="Archived Company Expense">Archived Company Expenses</option>
                                        <option value="Archived Client Payment">Archived Client Payments</option>
                                        <option value="Restored Project Expense">Restored Project Expenses</option>
                                        <option value="Restored Company Expense">Restored Company Expenses</option>
                                        <option value="Restored Client Payment">Restored Client Payments</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                            <table class="table table-bordered" id="project_expense">
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

                                    // To fetch user info
                                    $user_info_query = "SELECT * FROM users";
                                    $user_info_run = mysqli_query($con, $user_info_query);
                                    $users = [];
                                    while ($user = mysqli_fetch_assoc($user_info_run)) {
                                        $users[$user['id']] = $user;
                                    }

                                    $logs = [];

                                    // Project Expenses
                                    $proj_archive_query = "SELECT user_id, archived_at, project_id FROM expenses_archive";
                                    $proj_archive_logs = mysqli_query($con, $proj_archive_query);
                                    while ($log = mysqli_fetch_assoc($proj_archive_logs)) {

                                        if (!empty($log['project_id'])) {
                                            $proj_id = $log['project_id'];
                                            $project_name_query = mysqli_query($con, "SELECT project_name FROM projects WHERE project_id = '$proj_id' LIMIT 1");
                                            if ($proj_row = mysqli_fetch_assoc($project_name_query)) {
                                                $project_name = $proj_row['project_name'];
                                                 $project_name = $proj_row['project_name'];
                                                    $log['activity'] = "Archived Project Expense - $project_name";
                                                } else {
                                                    $log['activity'] = "Archived Project Expense - (Unknown Project)";
                                            }
                                        }

                                        $logs[] = $log;
                                    }

                                    $proj_restored_query = "SELECT restored_by AS user_id, project_id, restored_date AS archived_at FROM expenses WHERE restored_date IS NOT NULL";
                                    $proj_restored_logs = mysqli_query($con, $proj_restored_query);
                                    while ($log = mysqli_fetch_assoc($proj_restored_logs)){

                                        if (!empty($log['project_id'])) {
                                            $proj_id = $log['project_id'];
                                            $project_name_query = mysqli_query($con, "SELECT project_name FROM projects WHERE project_id = '$proj_id' LIMIT 1");
                                            if ($proj_row = mysqli_fetch_assoc($project_name_query)) {
                                                $project_name = $proj_row['project_name'];
                                                
                                                // add project name into activity
                                                $log['activity'] = "Restored Project Expense - $project_name";
                                            }
                                        }

                                        $logs[] = $log;
                                    }

                                    // Company Expenses
                                    $comp_archive_query = "SELECT user_id, archived_at FROM company_archive";
                                    $comp_archive_logs = mysqli_query($con, $comp_archive_query);
                                    while ($log = mysqli_fetch_assoc($comp_archive_logs)) {
                                        $log['activity'] = 'Archived Company Expense';
                                        $logs[] = $log;
                                    }

                                    $company_restored_query = "SELECT restored_by AS user_id, restored_date AS archived_at FROM company_expense WHERE restored_date IS NOT NULL";
                                    $company_restored_logs = mysqli_query($con, $company_restored_query);
                                    while ($log = mysqli_fetch_assoc($company_restored_logs)){
                                        $log['activity'] = 'Restored Company Expense';
                                        $logs[] = $log;
                                    }


                                    // Client Payments
                                    $payment_archive_query = "SELECT user_id, archived_at, client_id FROM payment_archive";
                                    $payment_archive_logs = mysqli_query($con, $payment_archive_query);
                                    while ($log = mysqli_fetch_assoc($payment_archive_logs)) {

                                        if (!empty($log['client_id'])) {
                                            $client_id = $log['client_id'];
                                            $client_name_query = mysqli_query($con, "SELECT client_name FROM clients WHERE client_id = '$client_id' LIMIT 1");
                                            if ($client_row = mysqli_fetch_assoc($client_name_query)) {
                                                $client_name = $client_row['client_name'];
                                                
                                                // add project name into activity
                                                $log['activity'] = "Archived Client Payment - $client_name";
                                            }
                                        }

                                        $logs[] = $log;
                                    }
                                    
                                    $payment_restored_query = "SELECT restored_by AS user_id, client_id, restored_date AS archived_at FROM payment_clients WHERE restored_date IS NOT NULL";
                                    $payment_restored_logs = mysqli_query($con, $payment_restored_query);
                                    while ($log = mysqli_fetch_assoc($payment_restored_logs)) {

                                        if (!empty($log['client_id'])) {
                                            $client_id = $log['client_id'];
                                            $client_name_query = mysqli_query($con, "SELECT client_name FROM clients WHERE client_id = '$client_id' LIMIT 1");
                                            if ($client_row = mysqli_fetch_assoc($client_name_query)) {
                                                $client_name = $client_row['client_name'];
                                                
                                                // add project name into activity
                                                $log['activity'] = "Restored Client Payment - $client_name";
                                            }
                                        }

                                        $logs[] = $log;
                                    }

                                    // Sort logs by latest first
                                    usort($logs, function ($a, $b) {
                                        return strtotime($b['archived_at']) - strtotime($a['archived_at']);
                                    });

                                    
                                    $last_viewed_archive = $_SESSION['last_viewed_archive'] ?? null;

                                    $has_new_archive = $latest_archive  && (!$last_viewed_archive || strtotime($latest_archive) > strtotime($last_viewed_archive));

                                    // Output rows
                                    if (!empty($logs)) {
                                        $row_num = 1;
                                        foreach ($logs as $log) {
                                            $user_id = $log['user_id'];
                                            $timestamp = $log['archived_at'];
                                            $activity = $log['activity'];
                                            $username = $users[$user_id]['name'] ?? 'Unknown';
                                            $role = $users[$user_id]['role'] ?? 'Unknown';
                                            $email = $users[$user_id]['email'] ?? 'Unknown';
                                    ?>
                                            <tr>
                                                <td><?php echo $row_num++; ?></td>
                                                <td><?php echo $timestamp; ?></td>
                                                <td><?php echo $username; ?></td>
                                                <td><?php echo $email; ?></td>
                                                <td><?php echo $role; ?></td>
                                                 <td> <span style="
                                                        display: inline-block;
                                                        padding: 4px 8px;
                                                        border-radius: 12px;
                                                        font-size: 13px;
                                                        <?php 
                                                            if (str_contains($activity, 'Archived Project Expense')) {
                                                                echo 'background-color: #537D5D; color: #fbfdfe;';
                                                            } else if ($activity === 'Archived Company Expense') {
                                                                echo 'background-color: #9ebc8a; color: #fbfdfe;';
                                                            } else if (str_contains($activity, 'Archived Client Payment')) {
                                                                echo 'background-color: #E1EEBC; color: #183b4e;';
                                                            } else if ($activity === 'Added Account Title') {
                                                                echo 'background-color: #456882; color: #fbfdfe;';
                                                            } else if (str_contains($activity, 'Restored Project Expense')) {
                                                                echo 'background-color: #dda853; color: #fbfdfe;';
                                                            } else if (str_contains($activity, 'Restored Company Expense')) {
                                                                echo 'background-color: #f5eedc; color: #000;';
                                                            } else if (str_contains($activity, 'Restored Client Payment')) {
                                                                echo 'background-color: #ffdfaf; color: #000;';
                                                            } else {
                                                                echo 'background-color: #f8f9fa; color: #000;';
                                                            }
                                                        ?>
                                                    ">
                                                        <?php echo $activity; ?>
                                                    </span>
                                                    
                                                </td>
                                            </tr>
                                        </div>  
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                           <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                        </tr>
                                    <?php
                                    }
                                    if ($latest_archive ) {
                                        $_SESSION['last_viewed_archive'] = $latest_archive ;
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
                                There are no expenses found for the selected date range.</p>
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