<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$connection = mysqli_connect("localhost", "u570829513_php_rvrsmes", "u570829513_php_rvrsmesFMS1", "u570829513_php_rvrsmes");

if (!isset($_SESSION['auth_user']['id']) || !isset($_SESSION['auth_user']['role'])) {
    die("User not authenticated.");
}

$user_id = $_SESSION['auth_user']['id'] ?? null;
$user_role = $_SESSION['auth_user']['role'] ?? null;

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['save_changes'])) {
    $client_id = mysqli_real_escape_string($connection, $_POST['client_id']);
    $client_name = mysqli_real_escape_string($connection, $_POST['client_name']);
    // $email = isset($_POST['email']) ? mysqli_real_escape_string($connection, $_POST['email']) : '';
    // $phone_number = isset($_POST['phone_number']) ? mysqli_real_escape_string($connection, $_POST['phone_number']) : '';
    $street_address = mysqli_real_escape_string($connection, $_POST['street_address']);
    $zip_code = mysqli_real_escape_string($connection, $_POST['zip_code']);
    $province = mysqli_real_escape_string($connection, $_POST['province']);
    $city = mysqli_real_escape_string($connection, $_POST['city']);
    $barangay = mysqli_real_escape_string($connection, $_POST['barangay']);

    // Fetch existing client data for comparison
    $old_query = "SELECT * FROM clients WHERE client_id = $client_id";
    $old_result = mysqli_query($connection, $old_query);
    $old_data = mysqli_fetch_assoc($old_result);

    // Fetch old contacts data for comparison
    $old_contacts_query = "SELECT * FROM contacts WHERE client_id = $client_id ORDER BY id";
    $old_contacts_result = mysqli_query($connection, $old_contacts_query);
    $old_contacts = [];
    while ($row = mysqli_fetch_assoc($old_contacts_result)) {
        $old_contacts[] = $row;
    }
    // Fetch old client data
    $client_query = mysqli_query($connection, "SELECT email, phone_number FROM clients WHERE client_id = '$client_id'");
    $old_client = mysqli_fetch_assoc($client_query);

    // $email = isset($_POST['email']) ? mysqli_real_escape_string($connection, $_POST['email']) : '';
    // $phone_number = isset($_POST['phone_number']) ? mysqli_real_escape_string($connection, $_POST['phone_number']) : '';

    // Compare and log email
    if ($old_client && $old_client['email'] !== $email) {
        $log_msg = "Edited client email from {$old_client['email']} to {$email} ({$client_name})";
        $log_sql = "INSERT INTO edit_logs (user_id, client_id, role, activity, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($connection, $log_sql);
        mysqli_stmt_bind_param($stmt, "iiss", $user_id, $client_id, $user_role, $log_msg);
        mysqli_stmt_execute($stmt);
    }

    // Compare and log phone number
    if ($old_client && $old_client['phone_number'] !== $phone_number) {
        $log_msg = "Edited client phone number from {$old_client['phone_number']} to {$phone_number} ({$client_name})";
        $log_sql = "INSERT INTO edit_logs (user_id, client_id, role, activity, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($connection, $log_sql);
        mysqli_stmt_bind_param($stmt, "iiss", $user_id, $client_id, $user_role, $log_msg);
        mysqli_stmt_execute($stmt);
    }


    // Build list of changes for client data (exclude email and phone here, since handled in contacts)
    $changes = [];
    if ($client_name !== $old_data['client_name']) {
        $changes[] = "Edited client name from '{$old_data['client_name']}' to '{$client_name}'";
    }
    if ($street_address !== $old_data['street_address']) $changes[] = "Edited street address";
    if ($zip_code !== $old_data['zip_code']) $changes[] = "Edited zip code";
    if ($province !== $old_data['province']) $changes[] = "Edited province";
    if ($city !== $old_data['city']) $changes[] = "Edited city";
    if ($barangay !== $old_data['barangay']) $changes[] = "Edited barangay";


    // Update clients table
    $sql_client = "UPDATE clients SET 
            client_name = '$client_name',
            email = '$email',
            phone_number = '$phone_number',
            street_address = '$street_address',
            zip_code = '$zip_code',
            province = '$province',
            city = '$city',
            barangay = '$barangay'
            WHERE client_id = $client_id";

    if (!mysqli_query($connection, $sql_client)) {
        die("Error updating client record: " . mysqli_error($connection));
    }

    // Insert activity logs for client changes
    if (!empty($changes)) {
        foreach ($changes as $activity) {
            $formatted_activity = $activity . " (" . $client_name . ")";
            $log_sql = "INSERT INTO edit_logs (user_id, client_id, role, activity, timestamp) VALUES (?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($connection, $log_sql);
            mysqli_stmt_bind_param($stmt, "iiss", $user_id, $client_id, $user_role, $formatted_activity);
            mysqli_stmt_execute($stmt);
        }
    }

    // Process contacts - ONLY log changes for actually modified contacts
    if (
        isset($_POST['first_name']) && is_array($_POST['first_name']) &&
        isset($_POST['middle_name']) && is_array($_POST['middle_name']) &&
        isset($_POST['last_name']) && is_array($_POST['last_name']) &&
        isset($_POST['suffix_name']) && is_array($_POST['suffix_name']) &&
        isset($_POST['email']) && is_array($_POST['email']) &&
        isset($_POST['phone_number']) && is_array($_POST['phone_number']) &&
        isset($_POST['designation']) && is_array($_POST['designation'])
    ) {
        $first_names = $_POST['first_name'];
        $middle_names = $_POST['middle_name'];
        $last_names = $_POST['last_name'];
        $suffix_names = $_POST['suffix_name'];
        $emails = $_POST['email'];
        $phone_numbers = $_POST['phone_number'];
        $designations = $_POST['designation'];

        // First, check for changes and collect logs BEFORE deleting
        $contact_logs = [];

        for ($i = 0; $i < count($first_names); $i++) {
            $fn = mysqli_real_escape_string($connection, $first_names[$i]);
            $mn = mysqli_real_escape_string($connection, $middle_names[$i]);
            $ln = mysqli_real_escape_string($connection, $last_names[$i]);
            $sn = mysqli_real_escape_string($connection, $suffix_names[$i]);
            $em = mysqli_real_escape_string($connection, $emails[$i]);
            $pn = mysqli_real_escape_string($connection, $phone_numbers[$i]);
            $des = mysqli_real_escape_string($connection, $designations[$i]);

            // Check for differences if corresponding old contact exists
            if (isset($old_contacts[$i])) {
                $contact_changes = [];

                if ($fn !== $old_contacts[$i]['first_name']) $contact_changes[] = "first name";
                if ($mn !== $old_contacts[$i]['middle_name']) $contact_changes[] = "middle name";
                if ($ln !== $old_contacts[$i]['last_name']) $contact_changes[] = "last name";
                if ($sn !== $old_contacts[$i]['suffix_name']) $contact_changes[] = "suffix";
                if ($em !== $old_contacts[$i]['email']) $contact_changes[] = "email";
                if ($pn !== $old_contacts[$i]['phone_number']) $contact_changes[] = "phone number";
                if ($des !== $old_contacts[$i]['designation']) $contact_changes[] = "designation";

                // Only log if there were actual changes
                if (!empty($contact_changes)) {
                    $contact_fullname = trim("$fn $mn $ln $sn");
                    $activity = "Edited contact person ($contact_fullname): " . implode(", ", $contact_changes) . " ($client_name)";
                    $contact_logs[] = $activity;
                }
            } else {
                // This is a new contact
                $contact_fullname = trim("$fn $mn $ln $sn");
                $activity = "Added new contact person ($contact_fullname) - $des ($client_name)";
                $contact_logs[] = $activity;
            }
        }

        // Check for deleted contacts
        if (count($old_contacts) > count($first_names)) {
            for ($i = count($first_names); $i < count($old_contacts); $i++) {
                $old_contact = $old_contacts[$i];
                $deleted_fullname = trim("{$old_contact['first_name']} {$old_contact['middle_name']} {$old_contact['last_name']} {$old_contact['suffix_name']}");
                $activity = "Deleted contact person ($deleted_fullname) ($client_name)";
                $contact_logs[] = $activity;
            }
        }

        // Now delete and insert contacts (your original logic)
        $sql_delete_contacts = "DELETE FROM contacts WHERE client_id = $client_id";
        if (!mysqli_query($connection, $sql_delete_contacts)) {
            die("Error deleting old contacts: " . mysqli_error($connection));
        }

        // Insert new/updated contacts
        for ($i = 0; $i < count($first_names); $i++) {
            $fn = mysqli_real_escape_string($connection, $first_names[$i]);
            $mn = mysqli_real_escape_string($connection, $middle_names[$i]);
            $ln = mysqli_real_escape_string($connection, $last_names[$i]);
            $sn = mysqli_real_escape_string($connection, $suffix_names[$i]);
            $em = mysqli_real_escape_string($connection, $emails[$i]);
            $pn = mysqli_real_escape_string($connection, $phone_numbers[$i]);
            $des = mysqli_real_escape_string($connection, $designations[$i]);

            $sql_insert_contact = "INSERT INTO contacts (client_id, first_name, middle_name, last_name, suffix_name, email, phone_number, designation) VALUES 
                ($client_id, '$fn', '$mn', '$ln', '$sn', '$em', '$pn', '$des')";

            if (!mysqli_query($connection, $sql_insert_contact)) {
                die("Error inserting contact person: " . mysqli_error($connection));
            }
        }

        // Insert only the collected activity logs (only for actual changes)
        foreach ($contact_logs as $activity) {
            $log_sql = "INSERT INTO edit_logs (user_id, client_id, role, activity, timestamp) VALUES (?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($connection, $log_sql);

            if (!$stmt) {
                die("Prepare failed: " . mysqli_error($connection));
            }

            mysqli_stmt_bind_param($stmt, "iiss", $user_id, $client_id, $user_role, $activity);

            if (!mysqli_stmt_execute($stmt)) {
                die("Execute failed: " . mysqli_stmt_error($stmt));
            }
        }
    }

    // Redirect
    header('Location: ../inner_pages/clients.php?update=success');
    exit();
}
