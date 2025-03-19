<?php
session_start();
include "inc/db_config.php";

// Function to sanitize data (prevent XSS)
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get data from the POST request
    $product_id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : 0; // Sanitize as integer, default to 0
    $comment = isset($_POST["comment"]) ? sanitize($_POST["comment"]) : ""; // Sanitize comment
    $rating = isset($_POST["rating"]) ? (int)$_POST["rating"] : 0; // Sanitize rating as integer, default to 0

    // Validate data
    if ($product_id <= 0) {
        echo json_encode(array("status" => "error", "message" => "รหัสสินค้าไม่ถูกต้อง"));
        exit();
    }
    if (empty($comment)) {
        echo json_encode(array("status" => "error", "message" => "กรุณาใส่ความคิดเห็น"));
        exit();
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(array("status" => "error", "message" => "คะแนนรีวิวไม่ถูกต้อง"));
        exit();
    }

    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id']; // Sanitize user ID
    } else {
        echo json_encode(array("status" => "error", "message" => "กรุณาเข้าสู่ระบบก่อนทำการรีวิว"));
        exit();
    }
    // Fetch seller_id from featured_products table using the product_id
    $sql_get_seller_id = "SELECT seller_id FROM featured_products WHERE id = ?";
    $stmt_get_seller_id = $conn->prepare($sql_get_seller_id);
    $stmt_get_seller_id->bind_param("i", $product_id);
    $stmt_get_seller_id->execute();
    $result_get_seller_id = $stmt_get_seller_id->get_result();

    if ($result_get_seller_id->num_rows > 0) {
        $row_seller = $result_get_seller_id->fetch_assoc();
        $seller_id = (int)$row_seller['seller_id']; // Sanitize the seller_id
    } else {
        // If the product is not found, return an error message
        echo json_encode(array("status" => "error", "message" => "ไม่พบสินค้านี้"));
        exit();
    }
    $stmt_get_seller_id->close();
    // Prepare SQL statement to insert the review (ป้องกัน SQL Injection)
    $sql = "INSERT INTO reviews (user_id, product_id, seller_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    // Bind parameters (i = integer, s = string)
    $stmt->bind_param("iiiss", $user_id, $product_id, $seller_id, $rating, $comment);

    // Execute the query
    if ($stmt->execute()) {
        // Review submitted successfully
        echo json_encode(array("status" => "success", "message" => "รีวิวของคุณถูกส่งเรียบร้อยแล้ว"));
    } else {
        // Error submitting review
        echo json_encode(array("status" => "error", "message" => "เกิดข้อผิดพลาดในการส่งรีวิว: " . $stmt->error));
    }

    // Close statement
    $stmt->close();

} else {
    // If not a POST request, return an error
    echo json_encode(array("status" => "error", "message" => "Invalid request method"));
}

// Close connection
$conn->close();
?>