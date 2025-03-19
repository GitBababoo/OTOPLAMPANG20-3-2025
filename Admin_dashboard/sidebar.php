<div class="sidebar-sticky">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'active'; ?>" href="dashboard.php">
                <i class="fas fa-home"></i> แผงควบคุม <span class="sr-only">(current)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'user_roles.php') echo 'active'; ?>" href="user_roles.php">
                <i class="fas fa-users"></i> สิทธิ์ผู้ใช้งาน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'products.php') echo 'active'; ?>" href="products.php">
                <i class="fas fa-shopping-cart"></i> สินค้า
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'categories.php') echo 'active'; ?>" href="categories.php">
                <i class="fas fa-list"></i> หมวดหมู่
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'admin_order_management.php') echo 'active'; ?>" href="admin_order_management.php">
                <i class="fas fa-file-invoice"></i> คำสั่งซื้อ
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'users.php') echo 'active'; ?>" href="users.php">
                <i class="fas fa-user"></i> ผู้ใช้งาน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'discounts.php') echo 'active'; ?>" href="discounts.php">
                <i class="fas fa-percent"></i> ส่วนลด
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'approve_seller.php') echo 'active'; ?>" href="approve_seller.php">
                <i class="fas fa-check-circle"></i> อนุมัติผู้ขาย
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'banners.php') echo 'active'; ?>" href="banners.php">
                <i class="fas fa-image"></i> แบนเนอร์
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'reviews.php') echo 'active'; ?>" href="reviews.php">
                <i class="fas fa-star"></i> รีวิว
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'notifications.php') echo 'active'; ?>" href="notifications.php">
                <i class="fas fa-bell"></i> การแจ้งเตือน
            </a>
        </li>
    </ul>
</div>