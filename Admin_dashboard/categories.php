<?php
session_start();
require_once 'db_config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit();
}

// ไดเรกทอรีสำหรับเก็บรูปภาพหมวดหมู่สินค้า
$target_dir = "../uploads/หมวดหมู่สินค้า/";

// สร้างไดเรกทอรี หากยังไม่มี
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

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
        // เพิ่มหมวดหมู่สินค้า
        $title = htmlspecialchars($_POST['title']);
        $description = htmlspecialchars($_POST['description']);
        $link = htmlspecialchars($_POST['link']);

        // อัปโหลดรูปภาพ
        if (!empty($_FILES["image"]["name"])) {
            $original_filename = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $filename = preg_replace('/[^a-zA-Z0-9ก-๙_-]/u', '_', $original_filename); // Clean filename
            $new_filename = generateUniqueFilename($target_dir, $filename, $imageFileType);
            $target_file = $target_dir . $new_filename;
            $uploadOk = 1;

            // ตรวจสอบว่าเป็นไฟล์ภาพจริงหรือไม่
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $message = "<div class='alert alert-danger'>ไฟล์ไม่ใช่รูปภาพ</div>";
                $uploadOk = 0;
            }

            // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2000KB)
            if ($_FILES["image"]["size"] > 2000000) {
                $message = "<div class='alert alert-danger'>ขออภัย ไฟล์ของคุณมีขนาดใหญ่เกินไป (สูงสุด 2MB)</div>";
                $uploadOk = 0;
            }

            // อนุญาตเฉพาะบางนามสกุลไฟล์
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (!in_array($imageFileType, $allowed_types)) {
                $message = "<div class='alert alert-danger'>ขออภัย อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG และ GIF เท่านั้น</div>";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // อัปโหลดไฟล์สำเร็จ
                    $image = $new_filename; // เก็บชื่อไฟล์ในฐานข้อมูล
                } else {
                    $message = "<div class='alert alert-danger'>ขออภัย เกิดข้อผิดพลาดในการอัปโหลดไฟล์ของคุณ</div>";
                    $uploadOk = 0;
                }
            }
        } else {
            $image = ""; // ไม่มีรูปภาพ
            $uploadOk = 1; // ไม่ต้องอัปโหลด
        }

        if ($uploadOk == 1) {
            // ตรวจสอบข้อมูล
            if (empty($title)) {
                $message = "<div class='alert alert-danger'>โปรดกรอกชื่อหมวดหมู่</div>";
            } else {
                // ใช้ Prepared Statement
                $sql_add = "INSERT INTO categories (title, description, image, link) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_add);
                $stmt->bind_param("ssss", $title, $description, $image, $link);

                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>เพิ่มหมวดหมู่สินค้าสำเร็จ</div>";
                } else {
                    $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่: " . $stmt->error . "</div>";
                }
            }
        }
    } elseif (isset($_POST['edit'])) {
        // แก้ไขหมวดหมู่สินค้า
        $id = $_POST['id'];
        $title = htmlspecialchars($_POST['title']);
        $description = htmlspecialchars($_POST['description']);
        $link = htmlspecialchars($_POST['link']);

        // ดึงข้อมูลรูปภาพเก่า
        $sql_select_image = "SELECT image FROM categories WHERE id = ?";
        $stmt_select_image = $conn->prepare($sql_select_image);
        $stmt_select_image->bind_param("i", $id);
        $stmt_select_image->execute();
        $result_select_image = $stmt_select_image->get_result();
        $old_image = "";
        if ($result_select_image->num_rows > 0) {
            $row_image = $result_select_image->fetch_assoc();
            $old_image = $row_image['image'];
        }

        // อัปโหลดรูปภาพ (แก้ไข)
        if (!empty($_FILES["image"]["name"])) {
            $original_filename = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $filename = preg_replace('/[^a-zA-Z0-9ก-๙_-]/u', '_', $original_filename); // Clean filename
            $new_filename = generateUniqueFilename($target_dir, $filename, $imageFileType);
            $target_file = $target_dir . $new_filename;
            $uploadOk = 1;

            // ตรวจสอบว่าเป็นไฟล์ภาพจริงหรือไม่
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $message = "<div class='alert alert-danger'>ไฟล์ไม่ใช่รูปภาพ</div>";
                $uploadOk = 0;
            }

            // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2000KB)
            if ($_FILES["image"]["size"] > 2000000) {
                $message = "<div class='alert alert-danger'>ขออภัย ไฟล์ของคุณมีขนาดใหญ่เกินไป (สูงสุด 2MB)</div>";
                $uploadOk = 0;
            }

            // อนุญาตเฉพาะบางนามสกุลไฟล์
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (!in_array($imageFileType, $allowed_types)) {
                $message = "<div class='alert alert-danger'>ขออภัย อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG และ GIF เท่านั้น</div>";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // อัปโหลดไฟล์สำเร็จ
                    $image = $new_filename; // เก็บชื่อไฟล์ในฐานข้อมูล

                    // ลบรูปภาพเก่า
                    if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                        unlink($target_dir . $old_image);
                    }
                } else {
                    $message = "<div class='alert alert-danger'>ขออภัย เกิดข้อผิดพลาดในการอัปโหลดไฟล์ของคุณ</div>";
                    $uploadOk = 0;
                }
            }
        } else {
            // ไม่มีการอัปโหลดรูปภาพใหม่
            $image = $old_image;
            $uploadOk = 1; // ไม่ต้องอัปโหลด
        }

        if ($uploadOk == 1) {
            // อัปเดตข้อมูล
            $sql_edit = "UPDATE categories SET title=?, description=?, image=?, link=? WHERE id=?";
            $stmt = $conn->prepare($sql_edit);
            $stmt->bind_param("ssssi", $title, $description, $image, $link, $id);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>แก้ไขหมวดหมู่สินค้าสำเร็จ</div>";
            } else {
                $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่: " . $stmt->error . "</div>";
            }
        }
    } elseif (isset($_POST['delete'])) {
        // ลบหมวดหมู่สินค้า
        $id = $_POST['id'];

        // ดึงข้อมูลรูปภาพเก่า
        $sql_select_image = "SELECT image FROM categories WHERE id = ?";
        $stmt_select_image = $conn->prepare($sql_select_image);
        $stmt_select_image->bind_param("i", $id);
        $stmt_select_image->execute();
        $result_select_image = $stmt_select_image->get_result();
        $old_image = "";
        if ($result_select_image->num_rows > 0) {
            $row_image = $result_select_image->fetch_assoc();
            $old_image = $row_image['image'];
        }

        // ใช้ Prepared Statement
        $sql_delete = "DELETE FROM categories WHERE id=?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // ลบรูปภาพ
            if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                unlink($target_dir . $old_image);
            }
            $message = "<div class='alert alert-success'>ลบหมวดหมู่สินค้าสำเร็จ</div>";
        } else {
            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการลบหมวดหมู่: " . $stmt->error . "</div>";
        }
    }
}

