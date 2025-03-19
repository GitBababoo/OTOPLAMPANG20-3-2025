<?php
// mark_notification_read.php
session_start();
require_once 'db_config.php';  // Database connection

// Check if user is logged in (essential for security)
if (!isset($_SESSION['user_id'])) {
    echo "error"; // Or a more specific error message
    exit();
}

// Basic input validation.  Always validate!
if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    echo "error";
    exit();
}

$notification_id = (int)$_POST['notification_id'];  // Cast to integer!

// Update the notification to be read (using prepared statements)
$sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $notification_id);

if ($stmt->execute()) {
    echo "success"; // Return success to the AJAX call
} else {
    echo "error";  // Return error if the update fails
}

exit(); //  stop further execution

?>