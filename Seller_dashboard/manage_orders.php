<?php
session_start();
include "../inc/db_config.php";
include "../inc/functions.php";
// Assuming the directory structure is correct as previously discussed.

// Check if user is logged in and is a seller or admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check user role (Admin can view all, Seller can view only their orders)
$user_id = (int)$_SESSION['user_id'];
$is_admin = check_user_role($conn, $user_id, 'admin');
$seller_id = null;

if (!$is_admin) {
    $seller_id = get_seller_id_from_user_id($conn, $user_id);
    if (!$seller_id) {
        echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
        exit();
    }
}

// --- Get Filters ---
//$filter_status = isset($_GET['status']) ? $_GET['status'] : '';  //REMOVED
$filter_seller_id = $is_admin && isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : ($seller_id !== null ? $seller_id : null); // Allow admin to filter by seller

// --- Build SQL Query ---
$sql_orders = "SELECT
    o.id AS order_id,
    o.created_at AS order_date,
    o.status AS order_status,
    o.shipping_status AS shipping_status,
    u.first_name AS customer_first_name,
    u.last_name AS customer_last_name,
    u.address AS customer_address,
    u.phone_number AS customer_phone,
    GROUP_CONCAT(fp.name SEPARATOR ', ') AS product_names,
    GROUP_CONCAT(fp.image SEPARATOR ', ') AS product_images,
    GROUP_CONCAT(oi.price SEPARATOR ', ') AS product_prices,
	o.total_price as order_total_price,
    o.seller_id AS seller_id -- ADD THIS LINE
FROM orders o
JOIN users u ON o.user_id = u.user_id
JOIN order_items oi ON o.id = oi.order_id
JOIN featured_products fp ON oi.product_id = fp.id ";

$where_clauses = [];
$param_types = "";
$params = [];

if ($filter_seller_id !== null) {
    $where_clauses[] = "o.seller_id = ?";
    $param_types .= "i";
    $params[] = &$filter_seller_id;
}

//if (!empty($filter_status)) { //REMOVED Entirely
//$where_clauses[] = "o.status = ?";
//$param_types .= "s";
//$params[] = &$filter_status;
//}

