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
        <div class="main-content container-fluid">

            <!-- Top Nav -->
            <?php
            $page_title = 'Archive Records';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-0">
                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link active" href="archive_prjctlist.php">Project Expense Archives</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="company_exp_archives.php">Company Expense Archives</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_archive.php">Project List Archives</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>
                <div style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                    <ol class="breadcrumb mt-4" id="projects-breadcrumb">
                        <li class="breadcrumb-item" aria-current="page" id="projects-li"><a href="archive_prjctlist.php">Projects with Archived Records</a></li>
                        <li class="breadcrumb-item active" id="details-li">Archived Payments List</li>
                    </ol>
                </div>
                <?php
                $con = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

                $client_name = "N/A";
                $project_name = "N/A";

                if (isset($_GET['view_project'])) {
                    $project_id = intval($_GET['view_project']);
                    $project_result = mysqli_query($conn, "SELECT project_name, client_id FROM projects WHERE project_id = '$project_id'");
                    $project_row = mysqli_fetch_assoc($project_result);
                    $project_name = $project_row['project_name'] ?? "Unknown Project";
                    
                    $client_id = $project_row['client_id'] ?? 0;
                    $client_result = mysqli_query($conn, "SELECT client_name FROM clients WHERE client_id = '$client_id'");
                    $client_row = mysqli_fetch_assoc($client_result);
                    $client_name = $client_row['client_name'] ?? "Unknown Client";

                    // Fetch archive records for this project
                    $fetch_query_run = mysqli_query($conn, "SELECT * FROM payment_archive WHERE project_id = '$project_id' AND is_restored = 0 ORDER BY archived_at DESC");

                    if (mysqli_num_rows($fetch_query_run) > 0) {

                ?>
                        <!-- MOdal for no record found -->
                        <div class="modal fade" id="noDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content text-center p-4">
                                    <i class="bi bi-x-circle-fill text-danger" style="font-size:70px;"></i>
                                    <h4 class="mt-3">No Record Found</h4>
                                    <p style="text-align: center; font-size: 16px;">
                                        There are no payment record found for the selected date range.</p>
                                    <div class="modal-footer border-0 d-flex justify-content-center">
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Okay</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Table for Project Expense Archives -->
                        <section id="project_expense_archives" class="account-section">
                            <div class="card-body">
                                <div class="mt-0">
                                    <h4><?php echo $project_name; ?></h4>
                                    <span class="badge custom-client-badge d-inline-flex align-items-center mb-3 p-2" style="font-size:1rem; gap:6px; background-color:#f0f4f8; color:#183b4e;">
                                        <!-- User Icon SVG -->
                                        <svg viewBox="-102.4 -102.4 1228.80 1228.80" xmlns="http://www.w3.org/2000/svg" fill="#000000" width="28" height="28"
                                            style="vertical-align: middle;" stroke="#000000" stroke-width="0.01024">
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M512 505.6c-108.8 0-204.8-89.6-204.8-204.8S396.8 102.4 512 102.4c108.8 0 204.8 89.6 204.8 204.8S620.8 505.6 512 505.6z m0-358.4c-83.2 0-153.6 70.4-153.6 153.6s64 153.6 153.6 153.6 153.6-70.4 153.6-153.6S595.2 147.2 512 147.2z" fill="#183b4e"></path>
                                                <path d="M832 864c0-211.2-147.2-377.6-326.4-377.6s-326.4 166.4-326.4 377.6H832z" fill="#1f628e"></path>
                                                <path d="M832 889.6H147.2v-25.6c0-224 160-403.2 352-403.2s352 179.2 352 396.8v25.6l-19.2 6.4z m-633.6-51.2h608C800 659.2 665.6 512 505.6 512c-166.4 0-294.4 147.2-307.2 326.4zM710.4 499.2c-12.8 0-25.6-12.8-25.6-25.6s12.8-25.6 25.6-25.6c64 0 121.6-51.2 121.6-121.6 0-51.2-32-96-83.2-115.2-12.8-6.4-19.2-19.2-12.8-32 6.4-12.8 19.2-19.2 32-12.8 70.4 19.2 115.2 83.2 115.2 160-6.4 96-83.2 172.8-172.8 172.8z" fill="#183b4e"></path>
                                                <path d="M966.4 806.4h-57.6c-12.8 0-25.6-12.8-25.6-25.6s12.8-25.6 25.6-25.6h32c-12.8-140.8-115.2-249.6-236.8-249.6-12.8 0-25.6-12.8-25.6-25.6s12.8-25.6 25.6-25.6c160 0 288 147.2 288 326.4v25.6h-25.6z" fill="#183b4e"></path>
                                            </g>
                                        </svg>
                                        <span class="fw-bold"><?php echo $client_name; ?></span>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <script type="text/javascript">
                                        $(document).ready(function() {
                                            var table = $('#payment_table').DataTable({
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

                                            var noDataModal = new bootstrap.Modal(
                                                document.getElementById('noDataModal'), {
                                                    backdrop: 'static',
                                                    keyboard: false
                                                }
                                            );

                                            function filterByDate(range, customDates = []) {
                                                const today = new Date();
                                                let startDate, endDate;

                                                if (range === 'weekly') {
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
                                                    const dateStr = data[0];
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

                                            // Flatpickr
                                            flatpickr("#customDateRangePayment", {
                                                mode: "range",
                                                dateFormat: "Y-m-d",
                                                onClose: function(selectedDates) {
                                                    filterByDate('custom', selectedDates);
                                                }
                                            });

                                            $('#dateFilterPayment').on('change', function() {
                                                const value = $(this).val();
                                                if (value === 'custom') {
                                                    $('#customDateRangePayment').show();
                                                } else {
                                                    $('#customDateRangePayment').hide();
                                                    filterByDate(value);
                                                }
                                            });

                                            // Toolbar
                                            setTimeout(function() {
                                                var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');
                                                toolbar.append($('#payment_table_wrapper .dataTables_length'));
                                                toolbar.append($('#dateRangeContainer'));
                                                toolbar.append($('#payment_table_wrapper .dataTables_filter'));
                                                $('#payment_table_wrapper').before(toolbar);
                                            }, 0);
                                        });
                                    </script>
                                    <div id="dateRangeWrapper" style="display: contents">
                                        <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                                            <label for="dateFilter" class="mb-0" style="font-size: 16px;">Filter Date by:</label>
                                            <select id="dateFilterPayment" class="form-select form-select-sm w-auto">
                                                <option value="all">All</option>
                                                <option value="weekly">This Week</option>
                                                <option value="monthly">This Month</option>
                                                <option value="custom">Custom Range</option>
                                            </select>
                                            <input type="text" id="customDateRangePayment" class="forDate form-control form-control-sm w-auto"
                                                placeholder="Select date range" style="display: none;" />
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                    <table class="table table-bordered" id="payment_table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Archive Date</th>
                                                <th scope="col">Payment Date</th>
                                                <th scope="col">Description</th>
                                                <th scope="col">Category</th>
                                                <th scope="col">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($fetch_query_run)) {
                                                $archive_id = $row['archive_id'];
                                                $receipt_file = $row['receipt_file'];
                                            ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($row['archived_at'])); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
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
                                                                    <li><a class="dropdown-item fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#viewPaymentModal<?php echo $archive_id; ?>">View Details</a></li>
                                                                    <li><a class="dropdown-item fw-bold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#viewConfirmRestore<?php echo $archive_id; ?>">Restore</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>


                                                <div class="modal fade" id="viewPaymentModal<?php echo $archive_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-md">
                                                        <div class="modal-content">
                                                            <div class="modal-header" style="padding: 12px;">
                                                                <h5 class="modal-title" id="viewExpenseModal<?php echo $archive_id; ?>">Payment Details</h5>
                                                                <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                                <!-- Right Column -->
                                                                <p><strong style="font-size: 16px;"><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></strong><br>
                                                                    <span style="font-size: 12px; color: #555;"><i>Recorded Payment Date</i></span>
                                                                </p>
                                                                <p><strong style="font-size: 16px;"><?php echo $client_name . ' - ' . $project_name; ?></strong></p>
                                                                <p style="font-size: 16px;"><?php echo $row['category'] . ' - ' . ucwords($row['description']); ?><br>
                                                                    <span style="font-size: 12px; color: #555;"><i>Category & Description</i></span>
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
                                                                    <?php if (!empty($receipt_file) && file_exists('../receipts/client_payment_files/' . $receipt_file)): ?>
                                                                        <a href="../receipts/client_payment_files/<?php echo htmlspecialchars($receipt_file); ?>" target="_blank" style="text-decoration: none;">
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
                                                                    $user_query = mysqli_query($conn, "SELECT name, role FROM users WHERE id = $user_id");
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
                                                <div class="modal fade" id="viewConfirmRestore<?php echo $archive_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-md">
                                                        <div class="modal-content">
                                                            <div class="modal-header" style="padding: 12px;">
                                                                <h5 class="modal-title" id="viewConfirmRestore<?php echo $archive_id; ?>">Confirm Restore</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                                <div class="modal-body" style="line-height: 1.5; margin-bottom: 0; padding: 15px 15px 0 15px;">                                                                <form method="POST" action="../forms_logic/restore.php">
                                                                <form method="POST" action="../forms_logic/restore.php">
                                                                    <input type="hidden" name="id" value="<?php echo $archive_id; ?>">
                                                                    <p style="font-size: 16px;">
                                                                        You are about to restore a payment record
                                                                        <strong><?php echo ucwords($row['description']); ?></strong>,
                                                                        from client <strong><?php echo $client_name; ?></strong> under the project of <strong><?php echo $project_name; ?></strong>.

                                                                    </p>
                                                                    <p><strong>Do you wish to proceed?</strong></p>

                                                                    <div class="modal-footer" style="padding-bottom: 20px; border: none;">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="payment_restore_btn" class="btn btn-danger">Restore</button>
                                                                    </div>

                                                                </form>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                </div>
                            <?php } ?>
                            </tbody>
                            </table>
                            </div>
                        <?php
                    } else {
                        ?>
                            <div class="mt-0">
                                <h4><?php echo $project_name; ?></h4>
                                <span class="badge custom-client-badge d-inline-flex align-items-center mb-3 p-2" style="font-size:1rem; gap:6px; background-color:#f0f4f8; color:#183b4e;">
                                    <!-- User Icon SVG -->
                                    <svg viewBox="-102.4 -102.4 1228.80 1228.80" xmlns="http://www.w3.org/2000/svg" fill="#000000" width="28" height="28"
                                        style="vertical-align: middle;" stroke="#000000" stroke-width="0.01024">
                                        <g id="SVGRepo_iconCarrier">
                                            <path d="M512 505.6c-108.8 0-204.8-89.6-204.8-204.8S396.8 102.4 512 102.4c108.8 0 204.8 89.6 204.8 204.8S620.8 505.6 512 505.6z m0-358.4c-83.2 0-153.6 70.4-153.6 153.6s64 153.6 153.6 153.6 153.6-70.4 153.6-153.6S595.2 147.2 512 147.2z" fill="#183b4e"></path>
                                            <path d="M832 864c0-211.2-147.2-377.6-326.4-377.6s-326.4 166.4-326.4 377.6H832z" fill="#1f628e"></path>
                                            <path d="M832 889.6H147.2v-25.6c0-224 160-403.2 352-403.2s352 179.2 352 396.8v25.6l-19.2 6.4z m-633.6-51.2h608C800 659.2 665.6 512 505.6 512c-166.4 0-294.4 147.2-307.2 326.4zM710.4 499.2c-12.8 0-25.6-12.8-25.6-25.6s12.8-25.6 25.6-25.6c64 0 121.6-51.2 121.6-121.6 0-51.2-32-96-83.2-115.2-12.8-6.4-19.2-19.2-12.8-32 6.4-12.8 19.2-19.2 32-12.8 70.4 19.2 115.2 83.2 115.2 160-6.4 96-83.2 172.8-172.8 172.8z" fill="#183b4e"></path>
                                            <path d="M966.4 806.4h-57.6c-12.8 0-25.6-12.8-25.6-25.6s12.8-25.6 25.6-25.6h32c-12.8-140.8-115.2-249.6-236.8-249.6-12.8 0-25.6-12.8-25.6-25.6s12.8-25.6 25.6-25.6c160 0 288 147.2 288 326.4v25.6h-25.6z" fill="#183b4e"></path>
                                        </g>
                                    </svg>
                                    <span class="fw-bold"><?php echo $client_name; ?></span>
                                </span>
                            </div>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 80px; color: #aaa;"></i>
                                <h5>No records found</h5>
                                <p class="text-muted">There are no archived payment records for this project yet.</p>
                            </div>
                        <?php
                } 
            }else {
                        ?>
                        <div class="text-center py-5">
                            <i class="bi bi-folder2-open" style="font-size: 3rem; color: #aaa;"></i>
                            <h5>Please select a project</h5>
                            <p class="text-muted">Choose a project from the dropdown to view archived payment records.</p>
                        </div>
                    <?php
                }
                    ?>
                            </div>
                        </section>
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
                                    <rect width="20" height="5" x="2" y="3" rx="1"/>
                                    <path d="M4 8v11a2 2 0 0 0 2 2h2"/>
                                    <path d="M20 8v11a2 2 0 0 1-2 2h-2"/>
                                    <path d="m9 15 3-3 3 3"/>
                                    <path d="M12 12v9"/>
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
                                ? "The archived payment record has been returned to active records successfully."
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

                document.addEventListener("DOMContentLoaded", function() {
                    flatpickr("#expense-date", {
                        dateFormat: "Y-m-d",
                        defaultDate: "today", //Auto fill the date
                    });
                });
            </script>

</body>

</html>