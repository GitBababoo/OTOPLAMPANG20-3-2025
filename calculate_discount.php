<?php
session_start(); // Start the session (if not already started)
include "inc/db_config.php";
include "inc/functions.php";
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $discount_id = isset($_POST['discount_id']) ? intval($_POST['discount_id']) : 0;
    $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;

    if ($discount_id <= 0 || $total_price <= 0) {
        $response = array("error" => "Invalid data.");
        echo json_encode($response);
        exit;
    }
    // Fetch discount data from database
    $sql_discount = "SELECT * FROM discounts WHERE id = ?";
    $stmt_discount = $conn->prepare($sql_discount);
    $stmt_discount->bind_param("i", $discount_id);
    $stmt_discount->execute();
    $result_discount = $stmt_discount->get_result();

    if ($result_discount->num_rows <= 0) {
        $response = array("error" => "Discount not found.");
        echo json_encode($response);
        exit;
    }

    $discount = $result_discount->fetch_assoc();
    $discount_amount = 0;

    if ($total_price < $discount['min_spend']) {
        $response = array("error" => "Total price does not meet minimum spend requirement.");
        echo json_encode($response);
        exit;
    }

    if ($discount['discount_amount'] > 0) {
        $discount_amount = $discount['discount_amount'];
    } elseif ($discount['discount_percent'] > 0) {
        $discount_amount = ($total_price * $discount['discount_percent']) / 100;
    }

    $total_price_after_discount = max(0, $total_price - $discount_amount);

    $response = array(
        "discount_amount" => number_format($discount_amount, 2),
        "total_price_after_discount" => number_format($total_price_after_discount, 2)
    );

    echo json_encode($response);

} else {
    $response = array("error" => "Invalid request.");
    echo json_encode($response);
}
?>