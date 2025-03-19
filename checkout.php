<?php
session_start();

include "inc/db_config.php";
include "inc/functions.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql_user = "SELECT first_name, last_name, phone_number, address FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
    $first_name = sanitize($user['first_name']);
    $last_name = sanitize($user['last_name']);
    $phone_number = sanitize($user['phone_number']);
    $address = sanitize($user['address']);
} else {
    $first_name = "ไม่พบข้อมูล";
    $last_name = "";
    $phone_number = "";
    $address = "ไม่พบข้อมูล";
}

// Fetch user discounts
$sql_discounts = "SELECT discounts.*
                  FROM user_discounts
                  INNER JOIN discounts ON user_discounts.discount_id = discounts.id
                  WHERE user_discounts.user_id = ?
                  AND user_discounts.is_active = 1
                  AND discounts.start_date <= NOW()
                  AND discounts.end_date >= NOW()";
$stmt_discounts = $conn->prepare($sql_discounts);
$stmt_discounts->bind_param("i", $user_id);
$stmt_discounts->execute();
$result_discounts = $stmt_discounts->get_result();
$user_discounts = [];

while ($row = $result_discounts->fetch_assoc()) {
    $user_discounts[] = $row;
}

// Fetch cart items
$sql = "SELECT cart.*, featured_products.name AS product_name, featured_products.price AS product_price, featured_products.image AS product_image, featured_products.seller_id, featured_products.stock
        FROM cart
        INNER JOIN featured_products ON cart.product_id = featured_products.id
        WHERE cart.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total_price = 0;
$seller_id_for_order = null;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total_price += $row['product_price'] * $row['quantity'];
    if ($seller_id_for_order === null && isset($row['seller_id'])) {
        $seller_id_for_order = $row['seller_id'];
    }
}

// Fetch payment methods
$sql_payment_methods = "SELECT id, name FROM payment_methods WHERE is_active = 1";
$result_payment_methods = $conn->query($sql_payment_methods);
$payment_methods = [];
if ($result_payment_methods->num_rows > 0) {
    while($row = $result_payment_methods->fetch_assoc()) {
        $payment_methods[] = $row;
    }
}

$total_price_after_discount = $total_price;
$discount_amount = 0;
$selected_discount = null;

