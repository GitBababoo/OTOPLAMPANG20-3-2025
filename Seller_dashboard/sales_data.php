<?php
session_start();
include "../inc/db_config.php";
include "../inc/functions.php";

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Assuming you have a 'sellers' table with a 'user_id' column
$user_id = (int)$_SESSION['user_id'];
$sql_check_seller = "SELECT seller_id FROM sellers WHERE user_id = ?";
$stmt_check_seller = $conn->prepare($sql_check_seller);
$stmt_check_seller->bind_param("i", $user_id);
$stmt_check_seller->execute();
$result_check_seller = $stmt_check_seller->get_result();

if ($result_check_seller->num_rows == 0) {
    echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ <a href='../index.php'>กลับสู่หน้าหลัก</a>";
    exit();
}
$seller_id = (int)$result_check_seller->fetch_assoc()['seller_id'];
$stmt_check_seller->close();

// Prepare the SQL query to fetch detailed sales data
// เดิมที Query สรุปผลรวมรายวัน แต่ตอนนี้จะเปลี่ยนเป็นการแสดงผลแบบละเอียด
// แยกเป็นรายการสินค้าในแต่ละวัน

$sql = "SELECT
    DATE(o.created_at) AS sale_date,
    fp.name AS product_name,
    oi.quantity AS quantity_sold,
    oi.price AS product_price,  
    (oi.price * oi.quantity) AS item_revenue   
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN featured_products fp ON oi.product_id = fp.id
WHERE fp.seller_id = ?
ORDER BY o.created_at DESC
LIMIT 50"; // เพิ่ม LIMIT เพื่อป้องกันการดึงข้อมูลมากเกินไป

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$salesData = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $salesData[] = $row;
    }
}
$stmt->close();

?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ข้อมูลการขายแบบละเอียด</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #f8f9fa;
            }

            .seller-panel {
                padding: 20px;
            }

            .sidebar {
                width: 220px;
                padding: 15px;
                background-color: #fff;
                border-right: 1px solid #eee;
            }

            .content {
                padding: 20px;
            }

            .icon-link {
                margin-right: 5px; /* Space between icon and link text */
            }

            .full-width-card {
                width: 100%;
                margin-bottom: 15px;
            }

            .sales-data-item {
                margin-bottom: 10px;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .product-image {
                width: 75px;
                height: 75px;
                object-fit: cover;
                border-radius: 5px;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 0;
            }

            .table th,
            .table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                vertical-align: middle;
            }

            .table th {
                background-color: #f2f2f2;
                font-size: 14px;
            }

            .table td {
                font-size: 13px;
            }

            .action-buttons {
                white-space: nowrap;
            }
        </style>
    </head>
    <body>

    <?php include('navbar.php'); ?>

    <div class="container-fluid seller-panel">
        <div class="row">
            <?php include('_sidebar.php'); ?>

            <main role="main" class="col-md-9 content">
                <div class="container">
                    <h2>ข้อมูลการขายแบบละเอียด</h2>

                    <?php if (empty($salesData)): ?>
                        <p>ไม่มีข้อมูลการขาย</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>ราคาต่อหน่วย</th>
                                    <th>จำนวนที่ขาย</th>
                                    <th>รายได้จากสินค้า</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($salesData as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                        <td><?php echo number_format(htmlspecialchars($sale['product_price']), 2); ?></td>
                                        <td><?php echo htmlspecialchars($sale['quantity_sold']); ?></td>
                                        <td><?php echo number_format(htmlspecialchars($sale['item_revenue']), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    </body>
    </html>

<?php $conn->close(); ?>