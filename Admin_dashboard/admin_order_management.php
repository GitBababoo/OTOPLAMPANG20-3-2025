<?php
session_start();

include "../inc/db_config.php";
include "../inc/functions.php";

// VERY BASIC ADMIN CHECK! Replace this with your actual admin check
if (!isset($_SESSION['user_id']) /* || !isAdmin($_SESSION['user_id']) */) {
    header("Location: login.php"); // Or an unauthorized access page.
    exit();
}

//  MAIN SQL - Fetch All Orders, Joining Relevant Data:
$sql = "SELECT
            o.*,  -- All order columns.
            u.username AS customer_username,  -- Customer username (for easier readability)
            p.name AS payment_method_name,  -- Payment method name.
            s.store_name AS seller_store_name, -- Seller Store name.
             -- Item list

           (SELECT GROUP_CONCAT(CONCAT(fp.name, ' x ', oi.quantity)  ORDER BY fp.name SEPARATOR '<br>')   FROM order_items oi
            JOIN featured_products fp ON oi.product_id = fp.id  WHERE oi.order_id = o.id) AS  item_list
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN payment_methods p ON o.payment_method_id = p.id
        LEFT JOIN sellers s ON o.seller_id = s.seller_id -- join Table Sellers for All Item
        ORDER BY o.created_at DESC";  // Get orders, newest first.

$result = get_db_results($conn, $sql);

// Handle the status update (if form submitted):
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize($_POST['new_status']);

    if (!in_array($new_status, ['pending', 'packing', 'shipped', 'arrived'])) {
        $error_message = "Invalid status.";
    } else {
        $update_sql = "UPDATE orders SET shipping_status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Error updating status: " . $conn->error;
        }
    }
}

?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Order Management</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <style>
            body {
                font-family: Arial, sans-serif;
            }

            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 100;
                padding: 48px 0 0;
                box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            }

            .sidebar-sticky {
                position: relative;
                top: 0;
                height: calc(100vh - 48px);
                padding-top: .5rem;
                overflow-x: hidden;
                overflow-y: auto;
            }

            @media (max-width: 767.98px) {
                .sidebar {
                    top: 5rem;
                }
            }

            .sidebar .nav-link {
                font-weight: 500;
                color: #333;
            }

            .sidebar .nav-link .feather {
                margin-right: 4px;
                color: #999;
            }
            .sidebar .nav-link.active {
                color: #007bff;
            }

            .sidebar .nav-link:hover .feather,
            .sidebar .nav-link.active .feather {
                color: inherit;
            }

            .sidebar-heading {
                font-size: .75rem;
                text-transform: uppercase;
            }
            .order-item-image {
                width: 50px;
                height: 50px;
                object-fit: cover;
                margin-right: 10px;
            }
            .order-item-container {
                display: flex;
                align-items: center;
                margin-bottom: 5px;
            }
            .table-responsive {
                overflow-x: auto; /* Horizontal scroll on smaller screens */
            }
            .small-text {  /* Added new class */
                font-size: 0.85rem;  /*  Makes text Smaller, it is better. */
                color: #6c757d;   /* Set the Color and show better */
            }
        </style>
    </head>
    <body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="#">Your Company</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="logout.php">Sign out</a>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <?php include "sidebar.php"; ?>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Admin Order Management</h1>
                </div>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo  $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">  <!--  added table responsive is can if low width.  -->
                        <table class="table table-striped table-sm">
                            <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User</th>
                                <th>Items</th>
                                <th>วันที่สั่งซื้อ</th>
                                <th>สถานะการจัดส่ง</th>
                                <th>ราคารวม</th>
                                <th>วิธีการชำระเงิน</th> <!-- NEW -->
                                <th>Seller</th>    <!-- NEW -->
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $result->fetch_assoc()):
                                $order_id = $row['id'];
                                $orderDate =  date('Y-m-d H:i:s', strtotime($row['created_at'])); // format here!

                                // -- Reused and make is Easy -->
                                $orderStatus = $row['shipping_status']; // Use all you known how many.
                                $shipping_status_text = "";
                                switch ($orderStatus) { // show all status by order here.
                                    case 'pending':
                                        $shipping_status_text = 'รอดำเนินการ';
                                        break;

                                    case 'packing':

                                        $shipping_status_text = 'กำลังจัดเตรียมสินค้า';
                                        break;
                                    case 'shipped':
                                        $shipping_status_text = 'จัดส่งแล้ว';
                                        break;
                                    case  'arrived':

                                        $shipping_status_text = 'ถึงปลายทาง';
                                        break;
                                    default:
                                        $shipping_status_text = "สถานะไม่ถูกต้อง";
                                }
                                ?>
                                <tr>
                                    <td><?php echo $order_id; ?></td>
                                    <td><?php echo  htmlspecialchars($row['customer_username']); // Use user names?></td>
                                    <td>
                                        <!-- Use List items -->
                                        <?php
                                        echo $row['item_list'] ? $row['item_list'] : "ไม่มีสินค้า";   // SHOW is OK
                                        ?>
                                    </td>
                                    <td><?php echo $orderDate; ?></td>
                                    <td><span class="badge  <?php  // By status and Make sure All can view.
                                        switch ($row['shipping_status']){
                                            case 'pending' :
                                                echo 'badge-warning';
                                                break;

                                            case  'packing' :
                                                echo   'badge-info';
                                                break;
                                            case  'shipped' :
                                                echo   'badge-primary';
                                                break;
                                            case    'arrived' :

                                                echo 'badge-success';

                                                break;
                                            default:

                                                echo  'badge-secondary';

                                        }?>"><?php  echo  $shipping_status_text; ?></span></td>

                                    <td><?php echo number_format($row['total_price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['payment_method_name']); ?></td>   <!--  -->
                                    <td><?php echo htmlspecialchars($row['seller_store_name'])  ?  htmlspecialchars($row['seller_store_name']) :  "ไม่มีข้อมูล";  ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#updateStatusModal-<?php echo $order_id; ?>">
                                            Update Status
                                        </button>

                                        <!-- Bootstrap Modal -->
                                        <div class="modal fade" id="updateStatusModal-<?php echo $order_id; ?>" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="updateStatusModalLabel">Update Order Status (ID: <?php echo $order_id; ?>)</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">×</span>
                                                        </button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                                            <div class="form-group">
                                                                <label for="new_status">New Status:</label>
                                                                <select class="form-control" id="new_status" name="new_status" required>
                                                                    <option value="pending" <?php if($row['shipping_status'] == 'pending') echo 'selected'; ?>>รอดำเนินการ</option>
                                                                    <option value="packing" <?php if($row['shipping_status'] == 'packing') echo 'selected';?>>กำลังจัดเตรียมสินค้า</option>
                                                                    <option value="shipped" <?php if ($row['shipping_status'] == 'shipped') echo 'selected'; ?>>จัดส่งแล้ว</option>
                                                                    <option value="arrived" <?php if ($row['shipping_status'] == 'arrived') echo 'selected'; ?>>ถึงปลายทาง</option>
                                                                </select>
                                                            </div>
                                                            <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>  <!--  /table-responsive -->
                <?php else: ?>
                    <p>No orders found.</p>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
<?php $conn->close(); ?>