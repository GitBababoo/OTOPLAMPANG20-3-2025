<?php
session_start();
include "inc/db_config.php";
include "inc/navbar.php";

// Check if User IS Logged In
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$message = ""; // Variable to store messages
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = $_POST["old_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate Input
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $success = false;
    } elseif ($new_password !== $confirm_password) {
        $message = 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
        $success = false;
    } elseif (strlen($new_password) < 6) {
        $message = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        $success = false;
    } else {
        // Verify old password
        $user_id = $_SESSION["user_id"];
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($old_password, $row["password"])) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in database
                $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $message = 'เปลี่ยนรหัสผ่านสำเร็จ';
                    $success = true;
                } else {
                    error_log("Password update failed: " . $stmt->error);
                    $message = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน: ' . $stmt->error;
                    $success = false;
                }
            } else {
                $message = 'รหัสผ่านเดิมไม่ถูกต้อง';
                $success = false;
            }
        } else {
            $message = 'ไม่พบผู้ใช้งาน';
            $success = false;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่าน</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css">

</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4"><i class="fas fa-key mr-2"></i> เปลี่ยนรหัสผ่าน</h1>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="old_password">รหัสผ่านเดิม:</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">รหัสผ่านใหม่:</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">ยืนยันรหัสผ่านใหม่:</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
                            <a href="profile.php" class="btn btn-secondary">Back to profile (กลับสู่ โปรไฟล์)</a>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.9/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
        <?php if (!empty($message)): ?>
        Swal.fire({
            icon: '<?php echo $success ? 'success' : 'error'; ?>',
            title: '<?php echo $success ? 'สำเร็จ!' : 'ข้อผิดพลาด!'; ?>',
            text: '<?php echo $message; ?>',
        });
        <?php endif; ?>
    });
</script>

</body>
</html>