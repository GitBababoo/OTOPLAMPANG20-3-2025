<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Function to check if user is an admin (re-used from previous code)
function isAdmin($conn, $user_id) {
    $sql = "SELECT r.role_name FROM user_roles ur INNER JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row["role_name"] == 'admin') {
                return true;
            }
        }
    }
    return false;
}

if (!isAdmin($conn, $_SESSION['user_id'])) {
    echo "You do not have permission to access this page.";
    exit();
}

// Handle form submission (re-used and improved from previous code)
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve'])) {
        $seller_id = $_POST['seller_id'];
        $sql_get_user_id = "SELECT user_id FROM sellers WHERE seller_id = ?";
        $stmt_get_user_id = $conn->prepare($sql_get_user_id);
        $stmt_get_user_id->bind_param("i", $seller_id);
        $stmt_get_user_id->execute();
        $result_get_user_id = $stmt_get_user_id->get_result();

        if ($result_get_user_id->num_rows == 1) {
            $user_id = $result_get_user_id->fetch_assoc()['user_id'];
            $conn->begin_transaction();
            try {
                $sql_update = "UPDATE sellers SET status = 'active' WHERE seller_id = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("i", $seller_id);
                $stmt->execute();
                $sql_insert_role = "INSERT INTO user_roles (user_id, role_id) VALUES (?, 3)";
                $stmt_insert_role = $conn->prepare($sql_insert_role);
                $stmt_insert_role->bind_param("i", $user_id);
                $stmt_insert_role->execute();
                $conn->commit();
                $message = "<div class='alert alert-success'>Seller approved successfully.</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "<div class='alert alert-danger'>Error approving seller: " . $e->getMessage() . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Error: Could not retrieve user ID for seller.</div>";
        }
    } elseif (isset($_POST['reject'])) {
        $seller_id = $_POST['seller_id'];
        $sql_delete = "DELETE FROM sellers WHERE seller_id = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $seller_id);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Seller rejected successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error rejecting seller: " . $stmt->error . "</div>";
        }
    }  elseif (isset($_POST['change_status'])) {
        $seller_id = $_POST['seller_id'];
        $new_status = $_POST['new_status'];
        if (!in_array($new_status, ['active', 'inactive'])) {
            $message = "<div class='alert alert-danger'>Invalid status.</div>";
        } else {
            $sql_update_status = "UPDATE sellers SET status = ? WHERE seller_id = ?";
            $stmt_update_status = $conn->prepare($sql_update_status);
            $stmt_update_status->bind_param("si", $new_status, $seller_id);

            if ($stmt_update_status->execute()) {
                $message = "<div class='alert alert-success'>Seller status updated successfully.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error updating seller status: " . $stmt_update_status->error . "</div>";
            }
        }
    }
}

// Get sellers with inactive status (FOR APPROVAL TABLE)
$sql_inactive = "SELECT * FROM sellers WHERE status = 'inactive'";
$result_inactive = $conn->query($sql_inactive);


// Get ALL sellers WITH product count (FOR ALL SELLERS TABLE)
$sql_all = "SELECT s.*, COUNT(fp.id) AS product_count
            FROM sellers s
            LEFT JOIN featured_products fp ON s.seller_id = fp.seller_id
            GROUP BY s.seller_id";  // Group by seller to count correctly
$result_all = $conn->query($sql_all);

?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Approve Sellers</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            /* ... (rest of your styles, they are good) ... */
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

            .table-responsive {
                overflow-x: auto; /* Add horizontal scroll for small screens */
            }
            .btn-sm {  /* smaller buttons */
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            .btn-group-sm > .btn, .btn-sm {  /* apply to .btn-group-sm too for consistent sizing*/
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
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
                <h2>Approve Sellers</h2>

                <!-- Display Message -->
                <?php echo $message; ?>

                <!-- Seller List (Awaiting Approval) -->
                <h3>Sellers Awaiting Approval</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Seller ID</th>
                            <th>Store Name</th>
                            <th>Seller Email</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result_inactive->num_rows > 0) {
                            while($row = $result_inactive->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row["seller_id"]. "</td>";
                                echo "<td>" . htmlspecialchars($row["store_name"]). "</td>";
                                echo "<td>" . htmlspecialchars($row["seller_email"]). "</td>";
                                echo "<td>
                                <form method='post' style='display:inline;'>
                                    <input type='hidden' name='seller_id' value='" . $row["seller_id"] . "'>
                                    <button type='submit' class='btn btn-sm btn-success' name='approve'>Approve</button>
                                    <button type='submit' class='btn btn-sm btn-danger' name='reject'>Reject</button>
                                </form>
                              </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No sellers awaiting approval.</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

                <!-- All Sellers List -->
                <h3>All Sellers</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Seller ID</th>
                            <th>Store Name</th>
                            <th>Seller Email</th>
                            <th>Status</th>
                            <th>Product Count</th> <!-- Added Product Count column -->
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result_all->num_rows > 0) {
                            while($row = $result_all->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row["seller_id"]. "</td>";
                                echo "<td>" . htmlspecialchars($row["store_name"]). "</td>";
                                echo "<td>" . htmlspecialchars($row["seller_email"]). "</td>";
                                echo "<td>" . htmlspecialchars($row["status"]). "</td>";
                                echo "<td>" . $row["product_count"] . "</td>";  // Display Product Count
                                echo "<td>";
                                echo "<form method='post'>";
                                echo "<input type='hidden' name='seller_id' value='" . $row["seller_id"] . "'>";
                                echo "<select name='new_status' class='form-control form-control-sm'>";
                                echo "<option value='active' " . ($row["status"] == 'active' ? 'selected' : '') . ">Active</option>";
                                echo "<option value='inactive' " . ($row["status"] == 'inactive' ? 'selected' : '') . ">Inactive</option>";
                                echo "</select>";
                                echo "<button type='submit' name='change_status' class='btn btn-primary btn-sm mt-1'>Change Status</button>";
                                echo "</form>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No sellers found.</td></tr>";  // colspan='6' now
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
<?php
$conn->close();
?>