<?php
session_start();

include "inc/db_config.php";

// Function to sanitize input/output data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Process update quantity form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $quantity) {
        $cart_id = intval($cart_id);
        $quantity = intval($quantity);

        // Basic validation
        if ($quantity <= 0) {
            // Remove item from cart if quantity is 0 or less
            $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
            $stmt->execute();
        } else {
            // Update quantity in cart
            $sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
            $stmt->execute();
        }
    }
    $_SESSION['cart_message'] = "<div class='alert alert-success'>อัปเดตตะกร้าสินค้าสำเร็จ</div>";
    header("Location: cart.php"); // Redirect to refresh the cart
    exit();
}

// Fetch cart items for the current user
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT cart.*, featured_products.name AS product_name, featured_products.price AS product_price, featured_products.image AS product_image
            FROM cart
            INNER JOIN featured_products ON cart.product_id = featured_products.id
            WHERE cart.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $total_price = 0;
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['product_price'] * $row['quantity'];
    }
} else {
    // User not logged in
    $cart_items = [];
    $total_price = 0;
}

?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ตะกร้าสินค้า</title>
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- Custom CSS -->
        <style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #f8f9fa;
            }
            .cart-table {
                width: 100%;
                margin-top: 20px;
            }
            .cart-item-image {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border-radius: 8px; /* Rounded images */
                box-shadow: 0 0 5px rgba(0,0,0,0.1); /* Subtle shadow */
                margin-right: 10px; /* Add some space between image and name */
            }
            .cart-summary {
                margin-top: 20px;
                padding: 20px;
                background-color: #fff;
                border: 1px solid #ddd;
                border-radius: 10px; /* Rounded corners */
                box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Enhanced shadow */
            }
            .btn-primary, .btn-success {
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: transform 0.2s;
            }
            .btn-primary:hover, .btn-success:hover {
                transform: translateY(-2px); /* Slight lift on hover */
            }
            .quantity-input {
                text-align: center;
                border-radius: 5px;
                border: none; /* Remove the input border */
                width: 50px; /* Adjust width as needed */
            }
            .quantity-button {
                border-radius: 5px;
                transition: background-color 0.3s;
            }
            .quantity-button:hover {
                background-color: #e9ecef; /* Light gray on hover */
            }
        </style>
    </head>
    <body class="bg-light">

    <!-- Navigation Bar -->
    <?php include "inc/navbar.php"; ?>

    <div class="container mt-5">
        <h2 class="mb-4">ตะกร้าสินค้า</h2>

        <?php
        // Display cart message
        if (isset($_SESSION['cart_message'])) {
            echo $_SESSION['cart_message'];
            unset($_SESSION['cart_message']); // Remove message after displaying
        }
        ?>

        <?php if (empty($cart_items)): ?>
            <p class="alert alert-info">ไม่มีสินค้าในตะกร้า</p>
        <?php else: ?>

            <form method="post" action="cart.php">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr class="text-center">
                            <th>สินค้า</th>
                            <th>ราคา</th>
                            <th>จำนวน</th>
                            <th>ราคารวม</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/สินค้า/<?php echo sanitize($item['product_image']); ?>" alt="<?php echo sanitize($item['product_name']); ?>" class="cart-item-image">
                                        <div><?php echo sanitize($item['product_name']); ?></div>
                                    </div>
                                </td>
                                <td class="align-middle text-center">$<?php echo sanitize($item['product_price']); ?></td>
                                <td class="align-middle text-center">
                                    <div class="input-group justify-content-center">
                                        <span class="quantity-input" data-cart-id="<?php echo sanitize($item['id']); ?>"><?php echo sanitize($item['quantity']); ?></span>
                                        <input type="hidden" name="quantity[<?php echo sanitize($item['id']); ?>]" value="<?php echo sanitize($item['quantity']); ?>">
                                    </div>
                                </td>
                                <td class="align-middle text-center">$<?php echo sanitize($item['product_price'] * $item['quantity']); ?></td>
                                <td class="align-middle text-center">
                                    <a href="remove_from_cart.php?cart_id=<?php echo sanitize($item['id']); ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cart-summary">
                    <h5 class="mb-3">สรุปรายการสั่งซื้อ</h5>
                    <div class="d-flex justify-content-between">
                        <span>ราคารวมทั้งหมด:</span>
                        <span>$<?php echo sanitize($total_price); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>ค่าจัดส่ง:</span>
                        <span>ฟรี</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>ราคารวม (รวมค่าจัดส่ง):</span>
                        <span>$<?php echo sanitize($total_price); ?></span>
                    </div>
                    <div class="text-center mt-3">
                        <button type="submit" name="update_cart" class="btn btn-primary"><i class="fas fa-sync-alt mr-2"></i> อัปเดตตะกร้า</button>
                        <a href="checkout.php" class="btn btn-success"><i class="fas fa-check mr-2"></i> ดำเนินการสั่งซื้อ</a>
                    </div>
                </div>
            </form>

        <?php endif; ?>

    </div>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.quantity-button').on('click', function() {
                var cartId = $(this).data('cart-id');
                var action = $(this).data('action');
                var quantitySpan = $('.quantity-input[data-cart-id="' + cartId + '"]');
                var quantity = parseInt(quantitySpan.text());

                if (action === 'plus') {
                    quantity++;
                } else if (action === 'minus' && quantity > 1) {
                    quantity--;
                }

                quantitySpan.text(quantity); // Update the quantity in the span
                $('input[name="quantity[' + cartId + ']"]').val(quantity); // Update the hidden input
            });
        });
    </script>
    </body>
    </html>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>