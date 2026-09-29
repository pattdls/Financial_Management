<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$con = mysqli_connect("localhost", "root", "", "financial_management");

date_default_timezone_set('Asia/Manila');

$name = isset($_SESSION['auth_user']['name']) ? $_SESSION['auth_user']['name'] : "Guest";
$user_id = $_SESSION['auth_user']['user_id'] ?? 0; // Define user_id safely
$current_page = basename($_SERVER['PHP_SELF']); // Gets current page name
$archive_pages = ['archive_prjctlist.php', 'intro_archive.php', 'company_exp_archives.php', 'payment_archive.php', 'project_archive.php'];
$chart_accounts = ['intro_archive.php', 'company_exp_archives.php', 'payment_archive.php', 'project_archive.php'];
$logs_pages = ['exp_logs_list.php', 'arch_logs_list.php', 'session_logs_list.php', 'fs_access_logs.php'];


// Update last viewed session logs per page access
if ($current_page === 'session_logs_list.php') {
    $_SESSION['last_viewed_sessions'] = date('Y-m-d H:i:s');
}
if ($current_page === 'arch_logs_list.php') {
    $_SESSION['last_viewed_archive'] = date('Y-m-d H:i:s');
}
if ($current_page === 'exp_logs_list.php') {
    $_SESSION['last_viewed_recording'] = date('Y-m-d H:i:s');
}
if ($current_page === 'fs_access_logs.php') {
    $_SESSION['last_viewed_fsAccess'] = date('Y-m-d H:i:s');
}

// FETCH LATEST TIMESTAMPS
$tables = [
    'expenses' => 'created_at',
    'expenses_archive' => 'archived_at',
    'company_expense' => 'created_at',
    'company_archive' => 'archived_at',
    'payment_clients' => 'created_at',
    'payment_archive' => 'archived_at',
    'chart_accounts' => 'created_at',
    'projects' => 'created_at',
    'clients' => 'created_at',
    'edit_logs' => 'timestamp',
    'session_logs' => ['login_time', 'logout_time'],
    'fs_attempt_logs' => 'last_attempt_time'
];

// Helper function
function getLatest($con, $table, $column) {
    $result = mysqli_query($con, "SELECT MAX($column) AS latest FROM $table");
    $data = mysqli_fetch_assoc($result);
    return $data['latest'] ?? null;
}

// Collect latest timestamps
$latest_proj_expense = getLatest($con, 'expenses', 'created_at');
$latest_proj_expense_archive = getLatest($con, 'expenses_archive', 'archived_at');
$latest_proj_expense_restored = getLatest($con, 'expenses', 'restored_date');
$latest_comp_expense = getLatest($con, 'company_expense', 'created_at');
$latest_comp_expense_archive = getLatest($con, 'company_archive', 'archived_at');
$latest_comp_expense_restored = getLatest($con, 'company_expense', 'restored_date');
$latest_payment = getLatest($con, 'payment_clients', 'created_at');
$latest_payment_archive = getLatest($con, 'payment_archive', 'archived_at');
$latest_payment_restored = getLatest($con, 'payment_clients', 'restored_date');
$latest_chart_accounts = getLatest($con, 'chart_accounts', 'created_at');
$latest_new_projects = getLatest($con, 'projects', 'created_at');
$latest_new_clients = getLatest($con, 'clients', 'created_at');
$latest_edits = getLatest($con, 'edit_logs', 'timestamp');
$latest_login = getLatest($con, 'session_logs', 'login_time');
$latest_logout = getLatest($con, 'session_logs', 'logout_time');
$latest_fs_access = getLatest($con, 'fs_attempt_logs', 'last_attempt_time');

// Group them logically
$recording_array = array_filter([
    $latest_proj_expense,
    $latest_comp_expense,
    $latest_payment,
    $latest_chart_accounts,
    $latest_new_projects,
    $latest_new_clients,
    $latest_edits
]);
$latest_recording = !empty($recording_array) ? max($recording_array) : null;

$archive_array = array_filter([
    $latest_proj_expense_archive,
    $latest_proj_expense_restored,
    $latest_comp_expense_archive,
    $latest_comp_expense_restored,
    $latest_payment_archive,
    $latest_payment_restored
]);
$latest_archive = !empty($archive_array) ? max($archive_array) : null;

$sessions_array = array_filter([$latest_login, $latest_logout]);
$latest_sessions = !empty($sessions_array) ? max($sessions_array) : null;
$latest_fs = $latest_fs_access ?? null;


