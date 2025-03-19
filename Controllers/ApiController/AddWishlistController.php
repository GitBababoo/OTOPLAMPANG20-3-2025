<?php
session_start();
include '../../inc/db_config.php';  //รวมไฟล์ เชื่อมต่อ

// ตรวจสอบว่าผู้ใช้ login หรือยัง?
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];  //USER ID form SESSION
    $product_id = $_POST['product_id'];  //PRODUCT_ID

    // เช็คว่ามีสินค้า product id นี้ใน Wishlist หรือยัง
    $check_sql = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    //หากเจอสินค้าใน WISHLIST
    if ($check_result->num_rows > 0) {
        $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id=?";
        //Prepare
        $stmt = $conn->prepare($sql);

        if($stmt === false) {
            die("Prepare failed: " . $conn->error ); //ERROR หาก Prepare ไม่ผ่าน
        }

        //Biding value
        $stmt->bind_param("ii" , $user_id , $product_id); // user id , product id int

        if($stmt->execute()) {  //  RUN Command DELETE to databas  table  Wishlist
            $response = [  // หาก run Command ผ่าน ทำการ return result บอก php script
                "status" => "success",
                "message" => "ทำการลบสินค้านี้ออกจาก wishlist"
            ];

        }else{  // DELETE Not complete!

            $response = ["status" => "error"  , "message" => "ลบข้อมูล wishlist ล้มเหลว" ];

        }
        echo json_encode( $response );  // return json result to Script .PHP file
        exit;

    } else {
        // ยังไม่มีใน Wishlist. ให้ทำการเพิ่มข้อมูลลงไป
        $insert_sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $user_id, $product_id);
        if ($insert_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มลงใน Wishlist สำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $insert_stmt->error]);
        }
        $insert_stmt->close();
    }
    $check_stmt->close();

} else { //หากเข้าผ่าน url โดยตรง จะไม่ทำการ insert ข้อมูล
    header("Location: ../../products_all.php ");  //redirect to feadture_products page

}

$conn->close();