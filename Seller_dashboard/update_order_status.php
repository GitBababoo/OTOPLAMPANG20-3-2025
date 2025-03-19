<?php
session_start();
include "../inc/db_config.php";



// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ตรวจสอบว่าเป็น Seller หรือไม่ (ตรวจสอบจาก Role หรืออะไรก็ตามที่คุณใช้)
// ตัวอย่าง:
$user_id = $_SESSION['user_id'];
$sql_user_roles = "SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ?";
$stmt = $conn->prepare($sql_user_roles);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_roles = $stmt->get_result();

$is_seller = false;
while($row = $result_roles->fetch_assoc()) {
    if ($row['role_name'] == 'seller') {
        $is_seller = true;
        break;
    }
}

if (!$is_seller) {
    header("Location: ../unauthorized.php"); // Redirect if not seller
    exit();
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate order_id and status
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0; // (int), to is from database are int ( you have to by int); you force to ;int
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : '';  // Sanitized and trim data, from "table data;"; has on or can type here, you take
    $shipping_status = isset($_POST['shipping_status']) ? sanitize($_POST['shipping_status']) : '';
    // Basic validation
    if ($order_id <= 0 || empty($status)  || empty($shipping_status)) {  // if have ""any not correct",  will get all error; correct before action
        echo " Invalid Data; please type in again or other action what are told by. action:)";
        exit(); // Or is Redirect as your wanted!
    }
    // SQL and Set the "What are process or Update for order : and to set value is."
    $sql_update = "UPDATE orders SET status = ?, shipping_status = ? WHERE id = ?";
    $stmt_update  =$conn->prepare($sql_update);     // What update for set are can is prepare on there before
    $stmt_update -> bind_param("ssi",$status,  $shipping_status, $order_id );

    if ( $stmt_update ->execute()){      // test on before and that data' .   for what are true for (database save are the OK :) ;) ); - and will Redirect you want from  process!.
        //If database "is OK for saving" so:

        header("Location: manage_orders.php");  // you make the  Redirect; to back where the from "edit tables". "is to be is by complete or  successes action!"; from on,   data! to .view you  back is on can to  correct data;. so (correct  !correct!. all have is :D:)Congratutation;You make. It can . :) you smart ; .


    }
    else{          // if not success all then. and get

        echo "There an Error has on to save data;. to test them for admin or database  to tell all where from, get; - is an data that!. no success saved, .   :;";    // by give error - .for action. what has for problem!. that user can  for help.
    }

    $stmt_update  ->close();
    $conn       ->close();      // after this:; is " the database close!". from .Edit is can be for action complete;) from there,.


    exit();      // from process and finish on (redirect now); is .finish to action:) congratutation  for from data .to work for data!

}   // From action  all action .

// If not success process you give. what? not know so;. :) You told user "what next; or you show!

// From here (if data correct you direct on to table "data to edit",). is for tell user

else{       // by show

    echo " No data;";        // " is have an"  .and from tell why what? has ;:) by (no that,. to  ; or is to to get;)" from for from an process to know."".   :). congratulation"; and do next :D  ok";:

    exit();

}

?>