if (!isset($_SESSION["initialized_{$user_id}"])) {

    // Set timezone first
    date_default_timezone_set('Asia/Manila');

    // Fetch current latest timestamps from the DB to set as baseline
    $initial_latest_recording = max(array_filter([
        $latest_proj_expense,
        $latest_comp_expense,
        $latest_payment,
        $latest_chart_accounts,
        $latest_new_projects,
        $latest_new_clients,
        $latest_edits
    ]));

    $initial_latest_archive = max(array_filter([
        $latest_proj_expense_archive,
        $latest_proj_expense_restored,
        $latest_comp_expense_archive,
        $latest_comp_expense_restored,
        $latest_payment_archive,
        $latest_payment_restored
    ]));

    $initial_latest_sessions = max(array_filter([$latest_login, $latest_logout]));
    $initial_latest_fs = $latest_fs_access ?? date('Y-m-d H:i:s');

    // Initialize “last viewed” timestamps to current latest entries
    $_SESSION['last_viewed_recording'] = $initial_latest_recording ?: date('Y-m-d H:i:s');
    $_SESSION['last_viewed_archive'] = $initial_latest_archive ?: date('Y-m-d H:i:s');
    $_SESSION['last_viewed_sessions'] = $initial_latest_sessions
    ? date('Y-m-d H:i:s', strtotime($initial_latest_sessions) - 1)
    : date('Y-m-d H:i:s');
    $_SESSION['last_viewed_fsAccess'] = $initial_latest_fs ?: date('Y-m-d H:i:s');

    $_SESSION["initialized_{$user_id}"] = true;
}

// Compare timestamps
$last_viewed_recording = $_SESSION['last_viewed_recording'] ?? null;
$has_new_recording = $latest_recording && strtotime($latest_recording) > strtotime($last_viewed_recording);

$last_viewed_archive = $_SESSION['last_viewed_archive'] ?? null;
$has_new_archive = $latest_archive && strtotime($latest_archive) > strtotime($last_viewed_archive);

$last_viewed_sessions = $_SESSION['last_viewed_sessions'] ?? null;
$has_new_sessions = $latest_sessions && strtotime($latest_sessions) > strtotime($last_viewed_sessions);

$last_viewed_fsAccess = $_SESSION['last_viewed_fsAccess'] ?? null;
$has_new_fsAccess = $latest_fs && strtotime($latest_fs) > strtotime($last_viewed_fsAccess);

// Count function
function getCount($con, $table, $column, $last_viewed = null) {
    $where = $last_viewed ? "WHERE $column > '$last_viewed'" : "";
    $query = "SELECT COUNT(*) AS new_count FROM $table $where";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)($row['new_count'] ?? 0);
}

// Count only records after “last viewed” timestamp
$new_proj_expenses       = getCount($con, 'expenses', 'created_at', $last_viewed_recording);
$new_comp_expenses       = getCount($con, 'company_expense', 'created_at', $last_viewed_recording);
$new_client_payments     = getCount($con, 'payment_clients', 'created_at', $last_viewed_recording);
$new_chart_accounts      = getCount($con, 'chart_accounts', 'created_at', $last_viewed_recording);
$new_projects            = getCount($con, 'projects', 'created_at', $last_viewed_recording);
$new_clients             = getCount($con, 'clients', 'created_at', $last_viewed_recording);
$new_edits               = getCount($con, 'edit_logs', 'timestamp', $last_viewed_recording);
$new_sessions_login      = getCount($con, 'session_logs', 'login_time', $last_viewed_sessions);
$new_sessions_logout     = getCount($con, 'session_logs', 'logout_time', $last_viewed_sessions);
$new_proj_expense_archive = getCount($con, 'expenses_archive', 'archived_at', $last_viewed_archive);
$new_proj_expense_restored = getCount($con, 'expenses', 'restored_date', $last_viewed_archive);
$new_comp_expense_archive = getCount($con, 'company_archive', 'archived_at', $last_viewed_archive);
$new_comp_expense_restored = getCount($con, 'company_expense', 'restored_date', $last_viewed_archive);
$new_payment_archive     = getCount($con, 'payment_archive', 'archived_at', $last_viewed_archive);
$new_payment_restored    = getCount($con, 'payment_clients', 'restored_date', $last_viewed_archive);
$new_fs_access           = getCount($con, 'fs_attempt_logs', 'last_attempt_time', $last_viewed_fsAccess);

