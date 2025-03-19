<?php
session_start();
include "inc/db_config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Validate Input (Basic)
    if (empty($username) || empty($password)) {
        echo '<div class="alert alert-danger text-center">กรุณากรอกข้อมูลให้ครบถ้วน</div>';
    } else {

        $sql = "SELECT user_id, username, password FROM users WHERE username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if ($password == $row["password"]) { // Verify Plain Text Password
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                header("Location: index.php"); // Redirect to home page
                exit();
            } else {
                echo '<div class="alert alert-danger text-center">รหัสผ่านไม่ถูกต้อง</div>';
            }
        } else {
            echo '<div class="alert alert-danger text-center">ไม่พบชื่อผู้ใช้งานนี้ในระบบ</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include "inc/navbar.php"; // Navigation Bar ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    เข้าสู่ระบบ
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="username">ชื่อผู้ใช้งาน:</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้งาน" required>
                        </div>
                        <div class="form-group">
                            <label for="password">รหัสผ่าน:</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                        </div>
                        <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                        <p class="mt-3">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (Optional) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>