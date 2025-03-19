<?php
session_start(); // ใช้งาน session
include '../../inc/db_config.php';  // Path ไปยัง config.php


if(!isset( $_SESSION['user_id'])) { //ตรวจสอบการ Login หากไม่ได้ Login จะไม่สามารถใช้งานได้
    $response = array(
        "status" => "error",
        "message" => "Error, Please login" //Message login
    );
    echo json_encode($response);  //ส่ง response
    exit;
}

//  ดึง USER ID มาจาก SESSION php
$user_id =  $_SESSION['user_id'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //POST
    $product_id = $_POST['product_id']; //
    $quantity = $_POST['quantity'];



    $check_stock = "SELECT stock FROM featured_products WHERE id = ?" ;
    $check_stock_stmt =  $conn->prepare($check_stock);
    $check_stock_stmt->bind_param("i" ,$product_id);
    $check_stock_stmt->execute();

    // หาก Stock = 0  = out of stock!
    $get_stock=  $check_stock_stmt->get_result()->fetch_assoc()['stock'];
    if( $get_stock <= 0 || empty($get_stock) || $get_stock < $quantity){
        $response = [
            "status" => "error" , "message" => "สินค้าหมด!"
        ];

    }
    else{


        // เช็คก่อนว่า สินค้าถูก Add cart ไปหรือยัง
        $check_cart_sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
        $check_cart_stmt = $conn->prepare($check_cart_sql);
        //Bind Param
        $check_cart_stmt->bind_param("ii", $user_id, $product_id);

        $check_cart_stmt->execute(); //RUN Command check_cart
        $result =  $check_cart_stmt->get_result();



        if($result->num_rows > 0) { //  Add cart success  . and found cart  = update database *Cart
            $update_cart ="UPDATE cart SET quantity = quantity + ?  WHERE user_id = ? AND product_id = ?";  //SQL Command * UPDATE

            $stmt_update =$conn->prepare( $update_cart);  //เตรียมคำสั่ง
            //Biding parament
            $stmt_update ->bind_param("iii", $quantity ,$user_id, $product_id  );

            //Execute  UPDATE Command
            if ($stmt_update->execute()) {  //UPDATE Command successfully

                $response = ["status" => "success", "message" => "Update cart completed"];
                echo json_encode(  $response );

            }  else{ //ERROR Not work database
                //แสดง error
                $response = array(
                    "status" => "error",
                    "message" => "เกิดข้อผิดพลาดในการเพิ่มสินค้า: " . $stmt_update->error //Error
                );
                echo json_encode($response);
            }
            // ปิด statements.
            $stmt_update->close();
        }
        else {  //User never Add Product to cart, and not found data product  *Cart*
            $sql = "INSERT INTO cart (user_id, product_id, quantity , price ) VALUES (?, ?, ? , ( SELECT price FROM featured_products WHERE id = ? ))";

            // Prepare statement * Cart
            $stmt = $conn->prepare($sql);


            if ($stmt === false) { //Database not connected
                die("Prepare failed: " . $conn->error);
            }


            $stmt->bind_param("iiii", $user_id, $product_id, $quantity ,$product_id );

            // Run Command Excuted
            if ($stmt->execute()) {

                $response = array(
                    "status" => "success",
                    "message" => "เพิ่มสินค้าลงในตะกร้าเรียบร้อยแล้ว"
                );

            }
            else {

                //แสดง Error
                $response = array(
                    "status" => "error",
                    "message" => "เกิดข้อผิดพลาดในการเพิ่มสินค้า: " . $stmt->error
                );

            }

            echo json_encode($response); // ส่ง response

            $stmt->close();

        } //CHECK
    }
} else {
    //Direct
    header( "Location:../../products_all.php" );
}
$conn->close();

?>