<?php
session_start();
require_once 'db_config.php';

// ตรวจสอบการเข้าสู่ระบบ (Admin)
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่ได้ Login ให้ Redirect ไปหน้า Login
    header("Location: login_admin.php");
    exit();
}

// ไดเรกทอรีสำหรับเก็บรูปภาพแบนเนอร์
$target_dir = "../uploads/แบนเนอร์/";

// สร้างไดเรกทอรี หากยังไม่มี
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// ฟังก์ชันสร้างชื่อไฟล์ที่ไม่ซ้ำ (ป้องกันชื่อไฟล์ซ้ำกัน)
function generateUniqueFilename($target_dir, $filename, $extension) {
    $new_filename = $filename . "." . $extension; // สร้างชื่อไฟล์ใหม่
    $i = 1; // ตัวแปรนับจำนวน
    while (file_exists($target_dir . $new_filename)) {
        // ถ้ามีไฟล์ชื่อนี้อยู่แล้ว ให้เพิ่มตัวเลขต่อท้าย
        $new_filename = $filename . "_" . $i . "." . $extension;
        $i++;
    }
    return $new_filename; // คืนค่าชื่อไฟล์ใหม่ที่ไม่ซ้ำ
}

// ฟังก์ชันตรวจสอบรูปแบบวันที่
function isValidDateTime($dateTime) {
    return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateTime);
}

