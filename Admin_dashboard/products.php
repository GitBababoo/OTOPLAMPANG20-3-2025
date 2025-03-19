<?php
session_start();
require_once 'db_config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit();
}

// ไดเรกทอรีสำหรับเก็บรูปภาพ
$target_dir = "../uploads/สินค้า/";

// ฟังก์ชันสร้างชื่อไฟล์ที่ไม่ซ้ำ
function generateUniqueFilename($target_dir, $filename, $extension) {
    $new_filename = $filename . "." . $extension;
    $i = 1;
    while (file_exists($target_dir . $new_filename)) {
        $new_filename = $filename . "_" . $i . "." . $extension;
        $i++;
    }
    return $new_filename;
}

// จัดการการส่งฟอร์ม
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        // เพิ่มสินค้าใหม่
        $name = htmlspecialchars($_POST['name']);
        $description = htmlspecialchars($_POST['description']);
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $seller_id = $_POST['seller_id'];
        $stock = $_POST['stock']; // เพิ่ม stock

        // ตรวจสอบค่าที่จำเป็น
        if (empty($name) || empty($price) || empty($category_id) || empty($seller_id) || empty($stock)) {
            $message = "<div class='alert alert-danger'>กรุณากรอกข้อมูลให้ครบทุกช่อง</div>";
        } else {
            // ตรวจสอบว่ามีชื่อสินค้าซ้ำกันหรือไม่
            $sql_check = "SELECT COUNT(*) AS count FROM featured_products WHERE name = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $name);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $row_check = $result_check->fetch_assoc();
            if ($row_check['count'] > 0) {
                $message = "<div class='alert alert-danger'>ชื่อสินค้าซ้ำกัน กรุณาใช้ชื่ออื่น</div>";
            } else {
                // การอัปโหลดรูปภาพ
                $uploadOk = 1;
                $image = ""; // กำหนดค่าเริ่มต้นให้เป็นค่าว่าง

                if (!empty($_FILES["image"]["name"])) {
                    $original_filename = basename($_FILES["image"]["name"]);
                    $imageFileType = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                    $date = date("d-m-Y");
                    $encoded_name = preg_replace('/[^a-zA-Z0-9ก-๙_-]/u', '_', $name);
                    $new_filename = $encoded_name . "_" . $date . "." . $imageFileType;
                    $target_file = $target_dir . $new_filename;

                    // ตรวจสอบว่าไฟล์เป็นรูปภาพ
                    $check = getimagesize($_FILES["image"]["tmp_name"]);
                    if ($check === false) {
                        $message = "<div class='alert alert-danger'>ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ</div>";
                        $uploadOk = 0;
                    }

                    // ตรวจสอบขนาดไฟล์ (สูงสุด 2MB)
                    if ($_FILES["image"]["size"] > 2000000) {
                        $message = "<div class='alert alert-danger'>ขนาดไฟล์ใหญ่เกินไป (สูงสุด 2MB)</div>";
                        $uploadOk = 0;
                    }

                    // ตรวจสอบประเภทไฟล์
                    $allowed_types = ["jpg", "jpeg", "png", "gif"];
                    if (!in_array($imageFileType, $allowed_types)) {
                        $message = "<div class='alert alert-danger'>รองรับเฉพาะไฟล์ JPG, JPEG, PNG และ GIF เท่านั้น</div>";
                        $uploadOk = 0;
                    }

                    if ($uploadOk == 1) {
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                            $image = $new_filename;
                        } else {
                            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการอัปโหลดไฟล์</div>";
                            $uploadOk = 0;
                        }
                    }
                }

                // ตรวจสอบค่าของ $image ว่าถูกตั้งค่าหรือไม่
                if (empty($image)) {
                    $image = "default.jpg"; // ตั้งค่าเป็นรูปภาพเริ่มต้น ถ้าไม่มีการอัปโหลด
                }

                // ถ้าอัปโหลดรูปภาพสำเร็จ หรือ ไม่มีรูปภาพให้ดำเนินการต่อ
                if ($uploadOk == 1) {
                    $sql_add = "INSERT INTO featured_products (seller_id, name, description, price, category_id, image, stock) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql_add);
                    $stmt->bind_param("issdisi", $seller_id, $name, $description, $price, $category_id, $image, $stock);

                    if ($stmt->execute()) {
                        $message = "<div class='alert alert-success'>เพิ่มสินค้าสำเร็จ</div>";
                    } else {
                        $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
                    }
                }
            }
        }
    } elseif (isset($_POST['edit'])) {
        // แก้ไขสินค้า
        $id = $_POST['id'];
        $name = htmlspecialchars($_POST['name']);
        $description = htmlspecialchars($_POST['description']);
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $seller_id = $_POST['seller_id'];
        $stock = $_POST['stock']; // เพิ่ม stock

        if (empty($name) || empty($price) || empty($category_id) || empty($seller_id) || empty($stock)) {
            $message = "<div class='alert alert-danger'>กรุณากรอกข้อมูลให้ครบทุกช่อง</div>";
        } else {
            $sql_edit = "UPDATE featured_products SET seller_id=?, name=?, description=?, price=?, category_id=?, stock=? WHERE id=?";
            $stmt = $conn->prepare($sql_edit);
            $stmt->bind_param("issdiii", $seller_id, $name, $description, $price, $category_id, $stock, $id);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>แก้ไขสินค้าสำเร็จ</div>";
            } else {
                $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
            }
        }
    } elseif (isset($_POST['delete'])) {
        // ลบสินค้า
        $id = $_POST['id'];

        // ดึงชื่อรูปภาพก่อนลบ
        $sql_select_image = "SELECT image FROM featured_products WHERE id = ?";
        $stmt_select_image = $conn->prepare($sql_select_image);
        $stmt_select_image->bind_param("i", $id);
        $stmt_select_image->execute();
        $result_select_image = $stmt_select_image->get_result();

        if ($result_select_image->num_rows > 0) {
            $row_image = $result_select_image->fetch_assoc();
            $image_name = $row_image['image'];

            // ลบข้อมูลสินค้าจากฐานข้อมูล
            $sql_delete = "DELETE FROM featured_products WHERE id=?";
            $stmt = $conn->prepare($sql_delete);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                // ลบรูปภาพออกจากโฟลเดอร์ uploads/สินค้า/
                $image_path = "../uploads/สินค้า/" . $image_name;
                // ตรวจสอบว่ารูปภาพไม่ใช่ default.jpg และมีอยู่จริง
                if ($image_name != "default.jpg" && file_exists($image_path)) {
                    if (unlink($image_path)) {
                        $message = "<div class='alert alert-success'>ลบสินค้าและรูปภาพสำเร็จ</div>";
                    } else {
                        $message = "<div class='alert alert-warning'>ลบสินค้าสำเร็จ แต่ไม่สามารถลบรูปภาพได้</div>";
                    }
                } else {
                    $message = "<div class='alert alert-success'>ลบสินค้าสำเร็จ (ไม่มีรูปภาพให้ลบ หรือเป็นรูปเริ่มต้น)</div>";
                }

            } else {
                $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>ไม่พบสินค้าที่ต้องการลบ</div>";
        }
    }
}

