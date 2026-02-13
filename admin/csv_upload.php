<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}
include '../db_connect.php';

$success_message = "";
$error_message = "";

// Function to get or insert the category and return its ID
function getOrInsertCategory($conn, $category_name)
{
    try {
        $category_name = trim($category_name);
        $sql = "SELECT id FROM categories WHERE category_name = :category_name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':category_name', $category_name);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $category_id = $row['id'];
        } else {
            $sql = "INSERT INTO categories (category_name) VALUES (:category_name)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category_name', $category_name);
            if ($stmt->execute()) {
                $category_id = $conn->lastInsertId();
            } else {
                return false;
            }
        }
        return $category_id;
    } catch (PDOException $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");

    if ($handle !== FALSE) {
        $row = 0;
        $imported_count = 0;
        $failed_count = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip header row if exists (usually matches standard)
            if ($row == 0) {
                // Keep it simple: assume if first cell is 'product_name' or similar, skip it
                if (strtolower($data[0]) == 'product_name' || strtolower($data[0]) == 'name') {
                    $row++;
                    continue;
                }
            }

            // Expected columns: Name, Description, Price, Category, Brand, Rating, Packing, Weight, Quality, Stock, ImageURL (optional)
            // Ensure we have enough columns
            if (count($data) < 4) {
                $failed_count++;
                continue;
            }

            $product_name = $data[0] ?? '';
            $description = $data[1] ?? '';
            $price = floatval($data[2] ?? 0);
            $category_name = $data[3] ?? 'Uncategorized';
            $brand = $data[4] ?? '';
            $rating = floatval($data[5] ?? 0);
            $type_of_packing = $data[6] ?? '';
            $weight = floatval($data[7] ?? 0);
            $quality = $data[8] ?? '';
            $stock = intval($data[9] ?? 0);
            $image_url = $data[10] ?? '';

            $category_id = getOrInsertCategory($conn, $category_name);

            if ($category_id !== false) {
                $sql = "INSERT INTO products (product_name, description, price, category_id, brand, rating, type_of_packing, weight, quality, stock, image_url) 
                        VALUES (:product_name, :description, :price, :category_id, :brand, :rating, :type_of_packing, :weight, :quality, :stock, :image_url)";

                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    ':product_name' => $product_name,
                    ':description' => $description,
                    ':price' => $price,
                    ':category_id' => $category_id,
                    ':brand' => $brand,
                    ':rating' => $rating,
                    ':type_of_packing' => $type_of_packing,
                    ':weight' => $weight,
                    ':quality' => $quality,
                    ':stock' => $stock,
                    ':image_url' => $image_url
                ]);

                if ($result) {
                    $imported_count++;
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++;
            }
            $row++;
        }
        fclose($handle);

        if ($imported_count > 0) {
            $success_message = "Successfully imported $imported_count products.";
            if ($failed_count > 0) {
                $error_message = "Failed to import $failed_count rows.";
            }
        } else {
            $error_message = "No products were imported. Please check your CSV format.";
        }

    } else {
        $error_message = "Could not open the CSV file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Products - Admin Dashboard</title>
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
            --radius-md: 0.75rem;
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

        .sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: var(--radius-md);
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .upload-area:hover {
            border-color: var(--accent);
            background: #fffbeb;
        }

        .upload-area i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .upload-area input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .sample-download {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .sample-download h3 {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .sample-download p {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        code {
            background: #f1f5f9;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--primary);
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Logo" style="height: 60px;">
            <h2>Constructo</h2>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="view_products.php" class="nav-item active"><i class="fas fa-box"></i> Products</a>
            <a href="view_orders.php" class="nav-item"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="add_product.php" class="nav-item"><i class="fas fa-plus"></i> Add Product</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="card">
            <div class="page-header">
                <h1>Bulk Upload Products</h1>
                <a href="view_products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area">
                    <i class="fas fa-file-csv"></i>
                    <h3>Click to upload CSV File</h3>
                    <p style="color: #94a3b8;">Max size: 5MB</p>
                    <input type="file" name="csv_file" accept=".csv" required
                        onchange="this.nextElementSibling.textContent = this.files[0].name">
                    <div style="margin-top: 1rem; color: var(--primary); font-weight: 500;"></div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload & Import</button>
                </div>
            </form>

            <div class="sample-download">
                <h3>CSV Format Instructions</h3>
                <p>Your CSV file should have the following columns in order (NO Header Row Required, but skipped if
                    'product_name' is first):</p>
                <div
                    style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); font-family: monospace; font-size: 0.85rem; color: var(--text-main); overflow-x: auto;">
                    Name, Description, Price, Category, Brand, Rating (0-5), Packing Type, Weight, Quality, Stock, Image
                    URL (optional)
                </div>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-light);">
                    Example:
                    <code>Premium Brick, Red clay brick, 15, Bricks, Local, 4.5, Pallet, 2.5, Premium, 500, uploaded_images/brick.jpg</code>
                </p>
            </div>
        </div>
    </main>
</body>

</html>