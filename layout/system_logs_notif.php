<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$con = mysqli_connect("localhost", "root", "", "financial_management");

date_default_timezone_set('Asia/Manila');
function safe_max($arr) {
    $filtered = array_filter($arr);
    return !empty($filtered) ? max($filtered) : null;
}
$name = isset($_SESSION['auth_user']['name']) ? $_SESSION['auth_user']['name'] : "Guest";
$user_id = $_SESSION['auth_user']['user_id'] ?? 0; // Define user_id safely
$current_page = basename($_SERVER['PHP_SELF']); // Gets current page name
$archive_pages = ['intro_archive.php', 'company_exp_archives.php', 'payment_archive.php', 'project_archive.php'];
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
    $initial_latest_recording = safe_max([
        $latest_proj_expense,
        $latest_comp_expense,
        $latest_payment,
        $latest_chart_accounts,
        $latest_new_projects,
        $latest_new_clients,
        $latest_edits
    ]);

    $initial_latest_archive = safe_max([
        $latest_proj_expense_archive,
        $latest_proj_expense_restored,
        $latest_comp_expense_archive,
        $latest_comp_expense_restored,
        $latest_payment_archive,
        $latest_payment_restored
    ]);

    $initial_latest_sessions = safe_max([$latest_login, $latest_logout]);
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