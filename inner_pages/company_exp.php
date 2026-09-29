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
    <script src="company_exp.js"></script>

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
                        <a class="nav-link active" aria-current="page" href="company_exp.php">Company Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payment.php">Payment Transaction</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>
                <div class="cl-head mt-4">
                    <h4 style="font-weight: bold;">Company Expense Form</h4>
                </div>
                <div class="instructions">
                    <p>Fill out this form to record general company expenses outside project activities.
                        Provide complete and accurate details for each field. <strong>Kindly review your entries carefully before saving.</strong></p>
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
                <div class="d-flex justify-content-between align-items-start">
                    <div class="record-expense" id="companyForm">
                        <form name="companyExpenseForm" id="expense-form" action="expense_logic.php" method="POST" enctype="multipart/form-data">
                            <div class="expense-form container-fluid p-1">
                                 <div id="loadingStateWrap2">
                                        <div id="loadingStateBatch2">
                                            <div class="loader"></div>
                                                <p class="mt-5 text-secondary-emphasis" style="animation: pulse 1.5s ease-in-out infinite;">
                                                    <center><i>Processing your expense submission...</i></center> <br>Please don’t close or refresh the page until the process is complete.
                                                </p>
                                            </div>
                                        </div>
                                <input type="hidden" name="expense_type" value="company">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-md-6 px-0 ps-3">
                                        <h6 class="fw-bold mb-3">Basic Information</h6>
                                        <hr class="mt-1 mb-3 border-dark">
                                        <div class="d-flex mb-3">
                                            <label for="expense-date" class="col-2 col-form-label me-4">Date:<span class="required">*</span></label>
                                            <input name="date" class="form-control datepicker company-form" id="expense-date" required>
                                        </div>
                                        <div class="d-flex mb-3">
                                            <label for="description" class="col-2 col-form-label">Description:<span class="required">*</span></label>
                                            <input type="text" name="description_field" class="form-control company-form" id="description" required>
                                        </div>
                                        <div id="errorBoxdes" class="error-message mt-0" style="width: 65%;"></div>
                                        <div class="d-flex mb-3">
                                            <label for="category" class="col-2 col-form-label">Category:<span class="required">*</span></label>
                                            <select name="category_num" class="form-select company-form" id="category-dropdown" required onchange="toAddCategory(this); disableInvoice(this)">
                                                <option value="" disabled selected hidden>Select Category</option>
                                                <?php
                                                $category = mysqli_query($conn, "SELECT * FROM chart_accounts WHERE form_usage = 'Company Expense'");
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
                                            <label for="amount" class="col-2 col-form-label">Amount:<span class="required">*</span></label>
                                            <div class="input-group" style="width: 200px;">
                                                <span class="input-group-text" style="height: 35px;">₱</span>
                                                <input type="text" name="amount" class="form-control" id="amount">
                                            </div>
                                        </div>
                                        <div id="errorBoxamount" class="error-message mt-0" style="width: 65%;"></div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-md-6 px-0 pe-2">
                                        <h6 class="fw-bold mb-3">Payment Details</h6>
                                        <hr class="mt-1 mb-3 border-dark">
                                        <div class="payment-method-wrapper">
                                            <div class="d-flex mb-1">
                                                <label for="payment-method" class="col-4 col-form-label pmethod mb-3 me-0">Payment Method:<span class="required">*</span></label>
                                                <select id="payment-method" name="payment_method" class="form-select company-form pmethod payment-method- ms-0">
                                                    <option disabled selected hidden>Select mode of payment</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Credit Purchase">Credit Purchase</option>
                                                    <option value="E-payment">E-payment</option>
                                                    <option value="Installment Payment">Installment Payment</option>
                                                    <option value="Others">Others</option>
                                                </select>
                                            </div>
                                            <input type="text" id="other-payment-input" class="form-control other-payment-input mt-0 mb-3" placeholder="Please specify" style="display:none; width: 69%;">
                                        </div>
                                        <div class="d-flex mb-3">
                                            <label for="invoice" class="col-4 col-form-label me-0">Invoice Number:<span class="required">*</span></label>
                                            <input type="text" name="invoice_num" class="form-control company-form ms-0" id="invoice">
                                        </div>
                                        <div id="errorBoxinvoice" class="error-message1 mt-0" style="width: 65%;"></div>
                                        <div class="d-flex mb-1">
                                            <label for="receipt" class="col-4 col-form-label me-0">Upload Receipt:<span class="required">*</span></label>
                                            <input type="file" name="receipt_file" class="form-control company-form ms-0" id="receipt_file" accept="image/jpeg, image/jpg, application/pdf" required>
                                        </div>
                                        <small class="text-muted file-instructionCompany"
                                            style="
                                        text-align: center;
                                        display: block;
                                        font-size: 11px;">
                                            Upload JPG and PDF file type. Max of 5mb only.
                                        </small>
                                        <div id="errorBoxfile" class="error-message1 mt-0" style="width: 65%;"></div>
                                        <div class="text-end d-flex">
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
                <!-- MODAL FOR CONFIRMATION TO SAVE EXPENSE -->
                <div class="modal fade" id="confirmSubmitForm" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="confirmSubmitFormLabel">Confirm Expense Entries</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="line-height: 1; margin-bottom: 0; padding: 20px;">
                                <p>Are you sure you want to save this expense entry?</p>
                            </div>
                            <div class="modal-footer" style="padding-bottom: 10px; margin-top: 5px;">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" name="save_company_expense" class="btn btn-success" id="finalSubmit">Confirm and Save</button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Modal Confirmation for redirection to adding new account title -->
                <div class="modal fade" id="confirmAddCategory" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header" style="padding: 12px;">
                                <h5 class="modal-title" id="confirmAddCategoryLabel"><strong>Confirm Action</strong></h5>
                                <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="line-height: 1; margin-bottom: 0; padding: 20px 20px 0 20px;">
                                <p><strong>Do you want to add new category?</strong></p>
                                <p style="font-size: 16px;">This action will take you to the Chart of Accounts tab and will delete your current inputs.</p>
                            </div>
                            <div class="modal-footer" style="border-top: none;">
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
                        window.location.href = "chartaccounts.php?open_add_category=1#company_expense_accounts";
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
                <div class="cl-head mt-3">
                    <h3>List of Expenses</h3>
                </div>

                <div class="card-body px-0 p-4">

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

                    // $con = mysqli_connect("localhost", "root", "", "financial_management");

                    //This condition is to check whether date range is set or not. If not, all data is displayed
                    if (isset($_SESSION['company_start']) && isset($_SESSION['company_end'])) {
                        $start_period = $_SESSION['company_start'];
                        $end_period = $_SESSION['company_end'];

                        $fetch_query = "SELECT * FROM company_expense WHERE date BETWEEN '$start_period' AND '$end_period' ORDER BY date DESC";
                    } else {

                        $fetch_query = "SELECT * FROM company_expense ORDER BY date DESC";
                    }


                    $fetch_query_run = mysqli_query($conn, $fetch_query);

                    ?>
                    <div id="dateRangeWrapper" style="display: contents">
                        <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                            <label for="dateFilter" class="mb-0" style="font-size: 14px;">Filter Date by:</label>
                            <select id="dateFilterCompany" class="form-select form-select-sm w-auto" style="height: 30px;">
                                <option value="all">All</option>
                                <option value="weekly">This Week</option>
                                <option value="monthly">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            <input type="text" id="customDateRangeCompany" class="forDate form-control form-control-sm w-auto"
                                placeholder="Select date range" style="display: none; height: 30px;" />
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered border-secondary-subtle px-0" style="width: 100%;" id="company_expense">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Date</th>
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
                                        $company_exp_id = $row['company_exp_id'];
                                        $archived_by = $row['archived_by'];
                                        $restored_by = $row['restored_by'];
                                        $archived_date = $row['archived_date'];
                                        $restored_date = $row['restored_date'];
                                        $date = $row['date'];
                                        $description = $row['description'];
                                        $category = $row['category'];
                                        $payment_method = $row['payment_method'];
                                        $amount = $row['amount'];
                                        $invoice_num = $row['invoice_num'];
                                        $receipt_file = $row['receipt_file'];

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
                                            <td><?php echo ucwords($row['description']); ?></td>
                                            <td><?php echo $row['category']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    &#8369; <?php echo number_format($row['amount'], 2); ?>
                                                    <div class="dropdown" data-bs-display="static">
                                                        <button class="three_dots btn btn-light btn-sm ms-1" type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#viewExpenseModal<?php echo $company_exp_id; ?>">View Details</a></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#archiveExpenseModal<?php echo $company_exp_id; ?>">
                                                                    Archive
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>


                                        <div class="modal fade" id="viewExpenseModal<?php echo $company_exp_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="padding: 12px;">
                                                        <h5 class="modal-title" id="viewExpenseModal<?php echo $company_exp_id; ?>">Expense Details</h5>
                                                        <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                        <!-- Right Column -->
                                                        <p><strong style="font-size: 16px;"><?php echo date('M d, Y', strtotime($row['date'])); ?></strong></p>
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
                                                            type="button" data-target="#logs<?php echo $company_exp_id; ?>">
                                                            <i class="bi bi-chevron-bar-down"></i>
                                                        </button>
                                                        <!-- Collapsible Logs Section -->
                                                        <div class="custom-collapse" id="logs<?php echo $company_exp_id; ?>">
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
                                        <div class="modal fade" id="archiveExpenseModal<?php echo $company_exp_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="padding: 12px;">
                                                        <h5 class="modal-title" id="viewExpenseModal<?php echo $company_exp_id; ?>">Confirm Archive</h5>
                                                        <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="line-height: 1.5; margin-bottom: 0; padding: 15px 15px 0 15px;">
                                                        <form method="POST" action="../forms_logic/archive.php">
                                                            <input type="hidden" name="company_exp_id" value="<?php echo $company_exp_id; ?>">
                                                            <p style="font-size: 16px;">Are you sure you want to archive company expense record <strong><?php echo $row['category'] . ' - ' . ucwords($row['description']); ?></strong>? </p>

                                                            <div class="modal-footer" style="border-top: none; padding-bottom: 20px;">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="comp_archive_btn" class="btn btn-danger">Archive</button>
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
                                    ? "The selected company expense is no longer in active records and has been successfully moved to the archive."
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


                flatpickr("#expense-date", {
                    minDate: "2021-01-01",
                    maxDate: new Date(), // today
                    dateFormat: "Y-m-d",
                    defaultDate: "today", // auto-fill with today
                });
                function disableInvoice(selectElem) {
                // find the table row that contains this select
                const row = selectElem.closest(".row") || selectElem.parentElement;
                if (!row) return;
            
                // find the invoice input in the same row
                const invoiceInput = row.querySelector('input[name="invoice_num"]');
                if (!invoiceInput) return;
            
                // prefer data-category attribute if available, otherwise use the option text
                const selectedOption = selectElem.options[selectElem.selectedIndex];
                const rawCategory = (selectedOption?.getAttribute('data-category') || selectedOption?.text || "").toLowerCase().trim();
            
                // keywords that should exempt invoice number requirement
                const exemptKeywords = ["labor", "office staff", "staff", "salary"];
            
                // check if any keyword appears in the category string
                const isExempt = exemptKeywords.some(k => rawCategory.includes(k));
            
                if (isExempt) {
                    // Remove required, set value + placeholder and lock input
                    invoiceInput.removeAttribute("required");
                    invoiceInput.value = "No Invoice Number Required";
                    invoiceInput.placeholder = " ";
                    invoiceInput.classList.add("invoice-no-required"); // optional: for styling
                } else {
                    // Restore required, clear if it was the auto text, and unlock input
                    invoiceInput.setAttribute("required", "required");
                    if (invoiceInput.value === "No Invoice Number Required") invoiceInput.value = "";
                    invoiceInput.placeholder = "Invoice Number";
                    invoiceInput.readOnly = false;
                    invoiceInput.classList.remove("invoice-no-required");
                }
            }
            </script>

</body>

</html>