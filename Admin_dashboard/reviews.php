<?php
session_start();
require_once 'db_config.php'; // Make sure this path is correct

// Check if user is logged in AND is an admin.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to your login page
    exit();
}

//  Admin check function (re-use from previous code).
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

// Check if the user is an admin.  If not, redirect them or show an error.
if (!isAdmin($conn, $_SESSION['user_id'])) {
    echo "You do not have permission to access this page."; // Or redirect to a suitable page
    exit();
}



// Function to get star rating HTML (reusable and concise)
function getStarRating($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rating) ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
    }
    return $stars;
}


// Get reviews, product, and user information.  JOIN multiple tables for efficiency.
$sql = "SELECT r.rating, r.comment, r.created_at,
               fp.name AS product_name, fp.image AS product_image,
               u.first_name, u.last_name,
               s.store_name  -- Get the store name
        FROM reviews r
        INNER JOIN featured_products fp ON r.product_id = fp.id
        INNER JOIN users u ON r.user_id = u.user_id
        INNER JOIN sellers s ON r.seller_id = s.seller_id  -- Join with sellers table
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);

$reviews = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"  />
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
        .review-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px; /* Slightly rounder corners */
            padding: 10px; /* Reduced padding */
            margin-bottom: 10px; /* Reduced margin */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }
        .product-image {
            width: 50px; /* Smaller image */
            height: 50px;
            object-fit: cover;
            border-radius: 4px; /* Match card corner radius */
            margin-right: 10px; /* Spacing */
        }
        .product-info {
            display: flex;
            align-items: center;
            margin-bottom: 5px; /* Reduced margin */
        }
        .product-name {
            font-size: 1rem; /* Reduced font size */
            font-weight: bold;
            margin: 0; /* Remove default margin */
        }
        .store-name {  /* Style for the store name */
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 2px;

        }

        .rating {
            font-size: 0.9rem; /* Reduced font size */
            margin-bottom: 2px;

        }
        .user-info {
            font-size: 0.8rem;
            margin-bottom: 2px;
            color: #212529;
        }
        .comment {
            font-size: 0.9rem; /* Reduced font size */
            margin-bottom: 4px;

        }
        .timestamp {
            font-size: 0.7rem; /* Smaller font */
            color: #6c757d; /* Muted color */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: static; /* Remove sticky on small screens*/
                height: auto; /*Let the height adjust*/
            }
            .product-info {
                flex-direction: column; /* Stack on small screens */
                align-items: flex-start; /* Align to start */
            }
            .product-image{
                margin-bottom: 5px;
                margin-right:0px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <?php include 'sidebar.php'; ?>  <!-- Include the sidebar -->
        </nav>

        <!-- Content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 content">
            <h2 class="my-4">Product Reviews</h2>  <!--Use Bootstrap classes for margin-->

            <?php if (empty($reviews)): ?>
                <p>No reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="product-info">
                            <img src="../uploads/สินค้า/<?php echo htmlspecialchars($review['product_image']); ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>" class="product-image">
                            <div>
                                <h5 class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></h5>
                                <p class="store-name">From store: <?php echo htmlspecialchars($review['store_name']); ?></p>
                            </div>
                        </div>


                        <div class="rating">
                            <?php echo getStarRating($review['rating']); ?>
                        </div>
                        <p class="user-info">Reviewed by: <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></p>
                        <p class="comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <p class="timestamp"><?php echo htmlspecialchars($review['created_at']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>