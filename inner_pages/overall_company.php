<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

// For quote (username, email, and date generated)
$username = $_SESSION['auth_user']['name'] ?? 'Unknown User';
$email = $_SESSION['auth_user']['email'] ?? 'No Email';
date_default_timezone_set('Asia/Manila');
$generated_at = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "RVR SMES"; ?>
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
            $page_title = 'RVR SMES';
            include '../layout/topnav.php';
            ?>


            <div class="container-fluid px-0 pe-3">
            <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="fs-link active" href="overall_company.php">Financial Summary</a>
                    </li>
                    <li class="nav-item">
                        <a class="fs-link" href="income_stmntoverall.php">Financial Statement</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>
                <!-- Table for Project Expense Archives -->
                <section id="project_expense_archives" class="account-section">
                    <div class="card-body">
                        <div class="d-flex align-items-center mt-3 mb-4">
                            <h4 style="font-weight: bold;">Client Project Financial Summary</h4>
                            <!-- <button type="button" id="xls_summary" class="btn btn-outline-success ms-auto">
                        <i class="bi bi-filetype-xls"></i> Download Excel
                        </button>
                        <button type="button" id="csv_summary" class="btn btn-outline-success ms-2">
                        <i class="bi bi-filetype-csv"></i> Download CSV
                        </button> -->
                            <button type="button" id="pdf_overall_summary" class="btn btn-outline-orange ms-auto">
                                <i class="bi bi-filetype-pdf"></i> Download PDF
                            </button>
                            <button type="button" id="print_overall_summary" class="btn btn-outline-orange ms-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                        <div class="card-body px-0">
                            <script type="text/javascript">
                                let selectedStartDate = null;
                                let selectedEndDate = null;

                                $(document).ready(function() {
                                    console.log($("#overall_company thead th").length); 
                                $("#overall_company tbody tr").each(function(i, row) {
                                console.log("Row " + i + " cells:", $(row).find("td").length);
                                });
                                    var table = $('#overall_company').DataTable({
                                        responsive: true,
                                        dom: 'Blfrtip',
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
                                        },
                                        columnDefs: [
                                            { targets: "_all", defaultContent: "-" }
                                        ]

                                    });

                                    // to hide repeating client names
                                    function hideRepeatingClientNames() {
                                        let prevClient = '';
                                        table.rows({
                                            filter: 'applied',
                                            order: 'applied'
                                        }).every(function() {
                                            const $clientCell = $(this.node()).find('td.client-name');
                                            const clientText = $clientCell.data('client')?.trim();
                                            if (clientText) {
                                                $clientCell.text(clientText === prevClient ? '' : clientText);
                                                prevClient = clientText;
                                            }
                                        });
                                    }

                                    // To custom calendar for custom date range
                                    flatpickr("#customDateRangeProject", {
                                        mode: "range",
                                        dateFormat: "Y-m-d",
                                        onClose: function(selectedDates, dateStr) {
                                            filterByDate('custom', selectedDates);
                                        }
                                    });
                                    // If "Custom" option is selected
                                    $('#dateFilterProject').on('change', function() {
                                        const value = $(this).val();
                                        if (value === 'custom') {
                                            $('#customDateRangeProject').show();
                                        } else {
                                            $('#customDateRangeProject').hide();
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
                                            hideRepeatingClientNames();
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
                                            const dateStr = data[7]; // Get the date from the 5th column (0-based index)
                                            if (!dateStr) return false; //To exclude rows with no dates
                                            const rowDate = new Date(dateStr);
                                            if (isNaN(rowDate)) return false; // exclude invalid dates

                                            
                                            rowDate.setHours(0, 0, 0, 0);
                                            const start = new Date(startDate);
                                            const end = new Date(endDate);
                                            start.setHours(0, 0, 0, 0);
                                            end.setHours(0, 0, 0, 0);

                                            return rowDate >= start && rowDate <= end;
                                        });

                                        table.draw();
                                        $.fn.dataTable.ext.search.pop();
                                        hideRepeatingClientNames();
                                        if (table.rows({
                                                filter: 'applied'
                                            }).count() === 0) {
                                            noDataModal.show();
                                        }
                                    }
                                    $(document).on('click', '#noDataModal [data-bs-dismiss="modal"]', function() {
                                        noDataModal.hide();
                                    });


                                    // Wrap DataTables controls in a flex container
                                    var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');
                                    toolbar.append($('#overall_company_wrapper .dataTables_length'));
                                    toolbar.append($('#dateRangeContainer'));
                                    toolbar.append($('#overall_company_wrapper .dataTables_filter'));

                                    // Insert the toolbar container after the table wrapper
                                    $('#overall_company_wrapper').before(toolbar);
                                    hideRepeatingClientNames();
                                    table.on('draw', function() {
                                        hideRepeatingClientNames(); // Reapply after filter/sort
                                    });

                                    function handlePrint() {
                                        const iframe = document.getElementById('printFrame');

                                        let url = `client_proj_sum_print.php`;
                                        if (selectedStartDate && selectedEndDate) {
                                            url += `?start_period=${selectedStartDate}&end_period=${selectedEndDate}`;
                                        }

                                        iframe.onload = function() {
                                            setTimeout(() => {
                                                iframe.contentWindow.focus();
                                                iframe.contentWindow.print();
                                            }, 300);
                                        };

                                        iframe.src = url;
                                    }

                                    document.getElementById('print_overall_summary').addEventListener('click', function(e) {
                                        e.preventDefault();
                                        handlePrint();
                                    });

                                    function handlePdfDownload() {
                                        const iframe = document.getElementById('printFrame');

                                        iframe.onload = function() {
                                            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                            const container = iframeDoc.querySelector('#download-container');

                                            if (!container) {
                                                alert("Download container not found!");
                                                return;
                                            }
                                            
                                             const clonedContent = container.cloneNode(true);
                                             const styleTags = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                                                    styleTags.forEach(style => {
                                                        clonedContent.appendChild(style.cloneNode(true));
                                                    });

                                            setTimeout(() => {
                                                html2pdf()
                                                    .set({
                                                        filename: 'ClientProjectSummary.pdf',
                                                        // margin: [15, 10, 10, 10],
                                                        margin: [0.3, 0.2, 0.5, 0.5],
                                                        image: {
                                                            type: 'jpeg',
                                                            quality: 0.98
                                                        },
                                                        html2canvas: {
                                                            scale: 2,
                                                            useCORS: true,
                                                            logging: false,
                                                            scrollX: 0,
                                                            scrollY: 0
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
                                                            pdf.setFontSize(10);
                                                            pdf.setTextColor(85, 85, 85);

                                                            pdf.text(
                                                                "Generated by: <?php echo addslashes($username); ?> (<?php echo addslashes($email); ?>)  |  Date & Time: <?php echo $generated_at; ?>",
                                                                pageWidth / 2,
                                                                pageHeight - 0.3, 
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

                                        let url = `client_proj_sum_print.php`;
                                        if (selectedStartDate && selectedEndDate) {
                                            url += `?start_period=${selectedStartDate}&end_period=${selectedEndDate}`;
                                        }

                                        iframe.src = url;
                                    }

                                    document.getElementById('pdf_overall_summary').addEventListener('click', function(e) {
                                        e.preventDefault();
                                        handlePdfDownload();
                                    });
                                });
                            </script>

                            <div class="table-container px-0">
                                <div id="dateRangeWrapper">
                                    <div class="d-flex align-items-center gap-2 mb-0 flex-shrink-0" id="dateRangeContainer">
                                        <label for="dateFilter" style="font-size: 14px;">Filter Date by:</label>
                                        <select id="dateFilterProject" class="form-select form-select-sm w-auto" style="height: 30px;">
                                            <option value="all">All</option>
                                            <option value="weekly">This Week</option>
                                            <option value="monthly">This Month</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                        <input type="text" id="customDateRangeProject" class="form-control form-control-sm datepicker w-auto"
                                            placeholder="Select date range" style="display: none; height: 30px;" />
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table border border-secondary-subtle" id="overall_company" style="overflow: hidden;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Client</th>
                                                <th>Project</th>
                                                <th>Project Value</th>
                                                <th>Project Expenses</th>
                                                <th>Project Profit</th>
                                                <th>Status</th>
                                                <th class="d-none">Date</th>
                                            </tr>
                                        </thead>

                                        <?php

                                        // $con = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

                                        // For fetching client and project name
                                        $clients_projects = [];
                                        $clients_query = mysqli_query($conn, "SELECT client_id, client_name FROM clients ORDER BY client_name ASC");
                                        while ($client = mysqli_fetch_assoc($clients_query)) {
                                            $client_id = $client['client_id'];
                                            $client_name = $client['client_name'];

                                            $projects = [];

                                            $projects_query = mysqli_query($conn, "SELECT * FROM projects WHERE client_id = $client_id ORDER BY project_name ASC");

                                            while ($project = mysqli_fetch_assoc($projects_query)) {
                                                $project_id = $project['project_id'];
                                                $project_name = $project['project_name'];
                                                $project_status = $project['project_status'];
                                                $end_date = $project['end_date'];

                                                // Start with main project income
                                                $raw_project_income = $project['projected_budget_cost'] ?? 0;
                                                $clean_project_income = (float) str_replace(',', '', $raw_project_income);

                                                // Fetch addons for this project
                                                $addons_query = mysqli_query($conn, "SELECT * FROM project_addons WHERE project_id = '$project_id' ORDER BY addon_name ASC");
                                                $addons = [];
                                                while ($addon = mysqli_fetch_assoc($addons_query)) {
                                                    $addon_income = (float) str_replace(',', '', $addon['projected_budget_cost'] ?? 0);

                                                    // Add addon income to project total income
                                                    $clean_project_income += $addon_income;

                                                    $addons[] = [
                                                        'name' => $addon['addon_name'],
                                                        'income' => $addon_income,
                                                        // optionally calculate cost/profit for addons
                                                    ];
                                                }

                                                // Calculate project cost and profit as usual
                                                $project_cost_query = mysqli_query($conn, "SELECT SUM(amount) AS total_project_cost FROM expenses WHERE project_id = '$project_id'");
                                                $project_cost_row = mysqli_fetch_assoc($project_cost_query);
                                                $clean_project_cost = (float) str_replace(',', '', $project_cost_row['total_project_cost'] ?? 0);

                                                $project_profit_taxless = $clean_project_income - $clean_project_cost;
                                                $first_revenue = $clean_project_income / 1.12;
                                                $second_revenue = $first_revenue * 0.12;
                                                $tax_expense = $second_revenue;
                                                $clean_project_profit = $project_profit_taxless - $tax_expense;

                                                $projects[] = [
                                                    'name' => $project_name,
                                                    'income' => $clean_project_income,  // now includes addon income
                                                    'cost' => $clean_project_cost,
                                                    'profit' => $clean_project_profit,
                                                    'status' => $project_status,
                                                    'end_date' => $end_date,
                                                    'addons' => $addons
                                                ];
                                            }


                                            $clients_projects[] = [
                                                'client_name' => $client_name,
                                                'projects' => $projects
                                            ];
                                        }

                                        ?>
                                        <tbody>
                                            <?php
                                            $client_number = 1;

                                            if (empty($clients_projects)): ?>
                                               
                                                     <tr>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td class="d-none">--</td>
                                                        </tr>
                                                
                                                <?php else:
                                                foreach ($clients_projects as $client):
                                                    $projectCount = count($client['projects']);
                                                    if ($projectCount === 0): ?>
                                                        <tr>
                                                            <td><?php echo $client_number++ ?></td>
                                                            <td class="client-cell"><?php echo htmlspecialchars($client['client_name']) ?></td>
                                                            <td class="text-center text-muted">No projects found</td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td><span class="text-muted">—</span></td>
                                                            <td class="d-none">--</td>
                                                        </tr>
                                                        <?php
                                                    else:
                                                        $first = true;
                                                        foreach ($client['projects'] as $index => $project):
                                                            $isLastRow = ($index === $projectCount - 1);
                                                        ?>

                                                            <tr class="<?php echo $isLastRow ? 'client-end' : '' ?>">
                                                                <td class="client-cell" data-client="<?= $first ? $client_number : '' ?>">
                                                                    <?php echo $first ? $client_number++ : '' ?>
                                                                </td>
                                                                <td class="client-cell client-name" data-client="<?= htmlspecialchars($client['client_name']) ?>">
                                                                    <?php echo $first ? htmlspecialchars($client['client_name']) : '' ?>
                                                                </td>
                                                                <td>
                                                                    <?php 
                                                                        if (!empty($project['name'])) {
                                                                            $project_display_name = htmlspecialchars($project['name']);
                                                                            if (!empty($project['addons'])) {
                                                                                $addon_names = array_map(fn($a) => $a['name'], $project['addons']);
                                                                                $project_display_name .= ' (With Addons: ' . implode(', ', $addon_names) . ')';
                                                                            }
                                                                            echo $project_display_name;
                                                                        } else {
                                                                            echo '—'; // placeholder if no project yet
                                                                        }
                                                                    ?>
                                                                </td>
                                                                <td>&#8369; <?php echo !empty($project['income']) ? number_format($project['income'], 2) : '0.00'; ?></td>
                                                                <td>&#8369; <?php echo !empty($project['cost']) ? number_format($project['cost'], 2) : '0.00'; ?></td>
                                                                <td>
                                                                    <?php if (strtolower($project['status']) === 'completed'): ?>
                                                                        &#8369; <?php echo !empty($project['profit']) ? number_format($project['profit'], 2) : '0.00'; ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">—</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <!-- Condition for the color code of status -->
                                                                <?php 
                                                                    if (!empty($project['status'])){
                                                                        $status = htmlspecialchars($project['status']);
                                                                        $badgeClass = '';
                                                                    
                                                                    if ($status === 'Ongoing') {
                                                                        $badgeClass = 'badge-ongoing';
                                                                    } elseif ($status === 'Upcoming') {
                                                                        $badgeClass = 'badge-upcoming';
                                                                    } elseif ($status === 'Completed') {
                                                                        $badgeClass = 'badge-completed';
                                                                    } elseif ($status === 'Overdue') {
                                                                        $badgeClass = 'badge-overdue';
                                                                    } else {
                                                                        $badgeClass = 'badge-default';
                                                                    }

                                                                    echo '<span class="badge ' . $badgeClass . '">' . $status . '</span>';
                                                                } else {
                                                                    echo '—';
                                                                } ?>
                                                            </td>
                                                                
                                                                <td class="d-none"><?php echo !empty($project['end_date']) ? $project['end_date'] : '—'; ?></td>
                                                            </tr>
                                            <?php
                                                            $first = false;
                                                        endforeach;
                                                    endif;
                                                endforeach;
                                            endif;
                                            ?>
                                        </tbody>

                                    </table>
                                </div>
                            </div>

                            <script type="text/javascript">
                                let selectStartDate = null;
                                let selectEndDate = null;

                                $(document).ready(function() {
                                    var table = $('#outside_project').DataTable({
                                        responsive: true,
                                        dom: 'Blfrtip',
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

                                    function hideRepeatingCategoryNames() {
                                        const categoryTotals = {};
                                        const categoryFirstRow = {};
                                        let prevCategory = '';

                                        // First pass: clear old labels, collect totals and first row per category
                                        table.rows({
                                            filter: 'applied',
                                            order: 'applied'
                                        }).every(function() {
                                            const $row = $(this.node());
                                            const category = $row.find('td.category-name').data('category')?.trim();
                                            const amount = parseFloat($row.find('td.amount-cell').data('amount')) || 0;

                                           
                                            $row.removeClass('category-break');

                                            if (!category) return;

                                            if (!categoryTotals[category]) {
                                                categoryTotals[category] = 0;
                                                categoryFirstRow[category] = $row;
                                            }

                                            categoryTotals[category] += amount;

                                            // Clear content visually, but preserve cell structure for borders
                                            $row.find('td.category-name').html('&nbsp;');
                                            $row.find('td.fw-bold').html('&nbsp;');
                                        });

                                        // Second pass: apply category name, total, and add border to prior row
                                        let lastCategory = '';
                                        let lastRow = null;

                                        table.rows({
                                            filter: 'applied',
                                            order: 'applied'
                                        }).every(function() {
                                            const $row = $(this.node());
                                            const category = $row.find('td.category-name').data('category')?.trim();

                                            if (category && category !== lastCategory) {
                                                // Add content back for the first visible row of the category
                                                $row.find('td.category-name').text(category);
                                                $row.find('td.fw-bold').text(
                                                    categoryTotals[category].toLocaleString(undefined, {
                                                        minimumFractionDigits: 2
                                                    })
                                                );

                                                // Add .category-break to the previous row if it exists
                                                if (lastRow) {
                                                    $(lastRow).addClass('category-break');
                                                }

                                                lastCategory = category;
                                            }

                                            lastRow = $row;
                                        });
                                    }

                                    // To custom calendar for custom date range
                                    flatpickr("#customDateRange", {
                                        mode: "range",
                                        dateFormat: "Y-m-d",
                                        onClose: function(selectedDates, dateStr) {
                                            filterByDate('custom', selectedDates);
                                        }
                                    });
                                    // If "Custom" option is selected
                                    $('#dateFilter').on('change', function() {
                                        const value = $(this).val();
                                        if (value === 'custom') {
                                            $('#customDateRange').show();
                                        } else {
                                            $('#customDateRange').hide();
                                            selectStartDate = null;
                                            selectEndDate = null;
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
                                            hideRepeatingCategoryNames();
                                            return;
                                        }

                                        selectStartDate = startDate.getFullYear() + '-' +
                                            String(startDate.getMonth() + 1).padStart(2, '0') + '-' +
                                            String(startDate.getDate()).padStart(2, '0');

                                        selectEndDate = endDate.getFullYear() + '-' +
                                            String(endDate.getMonth() + 1).padStart(2, '0') + '-' +
                                            String(endDate.getDate()).padStart(2, '0');

                                        $.fn.dataTable.ext.search.push(function(settings, data) {
                                            const dateStr = data[5]; // Get the date from the 6th column (0-based index)
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
                                        hideRepeatingCategoryNames();
                                        if (table.rows({
                                                filter: 'applied'
                                            }).count() === 0) {
                                            noDataModal.show();
                                        }
                                    }


                                    // Wrap DataTables controls in a flex container
                                    var toolbar = $('<div id="toolbar" class="d-flex align-items-center justify-content-between"></div>');
                                    toolbar.append($('#outside_project_wrapper .dataTables_length'));
                                    toolbar.append($('#dateRangeFilter'));
                                    toolbar.append($('#outside_project_wrapper .dataTables_filter'));

                                    // Insert the toolbar container after the table wrapper
                                    $('#outside_project_wrapper').prepend(toolbar);
                                    hideRepeatingCategoryNames();
                                    table.on('draw', function() {
                                        hideRepeatingCategoryNames();
                                    });

                                    function handlePrint() {
                                        const iframe = document.getElementById('printFrame');

                                        let url = `non_project_sum_print.php`;
                                        if (selectStartDate && selectEndDate) {
                                            url += `?start_period=${selectStartDate}&end_period=${selectEndDate}`;
                                        }

                                        iframe.onload = function() {
                                            setTimeout(() => {
                                                iframe.contentWindow.focus();
                                                iframe.contentWindow.print();
                                            }, 300);
                                        };

                                        iframe.src = url;
                                    }

                                    document.getElementById('print_company_summary').addEventListener('click', function(e) {
                                        e.preventDefault();
                                        handlePrint();
                                    });

                                    function handlePdfDownload() {
                                        const iframe = document.getElementById('printFrame');

                                        iframe.onload = function() {
                                            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                            const container = iframeDoc.querySelector('#download-container');

                                            if (!container) {
                                                alert("Download container not found!");
                                                return;
                                            }
                                            
                                            // Clone content & styles
                                            const clonedContent = container.cloneNode(true);
                                            const styleTags = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                                                styleTags.forEach(style => {
                                                    clonedContent.appendChild(style.cloneNode(true));
                                                });

                                            setTimeout(() => {
                                                html2pdf()
                                                    .set({
                                                        filename: 'NonProjectSummary.pdf',
                                                        margin: [0.3, 0.2, 0.5, 0.5],
                                                        image: {
                                                            type: 'jpeg',
                                                            quality: 0.98
                                                        },
                                                        html2canvas: {
                                                            scale: 2,
                                                            useCORS: true,
                                                            logging: false,
                                                            scrollX: 0,
                                                            scrollY: 0
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
                                                            pdf.setFontSize(10);
                                                            pdf.setTextColor(85, 85, 85);

                                                            pdf.text(
                                                                "Generated by: <?php echo addslashes($username); ?> (<?php echo addslashes($email); ?>)  |  Date & Time: <?php echo $generated_at; ?>",
                                                                pageWidth / 2,
                                                                pageHeight - 0.3, 
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

                                        let url = `non_project_sum_print.php`;
                                        if (selectStartDate && selectEndDate) {
                                            url += `?start_period=${selectStartDate}&end_period=${selectEndDate}`;
                                        }

                                        iframe.src = url;
                                    }

                                    document.getElementById('pdf_company_summary').addEventListener('click', function(e) {
                                        e.preventDefault();
                                        handlePdfDownload();
                                    });
                                });
                            </script>


                            <!-- Table for company expenses outside project -->

                            <div class="table-container2 px-0">
                                <div class="d-flex align-items-center mt-2 mb-4">
                                    <h4 style="font-weight: bold;">Non-Project Financial Summary</h4>
                                    <!-- <button type="button" id="xls_summary" class="btn btn-outline-success ms-auto">
                                    <i class="bi bi-filetype-xls"></i> Download Excel
                                    </button>
                                    <button type="button" id="csv_summary" class="btn btn-outline-success ms-2">
                                    <i class="bi bi-filetype-csv"></i> Download CSV
                                    </button> -->
                                    <button type="button" id="pdf_company_summary" class="btn btn-outline-orange ms-auto">
                                        <i class="bi bi-filetype-pdf"></i> Download PDF
                                    </button>
                                    <button type="button" id="print_company_summary" class="btn btn-outline-orange ms-2">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-0" id="dateRangeFilter">
                                    <label for="dateFilter" style="font-size: 14px;">Filter Date by:</label>
                                    <select id="dateFilter" class="form-select form-select-sm w-auto" style="height: 30px;">
                                        <option value="all">All</option>
                                        <option value="weekly">This Week</option>
                                        <option value="monthly">This Month</option>
                                        <option value="custom">Custom Range</option>
                                    </select>

                                    <input type="text" id="customDateRange" class="form-control form-control-sm datepicker w-auto" 
                                        placeholder="Select date range" style="display: none; height: 30px;" />
                                </div>
                                <div class="table-responsive">
                                    <table class="table border border-secondary-subtle" id="outside_project" style="overflow: hidden;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Expense Category</th>
                                                <th>Expense Item/Purpose</th>
                                                <th>Amount</th>
                                                <th>Totals</th>
                                                <th class="d-none">Date</th>
                                            </tr>
                                        </thead>
                                        <?php

                                        // $con = mysqli_connect("localhost", "root", "", "financial_management");

                                        $company_expenses = [];
                                        $category_totals = [];

                                        $company_expenses_query = mysqli_query($conn, "SELECT * FROM company_expense ORDER BY category, description ASC");
                                        while ($row = mysqli_fetch_assoc($company_expenses_query)) {
                                            $category = $row['category'];
                                            $item = $row['description'];
                                            $amount = (float) str_replace(',', '', $row['amount']);
                                            $date = $row['date'];

                                            // Group by category and item
                                            if (!isset($company_expenses[$category])) {
                                                $company_expenses[$category] = [];
                                                $category_totals[$category] = 0;
                                            }

                                            if (!isset($company_expenses[$category][$item])) {
                                                $company_expenses[$category][$item] = [
                                                    'amount' => 0,
                                                    'date' => $date
                                                ];
                                            }

                                            $company_expenses[$category][$item]['amount'] += $amount;
                                            $category_totals[$category] += $amount;
                                        };

                                        ?>
                                        <tbody>
                                            <?php
                                            $category_number = 1;

                                            if (empty($company_expenses)): ?>
                                                <tr>
                                                    <td><span class="text-muted">#</span></td>
                                                    <td><span class="text-muted">—</span></td>
                                                    <td><span class="text-muted">—</span></td>
                                                    <td><span class="text-muted">—</span></td>
                                                    <td><span class="text-muted">—</span></td>
                                                    <td class="d-none"><span class="text-muted">—</span></td>
                                                </tr>
                                                <?php else:
                                                foreach ($company_expenses as $category => $items):
                                                    $itemKeys = array_keys($items); // get all keys/expense item names)
                                                    $itemCount = count($itemKeys);
                                                    $first = true;
                                                    foreach ($items as $item => $total):
                                                        $isLastRow = ($item === end($itemKeys)); //To check if this is the last key/item
                                                ?>
                                                        <tr class="<?= $isLastRow ? 'category-end' : '' ?>">
                                                            <td class="category-cell " data-category="<?= $first ? $category_number : '' ?>">
                                                                <?= $first ? $category_number++ : '' ?>
                                                            </td>
                                                            <td class="category-cell category-name" data-category="<?= htmlspecialchars($category) ?>">
                                                                <?= $first ? htmlspecialchars($category) : '' ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($item) ?></td>
                                                            <td class="amount-cell" data-amount="<?= $total['amount'] ?>">
                                                                &#8369; <?= number_format($total['amount'], 2) ?>
                                                            </td>
                                                            <td class="category-cell fw-bold"><?= $first ? number_format($category_totals[$category], 2) : '' ?></td>
                                                            <td class="d-none"> <?= $total['date'] ?> </td>
                                                        </tr>
                                            <?php
                                                        $first = false;
                                                    endforeach;
                                                endforeach;
                                            endif;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
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
        </script>

</body>

</html>