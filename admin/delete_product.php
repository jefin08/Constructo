<?php
// delete_product.php
include '../db_connect.php';

$id = $_GET['id'];

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

try {
    $sql = "DELETE FROM products WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: view_products.php'); // Redirect to product list after deletion
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
