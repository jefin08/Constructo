<?php
session_start();

// Database connection settings
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id']; // Get the client ID from session

// Get product ID from request
$data = json_decode(file_get_contents('php://input'), true); // Decode JSON input
if (isset($data['product_id'])) {
    $product_id = intval($data['product_id']); // Access the product_id

    try {
        // Check if product is already in cart
        $checkStmt = $conn->prepare("SELECT quantity FROM cart WHERE client_id = :client_id AND product_id = :product_id");
        $checkStmt->bindParam(':client_id', $user_id);
        $checkStmt->bindParam(':product_id', $product_id);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            // Update quantity
            $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + 1;

            $updateStmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE client_id = :client_id AND product_id = :product_id");
            $updateStmt->bindParam(':quantity', $new_quantity);
            $updateStmt->bindParam(':client_id', $user_id);
            $updateStmt->bindParam(':product_id', $product_id);

            if ($updateStmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Cart updated']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error updating cart']);
            }
        } else {
            // Insert into cart
            $stmt = $conn->prepare("INSERT INTO cart (client_id, product_id, quantity) VALUES (:client_id, :product_id, 1)");
            $stmt->bindParam(':client_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Product added to cart']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error adding to cart.']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Product ID not provided']);
}
?>