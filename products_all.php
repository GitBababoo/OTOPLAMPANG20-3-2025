<?php
session_start(); // Start session for user ID
include "inc/db_config.php";

// Function to sanitize input (prevent XSS)
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Configuration
$products_per_page = 20;

// Get current page
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$start_from = ($page - 1) * $products_per_page;

// Fetch all categories
$sql_categories = "SELECT id, title FROM categories";
$result_categories = $conn->query($sql_categories);

// Build the WHERE clause and prepare statement (SQL Injection Prevention)
$where = "WHERE 1=1"; // Always start with WHERE
$category_id = null;

if (isset($_GET['category_id']) && $_GET['category_id'] != "") {
    $category_id = (int)$_GET['category_id']; // Sanitize as integer
    $where .= " AND category_id = ?";
}

// Prepare the SQL statement for fetching products
$sql = "SELECT * FROM featured_products $where ORDER BY id ASC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if ($category_id !== null) {
    $stmt->bind_param("iii", $category_id, $start_from, $products_per_page); // integer, integer, integer
} else {
    $stmt->bind_param("ii", $start_from, $products_per_page); // integer, integer
}

$stmt->execute();
$result = $stmt->get_result();

// Prepare the SQL statement for counting total products
$sql_count = "SELECT COUNT(*) AS total FROM featured_products $where";
$stmt_count = $conn->prepare($sql_count);

if ($category_id !== null) {
    $stmt_count->bind_param("i", $category_id);
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $products_per_page);

// Fetch all reviews efficiently (single query, grouped by product_id)
$sql_all_reviews = "SELECT reviews.*, users.username FROM reviews INNER JOIN users ON reviews.user_id = users.user_id";
$stmt_all_reviews = $conn->prepare($sql_all_reviews);
$stmt_all_reviews->execute();
$result_all_reviews = $stmt_all_reviews->get_result();
$product_reviews = [];

if ($result_all_reviews && $result_all_reviews->num_rows > 0) {
    while ($row_review = $result_all_reviews->fetch_assoc()) {
        $product_id = $row_review["product_id"];
        if (!isset($product_reviews[$product_id])) {
            $product_reviews[$product_id] = [];
        }
        $product_reviews[$product_id][] = $row_review;
    }
}

