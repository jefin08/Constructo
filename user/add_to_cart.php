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
        // Prepare SQL statement to insert into cart
        $stmt = $conn->prepare("INSERT INTO cart (client_id, product_id, quantity) VALUES (:client_id, :product_id, 1)");
        $stmt->bindParam(':client_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Product added to cart']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error adding to cart.']);
        }
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Product ID not provided']);
}
?>