// ดึงข้อมูลหมวดหมู่สินค้า
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>การจัดการหมวดหมู่สินค้า</title>
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
                <h2>การจัดการหมวดหมู่สินค้า</h2>

                <!-- แสดงข้อความ -->
                <?php echo $message; ?>

                <!-- ฟอร์มเพิ่มหมวดหมู่สินค้า -->
                <h3>เพิ่มหมวดหมู่สินค้า</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">ชื่อหมวดหมู่:</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">รายละเอียด:</label>
                        <textarea class="form-control" id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image">รูปภาพ:</label>
                        <input type="file" class="form-control-file" id="image" name="image">
                        <small class="form-text text-muted">ขนาดไฟล์ไม่เกิน 2MB</small>
                    </div>
                    <div class="form-group">
                        <label for="link">ลิงก์:</label>
                        <input type="text" class="form-control" id="link" name="link">
                    </div>
                    <button type="submit" class="btn btn-primary" name="add">เพิ่ม</button>
                </form>

                <!-- รายการหมวดหมู่สินค้า -->
                <h3>รายการหมวดหมู่สินค้า</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อหมวดหมู่</th>
                            <th>รายละเอียด</th>
                            <th>รูปภาพ</th>
                            <th>ลิงก์</th>
                            <th>การกระทำ</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $image_path = (!empty($row["image"])) ? $target_dir . $row["image"] : "path/to/default.jpg";
                                echo "<tr>";
                                echo "<td>" . $row["id"]. "</td>";
                                echo "<td>" . htmlspecialchars($row["title"]). "</td>";
                                echo "<td>" . htmlspecialchars($row["description"]). "</td>";
                                echo "<td><img src='" . $image_path . "' width='100'></td>"; // Display image
                                echo "<td>" . htmlspecialchars($row["link"]). "</td>";
                                echo "<td>
                                    <button type='button' class='btn btn-sm btn-primary' data-toggle='modal' data-target='#editCategoryModal" . $row["id"] . "'>แก้ไข</button>
                                    <form method='post' style='display:inline;' onsubmit='return confirm(\"คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่นี้?\");'>
                                        <input type='hidden' name='id' value='" . $row["id"] . "'>
                                        <button type='submit' class='btn btn-sm btn-danger' name='delete'>ลบ</button>
                                    </form>
                                  </td>";
                                echo "</tr>";
                                ?>
                                <!-- Edit Category Modal -->
                                <div class="modal fade" id="editCategoryModal<?php echo $row["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel<?php echo $row["id"]; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editCategoryModalLabel<?php echo $row["id"]; ?>">แก้ไขหมวดหมู่สินค้า</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post" enctype="multipart/form-data">
                                                    <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
                                                    <div class="form-group">
                                                        <label for="title">ชื่อหมวดหมู่:</label>
                                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($row["title"]); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="description">รายละเอียด:</label>
                                                        <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($row["description"]); ?></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="image">รูปภาพ:</label>
                                                        <input type="file" class="form-control-file" id="image" name="image">
                                                        <small class="form-text text-muted">ขนาดไฟล์ไม่เกิน 2MB</small>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="link">ลิงก์:</label>
                                                        <input type="text" class="form-control" id="link" name="link" value="<?php echo htmlspecialchars($row["link"]); ?>">
                                                    </div>
                                                    <button type="submit" class="btn btn-primary" name="edit">บันทึกการเปลี่ยนแปลง</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='6'>ไม่พบหมวดหมู่สินค้า</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

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