// Only show new activities after last viewed
$total_new = $new_proj_expenses 
           + $new_comp_expenses 
           + $new_client_payments 
           + $new_chart_accounts 
           + $new_projects 
           + $new_clients 
           + $new_edits 
           + $new_sessions_login 
           + $new_sessions_logout
           + $new_proj_expense_archive
           + $new_proj_expense_restored
           + $new_comp_expense_archive
           + $new_comp_expense_restored
           + $new_payment_archive
           + $new_payment_restored
           + $new_fs_access;

?>

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
  
<header class="page-header d-flex align-items-center justify-content-between p-2">
  <button class="toggle-btn" id="toggleNav">☰</button>
</header>
    
<div class="side-nav-container d-flex flex-column p-3 shadow-sm ">
    <script>
                const toggleBtn = document.getElementById("toggleNav");
                const sideNav = document.querySelector(".side-nav-container");
                const backdrop = document.querySelector(".sidenav-backdrop");

                toggleBtn.addEventListener("click", function () {
                sideNav.classList.toggle("active");
                toggleBtn.classList.toggle("active");

                  if (sideNav.classList.contains("active")) {
                      document.body.style.overflow = "hidden"; // disable scroll
                  } else {
                      document.body.style.overflow = ""; // restore scroll
                  }
                });

                // Close sidenav when backdrop is clicked
                backdrop.addEventListener("click", function () {
                sideNav.classList.remove("active");
                 toggleBtn.classList.remove("active");
                  document.body.style.overflow = "";
                });
            </script>
    <!-- Company Name -->
   <div class="company-name me-1">
        <img src="../resources/images/sidenav_logo.svg" alt="" width="90" height="90"><br>
        <strong><span class="companyName">RVR SMES</strong>
    </div>

    <!-- Side Nav Options -->
    <ul class="nav nav-pills flex-column mb-auto text-start">
        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] === 'user'): ?>
            <li class="nav-item mb-2">
                <a href="../user/dashboard.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'dashboard.php' ? 'active' : 'inactive'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                        <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                        <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    </svg>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
        <?php else: ?>
            <li class="nav-item mb-2">
                <a href="../index.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'index.php' ? 'active' : 'inactive'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                        <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                        <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    </svg>
                   <span class="nav-label"> Dashboard</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'user'): ?>
            <li class="nav-item mb-2">
                <a href="clients.php" class="nav-link d-flex align-items-center <?php echo in_array($current_page, ['clients.php', 'projects.php', 'proj_stat.php', 'proj_budget_sum.php', 'Income.php', 'project_details.php', 'journal.php', 'cashflow.php'])  ? 'active' : 'inactive'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round-icon lucide-users-round">
                        <path d="M18 21a8 8 0 0 0-16 0" />
                        <circle cx="10" cy="8" r="5" />
                        <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
                    </svg>
                    <span class="nav-label">Clients</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'user'): ?>
            <li class="nav-item mb-2">
                <a href="batch_expense.php" class="nav-link d-flex align-items-center <?php echo in_array($current_page, ['batch_expense.php', 'expenses.php', 'company_exp.php', 'payment.php', 'allocate_budget.php'])  ? 'active' : 'inactive'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-banknote-arrow-down-icon lucide-banknote-arrow-down">
                        <path d="M12 18H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5" />
                        <path d="m16 19 3 3 3-3" />
                        <path d="M18 12h.01" />
                        <path d="M19 16v6" />
                        <path d="M6 12h.01" />
                        <circle cx="12" cy="12" r="2" />
                    </svg>
                    <span class="nav-label">Transactions</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'user'): ?>
            <li class="nav-item mb-2">
                <a href="chartaccounts.php" class="nav-link sidebar-link d-flex align-items-center <?php echo ($current_page == 'chartaccounts.php') ? 'active' : 'inactive '; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-chart-gantt-icon lucide-square-chart-gantt">
                        <rect width="18" height="18" x="3" y="3" rx="2" />
                        <path d="M9 8h7" />
                        <path d="M8 12h6" />
                        <path d="M11 16h5" />
                    </svg>
                   <span class="nav-label"> Chart of Accounts</span>
                </a>
            </li>
        <?php endif; ?>
         <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'user'): ?>
            <li class="nav-item mb-2">
                <a href="overall_company.php" class="nav-link d-flex align-items-center <?php echo in_array($current_page, ['overall_company.php', 'income_stmntoverall.php'])  ? 'active nav-link' : 'inactive'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brick-wall-icon lucide-brick-wall">
                        <rect width="18" height="18" x="3" y="3" rx="2" />
                        <path d="M12 9v6" />
                        <path d="M16 15v6" />
                        <path d="M16 3v6" />
                        <path d="M3 15h18" />
                        <path d="M3 9h18" />
                        <path d="M8 15v6" />
                        <path d="M8 3v6" />
                    </svg>
                    <span class="nav-label">RVR SMES</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance' && $_SESSION['auth_user']['role'] !== 'user'): ?>
            <li class="nav-item mb-2">
                <a href="create_user.php" class="nav-link d-flex align-items-center <?php echo $current_page == 'create_user.php' ? 'active nav-link' : 'inactive nav-link'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-user-round-icon lucide-square-user-round">
                        <path d="M18 21a6 6 0 0 0-12 0" />
                        <circle cx="12" cy="11" r="4" />
                        <rect width="18" height="18" x="3" y="3" rx="2" />
                    </svg>
                    <span class="nav-label">Manage users</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'user'): ?>
            <li class="nav-item mb-2">
                <a href="archive_prjctlist.php" class="nav-link d-flex align-items-center <?php echo in_array($current_page, $archive_pages) ?  'active nav-link' : 'inactive nav-link'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-archive-icon lucide-archive">
                        <rect width="20" height="5" x="2" y="3" rx="1" />
                        <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                        <path d="M10 12h4" />
                    </svg>
                    <span class="nav-label">Archive Records</span>
                </a>
            </li>
        <?php endif; ?>
        <?php
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
            
        if (isset($_SESSION['auth_user']['role']) && $_SESSION['auth_user']['role'] !== 'finance' && $_SESSION['auth_user']['role'] !== 'user'): ?>
            <li class="nav-item mb-2">
                <a href="exp_logs_list.php" 
                class="nav-link d-flex align-items-center position-relative <?php echo in_array($current_page, $logs_pages) ? 'active nav-link' : 'inactive nav-link'; ?>">

                    <div class="icon-wrapper position-relative">
                        <?php if ($total_new > 0): ?>
                          <span class="notification-count">
                              <?php echo ($total_new > 99) ? '99+' : $total_new; ?>
                          </span>
                        <?php endif; ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-logs-icon lucide-logs">
                            <path d="M13 12h8"/>
                            <path d="M13 18h8"/>
                            <path d="M13 6h8"/>
                            <path d="M3 12h1"/>
                            <path d="M3 18h1"/>
                            <path d="M3 6h1"/>
                            <path d="M8 12h1"/>
                            <path d="M8 18h1"/>
                            <path d="M8 6h1"/>
                        </svg>
                    </div>
                    <span class="nav-label">System Logs</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <!-- <ul class="nav nav-pills flex-column text-start mb-3">
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
    </ul> -->

