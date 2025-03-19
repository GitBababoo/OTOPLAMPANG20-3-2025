<h6 class="text-center mt-2 mb-2">สินค้ามาใหม่</h6>
<div class="row">
    <?php
    if ($conn) {
        $current_date = date('Y-m-d H:i:s');

        // Query ดึงข้อมูลสินค้า, หมวดหมู่ (ถ้ามี), และโปรโมชั่น (ปรับปรุง)
        $sql = "SELECT
                fp.*,
                COALESCE(d.discount_percent, 0) AS discount_percentage
            FROM featured_products fp
            LEFT JOIN product_promotions pp ON fp.id = pp.product_id  AND pp.is_active = 1 AND pp.start_date <= NOW() AND pp.end_date >= NOW()
            LEFT JOIN discounts d ON pp.discount_id = d.id
            WHERE fp.is_active = 1
            ORDER BY fp.created_at DESC
            LIMIT 8";

        $result = $conn->query($sql); // ไม่ต้อง Prepare เพราะไม่ได้ใช้ user input ตรงๆ

        if ($result) {
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $product_id = htmlspecialchars($row["id"]);  //ใช้ htmlspecialchars เพื่อป้องกัน XSS
                    $product_name = htmlspecialchars($row["name"]);
                    $product_description = htmlspecialchars($row["description"]);
                    $product_price = $row["price"];
                    $product_image = htmlspecialchars($row["image"]);
                    $product_stock = (int)$row["stock"];
                    $discount_percentage = (int)$row["discount_percentage"];


                    $product_image_path = "uploads/สินค้า/" . $product_image;
                    $product_link = "product.php?product_id=" . $product_id;

                    $discounted_price = $product_price * (1- ($discount_percentage/100));


                    echo '<div class="col-md-3 mb-2">';
                    echo '<div class="card product-card">';
                    echo '<a href="' . $product_link . '" class="product-link">';
                    echo '<img src="' . $product_image_path . '" class="card-img-top img-fluid" alt="' . $product_name . '" onerror="this.src=\'placeholder.png\'">';
                    echo '<div class="card-body">';
                    echo '<h6 class="card-title">' . $product_name . '</h6>';
                    echo '<p class="card-text"><small>' . $product_description . '</small></p>';


                    if ($discount_percentage > 0) {
                        echo '<p class="card-text"><small><del>฿' . number_format($product_price, 2) . '</del></small> - ';
                        echo '<span class="text-danger">ลด ' . $discount_percentage . '%</span></p>';
                        echo '<p class="card-text font-weight-bold"><small>฿' . number_format($discounted_price, 2) . '</small></p>';
                    }
                    else {
                        echo '<p class="card-text font-weight-bold"><small>฿' . number_format($product_price, 2) . '</small></p>';
                    }



                    echo '<p class="card-text product-stock"><small>เหลือ: ' . $product_stock . ' ชิ้น</small>';


                    if ($product_stock <= 5 && $product_stock > 0) {
                        echo ' <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ใกล้หมดแล้ว!</span></p>';
                    }
                    elseif($product_stock <= 0){
                        echo '<span class="text-danger">  สินค้าหมด</span></p>';

                    }
                    else{
                        echo'</p>';
                    }


                    echo ' <button class="btn btn-sm btn-outline-secondary add-to-cart" data-product-id="' . $product_id . '">  เพิ่มลงตะกร้าสินค้า  <i class="fa-solid fa-cart-shopping" style="color: #2f70ee;"></i> </button>

           ';
                    echo '</div>';
                    echo'</div>';
                    echo '</div>';
                }
            } else {
                echo "<div class='col-12 text-center'><small>ไม่มีสินค้าแนะนำในขณะนี้</small></div>";
            }
        } else {
            echo "<div class='col-12 text-center'><small>เกิดข้อผิดพลาดในการดึงข้อมูล: " . $conn->error . "</small></div>";
        }
    } else {
        echo "<div class='col-12 text-center'><small>ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้</small></div>";
    }
    ?>
</div>