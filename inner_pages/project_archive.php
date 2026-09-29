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
                        <a class="nav-link" href="company_exp_archives.php">Company Expense Archives</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="project_archive.php">Project List Archives</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Table for Project Expense Archives -->
                <section id="project_expense_archives" class="account-section">
                    <div class="card-body">
                        <div class="cl-head mt-3">
                            <h4 style="font-weight: bold;">Project Archive List</h4>
                        </div>
                        <div class="card-body">
                            <!-- For DATE RANGE -->
                            <div id="dateRangeContainer">
                                <form action="../forms_logic/archive.php" id="dateFilterField" method="GET">
                                </form>
                            </div>
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    $('#company_expense').DataTable({
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
                                    // Wrap DataTables controls in a flex container
                                    var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');
                                    toolbar.append($('#company_expense_wrapper .dataTables_length'));
                                    toolbar.append($('#dateRangeContainer'));
                                    toolbar.append($('#company_expense_wrapper .dataTables_filter'));

                                    // Insert the toolbar container after the table wrapper
                                    $('#company_expense_wrapper').prepend(toolbar);
                                });

                                flatpickr(".datepicker", {
                                    dateFormat: "Y-m-d"
                                });
                            </script>
                            <?php

                            $con = mysqli_connect("localhost", "root", "", "financial_management");

                            ?>
                            <div class="table-responsive">
                            <table class="table table-bordered" style="width: 100%;" id="company_expense">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Archive Date</th>
                                        <th scope="col">Client Name</th>
                                        <th scope="col">Project Name</th>
                                        <!-- <th scope="col">Total Budget Cost</th> -->
                                        <th scope="col">Contract</th>
                                        <!-- <th scope="col">Start Date</th> -->
                                        <!-- <th scope="col">End Date</th> -->
                                        <!-- <th scope="col">Area</th> -->
                                        <!-- <th scope="col">Completed</th> -->
                                        <th scope="col">P.O Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php

                                    $fetch_query = "SELECT * FROM project_archive ORDER BY archived_at DESC";
                                    $fetch_query_run = mysqli_query($con, $fetch_query);


                                    if (mysqli_num_rows($fetch_query_run) > 0) {
                                        $rowNumber = 1;
                                        while ($row = mysqli_fetch_array($fetch_query_run)) {
                                            $archive_id = $row['archive_id'];
                                            $project_name = $row['project_name'];
                                            $projected_budget_cost = $row['projected_budget_cost'];
                                            // $file_path = $row['file_path'];
                                            $po_number = $row['po_num'];
                                            $start_date = $row['start_date'];
                                            $end_date = $row['end_date'];
                                            $project_type = $row['project_type'];
                                            $completed_date = $row['completed_date'];
                                    ?>
                                            <tr>
                                                <!-- Row Num -->
                                                <td><?php echo $rowNumber++; ?></td>

                                                <!-- Archived at -->
                                                <td><?php echo date('M d, Y', strtotime($row['archived_at'])); ?></td>

                                                <!-- Client Name -->
                                                <?php
                                                $client_name = '';
                                                $client_id = $row['client_id'] ?? null;
                                                if ($client_id) {
                                                    $client_query = mysqli_query($con, "SELECT client_name FROM clients WHERE client_id = $client_id LIMIT 1");
                                                    if ($client_query && $client_row = mysqli_fetch_assoc($client_query)) {
                                                        $client_name = $client_row['client_name'];
                                                    }
                                                }
                                                ?>
                                                <td><?php echo htmlspecialchars($client_name); ?></td>

                                                <!-- Project Name -->
                                                <td><?php echo htmlspecialchars($row['project_name']); ?></td>

                                                <!-- Total Budget Cost -->
                                                <!-- <td><?php echo number_format($row['projected_budget_cost'], 2); ?></td> -->

                                                <!-- Contract -->
                                                <?php
                                                $project_id = $row['project_id'];
                                                $contracts_query = mysqli_query($con, "SELECT file_path FROM contracts WHERE project_id = $project_id");

                                                echo "<td>";
                                                if (mysqli_num_rows($contracts_query) > 0) {
                                                    while ($contract = mysqli_fetch_assoc($contracts_query)) {
                                                        echo '<a href="../contracts/' . htmlspecialchars($contract['file_path']) . '" target="_blank">View Contract</a><br>';
                                                    }
                                                } else {
                                                    echo "No Contract";
                                                }
                                                echo "</td>";
                                                ?>

                                                <!-- P.O Number -->
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <?php echo htmlspecialchars($row['po_num']); ?>
                                                        <div class="dropdown" data-bs-display="static">
                                                            <button class="three_dots btn btn-light btn-sm ms-1" type="button">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#viewExpenseModal<?php echo $archive_id; ?>">View Details</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- <td><?php echo htmlspecialchars($row['completed_date']); ?></td> -->

                                            </tr>

                                            <?php
                                            $client_email = '';
                                            $client_phone = '';
                                            $contacts = [];

                                            $client_id = $row['client_id'] ?? null;

                                            if ($client_id) {

                                                // To fetch client type
                                                $type_query = mysqli_query($con, "SELECT client_type FROM clients WHERE client_id = $client_id LIMIT 1");
                                                if ($type_query && $type_row = mysqli_fetch_assoc($type_query)) {
                                                    $client_type = $type_row['client_type'];
                                                }

                                                // if indiv, fetch contact deets within the same table
                                                if (strtolower($client_type) === 'individual') {
                                                    $client_query = mysqli_query($con, "SELECT email, phone_number FROM clients WHERE client_id = $client_id LIMIT 1");
                                                    if ($client_query && $client_row = mysqli_fetch_assoc($client_query)) {
                                                        $client_email = $client_row['email'];
                                                        $client_phone = $client_row['phone_number'];
                                                    }
                                                    // if company, fetch contact deets from contacts db table
                                                } elseif (strtolower($client_type) === 'company') {
                                                    $contacts_query = mysqli_query($con, "SELECT email, phone_number, designation FROM contacts WHERE client_id = $client_id");
                                                    if ($contacts_query) {
                                                        while ($contact_row = mysqli_fetch_assoc($contacts_query)) {
                                                            $contacts[] = $contact_row;
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
                                            <!-- View Project Details -->
                                            <div class="modal fade" id="viewExpenseModal<?php echo $archive_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-md">
                                                    <div class="modal-content">
                                                        <div class="modal-header" style="padding: 12px;">
                                                            <h5 class="modal-title" id="viewExpenseModal<?php echo $archive_id; ?>">Project Details</h5>
                                                            <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                            <!-- Right Column -->
                                                            <p><strong style="font-size: 16px;"><?php echo date('M d, Y', strtotime($row['archived_at'])); ?></strong><br>
                                                                <span style="font-size: 12px; color: #555;"><i>Archive Date</i></span>
                                                            </p>
                                                            <p><strong style="font-size: 16px;"><?php echo htmlspecialchars($client_name) . ' - ' . ucwords($row['project_name']); ?></strong><br>
                                                                <span style="font-size: 12px; color: #555;"><i>Client Name & Project Name</i></span>
                                                            </p>
                                                            <!-- For displaying the contact details -->
                                                            <?php if (strtolower($client_type) === 'individual'): ?>
                                                                <p>
                                                                    <strong style="font-size: 16px;"><?php echo htmlspecialchars($client_email) . ' - ' . htmlspecialchars($client_phone); ?></strong><br>
                                                                    <span style="font-size: 12px; color: #555;"><i>Contact Details</i></span>
                                                                </p>
                                                            <?php elseif (strtolower($client_type) === 'company' && !empty($contacts)): ?>
                                                                <?php foreach ($contacts as $contact): ?>
                                                                    <p>
                                                                        <strong style="font-size: 16px;">
                                                                            <?php
                                                                            echo htmlspecialchars($contact['email']) . ' - ' . htmlspecialchars($contact['phone_number']);
                                                                            if (!empty($contact['designation'])) {
                                                                                echo " (" . htmlspecialchars($contact['designation']) . ")";
                                                                            }
                                                                            ?></strong><br>
                                                                        <span style="font-size: 12px; color: #555;"><i>Contact Details</i></span>
                                                                    </p>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                            <p style="font-size: 16px;"><?php echo date('M d, Y', strtotime($row['start_date'])) . ' - ' . date('M d, Y', strtotime($row['end_date'])); ?><br>
                                                                <span style="font-size: 12px; color: #555;"><i>Start Date & End Date</i></span>
                                                            </p>
                                                            <p style="font-size: 16px;">&#8369; <?php echo number_format($row['projected_budget_cost'], 2) . ' - ' . htmlspecialchars($row['po_num']); ?><br>
                                                                <span style="font-size: 12px; color: #555;"><i>Total Project Cost & PO-Number</i></span>
                                                            </p>

                                                            <div style="display: flex; align-items: center; vertical-align: middle; gap: 12px; flex-wrap: wrap;">
                                                                <p style="font-size: 16px;">
                                                                    <?php echo $row['project_type']; ?><br>
                                                                    <span style="font-size: 12px; color: #555;"><i>Located Area</i></span>
                                                                </p>
                                                                <?php
                                                                $project_id = $row['project_id'];
                                                                $contracts_query = mysqli_query($con, "SELECT file_path FROM contracts WHERE project_id = $project_id");

                                                                if (mysqli_num_rows($contracts_query) > 0) {
                                                                    while ($contract = mysqli_fetch_assoc($contracts_query)) {
                                                                        echo '<a href="../contracts/' . htmlspecialchars($contract['file_path']) . '" target="_blank">
                                                                                    <button type="button" class="btn btn-outline-success btn-sm">
                                                                                        View Contract Attachment
                                                                                    </button>
                                                                                </a>';
                                                                    }
                                                                } else {
                                                                    echo '<span style="font-size: 14px; color: #888;">No Contract Available</span>';
                                                                }
                                                                ?>
                                                            </div>
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
                                                            <button class="btn btn-sm btn-outline-secondary mt-2 toggle-logs-btn" type="button"
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

                        </div>

                    <?php
                                        }
                    ?>

                <?php
                                    } else {
                ?>
                    <tr colspan="4">No Record Found</tr>
                <?php
                                    }

                ?>

                </tbody>
                </table>
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
                    </div>
                </section>

                <!-- Table for Cancelled Projects -->
    
                <?php if (!empty($_SESSION['status'])): ?>
                    <div class="modal fade" id="noDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-center p-4">
                                <i class="bi bi-x-circle-fill text-danger" style="font-size:70px;"></i>
                                <h4 class="mt-3"><?php echo $_SESSION['status']; ?></h4>
                                <p style="text-align: center; font-size: 16px;">
                                    There are no expenses found for the selected date range.</p>
                                <div class="modal-footer border-0 d-flex justify-content-center">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Okay</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            new bootstrap.Modal(document.getElementById('noDataModal')).show();
                        });
                    </script>
                <?php
                    unset($_SESSION['status'], $_SESSION['status_type']);
                endif;
                ?>

                <script src="./resources/js/auto_logout.js"></script>

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