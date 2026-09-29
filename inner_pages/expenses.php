<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

/** @var mysqli $conn */

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $pageTitle = "Transactions"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/expense.css">
    <script src="expenses.js"></script>
</head>

<body>

    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <div class="main-content container-fluid ">

           <!-- Top Nav -->
            <?php
            $page_title = 'Transactions';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-0">

               
                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="expenses.php">Project Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="company_exp.php">Company Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payment.php">Payment Transaction</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <div class="recording-option d-flex justify-content-between align-items-center">
                    <div class="cl-head mt-4">
                        <h4 style="font-weight: bold;">Project Expense Form </h4>
                    </div>
                    <ul class="form-nav mt-4 p-0 ">
                        <li class="form-item expense-options">
                            <a class="form-link" aria-current="page" href="batch_expense.php">Batch Record</a>
                        </li>
                        <li class="form-item expense-options">
                            <a class="form-link active" href="expenses.php">Upload and Scan</a>
                        </li>
                    </ul>
                </div>
                <div class="instructions pe-4">
                    <p>Upload a clear image of the receipt to allow the system to automatically extract and fill in relevant fields.
                        <strong>Only projects with recorded payments will appear in the Project Options.</strong> 
                        Fields labeled "Enter Manually" must still be completed by the user. <strong>Please review all entries carefully before submitting to ensure accuracy.</strong> </p>
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
                <?php
                if (isset($_SESSION['expense_status'])) {
                    echo $_SESSION['expense_status'];
                    unset($_SESSION['expense_status']);
                }
                ?>
                <script>
                    flatpickr(".datepicker", {
                        dateFormat: "Y-m-d"
                    });
                </script>
                <div id="alertPlaceholder"></div>   
                <div class="d-flex justify-content-between align-items-start">
                    <div class="record-expense pe-0">
                        <form name="expenseForm" id="expense-form" action="expense_logic.php" method="POST" enctype="multipart/form-data">
                            <div class="expense-form container-fluid">
                                <input type="hidden" name="expense_type" value="project">
                                <div class="row px-0">
                                    <div class="col-md-4">
                                        <div class="p-3 me-md-3 mb-4" style="height: 518px; border: 2px dashed #ccc; border-radius: 10px; position: relative;">
                                            <h6 class="fw-bold mb-3">Upload Your Receipt Here</h6>
                                            <hr class="mt-1 mb-3 border-dark">
                                            <div id="uploadArea" style="height: 350px; position: relative;">
                                                <div id="dragDropContainer" class="d-flex justify-content-center align-items-center" style="flex-direction: column; height: 100%; position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1;">
                                                    <i class="bi bi-folder2-open" style="font-size: 3rem; color: #aaa;"></i>
                                                    <p class="mt-3" style="font-size: 18px;">Drag and drop a file here</p>
                                                    <p>- OR -</p>
                                                    <input type="file" name="receipt_file" id="receipt_file" class="form-control" accept="image/jpeg, image/jpg" capture="environment"  required>
                                                    <small class="text-muted mt-2 file-instruction"
                                            style="
                                        text-align: center;
                                        display: block;
                                        font-size: 11px;">
                                            Upload JPG image file type. Preferrably a scanned image, but if not, ensure that it is a clear, top-view image. Max of 5mb only.
                                        </small>
                                                </div>
                                                <div id="loadingState">
                                                    <div class="loader"></div>
                                                    <p class="mt-2 text-secondary" style="animation: pulse 1.5s ease-in-out infinite;">Scanning your receipt...</p>
                                                </div>
                                                <div id="previewContainer" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; text-align: center; z-index: 2;">
                                                    <img id="preview" src="#" style="max-width: 100%; max-height: 100%;" />
                                                </div>
                                            </div>
                                            <div class="text-center mt-3">
                                                <button id="uploadAnother" class="btn btn-dark justify-content-center align-items-center" style="display: none;" onclick="resetUpload()">Upload Another File</button>
                                            </div>
                                            <div id="errorBoxfile" class="error-message1"></div>
                                        </div>
                                    </div>
                                     <div id="loadingStateWrap2">
                                        <div id="loadingStateBatch2">
                                            <div class="loader"></div>
                                                <p class="mt-5 text-secondary-emphasis" style="animation: pulse 1.5s ease-in-out infinite;">
                                                    <center><i>Processing your expense submission...</i></center> <br>Please don’t close or refresh the page until the process is complete.
                                                </p>
                                            </div>
                                        </div>

                                    <div class="col-md-8">
                                        <div class="row pe-0" style="border: 2px solid gainsboro;">
                                            <!-- Basic Information Column -->
                                            <div class="col-md-6 px-0 mb-3">
                                                <h6 class="fw-bold mb-3">Enter Manually</h6>
                                                <hr class="mt-1 mb-3 border-dark">
                                                <div class="d-flex mb-3">
                                                    <label for="client" class="col-2 col-form-label">Client:<span class="required">*</span></label>
                                                    <select name="client_id" class="form-select" id="client-dropdown" required onchange="toAddClient(this)">
                                                        <option value="" disabled selected hidden>Select Client</option>
                                                        <?php
                                                        $clients = mysqli_query($conn, "SELECT * FROM clients");
                                                        while ($c = mysqli_fetch_array($clients)) {
                                                        ?>
                                                            <option value="<?php echo $c['client_id'] ?>"><?php echo $c['client_name'] ?> </option>
                                                        <?php } ?>
                                                        <option value="new_client" style="font-style: italic;">+ Add New Client</option>
                                                    </select>
                                                </div>
                                                <div class="d-flex mb-3 projectMQuery">
                                                    <label for="project" class="col-2 col-form-label">Project:<span class="required">*</span></label>
                                                    <select name="project_id" id="project-dropdown" class="form-select bic" disabled required>
                                                        <option value="" disabled selected hidden>
                                                            <center>Select Project</center>
                                                        </option>
                                                    </select>
                                                    <!-- This is for passing main project id and add ons id -->
                                                <input type="hidden" name="main_project_id" id="main_project_id">
                                                <input type="hidden" name="addon_id" id="addon_id">
                                                </div>
                                            </div>

                                            <div class="col-md-6 px-0">
                                                <h6 class="fw-bold mb-3 secondHeader" style="visibility: hidden;">Enter Manually</h6>
                                                <hr class="mt-1 mb-3 border-dark secondHeader">
                                                <div class="d-flex mb-3 categoryMQuery">
                                                    <label for="category" class="col-3 col-form-label category">Category:<span class="required">*</span></label>
                                                    <select name="category_num" class="form-select" id="category-dropdown" required onchange="toAddCategory(this); disableInvoice(this); disableStore(this)">
                                                        <option value="" disabled selected hidden>Select Category</option>
                                                        <?php
                                                        $category = mysqli_query($conn, "SELECT * FROM chart_accounts WHERE form_usage = 'Project Expense'");
                                                        while ($c = mysqli_fetch_array($category)) {
                                                        ?>
                                                            <option value="<?php echo $c['account_num']; ?>" data-category="<?php echo htmlspecialchars($c['category']); ?>">
                                                                <?php echo $c['category']; ?>
                                                            </option>
                                                        <?php } ?>
                                                        <option value="new_category" style="font-style: italic;">+ Add New Category</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- Expense Details Column -->
                                            <div class="col-md-6 px-0">
                                                <h6 class="fw-bold mb-3">Expense Details</h6>
                                                <hr class="mt-1 mb-3 border-dark">
                                                <div class="d-flex mb-2">
                                                    <label for="expense-date" class="col-2 col-form-label">Date:<span class="required">*</span></label>
                                                    <input name="date" class="form-control datepicker" id="expense-date" required>
                                                </div>
                                                <div id="errorBoxdate" class="error-message"></div>
                                                <div class="d-flex mb-2 align-items-center">
                                                    <label for="description" class="col-2 col-form-label">Item Name:<span class="required">*</span></label>
                                                    <input type="text" name="item_name" class="form-control" id="description" placeholder="e.g., Steel Rebars (Grade 60)" required>
                                                </div>
                                                <div id="errorBoxdes" class="error-message"></div>
                                                <div class="d-flex mb-3">
                                                    <label for="store" class="col-2 col-form-label">Store:<span class="required">*</span></label>
                                                    <input type="text" name="store_name" class="form-control" placeholder="e.g., Wilcon Depot - Calamba" id="store" required>
                                                </div>
                                                <div id="errorBoxstore" class="error-message"></div>
                                            </div>

                                            <div class="col-md-6 px-0 mt-0">
                                                <h6 class="fw-bold mb-3 secondHeader" style="visibility: hidden;">Expense Details</h6>
                                                <hr class="mt-1 mb-3 border-dark secondHeader">
                                                <div class="d-flex mt-0 thirdcolMQuery">
                                                    <label for="amount" class="col-3 col-form-label">Amount:<span class="required">*</span></label>
                                                    <div class="input-group" style="width: 180px;">
                                                        <span class="input-group-text" style="height: 35px;">₱</span>
                                                        <input type="text" name="amount" class="form-control" id="amount" required>
                                                    </div>
                                                </div>
                                                <div id="errorBoxamount" class="error-message"></div>
                                                <div class="d-flex mb-1 align-items-center thirdcolMQuery">
                                                    <label for="payment-method" class="col-3 col-form-label">Payment Method:<span class="required">*</span></label>
                                                    <input type="text" name="payment_method" class="form-control" id="payment_method" required>
                                                    <!-- <select name="payment_method" class="form-select" required>
                                                        <option>Select mode of payment</option>
                                                        <option value="Cash">Cash</option>
                                                        <option value="E-payment">E-payment</option>
                                                    </select> -->
                                                </div>
                                                <div class="d-flex mb-1 align-items-cente thirdcolMQueryr">
                                                    <label for="invoice" class="col-3 col-form-label">Invoice Number:<span class="required">*</span></label>
                                                    <input type="text" name="invoice_num" class="form-control" id="invoice" required>
                                                </div>
                                                <div id="errorBoxinvoice" class="error-message1"></div>

                                            </div>
                                            <div class="mt-2 d-flex">
                                                <button type="button" class="clear-fields btn btn-warning mt-2 ms-auto" onclick="clearForm(this)" title="Clear inputs">Clear</button>
                                                <button type="submit" id="previewBeforeSave" class="btn btn-dark mt-2 ms-2">Save</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>

                     <!-- Modal for Image Preview of Receipt -->
                <div class="modal fade" id="previewReceipt" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center">
                    <div class="modal-body">
                        <img id="previewScan" src="" class="img-fluid mb-2" alt="Preview">
                        <p id="previewFileName"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cancelFile" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmFile" class="btn btn-success" data-bs-dismiss="modal">Confirm</button>
                    </div>
                    </div>
                </div>
                </div>

              <script>
                document.addEventListener("DOMContentLoaded", () => {
                    const input = document.getElementById("receipt_file");
                    const modalprev = document.getElementById("previewReceipt");
                    const previewScan = document.getElementById("previewScan");
                    const previewFileName = document.getElementById("previewFileName");
                    const modal = new bootstrap.Modal(modalprev);

                    // store the selected photo of user
                    let lastFile = null;
                    
                    input.addEventListener("change", e => {
                        const file = e.target.files[0];
                        if (file) {
                            lastFile = file; 
                            previewFileName.textContent = file.name;

                            const reader = new FileReader();
                            reader.onload = ev => {
                                previewScan.src = ev.target.result;
                                modal.show(); 
                            };
                            reader.readAsDataURL(file);

                            
                            input.value = "";
                        }
                    });

                    // cancel button
                    document.getElementById("cancelFile").addEventListener("click", () => {
                        lastFile = null;
                        input.value = "";
                    });

                    // confirm button
                    document.getElementById("confirmFile").addEventListener("click", () => {
                        if (lastFile) {
                            const dt = new DataTransfer();
                            dt.items.add(lastFile);
                            input.files = dt.files;

                            // to store the confirmed image to the OCR process function
                            previewImage(input);

                            lastFile = null;
                        }
                    });
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
                                <button type="button" name="save_expense" class="btn btn-success" id="finalSubmit">Confirm and Save</button>
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
                <!-- Modal Confirmation for redirection to adding new client  -->
                <div class="modal fade" id="confirmAddclient" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header" style="padding: 12px;">
                                <h5 class="modal-title" id="confirmAddCategoryLabel"><strong>Confirm Action</strong></h5>
                                <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="line-height: 1; margin-bottom: 0; padding: 20px 20px 0 20px;">
                                <p><strong>Do you want to add new client?</strong></p>
                                <p style="font-size: 16px;">This action will take you to the Clients tab and will delete your current inputs.</p>
                            </div>
                            <div class="modal-footer" style="border-top: none;">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" name="batchexp_confirm_btn" class="btn btn-success" onclick="redirectToAddClient()">Confirm</button>
                            </div>

                        </div>
                    </div>
                </div>
                 <!-- Link to adding new category (chartaccounts.php) -->
                    <script>
                        function toAddCategory(select) {
                            if (select.value === "new_category"){
                                // To show the modal
                                var confirmationModal = new bootstrap.Modal(document.getElementById('confirmAddCategory'),{
                                    keyboard: false
                                });
                                confirmationModal.show();
                                select.value = "";
                            }
                        }

                        function redirectToAddCategory(){
                            window.location.href = "chartaccounts.php?open_add_category=1";
                        }
                         function toAddClient(select) {
                            if (select.value === "new_client") {
                                // To show the modal
                                var confirmationModal = new bootstrap.Modal(document.getElementById('confirmAddclient'), {
                                    keyboard: false
                                });
                                confirmationModal.show();
                                select.value = "";
                            }
                        }

                        function redirectToAddClient() {
                            window.location.href = "clients.php?open_add_client=1";
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

                <!-- Query for Summary of expenses per client and project -->
                <?php
                // $con = mysqli_connect("localhost", "root", "", "financial_management");

                // Fetch projects with client names
                $projects_query = "SELECT p.project_id, p.project_name, c.client_id, c.client_name 
                    FROM projects p 
                    JOIN clients c ON p.client_id = c.client_id";
                $projects_result = mysqli_query($conn, $projects_query);
                $projects = mysqli_fetch_all($projects_result, MYSQLI_ASSOC);

                // Get selected project and client if submitted
                $selected_project_id = $_GET['project_id'] ?? null;
                $selected_client_id = $_GET['client_id'] ?? null;

                $expense_data = [];
                $total_expense = 0;
                $client_name = '';
                $project_name = '';

                if (!empty($selected_project_id) && !empty($selected_client_id)) {
                    $project_id = intval($selected_project_id);
                    $client_id = intval($selected_client_id);

                    // This is to fetch the project and client name
                    foreach ($projects as $proj) {
                        if ($proj['project_id'] == $project_id && $proj['client_id'] == $client_id) {
                            $client_name = $proj['client_name'];
                            $project_name = $proj['project_name'];
                            break;
                        }
                    }

                    // Query for summarizing expenses
                    $expenses_query = "SELECT category, SUM(amount) AS total_amount 
                                FROM expenses 
                                WHERE project_id = $project_id AND client_id = $client_id 
                                GROUP BY category";

                    $expense_result = mysqli_query($conn, $expenses_query);

                    while ($expense_row = mysqli_fetch_assoc($expense_result)) {
                        $category = $expense_row['category'];
                        $amount = $expense_row['total_amount'];

                        $expense_data[] = [
                            'category' => $category,
                            'amount' => $amount,
                        ];
                        $total_expense += $amount;
                    }
                }
                ?>
                <div class="cl-head mt-4 d-flex pe-3 justify-content-between align-items-center">
                    <h3>List of Expenses</h3>
                    <button class="btn btn-secondary view-summaty" data-bs-toggle="modal" data-bs-target="#viewSummaryModal">
                        View Summary
                    </button>
                </div>
                <!-- View Expense Modal -->
                <div class="modal fade" id="viewSummaryModal" tabindex="-1" aria-labelledby="viewSummaryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewSummaryModalLabel">View Summary</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h4 class="mb-3">Summary</h4>
                                    <div class="btn-group align-self-center">
                                        <button type="button" class="select-project btn btn-outline-secondary select_project dropdown-toggle align-items-center mas-auto" data-bs-toggle="dropdown" aria-expanded="false">
                                            Select Project
                                        </button>
                                        <ul class="dropdown-menu position-absolute" style="background-color: #f0f0f0; z-index: 1050; overflow-y: auto; max-height: 250px;">
                                            <?php foreach ($projects as $project): ?>
                                                <li style="background-color: #f0f0f0;">
                                                    <!-- Each form submits its own project/client IDs -->
                                                    <form method="GET" style="margin: 0;">
                                                        <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                                        <input type="hidden" name="client_id" value="<?= $project['client_id'] ?>">
                                                        <input type="hidden" name="show_modal" value="1"> <!-- Added line -->
                                                        <button style="background-color: #f0f0f0; color: #555;" type="submit" class="dropdown-item text-start">
                                                            <?= $project['project_name'] ?><br>
                                                            <small class="text-muted">Client: <?= $project['client_name'] ?></small>
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <!-- For Displaying the DATA -->
                                <?php if (!empty($expense_data)): ?>
                                    <p class="mb-2"><strong>Client Name: <?= strtoupper($client_name) ?></strong></p>
                                    <p class="mb-4"><strong>Project Name: <?= ucwords($project_name) ?></strong></p>
                                    <ul class="ms-4 mb-3 me-4" style="list-style-type: none; padding-left: 0;">
                                        <li class="mb-3 d-flex justify-content-between">
                                            <strong>Category</strong> <strong>Amount</strong>
                                        </li>
                                        <?php foreach ($expense_data as $item): ?>
                                            <li class="d-flex justify-content-between">
                                                <p class="mb-2"><?= ucwords($item['category']) ?></p> ₱<?= number_format($item['amount'], 2) ?>
                                            </li>
                                        <?php endforeach; ?>
                                        <li class="text-end mt-0">
                                            <hr style="display: inline-block; 
                                            border-bottom: 2px solid black; 
                                            min-width: 150px;">
                                        </li>
                                        <li class="mt-1 d-flex justify-content-between">
                                            <strong>Total</strong> ₱<?= number_format($total_expense, 2) ?>
                                        </li>
                                    </ul>

                                <?php elseif (isset($_POST['project_id'])): ?>
                                    <p>No expenses recorded for this project.</p>
                                <?php else: ?>
                                    <p>Please select a project to view the summary.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Auto-open the modal on page reload if project/client is selected -->
                <?php if (!empty($_GET['project_id']) && !empty($_GET['client_id'])  && isset($_GET['show_modal'])): ?>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            var myModal = new bootstrap.Modal(document.getElementById('viewSummaryModal'));
                            myModal.show();
                        });
                    </script>
                <?php endif; ?>

                <div class="card-body px-0 pe-4 p-4">
                    <!-- For DATE RANGE -->
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
                            flatpickr("#customDateRangeExpense", {
                                mode: "range",
                                dateFormat: "Y-m-d",
                                onClose: function(selectedDates, dateStr) {
                                    filterByDate('custom', selectedDates);
                                }
                            });

                            // // If "Custom" option is selected
                            $('#dateFilterExpense').on('change', function() {
                                const value = $(this).val();
                                if (value === 'custom') {
                                    $('#customDateRangeExpense').show();
                                } else {
                                    $('#customDateRangeExpense').hide();
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
                                toolbar.append($('#project_expense_wrapper .dataTables_length'));
                                toolbar.append($('#dateRangeContainer'));
                                toolbar.append($('#project_expense_wrapper .dataTables_filter'));

                                // Insert toolbar before the table wrapper
                                $('#project_expense_wrapper').before(toolbar);
                            }, 0);
                        });
                    </script>
                    <?php

                    // $con = mysqli_connect("localhost", "root", "", "financial_management");

                    //This condition is to check whether date range is set or not. If not, all data is displayed
                    if (isset($_SESSION['filter_start']) && isset($_SESSION['filter_end'])) {
                        $start_period = $_SESSION['filter_start'];
                        $end_period = $_SESSION['filter_end'];

                        $fetch_query = "SELECT * FROM expenses WHERE date BETWEEN '$start_period' AND '$end_period' ORDER BY date DESC";
                    } else {

                        $fetch_query = "SELECT * FROM expenses ORDER BY date DESC";
                    }


                    $fetch_query_run = mysqli_query($conn, $fetch_query);

                    ?>

                    <div id="dateRangeWrapper" style="display: contents">
                        <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                            <label for="dateFilter" class="mb-0" style="font-size: 14px;">Filter Date by:</label>
                            <select id="dateFilterExpense" class="form-select form-select-sm w-auto" style="height: 30px;">
                                <option value="all">All</option>
                                <option value="weekly">This Week</option>
                                <option value="monthly">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            <input type="text" id="customDateRangeExpense" class="forDate form-control form-control-sm w-auto"
                                placeholder="Select date range" style="display: none; height: 30px;" />
                        </div>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-bordered border-secondary-subtle" id="project_expense" >
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Date</th>
                                <th scope="col">Description</th>
                                <th scope="col">Category</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Client & Project Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // If there are records to display
                            if (mysqli_num_rows($fetch_query_run) > 0) {
                                $rowNumber = 1;
                                while ($row = mysqli_fetch_array($fetch_query_run)) {
                                     $id = $row['id'];
                                    $date = $row['date'];
                                    $client_id = $row['client_id'];
                                    $project_id = $row['project_id'];
                                    $addon_id = $row['addon_id'];
                                    $archived_by = $row['archived_by'];
                                    $restored_by = $row['restored_by'];
                                    $archived_date = $row['archived_date'];
                                    $restored_date = $row['restored_date'];
                                    $description = $row['description'];
                                    $category =  $row['category'];
                                    $store_name =  $row['store_name'];
                                    $amount =  $row['amount'];
                                    $payment_method =  $row['payment_method'];
                                    $receipt_file = $row['receipt_file'];
                                    $invoice_num =  $row['invoice_num'];


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
                                    if (!empty($row['archived_by'])){
                                        $archiver_query = mysqli_query($conn, "SELECT name, role FROM users WHERE id = {$row['archived_by']}");
                                        $archiver_data = mysqli_fetch_assoc($archiver_query);
                                        $archiver_name = $archiver_data['name'] ?? '';
                                        $archiver_role = $archiver_data['role'] ?? '';
                                    }

                                    // To fetch restorer name and role
                                    $restorer_name = '';
                                    $restorer_role = '';
                                    if (!empty($row['restored_by'])){
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
                                        <td>&#8369; <?php echo number_format($row['amount'], 2); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <?php  
                                                    if (!empty($addon_title)) {
                                                        // If record is from an Add-on project
                                                        echo $client_name . " - ". $addon_title . " <br>(Addons for: " . $project_name . ") " ;
                                                    } else {
                                                        // Normal project
                                                        echo $project_name . " - " . $client_name;
                                                    }
                                                ?>
                                                <div class="dropdown" data-bs-display="static">
                                                    <button class="three_dots btn btn-light btn-sm ms-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#viewExpenseModal<?php echo $id; ?>">View Details</a></li>
                                                        <li><a class="dropdown-item text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#archiveExpenseModal<?php echo $id; ?>">Archive</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- View Expense Modal -->
                                    <div class="modal fade" id="viewExpenseModal<?php echo $id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <div class="modal-header" style="padding: 12px;">
                                                    <h5 class="modal-title" id="viewExpenseModal<?php echo $id; ?>">Expense Details</h5>
                                                    <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                    <!-- Right Column -->
                                                    <p><strong style="font-size: 16px;"><?php echo date('M d, Y', strtotime($row['date'])); ?></strong></p>
                                                    <p><strong style="font-size: 16px;">
                                                            <?php
                                                            if (!empty($addon_title)) {
                                                                // If record is from an Add-on project
                                                                echo $client_name . " - " . $addon_title . " <br><small>(Addons for: " . $project_name . ")</small> ";
                                                            } else {
                                                                // Normal project
                                                                echo $project_name . " - " . $client_name;
                                                            }
                                                            ?></strong>
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
                                                        <?php if (!empty($receipt_file) && file_exists('../receipts/project_expenses_files/' . $receipt_file)): ?>
                                                            <a href="../receipts/project_expenses_files/<?php echo htmlspecialchars($receipt_file); ?>" target="_blank" style="text-decoration: none;">
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
                                                                type="button" 
                                                                data-target="#logs<?php echo $id; ?>" >
                                                            <i class="bi bi-chevron-bar-down"></i>
                                                        </button>
                                                    <!-- Collapsible Logs Section -->
                                                    <div class="custom-collapse" id="logs<?php echo $id; ?>">
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
                                    <div class="modal fade" id="archiveExpenseModal<?php echo $id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <div class="modal-header" style="padding: 12px;">
                                                    <h5 class="modal-title" id="viewExpenseModal<?php echo $id; ?>">Confirm Archive</h5>
                                                    <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                    <div class="modal-body" style="line-height: 1.5; margin-bottom: 0; padding: 15px 15px 0 15px;">
                                                        <form method="POST" action="../forms_logic/archive.php">
                                                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                                                        <p style="font-size: 16px;">Are you sure you want to archive expense record <strong><?php echo $row['category'] . ' - ' . ucwords($row['description']); ?></strong>
                                                            from client <strong><?php echo $client_name; ?></strong> under <strong><?php echo $project_name; ?></strong> project? </p>

                                                         <div class="modal-footer" style="border-top: none; padding-bottom: 20px;">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="batchexp_archive_btn" class="btn btn-danger">Archive</button>
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
                                        ? "The selected project expense is no longer in active records and has been successfully moved to the archive."
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

            <!-- Modal to show budget warning -->
              <div class="modal fade" id="limitWarningModal" tabindex="-1" aria-labelledby="limitWarningModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content text-center p-4">
                            <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 70px;"></i>
                            <h4 class="mt-3">Allocated Budget Limit Reached</h4>
                            <div class="modal-body">
                            <p style="text-align: center; font-size: 16px;">
                            </p>
                            </div>
                            <div class="modal-footer border-0 d-flex justify-content-center">
                                <button type="button" class="btn btn-warning text-light fw-bold" data-bs-dismiss="modal" id="proceedBtn">Okay, I understand</button>
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

                //For Date Validation
                document.addEventListener("DOMContentLoaded", function() {
                   flatpickr("#expense-date", {
                         minDate: "2021-01-01",
                        maxDate: new Date(),     // today
                        dateFormat: "Y-m-d",
                        defaultDate: "today",    // auto-fill with today
                    });
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
                    invoiceInput.placeholder = " ";
                    invoiceInput.readOnly = false;
                    invoiceInput.classList.remove("invoice-no-required");
                }
            }
            function disableStore(selectElem) {
                // find the table row that contains this select
                const row = selectElem.closest(".row") || selectElem.parentElement;
                if (!row) return;
            
                // find the invoice input in the same row
                const storeInput = row.querySelector('input[name="store_name"]');
                if (!storeInput) return;
            
                // prefer data-category attribute if available, otherwise use the option text
                const selectedOption = selectElem.options[selectElem.selectedIndex];
                const rawCategory = (selectedOption?.getAttribute('data-category') || selectedOption?.text || "").toLowerCase().trim();
            
                // keywords that should exempt invoice number requirement
                const exemptKeywords = ["labor", "office staff", "staff", "salary"];
            
                // check if any keyword appears in the category string
                const isExempt = exemptKeywords.some(k => rawCategory.includes(k));
            
                if (isExempt) {
                    // Remove required, set value + placeholder and lock input
                    storeInput.removeAttribute("required");
                    storeInput.value = "No Store Name Required";
                    storeInput.placeholder = "No Store Name Required";
                    storeInput.classList.add("store-no-required"); // optional: for styling
                } else {
                    // Restore required, clear if it was the auto text, and unlock input
                    storeInput.setAttribute("required", "required");
                    if (storeInput.value === "No Store Name Required") storeInput.value = "";
                    storeInput.placeholder = "e.g., Wilcon Depot - Calamba";
                    storeInput.readOnly = false;
                    storeInput.classList.remove("store-no-required");
                }
            }
            </script>

</body>

</html>