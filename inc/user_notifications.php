<?php
// user_notifications.php
session_start();
require_once 'db_config.php'; // Make sure this path is correct

// Check if user is logged in AND is an admin.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Or your admin login page
    exit();
}

$user_id = $_SESSION['user_id'];


// Fetch notifications
// Correct sql
$sql = "SELECT n.id, n.message, n.created_at, n.is_read, n.type, n.related_id
        FROM notifications n
        WHERE  seller_id = ? OR seller_id IS NULL
        ORDER BY n.created_at DESC";


$stmt = $conn->prepare($sql);  // Use prepared statements
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();


$notifications = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}


// Mark as read (Handle via AJAX for better UX. This is the basic PHP part)
// Use another file like we used. But in here , I write basic function like previous to more understand and easy way to get it!

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

    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Content -->
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

                    <?php echo htmlspecialchars($notification['message']); ?>


                </p>
                <p class="notification-time"><?php echo htmlspecialchars($notification['created_at']); ?></p>

                <!-- Mark as Read button (form for basic functionality) -->
                <?php if (!$notification['is_read']): ?>

                    <button class='btn btn-sm btn-primary mark-as-read' data-id='<?php echo $notification['id'];?>'>Mark as Read</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
                url: 'user_notifications.php',
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