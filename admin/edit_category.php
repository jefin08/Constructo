<?php
// session_start();
// if (!isset($_SESSION['email'])) {
//     header("Location: ../login.php");
//     exit();
// }
include '../db_connect.php';

$success_message = "";
$error_message = "";
$category_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$category_id) {
    header("Location: add_category.php");
    exit();
}

// Fetch existing category
try {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->bindParam(':id', $category_id);
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        $error_message = "Category not found.";
    }
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $category) {
    $category_name = $_POST['category_name'];
    $image_url = $category['image_url'];

    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
        $target_dir = "uploaded_images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = basename($_FILES["category_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["category_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["category_image"]["tmp_name"], $target_file)) {
                $image_url = $file_name;
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_message = "File is not an image.";
        }
    }

    if (empty($error_message)) {
        try {
            $sql = "UPDATE categories SET category_name = :category_name, image_url = :image_url WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category_name', $category_name);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':id', $category_id);

            if ($stmt->execute()) {
                $success_message = "Category updated successfully!";
                $category['category_name'] = $category_name;
                $category['image_url'] = $image_url;
            } else {
                $error_message = "Error updating category.";
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
    <title>Edit Category - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
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
            margin-bottom: 1.25rem;
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
            margin-bottom: 1rem;
            border: 1px solid #eee;
            border-radius: 8px;
            display: block;
        }
    </style>
</head>

<body>

    <div class="form-card">
        <h2>Edit Category</h2>

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

        <?php if ($category): ?>
            <form method="POST" enctype="multipart/form-data">
                <label>Category Name</label>
                <input type="text" name="category_name" value="<?php echo htmlspecialchars($category['category_name']); ?>"
                    required>

                <label>Current Image</label>
                <?php
                $img = $category['image_url'];
                if (!filter_var($img, FILTER_VALIDATE_URL)) {
                    $img = STORAGE_URL . str_replace(['uploaded_images/', 'admin/'], '', $img);
                }
                ?>
                <img src="<?php echo htmlspecialchars($img); ?>" class="current-img">

                <label>Change Image (Optional)</label>
                <input type="file" name="category_image" accept="image/*" style="margin-bottom: 1.25rem;">

                <button type="submit" class="btn">Update Category</button>
            </form>
        <?php endif; ?>

        <a href="add_category.php" class="back-link">Back to Categories</a>
    </div>

</body>

</html>