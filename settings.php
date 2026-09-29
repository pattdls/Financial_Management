<?php
session_start();
include('dbcon.php');


// Fetch the user's id from the users table based on session user_id
// $user_id = $_SESSION['user_id'];
// $userQuery = mysqli_query($conn, "SELECT id FROM users WHERE id = '$user_id' LIMIT 1");
// $userRow = mysqli_fetch_assoc($userQuery);
// $logged_in_user_id = $userRow['id'] ?? null;

// // Always include session check first
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header("Location: /Financial_Management/login_form.php");
//     exit();
// }

// Then prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Allow both admin and finance roles
if (
    !isset($_SESSION['authenticated']) ||
    !in_array($_SESSION['auth_user']['role'], ['admin', 'finance'])
) {

    // $_SESSION['status'] = "<div class='alert'>Access Denied.</div>";
    // header("Location: login_form.php");
    // exit(0);
}

// Database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get all clients
$clients = [];
$clientResult = mysqli_query($conn, "SELECT client_id, client_name FROM clients");
if ($clientResult && mysqli_num_rows($clientResult) > 0) {
    while ($row = mysqli_fetch_assoc($clientResult)) {
        $clients[] = $row;
    }
}

// Check for client_id in session
$client_id = $_SESSION['client_id'] ?? null;

$projects = []; // Initialize

