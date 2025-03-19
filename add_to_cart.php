<?php
include "inc/db_config.php";

// Function to sanitize input (ป้องกัน XSS และ SQL Injection เบื้องต้น)
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if data was submitted correctly
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id']) && isset($_POST['quantity']) && isset($_POST['user_id'])) {
    $product_id = (int)$_POST['product_id']; // Get product_id and cast to integer
    $quantity = (int)$_POST['quantity'];   // Get quantity and cast to integer
    $user_id = (int)$_POST['user_id'];

    // Data validation
    if ($product_id <= 0 || $quantity <= 0 || $user_id <=0) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
        exit();
    }

    // Fetch product price (BEST PRACTICE)
    $sql_price = "SELECT price, stock FROM featured_products WHERE id = ?";
    $stmt_price = $conn->prepare($sql_price);
    $stmt_price->bind_param("i", $product_id);
    $stmt_price->execute();
    $result_price = $stmt_price->get_result();

    if ($result_price->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบสินค้านี้']);
        exit();
    }

    $row_price = $result_price->fetch_assoc();
    $product_price = $row_price['price'];
    $product_stock = $row_price['stock'];

    // Verify Sufficient Stock: AVOID SELLING MORE THAN WHAT'S AVAILABLE (CRUCIAL)
    if ($quantity > $product_stock) {
        echo json_encode(['status' => 'error', 'message' => 'จำนวนสินค้าในตะกร้าเกินจำนวนที่มีในคลัง!']);  // User selected quantity > what we have. Stop them!
        $stmt_price->close();
        exit;
    }

    $stmt_price->close();

    // *** ALL VALIDATIONS ARE COMPLETE ***
    // ***********************************
    // Check if product already in cart
    $sql_check = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $user_id, $product_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {  //Found Existing in Cart.  DO NOT ADD, DO UPDATE.
        // Item already in cart.  Update the quantity. (KEY CHANGE)
        $row_check = $result_check->fetch_assoc();
        $new_quantity = $row_check['quantity'] + $quantity;

        // Further Stock validation when updating quantity. Avoid over-selling
        if ($new_quantity > $product_stock) {
            echo json_encode(['status' => 'error', 'message' => 'จำนวนสินค้ารวมในตะกร้าเกินจำนวนที่มีในคลัง!']);
            $stmt_check->close();
            exit;
        }

        $cart_id = $row_check['id'];
        $sql_update = "UPDATE cart SET quantity = ?, price = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("idi", $new_quantity, $product_price, $cart_id); // Use quantity from client site , update to database
        if ($stmt_update->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มจำนวนสินค้าในตะกร้าแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการแก้ไขตะกร้าสินค้า (1): ' . $stmt_update->error]);
        }
        $stmt_update->close();  // Clean resources in here only
    } else {    //Not existing product cart
        // Item isn't in cart:  Insert the new item.
        $sql_insert = "INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiid", $user_id, $product_id, $quantity, $product_price);
        if ($stmt_insert->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มสินค้าลงตะกร้าแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเพิ่มสินค้าลงตะกร้า: (2)  '. $stmt_insert->error]); //Add php print error, just incase have value un expect print so code go else
        }
        $stmt_insert->close();    //  Always do resources release
    }
    $stmt_check->close();      // Clean source first line from code
} else {
    // If NO submitted data at POST at cart_id return  NOT POST - 405 , GET - 200 OK but it not right . That POST value not sent and that normal, if code keep not work,  let look dev tool inside Network and see at bottom header
    echo json_encode(['status' => 'error', 'message' => 'เรียบร้อย']);
}