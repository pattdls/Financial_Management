<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/project_details.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
                    <a href="clients.php" class="nav-link active d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-people-fill me-2">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
                        </svg>
                        Clients
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="expenses.php" class="nav-link d-flex align-items-center" data-section="clients.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-wallet2 me-2">
                            <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5z" />
                        </svg>
                        Expenses
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="chartaccounts.php" class="nav-link  d-flex align-items-center" data-section="clients.html">
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
                    <img src="../images/1x1_unif_bluebg.png" alt="" width="32" height="32" class="rounded-circle me-2">
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

            <nav class="d-flex flex-row align-items-start gap-6 px-4">

                <div class="cl-head mt-3">
                    <h1><?php echo htmlspecialchars($project['project_name'] ?? 'Unknown Project'); ?></h1>
                </div>

                <p id="datetime"></p>

            </nav>

            <div class="container-fluid px-4">

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
                        <a class="nav-link" href="project_details.php?project_id=<?php echo $project_id; ?>">Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal.php?project_id=<?php echo $project_id; ?>">Journal Entry</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Income.php?project_id=<?php echo $project_id; ?>">Income Statement</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cashflow.php?project_id=<?php echo $project_id; ?>">Cash Flow</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="financial.php?project_id=<?php echo $project_id; ?>">Financial Report</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>

                <!-- Content Section -->
                <div class="content mt-4">
                    <h3>Financial Report</h3>

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
            </script>


</body>

</html>