// จัดการการส่งฟอร์ม
$message = ''; // ข้อความแจ้งเตือน
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ตรวจสอบว่ามีการกดปุ่ม "เพิ่ม"
    if (isset($_POST['add'])) {
        // เพิ่มแบนเนอร์ใหม่
        $title = htmlspecialchars($_POST['title']); // ชื่อแบนเนอร์
        $link = htmlspecialchars($_POST['link']); // ลิงก์ที่แบนเนอร์เชื่อมโยงไป
        $alt_text = htmlspecialchars($_POST['alt_text']); // ข้อความอธิบายรูปภาพ (SEO)
        $is_active = isset($_POST['is_active']) ? 1 : 0; // สถานะเปิด/ปิดใช้งาน

        $start_date = $_POST['start_date']; // วันที่เริ่มต้นแสดง
        $end_date = $_POST['end_date']; // วันที่สิ้นสุดการแสดง

        $position = $_POST['position']; // ตำแหน่งที่แสดง (Header, Sidebar, Footer)
        $priority = $_POST['priority']; // ลำดับความสำคัญ (0 คือสูงสุด)

        // อัปโหลดรูปภาพ
        if (!empty($_FILES["image"]["name"])) {
            // มีการเลือกไฟล์รูปภาพ
            $original_filename = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME); // ชื่อไฟล์เดิม
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION)); // นามสกุลไฟล์
            $filename = preg_replace('/[^a-zA-Z0-9ก-๙_-]/u', '_', $original_filename); // Clean filename
            $new_filename = generateUniqueFilename($target_dir, $filename, $imageFileType); // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำ
            $target_file = $target_dir . $new_filename; // Path สำหรับเก็บไฟล์
            $uploadOk = 1; // สถานะการอัปโหลด (1 = สำเร็จ, 0 = ไม่สำเร็จ)

            // ตรวจสอบว่าเป็นไฟล์ภาพจริงหรือไม่
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $message = "<div class='alert alert-danger'>ไฟล์ไม่ใช่รูปภาพ</div>";
                $uploadOk = 0;
            }

            // ตรวจสอบขนาดไฟล์ (ไม่เกิน 500KB)
            if ($_FILES["image"]["size"] > 500000) {
                $message = "<div class='alert alert-danger'>ขออภัย ไฟล์ของคุณมีขนาดใหญ่เกินไป</div>";
                $uploadOk = 0;
            }

            // อนุญาตเฉพาะบางนามสกุลไฟล์
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (!in_array($imageFileType, $allowed_types)) {
                $message = "<div class='alert alert-danger'>ขออภัย อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG และ GIF เท่านั้น</div>";
                $uploadOk = 0;
            }

            // ถ้าทุกอย่างเรียบร้อย ให้ทำการอัปโหลด
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
            // ไม่มีการเลือกไฟล์รูปภาพ
            $message = "<div class='alert alert-danger'>โปรดเลือกรูปภาพ</div>";
            $uploadOk = 0;
        }

        // ถ้าการอัปโหลดรูปภาพสำเร็จ หรือไม่มีข้อผิดพลาด
        if ($uploadOk == 1) {
            // ตรวจสอบข้อมูล
            if (empty($title) || empty($link)) {
                $message = "<div class='alert alert-danger'>โปรดกรอกข้อมูลให้ครบทุกช่อง</div>";
            } else {
                // ใช้ Prepared Statement (ป้องกัน SQL Injection)
                $sql_add = "INSERT INTO banners (title, image, link, alt_text, is_active, start_date, end_date, position, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_add);
                $stmt->bind_param("ssssisssi", $title, $image, $link, $alt_text, $is_active, $start_date, $end_date, $position, $priority);

                // ทำการเพิ่มข้อมูล
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>เพิ่มแบนเนอร์สำเร็จ</div>";
                } else {
                    $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการเพิ่มแบนเนอร์: " . $stmt->error . "</div>";
                }
            }
        }
    } elseif (isset($_POST['edit'])) {
        // แก้ไขแบนเนอร์
        $id = $_POST['id']; // ID ของแบนเนอร์ที่จะแก้ไข
        $title = htmlspecialchars($_POST['title']); // ชื่อแบนเนอร์
        $link = htmlspecialchars($_POST['link']); // ลิงก์ที่แบนเนอร์เชื่อมโยงไป
        $alt_text = htmlspecialchars($_POST['alt_text']); // ข้อความอธิบายรูปภาพ (SEO)
        $is_active = isset($_POST['is_active']) ? 1 : 0; // สถานะเปิด/ปิดใช้งาน

        $start_date = $_POST['start_date']; // วันที่เริ่มต้นแสดง
        $end_date = $_POST['end_date']; // วันที่สิ้นสุดการแสดง

        $position = $_POST['position']; // ตำแหน่งที่แสดง (Header, Sidebar, Footer)
        $priority = $_POST['priority']; // ลำดับความสำคัญ (0 คือสูงสุด)

        // ดึงข้อมูลรูปภาพเก่า
        $sql_select_image = "SELECT image FROM banners WHERE id = ?";
        $stmt_select_image = $conn->prepare($sql_select_image);
        $stmt_select_image->bind_param("i", $id);
        $stmt_select_image->execute();
        $result_select_image = $stmt_select_image->get_result();
        $old_image = ""; // ชื่อไฟล์รูปภาพเก่า
        if ($result_select_image->num_rows > 0) {
            $row_image = $result_select_image->fetch_assoc();
            $old_image = $row_image['image'];
        }

        // อัปโหลดรูปภาพ (แก้ไข)
        if (!empty($_FILES["image"]["name"])) {
            // มีการเลือกไฟล์รูปภาพใหม่
            $original_filename = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME); // ชื่อไฟล์เดิม
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION)); // นามสกุลไฟล์
            $filename = preg_replace('/[^a-zA-Z0-9ก-๙_-]/u', '_', $original_filename); // Clean filename
            $new_filename = generateUniqueFilename($target_dir, $filename, $imageFileType); // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำ
            $target_file = $target_dir . $new_filename; // Path สำหรับเก็บไฟล์
            $uploadOk = 1; // สถานะการอัปโหลด (1 = สำเร็จ, 0 = ไม่สำเร็จ)

            // ตรวจสอบว่าเป็นไฟล์ภาพจริงหรือไม่
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $message = "<div class='alert alert-danger'>ไฟล์ไม่ใช่รูปภาพ</div>";
                $uploadOk = 0;
            }

            // ตรวจสอบขนาดไฟล์ (ไม่เกิน 500KB)
            if ($_FILES["image"]["size"] > 500000) {
                $message = "<div class='alert alert-danger'>ขออภัย ไฟล์ของคุณมีขนาดใหญ่เกินไป</div>";
                $uploadOk = 0;
            }

            // อนุญาตเฉพาะบางนามสกุลไฟล์
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (!in_array($imageFileType, $allowed_types)) {
                $message = "<div class='alert alert-danger'>ขออภัย อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG และ GIF เท่านั้น</div>";
                $uploadOk = 0;
            }

            // ถ้าทุกอย่างเรียบร้อย ให้ทำการอัปโหลด
            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // อัปโหลดไฟล์สำเร็จ
                    $image = $new_filename; // เก็บชื่อไฟล์ในฐานข้อมูล

                    // ลบรูปภาพเก่า (ถ้ามี)
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

        // ถ้าการอัปโหลดรูปภาพสำเร็จ หรือไม่มีข้อผิดพลาด
        if ($uploadOk == 1) {
            // อัปเดตข้อมูล
            $sql_edit = "UPDATE banners SET title=?, image=?, link=?, alt_text=?, is_active=?, start_date=?, end_date=?, position=?, priority=? WHERE id=?";
            $stmt = $conn->prepare($sql_edit);
            // ตรวจสอบวันที่ให้ถูกต้อง
            if (!isValidDateTime($start_date)) {
                $start_date = null; // หรือค่าเริ่มต้นอื่น ๆ ที่เหมาะสม
            }
            if (!isValidDateTime($end_date)) {
                $end_date = null; // หรือค่าเริ่มต้นอื่น ๆ ที่เหมาะสม
            }
            $stmt->bind_param("ssssisssii", $title, $image, $link, $alt_text, $is_active, $start_date, $end_date, $position, $priority, $id);

            // ทำการแก้ไขข้อมูล
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>แก้ไขแบนเนอร์สำเร็จ</div>";
            } else {
                $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการแก้ไขแบนเนอร์: " . $stmt->error . "</div>";
            }
        }
    } elseif (isset($_POST['delete'])) {
        // ลบแบนเนอร์
        $id = $_POST['id']; // ID ของแบนเนอร์ที่จะลบ

        // ดึงข้อมูลรูปภาพเก่า
        $sql_select_image = "SELECT image FROM banners WHERE id = ?";
        $stmt_select_image = $conn->prepare($sql_select_image);
        $stmt_select_image->bind_param("i", $id);
        $stmt_select_image->execute();
        $result_select_image = $stmt_select_image->get_result();
        $old_image = ""; // ชื่อไฟล์รูปภาพเก่า
        if ($result_select_image->num_rows > 0) {
            $row_image = $result_select_image->fetch_assoc();
            $old_image = $row_image['image'];
        }

        // ใช้ Prepared Statement (ป้องกัน SQL Injection)
        $sql_delete = "DELETE FROM banners WHERE id=?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id);

        // ทำการลบข้อมูล
        if ($stmt->execute()) {
            // ลบรูปภาพ (ถ้ามี)
            if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                unlink($target_dir . $old_image);
            }
            $message = "<div class='alert alert-success'>ลบแบนเนอร์สำเร็จ</div>";
        } else {
            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการลบแบนเนอร์: " . $stmt->error . "</div>";
        }
    }
}

