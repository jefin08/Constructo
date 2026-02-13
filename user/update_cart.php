<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['product_id']) && isset($data['quantity'])) {
    $product_id = intval($data['product_id']);
    $quantity = intval($data['quantity']);

    if ($quantity < 1) {
        $quantity = 1; // Minimum quantity is 1
    }

    try {
        $stmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE client_id = :client_id AND product_id = :product_id");
        $stmt->execute([
            ':quantity' => $quantity,
            ':client_id' => $user_id,
            ':product_id' => $product_id
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Cart updated']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}
?>