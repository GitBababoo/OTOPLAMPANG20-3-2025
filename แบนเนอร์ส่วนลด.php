<div class="carousel-inner">
    <?php
    $bannerIndex = 0;
    if ($conn) {
        $sql_banners = "SELECT * FROM banners LIMIT 3";
        $result_banners = $conn->query($sql_banners);

        if ($result_banners) {
            if ($result_banners->num_rows > 0) {
                while($row_banner = $result_banners->fetch_assoc()) {
                    $banner_title = sanitize($row_banner["title"]);
                    $banner_image = sanitize($row_banner["image"]);
                    $banner_link = sanitize($row_banner["link"]);
                    $imagePath = "uploads/แบนเนอร์/" . $banner_image;
                    $activeClass = ($bannerIndex == 0) ? 'active' : '';

                    echo '<div class="carousel-item ' . $activeClass . '">';
                    echo '<a href="' . $banner_link . '"><img class="d-block w-100 img-fluid" src="' . $imagePath . '" alt="' . $banner_title . '"></a>';
                    echo '</div>';
                    $bannerIndex++;
                }
            } else {
                echo "<div class='col-12 text-center'><small>ไม่พบแบนเนอร์</small></div>";
            }
        } else {
            echo "<div class='col-12 text-center'><small>Query failed: " . $conn->error . "</small></div>";
        }
    } else {
        echo "<div class='col-12 text-center'><small>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</small></div>";
    }
    ?>
</div>