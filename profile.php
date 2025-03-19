<?php
session_start(); // Start the session
include "inc/db_config.php";
include "inc/navbar.php";

// Check if User IS Logged In
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Helper Function for Phone Validation
function validatePhoneNumber(string $phoneNumber): bool
{
    // Remove any non-numeric characters
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

    // Check for basic length and pattern for Thailand mobile numbers.
    if (preg_match('/^(\+66|66|0)\s?(?:9|6|8)\d{8}$/', $phoneNumber)) {
        return true;
    }

    return false;
}

// Fetch user data from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
} else {
    echo '<div class="alert alert-danger text-center">ไม่พบข้อมูลผู้ใช้งาน</div>';
    exit;
}

// Handle Profile Update Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    // Validate Email (basic)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="alert alert-danger text-center">รูปแบบอีเมลไม่ถูกต้อง</div>';
    }
    // Phone Number Validation
    elseif (!validatePhoneNumber($phone_number)) {
        echo '<div class="alert alert-danger text-center">รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง</div>';  // Better feedback
    }
    else {

        // Prepare Update Query
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone_number, $address, $user_id);

        if ($stmt->execute()) {
            echo '<div class="alert alert-success text-center">อัปเดตข้อมูลส่วนตัวสำเร็จ</div>';

            // Refresh user data after successful update
            $sql = "SELECT * FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
            }

        } else {
            error_log("Update failed: " . $stmt->error);
            echo '<div class="alert alert-danger text-center">เกิดข้อผิดพลาดในการอัปเดตข้อมูลส่วนตัว: ' . $stmt->error . '</div>';
        }
    }
}

// Check if user is already a seller
$sql_check_seller = "SELECT seller_id FROM sellers WHERE user_id = ?";
$stmt_check_seller = $conn->prepare($sql_check_seller);
$stmt_check_seller->bind_param("i", $user_id);
$stmt_check_seller->execute();
$result_check_seller = $stmt_check_seller->get_result();
$is_seller = ($result_check_seller->num_rows > 0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว</title>
    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome CDN -->
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css"> <!-- Corrected path -->
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4"><i class="fas fa-cog mr-2"></i> ข้อมูลส่วนตัว</h1>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-3"><i class="fas fa-user-edit mr-1"></i> แก้ไขข้อมูลส่วนตัว</h5>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="first_name">ชื่อ:</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user["first_name"]); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">นามสกุล:</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user["last_name"]); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">อีเมล:</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user["email"]); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone_number">เบอร์โทรศัพท์:</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user["phone_number"]); ?>">
                            </div>
                            <div class="form-group">
                                <label for="address">ที่อยู่:</label>
                                <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($user["address"]); ?></textarea>
                            </div>
                            <input type="hidden" name="action" value="update_profile">
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                                <?php if (!$is_seller): ?>
                                    <a href="register-seller.php" class="btn btn-success"><i class="fas fa-store mr-1"></i> สมัครเป็นผู้ขาย</a>
                                <?php endif; ?>
                                <a href="change-password.php" class="btn btn-warning"><i class="fas fa-key mr-1"></i> เปลี่ยนรหัสผ่าน</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p class="text-center">กรุณาเข้าสู่ระบบเพื่อดูข้อมูลส่วนตัว</p>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>