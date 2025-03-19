<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'test1');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8"); // Set character set to utf8

//Function to get user role
function getUserRole($conn, $user_id) {
    $sql = "SELECT r.role_name 
            FROM user_roles ur 
            INNER JOIN roles r ON ur.role_id = r.role_id 
            WHERE ur.user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error preparing statement: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute() === false) {
        error_log("Error executing statement: " . $stmt->error);
        return null;
    }
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["role_name"];
    } else {
        return null;
    }
    $stmt->close();
}
?>