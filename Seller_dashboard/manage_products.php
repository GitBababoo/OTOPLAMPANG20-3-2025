<?php
session_start();
include "../inc/db_config.php";

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

// Function to sanitize data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Configuration
$products_per_page = 10;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$start_from = ($page - 1) * $products_per_page;

// Fetch products for the current seller (with pagination)
$sql = "SELECT * FROM featured_products WHERE seller_id = ? ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $seller_id, $start_from, $products_per_page);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$stmt->close();

// Count total products for pagination
$sql_count = "SELECT COUNT(*) AS total FROM featured_products WHERE seller_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $seller_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $products_per_page);
$stmt_count->close();
?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>จัดการสินค้า</title>
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
            .table-responsive { /* Ensure the table is responsive */
                overflow-x: auto;
            }
            /* Improve image display */
            .product-image {
                width: 75px;  /* Smaller image for better fit */
                height: 75px;
                object-fit: cover; /* Maintain aspect ratio and fill the space */
                border-radius: 5px; /* Optional: rounded corners */
            }
            .table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 0; /* Remove default bottom margin */
            }

            .table th,
            .table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                vertical-align: middle; /* Align text vertically */
            }

            .table th {
                background-color: #f2f2f2;
                font-size: 14px; /* Slightly smaller font size */
            }
            .table td {
                font-size: 13px;
            }
            .action-buttons {
                white-space: nowrap; /* Prevent buttons from wrapping */
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
                    <h2>จัดการสินค้า</h2>
                    <a href="add_product.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> เพิ่มสินค้า</a>

                    <?php if (empty($products)): ?>
                        <p>ไม่มีสินค้าในร้านของคุณ</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>รูปภาพ</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>ราคา</th>
                                    <th>สต็อก</th>
                                    <th>การกระทำ</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                                        <td>
                                            <img src="../uploads/สินค้า/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['price']); ?></td>
                                        <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                        <td class="action-buttons">
                                            <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> แก้ไข</a>
                                            <a href="delete_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณต้องการลบสินค้านี้ใช่หรือไม่?')"><i class="fas fa-trash"></i> ลบ</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($total_pages > 1): ?>
                                    <?php if ($page > 1): ?>
                                        <li class="page-item"><a class="page-link" href="manage_products.php?page=<?php echo $page - 1; ?>">ก่อนหน้า</a></li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="manage_products.php?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item"><a class="page-link" href="manage_products.php?page=<?php echo $page + 1; ?>">ถัดไป</a></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </ul>
                        </nav>

                    <?php endif; ?>

                </div>
            </main>

        </div>
    </div>
    </body>
    </html>

<?php $conn->close(); ?>