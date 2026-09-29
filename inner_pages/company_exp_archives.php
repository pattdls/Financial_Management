<?php
include('../dbcon.php');
include('../layout/session_check.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Records</title>
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
            $page_title = 'Archive Records';
            include '../layout/topnav.php';
            ?>


            <div class="container-fluid px-0">
                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link" href="archive_prjctlist.php">Project Expense Archives</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="company_exp_archives.php">Company Expense Archives</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_archive.php">Project List Archives</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Table for Project Expense Archives -->
                <section id="project_expense_archives" class="account-section">
                    <div class="card-body">
                        <div class="cl-head mt-3">
                            <h4 style="font-weight: bold;">Company Expenses Archive List</h4>
                        </div>
                        <div class="card-body">
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    var table = $('#company_expense').DataTable({
                                        responsive: true,
                                        dom: 'Blfrtip',
                                        ordering: true,
                                        order: [],
                                        buttons: [],
                                        language: {
                                            search: '',
                                            searchPlaceholder: "Search expense record...",
                                            paginate: {
                                                previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                                next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                            }
                                        }

                                    });
                                    // To custom calendar for custom date range
                                    flatpickr("#customDateRangeCompany", {
                                        mode: "range",
                                        dateFormat: "Y-m-d",
                                        onClose: function(selectedDates, dateStr) {
                                            filterByDate('custom', selectedDates);
                                        }
                                    });

                                    // // If "Custom" option is selected
                                    $('#dateFilterCompany').on('change', function() {
                                        const value = $(this).val();
                                        if (value === 'custom') {
                                            $('#customDateRangeCompany').show();
                                        } else {
                                            $('#customDateRangeCompany').hide();
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
                                        });

                                        table.draw();
                                        $.fn.dataTable.ext.search.pop();
                                        if (table.rows({
                                                filter: 'applied'
                                            }).count() === 0) {
                                            noDataModal.show();
                                        }
                                    }

                                    // Ensure DataTables wrapper is ready before manipulating
                                    setTimeout(function() {
                                        // Create toolbar container
                                        var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');

                                        // Append elements in correct order
                                        toolbar.append($('#company_expense_wrapper .dataTables_length'));
                                        toolbar.append($('#dateRangeContainer'));
                                        toolbar.append($('#company_expense_wrapper .dataTables_filter'));

                                        // Insert toolbar before the table wrapper
                                        $('#company_expense_wrapper').before(toolbar);
                                    }, 0);
                                });
                            </script>
                            <?php

                            $con =  mysqli_connect("localhost", "root", "", "financial_management");

                            //This condition is to check whether date range is set or not. If not, all data is displayed
                            if (isset($_SESSION['company_archive_start']) && isset($_SESSION['company_archive_end'])) {
                                $start_archive_period = $_SESSION['company_archive_start'];
                                $end_archive_period = $_SESSION['company_archive_end'];

                                $fetch_query = "SELECT * FROM company_archive WHERE expense_date BETWEEN '$start_archive_period' AND '$end_archive_period' AND is_restored = 0 ORDER BY expense_date ASC";
                            } else {

                                $fetch_query = "SELECT * FROM company_archive WHERE is_restored = 0 ORDER BY archived_at ASC";
                            }


                            $fetch_query_run = mysqli_query($con, $fetch_query);

                            ?>
                            <div id="dateRangeWrapper" style="display: contents">
                                <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <label for="dateFilter" class="mb-0" style="font-size: 16px;">Filter Date by:</label>
                                    <select id="dateFilterCompany" class="form-select form-select-sm w-auto">
                                        <option value="all">All</option>
                                        <option value="weekly">This Week</option>
                                        <option value="monthly">This Month</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                    <input type="text" id="customDateRangeCompany" class="forDate form-control form-control-sm w-auto"
                                        placeholder="Select date range" style="display: none;" />
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" style="width: 100%;" id="company_expense">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Archive Date</th>
                                            <th scope="col">Expense Date</th>
                                            <th scope="col">Description</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php

                                        if (mysqli_num_rows($fetch_query_run) > 0) {
                                            $rowNumber = 1;
                                            while ($row = mysqli_fetch_array($fetch_query_run)) {
                                                $archive_id = $row['archive_id'];
                                                $expense_date = $row['expense_date'];
                                                $archived_at = date("Y-m-d", strtotime($row['archived_at']));
                                                $description = $row['description'];
                                                $category = $row['category'];
                                                $payment_method = $row['payment_method'];
                                                $store_name = $row['store_name'];
                                                $amount = $row['amount'];
                                                $invoice_num = $row['invoice_num'];
                                                $receipt_file = $row['receipt_file'];


                                        ?>
                                                <tr>
                                                    <td><?php echo $rowNumber++; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['archived_at'])); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>
                                                    <td><?php echo ucwords($row['description']); ?></td>
                                                    <td><?php echo $row['category']; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            &#8369; <?php echo number_format($row['amount'], 2); ?>
                                                            <div class="dropdown" data-bs-display="static">
                                                                <button class="three_dots btn btn-light btn-sm ms-1" type="button">
                                                                    <i class="bi bi-three-dots"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <li><a class="dropdown-item fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#viewExpenseModal<?php echo $archive_id; ?>">View Details</a></li>
                                                                    <li><a class="dropdown-item fw-bold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#viewConfirmRestore<?php echo $archive_id; ?>">Restore</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <!-- View Expense Modal -->
                                                <div class="modal fade" id="viewExpenseModal<?php echo $archive_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-md">
                                                        <div class="modal-content">
                                                            <div class="modal-header" style="padding: 12px;">
                                                                <h5 class="modal-title" id="viewExpenseModal<?php echo $archive_id; ?>">Expense Details</h5>
                                                                <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                                <!-- Right Column -->
                                                                <p><strong style="font-size: 16px;"><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></strong><br>
                                                                    <span style="font-size: 12px; color: #555;"><i>Recorded Expense Date</i></span>
                                                                </p>
                                                                <p style="font-size: 16px;"><?php echo $row['description'] . ' - ' . ucwords($row['category']) . ' - ' . $row['store_name']; ?><br>
                                                                    <span style="font-size: 12px; color: #555;"><i>Item, Category, & Store Name</i></span>
                                                                </p>
                                                                <p style="font-size: 16px;">&#8369; <?php echo number_format($row['amount'], 2) . ' - ' . $row['payment_method']; ?><br>
                                                                    <span style="font-size: 12px; color: #555;"><i>Amount & Payment Method</i></span>
                                                                </p>
                                                                <!-- Inline Invoice Number and Button -->
                                                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                                    <p style="font-size: 16px;"><?php echo $row['invoice_num']; ?><br>
                                                                        <span style="font-size: 12px; color: #555;"><i>Invoice Number</i></span>
                                                                    </p>
                                                                    <!-- This condition will check if an attachment exists and displays the button. -->
                                                                    <?php if (!empty($receipt_file) && file_exists('../receipts/company_expense_files/' . $receipt_file)): ?>
                                                                        <a href="../receipts/company_expense_files/<?php echo htmlspecialchars($receipt_file); ?>" target="_blank" style="text-decoration: none;">
                                                                            <button type="button" class="btn btn-outline-success btn-sm">
                                                                                View Receipt Attachment
                                                                            </button>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <span style="font-size: 16px; color: #888;">No Receipt Available</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <!-- Newly Added -->
                                                                <?php
                                                                $user_id = $row['user_id'] ?? null;
                                                                $user_name = '';
                                                                $user_role = '';

                                                                if ($user_id) {
                                                                    $user_query = mysqli_query($con, "SELECT name, role FROM users WHERE id = $user_id");
                                                                    if ($user_query && $user_data = mysqli_fetch_assoc($user_query)) {
                                                                        $user_name = $user_data['name'];
                                                                        $user_role = $user_data['role'];
                                                                    }
                                                                }
                                                                ?>

                                                                <button class="btn btn-sm btn-outline-secondary mt-2 toggle-logs-btn"
                                                                    type="button"
                                                                    data-target="#logs<?php echo $archive_id; ?>">
                                                                    <i class="bi bi-chevron-bar-down"></i>
                                                                </button>
                                                                <!-- Collapsible Logs Section -->
                                                                <div class="custom-collapse" id="logs<?php echo $archive_id; ?>">
                                                                    <div class="highlight-box-charts" style="font-size: 12px; color: #555;">
                                                                        <p class="mt-2 mb-2">Archived by: <?php echo htmlspecialchars($user_name); ?> | <?php echo ucfirst(htmlspecialchars($user_role)); ?></p>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>
                                                 </div>

                                                    <!-- Restore Modal -->
                                                    <div class="modal fade" id="viewConfirmRestore<?php echo $archive_id; ?>" tabindex="-1" aria-labelledby="restoreLabel<?php echo $archive_id; ?>" aria-hidden="true">
                                                        <div class="modal-dialog modal-md">
                                                            <div class="modal-content">
                                                                <div class="modal-header" style="padding: 12px;">
                                                                    <h5 class="modal-title" id="restoreLabel<?php echo $archive_id; ?>">Confirm Restore</h5>
                                                                    <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body" style="line-height: 1.5; margin-bottom: 0; padding: 15px 15px 0 15px;">
                                                                    <form method="POST" action="../forms_logic/restore.php">
                                                                            <input type="hidden" name="id" value="<?php echo $archive_id; ?>">
                                                                            <p style="font-size: 16px;">
                                                                                You are about to restore the company expense record
                                                                                <strong><?php echo $row['category'] . ' - ' . ucwords($row['description']); ?></strong>.

                                                                            </p>
                                                                            <p><strong>Do you wish to proceed?</strong></p>

                                                                            <div class="modal-footer" style="border-top: none; padding-top: 0; padding-bottom: 20px;">
                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" name="compExp_restore_btn" class="btn btn-danger">Restore</button>
                                                                            </div>

                                                                        </form>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                            <?php
                                            }
                                            ?>

                                        <?php
                                        } else {
                                        ?>
                                            <!-- This is to make sure that data tables will still work despite having empty table data -->
                                            <tr>
                                                <td style="text-align:center;"><span class="text-muted">#</span></td>
                                                <td style="text-align:center;"><span class="text-muted">YYY-MM-DD</span></td>
                                                <td style="text-align:center;"><span class="text-muted">YYY-MM-DD</span></td>
                                                <td style="text-align:center;"><span class="text-muted">—</span></td>
                                                <td style="text-align:center;"><span class="text-muted">—</span></td>
                                                <td style="text-align:center;"><span class="text-muted">—</span></td>
                                            </tr>
                                        <?php
                                        }

                                        ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <script>
                            // For dropdown menu three dots
                            document.querySelectorAll('.three_dots').forEach(btn => {
                                btn.addEventListener('click', function(e) {
                                    e.stopPropagation();

                                    // Close all other dropdowns first
                                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                                        if (menu !== btn.nextElementSibling) {
                                            menu.classList.remove('show');
                                        }
                                    });

                                    // Toggle current one
                                    const menu = btn.nextElementSibling;
                                    menu.classList.toggle('show');
                                });
                            });

                            document.addEventListener('click', function() {
                                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                                    menu.classList.remove('show');
                                });
                            });

                            //For toggle logs
                            document.querySelectorAll('.toggle-logs-btn').forEach(btn => {
                                const target = document.querySelector(btn.dataset.target);
                                if (!target) return;

                                btn.addEventListener('click', () => {
                                    const isOpen = btn.classList.contains('active');

                                    if (isOpen) {
                                        // Collapse
                                        target.style.height = target.scrollHeight + 'px'; // fix jump
                                        requestAnimationFrame(() => {
                                            target.style.height = '0';
                                        });
                                        target.addEventListener('transitionend', () => {
                                            target.style.height = '';
                                        }, {
                                            once: true
                                        });

                                        btn.classList.remove('active');
                                        btn.querySelector('i').style.transform = 'rotate(0deg)';
                                    } else {
                                        // Expand
                                        target.style.height = '0';
                                        target.classList.add('active');
                                        requestAnimationFrame(() => {
                                            target.style.height = target.scrollHeight + 'px';
                                        });
                                        target.addEventListener('transitionend', () => {
                                            target.style.height = 'auto';
                                        }, {
                                            once: true
                                        });

                                        btn.classList.add('active');
                                        btn.querySelector('i').style.transform = 'rotate(180deg)';
                                    }
                                });
                            });
                        </script>
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
                        <!-- Modal for Restore Notification -->
                        <?php if (!empty($_SESSION['restore_status'])): ?>
                            <div class="modal fade" id="restoreModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content text-center p-4">
                                        <?php if ($_SESSION['restore_status_type'] === 'success'): ?>
                                            <div class="d-flex justify-content-center mb-3">
                                                <div class="rounded-circle bg-info bg-gradient d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        width="50" height="50"
                                                        viewBox="0 0 24 24"
                                                        fill="white"
                                                        stroke="white"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <rect width="20" height="5" x="2" y="3" rx="1" />
                                                        <path d="M4 8v11a2 2 0 0 0 2 2h2" />
                                                        <path d="M20 8v11a2 2 0 0 1-2 2h-2" />
                                                        <path d="m9 15 3-3 3 3" />
                                                        <path d="M12 12v9" />
                                                    </svg>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill text-danger" style="font-size:70px;"></i>
                                        <?php endif; ?>
                                        <h5 class="mt-3"><?php echo $_SESSION['restore_status']; ?></h5>
                                        <?php
                                        echo '<div style="font-size: 15px;">' .
                                            ($_SESSION['restore_status_type'] === 'success'
                                                ? "The archived company expense has been returned to active records successfully."
                                                : "Something went wrong while restoring the expense.") .
                                            '</div>';
                                        ?>
                                        <div class="modal-footer border-0 d-flex justify-content-center">
                                            <button type="button" class="btn btn-info text-light" data-bs-dismiss="modal">Okay</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    new bootstrap.Modal(document.getElementById('restoreModal')).show();
                                });
                            </script>
                        <?php
                            unset($_SESSION['restore_status'], $_SESSION['restore_status_type']);
                        endif;
                        ?>

                        <script>
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

                            document.addEventListener("DOMContentLoaded", function() {
                                document.getElementById("expense-date").valueAsDate = new Date(); // Auto-fill date


                            });


                            flatpickr("#expense-date", {
                                dateFormat: "Y-m-d",
                                defaultDate: "today", //Auto fill the date
                            });
                            document.getElementById('btnClear').addEventListener('click', function(e) {
                                e.preventDefault();
                                document.querySelector('[name="start_archive_company"]').value = '';
                                document.querySelector('[name="end_archive_company"]').value = '';
                                window.location.href = 'expense_logic.php?clear=true';
                            });
                        </script>

</body>

</html>