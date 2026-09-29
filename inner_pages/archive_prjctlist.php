<?php
include('../dbcon.php'); 
include('../layout/session_check.php'); 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Archive Records"; ?>
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
            $page_title = 'Archive Records';
            include '../layout/topnav.php';
            ?>

            <div class="container-fluid px-0">
                 <ul class="financial-nav mt-5 p-0 ">
                    <li class="nav-item">
                        <a class="nav-link active" href="archive_prjctlist.php">Project Expense Archives</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="company_exp_archives.php">Company Expense Archives</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_archive.php">Project List Archives</a>
                    </li>
                </ul>

                <div class=" p-0">
                    <hr>
                </div>
                    <!-- Project List -->
                    <div id="client-list" class="container-fluid table-bordered row mt-3">
                        <script>
                            $(document).ready(function() {
                                $('#projectsArchive').DataTable({
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
                                // Create toolbar container
                                var toolbar = $('<div id="toolbar_archive" class="d-flex align-items-center justify-content-between mb-0"></div>');

                                // Append elements in correct order
                                toolbar.append($('#projectsArchive_wrapper .dataTables_length'));
                                toolbar.append($('#dateRangeContainer'));
                                toolbar.append($('#projectsArchive_wrapper .dataTables_filter'));

                                // Insert toolbar before the table wrapper
                                $('#projectsArchive_wrapper').before(toolbar);
                            });
                        </script>
                        <div style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                            <ol class="breadcrumb mt-0 mb-3" id="projects-breadcrumb">
                                <li class="breadcrumb-item active" aria-current="page" id="projects-li">Projects with Archived Records</li>
                                <li class="breadcrumb-item d-none" id="details-li">Archived Expenses List</li>
                            </ol>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="projectsArchive" style="margin-top: 0 !important;">
                                <thead>
                                    <tr>
                                        <th scope="col">Project Name</th>
                                        <th scope="col">Client Name</th>
                                        <th scope="col">Archived Expenses (No.)</th>
                                        <th scope="col">Archived Payments (No.)</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php
                                    $conn =  mysqli_connect("localhost", "root", "", "financial_management");


                                    // To fetch projects with archived expenses
                                    $expense_projects_query = "SELECT DISTINCT project_id, client_id FROM expenses_archive ORDER BY project_id ASC";
                                    $expense_projects_result = mysqli_query($conn, $expense_projects_query);

                                    $projects = [];

                                    while ($proj = mysqli_fetch_assoc($expense_projects_result)) {
                                        $project_id = $proj['project_id'];
                                        $client_id  = $proj['client_id'];

                                        $expense_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_archives FROM expenses_archive WHERE project_id = '$project_id'"))['total_archives'] ?? 0;

                                        $projects[$project_id] = [
                                            'client_id' => $client_id,
                                            'expense_count' => $expense_count,
                                            'payment_count' => 0,
                                        ];
                                    }

                                    // To fetch projects with archived payments
                                    $payment_projects_query = "SELECT DISTINCT project_id, client_id FROM payment_archive ORDER BY project_id ASC";
                                    $payment_projects_result = mysqli_query($conn, $payment_projects_query);

                                    while ($proj = mysqli_fetch_assoc($payment_projects_result)){
                                        $project_id = $proj['project_id'];
                                        $client_id  = $proj['client_id'];  
                                        
                                        $payment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_archives FROM payment_archive WHERE project_id = '$project_id'"))['total_archives'] ?? 0;

                                        if (isset($projects[$project_id])) {
                                            // Project exists from expenses
                                            $projects[$project_id]['payment_count'] = $payment_count;
                                        } else {
                                            // Project only has payments
                                            $projects[$project_id] = [
                                                'client_id' => $client_id,
                                                'expense_count' => 0,
                                                'payment_count' => $payment_count,
                                            ];
                                        }
                                    }

                                    foreach ($projects as $project_id => $data) {
                                        $client_id = $data['client_id'];

                                        // Get client and project names
                                        $client_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT client_name FROM clients WHERE client_id = '$client_id'"))['client_name'] ?? 'N/A';
                                        $project_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT project_name FROM projects WHERE project_id = '$project_id'"))['project_name'] ?? 'N/A';
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project_name); ?></td>
                                            <td><?php echo htmlspecialchars($client_name); ?></td>
                                            <td><?php echo $data['expense_count']; ?></td>
                                            <td><?php echo $data['payment_count']; ?></td>
                                            <td class="actions-column">
                                                <a href="intro_archive.php?view_project=<?php echo $project_id; ?>">
                                                <button type="button" class="btn archive_action" title="View List">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 18">
                                                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                                    </svg>
                                                        Archived Expenses
                                                    </button>
                                                </a>
                                                <a href="payment_archive.php?view_project=<?php echo $project_id; ?>">
                                                <button type="button" class="btn archive_action1" title="View List">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 18">
                                                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                                    </svg>
                                                Archived Payments</button>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

            </div>

        </div>
    </div>

    <script src="../resources/js/projects.js"></script>

</body>
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

</html>