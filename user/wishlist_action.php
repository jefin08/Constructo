<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

try {
    if ($action === 'toggle') {
        // Check if exists
        $check = $conn->prepare("SELECT 1 FROM wishlist WHERE user_id = :uid AND product_id = :pid");
        $check->execute([':uid' => $user_id, ':pid' => $product_id]);
        
        if ($check->rowCount() > 0) {
            // Remove
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = :uid AND product_id = :pid");
            $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
            echo json_encode(['success' => true, 'status' => 'removed', 'message' => 'Removed from wishlist']);
        } else {
            // Add
            $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (:uid, :pid)");
            $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
            echo json_encode(['success' => true, 'status' => 'added', 'message' => 'Added to wishlist']);
        }
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
        echo json_encode(['success' => true, 'message' => 'Item removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