</div>
<div class="sidenav-backdrop"></div>
<style>
.icon-wrapper {
    position: relative;
    display: inline-block;
}

/*.notification-dot {*/
/*    position: absolute;*/
/*    top: -2px;   */
/*    left: -2px;  */
/*    width: 10px;*/
/*    height: 10px;*/
/*    background-color: red;*/
/*    border-radius: 50%;*/
/*}*/

.company-name {
  margin-top: 20px;
  text-align: center;
  margin-bottom: 17px;
  padding-left: 0 !important

}
.companyName{
    font-weight: normal !important;

}
.notification-count {
    position: absolute;
    top: -6px;
    left: -9px;
    background-color: #dc3545;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    border-radius: 50%;
    padding: 2px 6px;
    min-width: 18px;
    text-align: center;
    line-height: 1.2;
}
.toggle-btn {
  position: fixed;
  top: 15px;
  left: 15px;
  font-size: 24px;
  background: none;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 6px 10px;
  cursor: pointer;
  z-index: 2500 !important;
  display: none; /* hidden on desktop */
}

.sidenav-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
   z-index: 1000; 
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease;
    display: block;
  }
@media (min-width: 577px) and (max-width: 992px){
      /* For collapsable sidenav in tablets or smaller screens */
 .side-nav-container {
    position: fixed;
    height: 100vh;    
    width: 60px; 
    padding: 0.5rem;
    overflow: visible;
    z-index: 1001;   
  }
  /* normal width when clicked/toggled */
  .side-nav-container.active {
    width: 220px; 
    transform: translateX(0);
    box-shadow: 2px 0 10px rgba(0,0,0,0.5);
  }

  .toggle-btn {
    display: block;
    left: 10px;   
    margin-bottom: 0 !important;
  }

  
  .side-nav-container:not(.active) .nav-label {
    display: none;
    white-space: nowrap !important;
    overflow: hidden; 
    padding-right: 0;
  }

  
  .side-nav-container:not(.active) .nav-item {
    text-align: center;
    padding-left: none !important;
  }

  .side-nav-container:not(.active) .nav-link {
  display: flex;
  justify-content: center;   
  align-items: center;      
  padding: 10px 0;  
  }


  .side-nav-container:not(.active) svg {
    margin-left: 4px;  
    font-size: 20px;  
  }

  
  .side-nav-container:not(.active) .companyName {
    display: none !important;
  }

  .side-nav-container .company-name {
    display: flex;
    flex-direction: column;
    align-items: center; 
    margin-bottom: 10px;
    margin-top: 70px;
    padding-left: 0 !important;
  }

  .side-nav-container .company-name img {
    height: 40px;
    width: 40px;
    margin-top: 0;
  }
  .side-nav-container .nav-label {
    visibility: visible;
  }

  hr{
    z-index: -1;
  }
    /* When sidenav is active, show backdrop */
    .side-nav-container.active ~ .sidenav-backdrop {
      opacity: 1;
      visibility: visible;
      width: 100vw;   /* full width */
      height: 100vh;  /* full height */

    }
    .side-nav-container.active .companyName{
      text-align: center;
      margin-top: 0 !important;
      margin-bottom: 0 !important;
    }
      .side-nav-container.active .nav-label {
      display: block;
      visibility: visible;
  }
    
    .side-nav-container.active .company-name img{
      height: 90px;
      width: 90px;
      margin-top: 0 !important;
      margin-bottom: 0 !important;
    }
}
@media (max-width: 576px) {
  .side-nav-container {
    position: fixed;   /* overlay instead of taking space */
    top: 0;
    left: 0;
    width: 220px;
    height: 100vh;
    background: #222;
    transform: translateX(-100%); /* hide */
    transition: transform 0.3s ease;
    z-index: 1100;
  }

  .side-nav-container.active {
    transform: translateX(0); /* show */
  }

  .main-content {
    margin-left: 0 !important; /* take full width */
    width: 100%;
  }

  .toggle-btn {
    display: inline-block;
    background: none;
    border: none;
    font-size: 24px;
    padding-top: 50px;
    cursor: pointer;
    color: #3d679a;
    z-index: 2500; /* above sidebar */
  }

  .sidenav-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    background: rgba(0,0,0,0.4);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease;
    z-index: 1000;
  }
    .toggle-btn.active {
    display: block;
    color: #fff;
    margin-top: 0 !important;
    padding-top: 15px;
  }
  
  .side-nav-container:not(.active) .nav-label {
    display: none;
    white-space: nowrap !important;
    overflow: hidden; 
    padding-right: 0;
  }

  
  .side-nav-container:not(.active) .nav-item {
    text-align: center;
    padding-left: none !important;
  }

  .side-nav-container:not(.active) .nav-link {
  display: flex;
  justify-content: center;   
  align-items: center;      
  padding: 10px 0;  
}


.side-nav-container:not(.active) svg {
  margin-left: 4px;  
  font-size: 20px;  
}

.side-nav-container:not(.active) .companyName {
  display: none;
}

.side-nav-container .company-name {
  display: flex;
  flex-direction: column;
  align-items: center; 
  margin-bottom: 15px;
  margin-top: 60px;
}

.side-nav-container .company-name img {
  height: 30px;
  width: 30px;
  margin-top: 0;
}

hr{
  z-index: -1;
}
  /* When sidenav is active, show backdrop */
   .side-nav-container.active ~ .sidenav-backdrop {
    opacity: 1;
    visibility: visible;
     width: 100vw;   /* full width */
    height: 100vh;  /* full height */
  }
  .side-nav-container.active .companyName{
    text-align: center;
    margin-top: 20px;
  }
  .side-nav-container.active .company-name img{
    height: 40px;
    width: 40px;
    margin-top: 0;
    margin-bottom: 10px;
  }


}
</style>