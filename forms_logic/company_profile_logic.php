<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['auth_user']) || $_SESSION['auth_user']['role'] !== 'admin') {
    header('Location: ../unauthorized.php');
    exit;
}

if (isset($_POST['update_company'])) {
    $company_id = mysqli_real_escape_string($conn, $_POST['company_id'] ?? $_SESSION['auth_user']['company_id']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name'] ?? '');
    $business_reg_num = mysqli_real_escape_string($conn, $_POST['business_reg_num'] ?? '');
    $industry = mysqli_real_escape_string($conn, $_POST['industry'] ?? '');
    $company_size = mysqli_real_escape_string($conn, $_POST['company_size'] ?? '');
    $company_address = mysqli_real_escape_string($conn, $_POST['company_address'] ?? '');
    $company_phone = mysqli_real_escape_string($conn, $_POST['company_phone'] ?? '');
    $company_email = mysqli_real_escape_string($conn, $_POST['company_email'] ?? '');

    // Check if company exists
    $check_query = "SELECT id FROM company_profile WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    if (!$stmt) {
        $_SESSION['status'] = '<div class="alert alert-danger">Database error (check): ' . mysqli_error($conn) . '</div>';
        header('Location: ../forms/company_profile.php');
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'i', $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    if ($exists) {
        // Update existing company
        $query = "UPDATE company_profile SET 
            company_name = ?, 
            business_reg_num = ?, 
            industry = ?, 
            company_size = ?, 
            company_address = ?, 
            phone_number = ?, 
            email = ? 
            WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            $_SESSION['status'] = '<div class="alert alert-danger">Database error (update): ' . mysqli_error($conn) . '</div>';
            header('Location: ../forms/company_profile.php');
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'sssssssi', 
            $company_name, 
            $business_reg_num, 
            $industry, 
            $company_size, 
            $company_address, 
            $company_phone, 
            $company_email, 
            $company_id
        );
    } else {
        // Insert new company
        $query = "INSERT INTO company_profile (
            id, company_name, business_reg_num, industry, company_size, company_address, phone_number, email
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            $_SESSION['status'] = '<div class="alert alert-danger">Database error (insert): ' . mysqli_error($conn) . '</div>';
            header('Location: ../forms/company_profile.php');
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'isssssss', 
            $company_id, 
            $company_name, 
            $business_reg_num, 
            $industry, 
            $company_size, 
            $company_address, 
            $company_phone, 
            $company_email
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt); // Close before refresh

        $_SESSION['status'] = '<div class="alert alert-success">Company profile updated successfully.</div>';

        // Refresh company data
        $company_query = "SELECT * FROM company_profile WHERE id = ?";
        $stmt_refresh = mysqli_prepare($conn, $company_query);
        if ($stmt_refresh) {
            mysqli_stmt_bind_param($stmt_refresh, 'i', $company_id);
            mysqli_stmt_execute($stmt_refresh);
            $result = mysqli_stmt_get_result($stmt_refresh);
            $_SESSION['company_data'] = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt_refresh);
        }
    } else {
        $_SESSION['status'] = '<div class="alert alert-danger">Failed to update company profile: ' . mysqli_error($conn) . '</div>';
        mysqli_stmt_close($stmt);
    }
} else {
    $_SESSION['status'] = '<div class="alert alert-danger">Invalid request.</div>';
}

header('Location: ../inner_pages/company_profile.php');
exit;
?>
