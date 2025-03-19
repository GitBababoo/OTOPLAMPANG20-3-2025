<?php
// notification_icon.php

// We still need to start/resume the session and connect to the DB
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "db_config.php";  // Make sure this path is correct!

// Check if user is logged in.
if (!isset($_SESSION['user_id'])) {
    echo ''; // If not, print empty and stop more action
    exit();
}
$user_id = $_SESSION['user_id'];


// --- Fetch Notification Count ---
//Correct sql
$sql_count = "SELECT COUNT(*) AS unread_count FROM notifications WHERE (seller_id = ? OR seller_id IS NULL) AND is_read = 0";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $user_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$unread_count = $result_count->fetch_assoc()['unread_count'];


// --- Fetch Notifications (limited for display in dropdown) ---
// Correct sql
$sql_notifications = "SELECT id, message, created_at, type FROM notifications
                      WHERE (seller_id = ? OR seller_id IS NULL) AND is_read=0
                      ORDER BY created_at DESC
                      LIMIT 5";  // Limit to, say, 5 notifications
$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("i", $user_id);
$stmt_notifications->execute();
$result_notifications = $stmt_notifications->get_result();



?>
<!-- Notification Icon and Dropdown (HTML) -->
<li class="nav-item dropdown">
    <a class="nav-link" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <?php if ($unread_count > 0): ?>
            <span class="badge badge-danger" id="notification-count"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown" id="notification-dropdown">
        <?php if ($result_notifications->num_rows > 0): ?>
            <?php while ($row = $result_notifications->fetch_assoc()): ?>
                <a class="dropdown-item" href="#" data-notification-id="<?php echo $row['id']; ?>">
                    <div class='notification-content'>
                        <span class='notification-type'><?php echo htmlspecialchars(ucfirst($row['type'])); ?></span>:  <!-- Display type -->
                        <span class='notification-message'><?php echo htmlspecialchars($row['message']); ?></span>
                    </div>
                    <div class='notification-time'><?php echo $row['created_at']; ?></div>  <!-- Display timestamp-->

                </a>
            <?php endwhile; ?>

            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-center" href="user_notifications.php">See All Notifications</a>

        <?php else: ?>
            <span class="dropdown-item">No new notifications</span>
        <?php endif; ?>
    </div>
</li>

<script>
    $(document).ready(function() {
        // Click on the notification (in the dropdown). This is where AJAX is best.
        $('#notification-dropdown').on('click', '.dropdown-item', function(e) {
            e.preventDefault(); // prevent <a> from navigating

            let notificationId = $(this).data('notification-id');

            // Make an AJAX call to mark the notification as read.
            $.ajax({
                url: 'mark_notification_read.php', // This file handles marking as read
                type: 'POST',
                data: { notification_id: notificationId },
                success: function(response) {
                    if (response === 'success') {
                        // Update the UI:
                        $(e.currentTarget).remove();  //Remove notification that clicked

                        let currentCount = parseInt($('#notification-count').text()) || 0; // Get current count or zero
                        let newCount = currentCount -1;

                        if(!isNaN(currentCount) && currentCount > 0) { // check is a number first
                            $("#notification-count").text(newCount);
                        }

                        // If the count reaches 0, hide badge.
                        if (newCount <= 0){
                            $('#notification-count').hide();

                            //Check If not has notification display
                            if($('#notification-dropdown .dropdown-item').length <= 1){  //<=1 mean include div see all.
                                $('#notification-dropdown').html("<span class='dropdown-item'>No new notifications</span>")
                            }
                        }
                    } else {
                        alert('Error marking notification as read');
                    }

                    // console.log(response);  // For debugging.

                },
                error: function() {
                    alert('An error occurred');
                }
            });
        });


    });
</script>