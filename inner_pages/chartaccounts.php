<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Chart of Accounts"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/chartAccounts.css">
    <script src="chartAccounts.js"></script>
</head>

<body>

    <div class="d-flex">
        <!-- Side Nav Container -->
        <?php include('../layout/sidenav.php'); ?>

        <!-- Main Content -->
        <div class="main-content container-fluid ">
            
            
               <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Log Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <a href="<?php echo $site_base_url; ?>forms_logic/logout.php" class="btn btn-danger rounded-pill px-3">Log Out</a>
                </div>
            </div>
        </div>
    </div>

            <!-- Top Nav -->
            <?php
            $page_title = 'Chart of Accounts';
            include '../layout/topnav.php';
            ?>
        
            <div class="container-fluid px-0 pe-3">
             <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link active section-link" href="#project_expense_accounts">Project Expense Accounts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link section-link" href="#company_expense_accounts">Company Expense Accounts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link section-link" href="#payment_accounts">Payment Transaction Accounts</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>
                <!-- Modal notif for success or duplicating message -->
                <?php if (!empty($_SESSION['title_status'])): ?>
                    <div class="modal fade" id="statusModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-center p-3">
                                <?php
                                $status = strtolower($_SESSION['title_status']); 
                                $icon = "bi-info-circle-fill text-secondary"; 
                                $btnClass = "secondary";

                                if ($status === "success" || $status === "account title successfully added") {
                                    $icon = "bi-check-circle-fill text-success";
                                    $btnClass = "success";
                                } elseif ($status === "warning") {
                                    $icon = "bi-exclamation-triangle-fill text-warning";
                                    $btnClass = "warning";
                                } elseif ($status === "error") {
                                    $icon = "bi-x-circle-fill text-danger";
                                    $btnClass = "danger";
                                }
                                ?>

                                <i class="bi <?php echo $icon; ?>" style="font-size:70px;"></i>
                                <h4 class="mt-2">
                                    <?php echo htmlspecialchars($_SESSION['title_status']); ?>
                                </h4>
                                <p class="mt-2" style="font-size: 15px;">
                                    <?php echo $_SESSION['title_message']; ?>
                                </p>
                                <div class="modal-footer border-0 d-flex justify-content-center">
                                    <button type="button" class="btn btn-<?php echo $btnClass; ?>" data-bs-dismiss="modal">Okay</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auto-show the modal -->
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            new bootstrap.Modal(document.getElementById('statusModal')).show();
                        });
                    </script>

                    <?php unset($_SESSION['title_status'], $_SESSION['title_message']); ?>
            <?php endif; ?>

                <!-- Add Category Modal -->
                <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">New Account Title</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="client-form" action="chartAccounts_logic.php" method="POST">
                                    <div class="mb-2">
                                        <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Number</label>
                                        <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                            <i>Auto-fills once an Account Type is selected.</i>
                                        </span>
                                        <input type="text" class="form-control" name="accountNum" id="account_num" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Title</label>
                                        <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                            <i>This will appear as an account title or category option in transaction forms.</i>
                                        </span>
                                        <input type="text" class="form-control" name="accountDes" id="account_des" placeholder="Ex. Salary Expense, Construction Supply Expense" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="accountType" class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Type</label>
                                        <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                            <i>Select how this account is classified.</i>
                                        </span>
                                        <select name="account_type" id="account_type" class="form-select" required>
                                            <option value="" disabled selected hidden>Select Account Type</option>
                                            <option value="Asset">Asset</option>
                                            <option value="Liability">Liability</option>
                                            <option value="Equity">Equity</option>
                                            <option value="Revenue">Revenue</option>
                                            <option value="Expense">Expense</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="form_usage" class="form-label mb-0 fw-bold" style="font-size: 18px;">Form Assignment</label>
                                        <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                            <i>Select transaction form to which this category/account title will be used.</i>
                                        </span>
                                        <select name="form_usage" class="form-select" required>
                                            <option value="" disabled selected hidden>Select Form</option>
                                            <option value="Project Expense">Project Expense</option>
                                            <option value="Company Expense">Company Expense</option>
                                            <option value="Payment Transaction">Payment Transaction</option>
                                        </select>
                                    </div>
                                    <!-- <div class="mb-3">
                                        <label for="statement-field" class="form-label">Statement</label>
                                        <input type="text" class="form-control" name="statement_field" id="statement_field" readonly>
                                    </div> -->
                                    <div class="text-end">
                                        <button name="save_accountTitle" class="btn btn-primary ">Save Account Title</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- to prevent users from typing numbers -->
                <script>
                    document.getElementById("account_des").addEventListener("keypress", function(e) {
                        // Block digits 0-9
                        if (/[0-9]/.test(e.key)) {
                            e.preventDefault();
                        }
                    });
                </script>
                <?php if (isset($_GET['open_add_category']) && $_GET['open_add_category'] == 1): ?>
                    <script>
                        window.addEventListener('load', function() {
                            var addCategoryModal = new bootstrap.Modal(document.getElementById('addClientModal'), {
                                keyboard: false
                            });
                            addCategoryModal.show();
                        });
                    </script>
                <?php endif; ?>


             
                <section id="project_expense_accounts" class="account-section">
                    <!-- Table for Project Expense Account Titles -->
                    <div class="card-body">   
                         <div class="d-flex align-items-center justify-content-between mt-3 mb-4" >
                            <h4 style="font-weight: bold;" class=" mb-0">Project Expenses Accounts List</h4>
                        </div>    
                        <!-- DATA TABLE FOR PROJECT EXPENSE ACCOUNT TITLES -->
                    <script>
                        $(document).ready(function() {
                            // Project Expense Accounts Table
                            $('#project_expense_titles').DataTable({
                                responsive: true,
                                dom: 'lfrtip',
                                buttons: [],
                                ordering: false,
                                language: {
                                    search: '',
                                    searchPlaceholder: "Search account title...",
                                    paginate: {
                                        previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                        next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                    }
                                }
                            });
                            // Wrap toolbar and add margin (this is for above the table)
                            var toolbar = $('<div id="toolbarProj" class="d-flex align-items-center justify-content-between"></div>');
                            toolbar.append($('#project_expense_titles_wrapper .dataTables_length'));
                            
                            var rightControls = $('<div class="d-flex align-items-center gap-2"></div>');
                            rightControls.append($('#project_expense_titles_wrapper .dataTables_filter'));
                            rightControls.append(`
                                <button type="button" class="btn add-account-title-btn shadow-sm" 
                                        data-bs-toggle="modal" data-bs-target="#addClientModal">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Add Account Title
                                </button>
                            `);

                            toolbar.append(rightControls);
                            $('#project_expense_titles_wrapper').prepend(toolbar);
                              $('#toolbarProj').css({
                                        marginBottom: '20px',
                                        paddingBottom: '15px',
                                        borderBottom: '#d1d0d0 solid 1px'
                                    });
                        })
                    </script>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="project_expense_titles">
                                <thead>
                                    <tr>
                                        <th scope="col" class="num_head">#</th>
                                        <th scope="col" class="account_num_head">Account Number</th>
                                        <th scope="col" class="three_head">Account Title</th>
                                        <th scope="col" class="three_head">Account Type</th>
                                        <th scope="col" class="three_head">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php

                                    // $con = mysqli_connect("localhost", "root", "", "financial_management");
                                    $fetch_query = "SELECT * FROM chart_accounts WHERE form_usage = 'Project Expense' ORDER BY account_num ASC";
                                    $fetch_query_run = mysqli_query($conn, $fetch_query);

                                    $row_num = 1;
                                    if (mysqli_num_rows($fetch_query_run) > 0) {
                                        while ($row = mysqli_fetch_array($fetch_query_run)) {
                                            $category_id = $row['category_id'];
                                            $account_num = $row['account_num'];
                                            $category = $row['category'];
                                            $account_type = $row['account_type'];
                                            $form_usage = $row['form_usage'];
                                            $statement_field = $row['statement_field'];

                                    ?>
                                            <tr>
                                                <td style="text-align: center; padding-left: 0;"><?php echo $row_num++; ?></td>
                                                <td style="text-align: center; padding-left: 0;"><?php echo $row['account_num']; ?></td>
                                                <td><?php echo $row['category']; ?></td>
                                                <td><?php echo $row['account_type']; ?></td>
                                                <td class="d-flex justify-content-center align-items-center gap-3">
                                                    <a href="#" class="btn btn-view btn-sm" data-bs-toggle="modal" data-bs-target="#viewChartAccount<?php echo $category_id; ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 18">
                                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                                        </svg>
                                                        View
                                                    </a>
                                                     <a href="#" class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#editChartAccounts<?php echo $category_id; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                        Edit
                                                    </a>
                                                </td>
                                            </tr>
                                             <!-- This is for fetching and displaying total amounts for each category -->
                                             <?php
                                             $total_amount = 0;
                                             $recent_ten = [];
                                             
                                            //  fetch total from expenses table and display 10 recent entries
                                            $query_expenses = mysqli_query($conn, "SELECT amount, date, description, client_id, project_id, category, category_num
                                                                                FROM expenses WHERE category = '$category' AND category_num = '$account_num'
                                                                                ORDER BY date DESC LIMIT 10");
                                            $total_expenses = mysqli_query($conn, "SELECT SUM(amount) AS total FROM expenses WHERE category = '$category' AND category_num = '$account_num'");
                                            if ($total_expenses && mysqli_num_rows($total_expenses) > 0) {
                                                $total_expenses_row = mysqli_fetch_assoc($total_expenses);
                                                $total_amount += $total_expenses_row['total'] ?? 0;
                                            }

                                            if ($query_expenses && mysqli_num_rows($query_expenses) > 0){
                                                while ($exp = mysqli_fetch_assoc($query_expenses)) {
                                                    $client_name = '';
                                                    $project_name = '';

                                                    // To fetch client name
                                                    if (!empty($exp['client_id'])){
                                                        $client_id = $exp['client_id'];
                                                        $client_name_query = mysqli_query($conn, "SELECT client_name FROM clients WHERE client_id = '$client_id' LIMIT 1");
                                                        if ($client_name_query && mysqli_num_rows($client_name_query) > 0){
                                                            $client_data = mysqli_fetch_assoc($client_name_query);
                                                            $client_name = $client_data['client_name'];
                                                        }
                                                    }

                                                    // to fetch project name
                                                    if (!empty($exp['project_id'])){
                                                        $project_id = $exp['project_id'];
                                                        $project_name_query = mysqli_query($conn, "SELECT project_name FROM projects WHERE project_id = '$project_id' LIMIT 1");
                                                        if ($project_name_query && mysqli_num_rows($project_name_query) > 0) {
                                                            $project_data = mysqli_fetch_assoc($project_name_query);
                                                            $project_name = $project_data['project_name'];
                                                        }
                                                    }
                                                     // Combine names
                                                    $client_project_display = '';
                                                    if ($client_name && $project_name) {
                                                        $client_project_display = "$client_name - $project_name";
                                                    } elseif ($client_name) {
                                                        $client_project_display = $client_name;
                                                    } elseif ($project_name) {
                                                        $client_project_display = $project_name;
                                                    } else {
                                                        $client_project_display = '—';
                                                    }

                                                    // Store formatted data
                                                    $recent_ten[] = [
                                                        'source' => 'Project Expense',
                                                        'description' => $exp['description'],
                                                        'amount' => $exp['amount'],
                                                        'date' => $exp['date'],
                                                        'client_project' => $client_project_display
                                                    ];
                                                }
                                            }
                                            
                                             ?>
                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewChartAccount<?php echo $category_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-md">
                                                    <div class="modal-content">
                                                        <div class="modal-header" style="padding: 12px;">
                                                            <h5 class="modal-title" id="viewChartAccountModal<?php echo $category_id; ?>">Expense Details</h5>
                                                            <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                            <!-- Right Column -->
                                                            <p style="font-size: 16px;"><?php echo $row['account_num'] . ' - ' . ucwords($row['category']); ?><br>
                                                                <span style="font-size: 12px; color: #555;"><i>Account Number & Account Title/Category</i></span><br>
                                                            </p>
                                                            <p style="font-size: 16px;"><?php echo ucwords($row['account_type']) . ' - ' . ucwords($row['form_usage']); ?><br>
                                                                <span style="font-size: 12px; color: #555;"><i>Account Type & Form Usage</i></span>
                                                            </p>
                                                            <div class="highlight-box">
                                                                <p>This account title will appear in the category dropdown when recording a transaction.
                                                                    It will specifically be available in the <strong><?php echo  ucwords($row['form_usage']); ?> Form</strong> under the transaction tab.
                                                                </p>
                                                                <h6 style="font-size: 15px; font-weight: bold;">Total Amount: &#8369;<?php echo number_format($total_amount, 2); ?></h6>
                                 
                                                                <!-- Recent 10 Entries -->
                                                                
                                                                    <h6 style="font-size: 15px; font-weight: bold;">Recent Data Entries:</h6>
                                                                    <?php if (!empty($recent_ten)): ?>
                                                                        <ol style="padding: 0; margin: 0; padding-left: 20px;">
                                                                            <?php foreach ($recent_ten as $record): ?>
                                                                                <li style="margin-bottom: 10px; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 6px;">
                                                                                    <?php echo date('F d, Y', strtotime($record['date'])); ?>&nbsp; | &nbsp;
                                                                                    <?php echo htmlspecialchars($record['description']); ?>&nbsp; | &nbsp;
                                                                                    &#8369;<?php echo number_format($record['amount'], 2); ?><br>
                                                                                    <span style="font-size: 12px; color: #555;">
                                                                                        <i><?php echo htmlspecialchars($record['client_project']); ?></i><br>
                                                                                    </span>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ol>
                                                                    <?php else: ?>
                                                                        <p style="font-size: 13px; color: #777;">No recent records available.</p>
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
                                                               data-target="#logs<?php echo $category_id; ?>">
                                                                <i class="bi bi-chevron-bar-down"></i>
                                                            </button>
                                                            <!-- Collapsible Logs Section -->
                                                            <div class="custom-collapse" id="logs<?php echo $category_id; ?>">
                                                                <div class="highlight-box-charts" style="font-size: 12px; color: #555;">
                                                                    <p class="mt-2 mb-2">Recorded by: <?php echo htmlspecialchars($user_name); ?> | <?php echo ucfirst(htmlspecialchars($user_role)); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Edit Account Title Modal -->
                                            <div class="modal fade" id="editChartAccounts<?php echo $category_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editClientLabel">Edit Account Title</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form id="client-form" action="../forms_logic/update_chartAccounts.php" method="POST">
                                                                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Number</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Auto-fills once an Account Type is selected.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountNum" value="<?php echo $account_num; ?>" id="account_num" readonly>
                                                                </div>
                                                                <div class="mb-3">
                                                                   <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Title</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>This will appear as an account title or category option in transaction forms.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountDes" value="<?php echo $category; ?>" id="account_des" placeholder="Ex. Salary Expense, Construction Supply Expense" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="accountType" class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Type</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select how this account is classified.</i>
                                                                    </span>
                                                                    <select name="account_type" value="<?php echo $account_type; ?>" id="account_type" class="form-select" required>
                                                                        <option value="Asset" <?php ($account_type == 'Asset') ? 'selected' : ''; ?>>Asset</option>
                                                                        <option value="Liability" <?php ($account_type == 'Liability') ? 'selected' : ''; ?>>Liability</option>
                                                                        <option value="Equity" <?php ($account_type == 'Equity') ? 'selected' : ''; ?>>Equity</option>
                                                                        <option value="Revenue" <?php ($account_type == 'Revenue') ? 'selected' : ''; ?>>Revenue</option>
                                                                        <option value="Expense" <?php ($account_type == 'Expense') ? 'selected' : ''; ?>>Expense</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                     <label for="form_usage" class="form-label mb-0 fw-bold" style="font-size: 18px;">Form Assignment</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select transaction form to which this category/account title will be used.</i>
                                                                    </span>
                                                                    <select name="form_usage" value="<?php echo $form_usage; ?>" class="form-select" required>
                                                                        <option value="<?php echo $form_usage; ?>"><?php echo $form_usage; ?></option>
                                                                        <option value="Company Expense">Company Expense</option>
                                                                        <option value="Payment Transaction">Payment Transaction</option>
                                                                    </select>
                                                                </div>
                                                                <div class="text-end">
                                                                    <button name="save_projectChanges" class="btn btn-primary ">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td><span class="text-muted">#</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                         </div>
                    </div>
                </section>

                <!-- FOR COMPANY EXPENSES ACCOUNT TITLE LIST -->
                <section id="company_expense_accounts" class="account-section" style="display: none; padding: 0 !important;">
            
                    <!-- Table for Company Expense Account Titles -->
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mt-3 mb-4">
                            <h4 style="font-weight: bold;" class="mb-0">Company Expense Accounts List</h4>
                        </div> 
                        <!-- DATA TABLE FOR COMPANY EXPENSE ACCOUNT TITLES -->
                        <script type="text/javascript">
                            $(document).ready(function() {
                                // Project Expense Accounts Table
                                $('#company_expense_titles').DataTable({
                                    responsive: true,
                                    dom: 'lfrtip',
                                    buttons: [],
                                    ordering: false,
                                    language: {
                                        search: '',
                                        searchPlaceholder: "Search account title...",
                                        paginate: {
                                            previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                            next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                        }
                                    }
                                });
                                // Wrap toolbar and add margin
                                var toolbar1 = $('<div id="toolbarComp" class="d-flex align-items-center justify-content-between"></div>');
                                toolbar1.append($('#company_expense_titles_wrapper .dataTables_length'));

                                var rightControls1 = $('<div class="d-flex align-items-center gap-2"></div>');
                                rightControls1.append($('#company_expense_titles_wrapper .dataTables_filter'));
                                rightControls1.append(`
                                    <button type="button" class="btn add-account-title-btn shadow-sm" 
                                            data-bs-toggle="modal" data-bs-target="#addClientModal">
                                        <i class="bi bi-plus-circle me-1"></i>
                                        Add Account Title
                                    </button>
                                `);

                            toolbar1.append(rightControls1);
                                $('#company_expense_titles_wrapper').prepend(toolbar1);

                                // To match the width of the table
                                    $('#toolbarComp').css({
                                        marginBottom: '20px',
                                        paddingBottom: '15px',
                                        borderBottom: '#d1d0d0 solid 1px'
                                    });
                            })
                        </script>
                        <table class="table table-bordered" id="company_expense_titles">
                            <thead>
                                <tr>
                                    <th scope="col" class="num_head">#</th>
                                    <th scope="col" class="account_num_head">Account Number</th>
                                    <th scope="col" class="three_head">Account Title</th>
                                    <th scope="col" class="three_head">Account Type</th>
                                    <th scope="col" class="three_head">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                // $con = mysqli_connect("localhost", "root", "", "financial_management");
                                $fetch_query = "SELECT * FROM chart_accounts WHERE form_usage = 'Company Expense' ORDER BY account_num ASC";
                                $fetch_query_run = mysqli_query($conn, $fetch_query);

                                $row_num = 1;
                                if (mysqli_num_rows($fetch_query_run) > 0) {
                                    while ($row = mysqli_fetch_array($fetch_query_run)) {
                                        $category_id = $row['category_id'];
                                        $account_num = $row['account_num'];
                                        $category = $row['category'];
                                        $account_type = $row['account_type'];
                                        $form_usage = $row['form_usage'];
                                        $statement_field = $row['statement_field'];
                                        // echo $row['category_id'];

                                ?>
                                        <tr>
                                            <td style="text-align: center; padding-left: 0;"><?php echo $row_num++; ?></td>
                                            <td style="text-align: center; padding-left: 0;"><?php echo $row['account_num']; ?></td>
                                            <td><?php echo $row['category']; ?></td>
                                            <td><?php echo $row['account_type']; ?></td>
                                            <td class="d-flex justify-content-center align-items-center gap-3">
                                                <a href="#" class="btn btn-view btn-sm" data-bs-toggle="modal" data-bs-target="#viewChartAccount<?php echo $category_id; ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 18">
                                                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                                    </svg>
                                                    View
                                                </a>
                                                <a href="#" class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#editChartAccounts<?php echo $category_id; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                        Edit
                                                    </a>
                                            </td>
                                        </tr>
                                        <!-- This is for fetching and displaying total amounts for each category -->
                                             <?php
                                             $total_company = 0;
                                             $recent_ten_company = [];
                                             
                                            //  fetch total from expenses table and display 10 recent entries
                                            $query_comp_expenses = mysqli_query($conn, "SELECT amount, date, description, category, category_num
                                                                                FROM company_expense WHERE category = '$category' OR category_num = '$account_num'
                                                                                ORDER BY date DESC LIMIT 10");
                                            $total_comp_expenses = mysqli_query($conn, "SELECT SUM(amount) AS totalCompany FROM company_expense WHERE category = '$category' OR category_num = '$account_num'");
                                            if ($total_comp_expenses && mysqli_num_rows($total_comp_expenses) > 0) {
                                                $total_comp_expenses_row = mysqli_fetch_assoc($total_comp_expenses);
                                                $total_company += $total_comp_expenses_row['totalCompany'] ?? 0;
                                            }

                    
                                            if ($query_comp_expenses && mysqli_num_rows($query_comp_expenses) > 0) {
                                                while ($exp = mysqli_fetch_assoc($query_comp_expenses)) {
                                                    $recent_ten_company[] = [
                                                        'source' => 'Company Expense',
                                                        'description' => $exp['description'],
                                                        'amount' => $exp['amount'],
                                                        'date' => $exp['date']
                                                    ];
                                                }
                                            }
                                            
                                             ?>
                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewChartAccount<?php echo $category_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="padding: 12px;">
                                                        <h5 class="modal-title" id="viewChartAccountModal<?php echo $category_id; ?>">Expense Details</h5>
                                                        <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                        <!-- Right Column -->
                                                        <p style="font-size: 16px;"><?php echo $row['account_num'] . ' - ' . ucwords($row['category']); ?><br>
                                                            <span style="font-size: 12px; color: #555;"><i>Account Number & Account Title/Category</i></span><br>
                                                        </p>
                                                        <p style="font-size: 16px;"><?php echo ucwords($row['account_type']) . ' - ' . ucwords($row['form_usage']); ?><br>
                                                            <span style="font-size: 12px; color: #555;"><i>Account Type & Form Usage</i></span>
                                                        </p>
                                                        <div class="highlight-box">
                                                            <p>This account title will appear in the category dropdown when recording a transaction.
                                                                It will specifically be available in the <strong><?php echo  ucwords($row['form_usage']); ?> Form</strong> under the transaction tab.
                                                            </p>
                                                              <h6 style="font-size: 15px; font-weight: bold;">Total Amount: &#8369;<?php echo number_format($total_company, 2); ?></h6>
                                 
                                                                <!-- Recent 10 Entries -->
                                                                
                                                                    <h6 style="font-size: 15px; font-weight: bold;">Recent Data Entries:</h6>
                                                                    <?php if (!empty($recent_ten_company)): ?>
                                                                        <ol style="padding: 0; margin: 0; padding-left: 20px;">
                                                                            <?php foreach ($recent_ten_company as $record): ?>
                                                                                <li style="margin-bottom: 10px; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 6px;">
                                                                                    <?php echo date('F d, Y', strtotime($record['date'])); ?>&nbsp; | &nbsp;
                                                                                    <?php echo htmlspecialchars($record['description']); ?>&nbsp; | &nbsp;
                                                                                    &#8369;<?php echo number_format($record['amount'], 2); ?><br>
                                                                                    
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ol>
                                                                    <?php else: ?>
                                                                        <p style="font-size: 13px; color: #777;">No recent records available.</p>
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
                                                            data-target="#logs<?php echo $category_id; ?>">
                                                            <i class="bi bi-chevron-bar-down"></i>
                                                        </button>
                                                        <!-- Collapsible Logs Section -->
                                                        <div class="custom-collapse" id="logs<?php echo $category_id; ?>">
                                                            <div class="highlight-box-charts" style="font-size: 12px; color: #555;">
                                                                <p class="mt-2 mb-2">Recorded by: <?php echo htmlspecialchars($user_name); ?> | <?php echo ucfirst(htmlspecialchars($user_role)); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                         <!-- Edit Account Title Modal -->
                                            <div class="modal fade" id="editChartAccounts<?php echo $category_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editClientLabel">Edit Account Title</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form id="client-form" action="../forms_logic/update_chartAccounts.php" method="POST">
                                                                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Number</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Auto-fills once an Account Type is selected.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountNum" value="<?php echo $account_num; ?>" id="account_num" readonly>
                                                                </div>
                                                                <div class="mb-3">
                                                                   <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Title</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>This will appear as an account title or category option in transaction forms.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountDes" value="<?php echo $category; ?>" id="account_des" placeholder="Ex. Salary Expense, Construction Supply Expense" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="accountType" class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Type</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select how this account is classified.</i>
                                                                    </span>
                                                                    <select name="account_type" value="<?php echo $account_type; ?>" id="account_type" class="form-select" required>
                                                                        <option value="Asset" <?php ($account_type == 'Asset') ? 'selected' : ''; ?>>Asset</option>
                                                                        <option value="Liability" <?php ($account_type == 'Liability') ? 'selected' : ''; ?>>Liability</option>
                                                                        <option value="Equity" <?php ($account_type == 'Equity') ? 'selected' : ''; ?>>Equity</option>
                                                                        <option value="Revenue" <?php ($account_type == 'Revenue') ? 'selected' : ''; ?>>Revenue</option>
                                                                        <option value="Expense" <?php ($account_type == 'Expense') ? 'selected' : ''; ?>>Expense</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                     <label for="form_usage" class="form-label mb-0 fw-bold" style="font-size: 18px;">Form Assignment</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select transaction form to which this category/account title will be used.</i>
                                                                    </span>
                                                                    <select name="form_usage" value="<?php echo $form_usage; ?>" class="form-select" required>
                                                                        <option value="<?php echo $form_usage; ?>"><?php echo $form_usage; ?></option>
                                                                        <option value="Company Expense">Company Expense</option>
                                                                        <option value="Payment Transaction">Payment Transaction</option>
                                                                    </select>
                                                                </div>
                                                                <div class="text-end">
                                                                    <button name="save_projectChanges" class="btn btn-primary ">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td><span class="text-muted">#</span></td>
                                        <td><span class="text-muted">—</span></td>
                                        <td><span class="text-muted">—</span></td>
                                        <td><span class="text-muted">—</span></td>
                                        <td><span class="text-muted">—</span></td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                
                <!-- FOR PAYMENT ACCOUNT TITLES -->
                <section id="payment_accounts" class="account-section" style="display: none;">
                    <!-- Table for Payment Transaction Account Titles -->
                    <div class="card-body">
                         <div class="d-flex align-items-center justify-content-between mt-3 mb-4" >
                            <h4 style="font-weight: bold;" class=" mb-0">Payment Transaction Accounts List</h4>
                        </div>   
                        <!-- DATA TABLE FOR CLIENT PAYMENTS ACCOUNT TITLES -->
                    <script type="text/javascript">
                        $(document).ready(function() {
                            // Project Expense Accounts Table
                            $('#client_payment_titles').DataTable({
                                responsive: true,
                                dom: 'lfrtip',
                                buttons: [],
                                ordering: false,
                                language: {
                                    search: '',
                                    searchPlaceholder: "Search account title...",
                                    paginate: {
                                            previous: '<i class="bi bi-chevron-bar-left"></i>', // icon only
                                            next: '<i class="bi bi-chevron-bar-right"></i>' // icon only
                                        }
                                }
                            });
                            // Wrap toolbar and add margin
                            var toolbar2 = $('<div id="toolbarPay" class="d-flex align-items-center justify-content-between"></div>');
                            toolbar2.append($('#client_payment_titles_wrapper .dataTables_length'));
                            toolbar2.append($('#client_payment_titles_wrapper .dataTables_filter'));
                            $('#client_payment_titles_wrapper').prepend(toolbar2);

                            var rightControls2 = $('<div class="d-flex align-items-center gap-2"></div>');
                            rightControls2.append($('#client_payment_titles_wrapper .dataTables_filter'));
                            rightControls2.append(`
                                <button type="button" class="btn add-account-title-btn shadow-sm" 
                                        data-bs-toggle="modal" data-bs-target="#addClientModal">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Add Account Title
                                </button>
                            `);

                            toolbar2.append(rightControls2);
                            // To match the width of the table
                                    $('#toolbarPay').css({
                                        marginBottom: '20px',
                                        paddingBottom: '15px',
                                        borderBottom: '#d1d0d0 solid 1px'
                                    });
                        })
                    </script>
                        <table class="table table-bordered" id="client_payment_titles">
                            <thead>
                                <tr>
                                    <th scope="col" class="num_head">#</th>
                                    <th scope="col" class="account_num_head">Account Number</th>
                                    <th scope="col" class="three_head">Account Title</th>
                                    <th scope="col" class="three_head">Account Type</th>
                                    <th scope="col" class="three_head">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                // $con = mysqli_connect("localhost", "root", "", "financial_management");
                                $fetch_query = "SELECT * FROM chart_accounts WHERE form_usage = 'Payment Transaction' ORDER BY account_num ASC";
                                $fetch_query_run = mysqli_query($conn, $fetch_query);

                                $row_num = 1;
                                if (mysqli_num_rows($fetch_query_run) > 0) {
                                    while ($row = mysqli_fetch_array($fetch_query_run)) {
                                        $category_id = $row['category_id'];
                                        $account_num = $row['account_num'];
                                        $category = $row['category'];
                                        $account_type = $row['account_type'];
                                        $form_usage = $row['form_usage'];
                                        $statement_field = $row['statement_field'];
                                        // echo $row['category_id'];

                                ?>
                                        <tr>
                                            <td style="text-align: center; padding-left: 0;"><?php echo $row_num++; ?></td>
                                            <td style="text-align: center; padding-left: 0;"><?php echo $row['account_num']; ?></td>
                                            <td><?php echo $row['category']; ?></td>
                                            <td><?php echo $row['account_type']; ?></td>
                                            <td class="d-flex justify-content-center align-items-center gap-3">
                                                <a href="#" class="btn btn-view btn-sm" data-bs-toggle="modal" data-bs-target="#viewChartAccount<?php echo $category_id; ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 18">
                                                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                                    </svg>
                                                    View
                                                </a>
                                                <a href="#" class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#editChartAccounts<?php echo $category_id; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                        Edit
                                                    </a>
                                            </td>
                                        </tr>
                                        <!-- This is for fetching and displaying total amounts for each category -->
                                             <?php
                                             error_reporting(E_ALL);
                                            ini_set('display_errors', 1);
                                             $total_payment = 0;
                                             $recent_ten_payment = [];
                                             
                                            //  fetch total from expenses table and display 10 recent entries
                                            $query_payment = mysqli_query($conn, "SELECT amount, date, description, client_id, project_id, category, category_num
                                                                                FROM payment_clients WHERE category = '$category' AND category_num = '$account_num'
                                                                                ORDER BY date DESC LIMIT 10");
                                            $total_client_payment = mysqli_query($conn, "SELECT SUM(amount) AS total FROM payment_clients WHERE category = '$category' AND category_num = '$account_num'");
                                            if ($total_client_payment && mysqli_num_rows($total_client_payment) > 0) {
                                                $total_payment_row = mysqli_fetch_assoc($total_client_payment);
                                                $total_payment += $total_payment_row['total'] ?? 0;
                                            }

                                            if ($query_payment && mysqli_num_rows($query_payment) > 0){
                                                while ($pay = mysqli_fetch_assoc($query_payment)) {
                                                    $client_name = '';
                                                    $project_name = '';

                                                    // To fetch client name
                                                    if (!empty($pay['client_id'])){
                                                        $client_id = $pay['client_id'];
                                                        $client_name_query = mysqli_query($conn, "SELECT client_name FROM clients WHERE client_id = '$client_id' LIMIT 1");
                                                        if ($client_name_query && mysqli_num_rows($client_name_query) > 0){
                                                            $client_data = mysqli_fetch_assoc($client_name_query);
                                                            $client_name = $client_data['client_name'];
                                                        }
                                                    }

                                                    // to fetch project name
                                                    if (!empty($pay['project_id'])){
                                                        $project_id = $pay['project_id'];
                                                        $project_name_query = mysqli_query($conn, "SELECT project_name FROM projects WHERE project_id = '$project_id' LIMIT 1");
                                                        if ($project_name_query && mysqli_num_rows($project_name_query) > 0) {
                                                            $project_data = mysqli_fetch_assoc($project_name_query);
                                                            $project_name = $project_data['project_name'];
                                                        }
                                                    }
                                                     // Combine names
                                                    $client_project_display = '';
                                                    if ($client_name && $project_name) {
                                                        $client_project_display = "$client_name - $project_name";
                                                    } elseif ($client_name) {
                                                        $client_project_display = $client_name;
                                                    } elseif ($project_name) {
                                                        $client_project_display = $project_name;
                                                    } else {
                                                        $client_project_display = '—';
                                                    }

                                                    // Store formatted data
                                                    $recent_ten_payment[] = [
                                                        'source' => 'Payment Transaction',
                                                        'description' => $pay['description'],
                                                        'amount' => $pay['amount'],
                                                        'date' => $pay['date'],
                                                        'client_project' => $client_project_display
                                                    ];
                                                }
                                            }
                                            $total_budget = 0;
                                            $recent_ten_budget = [];
                                            
                                            // Fetch total and 10 most recent records from budget_allocation
                                            $query_budget = mysqli_query($conn, "SELECT amount, date, description, client_id, project_id, category, category_num
                                                                                FROM budget_allocation 
                                                                                WHERE category = '$category' OR category_num = '$account_num'
                                                                                ORDER BY date DESC LIMIT 10");
                                            
                                            $total_budget_allocation = mysqli_query($conn, "SELECT SUM(amount) AS total 
                                                                                            FROM budget_allocation 
                                                                                            WHERE category = '$category' OR category_num = '$account_num'");
                                            
                                            if ($total_budget_allocation && mysqli_num_rows($total_budget_allocation) > 0) {
                                                $total_budget_row = mysqli_fetch_assoc($total_budget_allocation);
                                                $total_budget += $total_budget_row['total'] ?? 0;
                                            }
                                            
                                            if ($query_budget && mysqli_num_rows($query_budget) > 0) {
                                                while ($budget = mysqli_fetch_assoc($query_budget)) {
                                                    $client_name = '';
                                                    $project_name = '';
                                            
                                                    // Fetch client name
                                                    if (!empty($budget['client_id'])) {
                                                        $client_id = $budget['client_id'];
                                                        $client_name_query = mysqli_query($conn, "SELECT client_name FROM clients WHERE client_id = '$client_id' LIMIT 1");
                                                        if ($client_name_query && mysqli_num_rows($client_name_query) > 0) {
                                                            $client_data = mysqli_fetch_assoc($client_name_query);
                                                            $client_name = $client_data['client_name'];
                                                        }
                                                    }
                                            
                                                    // Fetch project name
                                                    if (!empty($budget['project_id'])) {
                                                        $project_id = $budget['project_id'];
                                                        $project_name_query = mysqli_query($conn, "SELECT project_name FROM projects WHERE project_id = '$project_id' LIMIT 1");
                                                        if ($project_name_query && mysqli_num_rows($project_name_query) > 0) {
                                                            $project_data = mysqli_fetch_assoc($project_name_query);
                                                            $project_name = $project_data['project_name'];
                                                        }
                                                    }
                                            
                                                    // Combine names
                                                    $client_project_display = '';
                                                    if ($client_name && $project_name) {
                                                        $client_project_display = "From Allocate Budget Form " . "$client_name - $project_name";
                                                    } elseif ($client_name) {
                                                        $client_project_display = "From Allocate Budget Form " . $client_name;
                                                    } elseif ($project_name) {
                                                        $client_project_display = "From Allocate Budget Form " . $project_name;
                                                    } else {
                                                        $client_project_display = "From Allocate Budget Form " . '—';
                                                    }
                                            
                                                    // Store formatted data
                                                    $recent_ten_budget[] = [
                                                        'source' => 'Budget Allocation',
                                                        'description' => $budget['description'],
                                                        'amount' => $budget['amount'],
                                                        'date' => $budget['date'],
                                                        'client_project' => $client_project_display
                                                    ];
                                                }
                                            }
                                            $combined_records = array_merge($recent_ten_payment, $recent_ten_budget);
                                            
                                            // Sort by date (most recent first)
                                            usort($combined_records, function($a, $b) {
                                                return strtotime($b['date']) - strtotime($a['date']);
                                            });
                                            
                                             ?>
                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewChartAccount<?php echo $category_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="padding: 12px;">
                                                        <h5 class="modal-title" id="viewChartAccountModal<?php echo $category_id; ?>">Expense Details</h5>
                                                        <button type="button" class="btn-close" style="font-size: 14px;" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="line-height: 1.2; margin-bottom: 0; padding: 20px;">
                                                        <!-- Right Column -->
                                                        <p style="font-size: 16px;"><?php echo $row['account_num'] . ' - ' . ucwords($row['category']); ?><br>
                                                            <span style="font-size: 12px; color: #555;"><i>Account Number & Account Title/Category</i></span><br>
                                                        </p>
                                                        <p style="font-size: 16px;"><?php echo ucwords($row['account_type']) . ' - ' . ucwords($row['form_usage']); ?><br>
                                                            <span style="font-size: 12px; color: #555;"><i>Account Type & Form Usage</i></span>
                                                        </p>
                                                        <div class="highlight-box">
                                                            <p>This account title will appear in the category dropdown when recording a transaction.
                                                                It will specifically be available in the <strong><?php echo  ucwords($row['form_usage']); ?> Form</strong> under the transaction tab.
                                                            </p>
                                                            
                                                             <p><strong>Payment Total:</strong> ₱<?php echo number_format($total_payment, 2); ?></p>
                                                              <p><strong>Budget Total:</strong> ₱<?php echo number_format($total_budget, 2); ?></p>
                                 
                                                                <!-- Recent 10 Entries -->
                                                                
                                                                    <h6 style="font-size: 15px; font-weight: bold;">Recent Data Entries:</h6>
                                                                    <?php if (!empty($combined_records)): ?>
                                                                        <ol style="padding: 0; margin: 0; padding-left: 20px;">
                                                                            <?php foreach ($combined_records as $record): ?>
                                                                                <li style="margin-bottom: 10px; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 6px;">
                                                                                    <?php echo date('F d, Y', strtotime($record['date'])); ?>&nbsp; | &nbsp;
                                                                                    <?php echo htmlspecialchars($record['description']); ?>&nbsp; | &nbsp;
                                                                                    &#8369;<?php echo number_format($record['amount'], 2); ?><br>
                                                                                    <span style="font-size: 12px; color: #555;">
                                                                                        <i><?php echo htmlspecialchars($record['client_project']); ?></i><br>
                                                                                    </span>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ol>
                                                                    <?php else: ?>
                                                                        <p style="font-size: 13px; color: #777;">No recent records available.</p>
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
                                                            data-target="#logs<?php echo $category_id; ?>">
                                                            <i class="bi bi-chevron-bar-down"></i>
                                                        </button>
                                                        <!-- Collapsible Logs Section -->
                                                        <div class="custom-collapse" id="logs<?php echo $category_id; ?>">
                                                            <div class="highlight-box-charts" style="font-size: 12px; color: #555;">
                                                                <p class="mt-2 mb-2">Recorded by: <?php echo htmlspecialchars($user_name); ?> | <?php echo ucfirst(htmlspecialchars($user_role)); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                         <div class="modal fade" id="editChartAccounts<?php echo $category_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editClientLabel">Edit Account Title</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form id="client-form" action="../forms_logic/update_chartAccounts.php" method="POST">
                                                                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Number</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Auto-fills once an Account Type is selected.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountNum" value="<?php echo $account_num; ?>" id="account_num" readonly>
                                                                </div>
                                                                <div class="mb-3">
                                                                   <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Title</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>This will appear as an account title or category option in transaction forms.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountDes" value="<?php echo $category; ?>" id="account_des" placeholder="Ex. Salary Expense, Construction Supply Expense" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="accountType" class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Type</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select how this account is classified.</i>
                                                                    </span>
                                                                    <select name="account_type" value="<?php echo $account_type; ?>" id="account_type" class="form-select" required>
                                                                        <option value="Asset" <?php ($account_type == 'Asset') ? 'selected' : ''; ?>>Asset</option>
                                                                        <option value="Liability" <?php ($account_type == 'Liability') ? 'selected' : ''; ?>>Liability</option>
                                                                        <option value="Equity" <?php ($account_type == 'Equity') ? 'selected' : ''; ?>>Equity</option>
                                                                        <option value="Revenue" <?php ($account_type == 'Revenue') ? 'selected' : ''; ?>>Revenue</option>
                                                                        <option value="Expense" <?php ($account_type == 'Expense') ? 'selected' : ''; ?>>Expense</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                     <label for="form_usage" class="form-label mb-0 fw-bold" style="font-size: 18px;">Form Assignment</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select transaction form to which this category/account title will be used.</i>
                                                                    </span>
                                                                    <select name="form_usage" value="<?php echo $form_usage; ?>" class="form-select" required>
                                                                        <option value="<?php echo $form_usage; ?>"><?php echo $form_usage; ?></option>
                                                                        <option value="Company Expense">Company Expense</option>
                                                                        <option value="Payment Transaction">Payment Transaction</option>
                                                                    </select>
                                                                </div>
                                                                <div class="text-end">
                                                                    <button name="save_projectChanges" class="btn btn-primary ">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                              <!-- Edit Account Title Modal -->
                                            <div class="modal fade" id="editChartAccounts<?php echo $category_id; ?>" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editClientLabel">Edit Account Title</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form id="client-form" action="../forms_logic/update_chartAccounts.php" method="POST">
                                                                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Number</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Auto-fills once an Account Type is selected.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountNum" value="<?php echo $account_num; ?>" id="account_num" readonly>
                                                                </div>
                                                                <div class="mb-3">
                                                                   <label class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Title</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>This will appear as an account title or category option in transaction forms.</i>
                                                                    </span>
                                                                    <input type="text" class="form-control" name="accountDes" value="<?php echo $category; ?>" id="account_des" placeholder="Ex. Salary Expense, Construction Supply Expense" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="accountType" class="form-label mb-0 fw-bold" style="font-size: 18px;">Account Type</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select how this account is classified.</i>
                                                                    </span>
                                                                    <select name="account_type" value="<?php echo $account_type; ?>" id="account_type" class="form-select" required>
                                                                        <option value="Asset" <?php ($account_type == 'Asset') ? 'selected' : ''; ?>>Asset</option>
                                                                        <option value="Liability" <?php ($account_type == 'Liability') ? 'selected' : ''; ?>>Liability</option>
                                                                        <option value="Equity" <?php ($account_type == 'Equity') ? 'selected' : ''; ?>>Equity</option>
                                                                        <option value="Revenue" <?php ($account_type == 'Revenue') ? 'selected' : ''; ?>>Revenue</option>
                                                                        <option value="Expense" <?php ($account_type == 'Expense') ? 'selected' : ''; ?>>Expense</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                     <label for="form_usage" class="form-label mb-0 fw-bold" style="font-size: 18px;">Form Assignment</label>
                                                                    <span style="display:block; font-size: 12px; color: #555; margin-top: 0; margin-bottom: 8px;">
                                                                        <i>Select transaction form to which this category/account title will be used.</i>
                                                                    </span>
                                                                    <select name="form_usage" value="<?php echo $form_usage; ?>" class="form-select" required>
                                                                        <option value="<?php echo $form_usage; ?>"><?php echo $form_usage; ?></option>
                                                                        <option value="Company Expense">Company Expense</option>
                                                                        <option value="Payment Transaction">Payment Transaction</option>
                                                                    </select>
                                                                </div>
                                                                <div class="text-end">
                                                                    <button name="save_projectChanges" class="btn btn-primary ">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td><span class="text-muted">#</span></td>
                                        <td><span class="text-muted">—</span></td>
                                        <td><span class="text-muted">—</span></td>
                                        <td><span class="text-muted">—</span></td>
                                        <td><span class="text-muted">—</span></td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>
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
                        ? "The account title has been move to the archive records."
                        : "Something went wrong while archiving the account title.";
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

    <script>
        // Close modal
        let modalElement = document.getElementById("addClientModal");
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
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

</body>

</html>