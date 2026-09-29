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
    <link rel="stylesheet" href="../resources/css/index.css">
    <link rel="stylesheet" href="../resources/css/project_details.css">
</head>

<body>

    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <!-- Main Content -->
        <div class="main-content container-fluid ">


            <!-- Top Nav -->
            <?php
            $page_title = htmlspecialchars($project['project_name'] ?? 'Unknown Project');
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-5">

                <!-- Breadcrumbs -->
                <div style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                    <ol class="breadcrumb mt-3">
                        <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>

                        <li class="breadcrumb-item"><a href="projects.php?client_id=<?php echo $client_id; ?>">Projects</a></li> <!-- Pass client_id -->

                        <li class="breadcrumb-item active" aria-current="page">Project Details</li>
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
                        <a class="nav-link" href="project_details.php?project_id=<?php echo $project_id; ?>">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal.php?project_id=<?php echo $project_id; ?>">Journal Entry</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Income.php?project_id=<?php echo $project_id; ?>">Income Statement</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cashflow.php?project_id=<?php echo $project_id; ?>">Cash Flow</a>
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
                    <h3>Balance Sheet</h3>
                    <div class="d-flex gap-5 mt-3 mb-3">
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
                                        <input name="start_balance" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 30px; background-color: transparent;" placeholder="Select start date"
                                            value="<?php echo isset($_SESSION['balance_period_start']) ? $_SESSION['balance_period_start'] : ''; ?>">
                                    </div>
                                    <!-- For Ending Period Field -->
                                    <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                        <label style="font-size: 15px; margin: 0;">Ending Period:</label>
                                        <input name="end_balance" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 30px; margin-left: 0; background-color: transparent;" placeholder="Select end date"
                                            value="<?php echo isset($_SESSION['balance_period_end']) ? $_SESSION['balance_period_end'] : ''; ?>">
                                    </div>
                                    <!-- To change buttons -->
                                    <?php
                                    $filterSet = isset($_SESSION['balance_period_start']) && isset($_SESSION['balance_period_end']);
                                    ?>
                                    <button type="submit" id="setBtn" class="btn btn-dark btn-sm <?php echo $filterSet ? 'd-none' : '' ?>">Set Range</button>
                                    <button type="submit" name="clearbtn" id="clearbtn" value="true" class="btn btn-dark btn-sm <?php echo $filterSet ? '' : 'd-none' ?> ">Clear</button>
                                </div>
                            </form>
                        </div>

                        <script>
                            flatpickr(".datepicker", {
                                dateFormat: "Y-m-d"
                            });
                        </script>
                        <!-- For Project Status -->
                        <input type="hidden" id="project_status" value="<?php echo $project_status; ?>">

                        <div class="d-flex align-items-center mb-3 ms-auto">
                            <!-- <button type="button" id="xls_balance" class="btn btn-outline-success ms-auto">
                        <i class="bi bi-filetype-xls"></i> Download Excel
                        </button>
                        <button type="button" id="csv_balance" class="btn btn-outline-success  ms-2">
                        <i class="bi bi-filetype-csv"></i> Download CSV
                        </button> -->
                            <button type="button" id="pdf_balance" class="btn <?php echo $button_class; ?> ms-auto">
                                <i class="bi bi-filetype-pdf"></i> Download PDF
                            </button>
                            <button type="button" id="print_balance" class="btn <?php echo $button_class; ?> ms-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
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
                        <div class="blur-overlay-note">
                            <div class="eye-icon">
                                <i class="bi bi-eye-slash-fill" style="font-size: 70px; color: #393E46;"></i>
                            </div>
                            <strong style="color: #393E46;">Important Note</strong><br>
                            This Balance Sheet will only be accessible once the project status is marked as Completed.
                        </div>
                    <?php endif; ?>
                    <?php

                    //Queries for calculating TOTAL ASSETS
                    $con = mysqli_connect("localhost", "root", "", "financial_management");

                    $start_date = $_SESSION['balance_period_start'] ?? null;
                    $end_date = $_SESSION['balance_period_end'] ?? null;

                    //Where conditions because the date range cannot be inserted after group by condition
                    $conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
                    if ($start_date && $end_date) {
                        $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
                    }

                    // Fetch total cash inflow (debits) - payment of client
                    $query_cash = "SELECT SUM(amount) as total_cash FROM payment_clients WHERE $conditions";
                    $cash_result = mysqli_query($con, $query_cash);
                    $cash_row = mysqli_fetch_assoc($cash_result);
                    $total_cash_in = $cash_row['total_cash'] ?? 0;

                    //For fetching the project cost as Service Revenue in Equity
                    $project_cost_query = "SELECT projected_budget_cost FROM projects WHERE client_id = '$client_id' AND project_id = '$project_id'";
                    $cost_result = mysqli_query($con, $project_cost_query);
                    $project_cost = mysqli_fetch_assoc($cost_result)['projected_budget_cost'];
                    $clean_poject_cost = (float) str_replace(',', '', $project_cost);

                    //For computing the Accounts Receivable
                    $payment_query = "SELECT SUM(amount) AS total_client_payments FROM payment_clients WHERE $conditions AND category LIKE '%Payment%'";
                    $payment_result = mysqli_query($con, $payment_query);
                    $total_client_payments = mysqli_fetch_assoc($payment_result)['total_client_payments'] ?? 0;

                    $accounts_receivable = floatval($clean_poject_cost) - floatval($total_client_payments);

                    // Fetch total cash outflow (expenses) as liabilities

                    $query_cashOut = "SELECT category, SUM(amount) as total_amount FROM expenses 
                    WHERE $conditions GROUP BY category";
                    $result_cash_out = mysqli_query($con, $query_cashOut);

                    $total_cash_out = 0; // Initialize total outflow
                    while ($row_cash_out = mysqli_fetch_assoc($result_cash_out)) {
                        $category = $row_cash_out['category'];
                        $amount = $row_cash_out['total_amount'];
                        $total_cash_out += $amount; // Add to total outflow
                    }

                    if ($total_cash_in == 0 && $clean_poject_cost == 0 && $total_client_payments == 0 && $total_cash_out == 0) {
                        $_SESSION['status_type'] = "no_data";
                        $_SESSION['status'] = "No Record Found";
                    }
                    //COmputation/Assignment for displaying Balance Sheet
                    $total_cash = floatval($total_cash_in) - floatval($total_cash_out);
                    $accounts_receivable = floatval($clean_poject_cost) - floatval($total_client_payments);
                    $total_assets = floatval($total_cash) + floatval($accounts_receivable);
                    $total_liabilities = floatval($total_cash_out);
                    $equity = floatval($clean_poject_cost);
                    $total_liabilities_equity = floatval($clean_poject_cost) - floatval($total_cash_out);

                    //Will be used for Printing View
                    $client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
                    $client_result = mysqli_query($con, $client_query);
                    $client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

                    $project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
                    $project_result = mysqli_query($con, $project_query);
                    $project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';
                    ?>

                    <div id="balanceSheetPDF" class="<?php echo $project_status !== 'Completed' ? 'not-completed' : ''; ?> mb-3">
                        <div class="card-body" id="balanceSheetPrint">
                            <div class="row g-0">
                                <div class="col-md-6">
                                    <table class="table" style="width: 95%;">

                                        <!-- For ASSETS -->
                                        <thead>
                                            <tr>
                                                <?php $isCompleted = $project_status === 'Completed'; ?>
                                                <th colspan="2" class="text-light <?php echo $isCompleted ? 'bg-completed' : ''; ?>">ASSETS</th>
                                            </tr>
                                        </thead>
                                        <tr>
                                            <td style="border-bottom: none;">Cash</td>
                                            <td class="text-end" style="border-bottom: none;">
                                                <?php echo $total_cash < 0 ? '(&#8369; ' . number_format(abs($total_cash), 2) . ')' : '&#8369; ' . number_format($total_cash, 2); ?></td>
                                        </tr>
                                        <tr style="border-top: none; padding: none; line-height: .5;">
                                            <td></td>
                                            <td class="text-end"><small class="text-muted" style="font-size: 14px; font-style: italic; line-height: 1;">(Expenses paid in cash are being deducted)</small></td>
                                        </tr>
                                        <tr>
                                            <td style="white-space: nowrap;">Accounts Receivable</td>
                                            <td class="text-end">
                                                <span class="line-total"
                                                    style="display: inline-block; 
                                            border-bottom: 2px solid #6c757d; 
                                            min-width: 170px;
                                            text-align: right;">
                                                    <?php echo $accounts_receivable < 0 ? '(&#8369; ' . number_format(abs($accounts_receivable), 2) . ')' : '&#8369; ' . number_format($accounts_receivable, 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr class="fw-bold totals">
                                            <td>Total Assets</td>
                                            <td class="text-end text-success">
                                                <?php echo $total_assets < 0 ? '(&#8369; ' . number_format(abs($total_assets), 2) . ')' : '&#8369; ' . number_format($total_assets, 2); ?></td>
                                            <!-- The condition will enclose in a parenthesis if amount is negative -->

                                        </tr>
                                    </table>
                                </div>

                                <!-- Table for LIABILITIES AND EQUITY -->
                                <div class="col-md-6">
                                    <table class="table" style="width: 95%;">
                                        <thead>
                                            <tr>
                                                <?php $isCompleted = $project_status === 'Completed'; ?>
                                                <th colspan="2" class="text-light <?php echo $isCompleted ? 'bg-completed' : ''; ?>">LIABILITIES</th>
                                            </tr>
                                        </thead>

                                        <?php
                                        // Fetch all expense categories and amounts for liabilities
                                        //This is required so that each category will be displayed instead of displaying the latest only
                                        $query_cashOut = "SELECT category, SUM(amount) as total_amount FROM expenses 
                             WHERE $conditions GROUP BY category";
                                        $result_cash_out = mysqli_query($con, $query_cashOut);

                                        $total_cash_out = 0; // Reset total cash out to recalculate

                                        // Loop through and display each category dynamically
                                        while ($row_cash_out = mysqli_fetch_assoc($result_cash_out)) {
                                            $category = $row_cash_out['category'];
                                            $amount = $row_cash_out['total_amount'];
                                            $total_cash_out += $amount; // Accumulate the total liabilities
                                        ?>
                                            <tr>
                                                <td><?php echo $category; ?></td>
                                                <td class="text-end">&#8369; <?php echo number_format($amount, 2); ?></td>
                                            </tr>
                                        <?php } ?>
                                        <tr class="fw-bold bg-light">
                                            <td>Total Liabilities</td>
                                            <td class="text-end text-success">(&#8369; <?php echo number_format($total_cash_out, 2); ?>)</td>
                                        </tr>
                                        <thead>
                                            <tr>
                                                <?php $isCompleted = $project_status === 'Completed'; ?>
                                                <th colspan="2" class="text-light <?php echo $isCompleted ? 'bg-completed' : ''; ?>">EQUITY</th>
                                            </tr>
                                        </thead>
                                        <tr>
                                            <td>Service Revenue</td>
                                            <td class="text-end">&#8369; <?php echo number_format($clean_poject_cost, 2); ?></td>
                                        </tr>
                                        <tr class="fw-bold bf-light">
                                            <td>Total Equity</td>
                                            <td class="text-end text-success">
                                                <span class="line-total"
                                                    style="display: inline-block; 
                                            border-bottom: 2px solid #6c757d; 
                                            min-width: 170px;">
                                                    &#8369; <?php echo number_format($clean_poject_cost, 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr class="fw-bold totals">
                                            <td>Total Liabilities and Equity</td>
                                            <td class="text-end text-success">
                                                <?php echo $total_liabilities_equity < 0 ? '(&#8369; ' . number_format(abs($total_liabilities_equity), 2) . ')' : '&#8369; ' . number_format($total_liabilities_equity, 2); ?></td>
                                            <!-- The condition will enclose in a parenthesis if amount is negative -->

                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
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

                <script src="./resources/js/auto_logout.js"></script>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        new bootstrap.Modal(document.getElementById('noDataModal')).show();
                    });
                </script>
            <?php
                unset($_SESSION['status'], $_SESSION['status_type']);
            endif;
            ?>
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
                document.getElementById('clearbtn').addEventListener('click', function(e) {

                    document.querySelector('[name="start_balance"]').value = '';
                    document.querySelector('[name="end_balance"]').value = '';

                });
                // This is for fetching and displaying only the print dialog of balance-sheet template
                document.addEventListener('DOMContentLoaded', function() {
                    // Function to handle print action
                    function handlePrint() {
                        const iframe = document.getElementById('printFrame');
                        iframe.onload = function() {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        };
                        iframe.src = 'balance_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
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
                                    margin: [0.3, 0.3, 0.3, 0.3],
                                    filename: 'BalanceSheet.pdf',
                                    image: {
                                        type: 'jpeg',
                                        quality: 0.95
                                    },
                                    html2canvas: {
                                        scale: 1.5,
                                        useCORS: true,
                                        logging: false,
                                        windowWidth: document.body.scrollWidth
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
                                    pdf.save('BalanceSheet.pdf');
                                })
                                .catch(err => {
                                    console.error('PDF generation failed:', err);
                                    alert('Failed to generate PDF.');
                                });
                        };

                        iframe.src = 'balance_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
                    }

                    document.getElementById('pdf_balance').addEventListener('click', function(e) {
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
                    document.getElementById('print_balance').addEventListener('click', function(e) {
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