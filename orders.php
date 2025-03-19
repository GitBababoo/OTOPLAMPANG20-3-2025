<?php
session_start(); // Start the session if not already started

include "inc/db_config.php"; // Database configuration
include "inc/navbar.php"; // Navigation bar

// Check if order_id is provided in the URL
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    // Redirect to orders page if order_id is missing or invalid
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['order_id']); // Sanitize order_id

// Function to sanitize output data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Fetch order details
$sql = "SELECT orders.*, users.first_name, users.last_name, payment_methods.name AS payment_method_name
        FROM orders
        INNER JOIN users ON orders.user_id = users.user_id
        LEFT JOIN payment_methods ON orders.payment_method_id = payment_methods.id
        WHERE orders.id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Redirect to orders page if order not found
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();

// Fetch order items
$sql = "SELECT order_items.*, featured_products.name AS product_name, featured_products.image AS product_image
        FROM order_items
        INNER JOIN featured_products ON order_items.product_id = featured_products.id
        WHERE order_items.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>รายละเอียดคำสั่งซื้อ #<?php echo sanitize($order['id']); ?></title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <!-- Custom CSS -->
        <link href="css/style.css" rel="stylesheet">
    </head>
    <body class="bg-light">

    <!-- Navigation Bar -->
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <h2 class="mb-4">รายละเอียดคำสั่งซื้อ #<?php echo sanitize($order['id']); ?></h2>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>ข้อมูลคำสั่งซื้อ</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>ชื่อลูกค้า:</strong> <?php echo sanitize($order['first_name']) . ' ' . sanitize($order['last_name']); ?></p>
                        <p><strong>วันที่สั่งซื้อ:</strong> <?php echo sanitize($order['created_at']); ?></p>
                        <p><strong>สถานะ:</strong> <?php echo sanitize($order['status']); ?></p>
                        <p><strong>วิธีการชำระเงิน:</strong> <?php echo sanitize($order['payment_method_name'] ? $order['payment_method_name'] : 'ไม่ได้ระบุ'); ?></p>
                        <p><strong>สถานะการชำระเงิน:</strong> <?php echo sanitize($order['payment_status']); ?></p>
                        <p><strong>สถานะการจัดส่ง:</strong> <?php echo sanitize($order['shipping_status']); ?></p>
                        <p><strong>ราคารวม:</strong> $<?php echo sanitize($order['total_price']); ?></p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4>รายการสินค้า</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($items_result->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while ($item = $items_result->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <img src="<?php echo sanitize($item['product_image']); ?>" alt="<?php echo sanitize($item['product_name']); ?>" class="img-fluid">
                                            </div>
                                            <div class="col-md-9">
                                                <h5><?php echo sanitize($item['product_name']); ?></h5>
                                                <p>จำนวน: <?php echo sanitize($item['quantity']); ?></p>
                                                <p>ราคาต่อหน่วย: $<?php echo sanitize($item['price']); ?></p>
                                                <p>ราคารวม: $<?php echo sanitize($item['price'] * $item['quantity']); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p>ไม่มีรายการสินค้าในคำสั่งซื้อนี้</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- You can add additional information or actions here -->
                <div class="card">
                    <div class="card-header">
                        <h4>Actions</h4>
                    </div>
                    <div class="card-body">
                        <a href="orders.php" class="btn btn-secondary btn-block">กลับไปหน้าคำสั่งซื้อ</a>
                        <!-- Add other actions like "Contact Seller", "Report Problem", etc. -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>

<?php
$stmt->close();
$conn->close();
?>