<?php
include('../dbcon.php');
include('../layout/session_check.php');

$connection = mysqli_connect("localhost", "root", "", "financial_management");

// Check if project_id is provided
if (isset($_GET['project_id']) && is_numeric($_GET['project_id'])) {
    $project_id = mysqli_real_escape_string($connection, $_GET['project_id']);

    // Fetch project details along with client_id
    $project_query = "SELECT * FROM projects WHERE project_id = '$project_id'";
    $project_result = mysqli_query($connection, $project_query);
    $project = mysqli_fetch_assoc($project_result);

    if (!$project) {
        die("Project not found.");
    }

    $client_id = $project['client_id']; // Get the client_id from the project
} else {
    die("No project selected.");
}
//Will be used to disable print and download
$project_status_query = "SELECT project_name, project_status FROM projects WHERE project_id = '$project_id'";
$project_status_result = mysqli_query($connection, $project_status_query);
$project_data = mysqli_fetch_assoc($project_status_result);
$project_status = $project_data['project_status'] ?? 'Ongoing';
$is_completed = ($project_status === 'Completed');
$button_class = $project_status !== 'Completed' ? 'btn-outline-secondary' : 'btn-outline-orange'; //This is for changing the color of print and pdf btns

// For quote (username, email, and date generated)
$username = $_SESSION['auth_user']['name'] ?? 'Unknown User';
$email = $_SESSION['auth_user']['email'] ?? 'No Email';
date_default_timezone_set('Asia/Manila');
$generated_at = date('Y-m-d H:i:s');

$client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
$client_result = mysqli_query($connection, $client_query);
$client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($connection, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('../forms_logic/fs_incomeproj.php');
}

