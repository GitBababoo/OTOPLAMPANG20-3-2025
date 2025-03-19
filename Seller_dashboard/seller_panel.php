<?php
session_start();
include "../inc/db_config.php";

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page
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
    // If user is not a seller, redirect them or display an error message
    echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ <a href='../index.php'>กลับสู่หน้าหลัก</a>";
    exit();
}
//Seller ID Get Here!
$seller_id = (int)$result_check_seller->fetch_assoc()['seller_id'];

$stmt_check_seller->close();

// Function to sanitize data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Fetch seller data
$sql_seller = "SELECT * FROM sellers WHERE user_id = ?";
$stmt_seller = $conn->prepare($sql_seller);
$stmt_seller->bind_param("i", $user_id);
$stmt_seller->execute();
$result_seller = $stmt_seller->get_result();
$seller = $result_seller->fetch_assoc();
$stmt_seller->close();

// Fetch total product count for the seller
$sql_total_products = "SELECT COUNT(*) AS total FROM featured_products WHERE seller_id = ?";
$stmt_total_products = $conn->prepare($sql_total_products);
$stmt_total_products->bind_param("i", $seller_id);
$stmt_total_products->execute();
$result_total_products = $stmt_total_products->get_result();
$total_products = $result_total_products->fetch_assoc()['total'];
$stmt_total_products->close();

// Fetch total orders and revenue for the seller
$sql_sales_summary = "SELECT COUNT(DISTINCT o.id) AS total_orders, SUM(oi.quantity * oi.price) AS total_revenue
                      FROM orders o
                      JOIN order_items oi ON o.id = oi.order_id
                      JOIN featured_products fp ON oi.product_id = fp.id
                      WHERE fp.seller_id = ?";

$stmt_sales_summary = $conn->prepare($sql_sales_summary);
$stmt_sales_summary->bind_param("i", $seller_id);
$stmt_sales_summary->execute();
$result_sales_summary = $stmt_sales_summary->get_result();
$sales_summary = $result_sales_summary->fetch_assoc();

$total_orders = $sales_summary['total_orders'];
$total_revenue = $sales_summary['total_revenue'];
$stmt_sales_summary->close();

// Handle form submissions (e.g., updating seller information)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form data here
    // Example:
    if (isset($_POST['update_store_name'])) {
        $store_name = sanitize($_POST['store_name']);
        // Validate and update store name in the database
        $sql_update = "UPDATE sellers SET store_name = ? WHERE user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $store_name, $user_id);

        if ($stmt_update->execute()) {
            echo "<div class='alert alert-success'>แก้ไขชื่อร้านค้าสำเร็จ</div>";
            // Refresh seller data
            $sql_seller = "SELECT * FROM sellers WHERE user_id = ?";
            $stmt_seller = $conn->prepare($sql_seller);
            $stmt_seller->bind_param("i", $user_id);
            $stmt_seller->execute();
            $result_seller = $stmt_seller->get_result();
            $seller = $result_seller->fetch_assoc();
            $stmt_seller->close();
        } else {
            echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการแก้ไขชื่อร้านค้า: " . $stmt_update->error . "</div>";
        }

        $stmt_update->close();
    }
    // Add more form handling as needed
}
include 'navbar.php';
?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Seller Panel</title>
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

            /* Style the cards to take up the full available width */
            .full-width-card {
                width: 100%; /* Use the full width of its parent */
                margin-bottom: 15px; /* Space between the cards */
            }

            .sales-data-item {
                margin-bottom: 10px;
            }

        </style>
    </head>
    <body>
    <!-- Navbar (adjust include path if needed) -->


    <div class="container-fluid seller-panel">
        <div class="row">
            <!-- Sidebar -->
            <?php include('_sidebar.php'); ?>

            <!-- Content -->
            <main role="main" class="col-md-9 content">
                <h2>Seller Dashboard</h2>
                <div class="row">

                    <!-- Seller Information Card -->
                    <div class="col-md-6 full-width-card">
                        <div class="card">
                            <div class="card-header">
                                ข้อมูลร้านค้า
                            </div>
                            <div class="card-body">
                                <?php if ($seller): ?>
                                    <div class="mb-2"><i class="fas fa-store icon-link"></i>ชื่อร้านค้า: <?php echo htmlspecialchars($seller['store_name']); ?></div>
                                    <div class="mb-2"><i class="fas fa-envelope icon-link"></i>อีเมลร้านค้า: <?php echo htmlspecialchars($seller['seller_email']); ?></div>
                                    <div class="mb-2"><i class="fas fa-info-circle icon-link"></i>รายละเอียดร้านค้า: <?php echo htmlspecialchars($seller['store_description']); ?></div>
                                    <!-- Add more seller information as needed -->
                                    <hr>
                                    <h5 class="mt-3">แก้ไขชื่อร้านค้า</h5>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="store_name" class="form-label">ชื่อร้านค้าใหม่</label>
                                            <input type="text" class="form-control" id="store_name" name="store_name" value="<?php echo htmlspecialchars($seller['store_name']); ?>">
                                        </div>
                                        <button type="submit" name="update_store_name" class="btn btn-primary">อัปเดต</button>
                                    </form>
                                <?php else: ?>
                                    <p>ไม่พบข้อมูลร้านค้า</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary Card -->
                    <div class="col-md-6 full-width-card">
                        <div class="card">
                            <div class="card-header">
                                สรุปยอดขาย
                            </div>
                            <div class="card-body">
                                <div class="sales-data-item"><i class="fas fa-chart-bar icon-link"></i>ยอดขายทั้งหมด: <?php echo htmlspecialchars($total_orders); ?></div>
                                <div class="sales-data-item"><i class="fas fa-money-bill-wave icon-link"></i>รายได้ทั้งหมด: $<?php echo htmlspecialchars(number_format($total_revenue, 2)); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Products Card -->
                    <div class="col-md-6 full-width-card">
                        <div class="card">
                            <div class="card-header">
                                สินค้าทั้งหมด
                            </div>
                            <div class="card-body">
                                <i class="fas fa-box-open icon-link"></i>จำนวนสินค้าทั้งหมด: <?php echo htmlspecialchars($total_products); ?>
                            </div>
                        </div>
                    </div>
                    <!-- Additional Content Card (to take up space and prevent emptiness) -->
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
<?php $conn->close(); ?>