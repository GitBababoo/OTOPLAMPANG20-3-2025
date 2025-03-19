<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "db_config.php";

// Function to get user roles based on user_id from user_roles table
function getUserRoles($conn, $user_id) {
    $roles = array();
    $sql = "SELECT role_id FROM user_roles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['role_id'];
    }
    return $roles;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-store mr-2"></i>OTOP-ลำปาง
        </a>
        <a class="navbar-brand" href="products_all.php">
            สินค้าทั้งหมด
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Search Bar -->
            <form class="form-inline my-2 my-lg-0 ml-auto">
                <!--  You'd put a search input here -->
            </form>

            <!-- Menu Items -->
            <ul class="navbar-nav ml-auto align-items-center">
                <?php if (isset($_SESSION['user_id'])) :
                    $user_id = $_SESSION['user_id'];
                    $roles = getUserRoles($conn, $user_id);  // Get roles from database

                    // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
                    $sql_user = "SELECT first_name, last_name FROM users WHERE user_id = ?";
                    $stmt_user = $conn->prepare($sql_user);
                    $stmt_user->bind_param("i", $user_id);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();
                    $user = $result_user->fetch_assoc();
                    $username = $user['first_name'] . ' ' . $user['last_name']; // ชื่อผู้ใช้
                    ?>
                    <!-- User is logged in -->
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-cog mr-1"></i> โปรไฟล์</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders_history.php"><i class="fas fa-history mr-1"></i> ประวัติการสั่งซื้อ</a>
                    </li>
                    <?php if (in_array(1, $roles)) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="Admin_dashboard/dashboard.php"><i class="fas fa-tools mr-1"></i> Admin Panel</a>
                    </li>
                <?php endif; ?>
                    <?php if (in_array(3, $roles)) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="Seller_dashboard/seller_panel.php"><i class="fas fa-store mr-1"></i> Seller Panel</a>
                    </li>
                <?php endif; ?>

                    <!-- *********  Notification Icon ********* -->
                    <?php include 'notification_icon.php'; ?>


                    <li class="nav-item">
                        <span class="navbar-text"><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($username); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> ออกจากระบบ</a>
                    </li>
                <?php else : ?>
                    <!-- User is not logged in -->
                    <li class="nav-item">
                        <a class="nav-link text-success" href="register.php"><i class="fas fa-user-plus mr-1"></i> สมัครสมาชิก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-primary" href="login.php"><i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ</a>
                    </li>

                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link text-warning" href="cart.php"><i class="fas fa-shopping-cart mr-1"></i> ตะกร้าสินค้า</a>
                </li>
            </ul>
        </div>
    </div>
</nav>