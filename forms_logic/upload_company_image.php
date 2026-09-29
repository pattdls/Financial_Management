<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['auth_user']) || $_SESSION['auth_user']['role'] !== 'admin') {
    header('Location: ../unauthorized.php');
    exit;
}

if (isset($_POST['upload_company_image'])) {
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['image/jpeg', 'image/png'];
    $uploadDir = '../Uploads/company_images/';
    $companyId = $_SESSION['auth_user']['company_id'] ?? null;

    // Validate company ID
    if (!$companyId) {
        // Create a default company record if none exists
        $query = "INSERT INTO company_profile (company_name) VALUES ('Default Company')";
        if (mysqli_query($conn, $query)) {
            $companyId = mysqli_insert_id($conn);
            $_SESSION['auth_user']['company_id'] = $companyId; // Update session
        } else {
            $_SESSION['status'] = '<div class="alert alert-danger">Failed to create company record: ' . mysqli_error($conn) . '</div>';
            error_log('Failed to create company: ' . mysqli_error($conn));
            header('Location: ../forms/company_profile.php');
            exit;
        }
    }

    // Check if company exists
    $check_query = "SELECT id FROM company_profile WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    if (!$stmt) {
        $_SESSION['status'] = '<div class="alert alert-danger">Database prepare error: ' . mysqli_error($conn) . '</div>';
        header('Location: ../forms/company_profile.php');
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        $query = "INSERT INTO company_profile (id, company_name) VALUES (?, 'Default Company')";
        $stmt_create = mysqli_prepare($conn, $query);
        if (!$stmt_create) {
            $_SESSION['status'] = '<div class="alert alert-danger">Database prepare error (create): ' . mysqli_error($conn) . '</div>';
            error_log('Prepare error (create): ' . mysqli_error($conn));
            header('Location: ../forms/company_profile.php');
            exit;
        }
        mysqli_stmt_bind_param($stmt_create, 'i', $companyId);
        if (!mysqli_stmt_execute($stmt_create)) {
            $_SESSION['status'] = '<div class="alert alert-danger">Failed to create company record: ' . mysqli_error($conn) . '</div>';
            error_log('Failed to create company: ' . mysqli_error($conn));
            mysqli_stmt_close($stmt_create);
            header('Location: ../forms/company_profile.php');
            exit;
        }
        mysqli_stmt_close($stmt_create);
    } else {
        mysqli_stmt_close($stmt);
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['company_image'];
    $fileName = $file['name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];

    if ($fileError === UPLOAD_ERR_OK) {
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['status'] = '<div class="alert alert-danger">Only JPG and PNG files are allowed.</div>';
        } elseif ($fileSize > $maxFileSize) {
            $_SESSION['status'] = '<div class="alert alert-danger">File size exceeds 5MB limit.</div>';
        } else {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'company_' . $companyId . '_' . time() . '.' . $fileExtension;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpName, $destination)) {
                $query = "UPDATE company_profile SET company_image = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                if (!$stmt) {
                    $_SESSION['status'] = '<div class="alert alert-danger">Database prepare error: ' . mysqli_error($conn) . '</div>';
                    error_log('Prepare error: ' . mysqli_error($conn));
                    unlink($destination);
                    header('Location: ../forms/company_profile.php');
                    exit;
                }
                mysqli_stmt_bind_param($stmt, 'si', $destination, $companyId);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);

                    // Verify the update
                    $verify_query = "SELECT company_image FROM company_profile WHERE id = ?";
                    $stmt_verify = mysqli_prepare($conn, $verify_query);
                    if ($stmt_verify) {
                        mysqli_stmt_bind_param($stmt_verify, 'i', $companyId);
                        mysqli_stmt_execute($stmt_verify);
                        mysqli_stmt_bind_result($stmt_verify, $new_image);
                        mysqli_stmt_fetch($stmt_verify);
                        mysqli_stmt_close($stmt_verify);

                        if ($new_image === $destination) {
                            $_SESSION['status'] = '<div class="alert alert-success">Company logo uploaded successfully.</div>';

                            // Refresh company data in session
                            $company_query = "SELECT * FROM company_profile WHERE id = ?";
                            $stmt_refresh = mysqli_prepare($conn, $company_query);
                            if ($stmt_refresh) {
                                mysqli_stmt_bind_param($stmt_refresh, 'i', $companyId);
                                mysqli_stmt_execute($stmt_refresh);
                                $result = mysqli_stmt_get_result($stmt_refresh);
                                $_SESSION['company_data'] = mysqli_fetch_assoc($result);
                                mysqli_stmt_close($stmt_refresh);
                            }
                        } else {
                            $_SESSION['status'] = '<div class="alert alert-danger">Database update failed: Image path not saved.</div>';
                            error_log('Database update failed: Image path not saved for company ID ' . $companyId);
                            unlink($destination);
                        }
                    } else {
                        $_SESSION['status'] = '<div class="alert alert-danger">Database prepare error (verify): ' . mysqli_error($conn) . '</div>';
                        unlink($destination);
                    }
                } else {
                    $_SESSION['status'] = '<div class="alert alert-danger">Database update error: ' . mysqli_error($conn) . '</div>';
                    error_log('Database update error: ' . mysqli_error($conn));
                    mysqli_stmt_close($stmt);
                    unlink($destination);
                }
            } else {
                $_SESSION['status'] = '<div class="alert alert-danger">Failed to move uploaded file.</div>';
                error_log('File move failed for: ' . $destination);
            }
        }
    } else {
        $_SESSION['status'] = '<div class="alert alert-danger">Error uploading file: ' . $fileError . '</div>';
    }
} else {
    $_SESSION['status'] = '<div class="alert alert-danger">Invalid request.</div>';
}

header('Location: ../inner_pages/company_profile.php');
exit;
?>
