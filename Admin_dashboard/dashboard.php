<?php
session_start();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบและเป็นแอดมิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit();
}

// ฟังก์ชันตรวจสอบว่าผู้ใช้เป็นแอดมิน (ใช้ซ้ำได้!)
function isAdmin($conn, $user_id) {
    $sql = "SELECT r.role_name FROM user_roles ur INNER JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row["role_name"] == 'admin') {
                return true;
            }
        }
    }
    return false;
}

require_once 'db_config.php'; // การเชื่อมต่อที่สอดคล้องกัน

if (!isAdmin($conn, $_SESSION['user_id'])) {
    echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    exit();
}

// --- KPIs ---

// จำนวนผู้ใช้ทั้งหมด
$sql_total_users = "SELECT COUNT(*) AS total FROM users";
$result_total_users = $conn->query($sql_total_users);
$total_users = $result_total_users->fetch_assoc()['total'];

// ผู้ใช้ใหม่วันนี้
$sql_new_users_today = "SELECT COUNT(*) AS new_today FROM users WHERE DATE(created_at) = CURDATE()";
$result_new_users_today = $conn->query($sql_new_users_today);
$new_users_today = $result_new_users_today->fetch_assoc()['new_today'];

// จำนวนคำสั่งซื้อทั้งหมด
$sql_total_orders = "SELECT COUNT(*) AS total FROM orders";
$result_total_orders = $conn->query($sql_total_orders);
$total_orders = $result_total_orders->fetch_assoc()['total'];

// ยอดขายรวม (ทั้งหมด)
$sql_total_sales = "SELECT SUM(total_price) AS total_sales FROM orders";
$result_total_sales = $conn->query($sql_total_sales);
$total_sales = $result_total_sales->fetch_assoc()['total_sales'];
$total_sales = number_format($total_sales, 2); // จัดรูปแบบให้สวยงาม

// ยอดขายวันนี้
$sql_today_sales = "SELECT SUM(total_price) AS today_sales FROM orders WHERE DATE(created_at) = CURDATE()";
$result_today_sales = $conn->query($sql_today_sales);
$today_sales = $result_today_sales->fetch_assoc()['today_sales'];
$today_sales = ($today_sales === null) ? 0 : number_format($today_sales, 2);  // จัดการค่า NULL ที่อาจเกิดขึ้น

// คำสั่งซื้อตามสถานะ (ใช้ prepared statement เพื่อความสอดคล้อง)
$sql_orders_by_status = "SELECT status, COUNT(*) AS count FROM orders GROUP BY status";
$stmt_orders_by_status = $conn->prepare($sql_orders_by_status);
$stmt_orders_by_status->execute();
$result_orders_by_status = $stmt_orders_by_status->get_result();
$orders_by_status = [];
while ($row = $result_orders_by_status->fetch_assoc()) {
    $orders_by_status[$row['status']] = $row['count'];
}

// จำนวนสินค้าทั้งหมด
$sql_total_products = "SELECT COUNT(*) AS total_products FROM featured_products";
$stmt_total_products = $conn->prepare($sql_total_products);
$stmt_total_products->execute();
$result_total_products = $stmt_total_products->get_result();
$total_products = $result_total_products->fetch_assoc()['total_products'];

// สินค้าที่เหลือน้อย (ตัวอย่าง: stock < 10)
$low_stock_threshold = 10;  // กำหนดเกณฑ์
$sql_low_stock = "SELECT COUNT(*) AS low_stock_count FROM featured_products WHERE stock < ?";
$stmt_low_stock = $conn->prepare($sql_low_stock);
$stmt_low_stock->bind_param("i", $low_stock_threshold);
$stmt_low_stock->execute();
$result_low_stock = $stmt_low_stock->get_result();
$low_stock_count = $result_low_stock->fetch_assoc()['low_stock_count'];

//สินค้าหมดสต็อก
$sql_out_of_stock = "SELECT COUNT(*) AS out_of_stock FROM featured_products WHERE stock = 0";
$result_out_of_stock = $conn->query($sql_out_of_stock);
$total_out_of_stock = $result_out_of_stock->fetch_assoc()['out_of_stock'];

//จำนวนผู้ขายทั้งหมด
$sql_total_sellers = "SELECT COUNT(*) AS total_sellers FROM sellers";
$result_total_sellers = $conn->query($sql_total_sellers);
$total_sellers = $result_total_sellers->fetch_assoc()['total_sellers'];

//ผู้ขายรออนุมัติ
$sql_total_sellers_inactive = "SELECT COUNT(*) AS total FROM sellers WHERE status = 'inactive'";
$result_total_sellers_inactive = $conn->query($sql_total_sellers_inactive);
$total_sellers_inactive = $result_total_sellers_inactive->fetch_assoc()['total'];


