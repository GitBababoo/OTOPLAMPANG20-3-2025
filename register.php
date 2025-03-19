<?php
include "inc/db_config.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Input
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['phone_number'])) {
        echo '<div class="alert alert-danger text-center">กรุณากรอกข้อมูลให้ครบถ้วน</div>';
    } else {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);

        // Check if username or email already exists
        $check_sql = "SELECT user_id FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            echo '<div class="alert alert-danger text-center">ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว</div>';
        } else {
            // Insert new user
            $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone_number) VALUES ('$username', '$email', '$password', '$first_name', '$last_name', '$phone_number')";

            if ($conn->query($sql) === TRUE) {
                $user_id = $conn->insert_id;  // Get last insert ID

                // Assign default role (user) - role_id = 2
                $role_sql = "INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, 2)";
                $conn->query($role_sql);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                header("Location: index.php");  // Redirect
                exit();
            } else {
                echo '<div class="alert alert-danger text-center">เกิดข้อผิดพลาดในการสมัครสมาชิก: ' . $conn->error . '</div>';
                error_log("Registration error: " . $conn->error); // Log the error
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
    <title>สมัครสมาชิก</title>
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
                    สมัครสมาชิก
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="username">ชื่อผู้ใช้งาน:</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้งาน" required>
                        </div>
                        <div class="form-group">
                            <label for="email">อีเมล:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="อีเมล" required>
                        </div>
                        <div class="form-group">
                            <label for="password">รหัสผ่าน:</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                        </div>
                        <div class="form-group">
                            <label for="first_name">ชื่อ:</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="ชื่อ" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">นามสกุล:</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="นามสกุล" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">เบอร์โทรศัพท์:</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" placeholder="เบอร์โทรศัพท์" required>
                        </div>
                        <button type="submit" class="btn btn-primary">สมัครสมาชิก</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap JS (Optional: Add if needed for Bootstrap features) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>