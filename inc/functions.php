<?php

// Get the user's full name (from your previous code's logic)
function getUserFullName($conn, $user_id) {
    $sql_user = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        return sanitize($user['first_name']) . ' ' . sanitize($user['last_name']);
    } else {
        return "ไม่พบข้อมูล";
    }
    $stmt_user->close();
}

// A helper function to make database access easier (very important!)
function get_db_results($conn, $sql, $param_types = "", ...$params) {
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {  // Crucial error handling!
        error_log("Prepare failed: " . $conn->error);
        return false; // Or throw an exception.
    }

    if ($param_types != "") {
        $stmt->bind_param($param_types, ...$params);

    }


    if (!$stmt->execute()) { // Always Check Execution, VERY VERY IMPORTANT for DB interactions
        error_log("Execute failed: " . $stmt->error);
        return false; // Or throw an exception.
    }

    $result = $stmt->get_result();
    $stmt->close();
    return $result;

}
// Get Product Name By ID
function getProductNameById($conn, $productId) {
    $sql = "SELECT name FROM featured_products WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return "Prepare failed";
    }

    $stmt->bind_param("i", $productId);
    if (!$stmt->execute()) {
        error_log("Execute failed: ". $stmt->error);
    }

    $result =  $stmt->get_result();
    if ($result->num_rows>0){
        $row = $result->fetch_assoc();
        return $row['name'];
    } else {
        return  "Product Not Found";
    }

}

// Get username for Admin Panel
function getUsernameById($conn, $userId) {
    $sql = "SELECT username FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("getUsernameById - Prepare failed: " . $conn->error);
        return "Prepare failed";
    }

    $stmt->bind_param("i", $userId);

    if (!$stmt->execute()) {
        error_log("getUsernameById - Execute failed: " . $stmt->error);
        return "Execute failed";
    }

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row =  $result->fetch_assoc();
        return $row['username'];
    } else {
        return "Unknown User"; // Return "Unknown user" if user not found.
    }
    $stmt->close();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}


// Function to fetch seller ID from user ID (COPY จากโค้ดก่อนหน้า)
function get_seller_id_from_user_id($conn, $user_id) {
    $sql_check_seller = "SELECT seller_id FROM sellers WHERE user_id = ?";
    $stmt_check_seller = $conn->prepare($sql_check_seller);
    $stmt_check_seller->bind_param("i", $user_id);
    $stmt_check_seller->execute();
    $result_check_seller = $stmt_check_seller->get_result();

    if ($result_check_seller->num_rows == 0) {
        return null;
    } else {
        return (int)$result_check_seller->fetch_assoc()['seller_id'];
    }
    if($stmt_check_seller){
        $stmt_check_seller->close();
    }
}

// Function to check if user has a specific role (COPY จากโค้ดก่อนหน้า)
function check_user_role($conn, $user_id, $role_name) {
    $sql_check_role = "SELECT ur.user_id
                         FROM user_roles ur
                         JOIN roles r ON ur.role_id = r.role_id
                         WHERE ur.user_id = ? AND r.role_name = ?";

    $stmt_check_role = $conn->prepare($sql_check_role);
    $stmt_check_role->bind_param("is", $user_id, $role_name);
    $stmt_check_role->execute();
    $result_check_role = $stmt_check_role->get_result();
    if($stmt_check_role){
        $stmt_check_role->close();
    }
    return $result_check_role->num_rows > 0;
}
?>