// ดึงข้อมูลแบนเนอร์ทั้งหมดจากฐานข้อมูล
$sql = "SELECT * FROM banners";
$result = $conn->query($sql);

?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>การจัดการแบนเนอร์</title>
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
                <h2>การจัดการแบนเนอร์</h2>

                <!-- แสดงข้อความแจ้งเตือน -->
                <?php echo $message; ?>

                <!-- ฟอร์มเพิ่มแบนเนอร์ -->
                <h3>เพิ่มแบนเนอร์</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">ชื่อแบนเนอร์:</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="image">รูปภาพ:</label>
                        <input type="file" class="form-control-file" id="image" name="image" required>
                    </div>
                    <div class="form-group">
                        <label for="link">ลิงก์:</label>
                        <input type="text" class="form-control" id="link" name="link" required>
                    </div>
                    <div class="form-group">
                        <label for="alt_text">ข้อความอธิบายรูปภาพ (Alt Text):</label>
                        <input type="text" class="form-control" id="alt_text" name="alt_text">
                    </div>
                    <div class="form-group">
                        <label for="is_active">สถานะ:</label>
                        <select class="form-control" id="is_active" name="is_active">
                            <option value="1">เปิดใช้งาน</option>
                            <option value="0">ปิดใช้งาน</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">วันที่เริ่มต้น:</label>
                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" >
                    </div>
                    <div class="form-group">
                        <label for="end_date">วันที่สิ้นสุด:</label>
                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" >
                    </div>
                    <div class="form-group">
                        <label for="position">ตำแหน่ง:</label>
                        <select class="form-control" id="position" name="position">
                            <option value="header">Header (ส่วนหัว)</option>
                            <option value="sidebar">Sidebar (ด้านข้าง)</option>
                            <option value="footer">Footer (ส่วนท้าย)</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priority">ลำดับความสำคัญ:</label>
                        <input type="number" class="form-control" id="priority" name="priority" value="0">
                    </div>
                    <button type="submit" class="btn btn-primary" name="add">เพิ่ม</button>
                </form>

                <!-- รายการแบนเนอร์ -->
                <h3>รายการแบนเนอร์</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อแบนเนอร์</th>
                            <th>รูปภาพ</th>
                            <th>ลิงก์</th>
                            <th>Alt Text</th>
                            <th>สถานะ</th>
                            <th>วันที่เริ่มต้น</th>
                            <th>วันที่สิ้นสุด</th>
                            <th>ตำแหน่ง</th>
                            <th>ลำดับความสำคัญ</th>
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
                                echo "<td><img src='" . $image_path . "' width='200'></td>"; // แสดงรูปภาพ
                                echo "<td>" . htmlspecialchars($row["link"]). "</td>";
                                echo "<td>" . htmlspecialchars($row["alt_text"]). "</td>";
                                echo "<td>" . ($row["is_active"] == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน') . "</td>";
                                // ตรวจสอบรูปแบบวันที่และแสดงผลให้ถูกต้อง
                                $start_date = htmlspecialchars($row["start_date"]);
                                $end_date = htmlspecialchars($row["end_date"]);
                                if (!isValidDateTime($start_date)) {
                                    $start_date = "N/A";
                                }
                                if (!isValidDateTime($end_date)) {
                                    $end_date = "N/A";
                                }
                                echo "<td>" . $start_date. "</td>";
                                echo "<td>" .  $end_date. "</td>";
                                echo "<td>" . htmlspecialchars($row["position"]). "</td>";
                                echo "<td>" . htmlspecialchars($row["priority"]). "</td>";
                                echo "<td>
                                    <button type='button' class='btn btn-sm btn-primary' data-toggle='modal' data-target='#editBannerModal" . $row["id"] . "'>แก้ไข</button>
                                    <form method='post' style='display:inline;' onsubmit='return confirm(\"คุณแน่ใจหรือไม่ว่าต้องการลบแบนเนอร์นี้?\");'>
                                        <input type='hidden' name='id' value='" . $row["id"] . "'>
                                        <button type='submit' class='btn btn-sm btn-danger' name='delete'>ลบ</button>
                                    </form>
                                  </td>";
                                echo "</tr>";
                                ?>
                                <!-- Edit Banner Modal -->
                                <div class="modal fade" id="editBannerModal<?php echo $row["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="editBannerModalLabel<?php echo $row["id"]; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editBannerModalLabel<?php echo $row["id"]; ?>">แก้ไขแบนเนอร์</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post" enctype="multipart/form-data">
                                                    <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
                                                    <div class="form-group">
                                                        <label for="title">ชื่อแบนเนอร์:</label>
                                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($row["title"]); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="image">รูปภาพ:</label>
                                                        <input type="file" class="form-control-file" id="image" name="image">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="link">ลิงก์:</label>
                                                        <input type="text" class="form-control" id="link" name="link" value="<?php echo htmlspecialchars($row["link"]); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="alt_text">ข้อความอธิบายรูปภาพ (Alt Text):</label>
                                                        <input type="text" class="form-control" id="alt_text" name="alt_text" value="<?php echo htmlspecialchars($row["alt_text"]); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="is_active">สถานะ:</label>
                                                        <select class="form-control" id="is_active" name="is_active">
                                                            <option value="1" <?php if ($row["is_active"] == 1) echo "selected"; ?>>เปิดใช้งาน</option>
                                                            <option value="0" <?php if ($row["is_active"] == 0) echo "selected"; ?>>ปิดใช้งาน</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="start_date">วันที่เริ่มต้น:</label>
                                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($row["start_date"]); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="end_date">วันที่สิ้นสุด:</label>
                                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($row["end_date"]); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="position">ตำแหน่ง:</label>
                                                        <select class="form-control" id="position" name="position">
                                                            <option value="header" <?php if ($row["position"] == 'header') echo 'selected'; ?>>Header (ส่วนหัว)</option>
                                                            <option value="sidebar" <?php if ($row["position"] == 'sidebar') echo 'selected'; ?>>Sidebar (ด้านข้าง)</option>
                                                            <option value="footer" <?php if ($row["position"] == 'footer') echo 'selected'; ?>>Footer (ส่วนท้าย)</option>
                                                            <option value="อื่นๆ" <?php if ($row["position"] == 'อื่นๆ') echo 'selected'; ?>>อื่นๆ</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="priority">ลำดับความสำคัญ:</label>
                                                        <input type="number" class="form-control" id="priority" name="priority" value="<?php echo htmlspecialchars($row["priority"]); ?>">
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
                            echo "<tr><td colspan='11'>ไม่พบแบนเนอร์</td></tr>";
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