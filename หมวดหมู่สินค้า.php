<!-- Categories -->
<h6 class="text-center mt-1 mb-2">เลือกชมตามหมวดหมู่</h6>
<div class="row">
    <?php
    if ($conn) {
        $sql = "SELECT * FROM categories LIMIT 4";
        $result = $conn->query($sql);

        if ($result) {
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $category_id = sanitize($row["id"]);
                    $category_title = sanitize($row["title"]);
                    $category_description = sanitize($row["description"]);
                    $category_image = sanitize($row["image"]);

                    $imagePath = "uploads/หมวดหมู่สินค้า/" . $category_image;

                    // สร้าง link ไปยัง products_all.php พร้อม category_id
                    $category_link = "products_all.php?category_id=" . $category_id;

                    echo '<div class="col-md-3 mb-2">';
                    echo '<a href="' . $category_link . '" class="category-card-link">';
                    echo '<div class="card category-card">';
                    echo '<img src="' . $imagePath . '" class="card-img-top img-fluid" alt="' . $category_title . '">';
                    echo '<div class="card-body">';
                    echo '<h6 class="card-title text-center">' . $category_title . '</h6>';
                    echo '<p class="card-text text-center"><small>' . $category_description . '</small></p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</a>';
                    echo '</div>';
                }
            } else {
                echo "<div class='col-12 text-center'><small>ไม่พบหมวดหมู่สินค้า</small></div>";
            }
        } else {
            echo "<div class='col-12 text-center'><small>Query failed: " . $conn->error . "</small></div>";
        }
    } else {
        echo "<div class='col-12 text-center'><small>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</small></div>";
    }
    ?>
</div>