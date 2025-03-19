<?php
include "inc/db_config.php";
session_start();

function isThaiPhoneNumber($phone_number) {
    // Remove any non-digit characters
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);

    // Check if it starts with 0 and has 10 digits
    return (preg_match('/^0[0-9]{9}$/', $phone_number) === 1);
}

function isValidName($name) {
    // Allows Thai and English characters, spaces, and hyphens
    return preg_match('/^[a-zA-Z\p{Thai} \-]+$/u', $name);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Input
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);

    $errors = [];

    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($phone_number)) {
        $errors[] = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    }

    if (!isThaiPhoneNumber($phone_number)) {
        $errors[] = "เบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นเบอร์ไทย 10 หลัก)";
    }

    if (!isValidName($first_name) || !isValidName($last_name)) {
        $errors[] = "ชื่อและนามสกุลต้องเป็นภาษาไทยหรืออังกฤษเท่านั้น";
    }

    if (strlen($first_name) > 50 || strlen($last_name) > 50) {
        $errors[] = "ชื่อและนามสกุลต้องไม่เกิน 50 ตัวอักษร";
    }
    // Check length of first name and last name
    if (strlen($first_name) > 10) {
        $errors[] = "ชื่อต้องไม่เกิน 10 ตัวอักษร";
    }

    if (strlen($last_name) > 10) {
        $errors[] = "นามสกุลต้องไม่เกิน 10 ตัวอักษร";
    }

    // Check if username or email already exists
    $check_sql = "SELECT user_id FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $errors[] = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว";
    }

    if (empty($errors)) {
        // Insert new user
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone_number) VALUES ('$username', '$email', '$password', '$first_name', '$last_name', '$phone_number')";

        if ($conn->query($sql) === TRUE) {
            $user_id = $conn->insert_id;  // Get last insert ID

            // Assign default role (user) - role_id = 2
            $role_sql = "INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, 2)";
            $conn->query($role_sql);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            // SweetAlert for success
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'สมัครสมาชิกสำเร็จ!',
                    text: 'ยินดีต้อนรับสู่เว็บไซต์ของเรา',
                    confirmButtonText: 'ตกลง'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'index.php';
                    }
                });
            </script>";
            exit();
        } else {
            $error_message = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $conn->error;
            error_log("Registration error: " . $conn->error); // Log the error
            // SweetAlert for database error
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: '$error_message',
                    confirmButtonText: 'ตกลง'
                });
            </script>";
        }
    } else {
        // Display errors using SweetAlert
        $error_string = implode("<br>", $errors);
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'มีข้อผิดพลาด!',
                html: '$error_string',
                confirmButtonText: 'ตกลง'
            });
        </script>";
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
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
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
<!-- SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
</body>
</html>