if ($client_id) {
    // Fetch projects for logged-in client
    $projectResult = mysqli_query($conn, "SELECT p.project_id, p.project_name, c.client_id, c.client_name, p.project_status, p.start_date, p.end_date, p.user_approval, p.finance_approval
                                      FROM projects p 
                                      JOIN clients c ON p.client_id = c.client_id
                                      WHERE p.client_id = '$client_id'");
} else {
    // Fetch all projects if no client session is set
    $projectResult = mysqli_query($conn, "SELECT p.project_id, p.project_name, c.client_id, c.client_name, p.project_status, p.start_date, p.end_date, p.user_approval, p.finance_approval
                                  FROM projects p 
                                  JOIN clients c ON p.client_id = c.client_id");
}

if ($projectResult && mysqli_num_rows($projectResult) > 0) {
    while ($row = mysqli_fetch_assoc($projectResult)) {
        $projects[] = $row;
    }
}
$selected_project_id = $_GET['project_id'] ?? null; // Or use session if needed

$weeklyIncome = [];
$weeklyExpenses = [];
$weeklyLabels = [];

if ($selected_project_id) {
    $projectQuery = mysqli_query($conn, "SELECT start_date, end_date, projected_revenue FROM projects WHERE project_id = '$selected_project_id'");
    $project = mysqli_fetch_assoc($projectQuery);

    $start_date = $project['start_date'] ?? date('Y-m-01');
    $end_date = $project['end_date'] ?? date('Y-m-t');
    $projected_revenue = (float)$project['projected_revenue'] ?? 0;

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');

    $interval = new DateInterval('P7D');
    $period = new DatePeriod($start, $interval, $end);
    $weeksCount = iterator_count($period);
    $period = new DatePeriod($start, $interval, $end); // reset

    foreach ($period as $weekStart) {
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');

        $startStr = $weekStart->format('Y-m-d');
        $endStr = $weekEnd->format('Y-m-d');

        // Income: divide projected revenue equally per week
        $weeklyIncome[] = round($projected_revenue / max($weeksCount, 1), 2);

        // Expense: sum from database
        $expenseQuery = "
            SELECT SUM(amount) AS total 
            FROM expenses 
            WHERE project_id = '$selected_project_id'
            AND date BETWEEN '$startStr' AND '$endStr'";
        $expenseResult = mysqli_query($conn, $expenseQuery);
        $expenseRow = mysqli_fetch_assoc($expenseResult);

        $weeklyExpenses[] = (float)$expenseRow['total'] ?? 0;
        $weeklyLabels[] = "Week of " . $weekStart->format('M j');
    }
}


// Get user name from session
$name = $_SESSION['auth_user']['name'] ?? 'Guest';

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <!-- Bootsrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- System Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="resources/css/main.css">
    <link rel="stylesheet" href="resources/css/index.css">
    <link rel="stylesheet" href="resources/css/project_details.css">

    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- ChartJs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <!-- Luicide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

</head>

<body class="d-flex">

    <div>
        <!-- Side Nav Container -->
        <div id="sidebar" class="side-nav-container container-fluid">
            <!-- Side Nav Header -->
            <!-- <div class="d-flex justify-content-end">
                <button id="sidebarToggle" class="btn btn-outline-light mb-3" type="button" style="width:40px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left-to-line-icon lucide-arrow-left-to-line"><path d="M3 19V5"/><path d="m13 6-6 6 6 6"/><path d="M7 12h14"/></svg>
                </button>
            </div> -->
            <!-- Company Name -->
            <div class="company-name">
                <img src="resources/images/rvr logo.png " alt="" width="45" height="45" class="rounded-circle me-2">
                <strong>RVR Squared</strong>
            </div>

            <!-- Side Nav Options -->
            <ul class="nav nav-pills flex-column mb-auto text-start">
                <li class="nav-item mb-2">
                    <a href="index.php" class="nav-link active d-flex align-items-center" data-section="home.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                            <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                            <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        </svg>
                        Dashboard
                    </a>
                </li>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                    <li class="nav-item mb-2">
                        <a href="inner_pages/clients.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round-icon lucide-users-round">
                                <path d="M18 21a8 8 0 0 0-16 0" />
                                <circle cx="10" cy="8" r="5" />
                                <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
                            </svg>
                            Clients
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item mb-2">
                    <a href="inner_pages/expenses.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-banknote-arrow-down-icon lucide-banknote-arrow-down">
                            <path d="M12 18H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5" />
                            <path d="m16 19 3 3 3-3" />
                            <path d="M18 12h.01" />
                            <path d="M19 16v6" />
                            <path d="M6 12h.01" />
                            <circle cx="12" cy="12" r="2" />
                        </svg>
                        Transactions
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="inner_pages/chartaccounts.php" class="nav-link  d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-chart-gantt-icon lucide-square-chart-gantt">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M9 8h7" />
                            <path d="M8 12h6" />
                            <path d="M11 16h5" />
                        </svg>
                        Chart of Accounts
                    </a>
                </li>
                <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance'): ?>
                    <li class="nav-item mb-2">
                        <a href="inner_pages/create_user.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page == 'create_user.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-user-round-icon lucide-square-user-round">
                                <path d="M18 21a6 6 0 0 0-12 0" />
                                <circle cx="12" cy="11" r="4" />
                                <rect width="18" height="18" x="3" y="3" rx="2" />
                            </svg>
                            Manage users
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item mb-2">
                    <a href="inner_pages/intro_archive.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page == 'intro_archive.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-archive-icon lucide-archive">
                            <rect width="20" height="5" x="2" y="3" rx="1" />
                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                            <path d="M10 12h4" />
                        </svg>
                        Archive Records
                    </a>
                </li>
            </ul>

            <ul class="nav nav-pills flex-column text-start mb-3">
                <li class="nav-item mb-2">
                    <a href="../forms_logic/logout.php" class="nav-link d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" x2="9" y1="12" y2="12" />
                        </svg>
                        Log out
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
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
                    <a href="/Financial_Management/forms_logic/logout.php" class="btn btn-danger rounded-pill px-3">Log Out</a>
                </div>
            </div>
        </div>
    </div>


    <!-- Main Content -->
    <div class="main-content container-fluid p-0">

        <nav class="d-flex flex-row align-items-start justify-content-between">

            <div class="page-title">
                Settings
            </div>

            <div class="d-flex align-items-center">
                <p id="datetime"></p>

                <!-- Settings -->
                <a href="settings.php" style="color: black;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings-icon lucide-settings mt-2 me-3">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </a>

                <!-- User Account -->
                <div class="">
                    <div href="#" class="user-account d-flex align-items-center text-decoration-none" id="dropdownUser1" data-bs-toggle="" aria-expanded="false">

                        <div class=""><?php echo $name; ?></div>

                        <img src="resources/images\1x1_unif_bluebg.png" alt="" width="32" height="32" class="rounded-circle me-2 mt-1">
                    </div>
                    <ul class="user-account-dropdown dropdown-menu dropdown-menu-dark text-small shadow">
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <ul class="financial-nav mt-5">
            <li class="nav-item">
                <a class="nav-link" aria-current="page" href="#">Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" aria-current="page" href="#">Project Status</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" aria-current="page" href="#">Project Status</a>
            </li>
        </ul>



    </div>


    <script src="resources/js/dashboard.js"></script>
</body>

</html>