// --- คำสั่งซื้อล่าสุด (ตัวอย่าง) ---
$sql_recent_orders = "SELECT o.id, u.first_name, u.last_name, o.total_price, o.status, o.created_at
                        FROM orders o
                        INNER JOIN users u ON o.user_id = u.user_id
                        ORDER BY o.created_at DESC
                        LIMIT 5";  // จำกัดแค่ 5 รายการล่าสุด
$result_recent_orders = $conn->query($sql_recent_orders);


$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผงควบคุมผู้ดูแลระบบ</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Arial', sans-serif;
        }

        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
            position: sticky; /* ทำให้แถบด้านข้างอยู่กับที่ */
            top: 0; /* ติดกับด้านบน */
        }

        .sidebar a {
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            display: block;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .sidebar a.active { /* รูปแบบสำหรับลิงก์ที่ใช้งานอยู่ */
            background-color: #007bff;
            color: white;
        }

        .content {
            padding: 20px;
        }

        .summary-card {
            margin-bottom: 20px;
            border-radius: 8px; /* มุมโค้งมนเล็กน้อย */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* เงาที่ละเอียดอ่อน */

        }
        /* การปรับขนาดตามอุปกรณ์ */
        @media (max-width: 768px) {
            .sidebar {
                position: static; /* นำการตรึงออกบนหน้าจอขนาดเล็ก */
                height: auto; /* ปล่อยให้ปรับความสูง */
            }

        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
            <?php include 'sidebar.php'; ?>
        </nav>

        <!-- Content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 content">
            <h2>แผงควบคุมผู้ดูแลระบบ</h2>

            <!-- KPIs -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">จำนวนผู้ใช้ทั้งหมด</h5>
                            <p class="card-text"><i class="fas fa-users"></i> <?php echo $total_users; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">ผู้ใช้ใหม่วันนี้</h5>
                            <p class="card-text"><i class="fas fa-user-plus"></i> <?php echo $new_users_today; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">จำนวนคำสั่งซื้อทั้งหมด</h5>
                            <p class="card-text"><i class="fas fa-shopping-cart"></i> <?php echo $total_orders; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">ยอดขายรวม</h5>
                            <p class="card-text"><i class="fas fa-dollar-sign"></i> <?php echo $total_sales; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">ยอดขายวันนี้</h5>
                            <p class="card-text"><i class="fas fa-dollar-sign"></i> <?php echo $today_sales; ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">จำนวนสินค้าทั้งหมด</h5>
                            <p class="card-text"><i class="fas fa-box"></i> <?php echo $total_products;?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">สินค้าที่เหลือน้อย</h5>
                            <p class="card-text"><i class="fas fa-exclamation-triangle"></i> <?php echo $low_stock_count;?></p>

                            <?php if($low_stock_count > 0): ?>
                                <a href="low_stock.php" class="btn btn-sm btn-warning">ดูสินค้าที่เหลือน้อย</a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">สินค้าหมดสต็อก</h5>
                            <p class="card-text"><i class="fas fa-ban"></i> <?php echo $total_out_of_stock; ?></p>

                            <?php if($total_out_of_stock > 0): ?>
                                <a href="out_of_stock_products.php" class="btn btn-sm btn-danger">ดูสินค้าหมดสต็อก</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">จำนวนผู้ขายทั้งหมด</h5>
                            <p class="card-text"><i class="fas fa-store"></i>  <?php echo $total_sellers; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title">ผู้ขายรอการอนุมัติ</h5>
                            <p class="card-text"><i class="fas fa-user-check"></i><?php echo $total_sellers_inactive;?></p>

                            <?php if($total_sellers_inactive > 0): ?>
                                <a href="approve_seller.php" class="btn btn-sm btn-info">ดูผู้ขายที่รออนุมัติ</a>
                            <?php endif;?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Order Status Chart (using Chart.js) -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">

                    </div>
                </div>
                <!-- Recent Orders Table -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">คำสั่งซื้อล่าสุด</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>รหัสคำสั่งซื้อ</th>
                                        <th>ลูกค้า</th>
                                        <th>ยอดรวม</th>
                                        <th>สถานะ</th>
                                        <th>วันที่</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($row = $result_recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td><?php echo number_format($row['total_price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="admin_order_management.php" class="btn btn-primary">ดูคำสั่งซื้อทั้งหมด</a>  <!-- Link -->
                        </div>
                    </div>
                </div>

            </div>

            <a href="logout.php" class="btn btn-danger mt-3"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a> <!-- เพิ่ม mt-3 สำหรับเว้นระยะ -->
        </main>
    </div>
</div>

<!-- Bootstrap JS และ dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>



</body>
</html>