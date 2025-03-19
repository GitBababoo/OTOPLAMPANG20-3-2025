<?php
session_start();
include '../inc/db_config.php';
include '../inc/functions.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่ และมีบทบาทเป็น admin หรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$is_admin = check_user_role($conn, $user_id, 'admin');

// กำหนด seller_id ตามบทบาทของผู้ใช้
$seller_id = null;
if (!$is_admin) {
    $seller_id = get_seller_id_from_user_id($conn, $user_id);
    if (!$seller_id) {
        echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
        exit();
    }
}

// ดึงรีวิวทั้งหมดหรือเฉพาะของ seller ที่เข้าสู่ระบบ
if ($is_admin) {
    $sql_reviews = "SELECT r.*, u.first_name, u.last_name, fp.name AS product_name, fp.image AS product_image
                    FROM reviews r
                    JOIN users u ON r.user_id = u.user_id
                    JOIN featured_products fp ON r.product_id = fp.id
                    ORDER BY r.created_at DESC";
    $result_reviews = $conn->query($sql_reviews);
} else {
    $sql_reviews = "SELECT r.*, u.first_name, u.last_name, fp.name AS product_name, fp.image AS product_image
                    FROM reviews r
                    JOIN users u ON r.user_id = u.user_id
                    JOIN featured_products fp ON r.product_id = fp.id
                    WHERE r.seller_id = $seller_id
                    ORDER BY r.created_at DESC";
    $result_reviews = $conn->query($sql_reviews);
}

$reviews = [];
if ($result_reviews && $result_reviews->num_rows > 0) {
    while ($row = $result_reviews->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// ฟังก์ชันสำหรับส่งคืนข้อความดาว
function getStarRating($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $stars .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $stars;
}

// Handle Reply Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review_id']) && isset($_POST['reply_text'])) {
    $review_id = (int)$_POST['review_id'];
    $reply_text = trim($_POST['reply_text']);

    if ($review_id > 0 && !empty($reply_text)) {
        // ป้องกัน SQL Injection โดยใช้ Prepared Statements
        $sql_reply = "INSERT INTO review_replies (review_id, seller_id, reply_text) VALUES (?, ?, ?)";
        $stmt_reply = $conn->prepare($sql_reply);

        if ($stmt_reply) {
            $stmt_reply->bind_param("iis", $review_id, $seller_id, $reply_text);
            if ($stmt_reply->execute()) {
                echo "<script>alert('ส่งข้อความตอบกลับสำเร็จ'); window.location.href='messages_review_สินค้าของเรา.php';</script>";
                exit();
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการส่งข้อความตอบกลับ: " . $stmt_reply->error . "');</script>";
            }
            $stmt_reply->close();
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการเตรียม prepared statement: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('ข้อมูลไม่ถูกต้อง กรุณากรอกข้อมูลให้ครบถ้วน');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรีวิวสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .seller-panel {
            padding: 10px; /* ลด padding */
        }
        .sidebar {
            width: 200px; /* ลดความกว้าง */
            padding: 10px; /* ลด padding */
            background-color: #fff;
            border-right: 1px solid #eee;
        }
        .content {
            padding: 10px; /* ลด padding */
        }
        .review-card {
            margin-bottom: 10px; /* ลดระยะห่าง */
            border: 1px solid #ddd;
            padding: 8px; /* ลด padding */
            border-radius: 5px;
            background-color: #fff; /* เพิ่มสีพื้นหลัง */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* เพิ่มเงา */
        }
        .review-card .product-info {
            display: flex;
            align-items: center;
            margin-bottom: 5px; /* ลดระยะห่าง */
        }
        .review-card  .product-image {
            width: 40px; /* ลดขนาดรูป */
            height: 40px; /* ลดขนาดรูป */
            object-fit: cover;
            margin-right: 8px; /* ลดระยะห่าง */
            border-radius: 5px;
        }
        .review-card .product-name {
            font-size: 1rem; /* ลดขนาด font */
            margin: 0; /*  reset margin */
            font-weight: bold;
        }

        .review-card .rating {
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .review-card .user-info{
            font-size: 0.8rem;
            margin-bottom: 2px;

        }

        .review-card .comment {
            font-size: 0.9rem; /* ลดขนาด font */
            margin-bottom: 5px; /* ลดระยะห่าง */
        }

        .review-card .timestamp {
            font-size: 0.7rem; /* ลดขนาด font */
            color: #6c757d; /* สีจางลง */
        }

        /* ปรับปรุง responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
            }

            .review-card .product-info {
                flex-direction: column; /* Stack elements vertically on small screens */
            }
            .review-card .product-image {
                margin-right: 0;
                margin-bottom: 4px;
            }

        }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>
<div class="container-fluid">
    <div class="row">
        <?php include "_sidebar.php"; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">จัดการรีวิวสินค้า</h1>
            </div>

            <?php if (empty($reviews)): ?>
                <p>ไม่มีรีวิวสินค้า</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="product-info">
                            <img src="../uploads/สินค้า/<?php echo htmlspecialchars($review['product_image']); ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>" class="product-image">
                            <h5 class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></h5>
                        </div>

                        <div class="rating">
                            <?php echo getStarRating($review['rating']); ?>
                        </div>
                        <p class="user-info"><strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong></p>
                        <p class="comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <p class="timestamp">เมื่อ: <?php echo htmlspecialchars($review['created_at']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>