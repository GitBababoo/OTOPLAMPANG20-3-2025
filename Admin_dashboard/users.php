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

// ฟังก์ชันเพิ่มผู้ใช้งานใหม่
if (isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // SQL query to insert user data
    $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone_number, address) 
            VALUES ('$username', '$email', '$password', '$first_name', '$last_name', '$phone_number', '$address')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('เพิ่มผู้ใช้งานสำเร็จ');</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// ฟังก์ชันลบผู้ใช้งาน
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);

    // SQL query to delete user
    $sql = "DELETE FROM users WHERE user_id = '$delete_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('ลบผู้ใช้งานสำเร็จ');</script>";
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}

// ฟังก์ชันแก้ไขผู้ใช้งาน
if (isset($_POST['update_user'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $is_active = isset($_POST['is_active']) ? 1 : 0; // Checkbox value

    // SQL query to update user data
    $sql = "UPDATE users SET 
            username = '$username',
            email = '$email',
            first_name = '$first_name',
            last_name = '$last_name',
            phone_number = '$phone_number',
            address = '$address',
            is_active = '$is_active'
            WHERE user_id = '$user_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('แก้ไขผู้ใช้งานสำเร็จ');</script>";
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
        <title>จัดการผู้ใช้งาน</title>
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
                <h2 class="mb-4">จัดการผู้ใช้งาน</h2>

                <!-- Add User Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>เพิ่มผู้ใช้งานใหม่</h5>
                    </div>
                    <div class="card-body">
                        <form action="users.php" method="POST">
                            <div class="form-group">
                                <label for="username">ชื่อผู้ใช้:</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>

                            <div class="form-group">
                                <label for="email">อีเมล:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="password">รหัสผ่าน:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="first_name">ชื่อ:</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="last_name">นามสกุล:</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="phone_number">เบอร์โทรศัพท์:</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number">
                            </div>

                            <div class="form-group">
                                <label for="address">ที่อยู่:</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" name="add_user">เพิ่มผู้ใช้งาน</button>
                        </form>
                    </div>
                </div>

                <!-- User List -->
                <div class="card">
                    <div class="card-header">
                        <h5>รายการผู้ใช้งาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>อีเมล</th>
                                    <th>ชื่อ</th>
                                    <th>นามสกุล</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th>ที่อยู่</th>
                                    <th>สถานะ</th>
                                    <th>แก้ไข</th>
                                    <th>ลบ</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                // ดึงข้อมูลจากฐานข้อมูล
                                $sql = "SELECT * FROM users";
                                $result = mysqli_query($conn, $sql);

                                if (mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                                        echo "<td>" . ($row['is_active'] == 1 ? 'Active' : 'Inactive') . "</td>";
                                        echo "<td><a href='#' class='btn btn-sm btn-warning' data-toggle='modal' data-target='#editUserModal' data-user_id='" . $row['user_id'] . "' data-username='" . htmlspecialchars($row['username']) . "' data-email='" . htmlspecialchars($row['email']) . "' data-first_name='" . htmlspecialchars($row['first_name']) . "' data-last_name='" . htmlspecialchars($row['last_name']) . "' data-phone_number='" . htmlspecialchars($row['phone_number']) . "' data-address='" . htmlspecialchars($row['address']) . "' data-is_active='" . $row['is_active'] . "'><i class='fas fa-edit'></i> แก้ไข</a></td>";
                                        echo "<td><a href='users.php?delete_id=" . $row['user_id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"คุณต้องการลบผู้ใช้งานนี้?\");'><i class='fas fa-trash-alt'></i> ลบ</a></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9'>ไม่มีข้อมูลผู้ใช้งาน</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editUserModalLabel">แก้ไขผู้ใช้งาน</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="users.php" method="POST">
                                    <input type="hidden" name="user_id" id="user_id">
                                    <div class="form-group">
                                        <label for="username">ชื่อผู้ใช้:</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">อีเมล:</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="first_name">ชื่อ:</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="last_name">นามสกุล:</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone_number">เบอร์โทรศัพท์:</label>
                                        <input type="text" class="form-control" id="phone_number" name="phone_number">
                                    </div>

                                    <div class="form-group">
                                        <label for="address">ที่อยู่:</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active">
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" class="btn btn-primary" name="update_user">บันทึกการแก้ไข</button>
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
        $('#editUserModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget) // Button that triggered the modal
            var user_id = button.data('user_id') // Extract info from data-* attributes
            var username = button.data('username')
            var email = button.data('email')
            var first_name = button.data('first_name')
            var last_name = button.data('last_name')
            var phone_number = button.data('phone_number')
            var address = button.data('address')
            var is_active = button.data('is_active')

            // Update the modal's content.
            var modal = $(this)
            modal.find('.modal-body #user_id').val(user_id)
            modal.find('.modal-body #username').val(username)
            modal.find('.modal-body #email').val(email)
            modal.find('.modal-body #first_name').val(first_name)
            modal.find('.modal-body #last_name').val(last_name)
            modal.find('.modal-body #phone_number').val(phone_number)
            modal.find('.modal-body #address').val(address)
            modal.find('.modal-body #is_active').prop('checked', is_active == 1) // Set checkbox
        })
    </script>
    </body>
    </html>

<?php mysqli_close($conn); ?>