// Handle checkout form submission and bank transfer form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['checkout'])) {
        // Checkout process (เดิม)
        $payment_method_id = isset($_POST['payment_method']) ? intval($_POST['payment_method']) : 0;
        $discount_id = (isset($_POST['discount_id']) && $_POST['discount_id'] != '') ? intval($_POST['discount_id']) : null;

        $phone_number = sanitize($phone_number);
        $shipping_address = sanitize($address);

        if ($payment_method_id <= 0) {
            $error_message = "กรุณาเลือกวิธีการชำระเงิน";
        } else {
            $discount_amount = 0;
            if (!is_null($discount_id)) {
                $found_discount = false;
                foreach ($user_discounts as $discount) {
                    if ($discount['id'] == $discount_id) {
                        $found_discount = true;
                        $selected_discount = $discount;
                        if ($total_price < $discount['min_spend']) {
                            $error_message = "ยอดคำสั่งซื้อไม่ถึงขั้นต่ำที่จะใช้ส่วนลดนี้ได้";
                            $discount_amount = 0;
                            break;
                        }
                        if ($discount['discount_amount'] > 0) {
                            $discount_amount = $discount['discount_amount'];
                        } elseif ($discount['discount_percent'] > 0) {
                            $discount_amount = ($total_price * $discount['discount_percent']) / 100;
                        }
                        break;
                    }
                }
                if (!$found_discount && empty($error_message)) {
                    $error_message = "รหัสส่วนลดไม่ถูกต้องหรือไม่สามารถใช้งานได้";
                }
            }

            $total_price_after_discount = max(0, $total_price - $discount_amount);

            $conn->begin_transaction();
            try {
                // Validate stock
                foreach ($cart_items as $item) {
                    $product_id = $item['product_id'];
                    $quantity_to_buy = $item['quantity'];
                    $product_stock = $item['stock'];

                    if ($quantity_to_buy > $product_stock) {
                        throw new Exception("สินค้า " . sanitize($item['product_name']) . " มีจำนวนในสต็อกไม่เพียงพอ (มี " . $product_stock . " ต้องการ " . $quantity_to_buy . ")");
                    }
                }

                $sql_order = "INSERT INTO orders (user_id, total_price, payment_method_id, status, discount_id, seller_id) VALUES (?, ?, ?, 'pending', ?, ?)";

                $stmt_order = $conn->prepare($sql_order);

                $stmt_order->bind_param("idiii", $user_id, $total_price_after_discount, $payment_method_id, $discount_id, $seller_id_for_order);
                if (!$stmt_order->execute()) {
                    throw new Exception("Error inserting into orders: " . $stmt_order->error);
                }
                $order_id = $conn->insert_id;

                $sql_order_items = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt_order_items = $conn->prepare($sql_order_items);

                // Update stock and insert into order_items
                $sql_update_stock = "UPDATE featured_products SET stock = stock - ? WHERE id = ?";
                $stmt_update_stock = $conn->prepare($sql_update_stock);
                foreach ($cart_items as $item) {
                    $stmt_order_items->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['product_price']);
                    if (!$stmt_order_items->execute()) {
                        throw new Exception("Error inserting into order_items: " . $stmt_order_items->error);
                    }
                    $stmt_update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                    if (!$stmt_update_stock->execute()) {
                        throw new Exception("Error updating product stock: " . $stmt_update_stock->error);
                    }
                }
                $stmt_update_stock->close();
                $sql_clear_cart = "DELETE FROM cart WHERE user_id = ?";
                $stmt_clear_cart = $conn->prepare($sql_clear_cart);
                $stmt_clear_cart->bind_param("i", $user_id);
                if (!$stmt_clear_cart->execute()) {
                    throw new Exception("Error clearing cart: " . $stmt_clear_cart->error);
                }

                if (!is_null($discount_id)) {
                    $sql_Update_Discount = "UPDATE user_discounts SET is_active = 0 Where discount_id = ? And user_id = ?";
                    $stmt_Update_Discount = $conn->prepare($sql_Update_Discount);
                    $stmt_Update_Discount->bind_param("ii", $discount_id, $user_id);
                    if (!$stmt_Update_Discount->execute()) {
                        throw new Exception("Error update discount: " . $stmt_Update_Discount->error);
                    }
                }

                // Handle bank transfer specifics
                if ($payment_method_id == 2) { // Bank transfer, Payment method id value on the code table
                    //redirect to a page where the transfer happens
                    header("Location: bank_transfer.php?order_id=$order_id");
                    exit(); // stop code from render ( avoid to update to error without confirm file after
                } else {
                    $conn->commit();
                    // Redirect to success page on credit! payment option.
                    header("Location: order_success.php?order_id=$order_id");
                    exit();
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['bank_transfer_submit'])) {
        // Bank transfer process (ใหม่)
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if ($order_id <= 0) {
            $error_message = "Invalid order ID.";
        } else {
            $bank_name = sanitize($_POST['bank_name']);
            $account_number = sanitize($_POST['account_number']);
            $account_name = sanitize($_POST['account_name']); // Use the account name provided in the form
            $transfer_amount = sanitize($_POST['transfer_amount']);

            // File upload handling
            if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] == 0) {
                $uploadDir = "uploads/transfer/";
                $fileTmpName = $_FILES['evidence_image']['tmp_name'];
                $fileName = $_FILES['evidence_image']['name'];
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid() . "." . $fileExt; // Generate unique file name
                $destFile = $uploadDir . $newFileName;

                // Validate file type and size (optional)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxFileSize = 2 * 1024 * 1024; // 2MB

                if (!in_array($_FILES['evidence_image']['type'], $allowedTypes)) {
                    $error_message = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
                } elseif ($_FILES['evidence_image']['size'] > $maxFileSize) {
                    $error_message = "File size exceeds the maximum allowed size (2MB).";
                } else {
                    // Move the uploaded file to the destination directory
                    if (move_uploaded_file($fileTmpName, $destFile)) {
                        // File uploaded successfully, now insert data into the database
                        $sql_transfer = "INSERT INTO bank_transfers (order_id, bank_name, account_number, account_name, transfer_amount, transfer_timestamp, image_evidence)
                                    VALUES (?, ?, ?, ?, ?, NOW(), ?)";
                        $stmt_transfer = $conn->prepare($sql_transfer);
                        $stmt_transfer->bind_param("isssds", $order_id, $bank_name, $account_number, $account_name, $transfer_amount, $newFileName); // Save new file name

                        if ($stmt_transfer->execute()) {
                            // Update order status to pending
                            $sql_update_order = "UPDATE orders SET payment_status = 'pending' WHERE id = ?";
                            $stmt_update_order = $conn->prepare($sql_update_order);
                            $stmt_update_order->bind_param("i", $order_id);
                            if ($stmt_update_order->execute()) {
                                // Redirect to a success page or order details page
                                header("Location: order_success.php?order_id=$order_id");
                                exit();
                            } else {
                                $error_message = "Error updating order status.";
                            }
                            $stmt_update_order->close();
                        } else {
                            $error_message = "Error inserting data into the database: " . $stmt_transfer->error;
                        }
                        $stmt_transfer->close();
                    } else {
                        $error_message = "Error moving the uploaded file.";
                    }
                }
            } else {
                $error_message = "Please upload a file.";
            }
        }
    }
}

