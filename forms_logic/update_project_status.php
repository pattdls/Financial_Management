<?php
$connection = mysqli_connect("localhost", "root", "", "financial_management");

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get today's date
$today = date("Y-m-d");

// Debugging: Output today's date
    "Today's date: $today<br>";

// Update project statuses
$query = "UPDATE projects 
          SET project_status = CASE
              WHEN project_status = 'Completed' THEN 'Completed' -- Keep completed projects as is
              WHEN '$today' > end_date THEN 'Overdue'
              WHEN '$today' BETWEEN start_date AND end_date THEN 'Ongoing'
              ELSE 'Upcoming'
          END";

if (mysqli_query($connection, $query)) {
    "✅ Status updated! Rows affected: " . mysqli_affected_rows($connection) . "<br>";
} else {
     "❌ Error updating: " . mysqli_error($connection);
}


// Debugging: Output updated rows
 "<strong>Today:</strong> $today<br>";

$result = mysqli_query($connection, "SELECT project_name, start_date, end_date FROM projects");
while ($row = mysqli_fetch_assoc($result)) {
     "<strong>{$row['project_name']}</strong> — Start: {$row['start_date']} | End: {$row['end_date']}<br>";
}


mysqli_close($connection);
?>