<?php
include "../inc/db_config.php";
include "../inc/navbar.php";

// Fetch product details based on ID
$product_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;  //Sanitize Input

$sql = "SELECT * FROM featured_products WHERE id = $product_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>รายละเอียดสินค้า - <?php echo $row["name"]; ?></title>
        <!-- Tailwind CSS CDN -->
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link href="css/style.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100">
    <?php include "../inc/navbar.php"; // Navigation Bar ?>
    <div class="container mx-auto py-8">
        <div class="bg-white shadow-md rounded-lg p-4">
            <img src="<?php echo $row["image"]; ?>" alt="<?php echo $row["name"]; ?>" class="w-full h-64 object-cover rounded-md">
            <h2 class="text-2xl font-bold mt-4"><?php echo $row["name"]; ?></h2>
            <p class="text-gray-700 mt-2"><?php echo $row["description"]; ?></p>
            <p class="text-blue-500 font-bold mt-4">ราคา: $<?php echo $row["price"]; ?></p>
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mt-4">เพิ่มลงในตะกร้า</button>
        </div>
    </div>
    </body>
    </html>
    <?php
} else {
    echo "ไม่พบสินค้า";
}
?>