<?php
session_start();

include "inc/db_config.php";
include "inc/functions.php";  // Include helper functions

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_full_name = getUserFullName($conn, $user_id);

$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";  // Get orders, newest first.
$result = get_db_results($conn, $sql, "i", $user_id);

?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ประวัติการสั่งซื้อ</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>
    <body>
    <?php include "inc/navbar.php"; ?>
    <div class="container mt-5">
        <h1>ประวัติการสั่งซื้อของคุณ <?php echo $user_full_name; ?></h1>

        <?php if ($result && $result->num_rows > 0): ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>สถานะการจัดส่ง</th>
                    <th>ราคารวม</th>
                    <th>Action</th>  <!-- Added Action column -->
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    $order_id = $row['id'];
                    $orderDate =  date('Y-m-d H:i:s', strtotime($row['created_at']));
                    $orderStatus = $row['shipping_status'];
                    $shipping_status_text = "";
                    switch ($orderStatus) { // Add Here Status Shipping
                        case 'pending':
                            $shipping_status_text = 'รอดำเนินการ';
                            break;
                        case 'packing':
                            $shipping_status_text = "กำลังจัดเตรียมสินค้า";
                            break;

                        case  'shipped':
                            $shipping_status_text = 'จัดส่งแล้ว';
                            break;
                        case 'arrived' :
                            $shipping_status_text = "ถึงปลายทาง";
                            break;
                        default:
                            $shipping_status_text = "Unknown Status"; // Best to make error case.

                    }


                    ?>

                    <tr>
                        <td><?php echo $order_id; ?></td>
                        <td><?php echo   $orderDate ; ?></td>
                        <td>
                            <?php echo $shipping_status_text;  ?></td>
                        <td><?php echo number_format($row['total_price'], 2); ?></td>
                        <td>
                            <a href="order_details.php?order_id=<?php echo $order_id; ?>" class="btn btn-info btn-sm">ดูรายละเอียด</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>ไม่มีประวัติการสั่งซื้อ.</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    </body>
    </html>
<?php $conn->close(); ?>