<?php
// db_connect.php

// Database configuration for Supabase (PostgreSQL)
$host = "aws-1-ap-south-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.yyeploxrzxwhhsnffexp";
$password = "jen#159300#";

// Supabase Storage Configuration
// REPLACE 'images' WITH YOUR ACTUAL BUCKET NAME IF DIFFERENT
define('SUPABASE_STORAGE_URL', "https://yyeploxrzxwhhsnffexp.supabase.co/storage/v1/object/public/Construct_image/");

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    
    // Create PDO connection
    $conn = new PDO($dsn, $user, $password);
    
    // Set errormode to exceptions
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Print error message
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>
