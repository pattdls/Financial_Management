<?php
include('../dbcon.php');
include('../layout/session_check.php');
date_default_timezone_set('Asia/Manila');
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
        <div class="main-content container-fluid">

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
                        <a class="nav-link active" href="fs_access_logs.php">
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
                    <div class="card-body px-0 pe-3">
                        <div class="cl-head mt-3">
                            <h4 style="font-weight: bold;">FS Access Logs</h4>
                        </div>
                        <div class="card-body">
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
                                            // Clear all filters
                                            table.search('').columns().search('').draw();
                                            if (table.rows({
                                                    filter: 'applied'
                                                }).count() === 0) {
                                                noDataModal.show();
                                            }
                                            return;
                                        }

                                        // Remove existing date filter
                                        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                                            return fn.toString().indexOf('dateFilter') === -1;
                                        });

                                        // Add new date filter
                                        $.fn.dataTable.ext.search.push(function dateFilter(settings, data) {
                                            const dateStr = data[4]; // Login Time column
                                            if (!dateStr || dateStr.includes('No login')) {
                                                return false;
                                            }

                                            const rowDate = new Date(dateStr);
                                            rowDate.setHours(0, 0, 0, 0);

                                            const start = new Date(startDate);
                                            const end = new Date(endDate);
                                            start.setHours(0, 0, 0, 0);
                                            end.setHours(0, 0, 0, 0);

                                            return rowDate >= start && rowDate <= end;
                                        });

                                        table.draw();

                                        if (table.rows({
                                                filter: 'applied'
                                            }).count() === 0) {
                                            noDataModal.show();
                                        }
                                    }

                                    // Activity filter functionality
                                     $('#activityFilter').on('change', function() {
                                        table.draw();
                                    });

                                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                                        const selectedActivity = $('#activityFilter').val();
                                        const rowActivity = data[6].trim();

                                        if (!selectedActivity) return true;

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
                                        $('#project_expense_wrapper').before(toolbar);
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
                                        <option value="">All</option>
                                         <option value="Accessed Project">Access to Project Specific</option>
                                        <option value="Accessed RVR SMES">Access to RVR SMES</option>
                                    </select>

                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mt-3" id="project_expense" style="overflow: hidden; vertical-align: middle;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Access Time</th>
                                            <th>Attempts</th>
                                            <th>Component/Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT identifier, login_id, description, MAX(last_attempt_time) AS access_time,
                                        CASE WHEN MAX(last_status) = 'success' THEN COUNT(*) ELSE 'Unsuccessful' END AS attempts_before_success,
                                        MAX(last_status) AS final_status FROM fs_attempt_logs GROUP BY login_id, identifier, description ORDER BY access_time DESC";

                                        $result = $conn->query($sql);

                                        $logs = [];
                                            if ($result && $result->num_rows > 0) {
                                                while ($r = $result->fetch_assoc()) {
                                                    $logs[] = $r;
                                                }
                                            }

                
                                            if (!empty($logs)) {
                                                usort($logs, function ($a, $b) {
                                                    return strtotime($b['access_time']) - strtotime($a['access_time']);
                                                });
                                            }

                                        // Get last viewed FS access log time from session
                                            $last_viewed_fsAccess = $_SESSION['last_viewed_fsAccess'] ?? null;

                                            // Find latest FS access log
                                            $latest_fs = !empty($logs) ? $logs[0]['access_time'] : null;

                                            // Check if there are new FS logs (compare first)
                                            $has_new_fsAccess = $latest_fs &&
                                                (!$last_viewed_fsAccess || strtotime($latest_fs) > strtotime($last_viewed_fsAccess));
                                        
                                            $row_num = 1;

                                            if (!empty($logs)): 
                                                foreach ($logs as $row):
                                                    // get user details manually by email
                                                    $user_sql = "SELECT name, role FROM users WHERE email = '" . $conn->real_escape_string($row['identifier']) . "' LIMIT 1";
                                                    $user_result = $conn->query($user_sql);
                                                    $user = $user_result && $user_result->num_rows > 0 ? $user_result->fetch_assoc() : ['name' => 'Unknown', 'role' => 'Unknown'];

                                                    $name = $user['name'];
                                                    $email = $row['identifier'];
                                                    $role = $user['role'];
                                                    $access_time = $row['access_time'];
                                                    $attempts = $row['attempts_before_success'];
                                                    $activity = $row['description'];
                                                    ?>
                                                <tr>
                                                    <td><?php echo $row_num++; ?></td>
                                                    <td><?php echo htmlspecialchars($name); ?></td>
                                                    <td><?php echo htmlspecialchars($email); ?></td>
                                                    <td><?php echo htmlspecialchars($role); ?></td>
                                                    <td><?php echo htmlspecialchars($access_time); ?></td>
                                                    <td>
                                                         <?php if ($attempts === 'Unsuccessful'): ?>
                                                            <span class="badge-unsuccess"><?php echo htmlspecialchars($attempts); ?></span>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($attempts); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                     <td>
                                                         <span style="
                                                            display: inline-block;
                                                            padding: 4px 8px;
                                                            border-radius: 12px;
                                                            font-size: 13px;
                                                            <?php
                                                            if (str_contains($activity, 'RVR SMES')) {
                                                                echo 'background-color: #537D5D; color: #fbfdfe;';
                                                            } else if (str_contains($activity, 'Accessed Project')) {
                                                                echo 'background-color: #E1EEBC; color: #183b4e;';
                                                            } else {
                                                                echo 'background-color: #f8f9fa; color: #000;';
                                                            }
                                                            ?>
                                                        ">
                                                            <?php echo $activity; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php 
                                            if ($latest_fs) {
                                                $_SESSION['last_viewed_fsAccess'] = $latest_fs;
                                            }
                                            ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" style="text-align: center;">No Record Found</td>
                                            </tr>
                                        <?php endif; ?>
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