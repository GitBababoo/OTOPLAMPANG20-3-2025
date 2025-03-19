<?php
// _product_modal.php
// รับข้อมูลที่ต้องการแสดงใน Modal
$product_name = htmlspecialchars($row["name"]);
$product_description = htmlspecialchars($row["description"]);
$product_price = $row["price"];
$product_image = $row["image"];
$product_id = $row["id"];

// รับ reviews ที่ถูก fetch มาจาก products_all.php แล้ว
// get Product review for products.
$reviews = isset($product_reviews[$product_id]) ? $product_reviews[$product_id] : [];
$total_ratings = 0;
$num_reviews = count($reviews);

foreach ($reviews as $review) {
    $total_ratings += $review["rating"];
}

// Calculate average rating
$average_rating = ($num_reviews > 0) ? ($total_ratings / $num_reviews) : 0;

?>
<style>
    /* Styles for the modal layout */
    .product-modal-body {
        display: flex;
        align-items: center; /* Vertical alignment */
    }

    .product-image-container {
        width: 50%; /* Image takes half of the modal width */
        padding-right: 20px; /* Space between image and content */
    }

    .product-image {
        max-width: 100%; /* Image fills its container */
        height: auto;
        display: block;
        border-radius: 8px; /* Optional: Rounded corners */
    }

    .product-details {
        width: 50%; /* Details take half of the modal width */
    }

    .review-section {
        margin-top: 20px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .star-rating {
        color: #ffc107;
    }

    .review-item {
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .product-modal-body {
            flex-direction: column; /* Stack image and details on small screens */
        }

        .product-image-container,
        .product-details {
            width: 100%; /* Full width on small screens */
            padding-right: 0;
        }

        .product-image-container {
            margin-bottom: 20px; /* Space between image and details */
        }
    }
</style>

<div class="modal fade" id="productModal<?php echo $product_id; ?>" tabindex="-1" aria-labelledby="productModalLabel<?php echo $product_id; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Use modal-lg for a larger modal -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel<?php echo $product_id; ?>"><?php echo $product_name; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body product-modal-body"> <!-- Apply flex layout -->

                <div class="product-image-container">
                    <img src="uploads/สินค้า/<?php echo $product_image; ?>" class="product-image" alt="<?php echo $product_name; ?>">
                </div>

                <div class="product-details">
                    <h4><?php echo $product_name; ?></h4>
                    <p><?php echo $product_description; ?></p>
                    <p class="fw-bold">ราคา: $<?php echo $product_price; ?></p>

                    <!-- Form เพิ่มลงตะกร้า -->
                    <?php if ($user_id === 0): ?>
                        <p>กรุณาเข้าสู่ระบบ</p>
                    <?php else: ?>
                        <form id="add-to-cart-form-<?php echo $product_id; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <div class="mb-3">
                                <label for="quantity-<?php echo $product_id; ?>" class="form-label">จำนวน:</label>
                                <input type="number" class="form-control" id="quantity-<?php echo $product_id; ?>" name="quantity" value="1" min="1">
                            </div>
                            <button type="button" class="btn btn-success add-to-cart-button" data-product-id="<?php echo $product_id; ?>">เพิ่มลงตะกร้า</button>
                        </form>
                        <div id="add-to-cart-message-<?php echo $product_id; ?>"></div>
                    <?php endif; ?>

                    <!-- Review Form -->
                    <?php if ($is_logged_in): ?>
                        <div id="review-form-<?php echo $product_id; ?>" class="mt-3">
                            <hr>
                            <p>เขียนรีวิวสินค้า</p>
                            <div class="star-rating-form" data-product-id="<?php echo $product_id; ?>">
                                <i class="far fa-star" data-rating="1"></i>
                                <i class="far fa-star" data-rating="2"></i>
                                <i class="far fa-star" data-rating="3"></i>
                                <i class="far fa-star" data-rating="4"></i>
                                <i class="far fa-star" data-rating="5"></i>
                            </div>
                            <textarea class="form-control mt-2" id="comment-<?php echo $product_id; ?>" rows="3" placeholder="เขียนรีวิวของคุณ"></textarea>
                            <button type="button" class="btn btn-primary mt-2" onclick="submitReview(<?php echo $product_id; ?>)">ส่งรีวิว</button>
                            <div id="review-message-<?php echo $product_id; ?>" class="mt-2"></div>
                        </div>
                    <?php else: ?>
                        <p class="mt-3">กรุณา <a href="login.php">ล็อกอิน</a> เพื่อเขียนรีวิว</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Reviews Display Section -->
            <div class="modal-body review-section">
                <h6 class="mt-4">รีวิวสินค้า</h6>
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <p><strong><?php echo htmlspecialchars($review['username']); ?></strong></p>
                            <div class="star-rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $review['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <p><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                <?php endif; ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>