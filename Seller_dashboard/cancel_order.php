<?php
session_start();
include "../inc/db_config.php";

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
//Check You user or not? :)//;
$user_id = $_SESSION['user_id'];
$sql_user_roles = "SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ?";
$stmt = $conn->prepare($sql_user_roles);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_roles = $stmt->get_result();

$is_seller = false;
while($row = $result_roles->fetch_assoc()) {
    if ($row['role_name'] == 'seller') {
        $is_seller = true;
        break;
    }
}

if (!$is_seller) {
    header("Location: ../unauthorized.php"); // Redirect if not seller
    exit();
}
$stmt->close();

if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];

    // Update the order status to "cancelled"
    $sql_cancel = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
    $stmt_cancel = $conn->prepare($sql_cancel);
    $stmt_cancel->bind_param("i", $order_id);

    if ($stmt_cancel->execute()) {
        // Redirect back to the manage_orders page
        header("Location: manage_orders.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการยกเลิกคำสั่งซื้อ: " . $conn->error;
    }
    $stmt_cancel->close(); // important can no hold that, after it :) Congratuation!
    $conn ->close();

} else {
    echo "ไม่พบ Order ID";
}
?>