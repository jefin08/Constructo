<?php
// db_connect.php

// Database configuration for Supabase (PostgreSQL)
$host = "db.yyeploxrzxwhhsnffexp.supabase.co";
$port = "5432";
$dbname = "postgres";
$user = "postgres";
$password = "jen#1212#code";

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
