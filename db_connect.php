<?php
// db_connect.php

// Load .env file if it exists (for local development)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0)
            continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database configuration for PostgreSQL
$host = getenv('DB_HOST') ?: "localhost";
$port = getenv('DB_PORT') ?: "5432";
$dbname = getenv('DB_NAME') ?: "constructo";
$user = getenv('DB_USER') ?: "postgres";
$password = getenv('DB_PASSWORD') ?: "postgres";

// Storage Configuration
// Determine if we are in admin or user directory
$current_dir = basename(getcwd());
if ($current_dir === 'admin') {
    define('STORAGE_URL', 'uploaded_images/');
} elseif ($current_dir === 'user') {
    define('STORAGE_URL', '../admin/uploaded_images/');
} else {
    // Root directory e.g. index.php
    define('STORAGE_URL', 'admin/uploaded_images/');
}
// For backward compatibility
if (!defined('SUPABASE_STORAGE_URL')) {
    define('SUPABASE_STORAGE_URL', STORAGE_URL);
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";

    // Create PDO connection
    $conn = new PDO($dsn, $user, $password);

    // Set errormode to exceptions
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Print error message
    // On Vercel, logs are captured.
    error_log("Connection failed: " . $e->getMessage());
    echo "Connection failed. Please check logs.";
    exit();
}
?>