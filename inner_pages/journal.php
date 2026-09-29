<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// Check if project_id is provided
if (isset($_GET['project_id']) && is_numeric($_GET['project_id'])) {
    $project_id = mysqli_real_escape_string($conn, $_GET['project_id']);

    // Fetch project details along with client_id
    $project_query = "SELECT * FROM projects WHERE project_id = '$project_id'";
    $project_result = mysqli_query($conn, $project_query);
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
$project_status_result = mysqli_query($conn, $project_status_query);
$project_data = mysqli_fetch_assoc($project_status_result);
$project_status = $project_data['project_status'] ?? 'Ongoing';
$is_completed = ($project_status === 'Completed');
$button_class = $project_status !== 'Completed' ? 'btn-outline-secondary' : 'btn-outline-orange'; //This is for changing the color of print and pdf btns

// For quote (username, email, and date generated)
$username = $_SESSION['auth_user']['name'] ?? 'Unknown User';
$email = $_SESSION['auth_user']['email'] ?? 'No Email';
date_default_timezone_set('Asia/Manila');
$generated_at = date('Y-m-d H:i:s');

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($conn, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';
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
                        <a class="nav-link active" href="journal.php?project_id=<?php echo $project_id; ?>">Journal Entry</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="Income.php?project_id=<?php echo $project_id; ?>">Project Income</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Content Section -->
                <div class="content mt-4 p-1">
                    <div class="d-flex mb-3">
                        <h4 style="font-weight: bold;">Journal Entry</h4>
                        <!-- For Project Status -->
                        <input type="hidden" id="project_status" value="<?php echo $project_status; ?>">

                        <div class="d-flex align-items-center mb-3 ms-auto">

                            <!-- <button type="button" id="xls_journal" class="btn btn-outline-success ms-auto">
                                <i class="bi bi-filetype-xls"></i> Download Excel
                                </button>
                                <button type="button" id="csv_journal" class="btn btn-outline-success ms-2">
                                <i class="bi bi-filetype-csv"></i> Download CSV
                                </button> -->
                            <button type="button" id="pdf_journal" class="btn <?php echo $button_class; ?> ms-auto">
                                <i class="bi bi-filetype-pdf"></i> Download PDF
                            </button>
                            <button type="button" id="print_journal" class="btn <?php echo $button_class; ?> ms-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="d-flex gap-5">
                        <div id="dateRangeContainer">
                            <form action="../forms_logic/date_range.php" id="dateFilterField" method="GET">
                                <!-- Hidden fields to identify client and project -->
                                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                                <input type="hidden" name="source" value="project_details">
                                <!-- For Starting Period Field -->
                                <div class="period d-flex align-items-center justify-content-end" style="gap: 10px;">
                                    <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                        <label style="font-size: 15px; margin: 0;">Starting Period:</label>
                                        <input name="start_journal" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 25px; background-color: transparent;" placeholder="Select start date"
                                            value="<?php echo isset($_SESSION['journal_period_start']) ? $_SESSION['journal_period_start'] : ''; ?>">
                                    </div>
                                    <!-- For Ending Period Field -->
                                    <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                        <label style="font-size: 15px; margin: 0;">Ending Period:</label>
                                        <input name="end_journal" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 25px; margin-left: 0; background-color: transparent;" placeholder="Select end date"
                                            value="<?php echo isset($_SESSION['journal_period_end']) ? $_SESSION['journal_period_end'] : ''; ?>">
                                    </div>
                                    <!-- To change buttons -->
                                    <?php
                                    $filterSet = isset($_SESSION['journal_period_start']) && isset($_SESSION['journal_period_end']);
                                    ?>
                                    <button type="submit" id="setBtn" class="btn btn-dark btn-sm <?php echo $filterSet ? 'd-none' : '' ?>">Set Range</button>
                                    <button type="submit" name="klir" id="clearbtn" value="true" class="btn btn-dark btn-sm <?php echo $filterSet ? '' : 'd-none' ?> ">Clear</button>
                                </div>
                            </form>
                        </div>
                        <script>
                            $(document).ready(function() {
                                $('#journal_table').DataTable({
                                    responsive: true,
                                    dom: 'lfrtip',
                                    ordering: false,
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

                                var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');

                                // Append elements in correct order
                                toolbar.append($('#journal_table_wrapper .dataTables_length'));
                                toolbar.append($('#dateRangeContainer'));
                                toolbar.append($('#journal_table_wrapper .dataTables_filter'));

                                // Insert toolbar before the table wrapper
                                $('#journal_table_wrapper').before(toolbar);

                                // Initialize Flatpickr
                                flatpickr(".datepicker", {
                                    dateFormat: "Y-m-d",
                                    minDate: "2021-01-01"
                                });
                            });
                        </script>
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
                                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Proceed</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($project_status !== 'Completed'): ?>
                        <div class="alert-secondary mb-4 p-2 unofficial-fs">
                            <strong>Important Note:</strong>
                            This document serves as a preliminary/initial journal entry reflecting current project data. It is subject to revisions and will only be finalized upon the formal completion of the project.
                        </div>
                    <?php endif; ?>

                    <div id="balanceSheetPDF">
                        <div class="card-body" id="balanceSheetPrint">
                            <div class="print-only" style="margin-bottom: 20px;">
                                <h5 style="margin: 0;">Client: <strong><?php echo $client_name; ?></strong></h5>
                                <h5 style="margin: 0;">Project: <strong><?php echo $project_name; ?></strong></h5>
                            </div>
                            <div class="table-responsive">
                            <table class="table table-bordered" id="journal_table">

                                <thead>
                                    <tr>
                                        <th scope="col" class="row_num bg-success text-light text-center">#</th>
                                        <th scope="col" class="bg-success text-light text-center">Date</th>
                                        <th scope="col" class="bg-success text-light text-center">Account Title</th>
                                        <th scope="col" class="bg-success text-light text-center">Debit</th>
                                        <th scope="col" class="bg-success text-light text-center">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php

                                    //Queries for calculating TOTAL ASSETS
                                    //Query for calculating total cash 
                                    $connection = mysqli_connect("localhost", "root", "", "financial_management");

                                    $start_date = $_SESSION['journal_period_start'] ?? null;
                                    $end_date = $_SESSION['journal_period_end'] ?? null;

                                    //Where conditions because the date range cannot be inserted after group by condition
                                    $conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
                                    if ($start_date && $end_date) {
                                        $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
                                    }

                                    //First query is to select accounts for DEBIT
                                    $project_cost_query = "SELECT projected_budget_cost FROM projects WHERE client_id = '$client_id' AND project_id = '$project_id'";
                                    $cost_result = mysqli_query($con, $project_cost_query);
                                    $project_cost_row = mysqli_fetch_assoc($cost_result);
                                    $project_cost = $project_cost_row ? (float)$project_cost_row['projected_budget_cost'] : 0;
                                    $clean_poject_cost = (float) str_replace(',', '', $project_cost);

                                    // Query to fetch the very first payment
                                    $payment_query = "SELECT amount FROM payment_clients WHERE $conditions ORDER BY date ASC LIMIT 1";
                                    $payment_result = mysqli_query($con, $payment_query);
                                    $first_payment_row = mysqli_fetch_assoc($payment_result);
                                    $first_payment_amount = $first_payment_row ? (float)$first_payment_row['amount'] : 0;
                                    $clean_first_payment = (float) str_replace(',', '', $first_payment_amount);

                                    // Calculate accounts receivable based on the first payment
                                    $accounts_receivable = $clean_poject_cost - $clean_first_payment;

                                    $journal_query = "SELECT 
                                                    date, description, 
                                                     category AS debit_account, 
                                                        amount AS debit_amount, 
                                                        payment_method AS credit_account, 
                                                        amount AS credit_amount
                                                FROM expenses WHERE $conditions ORDER BY date ASC";

                                    $journal_result = mysqli_query($con, $journal_query);

                                    $payment_clients_query = "SELECT 
                                                            date, 
                                                            addon_id,
                                                            category, 
                                                            description,
                                                            amount 
                                                        FROM payment_clients
                                                        WHERE $conditions
                                                        ORDER BY date ASC";

                                    $payment_result = mysqli_query($con, $payment_clients_query);
                                    $rowNumber = 1;
                                    $firstPayments = [];

                                    $capital_query = "SELECT date, description, amount FROM budget_allocation
                                                        WHERE $conditions ORDER BY date ASC";
                                    $capital_result = mysqli_query($con, $capital_query);

                                    //This is used to fetch and store results first in an array
                                    $payments = [];
                                    while ($row = mysqli_fetch_assoc($payment_result)) {
                                        $row['entry_type'] = 'payment'; //used to define that the row is from payment
                                        $payments[] = $row;
                                    }

                                    $journals = [];
                                    while ($row = mysqli_fetch_assoc($journal_result)) {
                                        $row['entry_type'] = 'journal'; //used to define that the row is from expenses
                                        $journals[] = $row;
                                    }
                                    $capitals = [];
                                    while ($row = mysqli_fetch_assoc($capital_result)) {
                                        $row['entry_type'] = 'capital'; 
                                        $row['debit_account'] = 'Cash';
                                        $row['credit_account'] = 'Capital';
                                        $row['debit_amount'] = (float) $row['amount'];
                                        $row['credit_amount'] = (float) $row['amount'];
                                        $capitals[] = $row;
                                    }

                                    //used to combine results so that it will be arranged in ASC order based on date
                                    $combined = array_merge($payments, $journals, $capitals);
                                    usort(
                                        $combined,
                                        function ($a, $b) {
                                            return strtotime($a['date']) - strtotime($b['date']);
                                        }
                                    );


                                    // Process client payments
                                    foreach ($combined as $row) {
                                        if ($row['entry_type'] === 'payment') {
                                            $key = $project_id . '_' . ($row['addon_id'] ?? 0);
                                            // First payment logic
                                            if (!isset($firstPayments[$key])) {

                                                // If addon project
                                                if (!empty($row['addon_id']) && $row['addon_id'] != 0) {
                                                    $addon_id = $row['addon_id'];

                                                    // Fetch projected cost for this addon
                                                    $addon_cost_query = "SELECT projected_budget_cost 
                                                                        FROM project_addons 
                                                                        WHERE project_id = '$project_id' 
                                                                        AND addon_id = '$addon_id'";
                                                    $addon_cost_result = mysqli_query($con, $addon_cost_query);
                                                    $addon_cost_row = mysqli_fetch_assoc($addon_cost_result);
                                                    $project_cost = $addon_cost_row ? (float) str_replace(',', '', $addon_cost_row['projected_budget_cost']) : 0;
                                                }
                                                // Else = main project
                                                else {
                                                    $main_cost_query = "SELECT projected_budget_cost 
                                                                        FROM projects 
                                                                        WHERE client_id = '$client_id' 
                                                                        AND project_id = '$project_id'";
                                                    $main_cost_result = mysqli_query($con, $main_cost_query);
                                                    $main_cost_row = mysqli_fetch_assoc($main_cost_result);
                                                    $project_cost = $main_cost_row ? (float) str_replace(',', '', $main_cost_row['projected_budget_cost']) : 0;
                                                }

                                                // Compute Accounts Receivable
                                                $first_payment_amount = (float) str_replace(',', '', $row['amount']);
                                                $accounts_receivable = $project_cost - $first_payment_amount;
                                    ?>
                                                <tr>
                                                    <td><?php echo $rowNumber++; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                    <td>Cash</td>
                                                    <td>&#8369; <?php echo number_format($row['amount'], 2); ?></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <td>Accounts Receivable</td>
                                                    <td>₱ <?php echo number_format($accounts_receivable, 2); ?></td>
                                                    <td></td>
                                                </tr>
                                                <tr class="credit-entry">
                                                    <td></td>
                                                    <td></td>
                                                    <td class="credit-account">Service Revenue</td>
                                                    <td></td>
                                                    <td>₱ <?php echo number_format($project_cost, 2); ?></td>
                                                </tr>
                                            <?php
                                                $firstPayments[$key] = true;
                                                //If subsequent payments for addons, only Client Initial Payment as Debit and AR as Credit
                                            } else {
                                            ?>
                                                <tr>
                                                    <td><?php echo $rowNumber++; ?></td>
                                                    <td><?php echo $row['date']; ?></td>
                                                    <td>Cash</td>
                                                    <td>&#8369; <?php echo number_format($row['amount'], 2); ?></td>
                                                    <td></td>
                                                </tr>
                                                <tr class="credit-entry">
                                                    <td></td>
                                                    <td></td>
                                                    <td class="credit-account">Accounts Receivable</td>
                                                    <td></td>
                                                    <td>&#8369; <?php echo number_format($row['amount'], 2); ?></td>
                                                </tr>
                                            <?php
                                            }

                                            // Description
                                            ?>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td class="account-description">(To record received <?php echo isset($row['description']) ? $row['description'] : 'No Description'; ?> of &#8369; <?php echo number_format($row['amount'], 2); ?>)</td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        <?php

                                        } elseif ($row['entry_type'] === 'journal') {
                                        ?>
                                            <tr>
                                                <td><?php echo $rowNumber++; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['debit_account']; ?></td>
                                                <td>&#8369; <?php echo number_format($row['debit_amount'], 2); ?></td>
                                                <td></td>
                                            </tr>
                                            <tr class="credit-entry">
                                                <td></td>
                                                <td></td>
                                                <td class="credit-account"><?php echo $row['credit_account']; ?></td>
                                                <td></td>
                                                <td>&#8369; <?php echo number_format(floatval(str_replace(',', '', $row['credit_amount'])), 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td class="account-description">
                                                    <?php 
                                                    if ($row['debit_account'] === 'Salary Expense'){
                                                        echo "(To record payment of {$row['description']} - {$row['debit_account']} paid via {$row['credit_account']}.)";
                                                    } else{
                                                    echo "(To record purchase of {$row['description']} - {$row['debit_account']} paid via {$row['credit_account']}.)";
                                                    }
                                                    ?>
                                                </td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                    <?php
                                        }
                                        elseif ($row['entry_type'] === 'capital') {
                                           ?>
                                            <tr>
                                                <td><?php echo $rowNumber++; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['debit_account']; ?></td>
                                                <td>&#8369; <?php echo number_format($row['debit_amount'], 2); ?></td>
                                                <td></td>
                                            </tr>
                                            <tr class="credit-entry">
                                                <td></td>
                                                <td></td>
                                                <td class="credit-account"><?php echo $row['credit_account']; ?></td>
                                                <td></td>
                                                <td>&#8369; <?php echo number_format(floatval(str_replace(',', '', $row['credit_amount'])), 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td class="account-description">(To record <?php echo $row['description']; ?> of &#8369;<?php echo number_format($row['debit_amount'], 2); ?>.)</td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                           <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>


                <!-- This is for print and download purpose  -->
                <iframe id="printFrame" style="display:none;"></iframe>

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

                    document.addEventListener('DOMContentLoaded', function() {
                        const projectStatus = document.getElementById('project_status').value;
                        const pdfButton = document.getElementById('pdf_journal');
                        const printButton = document.getElementById('print_journal');
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
                            iframe.src = 'journal_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
                        }


                        function handlePdfDownload() {
                            const iframe = document.getElementById('printFrame');

                            iframe.onload = function() {
                                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                const container = iframeDoc.querySelector('#download-container');

                                if (!container) {
                                    alert("Download container not found!");
                                    return;
                                }

                                // Wait for rendering to complete
                                setTimeout(() => {
                                    html2pdf()
                                        .from(container)
                                        .set({
                                            filename: 'JournalEntry.pdf',
                                            margin: [15, 10, 10, 10], // [top, right, bottom, left] in mm; increase top margin for all pages
                                            html2canvas: {
                                                scale: 2,
                                                scrollY: 0,
                                                ignoreElements: (element) => element.classList.contains('footer') 
                                            },
                                            jsPDF: {
                                                unit: 'mm',
                                                format: 'a4',
                                                orientation: 'portrait'
                                            },
                                            pagebreak: {
                                                mode: ['css'],
                                                avoid: ['tr'] // Avoid breaking inside table rows
                                            }
                                        })
                                        .toPdf()
                                        .get('pdf')
                                        .then(function(pdf) {
                                            // Customize subsequent pages
                                            const totalPages = pdf.internal.getNumberOfPages();
                                            for (let i = 2; i <= totalPages; i++) {
                                                pdf.setPage(i);
                                                pdf.setFontSize(10);
                                                pdf.setFont('times', 'italic')
                                                pdf.setTextColor(0, 0, 0); // Black text
                                                pdf.text('Continued...', 10, 10); // Optional: Indicate continuation
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
                                                            pageHeight - 5, 
                                                            { align: "center" }
                                                        );
                                                    }
                                        })
                                        .save()
                                        .catch(err => {
                                            console.error('PDF generation failed:', err);
                                            alert('Failed to generate PDF.');
                                        });
                                }, 500);
                            };

                            iframe.src = 'journal_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
                        }

                        document.getElementById('pdf_journal').addEventListener('click', function(e) {
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
                        document.getElementById('print_journal').addEventListener('click', function(e) {
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