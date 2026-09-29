<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

$connection = mysqli_connect("localhost", "root", "", "financial_management");

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
//Will be used for Printing View
$client_query = "SELECT client_name FROM clients WHERE client_id = '$client_id'";
$client_result = mysqli_query($conn, $client_query);
$client_name = mysqli_fetch_assoc($client_result)['client_name'] ?? 'N/A';

$project_query = "SELECT project_name FROM projects WHERE project_id = '$project_id'";
$project_result = mysqli_query($conn, $project_query);
$project_name = mysqli_fetch_assoc($project_result)['project_name'] ?? 'N/A';

// For quote (username, email, and date generated)
$username = $_SESSION['auth_user']['name'] ?? 'Unknown User';
$email = $_SESSION['auth_user']['email'] ?? 'No Email';
date_default_timezone_set('Asia/Manila');
$generated_at = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Project Details"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/project_details.css">
    <link rel="stylesheet" href="../resources/css/expense.css">
    <link rel="stylesheet" href="../resources/css/financial_statements.css">
</head>

<body>
    <div class="d-flex">

        <!-- Side Nav Container (Layout) -->
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
                        <a class="nav-link active " aria-current="page" href="#">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal.php?project_id=<?php echo $project_id; ?>">Journal Entry</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link " href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="Income.php?project_id=<?php echo $project_id; ?>">Project Income</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Content Section -->
                <div class="content mt-4 p-0">
                     <div class="d-flex align-items-center mb-4">
                        <h4 style="font-weight: bold;">List of Expenses</h4>
                        <button type="button" id="pdf_summary_exp" class="btn btn-outline-orange ms-auto">
                            <i class="bi bi-filetype-pdf"></i> Download PDF
                        </button>
                        <button type="button" id="print_summary_exp" class="btn btn-outline-orange ms-2">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                <div class="mb-4 p-2 mt-3 align-items-center justify-content-center unofficial-fs">
                   Note that expenses for project addons are not included here. 
                   Its expense list can be viewed in <i><a class="expenseTab" href="proj_budget_sum.php?project_id=<?php echo $project_id; ?>" style="color: black;">project budget summary</a></i> page.
                   <strong> To add or record a new expense, kindly proceed to the Transactions tab or click this <i><a class="expenseTab" href="batch_expense.php"> link</a></i> to open the expense form. </strong>               
                </div>
                
                <div id="balanceSheetPDF">
                    <div class="card-body p-0" id="balanceSheetPrint">
                        <div class="print-only" style="margin-bottom: 20px;">
                            <h5 style="margin: 0;">Client: <strong><?php echo $client_name; ?></strong></h5>
                            <h5 style="margin: 0;">Project: <strong><?php echo $project_name; ?></strong></h5>
                        </div>

                        <?php
                        $connection = mysqli_connect("localhost", "root", "", "financial_management");

                        $start_date = $_SESSION['period_start'] ?? null;
                        $end_date = $_SESSION['period_end'] ?? null;

                        //Where conditions because the date range cannot be inserted after group by condition
                        $conditions = "client_id  = '$client_id' AND project_id = '$project_id' AND (addon_id = 0 OR addon_id IS NULL)";
                        if ($start_date && $end_date) {
                            $conditions .= " AND date BETWEEN '$start_date' AND '$end_date'";
                        }

                        $expenses_query = "SELECT category, SUM(amount) AS total_amount FROM expenses WHERE $conditions GROUP BY category ORDER BY category ASC";
                        $expense_result = mysqli_query($con, $expenses_query);

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
                        ?>

                        <div class="expenseBox mb-5">
                            <div class="exbox table-responsive">
                                <table class="table total_expenses table-bordered" style="width: 100%; overflow: hidden;" id="summaryTable">
                                    <thead>
                                        <th>Total Expenses</th>
                                        <th style="width: 55%;">Amount (PHP)</th>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expense_data as $expense) { ?>
                                            <tr>
                                                <td><?php echo $expense['category']; ?></td>
                                                <td style="text-align: center;">&#8369; <?php echo number_format($expense['amount'], 2); ?></td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <td class="fw-bold" style="background-color: #afdcff4b">Total</td>
                                            <td class="fw-bold text-center" style="background-color: #afdcff4b">&#8369; <?php echo number_format($total_expense, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <script type="text/javascript">
                            let selectedStartDate = null;
                            let selectedEndDate = null;

                            $(document).ready(function() {
                                var table = $('#expense_deets').DataTable({
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

                                //Variable to hold the "All" records of summary table when filtered is backed to All
                                var originalSummaryHtml = $('#summaryTable tbody').html();
                               

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
                                            selectedStartDate = null;
                                            selectedEndDate = null;
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
                                        const day = today.getDay(); // 0 (Sun) to 6 (Sat)

                                         const monday = new Date(today);
                                        monday.setDate(today.getDate() - (day === 0 ? 6 : day - 1)); // If Sunday, go back 6 days

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
                                        $('#summaryTable tbody').html(originalSummaryHtml);
                                        if (table.rows({
                                                filter: 'applied'
                                            }).count() === 0) {
                                            noDataModal.show();
                                        }
                                        return;
                                    }
                                    selectedStartDate = startDate.getFullYear() + '-' +
                                        String(startDate.getMonth() + 1).padStart(2, '0') + '-' +
                                        String(startDate.getDate()).padStart(2, '0');

                                    selectedEndDate = endDate.getFullYear() + '-' +
                                        String(endDate.getMonth() + 1).padStart(2, '0') + '-' +
                                        String(endDate.getDate()).padStart(2, '0');

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
                                    updateSummaryTable();
                                    if (table.rows({
                                            filter: 'applied'
                                        }).count() === 0) {
                                        noDataModal.show();
                                    }
                                }
                                // To apply date filter as well on the summary table
                                function updateSummaryTable() {
                                    const summary = {};
                                    const $summaryBody = $('#summaryTable tbody');
                                    const totalRows = table.rows().count();
                                    const filteredRows = table.rows({
                                        filter: 'applied'
                                    }).count();

                                    if (filteredRows === totalRows) {
                                        $summaryBody.html(originalSummaryHtml); // Restore original summary
                                        return;
                                    }

                                    table.rows({
                                        filter: 'applied'
                                    }).every(function() {
                                        const row = $(this.node());
                                        const category = row.find('td').eq(3).text().trim(); // 4th column
                                        const amountText = row.find('td').eq(4).text().replace(/[₱,]/g, '').trim(); // 5th column
                                        const amount = parseFloat(amountText) || 0;

                                        if (category) {
                                            summary[category] = (summary[category] || 0) + amount;
                                        }
                                    });

                                    $summaryBody.empty();

                                    if (Object.keys(summary).length === 0) {
                                        $summaryBody.append(`<tr>
                                        <td colspan=2 class="text-center">No matching records found</td>
                                        </tr>`);
                                        return;
                                    }

                                    for (const [category, total] of Object.entries(summary)) {
                                        $summaryBody.append(`
                                        <tr>
                                            <td>${category}</td>
                                            <td class="right-column">&#8369; ${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                        </tr>
                                    `);
                                    }
                                }

                                // Ensure DataTables wrapper is ready before manipulating
                                setTimeout(function() {
                                    // Create toolbar container
                                    var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');

                                    // Append elements in correct order
                                    toolbar.append($('#expense_deets_wrapper .dataTables_length'));
                                    toolbar.append($('#dateRangeContainer'));
                                    toolbar.append($('#expense_deets_wrapper .dataTables_filter'));

                                    // Insert toolbar before the table wrapper
                                    $('#summaryTable').before(toolbar);
                                }, 0);
                                 function handlePrint() {
                                    const iframe = document.getElementById('printFrame');
                                    const clientId = '<?= $_GET['client_id'] ?? '' ?>';
                                    const projectId = '<?= $_GET['project_id'] ?? '' ?>';

                                    let url = `project_details_print.php?client_id=${clientId}&project_id=${projectId}`;
                                    if (selectedStartDate && selectedEndDate) {
                                        url += `&start_period=${selectedStartDate}&end_period=${selectedEndDate}`;
                                    }

                                    iframe.onload = function () {
                                        setTimeout(() => {
                                            iframe.contentWindow.focus();
                                            iframe.contentWindow.print();
                                        }, 300);
                                    };

                                    iframe.src = url;
                                }

                                // Print button event
                                document.getElementById('print_summary_exp').addEventListener('click', function (e) {
                                    e.preventDefault();
                                    handlePrint();
                                });

                                function handlePdfDownload() {
                                    const iframe = document.getElementById('printFrame');

                                    iframe.onload = function () {
                                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                        const container = iframeDoc.querySelector('#download-container');

                                        if (!container) {
                                            alert("Download container not found!");
                                            return;
                                        }

                                        setTimeout(() => {
                                            html2pdf()
                                                .from(container)
                                                .set({
                                                    filename: 'ProjectExpenses.pdf',
                                                    margin: [15, 10, 10, 10],
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
                                                        avoid: ['tr']
                                                    }
                                                })
                                                .toPdf()
                                                .get('pdf')
                                                .then(function (pdf) {
                                                    const totalPages = pdf.internal.getNumberOfPages();
                                                    for (let i = 2; i <= totalPages; i++) {
                                                        pdf.setPage(i);
                                                        pdf.setFontSize(10);
                                                        pdf.setFont('times', 'italic');
                                                        pdf.text('Continued...', 10, 10);
                                                    }
                                                    const pageWidth = pdf.internal.pageSize.getWidth();
                                                    const pageHeight = pdf.internal.pageSize.getHeight();

                                                    for (let j = 1; j <= totalPages; j++) {
                                                        pdf.setPage(j);
                                                        pdf.setFontSize(9);
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

                                    const clientId = '<?= $_GET['client_id'] ?? '' ?>';
                                    const projectId = '<?= $_GET['project_id'] ?? '' ?>';

                                    let url = `project_details_print.php?client_id=${clientId}&project_id=${projectId}`;
                                    if (selectedStartDate && selectedEndDate) {
                                        url += `&start_period=${selectedStartDate}&end_period=${selectedEndDate}`;
                                    }

                                    iframe.src = url;
                                }

                                document.getElementById('pdf_summary_exp').addEventListener('click', function (e) {
                                    e.preventDefault();
                                    handlePdfDownload();
                                });
                            });
                        </script>
                        <div id="dateRangeWrapper" style="display: contents">
                            <div id="dateRangeContainer" class="d-flex align-items-center gap-2 flex-shrink-0">
                                <label for="dateFilter" class="mb-0" style="font-size: 14px;">Filter by:</label>
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
                            <table class="table expense_deets table-bordered" id="expense_deets" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th scope="col" class="row_num">#</th>
                                        <th scope="col">Date</th>
                                        <th scope="col" style="width: 24%;">Description</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php

                                    $connection = mysqli_connect("localhost", "root", "", "financial_management");

                                    if (isset($_SESSION['period_start']) && isset($_SESSION['period_end'])) {
                                        $start_period = $_SESSION['period_start'];
                                        $end_period = $_SESSION['period_end'];

                                        $fetch_query = "SELECT * FROM expenses WHERE client_id = '$client_id' 
                                                        AND project_id = '$project_id' AND (addon_id = 0 OR addon_id IS NULL) AND date BETWEEN '$start_period' AND '$end_period' ORDER BY date DESC";
                                    } else {
                                        $fetch_query = "SELECT * FROM expenses WHERE client_id = '$client_id' 
                                                        AND project_id = '$project_id' AND (addon_id = 0 OR addon_id IS NULL) ORDER BY date DESC";
                                    }

                                    $fetch_query_run = mysqli_query($con, $fetch_query);

                                    // Fetch total amount (exclude addon_id too)
                                    $total_query = "SELECT SUM(amount) AS total_amount FROM expenses WHERE client_id = '$client_id' 
                                                    AND project_id = '$project_id' AND (addon_id = 0 OR addon_id IS NULL)";
                                    $total_result = mysqli_query($con, $total_query);
                                    $total_row = mysqli_fetch_assoc($total_result);
                                    $totalAmount = $total_row['total_amount'];


                                    if (mysqli_num_rows($fetch_query_run) > 0) {
                                        $rowNumber = 1;
                                        while ($row = mysqli_fetch_array($fetch_query_run)) {
                                            $id = $row['id'];
                                            $date = $row['date'];
                                            $client_id = $row['client_id'];
                                            $project_id = $row['project_id'];
                                            $archived_by = $row['archived_by'];
                                            $restored_by = $row['restored_by'];
                                            $archived_date = $row['archived_date'];
                                            $restored_date = $row['restored_date'];
                                            $description = $row['description'];
                                            $category =  $row['category'];
                                            $invoice_num =  $row['invoice_num'];
                                            $store_name =  $row['store_name'];
                                            $amount =  $row['amount'];
                                            $payment_method =  $row['payment_method'];
                                            $receipt_file = $row['receipt_file'];

                                            // To fetch archiver name and role
                                            $archiver_name = '';
                                            $archiver_role = '';
                                            if (!empty($row['archived_by'])){
                                                $archiver_query = mysqli_query($con, "SELECT name, role FROM users WHERE id = {$row['archived_by']}");
                                                $archiver_data = mysqli_fetch_assoc($archiver_query);
                                                $archiver_name = $archiver_data['name'] ?? '';
                                                $archiver_role = $archiver_data['role'] ?? '';
                                            }

                                            // To fetch restorer name and role
                                            $restorer_name = '';
                                            $restorer_role = '';
                                            if (!empty($row['restored_by'])){
                                                $restorer_query = mysqli_query($con, "SELECT name, role FROM users WHERE id = {$row['restored_by']}");
                                                $restorer_data = mysqli_fetch_assoc($restorer_query);
                                                $restorer_name = $restorer_data['name'] ?? '';
                                                $restorer_role = $restorer_data['role'] ?? '';
                                            }

                                    ?>
                                            <tr class="<?php echo !empty($row['restored_by']) ? 'restored-row' : ''; ?>">
                                                <td><?php echo $rowNumber++; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between position-relative">
                                                        &#8369; <?php echo number_format($row['amount'], 2); ?>
                                                    <div class="dropdown" data-bs-display="static">
                                                            <button class="three_dots btn btn-light btn-sm ms-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#viewExpenseModal<?php echo $id; ?>">View Details</a></li>
                                                                <li><a class="dropdown-item fw-bold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#archiveExpenseModal<?php echo $id; ?>">Archive</a></li>
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
                                                            $user_query = mysqli_query($con, "SELECT name, role FROM users WHERE id = $user_id");
                                                            if ($user_query && $user_data = mysqli_fetch_assoc($user_query)) {
                                                                $user_name = $user_data['name'];
                                                                $user_role = $user_data['role'];
                                                            }
                                                        }
                                                        ?>
                                                        <button class="btn btn-sm btn-outline-secondary mt-2 toggle-logs-btn" type="button"
                                                            data-target="#logs<?php echo $id; ?>">
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
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="viewExpenseModal<?php echo $id; ?>">Confirm Archive</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                            <form method="POST" action="../forms_logic/archive.php">
                                                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                                                <p>Are you sure you want to archive expense record <strong><?php echo $row['category'] . ' - ' . ucwords($row['description']); ?></strong>
                                                                    from client <strong><?php echo $client_name; ?></strong> under <strong><?php echo $project_name; ?></strong> project? </p>

                                                                <div class="modal-footer" style="padding-bottom: 0; margin-top: 25px;">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="prjct_archive_btn" class="btn btn-danger">Archive</button>
                                                                </div>

                                                            </form>

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
                                            <td style="text-align:center;"><span class="text-muted">—</span></td>
                                            <td style="text-align:center;"><span class="text-muted">—</span></td>
                                            <td style="text-align:center;"><span class="text-muted">—</span></td>
                                            <td style="text-align:center;"><span class="text-muted">—</span></td>
                                            <td style="text-align:center;"><span class="text-muted">—</span></td>
                                        </tr>
                                    <?php
                                    }

                                    mysqli_close($connection);
                                    ?>
                                </tbody>
                            </table>
                        </div>
                           <script>
                document.querySelectorAll('.three_dots').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        const menu = btn.nextElementSibling;
                        menu.classList.toggle('show');
                        e.stopPropagation();
                    });
                });

                document.addEventListener('click', function() {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
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
            </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>

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
                        <i class="bi bi-check-circle-fill text-success" style="font-size:70px;"></i>
                    <?php else: ?>
                        <i class="bi bi-x-circle-fill text-danger" style="font-size:70px;"></i>
                    <?php endif; ?>
                    <h4 class="mt-3"><?php echo $_SESSION['archive_status']; ?></h4>
                    <?php
                    echo ($_SESSION['archive_status_type'] === 'success')
                        ? "The expense has been moved to the archive."
                        : "Something went wrong while archiving the expense.";
                    ?>
                    <div class="modal-footer border-0 d-flex justify-content-center">
                        <button type="button" class="btn btn-<?php echo $_SESSION['archive_status_type']; ?>" data-bs-dismiss="modal">Okay</button>
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

        // document.getElementById('clearBtn').addEventListener('click', function(e) {

        //     document.querySelector('[name="start_period"]').value = '';
        //     document.querySelector('[name="end_period"]').value = '';

        // });

    //     document.addEventListener('DOMContentLoaded', function() {

    //             // Global date range variables from filterByDate()
    // let selectedStartDate = null;
    // let selectedEndDate = null;

    // // Print handler
    // function handlePrint() {
    //     const iframe = document.getElementById('printFrame');
    //     const clientId = '<?= $_GET['client_id'] ?? '' ?>';
    //     const projectId = '<?= $_GET['project_id'] ?? '' ?>';

    //     // Build the URL with optional date filters
    //     let url = `project_details_print.php?client_id=${clientId}&project_id=${projectId}`;

    //     if (selectedStartDate && selectedEndDate) {
    //         url += `&start_period=${selectedStartDate}&end_period=${selectedEndDate}`;
    //     }

    //     // Set iframe src and print on load
    //     iframe.onload = function () {
    //         setTimeout(() => {
    //             iframe.contentWindow.focus();
    //             iframe.contentWindow.print();
    //         }, 300); // slight delay to ensure full rendering
    //     };

    //     iframe.src = url;
    // }

    // // Print button click
    // document.getElementById('print_summary_exp').addEventListener('click', function (e) {
    //     e.preventDefault();
    //     handlePrint();
    // });
            
    //     });
        // const printBtn = document.getElementById('print_summary_exp');

        // printBtn.addEventListener('click', function() {
        //     print();

        // });

        // //This is for downloading PDF

        // jQuery(document).ready(function() {

        //     $('#pdf_summary_exp').click(function() {
        //         //To prevent from flashing the table on the screen
        //         document.body.classList.add("pdf-export-mode");
        //         document.body.style.visibility = "hidden";
        //         document.body.style.opacity = "0";

        //         html2canvas(document.querySelector('#balanceSheetPrint'), {
        //             scale: 3
        //         }).then((canvas) => {
        //             let balanceImage = canvas.toDataURL('image/png');
        //             // console.log(balanceImage);
        //             const {
        //                 jsPDF
        //             } = window.jspdf;
        //             let pdf = new jsPDF('p', 'mm', 'letter');

        //             //To get the size of letter 
        //             const pageWidth = pdf.internal.pageSize.getWidth();
        //             const pageHeight = pdf.internal.pageSize.getHeight();
        //             const margin = 10;

        //             //Stores the height and width of the table (#balanceSheetPrint)
        //             const imgProps = {
        //                 width: canvas.width,
        //                 height: canvas.height
        //             };
        //             //Since the canvas is pixel-based, this is used to convert px to mm
        //             const pxToMm = px => px * 0.264583;

        //             const imgWidthMM = pageWidth - margin * 2; //Sets the actual coverage of table 
        //             const imgHeightMM = pxToMm(canvas.height) * (imgWidthMM / pxToMm(canvas.width));

        //             pdf.addImage(balanceImage, 'PNG', margin, margin, imgWidthMM, imgHeightMM);
        //             pdf.save('project-summary-expenses.pdf');

        //             //Restores the whole content after button is clicked
        //             document.body.style.visibility = "visible";
        //             document.body.style.opacity = "1";
        //             document.body.classList.remove("pdf-export-mode");
        //         });

        //     });
        // });

        // //This is for the CSV File
        // function download_csv(csv, filename) {

        //     const csvfile = new Blob([csv], {
        //         type: "text/csv"
        //     });
        //     const downloadLink = document.createElement('a');

        //     downloadLink.download = filename;
        //     downloadLink.href = window.URL.createObjectURL(csvfile);
        //     downloadLink.style.display = "none";
        //     document.body.appendChild(downloadLink);
        //     downloadLink.click();
        // }


        // function export_table_csv(filename) {
        //     let csv = [];
        //     const rows = document.querySelectorAll("#summary_table tr");

        //     for (let i = 0; i < rows.length; i++) {
        //         let row = [];
        //         const cols = rows[i].querySelectorAll("td, th");

        //         for (let j = 0; j < cols.length; j++) {
        //             let cell = cols[j].innerText.replace(/"/g, '""');
        //             row.push(`"${cell}"`);
        //         }

        //         csv.push(row.join(","));
        //     }
        //     download_csv(csv.join("\n"), filename)
        // }

        // const csvBtn = document.getElementById('csv_summary');
        // csvBtn.addEventListener('click', function() {
        //     export_table_csv("project-summary.csv");

        // })

        // //This is for Excel file
        // document.getElementById('xls_summary').addEventListener('click', function() {

        //     const table2excel = new Table2Excel();
        //     table2excel.export(document.querySelectorAll("#summary_table"), "Project-Summary");
        // })
    </script>


</body>

</html>