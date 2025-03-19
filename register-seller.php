<?php
session_start();
require_once 'inc/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Check if user is already a seller
$user_id = $_SESSION['user_id'];
$sql_check_seller = "SELECT seller_id FROM sellers WHERE user_id = ?";
$stmt_check_seller = $conn->prepare($sql_check_seller);
$stmt_check_seller->bind_param("i", $user_id);
$stmt_check_seller->execute();
$result_check_seller = $stmt_check_seller->get_result();
$is_seller = ($result_check_seller->num_rows > 0);

// If user is already a seller, redirect or display a message
if ($is_seller) {
    header("Location: profile.php"); // Redirect to profile page
    exit();
}

// Handle form submission
$message = '';
$success = false; // Track if the operation was successful
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $store_name = htmlspecialchars($_POST['store_name']);
    $store_description = htmlspecialchars($_POST['store_description']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $seller_email = htmlspecialchars($_POST['seller_email']);

    // Validate data
    if (empty($store_name) || empty($seller_email)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $success = false;
    } else {
        // Validate phone number using regular expression
        if (!preg_match("/^[0-9]{10}$/", $phone_number)) {
            $message = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นตัวเลข 10 หลัก)';
            $success = false;
        } else {
            // Check if email already exists
            $sql_check_email = "SELECT seller_email FROM sellers WHERE seller_email = ?";
            $stmt_check_email = $conn->prepare($sql_check_email);
            $stmt_check_email->bind_param("s", $seller_email);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();

            if ($result_check_email->num_rows > 0) {
                $message = 'อีเมลนี้ถูกลงทะเบียนแล้ว';
                $success = false;
            } else {
                // Use prepared statement
                $sql_add = "INSERT INTO sellers (user_id, store_name, store_description, phone_number, seller_email, status) VALUES (?, ?, ?, ?, ?, 'inactive')";
                $stmt = $conn->prepare($sql_add);
                $stmt->bind_param("issss", $user_id, $store_name, $store_description, $phone_number, $seller_email);

                if ($stmt->execute()) {
                    $message = 'ใบสมัครของคุณถูกส่งแล้ว และรอการอนุมัติ';
                    $success = true;
                } else {
                    $message = 'เกิดข้อผิดพลาดในการเพิ่มผู้ขาย: ' . $stmt->error;
                    $success = false;
                }
            }
        }
    }
}

?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register as Seller (สมัครเป็นผู้ขาย)</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css">
        <style>
            body {
                font-family: 'Arial', sans-serif;
            }

            .container {
                margin-top: 50px;
            }
        </style>
    </head>
    <body>
    <div class="container">
        <h2>Register as Seller (สมัครเป็นผู้ขาย)</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$is_seller): ?>
            <form method="post">
                <div class="form-group">
                    <label for="store_name">Store Name (ชื่อร้านค้า):</label>
                    <input type="text" class="form-control" id="store_name" name="store_name" required>
                </div>
                <div class="form-group">
                    <label for="store_description">Store Description (รายละเอียดร้านค้า):</label>
                    <textarea class="form-control" id="store_description" name="store_description"></textarea>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number (เบอร์โทรศัพท์):</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number">
                    <small class="form-text text-muted">ต้องเป็นตัวเลข 10 หลักเท่านั้น</small>
                </div>
                <div class="form-group">
                    <label for="seller_email">Seller Email (อีเมลผู้ขาย):</label>
                    <input type="email" class="form-control" id="seller_email" name="seller_email" required>
                </div>
                <button type="submit" class="btn btn-primary">Submit (ส่ง)</button>
            </form>
        <?php else: ?>
            <p class="alert alert-info">คุณได้สมัครเป็นผู้ขายแล้ว</p>
        <?php endif; ?>
        <a href="profile.php" class="btn btn-secondary">Back to Profile (กลับสู่ โปรไฟล์)</a>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.9/dist/sweetalert2.all.min.js"></script>
    </body>
    </html>

<?php
$conn->close();
?>