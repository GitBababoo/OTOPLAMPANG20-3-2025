<?php
session_start();

include "inc/db_config.php";
include "inc/functions.php"; // Include your functions file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Get user_id
$user_id = $_SESSION['user_id'];
// Get order_id from the URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
// Get order details for the current user, by that.
$sql_order = "SELECT  o.* ,  p.name AS payment_name,
    s.store_name AS storename
  FROM  orders AS  o
  LEFT JOIN  payment_methods  AS p   ON  o.payment_method_id  = p.id
 LEFT JOIN sellers as s ON o.seller_id  = s.seller_id
  WHERE o.user_id = ?   AND o.id = ?";
$result_order = get_db_results($conn, $sql_order,"ii",  $user_id, $order_id);

// Check if result has no issue:

if (!$result_order || $result_order->num_rows === 0) {
    // if Error show Error:
    echo "order not Found or DB error: ". $conn->error;
    exit; // and out.

}
// Store Order.
$order =  $result_order->fetch_assoc();


$shipping_status_text = ""; // Switch Status Case
switch ($order['shipping_status']) {
    case 'pending':
        $shipping_status_text = 'รอดำเนินการ';
        break;
    case 'packing':
        $shipping_status_text = "กำลังจัดเตรียมสินค้า";
        break;
    case 'shipped':
        $shipping_status_text = 'จัดส่งแล้ว';
        break;
    case 'arrived':
        $shipping_status_text = "ถึงปลายทาง";
        break;
    default:
        $shipping_status_text = "Unknown Status"; // Handle unexpected status values

}

// GET data on order_items.

$sql_order_items =  "SELECT * FROM  order_items  WHERE order_id = ?";
$result_order_items =  get_db_results($conn,$sql_order_items,  "i",  $order_id);


?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Details</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>
    <body>
    <?php include "inc/navbar.php" ?>
    <div class="container mt-4">
        <h2> รายละเอียดคำสั่งซื้อ #<?php echo $order['id']; ?></h2>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">ข้อมูลการสั่งซื้อ</h5>
                <p><strong>Order ID : </strong>  <?php echo $order['id']; ?></p>
                <p><strong>Order Date :</strong> <?php  echo  $order['created_at'];  ?></p>
                <p><strong>Shipping Status :</strong> <?php echo   $shipping_status_text ?></p>

                <p><strong>Payment Method :</strong>  <?php  echo $order['payment_name']; ?></p>
                <?php  if(isset($order['storename'] ) ):  ?>
                    <p><strong>Seller: </strong><?php echo  $order['storename'] ;  ?></p>
                <?php endif;  ?>
                <p><strong>Total Price :</strong> <?php echo  number_format($order['total_price'] ,2 );  ?></p>
            </div>

        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6> สินค้า</h6>

            </div>
            <ul class="list-group list-group-flush">
                <?php   if ($result_order_items  && $result_order_items->num_rows  >0 ) :
                    while($item = $result_order_items->fetch_assoc() )  :

                        $productName = getProductNameById($conn, $item['product_id']); // GET PRODUCT FORM FUNCTION>
                        ?>

                        <li class="list-group-item">

                            <h6><?php echo   $productName ?></h6>

                            <small>จำนวน :  <?php  echo $item['quantity']  ?></small>,
                            <small>Price:  <?php  echo  number_format( $item['price'] * $item['quantity']);  ?></small>
                        </li>

                    <?php
                    endwhile;
                endif;?>
            </ul>

        </div>
    </div>
    <!-- Bootstrap JS, Popper.js, and jQuery (Place these at the end of the body) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
<?php $conn->close(); ?>