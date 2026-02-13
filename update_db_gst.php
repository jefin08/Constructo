<?php
include 'db_connect.php';

try {
    // Attempt to add the column, catch error if exists (PostgreSQL specific usually, but works for checking)
    // For universal PDO, it is harder. Let's just try to ADD and ignore if it fails.
    $sql = "ALTER TABLE products ADD COLUMN gst_rate DECIMAL(5,2) DEFAULT 18.00";
    $conn->exec($sql);
    echo "Added gst_rate column.<br>";
} catch (PDOException $e) {
    echo "Column likely already exists or error: " . $e->getMessage();
}
?>