// การตั้งค่าหน้าละกี่รายการ (Pagination)
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// ดึงข้อมูลสินค้า
$sql = "SELECT fp.*, s.store_name 
        FROM featured_products fp 
        JOIN sellers s ON fp.seller_id = s.seller_id
        LIMIT $start, $limit";
$result = $conn->query($sql);

// ดึงข้อมูลหมวดหมู่
$sql_categories = "SELECT id, title FROM categories";
$result_categories = $conn->query($sql_categories);

// ดึงข้อมูลร้านค้า
$sql_sellers = "SELECT seller_id, store_name FROM sellers";
$result_sellers = $conn->query($sql_sellers);

// นับจำนวนสินค้าทั้งหมด (สำหรับแบ่งหน้า)
$sql_total = "SELECT COUNT(*) AS total FROM featured_products";
$result_total = $conn->query($sql_total);
$row_total = $result_total->fetch_assoc();
$total_products = $row_total['total'];
$total_pages = ceil($total_products / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
        }

        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }

        .sidebar a {
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            display: block;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .content {
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
            <?php include 'sidebar.php'; ?>
        </nav>

        <!-- Content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 content">

            <h2>จัดการสินค้า</h2>

            <!-- แสดงข้อความแจ้งเตือน -->
            <?php echo $message; ?>

            <!-- ฟอร์มเพิ่มสินค้า -->
            <h3>เพิ่มสินค้า</h3>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">ชื่อสินค้า:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">รายละเอียด:</label>
                    <textarea class="form-control" id="description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">ราคา (บาท):</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="category_id">หมวดหมู่สินค้า:</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <?php
                        while ($row_category = $result_categories->fetch_assoc()): ?>
                            <option value="<?php echo $row_category['id']; ?>"><?php echo $row_category['title']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="seller_id">ร้านค้า:</label>
                    <select class="form-control" id="seller_id" name="seller_id" required>
                        <?php
                        while ($row_seller = $result_sellers->fetch_assoc()): ?>
                            <option value="<?php echo $row_seller['seller_id']; ?>"><?php echo htmlspecialchars($row_seller['store_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" class="form-control" id="stock" name="stock" value="0" required>
                </div>
                <div class="form-group">
                    <label for="image">อัปโหลดรูปภาพ:</label>
                    <input type="file" class="form-control-file" id="image" name="image">
                </div>
                <button type="submit" class="btn btn-primary" name="add">เพิ่มสินค้า</button>
            </form>


            <!-- Product List -->
            <h3>Product List</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Seller</th>
                        <th>Stock</th>  <!-- เพิ่ม header -->
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id"]. "</td>";
                            echo "<td>" . htmlspecialchars($row["name"]). "</td>";
                            echo "<td>" . htmlspecialchars($row["description"]). "</td>";
                            echo "<td>" . $row["price"]. "</td>";
                            echo "<td>" . $row["category_id"]. "</td>";
                            echo "<td>" . htmlspecialchars($row["store_name"]) . "</td>";
                            echo "<td>" . $row["stock"] . "</td>"; // แสดง stock
                            $image_path = (!empty($row["image"])) ? "../uploads/สินค้า/" . $row["image"] : "path/to/default.jpg";
                            echo "<td><img src='" . $image_path . "' width='100'></td>";
                            echo "<td>
                                <button type='button' class='btn btn-sm btn-primary' data-toggle='modal' data-target='#editProductModal" . $row["id"] . "'>Edit</button>
                                <form method='post' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this product?\");'>
                                    <input type='hidden' name='id' value='" . $row["id"] . "'>
                                    <button type='submit' class='btn btn-sm btn-danger' name='delete'>Delete</button>
                                </form>
                              </td>";
                            echo "</tr>";
                            ?>
                            <!-- Edit Product Modal -->
                            <div class="modal fade" id="editProductModal<?php echo $row["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel<?php echo $row["id"]; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editProductModalLabel<?php echo $row["id"]; ?>">Edit Product</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">×</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
                                                <div class="form-group">
                                                    <label for="name">Name:</label>
                                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($row["name"]); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="description">Description:</label>
                                                    <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($row["description"]); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="price">Price:</label>
                                                    <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo $row["price"]; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="category_id">Category:</label>
                                                    <select class="form-control" id="category_id" name="category_id" required>
                                                        <?php
                                                        $result_categories->data_seek(0); // Reset pointer to the beginning
                                                        while ($row_category = $result_categories->fetch_assoc()): ?>
                                                            <option value="<?php echo $row_category['id']; ?>" <?php if ($row["category_id"] == $row_category['id']) echo 'selected'; ?>><?php echo $row_category['title']; ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="seller_id">Seller:</label>
                                                    <select class="form-control" id="seller_id" name="seller_id" required>
                                                        <?php
                                                        $result_sellers->data_seek(0); // Reset pointer to the beginning
                                                        while ($row_seller = $result_sellers->fetch_assoc()): ?>
                                                            <option value="<?php echo $row_seller['seller_id']; ?>" <?php if ($row["seller_id"] == $row_seller['seller_id']) echo 'selected'; ?>><?php echo htmlspecialchars($row_seller['store_name']); ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="stock">Stock:</label>
                                                    <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $row["stock"]; ?>" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary" name="edit">Save changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='7'>No products found.</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($total_pages > 1): ?>
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="products.php?page=<?php echo ($page - 1); ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="products.php?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="products.php?page=<?php echo ($page + 1); ?>">Next</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>

        </main>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