// Check if user is logged in and has a user ID in the session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $is_logged_in = true;
} else {
    $user_id = 0; // Or handle as needed
    $is_logged_in = false;
}
?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>รายการสินค้าทั้งหมด</title>
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- Custom CSS -->
        <style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #f8f9fa;
            }

            .sidebar {
                width: 250px;
                padding: 20px;
                background-color: #fff;
                border-right: 1px solid #eee;
            }

            .content {
                padding: 20px;
            }

            .product-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Adjust minmax width as needed */
                gap: 20px;
            }

            .card {
                border: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: transform 0.2s;
            }

            .card:hover {
                transform: translateY(-5px);
            }

            .card-img-top {
                height: 200px;
                object-fit: cover;
            }

            .list-group-item {
                border: none;
                padding: 0.75rem 1.25rem;
                background-color: transparent;
                transition: background-color 0.3s;
            }

            .list-group-item:hover {
                background-color: #f0f0f0;
            }

            .list-group-item.active {
                background-color: #007bff;
                color: white;
            }

            .pagination .page-link {
                border-radius: 0;
                color: #007bff;
            }

            .pagination .page-item.active .page-link {
                background-color: #007bff;
                border-color: #007bff;
                color: #fff;
            }

            footer {
                background-color: #343a40;
                color: #fff;
                text-align: center;
                padding: 20px;
                margin-top: 50px;
            }

            /* Review Styles */
            .star-rating {
                color: #ffc107;
            }

            .review-item {
                margin-bottom: 15px;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }

            /* Loading State */
            .loading {
                text-align: center;
                margin-top: 20px;
            }
            .product-image-link {
                cursor: pointer; /* เปลี่ยน cursor เป็น pointer เพื่อบอกว่าคลิกได้ */
            }

            /* Star Rating Form Styles */
            .star-rating-form {
                display: flex;
                align-items: center;
            }

            .star-rating-form i {
                font-size: 1.5rem;
                margin-right: 5px;
                cursor: pointer;
            }

            .star-rating-form i.active {
                color: #ffc107;
            }
        </style>
    </head>
    <body class="bg-light">

    <!-- Navigation Bar -->
    <?php include "inc/navbar.php"; ?>

    <div class="container-fluid mt-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar">
                <h4>หมวดหมู่สินค้า</h4>
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="products_all.php" class="text-decoration-none">
                            <i class="fas fa-list-ul me-2"></i> แสดงทั้งหมด
                        </a>
                    </li>
                    <?php
                    // Loop categories
                    if ($result_categories->num_rows > 0) {
                        while($row_category = $result_categories->fetch_assoc()) {
                            $category_id_sidebar = $row_category['id'];
                            $category_title = htmlspecialchars($row_category['title']);
                            $active = (isset($_GET['category_id']) && $_GET['category_id'] == $category_id_sidebar) ? 'active' : '';
                            echo "<li class='list-group-item $active'>";
                            echo "<a href='products_all.php?category_id=$category_id_sidebar' class='text-decoration-none'>";
                            echo "<i class='fas fa-tag me-2'></i> $category_title";
                            echo "</a>";
                            echo "</li>";
                        }
                    }
                    ?>
                </ul>
            </div>

            <!-- Content -->
            <main role="main" class="col-md-9 content">
                <h2>รายการสินค้าทั้งหมด</h2>

                <!-- Loading State -->
                <div id="loading" class="loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p>กำลังโหลด...</p>
                </div>

                <!-- Product List -->
                <div class="product-grid">
                    <?php
                    // Loop through products
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $product_name = sanitize($row["name"]);
                            $product_description = sanitize($row["description"]);
                            $product_price = $row["price"];
                            $product_image = $row["image"];
                            $product_id = $row["id"];

                            // Get reviews for the product
                            $reviews = isset($product_reviews[$product_id]) ? $product_reviews[$product_id] : [];
                            $total_ratings = 0;
                            $num_reviews = count($reviews);

                            foreach ($reviews as $review) {
                                $total_ratings += $review["rating"];
                            }

                            // Calculate average rating
                            $average_rating = ($num_reviews > 0) ? ($total_ratings / $num_reviews) : 0;

                            // Filter out products that don't belong to category (Important!)
                            if ($category_id === null || $row['category_id'] == $category_id) {

                                echo '<div class="card">';
                                echo '<img src="uploads/สินค้า/' . $product_image . '" class="card-img-top" alt="' . $product_name . '" onclick="openProductModal(' . $product_id . ')">';
                                echo '<div class="card-body">';
                                echo '<h5 class="card-title">' . $product_name . '</h5>';
                                echo '<p class="card-text">' . $product_description . '</p>';
                                echo '<p class="card-text fw-bold">$' . $product_price . '</p>';
                                echo '<div class="star-rating">';
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $average_rating) {
                                        echo '<i class="fas fa-star"></i>'; // Star
                                    } else {
                                        echo '<i class="far fa-star"></i>'; // Empty star
                                    }
                                }
                                echo ' (' . $num_reviews . ' รีวิว)</div>';
                                echo '<button type="button" class="btn btn-primary" onclick="openProductModal(' . $product_id . ')">ดูรายละเอียด</button>'; //Call js Function
                                echo '</div>';
                                echo '</div>';

                                // Product Modal with Reviews Include Files .
                                include '_product_modal.php';
                            } // End of category filter
                        } // End of while loop

                    } else {
                        echo "<div class='col-12 text-center'>ไม่พบสินค้า</div>";
                    }
                    ?>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <?php
                        if ($total_pages > 1) {
                            $params = $_GET;
                            unset($params['page']);
                            $queryString = http_build_query($params);

                            // First Button
                            if ($page > 1) {
                                echo "<li class='page-item'><a class='page-link' href='products_all.php?page=1&" . $queryString . "'>First</a></li>";
                                echo "<li class='page-item'><a class='page-link' href='products_all.php?page=" . ($page - 1) . "&" . $queryString . "'>Previous</a></li>";
                            }

                            // Page Numbers with Ellipsis
                            $max_pages_to_show = 5;
                            $start_page = max(1, $page - floor($max_pages_to_show / 2));
                            $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);

                            if ($start_page > 1) {
                                echo "<li class='page-item'><span class='page-link'>...</span></li>";
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active = ($i == $page) ? 'active' : '';
                                echo "<li class='page-item " . $active . "'><a class='page-link' href='products_all.php?page=" . $i . "&" . $queryString . "'>" . $i . "</a></li>";
                            }

                            if ($end_page < $total_pages) {
                                echo "<li class='page-item'><span class='page-link'>...</span></li>";
                            }

                            // Next and Last Buttons
                            if ($page < $total_pages) {
                                echo "<li class='page-item'><a class='page-link' href='products_all.php?page=" . ($page + 1) . "&" . $queryString . "'>Next</a></li>";
                                echo "<li class='page-item'><a class='page-link' href='products_all.php?page=" . $total_pages . "&" . $queryString . "'>Last</a></li>";
                            }

                            // Current Page Display
                            echo "<li class='page-item disabled'><span class='page-link'>Page " . $page . " of " . $total_pages . "</span></li>";
                        }
                        ?>
                    </ul>
                </nav>

            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>© 2024 ร้านค้าออนไลน์. สงวนสิทธิ์ทุกประการ.</p>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        function openProductModal(productId) {
            var modalId = '#productModal' + productId;
            $(modalId).modal('show');
        }
        $(document).ready(function() {
            $('.add-to-cart-button').click(function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var quantity = $('#quantity-' + productId).val();
                var userId = $('input[name="user_id"]').val();  // Get user_id from the form

                $.ajax({
                    type: "POST",
                    url: "add_to_cart.php",
                    data: {
                        product_id: productId,
                        quantity: quantity,
                        user_id: userId //Add
                    },
                    dataType: "json", // Expect JSON response

                    success: function(response) {
                        $('#add-to-cart-message-' + productId).html('<div class="alert alert-' + (response.status === 'success' ? 'success' : 'danger') + '">' + response.message + '</div>');
                        // Auto hide the message after 3 seconds
                        setTimeout(function() {
                            $('#add-to-cart-message-' + productId).empty();
                        }, 3000);
                    },
                    error: function(xhr, status, error) {
                        // Handle AJAX errors
                        console.error("AJAX error: " + status + " - " + error);
                        $('#add-to-cart-message-' + productId).html('<div class="alert alert-danger">เกิดข้อผิดพลาดในการเพิ่มสินค้าลงตะกร้า</div>');
                    }
                });
            });

            // Star Rating Functionality
            $('.star-rating-form i').click(function() {
                var rating = $(this).data('rating');
                var productId = $(this).closest('.star-rating-form').data('product-id');

                // Set the active stars
                $(this).siblings('i').removeClass('active'); // Remove active class from all siblings
                $(this).addClass('active'); // Add active class to the clicked star
                $(this).prevAll('i').addClass('active'); // Add active class to previous stars

                // Store the rating somewhere (e.g., in a hidden field or data attribute)
                $('#review-form-' + productId).data('rating', rating); // Store the rating
            });
        });
        function submitReview(productId) {
            var comment = $('#comment-' + productId).val();
            var rating = $('#review-form-' + productId).data('rating');  // Get the rating

            if (!rating || rating < 1 || rating > 5) {
                $('#review-message-' + productId).html('<div class="alert alert-warning">กรุณาให้คะแนนสินค้า</div>');
                return;
            }

            if (!comment.trim()) { // Check if comment is not empty after trimming whitespace
                $('#review-message-' + productId).html('<div class="alert alert-warning">กรุณาใส่ความคิดเห็น</div>');
                return;
            }

            $.ajax({
                type: "POST",
                url: "submit_review.php", // Create this file to handle review submissions
                data: {
                    product_id: productId,
                    comment: comment,
                    rating: rating //Send rating to PHP
                },
                dataType: "json",
                success: function(response) {
                    $('#review-message-' + productId).html('<div class="alert alert-' + (response.status === 'success' ? 'success' : 'danger') + '">' + response.message + '</div>');

                    // Clear the form (optional)
                    $('#comment-' + productId).val('');
                    $('.star-rating-form[data-product-id="' + productId + '"] i').removeClass('active'); // Clear the rating stars

                    // Optionally, refresh the reviews (e.g., by reloading the modal content)
                    setTimeout(function() {
                        $('#review-message-' + productId).empty();
                    }, 3000);

                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    $('#review-message-' + productId).html('<div class="alert alert-danger">เกิดข้อผิดพลาดในการส่งรีวิว</div>');
                }
            });
        }
    </script>
    </body>
    </html>
<?php
// Close connections
$stmt->close();
$stmt_count->close();
$stmt_all_reviews->close();
$conn->close();
?>