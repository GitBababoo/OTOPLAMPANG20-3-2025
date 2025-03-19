<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Database credentials (same as dashboard.php)
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'test1';

// Connect to the database
$conn = mysqli_connect($db_host, $db_username, $db_password, $db_name);
mysqli_set_charset($conn, "utf8");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user has 'admin' role (Important for Security!)
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user_roles WHERE user_id='$user_id' AND role_id='1'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "คุณไม่มีสิทธิ์เข้าถึงส่วนนี้";
    exit();
}

// ฟังก์ชันเพิ่มส่วนลดใหม่
if (isset($_POST['add_discount'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $discount_amount = (float)$_POST['discount_amount'];
    $min_spend = (float)$_POST['min_spend'];
    $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $discount_percent = (int)$_POST['discount_percent'];
    $max_uses = (int)$_POST['max_uses'];
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    // SQL query to insert discount data
    $sql = "INSERT INTO discounts (title, description, discount_amount, min_spend, expiry_date, code, discount_percent, max_uses, start_date, end_date) 
            VALUES ('$title', '$description', $discount_amount, $min_spend, '$expiry_date', '$code', $discount_percent, $max_uses, '$start_date', '$end_date')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('เพิ่มส่วนลดสำเร็จ');</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// ฟังก์ชันลบส่วนลด
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);

    // SQL query to delete discount
    $sql = "DELETE FROM discounts WHERE id = '$delete_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('ลบส่วนลดสำเร็จ');</script>";
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}

// ฟังก์ชันแก้ไขส่วนลด
if (isset($_POST['update_discount'])) {
    $discount_id = mysqli_real_escape_string($conn, $_POST['discount_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $discount_amount = (float)$_POST['discount_amount'];
    $min_spend = (float)$_POST['min_spend'];
    $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $discount_percent = (int)$_POST['discount_percent'];
    $max_uses = (int)$_POST['max_uses'];
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    $sql = "UPDATE discounts SET 
            title = '$title',
            description = '$description',
            discount_amount = $discount_amount,
            min_spend = $min_spend,
            expiry_date = '$expiry_date',
            code = '$code',
            discount_percent = $discount_percent,
            max_uses = $max_uses,
            start_date = '$start_date',
            end_date = '$end_date'
            WHERE id = '$discount_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('แก้ไขส่วนลดสำเร็จ');</script>";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}

?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>จัดการส่วนลด</title>
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

            .summary-card {
                margin-bottom: 20px;
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
                <h2 class="mb-4">จัดการส่วนลด</h2>

                <!-- Add Discount Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>เพิ่มส่วนลดใหม่</h5>
                    </div>
                    <div class="card-body">
                        <form action="discounts.php" method="POST">
                            <div class="form-group">
                                <label for="title">ชื่อส่วนลด:</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="form-group">
                                <label for="description">รายละเอียด:</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="discount_amount">จำนวนส่วนลด:</label>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="min_spend">ยอดสั่งซื้อขั้นต่ำ:</label>
                                    <input type="number" class="form-control" id="min_spend" name="min_spend" step="0.01" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="expiry_date">วันที่หมดอายุ:</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="code">รหัสส่วนลด:</label>
                                    <input type="text" class="form-control" id="code" name="code" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="discount_percent">เปอร์เซ็นต์ส่วนลด:</label>
                                    <input type="number" class="form-control" id="discount_percent" name="discount_percent" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="max_uses">จำนวนครั้งที่ใช้ได้:</label>
                                    <input type="number" class="form-control" id="max_uses" name="max_uses" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="start_date">วันที่เริ่มต้น:</label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end_date">วันที่สิ้นสุด:</label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" name="add_discount">เพิ่มส่วนลด</button>
                        </form>
                    </div>
                </div>

                <!-- Discount List -->
                <div class="card">
                    <div class="card-header">
                        <h5>รายการส่วนลด</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th>ชื่อส่วนลด</th>
                                    <th>รายละเอียด</th>
                                    <th>จำนวนส่วนลด</th>
                                    <th>ยอดสั่งซื้อขั้นต่ำ</th>
                                    <th>วันที่หมดอายุ</th>
                                    <th>รหัสส่วนลด</th>
                                    <th>เปอร์เซ็นต์ส่วนลด</th>
                                    <th>จำนวนครั้งที่ใช้ได้</th>
                                    <th>วันที่เริ่มต้น</th>
                                    <th>วันที่สิ้นสุด</th>
                                    <th>แก้ไข</th>
                                    <th>ลบ</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                // ดึงข้อมูลจากฐานข้อมูล
                                $sql = "SELECT * FROM discounts";
                                $result = mysqli_query($conn, $sql);

                                if (mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['discount_amount']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['min_spend']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['expiry_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['code']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['discount_percent']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['max_uses']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['start_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
                                        echo "<td><a href='#' class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editDiscountModal' data-id='" . $row['id'] . "' data-title='" . htmlspecialchars($row['title']) . "' data-description='" . htmlspecialchars($row['description']) . "' data-discount_amount='" . htmlspecialchars($row['discount_amount']) . "' data-min_spend='" . htmlspecialchars($row['min_spend']) . "' data-expiry_date='" . htmlspecialchars($row['expiry_date']) . "' data-code='" . htmlspecialchars($row['code']) . "' data-discount_percent='" . htmlspecialchars($row['discount_percent']) . "' data-max_uses='" . htmlspecialchars($row['max_uses']) . "' data-start_date='" . htmlspecialchars($row['start_date']) . "' data-end_date='" . htmlspecialchars($row['end_date']) . "'><i class='fas fa-edit'></i> แก้ไข</a></td>";
                                        echo "<td><a href='discounts.php?delete_id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"คุณต้องการลบส่วนลดนี้?\");'><i class='fas fa-trash-alt'></i> ลบ</a></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='12'>ไม่มีข้อมูลส่วนลด</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Edit Discount Modal -->
                <div class="modal fade" id="editDiscountModal" tabindex="-1" role="dialog" aria-labelledby="editDiscountModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editDiscountModalLabel">แก้ไขส่วนลด</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="discounts.php" method="POST">
                                    <input type="hidden" name="discount_id" id="discount_id">
                                    <div class="form-group">
                                        <label for="title">ชื่อส่วนลด:</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">รายละเอียด:</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="discount_amount">จำนวนส่วนลด:</label>
                                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="min_spend">ยอดสั่งซื้อขั้นต่ำ:</label>
                                            <input type="number" class="form-control" id="min_spend" name="min_spend" step="0.01" required>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="expiry_date">วันที่หมดอายุ:</label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="code">รหัสส่วนลด:</label>
                                            <input type="text" class="form-control" id="code" name="code" required>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="discount_percent">เปอร์เซ็นต์ส่วนลด:</label>
                                            <input type="number" class="form-control" id="discount_percent" name="discount_percent" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="max_uses">จำนวนครั้งที่ใช้ได้:</label>
                                            <input type="number" class="form-control" id="max_uses" name="max_uses" required>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="start_date">วันที่เริ่มต้น:</label>
                                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="end_date">วันที่สิ้นสุด:</label>
                                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" class="btn btn-primary" name="update_discount">บันทึกการแก้ไข</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $('#editDiscountModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget) // Button that triggered the modal
            var id = button.data('id') // Extract info from data-* attributes
            var title = button.data('title')
            var description = button.data('description')
            var discount_amount = button.data('discount_amount')
            var min_spend = button.data('min_spend')
            var expiry_date = button.data('expiry_date')
            var code = button.data('code')
            var discount_percent = button.data('discount_percent')
            var max_uses = button.data('max_uses')
            var start_date = button.data('start_date')
            var end_date = button.data('end_date')

            // Update the modal's content.
            var modal = $(this)
            modal.find('.modal-body #discount_id').val(id)
            modal.find('.modal-body #title').val(title)
            modal.find('.modal-body #description').val(description)
            modal.find('.modal-body #discount_amount').val(discount_amount)
            modal.find('.modal-body #min_spend').val(min_spend)
            modal.find('.modal-body #expiry_date').val(expiry_date)
            modal.find('.modal-body #code').val(code)
            modal.find('.modal-body #discount_percent').val(discount_percent)
            modal.find('.modal-body #max_uses').val(max_uses)
            modal.find('.modal-body #start_date').val(start_date)
            modal.find('.modal-body #end_date').val(end_date)
        })
    </script>
    </body>
    </html>

<?php mysqli_close($conn); ?>