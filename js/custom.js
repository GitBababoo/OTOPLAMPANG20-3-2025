$(document).ready(function() {
    // Wishlist Button Click
    $('.wishlist-button').click(function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');

        $.ajax({
            type: "POST",
            url: "Controllers/ApiController/AddWishlistController.php",
            data: { product_id: productId },
            dataType: "json",
            success: function(response) {
                alert(response.message); // แสดงข้อความ (เปลี่ยนเป็น notification สวยๆ ได้)
            },
            error: function() {
                alert("เกิดข้อผิดพลาดในการเพิ่มลง Wishlist");
            }
        });
    });

    // Add to Cart Button Click
    $('.add-to-cart-button').click(function(e) {
        e.preventDefault(); // prevent Default
        var productId = $(this).data('product-id'); //Product Id From data-product-id
        var quantity = 1; //default = 1;

        //ดึงค่า user จาก seesion PHP

        $.ajax({
            type: "POST",
            url: "Controllers/ApiController/AddToCartController.php",  //Controller
            data: { product_id: productId,  //ส่ง Product ID
                quantity: quantity, // ส่งจำนวน
                //user_id: user_id    *User ID
            }, //ส่งค่าไปที่ AddToCartController.php

            dataType: "json",
            success: function(response) {
                // Success
                alert(response.message);  //เเสดง Response PHP

            },
            error: function(xhr, status, error) {
                //Handle Error

                alert("Error!!!."+ status);  //หาก ERROR เเจ้งเตือน
            }
        });
    });

});