<?php
include('../dbcon.php');
include('../layout/session_check.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses</title>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/expense.css">
    <script src="payments.js"></script>


</head>

<body>

    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <div class="main-content container-fluid">

            <!-- Top Nav -->
            <?php
            $page_title = 'Transactions';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-0 pe-3">


                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link" href="batch_expense.php">Project Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="company_exp.php">Company Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="payment.php">Payment Transaction</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>


                <div class="recording-option d-flex justify-content-between align-items-center">
                    <div class="cl-head mt-4">
                        <h4 style="font-weight: bold;">Payment Transaction Form</h41>
                    </div>
                    <ul class="form-nav mt-4 p-0 ">
                            <li class="form-item expense-options">
                                <a class="form-link active" aria-current="page" href="payment.php">Client Payment</a>
                            </li>
                            <li class="form-item expense-options">
                                <a class="form-link" href="allocate_budget.php">Allocate Budget</a>
                            </li>
                    </ul>
                </div>
                <div class="instructions">
                    <p>Record each payment transaction carefully and attach receipts. Accurate entries are essential for proper and reliable financial reports.
                        It is recommended to use <strong>Cash as the Category (Account Title)</strong>, as client payments are generally considered cash inflows in financial reports.
                    </p>
                </div>
                <!-- Modal for error message when date is invalid -->
                <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white border-0 shadow-sm">
                            <div class="modal-body text-center" style="padding-top: 10px;" id="modalContent">
                                <!-- Content will be coming from the JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div id="alertPlaceholder"></div>
                <?php
                if (isset($_SESSION['expense_status'])) {
                    echo $_SESSION['expense_status'];
                    unset($_SESSION['expense_status']);
                }
                ?>
                <div class="d-flex mb-0 justify-content-between align-items-start">
                    <div class="record-expense mb-0" id="paymentClient">
                        <form name="expenseForm" id="expense-form" action="expense_logic.php" method="POST" enctype="multipart/form-data">
                            <div class="expense-form container-fluid p-3 mb-0">
                                 <div id="loadingStateWrap2">
                                        <div id="loadingStateBatch2">
                                            <div class="loader"></div>
                                                <p class="mt-5 text-secondary-emphasis" style="animation: pulse 1.5s ease-in-out infinite;">
                                                    <center><i>Processing your payment record...</i></center> <br>Please don’t close or refresh the page until the process is complete.
                                                </p>
                                            </div>
                                        </div>
                                <div class="row">
                                    <div class="col-md-4 px-0 ps-1">
                                        <h6 class="fw-bold mb-3">Basic Information</h6>
                                        <hr class="mt-1 mb-3 border-dark">
                                        <div class="d-flex mb-3">
                                            <label for="expense-date" class="col-2 col-form-label dec">Date:<span class="required">*</span></label>
                                            <input name="date" class="form-control payment datepicker" id="expense-date" required>
                                        </div>
                                        <div class="d-flex mb-3">
                                            <label for="client" class="col-2 col-form-label dec">Client:<span class="required">*</span></label>
                                            <select name="client_id" class="form-select payment" id="client-dropdown" required>
                                                <option value="" disabled selected hidden>Select Client</option>
                                                <?php
                                                $clients = mysqli_query($conn, "SELECT * FROM clients");
                                                while ($c = mysqli_fetch_array($clients)) {
                                                ?>
                                                    <option value="<?php echo $c['client_id'] ?>"><?php echo $c['client_name'] ?> </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="d-flex mb-3">
                                            <label for="project" class="col-2 col-form-label dec">Project:<span class="required">*</span></label>
                                            <select name="project_id" id="project-dropdown" class="form-select bic payment select2-project" disabled required>
                                                <option value="" disabled selected hidden>
                                                    <center>Select Project</center>
                                                </option>
                                            </select>
                                            <!-- This is for passing main project id and add ons id -->
                                            <input type="hidden" name="main_project_id" id="main_project_id">
                                            <input type="hidden" name="addon_id" id="addon_id">
                                        </div>
                                    </div>

                                    <div class="col-md-4 px-0">
                                        <h6 class="fw-bold mb-3">Payment Details</h6>
                                        <hr class="mt-1 mb-3 border-dark">
                                        <div class="d-flex mb-3">
                                            <label for="description" class="col-3 col-form-label dec">Description:<span class="required">*</span></label>
                                            <input type="text" name="description_field" class="form-control payment" id="description" placeholder="Second Initial Payment" required>
                                        </div>
                                        <div id="errorBoxdes" class="error-message"></div>
                                        <div class="d-flex mb-3">
                                            <label for="category" class="col-3 col-form-label dec">Category:<span class="required">*</span></label>
                                            <select name="category_num" class="form-select payment" id="category-dropdown" required onchange="toAddCategory(this)">
                                                <option value="" disabled selected hidden>Select Category</option>
                                                <?php
                                                $category = mysqli_query($conn, "SELECT * FROM chart_accounts WHERE form_usage = 'Payment Transaction'");
                                                while ($c = mysqli_fetch_array($category)) {
                                                ?>
                                                    <option value="<?php echo $c['account_num']; ?>" data-category="<?php echo htmlspecialchars($c['category']); ?>">
                                                        <?php echo $c['category']; ?>
                                                    </option>
                                                <?php } ?>
                                                <option value="new_category" style="font-style: italic;">+ Add New Category</option>
                                            </select>
                                        </div>
                                        <div class="d-flex mb-3">
                                            <label for="amount" class="col-3 col-form-label dec">Amount:<span class="required">*</span></label>
                                            <div class="input-group" style="width: 200px;">
                                                <span class="input-group-text" style="height: 35px;">₱</span>
                                                <input type="text" name="amount" class="form-control" id="amount" required>
                                            </div>
                                        </div>
                                        <div id="errorBoxamount" class="error-message"></div>
                                    </div>

                                    <div class="col-md-4 px-0">
                                        <h6 style="visibility: hidden;" class="mb-3 secondHeader">Payment Details</h6>
                                        <hr class="mt-1 mb-3 border-dark secondHeader">
                                        <div class="payment-method-wrapper">
                                            <div class="d-flex mb-3">
                                                <label for="payment-method" class="col-5 col-form-label dec">Payment Method:<span class="required">*</span></label>
                                                <select id="payment-method" name="payment_method" class="form-select payment-method-dropdown payment">
                                                    <option disabled selected hidden>Select mode of payment</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="E-payment">E-payment</option>
                                                    <option value="Installment Payment">Installment Payment</option>
                                                    <option value="Others">Others</option>
                                                </select>
                                            </div>
                                            <input type="text" id="other-payment-input" class="form-control payment other-payment-input mt-0 mb-3" placeholder="Please specify" style="display:none; width: 100%;">
                                        </div>
                                        <div class="d-flex mb-3">
                                            <label for="invoice" class="col-5 col-form-label dec">Invoice Number:<span class="required">*</span></label>
                                            <input type="text" name="invoice_num" class="form-control payment" id="invoice">
                                        </div>
                                        <div id="errorBoxinvoice" class="error-message1"></div>
                                        <div class="d-flex mb-2">
                                            <label for="receipt" class="col-5 col-form-label dec">Proof of Payment:<span class="required">*</span></label>
                                            <input type="file" name="receipt_file" class="form-control payment" id="receipt_file" accept="image/jpeg, image/jpg, application/pdf" required>
                                        </div>
                                        <small class="text-muted file-instructionPayment"
                                            style="
                                            text-align: center;
                                            display: block;
                                            font-size: 11px;">
                                            Upload JPG and PDF file type. Max of 5mb only.
                                        </small>
                                        <div id="errorBoxfile" class="error-message1"></div>
                                    </div>
                                    <div class="text-end ms-4 d-flex">
                                        <button type="button" class="clear-fields btn btn-warning mt-2 ms-auto" onclick="clearForm(this)" title="Clear inputs">Clear</button>
                                        <button type="submit" id="previewBeforeSave" class="btn btn-dark mt-2 ms-2">Save</button>
                                    </div>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>
            </div>
            <!-- Modal for Image/PDF Preview -->
            <div class="modal fade" id="previewReceipt" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center">
                        <div class="modal-body">
                            <!-- Image preview -->
                            <img id="previewImage" src="" class="img-fluid mb-2 d-none" alt="Preview">

                            <!-- PDF preview -->
                            <iframe id="previewPdf" class="w-100 d-none" style="height: 500px;" frameborder="0"></iframe>

                            <p id="previewFileName" class="mt-2"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="cancelFile" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmFile" class="btn btn-success" data-bs-dismiss="modal">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                const input = document.getElementById("receipt_file");
                const modalprev = document.getElementById("previewReceipt");
                const previewImage = document.getElementById("previewImage");
                const previewPdf = document.getElementById("previewPdf");
                const previewFileName = document.getElementById("previewFileName");
                const modal = new bootstrap.Modal(modalprev);

                // store the selected photo of user
                let lastFile = null;

                input.addEventListener("change", e => {
                    const file = e.target.files[0];
                    if (file) {
                        lastFile = file;
                        previewFileName.textContent = file.name;


                        previewImage.classList.add("d-none");
                        previewPdf.classList.add("d-none");

                        const fileURL = URL.createObjectURL(file);

                        if (file.type.includes("pdf")) {
                            // Show PDF
                            previewPdf.src = fileURL;
                            previewPdf.classList.remove("d-none");
                        } else if (file.type.startsWith("image/")) {
                            // Show Image
                            previewImage.src = fileURL;
                            previewImage.classList.remove("d-none");
                        }

                        modal.show();
                    }
                });

                // cancel button
                document.getElementById("cancelFile").addEventListener("click", () => {
                    input.value = "";
                    lastFile = null;
                });
            </script>

            <!-- MODAL FOR CONFIRMATION TO SAVE PAYMENT -->
            <div class="modal fade" id="confirmSubmitForm" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmSubmitFormLabel">Confirm Payment Record</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="line-height: 1; margin-bottom: 0; padding: 20px;">
                            <p>Are you sure you want to save this client payment??</p>
                        </div>
                        <div class="modal-footer" style="padding-bottom: 10px; margin-top: 5px;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" name="save_client_payment" class="btn btn-success" id="finalSubmit">Confirm and Save</button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Modal Confirmation for redirection to adding new account title -->
            <div class="modal fade" id="confirmAddCategory" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmAddCategoryLabel">Confirm Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="line-height: 1; margin-bottom: 0; padding: 20px;">
                            <p><strong>Do you want to add new category?</strong></p>
                            <p style="font-size: 16px;">This action will take you to the Chart of Accounts tab and will delete your current inputs.</p>
                        </div>
                        <div class="modal-footer" style="padding-bottom: 10px; margin-top: 5px;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" name="batchexp_archive_btn" class="btn btn-success" onclick="redirectToAddCategory()">Confirm</button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- Link to adding new category (chartaccounts.php) -->
            <script>
                function toAddCategory(select) {
                    if (select.value === "new_category") {
                        // To show the modal
                        var confirmationModal = new bootstrap.Modal(document.getElementById('confirmAddCategory'), {
                            keyboard: false
                        });
                        confirmationModal.show();
                        select.value = "";
                    }
                }

                function redirectToAddCategory() {
                    window.location.href = "chartaccounts.php?open_add_category=1#payment_accounts";
                }
                document.querySelectorAll('input[name="amount"]').forEach(input => {
                    input.addEventListener("keypress", function(e) {
                        // Allow only digits 0-9
                        if (!/[0-9.,]/.test(e.key)) {
                            e.preventDefault();
                        }
                    });
                });
                document.querySelectorAll('input[name="invoice_num"]').forEach(input => {
                    input.addEventListener("keypress", function(e) {
                        // Allow only digits 0-9, comma, and period
                        if (!/[0-9.,]/.test(e.key)) {
                            e.preventDefault();
                        }
                    });
                });
            </script>
            <div class="cl-head mt-2 px-0 p-4 pb-1">
                    <h3>List of Client Payments</h3>
                </div>
            <div class="card-body p-5 px-0 pe-3 mt-2 pt-2">
                <script type="text/javascript">
                    $(document).ready(function() {
                        var table = $('#payment_table').DataTable({
                            responsive: true,
                            dom: 'Blfrtip',
                            ordering: true,
                            order: [],
                            buttons: [],
                            language: {
                                search: '',
                                searchPlaceholder: "Search payment record...",
                                paginate: {
                                    previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                    next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                }
                            }

                        });
                        // To custom calendar for custom date range
                        flatpickr("#customDateRangePayment", {
                            mode: "range",
                            dateFormat: "Y-m-d",
                            onClose: function(selectedDates, dateStr) {
                                filterByDate('custom', selectedDates);
                            }
                        });

                        // // If "Custom" option is selected
                        $('#dateFilterPayment').on('change', function() {
                            const value = $(this).val();
                            if (value === 'custom') {
                                $('#customDateRangePayment').show();
                            } else {
                                $('#customDateRangePayment').hide();
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
                            toolbar.append($('#payment_table_wrapper .dataTables_length'));
                            toolbar.append($('#dateRangeContainer'));
                            toolbar.append($('#payment_table_wrapper .dataTables_filter'));

                            // Insert toolbar before the table wrapper
                            $('#payment_table_wrapper').before(toolbar);
                        }, 0);
                    });
                </script>
                <?php

                // $con = mysqli_connect("localhost", "root", "", "financial_management");

                //This condition is to check whether date range is set or not. If not, all data is displayed
                if (isset($_SESSION['filter_start']) && isset($_SESSION['filter_end'])) {
                    $start_period = $_SESSION['filter_start'];
                    $end_period = $_SESSION['filter_end'];

                    $fetch_query = "SELECT * FROM payment_clients WHERE date BETWEEN '$start_period' AND '$end_period' ORDER BY date DESC";
                } else {

                    $fetch_query = "SELECT * FROM payment_clients ORDER BY date DESC";
                }


                $fetch_query_run = mysqli_query($conn, $fetch_query);

                ?>
                
                <div id="dateRangeWrapper" style="display: contents">
                    <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                        <label for="dateFilter" class="mb-0" style="font-size: 14px;">Filter Date by:</label>
                        <select id="dateFilterPayment" class="form-select form-select-sm w-auto" style="height: 30px;">
                            <option value="all">All</option>
                            <option value="weekly">This Week</option>
                            <option value="monthly">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                        <input type="text" id="customDateRangePayment" class="forDate form-control form-control-sm w-auto"
                            placeholder="Select date range" style="display: none; height: 30px;" />
                    </div>
                </div>
                <div class="table-responsive">
                <table class="table table-bordered border-secondary-subtle" id="payment_table" >
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Date</th>
                            <th scope="col">Client & Project Name</th>
                            <!-- <th scope="col">Category</th> -->
                            <th scope="col">Description</th>
                            <th scope="col">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($fetch_query_run) > 0) {
                            $rowNumber = 1;
                            while ($row = mysqli_fetch_array($fetch_query_run)) {
                                $payment_id = $row['payment_id'];
                                $date = $row['date'];
                                $client_id = $row['client_id'];
                                $project_id = $row['project_id'];
                                $addon_id = $row['addon_id'];
                                $archived_by = $row['archived_by'];
                                $restored_by = $row['restored_by'];
                                $archived_date = $row['archived_date'];
                                $restored_date = $row['restored_date'];
                                $category = $row['category'];
                                $description = $row['description'];
                                $payment_method = $row['payment_method'];
                                $amount = $row['amount'];
                                $receipt_file = $row['receipt_file'];
                                $invoice_num = $row['invoice_num'];
                                // echo $row['category_id'];
                                // Fetch client and project name
                                $client_name_query = mysqli_query($conn, "SELECT client_name FROM clients WHERE client_id = $client_id");
                                $client_name_data = mysqli_fetch_assoc($client_name_query);
                                $client_name = $client_name_data['client_name'] ?? 'No Client';

                                $project_name_query = mysqli_query($conn, "SELECT project_name FROM projects WHERE project_id = $project_id");
                                $project_name_data = mysqli_fetch_assoc($project_name_query);
                                $project_name = $project_name_data['project_name'] ?? 'No Project';

                                // To fetch the project add ons name 
                                $addon_title = '';

                                if (!empty($addon_id)) {
                                    $addon_id = (int)$addon_id; // cast to integer for safety
                                    $addon_title_query = mysqli_query($conn, "SELECT addon_name FROM project_addons WHERE addon_id = $addon_id");
                                    if ($addon_title_query && mysqli_num_rows($addon_title_query) > 0) {
                                        $addon_title_data = mysqli_fetch_assoc($addon_title_query);
                                        $addon_title = $addon_title_data['addon_name'] ?? '';
                                    }
                                }

                                // To fetch archiver name and role
                                $archiver_name = '';
                                $archiver_role = '';
                                if (!empty($row['archived_by'])) {
                                    $archiver_query = mysqli_query($conn, "SELECT name, role FROM users WHERE id = {$row['archived_by']}");
                                    $archiver_data = mysqli_fetch_assoc($archiver_query);
                                    $archiver_name = $archiver_data['name'] ?? '';
                                    $archiver_role = $archiver_data['role'] ?? '';
                                }

                                // To fetch restorer name and role
                                $restorer_name = '';
                                $restorer_role = '';
                                if (!empty($row['restored_by'])) {
                                    $restorer_query = mysqli_query($conn, "SELECT name, role FROM users WHERE id = {$row['restored_by']}");
                                    $restorer_data = mysqli_fetch_assoc($restorer_query);
                                    $restorer_name = $restorer_data['name'] ?? '';
                                    $restorer_role = $restorer_data['role'] ?? '';
                                }
                        ?>
                                <tr class="<?php echo !empty($row['restored_by']) ? 'restored-row' : ''; ?>">
                                    <td style="text-align: center;"><?php echo $rowNumber++; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <!-- To display the client name instead of just ID -->
                                    <td>
                                        <?php
                                        if (!empty($addon_id) && $addon_title) {
                                            // If record is from an Add-on project
                                            echo $client_name . " - " . $addon_title . " <br>(Addons for: " . $project_name . ") ";
                                        } else {
                                            // Normal project
                                            echo $project_name . " - " . $client_name;
                                        }
                                        ?>
                                    </td>
                                    <!-- <td><?php echo $row['category']; ?></td> -->
                                    <td><?php echo ucwords($row['description']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between">
                                            &#8369; <?php echo number_format($row['amount'], 2) ?>
                                            <div class="dropdown" data-bs-display="static">
                                                <button class="three_dots btn btn-light btn-sm ms-1" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#viewPaymentModal<?php echo $payment_id; ?>">View Details</a></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#archiveExpenseModal<?php echo $payment_id; ?>">
                                                            Archive
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>


                                <div class="modal fade" id="viewPaymentModal<?php echo $payment_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-md">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewExpenseModal<?php echo $payment_id; ?>">Payment Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                <!-- Right Column -->
                                                <p><strong style="font-size: 18px;"><?php echo date('M d, Y', strtotime($row['date'])); ?></strong></p>
                                                <p><strong style="font-size: 18px;">
                                                        <?php
                                                        if (!empty($addon_title)) {
                                                            // If record is from an Add-on project
                                                            echo $client_name . " - " . $addon_title . " <br>(Addons for: " . $project_name . ") ";
                                                        } else {
                                                            // Normal project
                                                            echo $project_name . " - " . $client_name;
                                                        }
                                                        ?>
                                                    </strong></p>
                                                <p><?php echo $row['category'] . ' - ' . ucwords($row['description']); ?><br>
                                                    <span style="font-size: 13px; color: #555;"><i>Category & Description</i></span>
                                                </p>
                                                <p>&#8369; <?php echo number_format($row['amount'], 2) . ' - ' . $row['payment_method']; ?><br>
                                                    <span style="font-size: 13px; color: #555;"><i>Amount & Payment Method</i></span>
                                                </p>
                                                <!-- Inline Invoice Number and Button -->
                                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                    <p><?php echo $row['invoice_num']; ?><br>
                                                        <span style="font-size: 13px; color: #555;"><i>Invoice Number</i></span>
                                                    </p>
                                                    <!-- This condition will check if an attachment exists and displays the button. -->
                                                    <?php if (!empty($receipt_file) && file_exists('../receipts/client_payment_files/' . $receipt_file)): ?>
                                                        <a href="../receipts/client_payment_files/<?php echo htmlspecialchars($receipt_file); ?>" target="_blank" style="text-decoration: none;">
                                                            <button type="button" class="btn btn-outline-success btn-sm">
                                                                View Receipt Attachment
                                                            </button>
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="font-size: 14px; color: #888;">No Receipt Available</span>
                                                    <?php endif; ?>
                                                </div>
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
                                                <button class="btn btn-sm btn-outline-secondary mt-2 toggle-logs-btn" type="button"
                                                    data-target="#logs<?php echo $payment_id; ?>">
                                                    <i class="bi bi-chevron-bar-down"></i>
                                                </button>
                                                <!-- Collapsible Logs Section -->
                                                <div class="custom-collapse" id="logs<?php echo $payment_id; ?>">
                                                    <div class="highlight-box" style="font-size: 12px; color: #555;">
                                                        <p class="mt-2">Recorded by: <?php echo htmlspecialchars($user_name); ?> | <?php echo ucfirst(htmlspecialchars($user_role)); ?></p>

                                                        <?php if (!empty($archiver_name)): ?>
                                                            <p>
                                                                <?php echo ucwords(htmlspecialchars($archiver_name)) . ' - ' . ucwords(htmlspecialchars($archiver_role)) . ' - ' . date('M d, Y', strtotime($archived_date)); ?><br>
                                                                <span style="font-size: 11px; color: #555;"><i>Archived by, User Role, & Archived Date</i></span>
                                                            </p>
                                                        <?php endif; ?>

                                                        <?php if (!empty($restorer_name)): ?>
                                                            <p>
                                                                <?php echo ucwords(htmlspecialchars($restorer_name)) . ' - ' . ucwords(htmlspecialchars($restorer_role)) . ' - ' . date('M d, Y', strtotime($restored_date)); ?><br>
                                                                <span style="font-size: 11px; color: #555;"><i>Restored by, User Role, & Restored Date</i></span>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Archive Modal -->
                                <div class="modal fade" id="archiveExpenseModal<?php echo $payment_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-md">
                                        <div class="modal-content">
                                            <div class="modal-header" style="padding: 12px;">
                                                <h5 class="modal-title" id="viewExpenseModal<?php echo $payment_id; ?>">Confirm Archive</h5>
                                                <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body" style="line-height: 1.5; margin-bottom: 0; padding: 15px 15px 0 15px;">
                                                <form method="POST" action="../forms_logic/archive.php">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment_id; ?>">
                                                    <p style="font-size: 16px;">Are you sure you want to archive payment record, <strong><?php echo ucwords($row['description']); ?></strong>,
                                                        from client <strong><?php echo $client_name; ?></strong> under the project of <strong><?php echo $project_name; ?></strong>?
                                                    </p>

                                                    <div class="modal-footer" style="border-top: none; padding-bottom: 20px;">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="pay_archive_btn" class="btn btn-danger">Archive</button>
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
                 document.addEventListener('DOMContentLoaded', function () {

            // Handle 3-dots dropdowns 
            document.querySelectorAll('.three_dots').forEach(btn => {
                btn.addEventListener('click', function (e) {
                e.stopPropagation();

                // Close all other open dropdowns except this one
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== btn.nextElementSibling) {
                    menu.classList.remove('show');
                    }
                });

                btn.nextElementSibling.classList.toggle('show');
                });
            });

            // Handle "select project" dropdowns (inside forms/modals)
            document.querySelectorAll('.dropdown-toggle').forEach(btn => {
                // kip navbar dropdowns by checking parent element
                if (btn.closest('.navbar') || btn.closest('.user-account')) return;

                btn.addEventListener('click', function (e) {
                e.stopPropagation();

                // Close all other open dropdowns except this one
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== btn.nextElementSibling) {
                    menu.classList.remove('show');
                    }
                });

                btn.nextElementSibling.classList.toggle('show');
                });
            });

            //close all non-navbar dropdowns 
            document.addEventListener('click', function (e) {
                // Ignore clicks inside navbar (user account dropdown)
                if (!e.target.closest('.dropdown') || !e.target.closest('.navbar')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    // Don't close if inside navbar dropdown
                    if (!menu.closest('.navbar') && !menu.closest('.user-account')) {
                    menu.classList.remove('show');
                    }
                });
                }
            });

            // Keep  "Toggle Logs" section working 
            document.querySelectorAll('.toggle-logs-btn').forEach(btn => {
                const target = document.querySelector(btn.dataset.target);
                if (!target) return;

                btn.addEventListener('click', () => {
                const isOpen = btn.classList.contains('active');

                if (isOpen) {
                    // Collapse
                    target.style.height = target.scrollHeight + 'px';
                    requestAnimationFrame(() => (target.style.height = '0'));
                    target.addEventListener('transitionend', () => {
                    target.style.height = '';
                    }, { once: true });

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
                    }, { once: true });

                    btn.classList.add('active');
                    btn.querySelector('i').style.transform = 'rotate(180deg)';
                }
                });
            });

            });

            </script>

        <!-- Modal for Archive Notification -->
        <?php if (!empty($_SESSION['archive_status'])): ?>
            <div class="modal fade" id="archiveModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center p-4">
                        <?php if ($_SESSION['archive_status_type'] === 'success'): ?>
                            <div class="d-flex justify-content-center mb-3">
                                <div class="rounded-circle bg-info bg-gradient d-flex align-items-center justify-content-center"
                                    style="width: 100px; height: 100px;">
                                    <i class="bi bi-archive-fill text-white" style="font-size:50px;"></i>
                                </div>
                            </div>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger" style="font-size:70px;"></i>
                        <?php endif; ?>
                        <h5 class="mt-3"><?php echo $_SESSION['archive_status']; ?></h5>
                        <?php
                        echo '<div style="font-size: 15px;">' .
                            ($_SESSION['archive_status_type'] === 'success'
                                ? "The selected client payment record is no longer in active records and has been successfully moved to the archive."
                                : "Something went wrong while archiving the expense.") .
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
                    new bootstrap.Modal(document.getElementById('archiveModal')).show();
                });
            </script>
        <?php
            unset($_SESSION['archive_status'], $_SESSION['archive_status_type']);
        endif;
        ?>

        <!-- MOdal for no record found -->
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
                flatpickr("#expense-date", {
                    maxDate: "today",
                    minDate: "2021-01-01",
                    dateFormat: "Y-m-d",
                    defaultDate: "today", //Auto fill the date
                });
            });
        </script>

</body>

</html>