<?php
session_start();

include "inc/db_config.php";

// Function to sanitize input/output data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if order_id is set
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // ดึงข้อมูล order
    $sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
    } else {
        // Order not found or does not belong to the user
        header("Location: index.php"); // Redirect to homepage
        exit();
    }
} else {
    // No order_id provided
    header("Location: index.php"); // Redirect to homepage
    exit();
}
?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>สั่งซื้อสำเร็จ</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>
    <body>

    <!-- Navigation Bar -->
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <h2>สั่งซื้อสำเร็จ!</h2>
        <p>หมายเลขคำสั่งซื้อของคุณคือ: <?php echo sanitize($order['id']); ?></p>
        <p>ขอบคุณที่ใช้บริการ</p>
    </div>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>