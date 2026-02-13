<?php
// session_start();
// if (!isset($_SESSION['email'])) {
//     header("Location: ../login.php");
//     exit();
// }
include '../db_connect.php';

$success_message = "";
$error_message = "";
$vendor_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$vendor_id) {
    header("Location: add_vendors.php");
    exit();
}

// Fetch existing vendor data
try {
    $stmt = $conn->prepare("SELECT * FROM vendors WHERE id = :id");
    $stmt->bindParam(':id', $vendor_id);
    $stmt->execute();
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        $error_message = "Vendor not found.";
    }
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $vendor) {
    $vendor_name = $_POST['vendor_name'];
    $image_url = $vendor['vendor_image']; // Default to existing image

    // Handle Image Upload if provided
    if (isset($_FILES['vendor_image']) && $_FILES['vendor_image']['error'] == 0) {
        $target_dir = "uploaded_images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = basename($_FILES["vendor_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["vendor_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["vendor_image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_message = "File is not an image.";
        }
    }

    if (empty($error_message)) {
        try {
            $sql = "UPDATE vendors SET vendor_name = :vendor_name, vendor_image = :vendor_image WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':vendor_name', $vendor_name);
            $stmt->bindParam(':vendor_image', $image_url);
            $stmt->bindParam(':id', $vendor_id);

            if ($stmt->execute()) {
                $success_message = "Vendor updated successfully!";
                // Refresh vendor data
                $vendor['vendor_name'] = $vendor_name;
                $vendor['vendor_image'] = $image_url;
            } else {
                $error_message = "Error updating vendor.";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vendor - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #f59e0b;
            --bg-body: #f8fafc;
            --text-main: #334155;
            --radius-md: 0.75rem;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            justify-content: center;
            align-items: center;
        }

        .form-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        h2 {
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--primary-light);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--text-main);
            text-decoration: none;
        }

        .current-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-top: 0.5rem;
            border: 1px solid #eee;
            border-radius: 8px;
        }
    </style>
</head>

<body>

    <div class="form-card">
        <h2>Edit Vendor</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($vendor): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Vendor Name</label>
                    <input type="text" name="vendor_name" value="<?php echo htmlspecialchars($vendor['vendor_name']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Current Image</label>
                    <?php
                    $img = $vendor['vendor_image'];
                    if (!filter_var($img, FILTER_VALIDATE_URL)) {
                        $img = STORAGE_URL . str_replace(['uploaded_images/', 'admin/'], '', $img);
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" class="current-img">
                </div>

                <div class="form-group">
                    <label>Change Image (Optional)</label>
                    <input type="file" name="vendor_image" accept="image/*">
                </div>

                <button type="submit" class="btn">Update Vendor</button>
            </form>
        <?php endif; ?>

        <a href="add_vendors.php" class="back-link">Back to Vendors</a>
    </div>

</body>

</html>