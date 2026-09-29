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
                        <a class="nav-link active" aria-current="page" href="cashflow.php?project_id=<?php echo $project_id; ?>">Cash Flow</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_details.php?project_id=<?php echo $project_id; ?>">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal.php?project_id=<?php echo $project_id; ?>">Journal Entry</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="Income.php?project_id=<?php echo $project_id; ?>">Project Income</a>
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
                        <h4 style="font-weight: bold;">Operating Cash Flow</h4>
                        <button class="info-btn" type="button" data-bs-toggle="modal" data-bs-target="#CashflowInfo">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                            </svg>
                        </button>
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
                                        <input name="start_cashflow" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 25px; background-color: transparent;" placeholder="Select start date"
                                            value="<?php echo isset($_SESSION['cashflow_period_start']) ? $_SESSION['cashflow_period_start'] : ''; ?>">
                                    </div>
                                    <!-- For Ending Period Field -->
                                    <div class="form-group d-flex align-items-center" style="gap: 7px;">
                                        <label style="font-size: 15px; margin: 0;">Ending Period:</label>
                                        <input name="end_cashflow" class="form-control form-control-sm datepicker"
                                            style="padding: 2px 6px; width: 145px; height: 25px; margin-left: 0; background-color: transparent;" placeholder="Select end date"
                                            value="<?php echo isset($_SESSION['cashflow_period_end']) ? $_SESSION['cashflow_period_end'] : ''; ?>">
                                    </div>
                                    <!-- To change buttons -->
                                    <?php
                                    $filterSet = isset($_SESSION['cashflow_period_start']) && isset($_SESSION['cashflow_period_end']);
                                    ?>
                                    <button type="submit" id="setBtn" class="btn btn-dark btn-sm <?php echo $filterSet ? 'd-none' : '' ?>">Set Range</button>
                                    <button type="submit" name="clearness" id="clearbtn" value="true" class="btn btn-dark btn-sm <?php echo $filterSet ? '' : 'd-none' ?> ">Clear</button>
                                </div>
                            </form>
                        </div>
                        <script>
                            flatpickr(".datepicker", {
                                dateFormat: "Y-m-d",
                                minDate: "2021-01-01"
                            });
                        </script>

                        <div class="d-flex align-items-center mb-3 fsBtns">
                            <button type="button" id="pdf_cashflow" class="btn btn-outline-orange ms-auto">
                                <i class="bi bi-filetype-pdf"></i> Download PDF
                            </button>
                            <button type="button" id="print_cashflow" class="btn btn-outline-orange ms-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                 <div class="alert-secondary mb-4 p-2 unofficial-fs">
                            <strong>Important Note:</strong>
                            Please note that both expenses and payments related to project add-ons are included in this report. Accordingly, the table presented shows the combined results of the main project and its add-ons.                
                        </div>
                <?php

                $connection = mysqli_connect("localhost", "root", "", "financial_management");

                $start_date = $_SESSION['cashflow_period_start'] ?? null;
                $end_date = $_SESSION['cashflow_period_end'] ?? null;

                //Where conditions because the date range cannot be inserted after group by condition
                $conditions = "client_id  = '$client_id' AND project_id = '$project_id'";
                if ($start_date && $end_date) {
                    $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
                }

                //To fetch sum of client's payment to RVR Squared
                $cash_inflow_query = "SELECT SUM(amount) as total_cash_in FROM payment_clients WHERE $conditions ";
                $cash_inflow_result = mysqli_query($con, $cash_inflow_query);
                $cash_inflow_row = mysqli_fetch_assoc($cash_inflow_result);
                $total_cash_in = $cash_inflow_row['total_cash_in'] ?? 0;

                // To fetch expenses and calculate the total by category (will be considered as outflows)
                $cash_outflow_query = "SELECT category, SUM(amount) AS total_amount FROM expenses WHERE $conditions
                                            GROUP BY category";
                $cash_outflow_result =  mysqli_query($con, $cash_outflow_query);

                //Initialize amount and array for storing fetched data
                $total_outflow = 0;
                $outflow_data = [];

                while ($outflow_row = mysqli_fetch_assoc($cash_outflow_result)) {
                    $category = $outflow_row['category'];
                    $amount = $outflow_row['total_amount'];
                    $outflow_data[] = [
                        'category' => $category,
                        'amount' => $amount,
                    ];
                    $total_outflow += $amount;
                }

                $net_cash_flow = $total_cash_in - $total_outflow;

                if ($total_cash_in == 0 && $total_outflow == 0 && $net_cash_flow == 0) {
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
                <div id="balanceSheetPDF" class="Completed> mb-3">
                    <!-- Used as id name to prevent having new css -->
                    <div class="card-body" id="balanceSheetPrint">
                        <div class="print-only" style="margin-bottom: 20px;">
                            <h5 style="margin: 0;">Client: <strong><?php echo $client_name; ?></strong></h5>
                            <h5 style="margin: 0;">Project: <strong><?php echo $project_name; ?></strong></h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="cash-flow_table">

                                <!-- Revenue Section -->
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-light bg-completed">Cash Flow from Operating Activities:</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>Cash received from client as payment</td>
                                        <td class="right-column">&#8369; <?php echo number_format($total_cash_in, 2); ?></td>
                                    </tr>

                                    <?php foreach ($outflow_data as $outflow) { ?>
                                        <tr>
                                            <td>Payment of <?php echo $outflow['category']; ?></td>
                                            <td class="right-column">(&#8369; <?php echo number_format($outflow['amount'], 2); ?>)</td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <th class="totals" style="background-color: white !important; color: black !important;">Cash Generated by Operating Activities:</th>
                                        <th class="right-column totals " style="background-color: white !important; color: black !important;">
                                            <span class="line-total-cf">
                                                <?php echo $net_cash_flow < 0 ? '(&#8369; ' . number_format(abs($net_cash_flow), 2) . ')' : '&#8369; ' . number_format($net_cash_flow, 2); ?>
                                            </span>
                                            <!-- The condition will enclose in a parenthesis if amount is negative -->
                                        </th>
                                    </tr>

                                </tbody>
                            </table>
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
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        new bootstrap.Modal(document.getElementById('noDataModal')).show();
                    });
                </script>
            <?php
                unset($_SESSION['status'], $_SESSION['status_type']);
            endif;
            ?>

            <!-- MODAL FOR CASHFLOW INFO -->
            <div class="modal fade" id="CashflowInfo" tabindex="-1" aria-labelledby="infoCashflow" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Centered and wider -->
                    <div class="modal-content">
                        <div class="modal-header" style="padding: 12px">
                            <h5 class="modal-title fw-bold" id="CashflowInfoLabel"><strong><?php echo $project_name ?></strong> Cashflow Report</h5>
                            <button type="button" class="btn-close" style="font-size: 14px" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body mb-0" style="text-align: justify; font-size: 15px;">
                            The <strong>Statement of Cash Flows</strong> outlines the cash inflows and
                            outflows of this project during a specific period. As this report focuses
                            solely on <i>Operating Activities</i>, it emphasizes how the project's core
                            operations generate and utilize cash. <br><br>

                            Please note that <strong>both expenses and payments related to project add-ons
                                are included in this report.</strong> Accordingly, the table presented shows
                            the combined results of the main project and its add-ons.
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
                    const pdfButton = document.getElementById('pdf_cashflow');
                    const printButton = document.getElementById('print_cashflow');


                    // This is for fetching and displaying only the print dialog of balance-sheet template

                    // Function to handle print action
                    function handlePrint() {
                        const iframe = document.getElementById('printFrame');
                        iframe.onload = function() {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        };
                        iframe.src = 'cashflow_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
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
                            const styleTags = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                            styleTags.forEach(style => {
                                clonedContent.appendChild(style.cloneNode(true));
                            });

                            html2pdf()
                                .set({
                                    margin: [0.2, 0.5, 0.5, 0.5],
                                    filename: 'CashflowReport.pdf',
                                    image: {
                                        type: 'jpeg',
                                        quality: 0.98
                                    },
                                    html2canvas: {
                                        scale: 2,
                                        useCORS: true,
                                        logging: false,
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
                                .from(clonedContent)
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
                                    pdf.save('CashflowReport.pdf');
                                })
                                .catch(err => {
                                    console.error('PDF generation failed:', err);
                                    alert('Failed to generate PDF.');
                                });
                        };

                        iframe.src = 'cashflow_print.php?client_id=<?= $client_id ?>&project_id=<?= $project_id ?>';
                    }

                    document.getElementById('pdf_cashflow').addEventListener('click', function(e) {
                        e.preventDefault();
                        handlePdfDownload();

                    });

                    // Handle Print button click
                    document.getElementById('print_cashflow').addEventListener('click', function(e) {
                        e.preventDefault();
                        handlePrint();
                    });
                });
            </script>


</body>

</html>