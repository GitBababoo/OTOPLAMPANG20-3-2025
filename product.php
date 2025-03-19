<?php
session_start();
include "inc/db_config.php";

// ดึง product_id จาก URL และตรวจสอบ
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
    header("Location: 404.php");
    exit();
}

// ดึงข้อมูลสินค้า, หมวดหมู่, และผู้ขาย พร้อมส่วนลด (ปรับปรุง Query)
$sql = "SELECT
            fp.*,
            c.title AS category_name,
            s.store_name,
            COALESCE(d.discount_percent, 0) AS discount_percentage
        FROM featured_products fp
        LEFT JOIN categories c ON fp.category_id = c.id
        LEFT JOIN sellers s ON fp.seller_id = s.seller_id
        LEFT JOIN product_promotions pp ON fp.id = pp.product_id AND pp.is_active = 1 AND pp.start_date <= NOW() AND pp.end_date >= NOW()
        LEFT JOIN discounts d ON pp.discount_id = d.id
        WHERE fp.id = ? AND fp.is_active = 1";

$stmt = $conn->prepare($sql);

// Prepare statement  Error
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $product_id); // Bind Parameter
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $product_name = htmlspecialchars($row["name"]);
    $product_description = htmlspecialchars($row["description"]);
    $product_price = $row["price"];
    $product_image = htmlspecialchars($row["image"]);
    $product_stock = (int)$row["stock"];
    $category_name = htmlspecialchars($row["category_name"]);
    $store_name = htmlspecialchars($row['store_name']);
    $image_url = "uploads/สินค้า/" . $product_image;
    $discount_percentage = (int)$row["discount_percentage"]; //get Discount

    $discounted_price = $product_price * (1 - ($discount_percentage / 100));

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    $is_logged_in = isset($user_id);


} else {
    header("Location: 404.php"); // Redirect ถ้าไม่พบสินค้า
    exit();
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product_name; ?> - ร้านค้าออนไลน์</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <style>
        body{
            font-family: sans-serif;
            background-color: #f8f9fa;
        }
        .product-image {
            max-width: 100%;
            height: auto;
        }
        .container {
            margin-top: 30px;
        }
        .product-container {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #fff;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
        }
        .product-description, .product-details-description{
            width: 750px;
            text-align: left;
            margin-left: 20px;
            margin-right:20px;
        }

        .img-fluid {
            width: 550px;
            height: auto;
            padding: 20px;
        }
        .mt-10{
            margin-top: 30px;
        }
        .add-to-cart-button,  .wishlist-button {
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }
        .add-to-cart-button:hover ,  .wishlist-button:hover{
            opacity: 0.8;
            color:white;
        }
        .wishlist-button i {
            margin-right: 5px;
        }
        .discount-tag {
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            .product-container {
                flex-direction: column;
                align-items: center;
            }
            .product-image {
                width: 100%;
            }
            .product-description {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<!-- Navigation Bar -->
<?php include 'inc/navbar.php'; ?>
<div class="container ">
    <div class="jumbotron">
        <h6 class="display-6"><?php echo $product_name; ?></h6>
    </div>

    <!-- Product Container -->
    <div class="product-container">
        <!-- Product Image -->
        <div>
            <img src="<?php echo $image_url; ?>" class="img-fluid" alt="<?php echo $product_name; ?>">
        </div>

        <!-- Product Description -->
        <div class="product-description">
            <h4 class="mt-3"><?php echo $product_name; ?></h4>
            <p><?php echo $product_description; ?></p>

            <?php if ($is_logged_in): ?>
                <p><small>ร้านค้า: <?php echo $store_name; ?></small></p>
            <?php endif; ?>

            <p class = "mt-10"> <span style = "font-weight: bold;"> หมวดหมู่: </span> <?php echo $category_name;?></p>

            <!-- Price -->
            <?php
            if ($discount_percentage > 0) {
                echo '<p class="card-text"><small><del>฿' . number_format($product_price, 2) . '</del></small>  <span class="discount-tag">ลด ' . $discount_percentage . '%</span> </p>';
                echo '<p class="card-text font-weight-bold"> ฿' . number_format($discounted_price, 2) . '</p>';
            } else {
                echo '<p class="card-text font-weight-bold"> ฿' . number_format($product_price, 2) . '</small></p>';
            }
            ?>

            <!-- Stock -->
            <p class="product-stock"><small>เหลือ: <?php echo $product_stock; ?> ชิ้น</small>
                <?php
                if ($product_stock <= 5 && $product_stock > 0) {
                    echo ' <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> สินค้าใกล้หมดแล้ว!</span></p>';
                } elseif ($product_stock <= 0) {
                    echo '  <p> <span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> สินค้าหมด</span></p>';
                } else {
                    echo'</p>';
                }
                ?>


                <button type = "button" class="btn btn-primary add-to-cart-button mt-3" data-product-id="<?php echo $product_id; ?>">เพิ่มลงในตะกร้าสินค้า  <i class="fa-solid fa-cart-shopping" style="color: #2f70ee;"></i> </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src ="js/custom.js"></script>
</body>
</html>