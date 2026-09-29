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

    // Check approval status
    $userApproved = ($project['user_approval'] ?? '') === '1';
    $financeApproved = ($project['finance_approval'] ?? '') === '1';
    $canEditPhases = $userApproved && $financeApproved;
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


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Project Details"; ?>
    <?php include('../layout/head.php'); ?>
    <link rel="stylesheet" href="../resources/css/project_details.css">
    <link rel="stylesheet" href="../resources/css/project_budget_sum.css">
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
                        <a class="nav-link active" aria-current="page" href="proj_stat.php?project_id=<?php echo $project_id; ?>">Project Status</a>
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
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link " href="balance.php?project_id=<?php echo $project_id; ?>">Balance Sheet</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="Income.php?project_id=<?php echo $project_id; ?>">Project Income</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="financial.php?project_id=<?php echo $project_id; ?>">Financial Report</a>
                    </li> -->
                </ul>

                <div class="p-0">
                    <hr>
                </div>

                <div class="two-column-layout row mt-4" style="border: none !important; box-shadow: none;">
                    <!-- Left Column -->
                    <div class="left-column d-flex flex-column gap-4">
                        <!-- Project Details -->
                        <div class="card">
                            <!-- Card Header -->
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="mb-0 fw-bold project-name"><?php echo htmlspecialchars($project['project_name'] ?? 'Unknown Project'); ?></div>
                                    <?php
                                        $isFinance = (isset($_SESSION['role']) && $_SESSION['role'] === 'finance');
                                        $showPO = true;
                                        
                                        // Finance should only see PO after project is approved/completed
                                        if ($isFinance) {
                                            if ($project['project_status'] !== 'completed' && $project['project_status'] !== 'approved') {
                                                $showPO = false;
                                            }
                                        }
                                        ?>
                                        
                                        <?php if ($showPO): ?>
                                            <small class="text-muted">
                                                PO Number: <?php echo htmlspecialchars($project['po_num'] ?? 'N/A'); ?>
                                            </small>
                                        <?php endif; ?>
                                </div>
                                <?php
                                // Determine status badge color based on project status
                                $status = $project['project_status'] ?? 'Unknown';
                                $badgeClass = 'bg-secondary'; // default badge color
                                $status = strtolower($status);
                                switch ($status) {
                                    case 'upcoming':
                                        $badgeClass = 'badge-upcoming';
                                        break;
                                    case 'ongoing':
                                        $badgeClass = 'badge-ongoing';
                                        break;
                                    case 'overdue':
                                        $badgeClass = 'badge-overdue';
                                        break;
                                    case 'cancelled':
                                        $badgeClass = 'badge-cancelled';
                                        break;
                                    case 'completed':
                                        $badgeClass = 'badge-completed';
                                        break;
                                }

                                $displayStatus = ($status === 'upcoming') ? 'Not yet started' : ucfirst($status);
                                ?>

                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $displayStatus; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row g-0 bg-white p-0 m-0" style="border: none !important; box-shadow: none;">
                                    <?php
                                    // Convert start date to "Month day, Year" format
                                    $startDate = !empty($project['start_date'])
                                        ? date("F j, Y", strtotime($project['start_date']))
                                        : 'N/A';

                                    // Convert end date the same way
                                    $endDate = !empty($project['end_date'])
                                        ? date("F j, Y", strtotime($project['end_date']))
                                        : 'N/A';
                                    ?>

                                    <div class="col-md-6">
                                        <p class="card-text mb-0"><?php echo htmlspecialchars($startDate); ?></p>
                                        <label class="project-form-label text-muted mt-0">Start Date</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text mb-0"><?php echo htmlspecialchars($endDate); ?></p>
                                        <label class="form-label text-muted mt-0">End Date</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text mb-0 mt-3">₱ <?php echo number_format(floatval(str_replace(',', '', $project['projected_budget_cost'])), 2); ?></p>
                                        <label class="form-label text-muted mt-0">Project Value</label>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text mb-0 mt-3"><?php echo htmlspecialchars($project['project_type']); ?></p>
                                        <label class="form-label text-muted mt-0">Area</label>
                                    </div>
                                </div>

                                <hr class="mt-3 mb-3">

                                <!-- User & Finance Approval -->
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-square-user-icon me-2">
                                            <rect width="18" height="18" x="3" y="3" rx="2" />
                                            <circle cx="12" cy="10" r="3" />
                                            <path d="M7 21v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
                                        </svg>
                                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($client_name); ?></p>
                                    </div>
                                    <?php if (($project['user_approval'] ?? 0) == 1): ?>
                                        <span class="badge badge-user-approved mt-0">
                                            <i class="bi bi-check-circle-fill me-1"></i>Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-user-pending mt-0">
                                            <i class="bi bi-clock-fill me-1"></i>Pending
                                        </span>
                                    <?php endif; ?>

                                </div>
                                <small class="text-muted">Client | <?php echo htmlspecialchars($client_name); ?></small><br>

                                <hr class="mt-3 mb-3">

                                <!-- Finance Approval -->
                                <?php
                                // Fetch the name of the finance user with the role 'finance'
                                $finance_name = 'Finance';
                                $finance_query = "SELECT name FROM users WHERE role = 'finance' LIMIT 1";
                                $finance_result = mysqli_query($conn, $finance_query);
                                if ($finance_row = mysqli_fetch_assoc($finance_result)) {
                                    $finance_name = $finance_row['name'];
                                }
                                ?>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-square-user-icon me-2">
                                            <rect width="18" height="18" x="3" y="3" rx="2" />
                                            <circle cx="12" cy="10" r="3" />
                                            <path d="M7 21v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
                                        </svg>
                                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($finance_name); ?></p>
                                    </div>
                                    <?php if (($project['finance_approval'] ?? 0) == 1): ?>
                                        <span class="badge badge-user-approved mt-0">
                                            <i class="bi bi-check-circle-fill me-1"></i>Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-user-pending mt-0">
                                            <i class="bi bi-clock-fill me-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <small class="text-muted">Finance | RVR Squared Mechanical Engr. Services</small>

                                <!-- For Finance user only! -->
                                <?php if (
                                    isset($_SESSION['auth_user']['role']) &&
                                    $_SESSION['auth_user']['role'] === 'finance' &&
                                    ($project['finance_approval'] ?? 0) != 1
                                ): ?>

                                    <!-- Finance Approval Form -->
                                    <?php
                                    $poError = '';
                                    $poErrorMsg = '';

                                    if (isset($_SESSION['status'])) {
                                        $poError = 'is-invalid';
                                        $poErrorMsg = strip_tags($_SESSION['status']);
                                        unset($_SESSION['status']);
                                    }
                                    ?>

                                    <form method="POST" action="../forms_logic/approve_finance.php" class="mt-2" id="financeApproveForm">
                                        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">

                                        <!-- Description for Finance Approval -->
                                        <div class="input-group mt-3">
                                            <input type="text" class="form-control input-po <?php echo $poError; ?>" name="po_num" placeholder="Enter PO Number" required>
                                            <button class="btn btn-dark" type="button" data-bs-toggle="modal" data-bs-target="#approveConfirmModal">Approve</button>
                                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#poInfoModal">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- PO Info Modal -->
                                        <div class="modal fade" id="poInfoModal" tabindex="-1" aria-labelledby="poInfoModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered "> <!-- Centered and wider -->
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="poInfoModalLabel"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#9eeaf9" stroke="#087990
" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info">
                                                                <circle cx="12" cy="12" r="10" />
                                                                <path d="M12 16v-4" />
                                                                <path d="M12 8h.01" />
                                                            </svg>Finance Confirmation Instructions</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body ">
                                                        As a Finance Officer, please verify the project by entering the Purchase Order (P.O.) number provided by the Admin.
                                                        <br><br>Enter it below to confirm the project and proceed with the financial process.
                                                        This confirmation ensures that all financial details are accurate and ready for the next steps.
                                                    </div>
                                                    <div class="modal-footer mt-0" style="padding-bottom: 20px; border-top: none;">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($poErrorMsg): ?>
                                            <small class="text-danger ms-1"><?php echo $poErrorMsg; ?></small>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Approve Confirmation Modal -->
                                    <div class="modal fade" id="approveConfirmModal" tabindex="-1" aria-labelledby="approveConfirmModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="approveConfirmModalLabel">Confirm Approval</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to approve this project?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-success" id="confirmApproveBtn">Confirm</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        document.getElementById('confirmApproveBtn').addEventListener('click', function() {
                                            document.getElementById('financeApproveForm').submit();
                                        });
                                    </script>

                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contract Details -->
                        <div class="card" style="width: 100%; border-radius: 12px;">
                            <div class="card-body">
                                <h6 class="text-muted mb-3"><strong>Contract Details</strong></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php
                                    $proj_id = $project['project_id'];
                                    $contract_query = "SELECT file_path, uploaded_at FROM contracts WHERE project_id = '$proj_id' LIMIT 1";
                                    $contract_result = mysqli_query($conn, $contract_query);
                                    if ($contract = mysqli_fetch_assoc($contract_result)) {
                                        $fileName = basename($contract['file_path']);
                                        $uploadedAt = date("F j, Y", strtotime($contract['uploaded_at']));
                                    ?>
                                        <div class="contract-file">
                                            <a href="<?= htmlspecialchars($contract['file_path']) ?>" target="_blank" class="contract-link">
                                                <i class="bi bi-file-earmark-text-fill me-2"></i>
                                                <span class="contract-filename"><?= htmlspecialchars($fileName) ?></span>
                                            </a>
                                            <small class="contract-uploaded">Uploaded: <?= $uploadedAt ?></small>
                                        </div>
                                    <?php
                                    } else {
                                        echo '<span class="text-muted"><i class="bi bi-file-earmark-x me-1"></i>No Contract Uploaded</span>';
                                    }
                                    ?>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- Output Phase Date Ranges from PHP to JavaScript -->
                    <?php
                    $phaseDates = [];
                    $phaseCompletedDates = [];
                    $phase_query = mysqli_query($conn, "SELECT phase_name, start_date, end_date FROM project_phases WHERE project_id = $project_id");
                    while ($row = mysqli_fetch_assoc($phase_query)) {
                        $phaseDates[] = $row;
                        if ($row['end_date']) {
                            $phaseCompletedDates[$row['phase_name']] = $row['end_date'];
                        }
                    }
                    ?>

                    <!-- Right Column -->
                    <div class="right-column">
                        <!-- Project In Progress -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><strong><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up-icon lucide-trending-up">
                                            <path d="M16 7h6v6" />
                                            <path d="m22 7-8.5 8.5-5-5L2 17" />
                                        </svg>Overall Status</strong></h5>
                                <?php
                                // Fetch all phases for this project from project_phases table
                                $phases = [];
                                $phases_query = mysqli_query($conn, "SELECT phase_name, status FROM project_phases WHERE project_id = '$project_id'");
                                while ($row = mysqli_fetch_assoc($phases_query)) {
                                    $phases[$row['phase_name']] = $row['status'];
                                }

                                // Calculate initial progress
                                $phase_weights = [
                                    'Design & Permits' => 10,
                                    'Material Procurement' => 20,
                                    'Site Preparation' => 15,
                                    'Project Installation' => 40,
                                    'Final Inspection' => 15
                                ];
                                $progress_percent = 0;
                                foreach ($phase_weights as $phase_name => $weight) {
                                    if (($phases[$phase_name] ?? '') === 'Completed') {
                                        $progress_percent += $weight;
                                    }
                                }
                                ?>

                                <!-- Progress Bar with Project Phases -->
                                <div class="d-flex align-items-center mb-4">
                                    <div class="progress flex-grow-1" style="height: 30px;" role="progressbar">
                                        <div class="progress-bar custom-progress-bar" id="overallProgressBar"
                                            style="width: <?= $progress_percent ?>%; height: 100%; transition: width 0.5s ease-in-out;">
                                        </div>
                                    </div>
                                    <span id="progressText" class="ms-3 fs-1" style="min-width: 48px; font-weight: bold;"><?= $progress_percent ?>%</span>
                                </div>
                                <!-- Project Phases -->
                                <div class="card-title mb-3 mt-3"><strong>Project Phases</strong></div>
                                <div class="d-flex flex-column gap-3 phase-items">
                                    <div class="d-flex align-items-center justify-content-between ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-scroll-text-icon lucide-scroll-text">
                                            <path d="M15 12h-5" />
                                            <path d="M15 8h-5" />
                                            <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                                            <path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3" />
                                        </svg>
                                        <span class="flex-grow-1 d-flex align-items-center justify-content-between">
                                            Design and Permits
                                            <?php if ($canEditPhases): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon"
                                                    role="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#logModalDesignPermits"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Add Progress Update">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon disabled"
                                                    style="opacity: 0.3; cursor: not-allowed;"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Available after project approval">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (!$canEditPhases): ?>
                                            <span class="badge badge-user-pending mt-0">
                                                <i class="bi bi-clock-fill me-1"></i>Waiting for Approval
                                            </span>
                                        <?php else: ?>
                                            <select id="design_permits" class="form-select form-select-sm w-auto phase-select"
                                                data-phase="Design & Permits">
                                                <option value="Pending" <?= ($phases['Design & Permits'] ?? 'Pending') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Progress" <?= ($phases['Design & Permits'] ?? 'Pending') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= ($phases['Design & Permits'] ?? 'Pending') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-baggage-claim-icon lucide-baggage-claim">
                                            <path d="M22 18H6a2 2 0 0 1-2-2V7a2 2 0 0 0-2-2" />
                                            <path d="M17 14V4a2 2 0 0 0-2-2h-1a2 2 0 0 0-2 2v10" />
                                            <rect width="13" height="8" x="8" y="6" rx="1" />
                                            <circle cx="18" cy="20" r="2" />
                                            <circle cx="9" cy="20" r="2" />
                                        </svg>
                                        <span class="flex-grow-1 d-flex align-items-center justify-content-between">
                                            Material Procurement
                                            <?php if ($canEditPhases): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon"
                                                    role="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#logModalMaterialProcurement"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Add Progress Update">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon disabled"
                                                    style="opacity: 0.3; cursor: not-allowed;"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Available after project approval">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (!$canEditPhases): ?>
                                            <span class="badge badge-user-pending mt-0">
                                                <i class="bi bi-clock-fill me-1"></i>Waiting for Approval
                                            </span>
                                        <?php else: ?>
                                            <select id="material_procurement" class="form-select form-select-sm w-auto phase-select"
                                                data-phase="Material Procurement"
                                                <?= !$canEditPhases ? 'disabled' : '' ?>>
                                                <option value="Pending" <?= ($phases['Material Procurement'] ?? 'Pending') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Progress" <?= ($phases['Material Procurement'] ?? 'Pending') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= ($phases['Material Procurement'] ?? 'Pending') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin-icon lucide-map-pin">
                                            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                                            <circle cx="12" cy="10" r="3" />
                                        </svg>
                                        <span class="flex-grow-1 d-flex align-items-center justify-content-between">
                                            Site Preparation
                                            <?php if ($canEditPhases): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon"
                                                    role="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#logModalSitePreparation"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Add Progress Update">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon disabled"
                                                    style="opacity: 0.3; cursor: not-allowed;"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Available after project approval">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (!$canEditPhases): ?>
                                            <span class="badge badge-user-pending mt-0">
                                                <i class="bi bi-clock-fill me-1"></i>Waiting for Approval
                                            </span>
                                        <?php else: ?>
                                            <select id="site_preparation" class="form-select form-select-sm w-auto phase-select"
                                                data-phase="Site Preparation"
                                                <?= !$canEditPhases ? 'disabled' : '' ?>>
                                                <option value="Pending" <?= ($phases['Site Preparation'] ?? 'Pending') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Progress" <?= ($phases['Site Preparation'] ?? 'Pending') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= ($phases['Site Preparation'] ?? 'Pending') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-construction-icon lucide-construction">
                                            <rect x="2" y="6" width="20" height="8" rx="1" />
                                            <path d="M17 14v7" />
                                            <path d="M7 14v7" />
                                            <path d="M17 3v3" />
                                            <path d="M7 3v3" />
                                            <path d="M10 14 2.3 6.3" />
                                            <path d="m14 6 7.7 7.7" />
                                            <path d="m8 6 8 8" />
                                        </svg>
                                        <span class="flex-grow-1 d-flex align-items-center justify-content-between">
                                            Project Installation
                                            <?php if ($canEditPhases): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon"
                                                    role="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#logModalProjectInstallation"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Add Progress Update">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon disabled"
                                                    style="opacity: 0.3; cursor: not-allowed;"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Available after project approval">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (!$canEditPhases): ?>
                                            <span class="badge badge-user-pending mt-0">
                                                <i class="bi bi-clock-fill me-1"></i>Waiting for Approval
                                            </span>
                                        <?php else: ?>
                                            <select id="project_installation" class="form-select form-select-sm w-auto phase-select"
                                                data-phase="Project Installation"
                                                <?= !$canEditPhases ? 'disabled' : '' ?>>
                                                <option value="Pending" <?= ($phases['Project Installation'] ?? 'Pending') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Progress" <?= ($phases['Project Installation'] ?? 'Pending') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= ($phases['Project Installation'] ?? 'Pending') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hard-hat-icon lucide-hard-hat">
                                            <path d="M10 10V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5" />
                                            <path d="M14 6a6 6 0 0 1 6 6v3" />
                                            <path d="M4 15v-3a6 6 0 0 1 6-6" />
                                            <rect x="2" y="15" width="20" height="4" rx="1" />
                                        </svg>
                                        <span class="flex-grow-1 d-flex align-items-center justify-content-between">
                                            Final Inspection
                                            <?php if ($canEditPhases): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon"
                                                    role="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#logModalFinalInspection"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Add Progress Update">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    width="20" height="20"
                                                    viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-square-pen update-icon disabled"
                                                    style="opacity: 0.3; cursor: not-allowed;"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Available after project approval">
                                                    <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (!$canEditPhases): ?>
                                            <span class="badge badge-user-pending mt-0">
                                                <i class="bi bi-clock-fill me-1"></i>Waiting for Approval
                                            </span>
                                        <?php else: ?>
                                            <select id="final_inspection" class="form-select form-select-sm w-auto phase-select"
                                                data-phase="Final Inspection"
                                                <?= !$canEditPhases ? 'disabled' : '' ?>>
                                                <option value="Pending" <?= ($phases['Final Inspection'] ?? 'Pending') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Progress" <?= ($phases['Final Inspection'] ?? 'Pending') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= ($phases['Final Inspection'] ?? 'Pending') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                            tooltipTriggerList.map(function(tooltipTriggerEl) {
                                return new bootstrap.Tooltip(tooltipTriggerEl)
                            })
                        });
                    </script>


                    <?php include('../resources/modals/project_phases_modals.php'); ?>

                    <script>
                        const phaseDateRanges = <?= json_encode($phaseDates) ?>;
                    </script>
                </div>

                <!-- Success Modal -->
                <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white border-0 shadow-sm">
                            <div class="modal-header border-0 ">
                                <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            </div>
                            <div class="modal-body text-center" style="padding-top: 10px;">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 90px; margin-bottom: 50px;"></i>

                                <h5 class="text-success mb-3">Progress Update Submitted!</h5>
                                <p>Your update has been saved successfully.</p>
                                <div class="modal-footer border-0 d-flex justify-content-center">
                                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Proceed</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Double Validation Confirmation Modal -->
                <div class="modal fade" id="confirmCompletionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="confirmCompletionModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content custom-modal-content">
                            <div class="modal-header border-0"></div>
                            <div class="modal-body text-center custom-modal-body">
                                <i class="bi bi-exclamation-circle-fill modal-icon-warning"></i>
                                <h4 class="modal-title-text">Confirm Phase Completion</h4>
                                <p class="modal-description">
                                    Are you sure you want to mark this phase as <strong>Completed</strong>? <br>
                                </p>
                                <div class="modal-footer border-0 d-flex justify-content-center custom-modal-footer">
                                    <button type="button" class="btn  btn-sm custom-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn  btn-sm custom-btn-confirm" id="confirmCompletionBtn">Yes, Complete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phase Warning Modal -->
                <div class="modal fade" id="phaseWarningModal" tabindex="-1" aria-labelledby="phaseWarningModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white border-0 shadow-sm">
                            <div class="modal-header border-0">
                                <!-- Optional close button -->
                                <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            </div>
                            <div class="modal-body text-center" style="padding-top: 10px;">
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 90px; "></i>

                                <h4 class="text-warning mb-3">Warning!</h4>
                                <div class="fs-6">
                                    Please ensure all preceding project phases are marked as completed before attempting to complete this phase.
                                </div>

                                <div class="modal-footer border-0 d-flex justify-content-center">
                                    <button type="button" class="btn btn-warning text-white" data-bs-dismiss="modal">Okay</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .phase-pending {
                        background-color: #fff3cd !important;
                        color: #856404 !important;
                        border-color: #ffeeba !important;
                    }

                    .phase-inprogress {
                        background-color: #cce5ff !important;
                        color: #004085 !important;
                        border-color: #b8daff !important;
                    }

                    .phase-completed {
                        background-color: #d4edda !important;
                        color: #155724 !important;
                        border-color: #c3e6cb !important;
                    }
                </style>

                <script>
                    // Flag to prevent auto-updates during page initialization
                    let pageInitialized = false;

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

                        const dateTimeElement = document.getElementById('datetime');
                        if (dateTimeElement) {
                            dateTimeElement.innerHTML = now.toLocaleDateString('en-US', options);
                        }
                    }

                    // Update every second
                    setInterval(updateDateTime, 1000);
                    updateDateTime(); // Initial call

                    // percentage of each phase
                    const phaseWeights = {
                        "Design & Permits": 10,
                        "Material Procurement": 20,
                        "Site Preparation": 15,
                        "Project Installation": 40,
                        "Final Inspection": 15
                    };

                    function updateProgressBar() {
                        let progressPercent = 0;
                        document.querySelectorAll('.phase-select').forEach(select => {
                            const phaseName = select.getAttribute('data-phase');
                            if (select.value === 'Completed' && phaseWeights[phaseName]) {
                                progressPercent += phaseWeights[phaseName];
                            }
                        });

                        const progressBar = document.getElementById('overallProgressBar');
                        const progressText = document.getElementById('progressText');
                        if (progressBar && progressText) {
                            progressBar.style.width = progressPercent + '%';
                            progressText.textContent = progressPercent + '%';
                        }
                    }

                    function updatePhaseSelectColors() {
                        document.querySelectorAll('.phase-select').forEach(function(select) {
                            select.classList.remove('phase-pending', 'phase-inprogress', 'phase-completed');
                            if (select.value === 'Pending') {
                                select.classList.add('phase-pending');
                            } else if (select.value === 'In Progress') {
                                select.classList.add('phase-inprogress');
                            } else if (select.value === 'Completed') {
                                select.classList.add('phase-completed');
                            }
                        });
                    }

                    document.addEventListener('DOMContentLoaded', function() {
    // Set initial status for each select to track Completed state
    document.querySelectorAll('.phase-select').forEach(select => {
        select.setAttribute('data-initial-status', select.value);
    });

    const phases = [
        "design_permits",
        "material_procurement",
        "site_preparation",
        "project_installation",
        "final_inspection"
    ];

    let selectedPhase = null;
    let selectedStatus = null;
    let selectedSelect = null;
    let projectId = <?= json_encode($project_id) ?>;

    // Attach change event to all phase selects
    document.querySelectorAll('.phase-select').forEach((select, index) => {
        select.addEventListener('change', function(event) {
            const phaseName = this.getAttribute('data-phase');
            const status = this.value;

            // Check if Site Preparation is completed
            const sitePrep = document.getElementById('site_preparation');
            const isSitePrepCompleted = sitePrep && sitePrep.value === "Completed";

            // Restriction: Project Installation and Final Inspection cannot be "In Progress" 
            // once Site Preparation is completed
            if (isSitePrepCompleted && 
                (phaseName === "Project Installation" || phaseName === "Final Inspection") && 
                status === "In Progress") {
                
                // Show warning and revert
                this.value = this.getAttribute('data-initial-status') || "Pending";
                updatePhaseSelectColors();
                updateProgressBar();
                
                const warningModal = new bootstrap.Modal(document.getElementById('phaseWarningModal'));
                const warningMessage = document.querySelector('#phaseWarningModal .modal-body div');
                if (warningMessage) {
                    warningMessage.innerHTML = 
                        `<strong>${phaseName}</strong> cannot be set to "In Progress" once Site Preparation is completed. ` +
                        `Please mark it as either "Pending" or "Completed".`;
                }
                warningModal.show();
                return;
            }

            // Phase sequence validation for "Completed" status
            if (status === "Completed") {
                let blocked = false;
                for (let i = 0; i < index; i++) {
                    const prevPhase = document.getElementById(phases[i]);
                    if (prevPhase && prevPhase.value !== "Completed") {
                        blocked = true;
                        break;
                    }
                }
                if (blocked) {
                    // Show warning modal and revert
                    this.value = this.getAttribute('data-initial-status') || "Pending";
                    updatePhaseSelectColors();
                    updateProgressBar();
                    const warningModal = new bootstrap.Modal(document.getElementById('phaseWarningModal'));
                    const warningMessage = document.querySelector('#phaseWarningModal .modal-body div');
                    if (warningMessage) {
                        warningMessage.innerHTML = 
                            'Please ensure all preceding project phases are marked as completed before attempting to complete this phase.';
                    }
                    warningModal.show();
                    return;
                }

                // Show confirmation modal
                selectedPhase = phaseName;
                selectedStatus = status;
                selectedSelect = this;

                // Revert selection until confirmed
                this.value = this.getAttribute('data-initial-status') || "Pending";
                updatePhaseSelectColors();
                updateProgressBar();

                const confirmModal = new bootstrap.Modal(document.getElementById('confirmCompletionModal'));
                confirmModal.show();

                // Attach one-time handler for confirmation
                const confirmBtn = document.getElementById('confirmCompletionBtn');
                // Remove previous handlers
                confirmBtn.onclick = null;
                confirmBtn.onclick = function() {
                    confirmModal.hide();
                    proceedPhaseUpdate(selectedSelect, projectId, selectedPhase, selectedStatus);
                };
                return;
            }

            // For Pending/In Progress, update immediately
            proceedPhaseUpdate(this, projectId, phaseName, status);
        });
    });

    function proceedPhaseUpdate(selectElement, projectId, phaseName, status) {
        fetch('../forms_logic/update_phase_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    project_id: projectId,
                    phase_name: phaseName,
                    status: status
                })
            })
            .then(response => response.text())
            .then(data => {
                // Update select and UI
                selectElement.value = status;
                selectElement.setAttribute('data-initial-status', status);
                updatePhaseSelectColors();
                updateProgressBar();
            })
            .catch(error => {
                // Revert on error
                selectElement.value = selectElement.getAttribute('data-initial-status') || "Pending";
                updatePhaseSelectColors();
                updateProgressBar();
                const errorModal = new bootstrap.Modal(document.getElementById('phaseWarningModal'));
                const warningMessage = document.querySelector('#phaseWarningModal .modal-body div');
                if (warningMessage) {
                    warningMessage.innerHTML = 
                        'Something went wrong while updating phase. Please try again later.';
                }
                errorModal.show();
            });
    }

    // Initial setup
    updatePhaseSelectColors();
    updateProgressBar();
});
                </script>
</body>

</html>

</html>