$error = $_SESSION['fs_error_income'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details</title>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/financial_statements.css">
    <link rel="stylesheet" href="../resources/css/main.css">
    <link rel="stylesheet" href="../resources/css/project_details.css">
</head>

<body>


    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>


        <!-- Main Content -->
       <div class="main-content container-fluid">

            <!-- Top Nav -->
            <?php
            $page_title = 'Project Details';
            include '../layout/topnav.php';
            ?>

            <div class="nav-container-fluid p-0">

                
                <!-- Breadcrumbs -->
                <div style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                        <li class="breadcrumb-item"><a href="projects.php?client_id=<?php echo $client_id; ?>">Projects</a></li> <!-- Pass client_id -->
                        <li class="breadcrumb-item active"><?php echo $project_name; ?></li>
                    </ol>
                </div>

                <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="proj_stat.php?project_id=<?php echo $project_id; ?>">Project Status</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="proj_budget_sum.php?project_id=<?php echo $project_id; ?>">Project Budget Summary</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="cashflow.php?project_id=<?php echo $project_id; ?>">Cash Flow</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_details.php?project_id=<?php echo $project_id; ?>">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal.php?project_id=<?php echo $project_id; ?>">Journal Entry</a>
                        <!-- </li>
                    <li class="nav-item">
                        <a class="nav-link" href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="Income.php?project_id=<?php echo $project_id; ?>">Project Income</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>
                <!-- Script for disabling the date range -->
                <script>
                    document.addEventListener("DOMContentLoaded", () => {
                        const status = document.getElementById('project_status')?.value?.toLowerCase();
                        const notCompletedModal = new bootstrap.Modal(document.getElementById('notCompletedModal'));

                        // Only intercept if project is NOT completed
                        if (status !== 'completed') {
                            // Make the inputs readonly (no datepicker, but still clickable)
                            document.querySelectorAll('#dateFilterField input.datepicker').forEach(input => {
                                input.readOnly = true;
                                input.style.cursor = 'not-allowed';

                                // Show modal on click
                                input.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    notCompletedModal.show();
                                });
                                if (input._flatpickr) {
                                    input._flatpickr.destroy();
                                }
                            });
                            document.getElementById('dateFilterField').addEventListener('submit', function(e) {
                                e.preventDefault();
                                notCompletedModal.show();
                            });
                        } else {
                            // Initialize Flatpickr only when status is completed
                            datepickerInputs.forEach(input => {
                                flatpickr(input, {
                                    dateFormat: "Y-m-d",
                                });
                            })
                        }
                        document.querySelector('#proceedBtn').addEventListener('click', function(e) {
                            notCompletedModal.hide();
                        });
                    });
                </script>
                <!-- Content Section -->
                <div class="content mt-4">
                    <div class="income-header">
                        <h4 style="font-weight: bold;">Project Income</h4>
                        <!--<button class="info-btn" type="button" data-bs-toggle="modal" data-bs-target="#IncomeInfo">-->
                        <!--    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">-->
                        <!--        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />-->
                        <!--        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />-->
                        <!--    </svg>-->
                        <!--</button>-->
                    </div>
                    <div class="table-toolbar d-flex flex-wrap justify-content-between align-items-center mt-2 mb-2">
                        <div id="dateRangeContainer">
                            <form action="../forms_logic/date_range.php" id="dateFilterField" method="GET">
                                <!-- Hidden fields to identify client and project -->
                                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                                <input type="hidden" name="source" value="project_details">
                                <!-- For Starting Period Field -->
                                <div class="period d-flex align-items-center justify-content-end" style="gap: 10px; margin-bottom: 20px;">
                                    <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                        <label style="font-size: 15px; margin: 0;">Starting Period:</label>
                                        <input name="start_income" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 30px; background-color: transparent;" placeholder="Select start date"
                                            value="<?php echo isset($_SESSION['income_period_start']) ? $_SESSION['income_period_start'] : ''; ?>">
                                    </div>
                                    <!-- For Ending Period Field -->
                                    <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                        <label style="font-size: 15px; margin: 0;">Ending Period:</label>
                                        <input name="end_income" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 30px; margin-left: 0; background-color: transparent;" placeholder="Select end date"
                                            value="<?php echo isset($_SESSION['income_period_end']) ? $_SESSION['income_period_end'] : ''; ?>">
                                    </div>
                                    <!-- To change buttons -->
                                    <?php
                                    $filterSet = isset($_SESSION['income_period_start']) && isset($_SESSION['income_period_end']);
                                    ?>
                                    <button type="submit" id="setBtn" class="btn btn-dark btn-sm <?php echo $filterSet ? 'd-none' : '' ?>">Set Range</button>
                                    <button type="submit" name="clrbtn" id="clearbtn" value="true" class="btn btn-dark btn-sm <?php echo $filterSet ? '' : 'd-none' ?> ">Clear</button>
                                </div>
                            </form>
                        </div>
                        <script>
                            flatpickr(".datepicker", {
                                dateFormat: "Y-m-d",
                                minDate: "2021-01-01"
                            });
                        </script>
                        <!-- For Project Status -->
                        <input type="hidden" id="project_status" value="<?php echo $project_status; ?>">

                        <div class="d-flex align-items-center mb-3 fsBtns">

                            <!-- <button type="button" id="xls_income" class="btn btn-outline-success ms-auto">
                            <i class="bi bi-filetype-xls"></i> Download Excel
                            </button>
                            <button type="button" id="csv_income" class="btn btn-outline-success ms-2">
                            <i class="bi bi-filetype-csv"></i> Download CSV
                            </button> -->
                            <button type="button" id="pdf_income" class="btn <?php echo $button_class; ?> ms-auto">
                                <i class="bi bi-filetype-pdf"></i> Download PDF
                            </button>
                            <button type="button" id="print_income" class="btn <?php echo $button_class; ?> ms-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                

                <!-- Warning Modal for Non-Completed Projects -->
                <div class="modal fade" id="notCompletedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content text-center p-4">
                            <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 70px;"></i>
                            <h4 class="mt-3">Action Not Available</h4>
                            <p style="text-align: center; font-size: 16px;">
                                The Balance Sheet is only downloadable and printable after the project is marked as Completed.
                            </p>
                            <div class="modal-footer border-0 d-flex justify-content-center">
                                <button type="button" class="btn btn-warning" data-bs-dismiss="modal" id="proceedBtn">Proceed</button>
                            </div>
                        </div>
                    </div>
                </div>
                 <?php if ($project_status !== 'Completed'): ?>
                        <div class="alert-secondary mb-4 p-2 unofficial-fs">
                            <strong>Important Note:</strong>
                            This document serves as a preliminary/initial Project Income reflecting current project data. It is subject to revisions and will only be finalized upon the formal completion of the project.
                        </div>
                <?php endif; ?>
                <!--<?php if ($project_status !== 'Completed'): ?>-->
                <!--    <div class="blur-overlay-note">-->
                <!--        <div class="eye-icon mb-0 mt-2 d-block" style="line-height: 1.3;">-->
                <!--            <i class="bi bi-eye-slash-fill" style="font-size: 60px; color: #393E46;"></i>-->
                <!--            <strong style="color: #393E46; font-weight: bold; display: block; margin-top: 2px;">Important Note</strong>-->
                <!--            <p style="font-size: 16px; margin-top: 5px;">This Income Statement will only be accessible once the project status is marked as Completed.</p>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--<?php endif; ?>-->
                <?php

                // To fetch the project cost and assign as service revenue
                $connection = mysqli_connect("localhost", "root", "", "financial_management");

                $start_date = $_SESSION['income_period_start'] ?? null;
                $end_date = $_SESSION['income_period_end'] ?? null;

                //Where conditions because the date range cannot be inserted after group by condition
                $conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
                if ($start_date && $end_date) {
                    $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
                }

                $project_cost_query = "SELECT projected_budget_cost FROM projects WHERE client_id = '$client_id' AND project_id = '$project_id'";
                $project_cost_result = mysqli_query($con, $project_cost_query);
                $project_cost_row = mysqli_fetch_assoc($project_cost_result);
                $service_revenue = $project_cost_row['projected_budget_cost'] ?? 0;
                $clean_main_project = (float) str_replace(',', '', $service_revenue);

                $addons_cost_query = "SELECT projected_budget_cost FROM project_addons WHERE project_id = '$project_id'";
                $addons_cost_result = mysqli_query($con, $addons_cost_query);
                $addons_cost_row = mysqli_fetch_assoc($addons_cost_result);
                $addons_cost = $addons_cost_row['projected_budget_cost'] ?? 0;
                $clean_addons_cost = (float) str_replace(',', '', $addons_cost);

                $clean_service_revenue = $clean_main_project + $clean_addons_cost;

                // To fetch expenses and calculate the total by category
                $expenses_query = "SELECT category, SUM(amount) AS total_amount FROM expenses WHERE $conditions
                                        GROUP BY category";
                $expense_result =  mysqli_query($con, $expenses_query);

                //Initialize amount and array for storing fetched data
                $total_expense = 0;
                $expense_data = [];

                while ($expense_row = mysqli_fetch_assoc($expense_result)) {
                    $category = $expense_row['category'];
                    $amount = $expense_row['total_amount'];
                    $expense_data[] = [
                        'category' => $category,
                        'amount' => $amount,
                    ];
                    $total_expense += $amount;
                }

                $net_before_tax = $clean_service_revenue - $total_expense;
                $first_tax_expense = $clean_service_revenue / 1.12;
                $second_tax_expense = $first_tax_expense * 0.12;
                $tax_expense = $second_tax_expense;
                $net_after_tax = $net_before_tax - $tax_expense;

                if ($clean_service_revenue == 0 && $total_expense == 0 &&  $net_after_tax == 0) {
                    $_SESSION['status_type'] = "no_data";
                    $_SESSION['status'] = "No Record Found";
                }


                //Will be used for Printing View
                $client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
                $client_result = mysqli_query($con, $client_query);
                $client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

                $project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
                $project_result = mysqli_query($con, $project_query);
                $project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';
                ?>
                <div class="fs-container">
                    <?php $fsClass = empty($_SESSION['fs_unlocked_income']) ? 'fs-blur' : 'fs-clear';?>
                    <div class="<?= $fsClass ?>">
                        <div id="balanceSheetPDF" class="Completed mb-3">
                            <!-- Used as id name to prevent having new css -->
                            <div class="card-body" id="balanceSheetPrint">
                                <div class="print-only" style="margin-bottom: 20px;">
                                    <h5 style="margin: 0;">Client: <strong><?php echo $client_name; ?></strong></h5>
                                    <h5 style="margin: 0;">Project: <strong><?php echo $project_name; ?></strong></h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table income-table" id="income_table">
                                        <!-- Revenue Section -->
                                        <thead>
                                            <tr>
                                                <!-- <?php $isCompleted = $project_status === 'Completed'; ?> <?php echo $isCompleted ? 'bg-completed' : ''; ?> -->
                                                <th colspan="3" class="text-light bg-completed ">Revenue</th>
                                            </tr>
                                        </thead>

                                        <tr>
                                            <td>Service Revenue</td>
                                            <td class="right-column">&#8369; <?php echo number_format($clean_service_revenue, 2); ?></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="cf-head">Total Revenue</td>
                                            <td></td>
                                            <td class="totals">&#8369; <?php echo number_format($clean_service_revenue, 2); ?></td>
                                        </tr>

                                        <!-- Expenses Section -->
                                        <thead>
                                            <tr>
                                                <!-- <?php $isCompleted = $project_status === 'Completed'; ?> <?php echo $isCompleted ? 'bg-completed' : ''; ?> -->
                                                <th colspan="3" class="text-light bg-completed">Expenses</th>
                                            </tr>
                                        </thead>
                                        <?php 
                                        // This is for giving span class only to the last row for expenses
                                        $lastIndex = count($expense_data) - 1; 
                                        foreach ($expense_data as $index => $expense) { ?>
                                            <tr>
                                                <td><?php echo $expense['category']; ?></td>
                                                <td class="right-column">
                                                    <?php 
                                                    if ($index === $lastIndex) { ?>
                                                    <span class="line-total-exp">
                                                        (&#8369; <?php echo number_format($expense['amount'], 2); ?>)
                                                    </span> 
                                                    <?php } else { ?>
                                                         (&#8369; <?php echo number_format($expense['amount'], 2); ?>)
                                                    <?php } ?>
                                                </td>
                                                <td></td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <td class="cf-head">Total Expenses</td>
                                            <td></td>
                                            <td class="totals">
                                                <span class="line-total-income">
                                                    (&#8369; <?php echo number_format($total_expense, 2); ?>)
                                                </span>
                                            </td>
                                        </tr>

                                        <!-- Net Income Section -->
                                        <thead>
                                            <tr class="incomeb4tax">
                                                <th style="background-color: white !important; color: black !important;">Operating Income</th>
                                                <th style="background-color: white !important; color: black !important;"></th>
                                                <td class="totals">
                                                    <?php echo  $net_before_tax < 0 ? '(&#8369; ' . number_format(abs($net_before_tax), 2) . ')' : '&#8369; ' . number_format($net_before_tax, 2); ?></td>
                                            </tr>
                                        </thead>

                                        <tr>
                                            <td class="cf-head">Value Added Tax</td>
                                            <td></td>
                                            <td class="totals">
                                                <span class="line-total-tax">
                                                    (&#8369; <?php echo number_format($tax_expense, 2); ?>)
                                                </span>
                                            </td>

                                        </tr>

                                        <thead>
                                            <tr class="incomeafter">
                                                <th style="background-color: white !important; color: black !important;">Project Income</th>
                                                <th style="background-color: white !important; color: black !important;"></th>
                                                <td class="totals text-success">
                                                    <?php echo $net_after_tax < 0 ? '(&#8369; ' . number_format(abs($net_after_tax), 2) . ')' : '&#8369; ' . number_format($net_after_tax, 2); ?></td>
                                                <!-- The condition will enclose in a parenthesis if amount is negative -->

                                            </tr>
                                        </thead>


                                    </table>
                                </div>
                            </div>
                        </div>
                </div>
                <?php if (empty($_SESSION['fs_unlocked_income'])): ?>
                        <div class="fs-blur-overlay">
                            <form method="POST">
                                    <div class="description-unlock">
                                       <p>To continue, please provide your account password to verify your identity and access the RVR SMES financial statements.</p>
                                    </div>
                                    <?php if (!empty($_SESSION['fs_error_income'])): ?>
                                        <div class="text-danger mt-2 mb-2"><?= $_SESSION['fs_error_income'] ?></div>
                                        <?php unset($_SESSION['fs_error_income']);?>
                                    <?php endif; ?>
                                    <div class="form-floating mb-3 position-relative">
                                        <input type="password" name="unlock_password" class="form-control" id="floatingPassword"
                                            placeholder="Password"
                                            value="">
                                        <label for="floatingPassword">Password</label>
                                        <input type="hidden" name="client_name" value="<?= $client_name ?>">
                                        <input type="hidden" name="project_name" value="<?= $project_name ?>">
                                        <input type="hidden" name="project_id" value="<?= $project_id ?>">

                                        <!-- Show/Hide Password -->
                                        <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2"
                                            style="z-index: 2;" onclick="togglePassword('floatingPassword', 'toggleIcon1')">
                                            <i id="toggleIcon1" class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <button type="submit" name="confirm-pass-income" class="login_btn w-100 mt-3">Submit</button>
                                    </div>
                                    
                            </form>
                        </div>
                        <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($_SESSION['status'])): ?>
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
                document.addEventListener("DOMContentLoaded", function() {
                    new bootstrap.Modal(document.getElementById('noDataModal')).show();
                });
            </script>
        <?php
            unset($_SESSION['status'], $_SESSION['status_type']);
        endif;
        ?>

        <!-- MODAL FOR INCOME STATEMENT INFO -->
        <div class="modal fade" id="IncomeInfo" tabindex="-1" aria-labelledby="infoIncome" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Centered and wider -->
                <div class="modal-content">
                    <div class="modal-header" style="padding: 12px">
                        <h5 class="modal-title fw-bold" id="IncomeInfoLabel"><strong><?php echo $project_name ?></strong> Income Statement</h5>
                        <button type="button" class="btn-close" style="font-size: 14px" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body mb-0" style="text-align: justify; font-size: 15px;">
                        The <strong>Income Statement</strong>, also referred to as the
                        <strong>Profit and Loss Statement</strong>, provides a summary of the project's
                        revenues, expenses, and resulting net profit or loss for a given period. <br><br>

                        Please note that the prices and expenses of project add-ons are already included
                        in this computation. For the calculation of <i>Value Added Tax</i>, the
                        <strong>Service Revenue (Total Gross)</strong> is multiplied by 12%
                        (<i>Total Gross × 12%</i>).
                    </div>
                    <div class="modal-footer mt-0" style="padding-bottom: 20px; margin-top: none; border-top: none;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- This is for print and download purpose  -->
        <iframe id="printFrame" style="display:none;"></iframe>
        <script>
            
            function togglePassword(inputId, iconId) {
                let inputField = document.getElementById(inputId);
                let icon = document.getElementById(iconId);

                if (inputField.type === "password") {
                    inputField.type = "text";
                    icon.classList.replace("bi-eye", "bi-eye-slash");
                } else {
                    inputField.type = "password";
                    icon.classList.replace("bi-eye-slash", "bi-eye");
                }
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

            document.addEventListener('DOMContentLoaded', function() {
                const projectStatus = document.getElementById('project_status').value;
                const pdfButton = document.getElementById('pdf_income');
                const printButton = document.getElementById('print_income');
                const notCompletedModal = new bootstrap.Modal(document.getElementById('notCompletedModal'));

                // Function to check if action is allowed
                function isActionAllowed() {
                    if (projectStatus !== 'Completed') {
                        notCompletedModal.show();
                        return false;
                    }
                    return true;
                }

                // This is for fetching and displaying only the print dialog of balance-sheet template

                // Function to handle print action
                function handlePrint() {
                    const iframe = document.getElementById('printFrame');
                    iframe.onload = function() {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    };
                    iframe.src = 'income_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
                }

                // Function to handle PDF download
                function handlePdfDownload() {
                    const iframe = document.getElementById('printFrame');
                    iframe.onload = function() {
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        const container = iframeDoc.querySelector('#download-container');

                        if (!container) {
                            alert('Download container not found');
                            return;
                        }

                        const clonedContent = container.cloneNode(true);
                        const styles = iframeDoc.querySelectorAll('style');
                        const wrapper = document.createElement('div'); //Container for the cloned content
                        const headClone = document.createElement('head');
                        const styleElement = document.createElement('style');

                        styles.forEach(style => {
                            headClone.appendChild(style.cloneNode(true));
                        });
                        styleElement.textContent = iframeDoc.querySelector('style').textContent;
                        headClone.appendChild(styleElement);

                        const htmlWrapper = document.createElement('html');
                        const bodyClone = document.createElement('body');
                        bodyClone.style.backgroundColor = 'transparent';
                        bodyClone.appendChild(clonedContent);
                        htmlWrapper.appendChild(headClone);
                        htmlWrapper.appendChild(bodyClone);

                        html2pdf()
                            .set({
                                margin: [0.3, 0.5, 0.5, 0.5],
                                filename: 'IncomeStatement.pdf',
                                image: {
                                    type: 'jpeg',
                                    quality: 0.98
                                },
                                html2canvas: {
                                    scale: 2,
                                    useCORS: true,
                                    logging: false,
                                    ignoreElements: (element) => element.classList.contains('footer') 

                                },
                                jsPDF: {
                                    unit: 'in',
                                    format: 'a4',
                                    orientation: 'portrait'
                                },
                                pagebreak: {
                                    avoid: ['table', 'tr']
                                }
                            })
                            .from(htmlWrapper)
                            .toPdf()
                            .get('pdf')
                            .then(function(pdf) {
                                const totalPages = pdf.internal.getNumberOfPages();
                                if (totalPages > 1) {
                                    pdf.deletePage(totalPages); // Remove the extra page
                                }
                                const pageWidth = pdf.internal.pageSize.getWidth();
                                const pageHeight = pdf.internal.pageSize.getHeight();

                                for (let j = 1; j <= totalPages; j++) {
                                    pdf.setPage(j);
                                    pdf.setFontSize(10);
                                    pdf.setTextColor(85, 85, 85);

                                    pdf.text(
                                        "Generated by: <?php echo addslashes($username); ?> (<?php echo addslashes($email); ?>)  |  Date & Time: <?php echo $generated_at; ?>",
                                        pageWidth / 2,
                                        pageHeight - 0.5, {
                                            align: "center"
                                        }
                                    );
                                }
                                pdf.save('IncomeStatement.pdf');
                            })
                            .catch(err => {
                                console.error('PDF generation failed:', err);
                                alert('Failed to generate PDF.');
                            });
                    };

                    iframe.src = 'income_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
                }

                document.getElementById('pdf_income').addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default action
                    const projectStatus = document.getElementById('project_status').value;
                    console.log('PDF Button - Project Status:', projectStatus); // Debug
                    if (projectStatus !== 'Completed') {
                        const notCompletedModal = new bootstrap.Modal(document.getElementById('notCompletedModal'));
                        notCompletedModal.show(); // Show the modal
                    } else {
                        handlePdfDownload(); // Call PDF download only for Completed projects
                    }
                });

                // Handle Print button click
                document.getElementById('print_income').addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default action
                    const projectStatus = document.getElementById('project_status').value;
                    console.log('Print Button - Project Status:', projectStatus); // Debug
                    if (projectStatus !== 'Completed') {
                        const notCompletedModal = new bootstrap.Modal(document.getElementById('notCompletedModal'));
                        notCompletedModal.show(); // Show the modal
                    } else {
                        handlePrint(); // Call print only for Completed projects
                    }
                });
            });
        </script>


</body>

</html>