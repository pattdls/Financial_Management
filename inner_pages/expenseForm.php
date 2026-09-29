<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/expense.css">
    <link rel="stylesheet" href="../css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="expenses.js"></script>
</head>

<body>

    <div class="d-flex">
        <!-- Side Nav Container -->
        <div class="side-nav-container d-flex flex-column flex-shrink-0 p-3 text-white bg-dark">
            <!-- Company Name -->
            <div class="company-name">
                <img src="../images/rvr logo.png" alt="" width="45" height="45" class="rounded-circle me-2">
                <strong>RVR Squared</strong>
            </div>

            <!-- Side Nav Options -->
            <ul class="nav nav-pills flex-column mb-auto text-start">
                <li class="nav-item mb-2">
                    <a href="../index.php" class="nav-link d-flex align-items-center" data-section="home.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-house-fill me-2">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293z" />
                            <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293z" />
                        </svg>
                        Home
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="clients.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-people-fill me-2">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
                        </svg>
                        Clients
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="expenses.php" class="nav-link active d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-wallet2 me-2">
                            <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5z" />
                        </svg>
                        Expenses
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="chartaccounts.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-clipboard-data me-2">
                            <path d="M4 11a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0zm6-4a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0zM7 9a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0z" />
                            <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z" />
                            <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z" />
                        </svg>
                        Chart of Accounts
                    </a>
                </li>
            </ul>

            <!-- User Account -->
            <div class="dropdown">
                <a href="#" class="user-account d-flex align-items-center text-white text-decoration-none dropdown-toggle mb-4" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="images\1x1_unif_bluebg.png" alt="" width="32" height="32" class="rounded-circle me-2">
                    <strong>User Account</strong>
                </a>
                <ul class="user-account-dropdown dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="#">New project...</a></li>
                    <li><a class="dropdown-item" href="#">Settings</a></li>
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="#">Sign out</a></li>
                </ul>
            </div>
        </div>


        <!-- Main Content -->
        <div class="main-content container-fluid p-0">

            <nav class="d-flex flex-row align-items-start px-4">

                <div class="cl-head mt-3">
                    <h1>Expenses</h1>
                </div>

                <p id="datetime"></p>

            </nav>

            <div class="container-fluid px-4">

                <div class="cl-head mt-4">
                    <h3>Expense Form</h3>
                </div>

                <div class="record-expense">
                    <?php
                    if (isset($_SESSION['expense_status'])) {
                        echo $_SESSION['expense_status'];
                        unset($_SESSION['expense_status']);
                    }
                    ?>
                    <form name="expenseForm" action="expense_logic.php" method="POST">

                        <div class="expense-form container-fluid p-3">

                            <div class="row">

                                <!-- Left Column -->
                                <div class="col-md-6">
                                    <div class="d-flex mb-3">
                                        <label for="expense-date" class="form-label">Date:</label>
                                        <input type="date" name="date" class="form-control" id="expense-date" required>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <label for="client" class="form-label">Client:</label>
                                        <select name="client_id" class="form-select" id="client-dropdown">
                                            <option value="">Select Client</option>
                                            <?php
                                            $clients = mysqli_query($con, "SELECT * FROM clients");
                                            while ($c = mysqli_fetch_array($clients)) {
                                            ?>
                                                <option value="<?php echo $c['client_id'] ?>"><?php echo $c['client_name'] ?> </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <label for="project" class="form-label">Project:</label>
                                        <select name="project_id" id="project-dropdown" class="form-select" disabled>
                                            <option value="">
                                                <center>--Select a project--</center>
                                            </option>

                                        </select>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <label for="category" class="form-label">Category:</label>
                                        <select name="category" class="form-select">
                                            <option value="">Select Category</option>
                                            <?php
                                            $category = mysqli_query($con, "SELECT * FROM chart_accounts");
                                            while ($c = mysqli_fetch_array($category)) {
                                            ?>
                                                <option value="<?php echo htmlspecialchars($c['category']); ?>"><?php echo $c['category']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <label for="payment-method" class="form-label">Payment Method:</label>
                                        <select name="payment_method" class="form-select">
                                            <option>Select mode of payment</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Check Payment">Check Payment</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Credit Purchase">Credit Purchase</option>
                                            <option value="E-payment">E-payment</option>
                                            <option value="Installment Payment">Installment Payment</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="col-md-6">
                                    <div class="field_space d-flex mb-3 gap-2">
                                        <label for="description" class="form-label">Description:</label>
                                        <input type="text" name="description_field" class="form-control" id="description" required>
                                    </div>
                                    <div class="field_space d-flex mb-3 gap-2">
                                        <label for="store" class="form-label">Store:</label>
                                        <input type="text" name="store_name" class="form-control" id="store">
                                    </div>
                                    <div class="field_space mb-3 d-flex align-items-center gap-2">
                                        <label for="amount" class="form-label">Amount:</label>
                                        <div class="input-group" style="width: 225px;">
                                            <span class="input-group-text">₱</span>
                                            <input type="text" name="amount" class="form-control" id="amount">
                                        </div>
                                    </div>
                                    <div class="field_space d-flex mb-3 gap-2">
                                        <label for="invoice" class="form-label">Invoice No.:</label>
                                        <input type="text" name="invoice_num" class="form-control" id="invoice">
                                    </div>
                                    <div class="text-end">
                                        <button name="save_expense" class="btn btn-dark mt-5 ms-auto">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cl-head mt-3">
                <h4>LIST OF EXPENSES</h4>
            </div>

            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Date</th>
                            <th scope="col">Description</th>
                            <th scope="col">Category</th>
                            <th scope="col">Invoice Number</th>
                            <th scope="col">Store Name</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Payment Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        $con = mysqli_connect("localhost", "root", "", "financial_management");
                        $fetch_query = "SELECT * FROM expenses ORDER BY date ASC";
                        $fetch_query_run = mysqli_query($con, $fetch_query);

                        // Fetch total amount
                        $total_query = "SELECT SUM(amount) AS total_amount FROM expenses";
                        $total_result = mysqli_query($con, $total_query);
                        $total_row = mysqli_fetch_assoc($total_result);
                        $totalAmount = $total_row['total_amount'];

                        if (mysqli_num_rows($fetch_query_run) > 0) {
                            $rowNumber = 1;
                            while ($row = mysqli_fetch_array($fetch_query_run)) {
                                // echo $row['category_id'];

                        ?>
                                <tr>
                                    <td><?php echo $rowNumber++; ?></td>
                                    <td><?php echo $row['date']; ?></td>
                                    <td><?php echo ucwords($row['description']); ?></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td><?php echo $row['invoice_num']; ?></td>
                                    <td><?php echo ucwords($row['store_name']); ?></td>
                                    <td>P <?php echo $row['amount']; ?>.00</td>
                                    <td><?php echo $row['payment_method']; ?></td>
                                </tr>

                            <?php
                            }
                            ?>
                            <!-- Total row -->
                            <tr>
                                <td colspan="6" style="text-align: right;"><b>Total:</b></td>
                                <td><b>P <?php echo number_format($totalAmount, 2); ?></b></td>
                                <td></td>
                            </tr>
                        <?php
                        } else {
                        ?>
                            <tr colspan="4">No Record Found</tr>
                        <?php
                        }

                        ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="./resources/js/auto_logout.js"></script>

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
            document.getElementById("expense-date").valueAsDate = new Date(); // Auto-fill date
        });
    </script>

</body>

</html>