<?php
include "inc/db_config.php";
include "inc/navbar.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="container mt-5"><div class="alert alert-warning">กรุณาเข้าสู่ระบบก่อนรับส่วนลด <a href="login.php" class="alert-link">เข้าสู่ระบบ</a></div></div>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Function to check if user has already received the discount
function hasUserReceivedDiscount($conn, $user_id, $discount_id) {
    $sql = "SELECT id FROM user_discounts WHERE user_id = ? AND discount_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $discount_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['discount_id'])) {
        $discount_id = $_POST['discount_id'];

        // Validate that discount_id is an integer
        if (!is_numeric($discount_id)) {
            echo '<div class="container mt-5"><div class="alert alert-danger">Discount ID ไม่ถูกต้อง</div></div>';
            exit;
        }

        // Check if the discount exists and is not expired (using start and end date)
        $sql_discount_check = "SELECT id, start_date, end_date FROM discounts WHERE id = ?";
        $stmt_discount_check = $conn->prepare($sql_discount_check);
        $stmt_discount_check->bind_param("i", $discount_id);
        $stmt_discount_check->execute();
        $result_discount_check = $stmt_discount_check->get_result();

        if ($result_discount_check->num_rows == 0) {
            echo '<div class="container mt-5"><div class="alert alert-danger">ไม่พบส่วนลดนี้</div></div>';
            exit;
        }

        $discount = $result_discount_check->fetch_assoc();
        $start_date = new DateTime($discount['start_date']);
        $end_date = new DateTime($discount['end_date']);
        $now = new DateTime();

        if ($now < $start_date || $now > $end_date) {
            echo '<div class="container mt-5"><div class="alert alert-danger">ส่วนลดหมดอายุแล้ว</div></div>';
            exit;
        }


        // Check if the user has already received the discount
        if (hasUserReceivedDiscount($conn, $user_id, $discount_id)) {
            echo '<div class="container mt-5"><div class="alert alert-info">คุณได้รับส่วนลดนี้ไปแล้ว</div></div>';
        } else {
            // Insert the discount for the user
            $sql = "INSERT INTO user_discounts (user_id, discount_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $discount_id);

            if ($stmt->execute()) {
                echo '<div class="container mt-5"><div class="alert alert-success">รับส่วนลดสำเร็จแล้ว!</div></div>';

                // Optionally, trigger database update or logging after discount assignment
                // Consider adding more logging or update mechanisms
            } else {
                echo '<div class="container mt-5"><div class="alert alert-danger">เกิดข้อผิดพลาดในการรับส่วนลด: ' . $stmt->error . '</div></div>';
            }
        }
    }
}
?>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


<div class="container mt-5">
    <h1>รับส่วนลด</h1>

    <?php
    // Fetch available discounts from the database
    $sql = "SELECT * FROM discounts";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<ul class="list-group">';
        while ($row = $result->fetch_assoc()) {
            $discount_id = $row['id'];
            $has_received = hasUserReceivedDiscount($conn, $user_id, $discount_id); // Check if user received

            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
            echo '<div>';
            echo '<h5>' . htmlspecialchars($row['title']) . '</h5>';
            echo '<p>' . htmlspecialchars($row['description']) . '</p>';
            echo '<p>Code: ' . htmlspecialchars($row['code']) . '</p>';
            echo '<small>Minimum Spend: ' . htmlspecialchars($row['min_spend']) . '</small><br>';
            echo '<small>Expiry Date: ' . htmlspecialchars($row['expiry_date']) . '</small>';
            echo '</div>';


            if ($has_received) {
                echo '<button class="btn btn-success" disabled><i class="fas fa-check-circle mr-1"></i> รับคูปองแล้ว</button>';  // disabled Button
            } else {
                echo '<form method="POST">';
                echo '<input type="hidden" name="discount_id" value="' . $row['id'] . '">';
                echo '<button type="submit" class="btn btn-primary"><i class="fas fa-gift mr-1"></i> รับคูปองนี้</button>';
                echo '</form>';
            }


            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<div class="alert alert-info">ไม่มีส่วนลดที่สามารถรับได้ในขณะนี้</div>';
    }
    ?>

</div>
