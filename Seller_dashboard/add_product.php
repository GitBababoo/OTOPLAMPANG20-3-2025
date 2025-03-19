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

// Fetch categories
$sql_categories = "SELECT id, title FROM categories";
$result_categories = $conn->query($sql_categories);
$categories = [];
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Function to sanitize data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST["name"]);
    $description = sanitize($_POST["description"]);
    $price = floatval($_POST["price"]);
    $category_id = (int)$_POST["category_id"];
    $stock = (int)$_POST["stock"];
    $is_active = isset($_POST["is_active"]) ? 1 : 0;

    // Validate data. REMOVE descritption. All is required except desc..
    if (empty($name) || $price <= 0 || $category_id <= 0 || $stock < 0) {
        echo "<div class='alert alert-danger'>กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง. ชื่อสินค้า ราคา หมวดหมู่ และ สต็อก จำเป็นต้องมี</div>"; // Adjusted alert

    } else {
        // Handle file upload
        $target_dir = "../uploads/สินค้า/";
        $uploadOk = 1;
        $image = ""; // Initialize image variable

        if (!empty($_FILES["image"]["name"])) {
            $original_filename = basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

            $encoded_name = preg_replace('/[^a-zA-Z0-9ก-๙_-]/u', '_', $name);  //Sanitize the NAME file name part, dont mess it up and add garbage

            $new_filename = uniqid() . "_" . $encoded_name . "." . $imageFileType; // Use the sanitized name in  filename in DIR. This name file must match featured table NAME column , clean , easy and direct
            $target_file = $target_dir . $new_filename;


            // Check if file is an image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                echo "<div class='alert alert-danger'>ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ</div>";
                $uploadOk = 0;
            }

            // Check file size
            if ($_FILES["image"]["size"] > 2000000) {
                echo  "<div class='alert alert-danger'>ขนาดไฟล์ใหญ่เกินไป (สูงสุด 2MB)</div>";
                $uploadOk = 0;
            }

            // Check file type
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (!in_array($imageFileType, $allowed_types)) {
                echo  "<div class='alert alert-danger'>รองรับเฉพาะไฟล์ JPG, JPEG, PNG และ GIF เท่านั้น</div>";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = $new_filename;  // set new names,

                    // Insert data into the database . USE VARIABLE now. More standard this way and easy, set and USE set and use.

                    $sql = "INSERT INTO featured_products (seller_id, name, description, price, image, category_id, stock, is_active)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";  //Standard
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issdsiii", $seller_id, $name, $description, $price, $image, $category_id, $stock, $is_active);


                    if ($stmt->execute()) {
                        echo "<div class='alert alert-success'>เพิ่มสินค้าสำเร็จ</div>";
                        header("location: manage_products.php");
                        exit();
                    } else {
                        echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการเพิ่มสินค้า: " . $stmt->error . "</div>";
                    }
                    $stmt->close();
                } else {
                    echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการอัปโหลดไฟล์</div>";
                }
            }
        } else {
            echo "<div class='alert alert-danger'>กรุณาเลือกไฟล์ภาพ</div>"; // Image
        }


    }
}

?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>เพิ่มสินค้าใหม่</title>
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
            <?php include('../Seller_dashboard/_sidebar.php'); ?>
            <main role="main" class="col-md-9 content">
                <h2>เพิ่มสินค้าใหม่</h2>
                <a href="manage_products.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> กลับไปจัดการสินค้า
                </a>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">ชื่อสินค้า</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียดสินค้า</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>   <!------Make unrequired------->
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">ราคา</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">หมวดหมู่</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">เลือกหมวดหมู่</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">สต็อก</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">รูปภาพสินค้า</label>
                        <input type="file" class="form-control" id="image" name="image" required>   <!--- This needs TO BE required. Image or NAME . at LEAST  it can generate the directory files  required=""--->
                        <small class="text-muted">อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF เท่านั้น</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">เปิดใช้งาน</label>
                    </div>
                    <button type="submit" class="btn btn-primary">เพิ่มสินค้า</button>
                </form>
            </main>
        </div>
    </div>
<?php $conn->close(); ?>