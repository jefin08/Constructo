<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

include '../db_connect.php';

// Fetch Admin Name
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT name FROM admin WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$firstName = $stmt->fetchColumn();
$initial = !empty($firstName) ? strtoupper($firstName[0]) : 'A';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: view_products.php");
    exit();
}

$success_message = "";
$error_message = "";

// Function to get or insert the category and return its ID
function getOrInsertCategory($conn, $category_name)
{
    try {
        $sql = "SELECT id FROM categories WHERE category_name = :category_name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':category_name', $category_name);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        } else {
            $sql = "INSERT INTO categories (category_name) VALUES (:category_name)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category_name', $category_name);
            if ($stmt->execute()) {
                return $conn->lastInsertId();
            }
        }
    } catch (PDOException $e) {
        return false;
    }
    return false;
}

// Fetch existing product data
try {
    $sql = "SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Product not found.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_name = $_POST['category'];
    $brand = $_POST['brand'];
    $rating = $_POST['rating'];
    $type_of_packing = $_POST['type_of_packing'];
    $weight = $_POST['weight'];
    $quality = $_POST['quality'];
    $gst_rate = $_POST['gst_rate'];
    $shipping_cost = $_POST['shipping_cost'];

    // Handle Image
    $image_url = $product['image_url']; // Default to existing

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = __DIR__ . "/uploaded_images/";
        $db_dir = "uploaded_images/";

        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $file_name = basename($_FILES["image"]["name"]);
        // Sanitize filename to prevent issues
        $file_name = preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);

        $new_file_name = time() . "_" . $file_name; // Add timestamp
        $target_file = $upload_dir . $new_file_name; // Absolute path for moving file
        $db_file_path = $db_dir . $new_file_name; // Relative path for DB

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif']) && $_FILES["image"]["size"] <= 5000000) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $db_file_path;
            } else {
                $error_message = "Error uploading image.";
            }
        } else {
            $error_message = "Invalid file type or size.";
        }
    }

    if (empty($error_message)) {
        try {
            $category_id = getOrInsertCategory($conn, $category_name);

            $sql = "UPDATE products SET 
                    product_name = :product_name,
                    description = :description,
                    price = :price,
                    stock = :stock,
                    category_id = :category_id,
                    brand = :brand,
                    rating = :rating,
                    type_of_packing = :type_of_packing,
                    weight = :weight,
                    quality = :quality,
                    image_url = :image_url,
                    gst_rate = :gst_rate,
                    shipping_cost = :shipping_cost
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':product_name' => $product_name,
                ':description' => $description,
                ':price' => $price,
                ':stock' => $stock,
                ':category_id' => $category_id,
                ':brand' => $brand,
                ':rating' => $rating,
                ':type_of_packing' => $type_of_packing,
                ':weight' => $weight,
                ':quality' => $quality,
                ':image_url' => $image_url,
                ':gst_rate' => $gst_rate,
                ':shipping_cost' => $shipping_cost,
                ':id' => $id
            ]);

            $success_message = "Product updated successfully!";
            // Refresh product data
            $product['product_name'] = $product_name;
            $product['description'] = $description;
            $product['price'] = $price;
            $product['stock'] = $stock;
            $product['category_name'] = $category_name;
            $product['brand'] = $brand;
            $product['rating'] = $rating;
            $product['type_of_packing'] = $type_of_packing;
            $product['weight'] = $weight;
            $product['quality'] = $quality;
            $product['gst_rate'] = $gst_rate;
            $product['shipping_cost'] = $shipping_cost;
            $product['image_url'] = $image_url;

        } catch (PDOException $e) {
            $error_message = "Error updating product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Dashboard</title>
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

        .current-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
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
                <h1>Edit Product</h1>
            </div>
            <div class="user-profile" onclick="document.getElementById('profileDropdown').classList.toggle('show')">
                <div class="avatar"><?php echo $initial; ?></div>
                <span><?php echo htmlspecialchars($firstName); ?></span>
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
                            <input type="text" id="product_name" name="product_name"
                                value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"
                                required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price">Price (₹)</label>
                            <input type="number" id="price" name="price" step="0.01"
                                value="<?php echo htmlspecialchars($product['price']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" id="stock" name="stock" min="1"
                                value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category"
                                value="<?php echo htmlspecialchars($product['category_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" id="brand" name="brand"
                                value="<?php echo htmlspecialchars($product['brand']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="rating">Rating (0-5)</label>
                            <input type="number" id="rating" name="rating" step="0.1" min="0" max="5"
                                value="<?php echo htmlspecialchars($product['rating']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="type_of_packing">Type of Packing</label>
                            <input type="text" id="type_of_packing" name="type_of_packing"
                                value="<?php echo htmlspecialchars($product['type_of_packing']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="weight">Weight</label>
                            <input type="text" id="weight" name="weight"
                                value="<?php echo htmlspecialchars($product['weight']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="quality">Quality Grade</label>
                            <input type="text" id="quality" name="quality"
                                value="<?php echo htmlspecialchars($product['quality']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="gst_rate">GST Rate (%)</label>
                            <input type="number" id="gst_rate" name="gst_rate" step="0.01" min="0"
                                value="<?php echo htmlspecialchars($product['gst_rate'] ?? 18.00); ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_cost">Shipping Cost (₹)</label>
                            <input type="number" id="shipping_cost" name="shipping_cost" step="0.01" min="0"
                                value="<?php echo htmlspecialchars($product['shipping_cost'] ?? 20.00); ?>">
                        </div>

                        <div class="form-group full-width">
                            <label>Product Image</label>
                            <?php
                            $imgUrl = $product['image_url'];
                            if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                $filename = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                                // Construct Local URL
                                $imgUrl = STORAGE_URL . $filename;
                            }
                            ?>
                            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                <div>
                                    <div style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.5rem;">
                                        Current Image:</div>
                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Current Product Image"
                                        class="current-image"
                                        onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNmMWY1ZjkiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5NGEzYjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Ob3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
                                </div>
                                <div style="flex: 1;">
                                    <div class="file-upload" onclick="document.getElementById('image').click()">
                                        <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                        <div class="file-upload-text">Click to upload new image (optional)</div>
                                        <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem;">Max size:
                                            5MB (JPG, PNG)</div>
                                        <input type="file" id="image" name="image" accept="image/*"
                                            onchange="updateFileName(this)">
                                    </div>
                                    <div id="file-name"
                                        style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--primary);"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="view_products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
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