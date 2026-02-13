<?php
// Database connection variables
include '../db_connect.php';

$success_message = ""; // Initialize success message variable
$error_message = "";

// Function to get or insert the category and return its ID
function getOrInsertCategory($conn, $category_name)
{
    try {
        // Check if the category already exists
        $sql = "SELECT id FROM categories WHERE category_name = :category_name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':category_name', $category_name);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Category exists, fetch the ID
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $category_id = $row['id'];
        } else {
            // Category doesn't exist, insert a new one
            $sql = "INSERT INTO categories (category_name) VALUES (:category_name)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category_name', $category_name);
            if ($stmt->execute()) {
                $category_id = $conn->lastInsertId();
            } else {
                die("Error inserting category.");
            }
        }
        return $category_id;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Collect form data
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category']; // This will be used to get or insert category
    $brand = $_POST['brand'];
    $rating = $_POST['rating'];
    $type_of_packing = $_POST['type_of_packing'];
    $weight = $_POST['weight'];
    $quality = $_POST['quality'];
    $quality = $_POST['quality'];
    $stock = $_POST['stock'];
    $stock = $_POST['stock'];
    $gst_rate = isset($_POST['gst_rate']) ? $_POST['gst_rate'] : 18.00; // Default GST
    $shipping_cost = isset($_POST['shipping_cost']) ? $_POST['shipping_cost'] : 20.00; // Default Shipping


    // Handle image upload
    $target_dir = "uploaded_images/";

    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Get the file name from the uploaded file
    $file_name = basename($_FILES["image"]["name"]);

    // Create the target file path
    $target_file = $target_dir . $file_name;

    // Get the file extension
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Function to move the uploaded file
    function uploaded_images($tmp_name, $target_file)
    {
        return move_uploaded_file($tmp_name, $target_file);
    }

    // Check if image file is a valid image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $error_message = "File is not an image.";
    } elseif ($_FILES["image"]["size"] > 5000000) {
        $error_message = "Sorry, your file is too large.";
    } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $error_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    } else {
        // Attempt to move the uploaded file
        if (uploaded_images($_FILES["image"]["tmp_name"], $target_file)) {
            try {
                // Get or insert the category and fetch the category_id
                $category_id = getOrInsertCategory($conn, $category);

                // Insert product data with category_id
                $sql = "INSERT INTO products (product_name, description, price, category_id, image_url, brand, rating, type_of_packing, weight, quality, stock, gst_rate, shipping_cost) 
                        VALUES (:product_name, :description, :price, :category_id, :image_url, :brand, :rating, :type_of_packing, :weight, :quality, :stock, :gst_rate, :shipping_cost)";

                // Prepare and bind parameters
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':product_name', $product_name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':image_url', $target_file);
                $stmt->bindParam(':brand', $brand);
                $stmt->bindParam(':rating', $rating);
                $stmt->bindParam(':type_of_packing', $type_of_packing);
                $stmt->bindParam(':weight', $weight);
                $stmt->bindParam(':quality', $quality);
                $stmt->bindParam(':quality', $quality);
                $stmt->bindParam(':stock', $stock);
                $stmt->bindParam(':stock', $stock);
                $stmt->bindParam(':gst_rate', $gst_rate);
                $stmt->bindParam(':shipping_cost', $shipping_cost);

                // Execute the query
                if ($stmt->execute()) {
                    $success_message = "New product added successfully!";
                } else {
                    $error_message = "Error inserting product.";
                }
            } catch (PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #f59e0b;
            --accent-hover: #d97706;
            --text-main: #334155;
            --text-light: #64748b;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 60px;
        }

        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
        }

        .nav-links {
            flex: 1;
            padding: 1.5rem 1rem;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .top-header {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            position: relative;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            width: 180px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            border: 1px solid #e2e8f0;
            display: none;
            overflow: hidden;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text-main);
            text-decoration: none;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 2rem;
            border: 1px solid #e2e8f0;
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h2 {
            font-size: 1.25rem;
            color: var(--primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        /* File Input */
        .file-upload {
            border: 2px dashed #cbd5e1;
            border-radius: var(--radius-md);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .file-upload:hover {
            border-color: var(--accent);
            background: #fffbeb;
        }

        .file-upload input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 2rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .file-upload-text {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Logo">
            <h2>Constructo</h2>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="view_products.php" class="nav-item active">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="view_orders.php" class="nav-item">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="view_users.php" class="nav-item">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="messages.php" class="nav-item">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="add_category.php" class="nav-item">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="add_vendors.php" class="nav-item">
                <i class="fas fa-store"></i> Vendors
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="page-title">
                <h1>Add New Product</h1>
            </div>
            <div class="user-profile" onclick="document.getElementById('profileDropdown').classList.toggle('show')">
                <div class="avatar">A</div>
                <span>Admin</span>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: var(--text-light);"></i>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="../logout.php" class="dropdown-item" style="color: #ef4444;"><i
                            class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <div class="form-header">
                    <h2>Product Details</h2>
                    <a href="view_products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to
                        List</a>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="product_name">Product Name</label>
                            <input type="text" id="product_name" name="product_name" required
                                placeholder="e.g. Premium Cement">
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" required
                                placeholder="Detailed product description..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price">Price (₹)</label>
                            <input type="number" id="price" name="price" step="0.01" required placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" id="stock" name="stock" min="1" required placeholder="Available stock">
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category" required
                                placeholder="e.g. Building Materials">
                        </div>

                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" id="brand" name="brand" placeholder="e.g. UltraTech">
                        </div>

                        <div class="form-group">
                            <label for="rating">Rating (0-5)</label>
                            <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" placeholder="4.5">
                        </div>

                        <div class="form-group">
                            <label for="type_of_packing">Type of Packing</label>
                            <input type="text" id="type_of_packing" name="type_of_packing" placeholder="e.g. Bag, Box">
                        </div>

                        <div class="form-group">
                            <label for="weight">Weight</label>
                            <input type="text" id="weight" name="weight" placeholder="e.g. 50kg">
                        </div>

                        <div class="form-group">
                            <label for="quality">Quality Grade</label>
                            <input type="text" id="quality" name="quality" placeholder="e.g. Premium">
                        </div>

                        <div class="form-group">
                            <label for="gst_rate">GST Rate (%)</label>
                            <input type="number" id="gst_rate" name="gst_rate" step="0.01" min="0" value="18.00"
                                placeholder="18.00">
                        </div>

                        <div class="form-group">
                            <label for="shipping_cost">Shipping Cost (₹)</label>
                            <input type="number" id="shipping_cost" name="shipping_cost" step="0.01" min="0"
                                value="20.00" placeholder="20.00">
                        </div>

                        <div class="form-group full-width">
                            <label>Product Image</label>
                            <div class="file-upload" onclick="document.getElementById('image').click()">
                                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                <div class="file-upload-text">Click to upload image</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem;">Max size: 5MB (JPG,
                                    PNG)</div>
                                <input type="file" id="image" name="image" accept="image/*" required
                                    onchange="updateFileName(this)">
                            </div>
                            <div id="file-name" style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--primary);">
                            </div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="view_products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : '';
            document.getElementById('file-name').textContent = fileName ? 'Selected: ' + fileName : '';
        }

        // Close dropdown when clicking outside
        window.onclick = function (event) {
            if (!event.target.closest('.user-profile')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>

</html>