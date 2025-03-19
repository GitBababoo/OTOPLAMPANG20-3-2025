<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in AND is an admin.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Or your admin login page
    exit();
}

// Reusable admin check function.
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

// Enforce admin access
if (!isAdmin($conn, $_SESSION['user_id'])) {
    echo "You do not have permission to access this page.";  // Or a redirect.
    exit();
}



// Fetch notifications
$sql = "SELECT n.id, n.message, n.created_at, n.is_read, n.type, n.related_id,
               u.first_name, u.last_name,  -- For user-related notifications
               s.store_name,                -- For seller-related notifications
               o.id AS order_number,
               d.title as discount_title  -- Include discount name
        FROM notifications n
        LEFT JOIN users u ON n.type = 'message' AND n.related_id = u.user_id    -- User related (if message)
        LEFT JOIN sellers s ON n.type = 'seller_approval' AND n.related_id = s.seller_id  -- seller related (if seller approve)
        LEFT JOIN orders o ON n.type = 'order' AND n.related_id = o.id -- orders related (if type order)
        LEFT JOIN discounts d ON n.type = 'discount' AND n.related_id = d.id -- for discounts
        ORDER BY n.created_at DESC";

$result = $conn->query($sql);

$notifications = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

// Mark as read (Handle via AJAX for better UX. This is the basic PHP part)
if (isset($_POST['notification_id']) && isset($_POST['mark_as_read'])) {
    $notification_id = (int)$_POST['notification_id']; // Cast to integer for security

    $sql_update = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql_update);  // ALWAYS use prepared statements
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        // Success.  You might return something here if using AJAX
        echo "success";
        exit();  // Very important to exit after echoing
    } else {
        echo "error"; // Indicate failure
        exit(); // exit here to
    }

}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
            position: sticky; /* Make the sidebar sticky */
            top: 0; /* Stick to the top */
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

        .sidebar a.active { /* Style for active link */
            background-color: #007bff;
            color: white;
        }
        .content {
            padding: 20px;
        }
        .notification-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }
        .notification-item.unread {
            background-color: #f0f8ff; /* Light blue for unread */
        }
        .notification-message {
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        .notification-time {
            font-size: 0.7rem;
            color: #6c757d;
        }
        .notification-type{
            font-weight: bold;

        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: static; /* Remove sticky on small screens*/
                height: auto; /*Let the height adjust*/
            }

        }

    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
            <?php include 'sidebar.php'; ?>  <!-- Include the sidebar -->
        </nav>

        <!-- Content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 content">
            <h2 class="my-4">Notifications</h2>

            <?php if (empty($notifications)): ?>
                <p>No notifications.</p>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">

                        <p class="notification-message">
                                     <span class="notification-type">
                                         <?php echo htmlspecialchars(ucfirst($notification['type'])); ?>:
                                     </span>  <!--e.g., Order, Message, etc. -->

                            <!-- Display different related info, based on type -->
                            <?php if ($notification['type'] === 'order'): ?>
                                Order #<?php echo htmlspecialchars($notification['order_number']); ?> - <?php echo htmlspecialchars($notification['message']); ?>

                            <?php elseif ($notification['type'] === 'message'): ?>
                                Message from <?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?> -  <?php echo htmlspecialchars($notification['message']); ?>

                            <?php elseif($notification['type'] == 'seller_approval'): ?>
                                <?php echo htmlspecialchars($notification['message']); ?>

                            <?php elseif ($notification['type'] === 'discount'): ?>
                                <?php echo htmlspecialchars($notification['message']); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars($notification['message']); ?>
                            <?php endif; ?>


                        </p>
                        <p class="notification-time"><?php echo htmlspecialchars($notification['created_at']); ?></p>

                        <!-- Mark as Read button (form for basic functionality) -->
                        <?php if (!$notification['is_read']): ?>

                            <button class='btn btn-sm btn-primary mark-as-read' data-id='<?php echo $notification['id'];?>'>Mark as Read</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Add jQuery (required for AJAX)-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

    $(document).ready(function() {

        $(".mark-as-read").click(function (e){
            e.preventDefault();  // Prevent default form submission

            let button = $(this); // Get the button that was clicked
            let notificationId = button.data('id');  //  Get the notification ID


            $.ajax({
                url: 'notifications.php',
                method: 'POST',  // Correctly set the method
                data: {
                    notification_id: notificationId,
                    mark_as_read: 1 //  to identify the action
                },
                success: function(response) {

                    if (response === 'success') {
                        button.closest('.notification-item').removeClass('unread'); //remove class unread
                        button.remove();   //remove button mark as read
                        // Optionally, update a notification count somewhere
                    } else {
                        alert('Error marking as read'); // Give feedback

                    }

                },
                error: function() {
                    alert('An error occurred');  // Network/server error
                }
            });
        });

    });


</script>

</body>
</html>