include "inc/header.php"; // Include header
?>

    <div class="container mt-5">
        <h2 class="mb-4">ยืนยันคำสั่งซื้อ</h2>

        <?php include "inc/messages.php"; // Include messages ?>

        <?php if (empty($cart_items)): ?>
            <p class="alert alert-info">ไม่มีสินค้าในตะกร้า</p>
        <?php else: ?>

            <div class="row">
                <div class="col-md-6">
                    <h3>รายการสินค้า</h3>
                    <?php include "inc/cart_items.php"; ?>
                </div>

                <div class="col-md-6">
                    <h3>ข้อมูลจัดส่ง</h3>
                    <?php include "inc/delivery_info.php"; ?> <!-- Include the delivery info file -->
                    <form method="post" action="checkout.php" enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="discount_id">เลือกส่วนลด:</label>
                            <select class="form-control" id="discount_id" name="discount_id">
                                <option value="">ไม่ใช้ส่วนลด</option>
                                <?php foreach ($user_discounts as $discount): ?>
                                    <option value="<?php echo sanitize($discount['id']); ?>"><?php echo sanitize($discount['title']); ?> (<?php echo sanitize($discount['code']); ?>) <?php if ($discount['discount_amount'] > 0) {echo " ลด ".number_format( $discount['discount_amount'],2)." บาท "; } else {echo  " ลด ".$discount['discount_percent']." % "; } ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="payment_method">วิธีการชำระเงิน</label>
                            <select class="form-control" id="payment_method" name="payment_method" required>
                                <option value="">-- เลือกวิธีการชำระเงิน --</option>
                                <?php foreach ($payment_methods as $method): ?>
                                    <?php if ($method['name'] == 'ชำระเงินปลายทาง'): ?>
                                        <option value="<?php echo sanitize($method['id']); ?>"><?php echo sanitize($method['name']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="checkout" class="btn btn-primary btn-block">ยืนยันคำสั่งซื้อ</button>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </div>

<?php include "inc/edit_profile_popup.php"; ?>

<?php
include "inc/footer.php"; // Include footer
?>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
$stmt_user->close();

if (isset($stmt_order)) {
    $stmt_order->close();
}
if (isset($stmt_order_items)) {
    $stmt_order_items->close();
}
if (isset($stmt_clear_cart)) {
    $stmt_clear_cart->close();
}
if (isset($stmt_Update_Discount)) {
    $stmt_Update_Discount->close();
}
if (isset($stmt_discounts)) {
    $stmt_discounts->close();
}

?>