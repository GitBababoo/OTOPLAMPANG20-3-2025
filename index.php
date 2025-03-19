<?php
include "inc/db_config.php";

// Function to sanitize input (prevent XSS)
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Function to check if image exists
function imageExists($path) {
    return file_exists($path) && is_readable($path);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="เว็บไซต์ขายสินค้าออนไลน์">
    <meta name="keywords" content="ขายของออนไลน์, สินค้า, ซื้อของ">
    <title>ร้านค้าออนไลน์</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <style>
        .category-card-link {
            text-decoration: none; /* ลบเส้นใต้ link */
            color: inherit; /* ให้ใช้สีตัวอักษรปกติ */
            display: block; /* ให้ link ครอบคลุมพื้นที่ card */
        }

        .category-card:hover {
            transform: translateY(-5px); /* เลื่อน card ขึ้นเล็กน้อยเมื่อ hover */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* เพิ่มเงาเมื่อ hover */
            transition: all 0.3s ease;
        }

        .category-card img {
            height: 150px; /* เพิ่มความสูงรูปภาพ */
            object-fit: cover;
        }
        .category-card .card-body {
            padding: 0.75rem;
        }
        .category-card .card-text {
            margin-bottom: 0;
        }
        .product-card {
            border: 1px solid #ddd; /* เพิ่มเส้นขอบ */
            transition: all 0.3s ease;
        }

        .product-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }

        .product-card img {
            height: 150px;  /* ปรับความสูงรูปภาพ */
            object-fit: cover;
        }

        .product-link {
            text-decoration: none;
            color: inherit; /* สืบทอดสีตัวอักษรจาก parent */
            display: block;  /* ให้ link ครอบคลุมพื้นที่ทั้งหมดของ card */
        }
        .product-link:hover {
            cursor:pointer; /*เปลี่ยนเป็นมืือชี้ */
        }
        .wishlist-button {
            margin-top: 5px;
        }

        .quick-view-button {
            margin-top: 5px;
            margin-left: 5px;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navigation Bar -->
<?php include "inc/navbar.php"; ?>

<div class="container mt-1">
    <!-- Promotions -->
    <div class="mb-1">
        <div class="row">
            <?php
            // TODO: ดึงข้อมูลส่วนลดจากฐานข้อมูล และแสดงผล
            // ตัวอย่าง:
            // echo "<div class='col-12'><small class='alert alert-success'>ลด 20% สินค้าใหม่!</small></div>";
            ?>
        </div>
    </div>


    <?php include 'หมวดหมู่สินค้า.php';?>



    <!-- Carousel -->
    <div id="carouselExampleIndicators" class="carousel slide mt-1" data-ride="carousel">
        <ol class="carousel-indicators">
            <?php
            $bannerCount = 0;
            if ($conn) {
                $sql_banners = "SELECT * FROM banners LIMIT 3";
                $result_banners = $conn->query($sql_banners);

                if ($result_banners && $result_banners->num_rows > 0) {
                    while($row_banner = $result_banners->fetch_assoc()) {
                        $bannerCount++;
                    }
                }
            }
            ?>
        </ol>
        <?php include 'แบนเนอร์ส่วนลด.php';?>


    </div>

        <?php include 'featured_products.php';?>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-2 mt-1">
    <p><small>© 2024 ร้านค้าออนไลน์. สงวนสิทธิ์ทุกประการ.</small></p>
</footer>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>