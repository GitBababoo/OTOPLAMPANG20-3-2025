<?php
session_start();

include "inc/db_config.php";

// Check if cart_id is provided and user is logged in
if (isset($_GET['cart_id']) && is_numeric($_GET['cart_id']) && isset($_SESSION['user_id'])) {
    $cart_id = intval($_GET['cart_id']);

    // Prepare and execute the delete query
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        // Redirect back to the cart page
        header("Location: cart.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการลบสินค้าออกจากตะกร้า: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    // Redirect to cart page or display an error message
    header("Location: cart.php");
    exit();
}
?>ห