<?php
// Database connection variables
include '../db_connect.php';

$success_message = ""; // Initialize success message variable
$error_message = ""; // Initialize error message variable

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure the vendor name and image fields are set
    if (isset($_POST['vendor_name']) && isset($_FILES['vendor_image'])) {
        // Collect form data
        $vendor_name = $_POST['vendor_name'];
        $image = $_FILES['vendor_image'];

        try {
            // Check for duplicate vendor
            $check_sql = "SELECT * FROM vendors WHERE vendor_name = :vendor_name";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':vendor_name', $vendor_name);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $error_message = "Vendor already exists."; // Error message for duplicate vendor
            } else {
                // Handle image upload
                $target_dir = "uploaded_images/";

                // Create directory if it doesn't exist
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                // Get the file name from the uploaded file
                $file_name = basename($_FILES["vendor_image"]["name"]); // Correct key

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
                $check = getimagesize($_FILES["vendor_image"]["tmp_name"]); // Correct key
                if ($check === false) {
                    $error_message = "File is not an image.";
                } elseif ($_FILES["vendor_image"]["size"] > 5000000) { // Correct key
                    $error_message = "Sorry, your file is too large.";
                } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $error_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                } else {
                    // Attempt to move the uploaded file
                    if (uploaded_images($_FILES["vendor_image"]["tmp_name"], $target_file)) { // Correct key
                        // Use the target file path for the database entry
                        $image_url = $target_file;

                        // Insert into database
                        $sql = "INSERT INTO vendors (vendor_name, vendor_image) VALUES (:vendor_name, :vendor_image)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':vendor_name', $vendor_name);
                        $stmt->bindParam(':vendor_image', $image_url);

                        if ($stmt->execute()) {
                            $success_message = "New vendor added successfully!";
                        } else {
                            $error_message = "Error inserting vendor.";
                        }
                    } else {
                        $error_message = "Sorry, there was an error uploading your file.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Handle vendor deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $sql = "DELETE FROM vendors WHERE id = :id";

        // Prepare and bind parameters
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $delete_id);

        if ($stmt->execute()) {
            $success_message = "Vendor deleted successfully!";
        } else {
            $error_message = "Error deleting vendor.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch vendors to display
try {
    $sql = "SELECT * FROM vendors ORDER BY id DESC";
    $stmt = $conn->query($sql);
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vendors - Admin Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Grid Layout */
        .page-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            height: fit-content;
        }

        .form-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-header h2 {
            font-size: 1.1rem;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        /* File Input */
        .file-upload {
            border: 2px dashed #cbd5e1;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            background: #f8fafc;
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
            font-size: 1.5rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .file-upload-text {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.95rem;
            background: var(--primary);
            color: white;
        }

        .btn:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.1rem;
            color: var(--primary);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8fafc;
        }

        .vendor-img {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }

        .btn-delete {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            grid-column: 1 / -1;
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

        @media (max-width: 1024px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="view_products.php" class="nav-item">
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
            <a href="add_vendors.php" class="nav-item active">
                <i class="fas fa-store"></i> Vendors
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="page-title">
                <h1>Manage Vendors</h1>
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

            <div class="page-grid">
                <!-- Add Vendor Form -->
                <div class="form-card">
                    <div class="form-header">
                        <h2>Add New Vendor</h2>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="vendor_name">Vendor Name</label>
                            <input type="text" id="vendor_name" name="vendor_name" required
                                placeholder="e.g. ABC Suppliers">
                        </div>

                        <div class="form-group">
                            <label>Vendor Image</label>
                            <div class="file-upload" onclick="document.getElementById('vendor_image').click()">
                                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                <div class="file-upload-text">Click to upload</div>
                                <input type="file" id="vendor_image" name="vendor_image" accept="image/*" required
                                    onchange="updateFileName(this)">
                            </div>
                            <div id="file-name" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--primary);">
                            </div>
                        </div>

                        <button type="submit" class="btn"><i class="fas fa-plus"></i> Add Vendor</button>
                    </form>
                </div>

                <!-- Vendors List -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Existing Vendors</h3>
                        <span style="color: var(--text-light); font-size: 0.9rem;">Total:
                            <?php echo count($vendors); ?></span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Vendor Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($vendors) > 0): ?>
                                <?php foreach ($vendors as $row): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <?php
                                            $imageUrl = $row['vendor_image'];
                                            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                                // Clean up the path to get just the filename
                                                $filename = str_replace(['uploaded_images/', 'admin/'], '', $imageUrl);
                                                // Construct Local URL
                                                // Assuming STORAGE_URL is defined in db_connect.php or elsewhere
                                                // For local testing, you might want to adjust this to point to your local uploaded_images folder
                                                $imageUrl = STORAGE_URL . $filename;
                                            }
                                            $placeholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDQ4IDQ4Ij48cmVjdCB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIGZpbGw9IiNmMWY1ZjkiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5NGEzYjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Ob3QgRm91bmQ8L3RleHQ+PC9zdmc+';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="vendor-img"
                                                alt="<?php echo htmlspecialchars($row['vendor_name']); ?>"
                                                onerror="this.src='<?php echo $placeholder; ?>'">
                                        </td>
                                        <td style="font-weight: 500;"><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                        <td>
                                            <a href="edit_vendor.php?id=<?php echo $row['id']; ?>" class="btn-edit"
                                                style="margin-right: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.5rem 1rem; border-radius: 0.75rem; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.85rem; font-weight: 500;">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="add_vendors.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this vendor?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-light);">
                                        <i class="fas fa-store-slash"
                                            style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                        <p>No vendors found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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