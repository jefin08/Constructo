<?php
include 'db_connect.php';

try {
    // Check if column exists
    $sql = "SHOW COLUMNS FROM products LIKE 'shipping_cost'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        // Add column if it doesn't exist
        $sql = "ALTER TABLE products ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00";
        $conn->exec($sql);
        echo "Successfully added shipping_cost column to products table.<br>";
    } else {
        echo "Column shipping_cost already exists.<br>";
    }

} catch (PDOException $e) {
    // Fallback for systems where SHOW COLUMNS might fail or if using different DB driver
    try {
        $sql = "ALTER TABLE products ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00";
        $conn->exec($sql);
        echo "Added shipping_cost column.<br>";
    } catch (PDOException $e2) {
        echo "Column likely exists or error: " . $e2->getMessage();
    }
}
?>