if (!empty($where_clauses)) {
    $sql_orders .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql_orders .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt_orders = $conn->prepare($sql_orders);
if ($stmt_orders) {

    //Bind parameters dynamically if there are any WHERE clauses.
    if (!empty($params)) {
        $stmt_orders->bind_param($param_types, ...$params); //Using the splat operator (...) to pass the array elements as individual parameters.
    }
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();

    $orders = [];
    if ($result_orders->num_rows > 0) {
        while ($row = $result_orders->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $stmt_orders->close();  // close

}
// Function to validate the status value.
function validateStatus($status) {
    $allowed_statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
    return in_array($status, $allowed_statuses) ? $status : null; // Return null for invalid values.
}
function validateShippingStatus($status) {
    $allowed_statuses = ['pending', 'packing', 'shipped', 'arrived']; // Added 'arrived'
    return in_array($status, $allowed_statuses) ? $status : null;
}
// Function to update Order status with validation and Prepared Statements to prevent SQL injection.
function updateOrderStatus($conn, $order_id, $new_status) {
    // Validate order_id as a first defense
    $order_id = (int)$order_id;

    $validated_status = validateStatus($new_status); // Call validateStatus for status.

    if ($validated_status !== null && $order_id > 0) {
        //  Prepare Statement to update.
        $sql_update = "UPDATE orders SET status = ? WHERE id = ?";

        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            // Bind  status and  orderId here.
            $stmt_update->bind_param("si", $validated_status, $order_id);

            $result = $stmt_update->execute();  // This now contains the execution state, i.e, True or false.

            $stmt_update->close();   // IMPORTANT - close statement AFTER execution

            return $result;  // To return status of successful operation here!
        } else {
            error_log("Error in preparing update statement:" . $conn->error);  // To track the error when the statement isn't actually successfully prepared!
            return false;  // Operation failure when PREPARE statement fails!
        }

    } else {
        error_log("Error: Invalid order_id or new_status"); // For log in actual!
        return false;    // return flag for NOT successfully operated function calls!
    }
}

// Function to update shipping status.
function updateShippingStatus($conn, $order_id, $new_shipping_status) {
    $order_id = (int)$order_id; //Validate the order id

    $validated_shipping_status = validateShippingStatus($new_shipping_status);

    if ($validated_shipping_status !== null && $order_id > 0) {

        $sql_update = "UPDATE orders SET shipping_status = ? WHERE id = ?";

        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param("si", $validated_shipping_status, $order_id); // Bind now and params.

            $result = $stmt_update->execute();  // Returns True on SUCCESS or  FALSE on failure.

            $stmt_update->close();     // IMPORTANT step after ANY execute: FREE resource!

            return $result;   // returns true or false , upon calling site code!

        } else {  // Handling failure  prepare states!
            error_log("Error in preparing update shipping statement: " . $conn->error);
            return false;     // failure handling in preparing step!!!
        }
    } else {     // If Invalid Order status occurs i, invalid ID!

        error_log("Error: Invalid order_id or new_shipping_status");
        return false;  // Inform that updating wasn't processed
    }
}
// Handle form submission for status update - This remains largely the same as in your shipping management.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['order_id']) && isset($_POST['new_order_status'])) {
        //  Call function now with parameters:
        if(updateOrderStatus($conn, $_POST['order_id'], $_POST['new_order_status'])) {    //Calling now our update status Order  method with  validated  inputs:  Also is a Success Condition!
            echo "<script>alert('Order Status Updated Successfully');</script>";
        }  else {

            echo "<script>alert('Order Status Update Operation Unsuccessful');</script>";   // If  error on validation , errors OR fails queries, alert on fail!.

        }

        echo "<script>window.location.href='manage_orders.php';</script>"; // Refresh

    }  elseif (isset($_POST['shipping_order_id']) && isset($_POST['new_shipping_status'])) {   // Handling of New code here.
        // Same structure from Above case but use shipping instead - The core idea , copy same validation approach!
        if(updateShippingStatus($conn, $_POST['shipping_order_id'], $_POST['new_shipping_status'])) {     // Successful action, updates state shipping for  POST values selected/passed and updated OK now!
            echo "<script>alert('Shipping Status Updated Successfully');</script>";       // Message shown as OK on alert window for updating
        } else {     //Failure: Show failed Update for all problems possibly, wrong IDs passed in etc OR issues the internal! db/ query-level failed!!!.

            echo "<script>alert('Shipping Status Operation Unsuccessful');</script>";   // Some error OR issues on status on failed submission due the errors to Update now .

        }
        echo "<script>window.location.href='manage_orders.php';</script>";
    }


}
?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>จัดการคำสั่งซื้อ</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> <!-- Add for icons -->

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
    <?php include "navbar.php"; ?>  <!-- Ensure  this in for navbar code - to correctly! -->
    <div class="container-fluid">
        <div class="row">
            <?php include "_sidebar.php"; ?> <!-- Include the sidebar - all of the correct way from folder structure -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">  <!-- Correct Class content: Set  to 9 + 10 to correctly-->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">จัดการคำสั่งซื้อ</h1>
                </div>
                <!-- ... (Your main content goes here, the Filters Form and the Table from now!) -->

                <!-- Filters Form -->
                <form action="" method="GET" class="search-form">
                    <div class="row">
                        <!-- Seller Filter (Admin Only) -->
                        <?php if ($is_admin): ?>
                            <div class="col-md-3 mb-2">
                                <label for="seller_id">เลือกผู้ขาย:</label>
                                <select class="form-select" id="seller_id" name="seller_id">
                                    <option value="">ทั้งหมด</option>
                                    <?php
                                    $sql_sellers = "SELECT seller_id, store_name FROM sellers";
                                    $result_sellers = $conn->query($sql_sellers);
                                    if ($result_sellers->num_rows > 0) {
                                        while ($seller = $result_sellers->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($seller['seller_id']) . "'";
                                            if ($filter_seller_id == $seller['seller_id']) {
                                                echo " selected";
                                            }
                                            echo ">" . htmlspecialchars($seller['store_name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <!-- REMOVED from code -->
                        <!-- Removed all-->
                        <!-- REMOVED Code Search   -->
                    </div>
                </form>

                <!-- Order Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                        <tr>
                            <th>หมายเลขคำสั่งซื้อ</th>
                            <th>วันที่สั่งซื้อ</th>
                            <th>ชื่อลูกค้า</th>
                            <th>ที่อยู่จัดส่ง</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>รายการสินค้า</th>
                            <th>ราคารวม</th>
                            <th>สถานะคำสั่งซื้อ</th>
                            <th>สถานะการจัดส่ง</th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="10" class="text-center">ไม่มีคำสั่งซื้อที่ตรงกับเงื่อนไข</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars(date("Y-m-d H:i:s", strtotime($order['order_date']))); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_first_name'] . ' ' . (isset($order['customer_last_name']) ? $order['customer_last_name'] : '')); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_address']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                    <td>
                                        <?php
                                        $product_names = explode(', ', $order['product_names']);
                                        $product_images = explode(', ', $order['product_images']);
                                        $product_prices = explode(', ', $order['product_prices']);

                                        for ($i = 0; $i < count($product_names); $i++) {
                                            echo "<div class='d-flex align-items-center mb-1'>";
                                            echo "<img src='../uploads/สินค้า/" . htmlspecialchars($product_images[$i]) . "' alt='" . htmlspecialchars($product_names[$i]) . "' class='product-image me-2'>";
                                            echo "<span>" . htmlspecialchars($product_names[$i]) . " (฿" . number_format(htmlspecialchars($product_prices[$i]), 2) . ")</span>";

                                            echo "</div>";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format(htmlspecialchars($order['order_total_price']), 2); ?></td>
                                    <td>
                                        <form method="post" action="">
                                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                            <select name="new_order_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="pending" <?php if ($order['order_status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                                <option value="paid" <?php if ($order['order_status'] == 'paid') echo 'selected'; ?>>Paid</option>
                                                <option value="shipped" <?php if ($order['order_status'] == 'shipped') echo 'selected'; ?>>Shipped</option>
                                                <option value="completed" <?php if ($order['order_status'] == 'completed') echo 'selected'; ?>>Completed</option>
                                                <option value="cancelled" <?php if ($order['order_status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" action="">
                                            <input type="hidden" name="shipping_order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                            <select name="new_shipping_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="pending" <?php if ($order['shipping_status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                                <option value="packing" <?php if ($order['shipping_status'] == 'packing') echo 'selected'; ?>>Packing</option>
                                                <option value="shipped" <?php if ($order['shipping_status'] == 'shipped') echo 'selected'; ?>>Shipped</option>
                                                <option value="arrived" <?php if ($order['shipping_status'] == 'arrived') echo 'selected'; ?>>Arrived</option>
                                            </select>
                                        </form>
                                    </td> <!-- status from database `orders.shipping_status`-->
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
<?php $conn->close(); ?>