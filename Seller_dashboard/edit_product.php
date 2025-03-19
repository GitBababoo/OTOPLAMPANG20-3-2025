<?php
session_start();
include "../inc/db_config.php";

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$sql_check_seller = "SELECT seller_id FROM sellers WHERE user_id = ?";
$stmt_check_seller = $conn->prepare($sql_check_seller);
$stmt_check_seller->bind_param("i", $user_id);
$stmt_check_seller->execute();
$result_check_seller = $stmt_check_seller->get_result();

if ($result_check_seller->num_rows == 0) {
    echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ <a href='../index.php'>กลับสู่หน้าหลัก</a>";
    exit();
}
$seller_id = (int)$result_check_seller->fetch_assoc()['seller_id'];
$stmt_check_seller->close();

// Function to sanitize data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get product ID from GET request
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    echo "รหัสสินค้าไม่ถูกต้อง";
    exit();
}

// Fetch categories
$sql_categories = "SELECT id, title FROM categories";
$result_categories = $conn->query($sql_categories);
$categories = [];
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch product data
$sql_product = "SELECT * FROM featured_products WHERE id = ? AND seller_id = ?";
$stmt_product = $conn->prepare($sql_product);
$stmt_product->bind_param("ii", $product_id, $seller_id);
$stmt_product->execute();
$result_product = $stmt_product->get_result();
$product = $result_product->fetch_assoc();
$stmt_product->close();

if (!$product) {
    echo "ไม่พบสินค้า";
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST["name"]);
    $description = sanitize($_POST["description"]);
    $price = floatval($_POST["price"]);
    $category_id = (int)$_POST["category_id"];
    $stock = (int)$_POST["stock"];
    $is_active = isset($_POST["is_active"]) ? 1 : 0;

    // Validate data
    if (empty($name) || $price <= 0 || $category_id <= 0 || $stock < 0) {  //Removed empty($description)
        echo "<div class='alert alert-danger'>กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง. ชื่อสินค้า ราคา หมวดหมู่ และ สต็อก จำเป็นต้องมี</div>";  //Adjusted message
    } else {
        $image = $product['image']; // Default to existing image
        $uploadOk = 1;  //Initialize $uploadOK here. This fixes it!


        // Handle file upload (if a new image is uploaded)
        if($_FILES["image"]["name"] != ''){
            $target_dir = "../uploads/สินค้า/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);

            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check !== false) {
                //  echo "File is an image - " . $check["mime"] . ".";   //No Need to show these...

            } else {
                echo "File is not an image.";
                $uploadOk = 0;
            }
            // Check file size
            if ($_FILES["image"]["size"] > 500000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }

            // Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif" ) {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
            } else {
                // Check if file already exists
                $new_file_name = uniqid().".".$imageFileType;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Delete old image
                    $old_image_path = "../uploads/สินค้า/" . $product["image"];
                    if (file_exists($old_image_path) && $product["image"]) { // Check file exist before unlinking and it's not a null value.
                        unlink($old_image_path);
                    }

                    $image = $new_file_name; // Update image with new file name

                } else {
                    echo "Sorry, there was an error uploading your file.";
                    $uploadOk = 0;
                }
            }
        }

        if ($uploadOk == 1) {
            // Update data into the database WITH or WITHOUT new image based on upload success
            $sql = "UPDATE featured_products SET name = ?, description = ?, price = ?, image = ?, category_id = ?, stock = ?, is_active = ? WHERE id = ? AND seller_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdssiiii", $name, $description, $price, $image, $category_id, $stock, $is_active, $product_id, $seller_id);  //Always include `image` param and name

            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>แก้ไขสินค้าสำเร็จ</div>";
                header("Location: manage_products.php");
                exit();
            } else {
                echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการแก้ไขสินค้า: " . $stmt->error . "</div>";
            }
            $stmt->close();

            // Refresh product data

            $sql_product = "SELECT * FROM featured_products WHERE id = ? AND seller_id = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("ii", $product_id, $seller_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();
            $product = $result_product->fetch_assoc();
            $stmt_product->close();
        }


    }
}
?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>แก้ไขสินค้า</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #f8f9fa;
            }
            .seller-panel {
                padding: 20px;
            }

            .sidebar {
                width: 220px;
                padding: 15px;
                background-color: #fff;
                border-right: 1px solid #eee;
            }

            .content {
                padding: 20px;
            }
            .icon-link {
                margin-right: 5px; /* Space between icon and link text */
            }

            /* Style the cards to take up the full available width */
            .full-width-card {
                width: 100%; /* Use the full width of its parent */
                margin-bottom: 15px; /* Space between the cards */
            }

            .sales-data-item {
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
    <?php include('navbar.php'); ?>

    <div class="container-fluid seller-panel">
        <div class="row">
            <?php include('_sidebar.php'); ?>
            <main role="main" class="col-md-9 content">
                <h2>แก้ไขสินค้า</h2>
                <a href="manage_products.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> กลับไปจัดการสินค้า
                </a>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">ชื่อสินค้า</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียดสินค้า</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">ราคา</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">หมวดหมู่</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">เลือกหมวดหมู่</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo ($category['id'] == $product['category_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">สต็อก</label>
                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">รูปภาพสินค้า (หากต้องการแก้ไข)</label>
                        <input type="file" class="form-control" id="image" name="image">
                        <small class="text-muted">อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF เท่านั้น</small>
                        <br>
                        <img src="../uploads/สินค้า/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 150px;">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo ($product['is_active'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">เปิดใช้งาน</label>
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                </form>
            </main>

        </div>
    </div>



    </body>
    </html>

<?php $conn->close(); ?>