<?php
session_start();

include "inc/db_config.php";
include "inc/functions.php";  // Make sure functions.php includes the sanitize function!

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login or show an error. Don't allow unauthorized access.
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize all the incoming data
    $first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
    $phone_number = isset($_POST['phone_number']) ? sanitize($_POST['phone_number']) : '';
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
    // Validate phone number (basic example, expand as needed)

    if (empty($phone_number)){
        $error_message = "กรุณากรอกเบอร์โทรศัพท์";
    }

    else {

        // Prepare the SQL update statement
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, address = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssssi", $first_name, $last_name, $phone_number, $address, $user_id);

            if ($stmt->execute()) {
                // Update was successful, redirect back to checkout
                header("Location: checkout.php"); // Redirect back to the checkout page after success.
                exit();
            } else {
                $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;  // Display error
            }

            $stmt->close();
        } else {
            $error_message = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;  // Display error
        }
    }
}
?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <title>บันทึกข้อมูลส่วนตัว</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo sanitize($error_message); ?></div>
        <?php endif; ?>
        <div class="alert alert-success" role="alert">
            <p> กำลังนำท่านกลับสู่หน้า checkout ...</p>

        </div>
        <!--  Additional content can go here, perhaps a link back to checkout.php if the redirect fails. -->

    </div>
    </body>
    </html>
<?php
$conn->close(); // Close database connection
?>